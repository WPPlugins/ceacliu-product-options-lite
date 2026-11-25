<?php
/**
 * Option groups: a private post type, its data and which products a group applies to.
 *
 * A group holds fields; a field is one of:
 *   checkbox – several choices, each adds its price (toppings, extras)
 *   radio    – one choice, adds its price (sauce, side)
 *   select   – like radio, as a drop-down
 *   textarea – free remarks from the customer
 *
 * The data is stored the same way as in the full version (Ceacliu Product Options),
 * so the groups carry over when a shop moves to it.
 *
 * @package Ceacliu_Product_Options_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ceacliu_POL_Groups {

	const CPT   = 'ceacliu_po_group';
	const CACHE = 'ceacliu_pol_groups';
	const TYPES = array( 'checkbox', 'radio', 'select', 'textarea' );

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		foreach ( array( 'save_post_' . self::CPT, 'deleted_post', 'trashed_post', 'untrashed_post' ) as $hook ) {
			add_action( $hook, array( __CLASS__, 'flush' ) );
		}
	}

	public static function register() {
		register_post_type(
			self::CPT,
			array(
				'labels'          => array(
					'name'               => __( 'Product options', 'ceacliu-product-options-lite' ),
					'singular_name'      => __( 'Option group', 'ceacliu-product-options-lite' ),
					'menu_name'          => __( 'Product options', 'ceacliu-product-options-lite' ),
					'all_items'          => __( 'Product options', 'ceacliu-product-options-lite' ),
					'add_new'            => __( 'Add option group', 'ceacliu-product-options-lite' ),
					'add_new_item'       => __( 'Add option group', 'ceacliu-product-options-lite' ),
					'edit_item'          => __( 'Edit option group', 'ceacliu-product-options-lite' ),
					'new_item'           => __( 'New option group', 'ceacliu-product-options-lite' ),
					'search_items'       => __( 'Search option groups', 'ceacliu-product-options-lite' ),
					'not_found'          => __( 'No option groups yet.', 'ceacliu-product-options-lite' ),
					'not_found_in_trash' => __( 'No option groups in the trash.', 'ceacliu-product-options-lite' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => 'edit.php?post_type=product',
				'supports'        => array( 'title', 'page-attributes' ),
				'capability_type' => 'product',
				'map_meta_cap'    => true,
				'rewrite'         => false,
				'query_var'       => false,
			)
		);
	}

	public static function flush() {
		delete_transient( self::CACHE );
	}

	/** All published groups, in menu order. */
	public static function all() {
		$cached = get_transient( self::CACHE );
		if ( is_array( $cached ) ) {
			return $cached;
		}
		$out   = array();
		$posts = get_posts(
			array(
				'post_type'      => self::CPT,
				'post_status'    => 'publish',
				'posts_per_page' => 200,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'ID'         => 'ASC',
				),
			)
		);
		foreach ( $posts as $p ) {
			$out[] = self::load( $p->ID, $p->post_title );
		}
		set_transient( self::CACHE, $out, DAY_IN_SECONDS );
		return $out;
	}

	public static function load( $id, $title = null ) {
		$fields = json_decode( (string) get_post_meta( $id, '_ceacliu_po_fields', true ), true );
		$rules  = json_decode( (string) get_post_meta( $id, '_ceacliu_po_rules', true ), true );
		return array(
			'id'     => (int) $id,
			'title'  => null === $title ? get_the_title( $id ) : $title,
			'rules'  => self::clean_rules( is_array( $rules ) ? $rules : array() ),
			'fields' => self::clean_fields( is_array( $fields ) ? $fields : array() ),
		);
	}

	/** The groups shown on a product (a variation counts as its parent). */
	public static function for_product( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return array();
		}
		$pid = (int) ( $product->get_parent_id() ? $product->get_parent_id() : $product->get_id() );
		$out = array();
		foreach ( self::all() as $g ) {
			if ( $g['fields'] && ( $g['rules']['all'] || in_array( $pid, $g['rules']['products'], true ) ) ) {
				$out[] = $g;
			}
		}
		return apply_filters( 'ceacliu_pol_groups_for_product', $out, $product );
	}

	/** Every field shown on the product, keyed by field id. */
	public static function fields_for_product( $product ) {
		$out = array();
		foreach ( self::for_product( $product ) as $g ) {
			foreach ( $g['fields'] as $f ) {
				$out[ $f['id'] ] = $f;
			}
		}
		return $out;
	}

	public static function has_required( $product ) {
		foreach ( self::fields_for_product( $product ) as $f ) {
			if ( $f['required'] ) {
				return true;
			}
		}
		return false;
	}

	public static function clean_rules( $r ) {
		return array(
			'all'      => ! empty( $r['all'] ),
			'products' => array_values( array_unique( array_filter( array_map( 'absint', (array) ( isset( $r['products'] ) ? $r['products'] : array() ) ) ) ) ),
		);
	}

	public static function num( $v ) {
		$v = str_replace( array( ' ', ',' ), array( '', '.' ), (string) $v );
		return is_numeric( $v ) ? max( 0, round( (float) $v, 4 ) ) : 0.0;
	}

	public static function clean_fields( $fields ) {
		$out  = array();
		$seen = array();
		foreach ( (array) $fields as $f ) {
			if ( ! is_array( $f ) || ! isset( $f['type'] ) || ! in_array( $f['type'], self::TYPES, true ) ) {
				continue; // field types of the full version are left out here
			}
			$type  = $f['type'];
			$label = sanitize_text_field( isset( $f['label'] ) ? $f['label'] : '' );
			$id    = isset( $f['id'] ) ? preg_replace( '/[^a-z0-9]/', '', strtolower( (string) $f['id'] ) ) : '';
			if ( '' === $id || isset( $seen[ $id ] ) ) {
				$id = 'f' . substr( md5( wp_rand() . $label . count( $out ) ), 0, 8 );
			}
			$seen[ $id ] = true;
			$choices     = array();
			if ( 'textarea' !== $type ) {
				foreach ( (array) ( isset( $f['choices'] ) ? $f['choices'] : array() ) as $c ) {
					$cl = sanitize_text_field( isset( $c['label'] ) ? $c['label'] : '' );
					if ( '' === $cl ) {
						continue;
					}
					$choices[] = array(
						'label'   => $cl,
						'price'   => self::num( isset( $c['price'] ) ? $c['price'] : 0 ),
						'default' => ! empty( $c['default'] ),
					);
				}
				if ( ! $choices ) {
					continue; // a list without choices shows nothing
				}
				if ( 'checkbox' !== $type ) { // one choice only: keep the first default
					$has = false;
					foreach ( $choices as $i => $c ) {
						$choices[ $i ]['default'] = $c['default'] && ! $has;
						$has                      = $has || $c['default'];
					}
				}
			}
			$out[] = array(
				'id'       => $id,
				'type'     => $type,
				'label'    => '' === $label ? __( 'Options', 'ceacliu-product-options-lite' ) : $label,
				'desc'     => sanitize_text_field( isset( $f['desc'] ) ? $f['desc'] : '' ),
				'required' => 'checkbox' !== $type && ! empty( $f['required'] ),
				'max'      => 'checkbox' === $type ? absint( isset( $f['max'] ) ? $f['max'] : 0 ) : 0,
				'maxlen'   => 'textarea' === $type ? min( 1000, absint( isset( $f['maxlen'] ) ? $f['maxlen'] : 0 ) ) : 0,
				'choices'  => $choices,
			);
		}
		return $out;
	}
}

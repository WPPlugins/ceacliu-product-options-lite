<?php
/**
 * Admin: the option group editor, where it shows, the list columns and a box on the product.
 *
 * @package Ceacliu_Product_Options_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ceacliu_POL_Admin {

	public static function init() {
		add_action( 'add_meta_boxes_' . Ceacliu_POL_Groups::CPT, array( __CLASS__, 'boxes' ) );
		add_action( 'add_meta_boxes_product', array( __CLASS__, 'product_box' ) );
		add_action( 'save_post_' . Ceacliu_POL_Groups::CPT, array( __CLASS__, 'save' ), 5 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'manage_' . Ceacliu_POL_Groups::CPT . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . Ceacliu_POL_Groups::CPT . '_posts_custom_column', array( __CLASS__, 'column' ), 10, 2 );
		add_filter( 'plugin_action_links_' . plugin_basename( CEACLIU_POL_FILE ), array( __CLASS__, 'links' ) );
		add_filter( 'post_updated_messages', array( __CLASS__, 'messages' ) );
		add_filter( 'enter_title_here', array( __CLASS__, 'title_hint' ), 10, 2 );
	}

	public static function links( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'edit.php?post_type=' . Ceacliu_POL_Groups::CPT ) ) . '">' . esc_html__( 'Option groups', 'ceacliu-product-options-lite' ) . '</a>' );
		$links[] = '<a href="' . esc_url( CEACLIU_POL_PRO_URL ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'Full version', 'ceacliu-product-options-lite' ) . '</a>';
		return $links;
	}

	public static function title_hint( $text, $post ) {
		return Ceacliu_POL_Groups::CPT === $post->post_type ? __( 'Group name, e.g. Extra toppings', 'ceacliu-product-options-lite' ) : $text;
	}

	public static function messages( $m ) {
		$m[ Ceacliu_POL_Groups::CPT ]    = array_fill( 0, 11, __( 'Option group saved.', 'ceacliu-product-options-lite' ) );
		$m[ Ceacliu_POL_Groups::CPT ][0] = '';
		return $m;
	}

	public static function assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || Ceacliu_POL_Groups::CPT !== $screen->post_type || ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		wp_enqueue_style( 'ceacliu-pol-admin', CEACLIU_POL_URL . 'assets/admin.css', array(), CEACLIU_POL_VERSION );
		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_script( 'ceacliu-pol-admin', CEACLIU_POL_URL . 'assets/admin.js', array( 'jquery', 'wc-enhanced-select' ), CEACLIU_POL_VERSION, true );
		wp_localize_script(
			'ceacliu-pol-admin',
			'ceacliuPOLAdmin',
			array(
				'types' => array(
					'checkbox' => __( 'Checkboxes (several choices – extras, toppings)', 'ceacliu-product-options-lite' ),
					'radio'    => __( 'Radio buttons (one choice – sauce, side)', 'ceacliu-product-options-lite' ),
					'select'   => __( 'Drop-down (one choice)', 'ceacliu-product-options-lite' ),
					'textarea' => __( 'Remarks (free text from the customer)', 'ceacliu-product-options-lite' ),
				),
				't'     => array(
					'addField'  => __( 'Add field', 'ceacliu-product-options-lite' ),
					'addChoice' => __( 'Add choice', 'ceacliu-product-options-lite' ),
					'label'     => __( 'Field name', 'ceacliu-product-options-lite' ),
					'labelHint' => __( 'e.g. Extra toppings', 'ceacliu-product-options-lite' ),
					'desc'      => __( 'Help text (optional)', 'ceacliu-product-options-lite' ),
					'required'  => __( 'Required', 'ceacliu-product-options-lite' ),
					'max'       => __( 'At most (0 = no limit)', 'ceacliu-product-options-lite' ),
					'priceAdd'  => __( 'Adds to the price', 'ceacliu-product-options-lite' ),
					'default'   => __( 'Selected', 'ceacliu-product-options-lite' ),
					'choice'    => __( 'Choice', 'ceacliu-product-options-lite' ),
					'maxlen'    => __( 'Maximum characters (0 = no limit)', 'ceacliu-product-options-lite' ),
					'remove'    => __( 'Remove', 'ceacliu-product-options-lite' ),
					'up'        => __( 'Move up', 'ceacliu-product-options-lite' ),
					'down'      => __( 'Move down', 'ceacliu-product-options-lite' ),
					'confirm'   => __( 'Remove this field and its choices?', 'ceacliu-product-options-lite' ),
					'empty'     => __( 'No fields yet. Add the first one below.', 'ceacliu-product-options-lite' ),
					'type'      => __( 'Type', 'ceacliu-product-options-lite' ),
				),
			)
		);
	}

	public static function boxes() {
		add_meta_box( 'ceacliu-pol-fields', __( 'Fields', 'ceacliu-product-options-lite' ), array( __CLASS__, 'box_fields' ), null, 'normal', 'high' );
		add_meta_box( 'ceacliu-pol-rules', __( 'Where it shows', 'ceacliu-product-options-lite' ), array( __CLASS__, 'box_rules' ), null, 'side', 'default' );
	}

	public static function box_fields( $post ) {
		$g    = Ceacliu_POL_Groups::load( $post->ID );
		$json = wp_json_encode( $g['fields'] );
		wp_nonce_field( 'ceacliu_pol_save', 'ceacliu_pol_nonce' );
		echo '<div id="ceacliu-pol-editor" data-fields="' . esc_attr( $json ) . '"></div>';
		echo '<input type="hidden" name="ceacliu_pol_fields" id="ceacliu-pol-json" value="' . esc_attr( $json ) . '">';
		echo '<p class="description">' . esc_html__( 'The price of each chosen option is added to the product price, for every piece.', 'ceacliu-product-options-lite' ) . '</p>';
	}

	public static function box_rules( $post ) {
		$r = Ceacliu_POL_Groups::load( $post->ID )['rules'];
		echo '<p><label><input type="checkbox" name="ceacliu_pol_rules[all]" value="1"' . checked( $r['all'], true, false ) . '> ' . esc_html__( 'All products', 'ceacliu-product-options-lite' ) . '</label></p>';
		echo '<p><strong>' . esc_html__( 'Products', 'ceacliu-product-options-lite' ) . '</strong><br><select class="wc-product-search" multiple="multiple" style="width:100%" name="ceacliu_pol_rules[products][]" data-placeholder="' . esc_attr__( 'Search for a product…', 'ceacliu-product-options-lite' ) . '" data-action="woocommerce_json_search_products">';
		foreach ( $r['products'] as $id ) {
			$p = wc_get_product( $id );
			if ( $p ) {
				echo '<option value="' . (int) $id . '" selected>' . esc_html( wp_strip_all_tags( $p->get_formatted_name() ) ) . '</option>';
			}
		}
		echo '</select></p>';
		echo '<p class="description">' . esc_html__( 'Several groups on one product are shown in the "Order" number set above (Page Attributes).', 'ceacliu-product-options-lite' ) . '</p>';
	}

	public static function save( $post_id ) {
		if ( ! isset( $_POST['ceacliu_pol_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ceacliu_pol_nonce'] ) ), 'ceacliu_pol_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$fields = json_decode( isset( $_POST['ceacliu_pol_fields'] ) ? wp_unslash( $_POST['ceacliu_pol_fields'] ) : '[]', true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON, every value is sanitized in clean_fields().
		$rules  = isset( $_POST['ceacliu_pol_rules'] ) && is_array( $_POST['ceacliu_pol_rules'] ) ? map_deep( wp_unslash( $_POST['ceacliu_pol_rules'] ), 'sanitize_text_field' ) : array();
		update_post_meta( $post_id, '_ceacliu_po_fields', wp_slash( wp_json_encode( Ceacliu_POL_Groups::clean_fields( is_array( $fields ) ? $fields : array() ) ) ) );
		update_post_meta( $post_id, '_ceacliu_po_rules', wp_slash( wp_json_encode( Ceacliu_POL_Groups::clean_rules( $rules ) ) ) );
		Ceacliu_POL_Groups::flush();
	}

	public static function columns( $cols ) {
		$new = array();
		foreach ( $cols as $k => $v ) {
			$new[ $k ] = $v;
			if ( 'title' === $k ) {
				$new['ceacliu_pol_fields'] = __( 'Fields', 'ceacliu-product-options-lite' );
				$new['ceacliu_pol_where']  = __( 'Shows on', 'ceacliu-product-options-lite' );
			}
		}
		return $new;
	}

	public static function column( $col, $post_id ) {
		$g = Ceacliu_POL_Groups::load( $post_id );
		if ( 'ceacliu_pol_fields' === $col ) {
			echo esc_html( implode( ', ', wp_list_pluck( $g['fields'], 'label' ) ) );
		} elseif ( 'ceacliu_pol_where' === $col ) {
			if ( $g['rules']['all'] ) {
				echo esc_html__( 'All products', 'ceacliu-product-options-lite' );
			} elseif ( $g['rules']['products'] ) {
				echo esc_html( implode( ', ', array_map( 'get_the_title', $g['rules']['products'] ) ) );
			} else {
				echo esc_html__( 'Nowhere yet – choose products', 'ceacliu-product-options-lite' );
			}
		}
	}

	/* --------------------------------------------- box on the product screen */

	public static function product_box() {
		add_meta_box( 'ceacliu-pol-product', __( 'Product options', 'ceacliu-product-options-lite' ), array( __CLASS__, 'box_product' ), 'product', 'side', 'low' );
	}

	public static function box_product( $post ) {
		$product = wc_get_product( $post->ID );
		$groups  = $product ? Ceacliu_POL_Groups::for_product( $product ) : array();
		if ( ! $groups ) {
			echo '<p>' . esc_html__( 'No option groups on this product.', 'ceacliu-product-options-lite' ) . '</p>';
		} else {
			echo '<ul>';
			foreach ( $groups as $g ) {
				echo '<li><a href="' . esc_url( get_edit_post_link( $g['id'] ) ) . '">' . esc_html( $g['title'] ) . '</a></li>';
			}
			echo '</ul>';
		}
		echo '<p><a href="' . esc_url( admin_url( 'edit.php?post_type=' . Ceacliu_POL_Groups::CPT ) ) . '">' . esc_html__( 'All option groups', 'ceacliu-product-options-lite' ) . '</a></p>';
	}
}

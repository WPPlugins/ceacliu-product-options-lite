<?php
/**
 * Shop side: the fields on the product page, checking them, the price in the cart and the lines on the order.
 *
 * @package Ceacliu_Product_Options_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Ceacliu_POL_Front {

	const KEY = 'ceacliu_po';

	/** Cart items already priced in this request (calculate_totals can run several times). */
	private static $priced = array();

	public static function init() {
		add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'validate' ), 10, 2 );
		add_filter( 'woocommerce_add_cart_item_data', array( __CLASS__, 'cart_item_data' ), 10, 3 );
		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'price' ), 20 );
		add_filter( 'woocommerce_get_item_data', array( __CLASS__, 'item_data' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'order_item' ), 10, 3 );
		add_filter( 'woocommerce_order_again_cart_item_data', array( __CLASS__, 'order_again' ), 10, 2 );
		add_filter( 'woocommerce_hidden_order_itemmeta', array( __CLASS__, 'hidden_meta' ) );
		// Products with required options: the shop button opens the product instead of adding it.
		add_filter( 'woocommerce_product_add_to_cart_url', array( __CLASS__, 'loop_url' ), 20, 2 );
		add_filter( 'woocommerce_product_add_to_cart_text', array( __CLASS__, 'loop_text' ), 20, 2 );
		add_filter( 'woocommerce_product_supports', array( __CLASS__, 'loop_ajax' ), 20, 3 );
	}

	public static function money( $amount ) {
		return html_entity_decode( wp_strip_all_tags( wc_price( $amount ) ), ENT_QUOTES, 'UTF-8' );
	}

	private static function needs_choice( $product ) {
		return $product instanceof WC_Product && ! $product->is_type( array( 'variable', 'grouped', 'external' ) ) && Ceacliu_POL_Groups::has_required( $product );
	}

	/* ------------------------------------------------------------ product page */

	public static function assets() {
		if ( ! is_product() ) {
			return;
		}
		wp_enqueue_style( 'ceacliu-pol-front', CEACLIU_POL_URL . 'assets/front.css', array(), CEACLIU_POL_VERSION );
		wp_enqueue_script(
			'ceacliu-pol-front',
			CEACLIU_POL_URL . 'assets/front.js',
			array( 'jquery' ),
			CEACLIU_POL_VERSION,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);
		wp_localize_script(
			'ceacliu-pol-front',
			'ceacliuPOL',
			array(
				'decimals' => wc_get_price_decimals(),
				'dec'      => wc_get_price_decimal_separator(),
				'thou'     => wc_get_price_thousand_separator(),
				'format'   => html_entity_decode( get_woocommerce_price_format(), ENT_QUOTES, 'UTF-8' ),
				'symbol'   => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
				'required' => __( 'Please choose an option.', 'ceacliu-product-options-lite' ),
				'fill'     => __( 'Please fill this in.', 'ceacliu-product-options-lite' ),
			)
		);
	}

	public static function render() {
		global $product;
		if ( ! $product instanceof WC_Product ) {
			return;
		}
		$groups = Ceacliu_POL_Groups::for_product( $product );
		if ( ! $groups ) {
			return;
		}
		$base = $product->is_type( 'variable' ) ? '' : wc_get_price_to_display( $product );
		echo '<div class="ceacliu-pol" data-base="' . esc_attr( $base ) . '">';
		foreach ( $groups as $g ) {
			echo '<div class="cpol-group">';
			if ( count( $g['fields'] ) > 1 ) { // one field: its own name is enough
				echo '<div class="cpol-group-title">' . esc_html( $g['title'] ) . '</div>';
			}
			foreach ( $g['fields'] as $f ) {
				self::render_field( $f );
			}
			echo '</div>';
		}
		echo '<div class="cpol-total" hidden><span>' . esc_html__( 'Total', 'ceacliu-product-options-lite' ) . '</span> <strong></strong></div>';
		echo '</div>';
	}

	private static function posted( $id ) {
		// WooCommerce's add-to-cart form has no nonce; every value is checked against the saved group in read().
		return isset( $_POST[ self::KEY ][ $id ] ) ? wp_unslash( $_POST[ self::KEY ][ $id ] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	}

	private static function render_field( $f ) {
		$name   = self::KEY . '[' . $f['id'] . ']';
		$posted = self::posted( $f['id'] );
		printf(
			'<div class="cpol-field cpol-%1$s" data-max="%2$d" data-required="%3$d">',
			esc_attr( $f['type'] ),
			(int) $f['max'],
			$f['required'] ? 1 : 0
		);
		echo '<div class="cpol-label">' . esc_html( $f['label'] );
		if ( $f['required'] ) {
			echo ' <abbr class="required" title="' . esc_attr__( 'required', 'ceacliu-product-options-lite' ) . '">*</abbr>';
		}
		if ( $f['max'] ) {
			/* translators: %d: how many choices at most. */
			echo ' <span class="cpol-hint">(' . esc_html( sprintf( __( 'up to %d', 'ceacliu-product-options-lite' ), $f['max'] ) ) . ')</span>';
		}
		echo '</div>';
		if ( '' !== $f['desc'] ) {
			echo '<div class="cpol-desc">' . esc_html( $f['desc'] ) . '</div>';
		}

		if ( 'textarea' === $f['type'] ) {
			$val = is_string( $posted ) ? $posted : '';
			echo '<textarea class="cpol-input" rows="2" name="' . esc_attr( $name ) . '"' . ( $f['maxlen'] ? ' maxlength="' . (int) $f['maxlen'] . '"' : '' ) . '>' . esc_textarea( $val ) . '</textarea>';
			echo '<div class="cpol-error" hidden></div></div>';
			return;
		}

		$chosen = array();
		if ( null !== $posted ) {
			$chosen = array_map( 'intval', (array) $posted );
		} else {
			foreach ( $f['choices'] as $i => $c ) {
				if ( $c['default'] ) {
					$chosen[] = $i;
				}
			}
		}

		if ( 'select' === $f['type'] ) {
			echo '<select class="cpol-input" name="' . esc_attr( $name ) . '"><option value="">' . esc_html__( 'Choose…', 'ceacliu-product-options-lite' ) . '</option>';
			foreach ( $f['choices'] as $i => $c ) {
				printf(
					'<option value="%1$d" data-price="%2$s"%3$s>%4$s%5$s</option>',
					(int) $i,
					esc_attr( $c['price'] ),
					in_array( $i, $chosen, true ) ? ' selected' : '',
					esc_html( $c['label'] ),
					$c['price'] ? esc_html( ' (+' . self::money( $c['price'] ) . ')' ) : ''
				);
			}
			echo '</select><div class="cpol-error" hidden></div></div>';
			return;
		}

		$input = 'checkbox' === $f['type'] ? 'checkbox' : 'radio';
		echo '<div class="cpol-choices">';
		foreach ( $f['choices'] as $i => $c ) {
			printf(
				'<label class="cpol-choice"><input type="%1$s" name="%2$s" value="%3$d" data-price="%4$s"%5$s> <span class="cpol-name">%6$s</span> <span class="cpol-price">%7$s</span></label>',
				esc_attr( $input ),
				esc_attr( $name . ( 'checkbox' === $input ? '[]' : '' ) ),
				(int) $i,
				esc_attr( $c['price'] ),
				in_array( $i, $chosen, true ) ? ' checked' : '',
				esc_html( $c['label'] ),
				esc_html( $c['price'] ? '+' . self::money( $c['price'] ) : '' )
			);
		}
		echo '</div><div class="cpol-error" hidden></div></div>';
	}

	/* ------------------------------------------------------- reading a choice */

	/**
	 * Turns the posted values into a clean selection, or a list of error messages.
	 *
	 * @return array{0: array, 1: string[]} [ selection keyed by field id, errors ]
	 */
	public static function read( $product, $source = null ) {
		$sel    = array();
		$errors = array();
		foreach ( Ceacliu_POL_Groups::fields_for_product( $product ) as $id => $f ) {
			$raw = null === $source ? self::posted( $id ) : ( isset( $source[ $id ] ) ? $source[ $id ] : null );
			if ( 'textarea' === $f['type'] ) {
				$text = is_scalar( $raw ) ? trim( sanitize_textarea_field( (string) $raw ) ) : '';
				if ( $f['maxlen'] ) {
					$text = trim( mb_substr( $text, 0, $f['maxlen'] ) );
				}
				if ( $f['required'] && '' === $text ) {
					/* translators: %s: field name. */
					$errors[] = sprintf( __( 'Please fill in: %s.', 'ceacliu-product-options-lite' ), $f['label'] );
				}
				if ( '' !== $text ) {
					$sel[ $id ] = $text;
				}
				continue;
			}
			$picked = array();
			foreach ( (array) $raw as $v ) {
				if ( is_scalar( $v ) && '' !== $v && ctype_digit( (string) $v ) && isset( $f['choices'][ (int) $v ] ) ) {
					$picked[ (int) $v ] = true;
				}
			}
			$picked = array_keys( $picked );
			if ( 'checkbox' !== $f['type'] ) {
				$picked = array_slice( $picked, 0, 1 );
			}
			$n = count( $picked );
			if ( $f['required'] && ! $n ) {
				/* translators: %s: field name, e.g. Sauce. */
				$errors[] = sprintf( __( 'Please choose: %s.', 'ceacliu-product-options-lite' ), $f['label'] );
			} elseif ( $f['max'] && $n > $f['max'] ) {
				/* translators: 1: how many, 2: field name. */
				$errors[] = sprintf( __( 'You can choose at most %1$d for %2$s.', 'ceacliu-product-options-lite' ), $f['max'], $f['label'] );
			}
			if ( $n ) {
				$sel[ $id ] = $picked;
			}
		}
		return array( $sel, $errors );
	}

	/**
	 * What a selection costs and how it reads.
	 *
	 * @return array{extra: float, lines: array} extra is added to each piece.
	 */
	public static function summarize( $product, $sel ) {
		$extra = 0.0;
		$lines = array();
		foreach ( Ceacliu_POL_Groups::fields_for_product( $product ) as $id => $f ) {
			if ( ! isset( $sel[ $id ] ) ) {
				continue;
			}
			if ( 'textarea' === $f['type'] ) {
				$lines[] = array(
					'label' => $f['label'],
					'value' => $sel[ $id ],
				);
				continue;
			}
			$parts = array();
			foreach ( $sel[ $id ] as $i ) {
				$c       = $f['choices'][ $i ];
				$extra  += $c['price'];
				$parts[] = $c['label'] . ( $c['price'] ? ' (+' . self::money( $c['price'] ) . ')' : '' );
			}
			$lines[] = array(
				'label' => $f['label'],
				'value' => implode( ', ', $parts ),
			);
		}
		return array(
			'extra' => $extra,
			'lines' => $lines,
		);
	}

	private static function pack( $product, $sel ) {
		$sum = self::summarize( $product, $sel );
		return array(
			'sel'   => $sel,
			'base'  => null,
			'extra' => $sum['extra'],
			'lines' => $sum['lines'],
		);
	}

	/* ------------------------------------------------------------------- cart */

	private static function order_again_running() {
		return isset( $_GET['order_again'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only tells which flow is running; WooCommerce checks its own nonce.
	}

	public static function validate( $passed, $product_id ) {
		if ( ! $passed || self::order_again_running() ) {
			return $passed;
		}
		$product = wc_get_product( $product_id );
		if ( ! $product || ! Ceacliu_POL_Groups::for_product( $product ) ) {
			return $passed;
		}
		list( , $errors ) = self::read( $product );
		foreach ( $errors as $e ) {
			wc_add_notice( $e, 'error' );
		}
		return $errors ? false : $passed;
	}

	public static function cart_item_data( $data, $product_id, $variation_id ) {
		if ( isset( $data[ self::KEY ] ) ) {
			return $data; // order again
		}
		$product = wc_get_product( $variation_id ? $variation_id : $product_id );
		if ( ! $product || ! Ceacliu_POL_Groups::for_product( $product ) ) {
			return $data;
		}
		list( $sel ) = self::read( $product );
		if ( $sel ) {
			$data[ self::KEY ] = self::pack( $product, $sel );
		}
		return $data;
	}

	public static function price( $cart ) {
		foreach ( $cart->get_cart() as $key => $item ) {
			if ( empty( $item[ self::KEY ] ) || ! isset( $item['data'] ) || ! $item['data'] instanceof WC_Product ) {
				continue;
			}
			$oid = spl_object_id( $item['data'] );
			if ( isset( self::$priced[ $oid ] ) ) {
				continue;
			}
			self::$priced[ $oid ] = true;
			// Always from the saved group settings, never from what the browser said the price was.
			list( $sel ) = self::read( $item['data'], (array) $item[ self::KEY ]['sel'] );
			$packed      = self::pack( $item['data'], $sel );
			$item['data']->set_price( (float) $item['data']->get_price( 'edit' ) + (float) $packed['extra'] );
			$cart->cart_contents[ $key ][ self::KEY ] = $packed;
		}
	}

	public static function item_data( $out, $item ) {
		if ( empty( $item[ self::KEY ]['lines'] ) ) {
			return $out;
		}
		foreach ( $item[ self::KEY ]['lines'] as $l ) {
			$out[] = array(
				'key'   => $l['label'],
				'value' => $l['value'],
			);
		}
		return $out;
	}

	public static function order_item( $item, $cart_key, $values ) {
		if ( empty( $values[ self::KEY ] ) ) {
			return;
		}
		$counts = array();
		foreach ( $values[ self::KEY ]['lines'] as $l ) {
			$k = $l['label'];
			if ( isset( $counts[ $k ] ) ) { // two fields with the same name stay two lines
				++$counts[ $k ];
				$k .= ' (' . $counts[ $k ] . ')';
			} else {
				$counts[ $k ] = 1;
			}
			$item->add_meta_data( $k, $l['value'] );
		}
		$item->add_meta_data( '_ceacliu_po', $values[ self::KEY ] );
	}

	public static function order_again( $data, $item ) {
		$saved = $item->get_meta( '_ceacliu_po' );
		if ( is_array( $saved ) && ! empty( $saved['sel'] ) ) {
			$product = $item->get_product();
			if ( $product ) { // today's prices, the same choices
				list( $sel ) = self::read( $product, $saved['sel'] );
				if ( $sel ) {
					$data[ self::KEY ] = self::pack( $product, $sel );
				}
			}
		}
		return $data;
	}

	public static function hidden_meta( $keys ) {
		$keys[] = '_ceacliu_po';
		return $keys;
	}

	/* ------------------------------------------------------------- shop pages */

	public static function loop_url( $url, $product ) {
		return self::needs_choice( $product ) ? $product->get_permalink() : $url;
	}

	public static function loop_text( $text, $product ) {
		return self::needs_choice( $product ) ? __( 'Choose options', 'ceacliu-product-options-lite' ) : $text;
	}

	public static function loop_ajax( $supports, $feature, $product ) {
		return ( 'ajax_add_to_cart' === $feature && self::needs_choice( $product ) ) ? false : $supports;
	}
}

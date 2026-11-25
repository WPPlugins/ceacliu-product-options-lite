<?php
/**
 * Plugin Name:       Ceacliu Product Options Lite
 * Plugin URI:        https://wordpress.org/plugins/ceacliu-product-options-lite/
 * Description:       Extra toppings, sauces, sides and remarks on WooCommerce products, with prices, a live total and the choices on the order and in the e-mails.
 * Version:           1.0.1
 * Author:            Ceacliu Studios
 * Author URI:        https://github.com/ceacliustudio
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ceacliu-product-options-lite
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * WC requires at least: 8.0
 * WC tested up to:   11.3
 *
 * @package Ceacliu_Product_Options_Lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CEACLIU_POL_VERSION', '1.0.1' );
define( 'CEACLIU_POL_FILE', __FILE__ );
define( 'CEACLIU_POL_DIR', plugin_dir_path( __FILE__ ) );
define( 'CEACLIU_POL_URL', plugin_dir_url( __FILE__ ) );
define( 'CEACLIU_POL_PRO_URL', 'https://www.codester.com/items/75713/ceacliu-woocommerce-product-options-and-toppings' );

require_once CEACLIU_POL_DIR . 'includes/class-ceacliu-pol-groups.php';
require_once CEACLIU_POL_DIR . 'includes/class-ceacliu-pol-front.php';
require_once CEACLIU_POL_DIR . 'includes/class-ceacliu-pol-admin.php';

add_action(
	'before_woocommerce_init',
	function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', CEACLIU_POL_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', CEACLIU_POL_FILE, true );
		}
	}
);

add_action(
	'plugins_loaded',
	function () {
		// The full version keeps the same option groups: when it is active, it does the work.
		if ( defined( 'CEACLIU_PO_VERSION' ) ) {
			return;
		}
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action(
				'admin_notices',
				function () {
					$screen = get_current_screen();
					if ( ! $screen || 'plugins' !== $screen->base || ! current_user_can( 'activate_plugins' ) ) {
						return;
					}

					echo '<div class="notice notice-error"><p>' . esc_html__( 'Ceacliu Product Options Lite needs WooCommerce. Please install and activate WooCommerce.', 'ceacliu-product-options-lite' ) . '</p></div>';
				}
			);
			return;
		}
		Ceacliu_POL_Groups::init();
		Ceacliu_POL_Front::init();
		if ( is_admin() ) {
			Ceacliu_POL_Admin::init();
		}
	}
);

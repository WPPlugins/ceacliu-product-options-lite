<?php
/**
 * Removes the option groups when the plugin is deleted, unless the full version
 * (which uses the same groups) is still installed. Orders keep their option lines.
 *
 * @package Ceacliu_Product_Options_Lite
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( file_exists( WP_PLUGIN_DIR . '/ceacliu-product-options/ceacliu-product-options.php' ) ) {
	return;
}

$ceacliu_pol_ids = get_posts(
	array(
		'post_type'      => 'ceacliu_po_group',
		'post_status'    => 'any',
		'posts_per_page' => -1,
		'fields'         => 'ids',
	)
);
foreach ( $ceacliu_pol_ids as $ceacliu_pol_id ) {
	wp_delete_post( $ceacliu_pol_id, true );
}
delete_transient( 'ceacliu_pol_groups' );

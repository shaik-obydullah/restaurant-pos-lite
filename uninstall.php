<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Restaurant_POS_Lite
 * @since   1.0.0
 */

// If uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;

// Delete plugin options
$oby_restaurant_pos_lite_options = array(
    'oby_restaurant_pos_lite_version',
    'oby_restaurant_pos_lite_currency',
    'oby_restaurant_pos_lite_tax_rate',
    'oby_restaurant_pos_lite_vat_rate',
    'oby_restaurant_pos_lite_shop_name',
    'oby_restaurant_pos_lite_shop_address',
    'oby_restaurant_pos_lite_shop_phone',
    'oby_restaurant_pos_lite_shop_logo',
    'oby_restaurant_pos_lite_currency_position',
    'oby_restaurant_pos_lite_date_format',
);

// Drop all tables in a loop
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
foreach ($oby_restaurant_pos_lite_options as $oby_restaurant_pos_lite_option) {
    delete_option($oby_restaurant_pos_lite_option);
    delete_site_option($oby_restaurant_pos_lite_option);
}

$oby_restaurant_pos_lite_tables = array(
    'pos_categories',
    'pos_products',
    'pos_stocks',
    'pos_stock_adjustments',
    'pos_customers',
    'pos_sales',
    'pos_sale_details',
    'pos_accounting'
);

// Drop all tables in a loop
foreach ($oby_restaurant_pos_lite_tables as $oby_restaurant_pos_lite_table) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}$oby_restaurant_pos_lite_table");
}

// Clear any cached data that might be related
wp_cache_flush();
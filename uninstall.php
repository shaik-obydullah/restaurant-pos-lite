<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Obydullah_Restaurant_POS_Lite
 * @since   1.0.0
 */

// If uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

global $wpdb;

// Delete plugin options
$orpl_options = array(
    'orpl_version',
    'orpl_currency',
    'orpl_tax_rate',
    'orpl_vat_rate',
    'orpl_shop_name',
    'orpl_shop_address',
    'orpl_shop_phone',
    'orpl_currency_position',
    'orpl_date_format',
);

// Drop all tables in a loop
foreach ($orpl_options as $orpl_option) {
    delete_option($orpl_option);
    delete_site_option($orpl_option);
}

$orpl_tables = array(
    'orpl_categories',
    'orpl_products',
    'orpl_stocks',
    'orpl_stock_adjustments',
    'orpl_customers',
    'orpl_sales',
    'orpl_sale_details',
    'orpl_accounting'
);

// Drop all tables in a loop
foreach ($orpl_tables as $orpl_table) {
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}$orpl_table");
}

// Clear any cached data that might be related
wp_cache_flush();
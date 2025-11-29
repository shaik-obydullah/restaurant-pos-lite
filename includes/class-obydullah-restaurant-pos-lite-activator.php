<?php
/**
 * Fired during plugin activation
 *
 * @package Obydullah_Restaurant_POS_Lite
 * @since   1.0.0
 */

class Obydullah_Restaurant_POS_Lite_Activator
{
    public static function activate()
    {
        global $wpdb;

        // Check permissions
        if (!current_user_can('activate_plugins')) {
            wp_die('You do not have sufficient permissions to activate this plugin.');
        }

        $charset_collate = $wpdb->get_charset_collate();

        $table_categories = $wpdb->prefix . 'orpl_categories';
        $table_products = $wpdb->prefix . 'orpl_products';
        $table_stocks = $wpdb->prefix . 'orpl_stocks';
        $table_stock_adjustments = $wpdb->prefix . 'orpl_stock_adjustments';
        $table_customers = $wpdb->prefix . 'orpl_customers';
        $table_sales = $wpdb->prefix . 'orpl_sales';
        $table_sale_details = $wpdb->prefix . 'orpl_sale_details';
        $table_accounting = $wpdb->prefix . 'orpl_accounting';

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $sql_categories = "CREATE TABLE $table_categories (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            status ENUM('active','inactive') DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_name (name)  
        ) $charset_collate;";

        $sql_products = "CREATE TABLE $table_products (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            fk_category_id BIGINT(20) UNSIGNED NOT NULL,
            image VARCHAR(255) DEFAULT NULL,
            status ENUM('active','inactive') DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_name (name),
            KEY fk_category_id (fk_category_id)
        ) $charset_collate;";

        $sql_customers = "CREATE TABLE $table_customers (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL,
            mobile VARCHAR(20) DEFAULT NULL,
            address VARCHAR(50) DEFAULT NULL,
            status ENUM('active','inactive') DEFAULT 'active',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        $sql_stocks = "CREATE TABLE $table_stocks (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            fk_product_id BIGINT(20) UNSIGNED NOT NULL,
            net_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            sale_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            quantity INT(11) NOT NULL DEFAULT 0,
            status ENUM('inStock','outStock','lowStock') DEFAULT 'inStock',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY fk_product_id (fk_product_id)
        ) $charset_collate;";

        $sql_sales = "CREATE TABLE $table_sales (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            fk_customer_id BIGINT(20) UNSIGNED DEFAULT NULL,
            invoice_id VARCHAR(30) DEFAULT NULL,
            net_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            vat_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            tax_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            shipping_cost DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            grand_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            paid_amount DECIMAL(10,2) DEFAULT NULL,
            buy_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            sale_type ENUM('dineIn','takeAway','pickUp') NOT NULL DEFAULT 'dineIn',
            cooking_instructions TEXT DEFAULT NULL,
            status ENUM('saveSale','completed','canceled') NOT NULL DEFAULT 'completed',
            note TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY fk_customer_id (fk_customer_id)
        ) $charset_collate;";

        $sql_stock_adjustments = "CREATE TABLE $table_stock_adjustments (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            fk_product_id BIGINT(20) UNSIGNED NOT NULL,
            adjustment_type ENUM('increase','decrease') NOT NULL,
            quantity INT(11) NOT NULL,
            note TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY fk_product_id (fk_product_id)
        ) $charset_collate;";

        $sql_sale_details = "CREATE TABLE $table_sale_details (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            fk_sale_id BIGINT(20) UNSIGNED NOT NULL,
            fk_product_id BIGINT(20) UNSIGNED NOT NULL,
            fk_stock_id BIGINT(20) UNSIGNED NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            quantity INT(11) NOT NULL DEFAULT 1,
            unit_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            total_price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY fk_sale_id (fk_sale_id),
            KEY fk_product_id (fk_product_id),
            KEY fk_stock_id (fk_stock_id)
        ) $charset_collate;";

        $sql_accounting = "CREATE TABLE $table_accounting (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            in_amount DECIMAL(10,2) DEFAULT NULL,
            out_amount DECIMAL(10,2) DEFAULT NULL,
            amount_payable DECIMAL(10,2) DEFAULT NULL,
            amount_receivable DECIMAL(10,2) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        dbDelta($sql_categories);
        dbDelta($sql_products);
        dbDelta($sql_customers);
        dbDelta($sql_stocks);
        dbDelta($sql_sales);
        dbDelta($sql_stock_adjustments);
        dbDelta($sql_sale_details);
        dbDelta($sql_accounting);

        // Insert default categories
        $default_categories = array(
            array('name' => 'Starter', 'status' => 'active'),
            array('name' => 'Main Dish', 'status' => 'active'),
            array('name' => 'Dessert', 'status' => 'active'),
            array('name' => 'Cold Drink', 'status' => 'active'),
            array('name' => 'Hot Drink', 'status' => 'active'),
            array('name' => 'Salad', 'status' => 'active'),
            array('name' => 'Vegetarian', 'status' => 'active'),
        );

        foreach ($default_categories as $category) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->insert(
                $table_categories,
                $category,
                array('%s', '%s')
            );
        }

        // Updated option names with 'orpl_' prefix
        update_option('orpl_version', '1.0.0');
        update_option('orpl_currency', 'USD');
        update_option('orpl_tax_rate', '0');
        update_option('orpl_vat_rate', '0');

        flush_rewrite_rules();
    }
}
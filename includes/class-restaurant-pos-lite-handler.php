<?php
if (!defined('ABSPATH'))
    exit;

require_once OBY_RESTAURANT_POS_LITE_PATH . 'includes/class-restaurant-pos-lite-dashboard.php';
require_once OBY_RESTAURANT_POS_LITE_PATH . 'includes/class-restaurant-pos-lite-helpers.php';
require_once OBY_RESTAURANT_POS_LITE_PATH . 'includes/class-restaurant-pos-lite-settings.php';

class Restaurant_POS_Lite_Handler
{
    public $dashboard;
    public $settings;

    public function __construct()
    {
        $this->dashboard = new Restaurant_POS_Lite_Dashboard();
        $this->settings = new Restaurant_POS_Lite_Settings();
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function register_admin_menu()
    {
        // Main menu
        add_menu_page(
            __('Restaurant POS', 'restaurant-pos-lite'),
            __('Restaurant POS', 'restaurant-pos-lite'),
            'manage_options',
            'wp-restaurant-pos-lite',
            [$this->dashboard, 'render_page'],
            'dashicons-store',
            25
        );

        // Dashboard submenu
        add_submenu_page(
            'wp-restaurant-pos-lite',
            __('Dashboard', 'restaurant-pos-lite'),
            __('Dashboard', 'restaurant-pos-lite'),
            'manage_options',
            'wp-restaurant-pos-lite',
            [$this->dashboard, 'render_page']
        );

        // Categories submenu
        // add_submenu_page(
        //     'wp-restaurant-pos-lite',
        //     __('Categories', 'restaurant-pos-lite'),
        //     __('Categories', 'restaurant-pos-lite'),
        //     'manage_options',
        //     'wp-restaurant-pos-lite-categories',
        //     [$this->categories, 'render_page']
        // );

        // Products submenu
        // add_submenu_page(
        //     'wp-restaurant-pos-lite',
        //     __('Products', 'restaurant-pos-lite'),
        //     __('Products', 'restaurant-pos-lite'),
        //     'manage_options',
        //     'wp-restaurant-pos-lite-products',
        //     [$this->products, 'render_page']
        // );

        // Stock submenu
        // add_submenu_page(
        //     'wp-restaurant-pos-lite',
        //     __('Stocks', 'restaurant-pos-lite'),
        //     __('Stocks', 'restaurant-pos-lite'),
        //     'manage_options',
        //     'wp-restaurant-pos-lite-stocks',
        //     [$this->stocks, 'render_page']
        // );

        // Stock Adjustments submenu
        // add_submenu_page(
        //     'wp-restaurant-pos-lite',
        //     __('Stock Adjustments', 'restaurant-pos-lite'),
        //     __('Stock Adjustments', 'restaurant-pos-lite'),
        //     'manage_options',
        //     'wp-restaurant-pos-lite-stock_adjustments',
        //     [$this->stock_adjustments, 'render_page']
        // );

        // Customer submenu
        // add_submenu_page(
        //     'wp-restaurant-pos-lite',
        //     __('Customers', 'restaurant-pos-lite'),
        //     __('Customers', 'restaurant-pos-lite'),
        //     'manage_options',
        //     'wp-restaurant-pos-lite-customers',
        //     [$this->customers, 'render_page']
        // );

        // POS submenu
        // add_submenu_page(
        //     'wp-restaurant-pos-lite',
        //     __('POS', 'restaurant-pos-lite'),
        //     __('POS', 'restaurant-pos-lite'),
        //     'manage_options',
        //     'wp-restaurant-pos-lite-pos',
        //     [$this->pos, 'render_page']
        // );

        // Sales submenu
        // add_submenu_page(
        //     'wp-restaurant-pos-lite',
        //     __('Sales', 'restaurant-pos-lite'),
        //     __('Sales', 'restaurant-pos-lite'),
        //     'manage_options',
        //     'wp-restaurant-pos-lite-sales',
        //     [$this->sales, 'render_page']
        // );

        // Accounting submenu
        // add_submenu_page(
        //     'wp-restaurant-pos-lite',
        //     __('Accounting', 'restaurant-pos-lite'),
        //     __('Accounting', 'restaurant-pos-lite'),
        //     'manage_options',
        //     'wp-restaurant-pos-lite-accounting',
        //     [$this->accounting, 'render_page']
        // );

        // Settings submenu
        add_submenu_page(
            'wp-restaurant-pos-lite',
            __('Settings', 'restaurant-pos-lite'),
            __('Settings', 'restaurant-pos-lite'),
            'manage_options',
            'wp-restaurant-pos-lite-settings',
            [$this->settings, 'render_page']
        );
    }

    public function enqueue_admin_scripts($hook)
    {
        if (strpos($hook, 'restaurant-pos-lite') !== false) {
            wp_enqueue_style(
                'restaurant-pos-lite-admin',
                OBY_RESTAURANT_POS_LITE_URL . 'assets/css/admin.css',
                [],
                '1.0.0'
            );
        }
    }
}
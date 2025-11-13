<?php
/**
 * Plugin Handler
 *
 * @package Restaurant_POS_Lite
 * @since   1.0.0
 */
if (!defined('ABSPATH'))
    exit;

require_once OBY_RESTAURANT_POS_LITE_PATH . 'includes/class-restaurant-pos-lite-dashboard.php';
require_once OBY_RESTAURANT_POS_LITE_PATH . 'includes/class-restaurant-pos-lite-helpers.php';
require_once OBY_RESTAURANT_POS_LITE_PATH . 'includes/class-restaurant-pos-lite-settings.php';
require_once OBY_RESTAURANT_POS_LITE_PATH . 'includes/class-restaurant-pos-lite-categories.php';
require_once OBY_RESTAURANT_POS_LITE_PATH . 'includes/class-restaurant-pos-lite-products.php';
require_once OBY_RESTAURANT_POS_LITE_PATH . 'includes/class-restaurant-pos-lite-stocks.php';
require_once OBY_RESTAURANT_POS_LITE_PATH . 'includes/class-restaurant-pos-lite-stock_adjustments.php';
require_once OBY_RESTAURANT_POS_LITE_PATH . 'includes/class-restaurant-pos-lite-customers.php';
require_once OBY_RESTAURANT_POS_LITE_PATH . 'includes/class-restaurant-pos-lite-pos.php';
require_once OBY_RESTAURANT_POS_LITE_PATH . 'includes/class-restaurant-pos-lite-sales.php';
require_once OBY_RESTAURANT_POS_LITE_PATH . 'includes/class-restaurant-pos-lite-accounting.php';

class Restaurant_POS_Lite_Handler
{
    public $dashboard;
    public $settings;
    public $categories;
    public $products;
    public $stocks;
    public $stock_adjustments;
    public $customers;
    public $pos;
    public $sales;
    public $accounting;

    public function __construct()
    {
        $this->dashboard = new Restaurant_POS_Lite_Dashboard();
        $this->settings = new Restaurant_POS_Lite_Settings();
        $this->categories = new Restaurant_POS_Lite_Categories();
        $this->products = new Restaurant_POS_Lite_Products();
        $this->stocks = new Restaurant_POS_Lite_Stocks();
        $this->stock_adjustments = new Restaurant_POS_Lite_Stock_Adjustments();
        $this->customers = new Restaurant_POS_Lite_Customers();
        $this->pos = new Restaurant_POS_Lite_POS();
        $this->sales = new Restaurant_POS_Lite_Sales();
        $this->accounting = new Restaurant_POS_Lite_Accounting();
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
            'restaurant-pos-lite',
            [$this->dashboard, 'render_page'],
            'dashicons-store',
            25
        );

        // Dashboard submenu
        add_submenu_page(
            'restaurant-pos-lite',
            __('Dashboard', 'restaurant-pos-lite'),
            __('Dashboard', 'restaurant-pos-lite'),
            'manage_options',
            'restaurant-pos-lite',
            [$this->dashboard, 'render_page']
        );

        // Categories submenu
        add_submenu_page(
            'restaurant-pos-lite',
            __('Categories', 'restaurant-pos-lite'),
            __('Categories', 'restaurant-pos-lite'),
            'manage_options',
            'restaurant-pos-lite-categories',
            [$this->categories, 'render_page']
        );

        // Products submenu
        add_submenu_page(
            'restaurant-pos-lite',
            __('Products', 'restaurant-pos-lite'),
            __('Products', 'restaurant-pos-lite'),
            'manage_options',
            'restaurant-pos-lite-products',
            [$this->products, 'render_page']
        );

        // Stock submenu
        add_submenu_page(
            'restaurant-pos-lite',
            __('Stocks', 'restaurant-pos-lite'),
            __('Stocks', 'restaurant-pos-lite'),
            'manage_options',
            'restaurant-pos-lite-stocks',
            [$this->stocks, 'render_page']
        );

        // Stock Adjustments submenu
        add_submenu_page(
            'restaurant-pos-lite',
            __('Stock Adjustments', 'restaurant-pos-lite'),
            __('Stock Adjustments', 'restaurant-pos-lite'),
            'manage_options',
            'restaurant-pos-lite-stock_adjustments',
            [$this->stock_adjustments, 'render_page']
        );

        // Customer submenu
        add_submenu_page(
            'restaurant-pos-lite',
            __('Customers', 'restaurant-pos-lite'),
            __('Customers', 'restaurant-pos-lite'),
            'manage_options',
            'restaurant-pos-lite-customers',
            [$this->customers, 'render_page']
        );

        // POS submenu
        add_submenu_page(
            'restaurant-pos-lite',
            __('POS', 'restaurant-pos-lite'),
            __('POS', 'restaurant-pos-lite'),
            'manage_options',
            'restaurant-pos-lite-pos',
            [$this->pos, 'render_page']
        );

        // Sales submenu
        add_submenu_page(
            'restaurant-pos-lite',
            __('Sales', 'restaurant-pos-lite'),
            __('Sales', 'restaurant-pos-lite'),
            'manage_options',
            'restaurant-pos-lite-sales',
            [$this->sales, 'render_page']
        );

        // Accounting submenu
        add_submenu_page(
            'restaurant-pos-lite',
            __('Accounting', 'restaurant-pos-lite'),
            __('Accounting', 'restaurant-pos-lite'),
            'manage_options',
            'restaurant-pos-lite-accounting',
            [$this->accounting, 'render_page']
        );

        // Settings submenu
        add_submenu_page(
            'restaurant-pos-lite',
            __('Settings', 'restaurant-pos-lite'),
            __('Settings', 'restaurant-pos-lite'),
            'manage_options',
            'restaurant-pos-lite-settings',
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
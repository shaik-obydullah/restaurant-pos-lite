<?php
/**
 * Plugin Handler
 *
 * @package Obydullah_Restaurant_POS_Lite
 * @since   1.0.0
 */
if (!defined('ABSPATH'))
    exit;

require_once ORPL_PATH . 'includes/class-obydullah-restaurant-pos-lite-dashboard.php';
require_once ORPL_PATH . 'includes/class-obydullah-restaurant-pos-lite-helpers.php';
require_once ORPL_PATH . 'includes/class-obydullah-restaurant-pos-lite-settings.php';
require_once ORPL_PATH . 'includes/class-obydullah-restaurant-pos-lite-categories.php';
require_once ORPL_PATH . 'includes/class-obydullah-restaurant-pos-lite-products.php';
require_once ORPL_PATH . 'includes/class-obydullah-restaurant-pos-lite-stocks.php';
require_once ORPL_PATH . 'includes/class-obydullah-restaurant-pos-lite-stock_adjustments.php';
require_once ORPL_PATH . 'includes/class-obydullah-restaurant-pos-lite-customers.php';
require_once ORPL_PATH . 'includes/class-obydullah-restaurant-pos-lite-pos.php';
require_once ORPL_PATH . 'includes/class-obydullah-restaurant-pos-lite-sales.php';
require_once ORPL_PATH . 'includes/class-obydullah-restaurant-pos-lite-accounting.php';

class Obydullah_Restaurant_POS_Lite_Handler
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
        $this->dashboard = new Obydullah_Restaurant_POS_Lite_Dashboard();
        $this->settings = new Obydullah_Restaurant_POS_Lite_Settings();
        $this->categories = new Obydullah_Restaurant_POS_Lite_Categories();
        $this->products = new Obydullah_Restaurant_POS_Lite_Products();
        $this->stocks = new Obydullah_Restaurant_POS_Lite_Stocks();
        $this->stock_adjustments = new Obydullah_Restaurant_POS_Lite_Stock_Adjustments();
        $this->customers = new Obydullah_Restaurant_POS_Lite_Customers();
        $this->pos = new Obydullah_Restaurant_POS_Lite_POS();
        $this->sales = new Obydullah_Restaurant_POS_Lite_Sales();
        $this->accounting = new Obydullah_Restaurant_POS_Lite_Accounting();
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function register_admin_menu()
    {
        // Main menu
        add_menu_page(
            __('OBY Restaurant POS', 'obydullah-restaurant-pos-lite'),
            __('OBY Restaurant POS', 'obydullah-restaurant-pos-lite'),
            'manage_options',
            'obydullah-restaurant-pos-lite',
            [$this->dashboard, 'render_page'],
            'dashicons-store',
            25
        );

        // Dashboard submenu
        add_submenu_page(
            'obydullah-restaurant-pos-lite',
            __('Dashboard', 'obydullah-restaurant-pos-lite'),
            __('Dashboard', 'obydullah-restaurant-pos-lite'),
            'manage_options',
            'obydullah-restaurant-pos-lite',
            [$this->dashboard, 'render_page']
        );

        // Categories submenu
        add_submenu_page(
            'obydullah-restaurant-pos-lite',
            __('Categories', 'obydullah-restaurant-pos-lite'),
            __('Categories', 'obydullah-restaurant-pos-lite'),
            'manage_options',
            'obydullah-restaurant-pos-lite-categories',
            [$this->categories, 'render_page']
        );

        // Products submenu
        add_submenu_page(
            'obydullah-restaurant-pos-lite',
            __('Products', 'obydullah-restaurant-pos-lite'),
            __('Products', 'obydullah-restaurant-pos-lite'),
            'manage_options',
            'obydullah-restaurant-pos-lite-products',
            [$this->products, 'render_page']
        );

        // Stock submenu
        add_submenu_page(
            'obydullah-restaurant-pos-lite',
            __('Stocks', 'obydullah-restaurant-pos-lite'),
            __('Stocks', 'obydullah-restaurant-pos-lite'),
            'manage_options',
            'obydullah-restaurant-pos-lite-stocks',
            [$this->stocks, 'render_page']
        );

        // Stock Adjustments submenu
        add_submenu_page(
            'obydullah-restaurant-pos-lite',
            __('Stock Adjustments', 'obydullah-restaurant-pos-lite'),
            __('Stock Adjustments', 'obydullah-restaurant-pos-lite'),
            'manage_options',
            'obydullah-restaurant-pos-lite-stock_adjustments',
            [$this->stock_adjustments, 'render_page']
        );

        // Customer submenu
        add_submenu_page(
            'obydullah-restaurant-pos-lite',
            __('Customers', 'obydullah-restaurant-pos-lite'),
            __('Customers', 'obydullah-restaurant-pos-lite'),
            'manage_options',
            'obydullah-restaurant-pos-lite-customers',
            [$this->customers, 'render_page']
        );

        // POS submenu
        add_submenu_page(
            'obydullah-restaurant-pos-lite',
            __('POS', 'obydullah-restaurant-pos-lite'),
            __('POS', 'obydullah-restaurant-pos-lite'),
            'manage_options',
            'obydullah-restaurant-pos-lite-pos',
            [$this->pos, 'render_page']
        );

        // Sales submenu
        add_submenu_page(
            'obydullah-restaurant-pos-lite',
            __('Sales', 'obydullah-restaurant-pos-lite'),
            __('Sales', 'obydullah-restaurant-pos-lite'),
            'manage_options',
            'obydullah-restaurant-pos-lite-sales',
            [$this->sales, 'render_page']
        );

        // Accounting submenu
        add_submenu_page(
            'obydullah-restaurant-pos-lite',
            __('Accounting', 'obydullah-restaurant-pos-lite'),
            __('Accounting', 'obydullah-restaurant-pos-lite'),
            'manage_options',
            'obydullah-restaurant-pos-lite-accounting',
            [$this->accounting, 'render_page']
        );

        // Settings submenu
        add_submenu_page(
            'obydullah-restaurant-pos-lite',
            __('Settings', 'obydullah-restaurant-pos-lite'),
            __('Settings', 'obydullah-restaurant-pos-lite'),
            'manage_options',
            'obydullah-restaurant-pos-lite-settings',
            [$this->settings, 'render_page']
        );
    }

    public function enqueue_admin_scripts($hook)
    {
        if (strpos($hook, 'obydullah-restaurant-pos-lite') !== false) {
            wp_enqueue_style(
                'orpl-admin',
                ORPL_URL . 'assets/css/admin.css',
                [],
                ORPL_VERSION
            );
        }
    }
}
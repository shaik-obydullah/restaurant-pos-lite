<?php
/**
 * Plugin Handler
 *
 * @package Obydullah_Restaurant_POS_Lite
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Load required class files
 */
$orpl_files = [
    'class-obydullah-restaurant-pos-lite-dashboard.php',
    'class-obydullah-restaurant-pos-lite-helpers.php',
    'class-obydullah-restaurant-pos-lite-settings.php',
    'class-obydullah-restaurant-pos-lite-categories.php',
    'class-obydullah-restaurant-pos-lite-products.php',
    'class-obydullah-restaurant-pos-lite-stocks.php',
    'class-obydullah-restaurant-pos-lite-stock_adjustments.php',
    'class-obydullah-restaurant-pos-lite-customers.php',
    'class-obydullah-restaurant-pos-lite-pos.php',
    'class-obydullah-restaurant-pos-lite-sales.php',
    'class-obydullah-restaurant-pos-lite-accounting.php',
];

foreach ($orpl_files as $file) {
    $path = ORPL_PATH . 'includes/' . $file;
    if (file_exists($path)) {
        require_once $path;
    }
}

if (!class_exists('Obydullah_Restaurant_POS_Lite_Handler')) {
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

        /**
         * Constructor
         */
        public function __construct()
        {
            $this->init();
        }

        /**
         * Initialize plugin components and hooks
         */
        private function init()
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

        /**
         * Register admin menu pages
         */
        public function register_admin_menu()
        {
            add_menu_page(
                __('OBY Restaurant POS', 'obydullah-restaurant-pos-lite'),
                __('OBY Restaurant POS', 'obydullah-restaurant-pos-lite'),
                'manage_options',
                'obydullah-restaurant-pos-lite',
                [$this->dashboard, 'render_page'],
                'dashicons-store',
                25
            );

            $submenus = [
                'obydullah-restaurant-pos-lite' => [__('Dashboard', 'obydullah-restaurant-pos-lite'), $this->dashboard],
                'obydullah-restaurant-pos-lite-categories' => [__('Categories', 'obydullah-restaurant-pos-lite'), $this->categories],
                'obydullah-restaurant-pos-lite-products' => [__('Products', 'obydullah-restaurant-pos-lite'), $this->products],
                'obydullah-restaurant-pos-lite-stocks' => [__('Stocks', 'obydullah-restaurant-pos-lite'), $this->stocks],
                'obydullah-restaurant-pos-lite-stock_adjustments' => [__('Stock Adjustments', 'obydullah-restaurant-pos-lite'), $this->stock_adjustments],
                'obydullah-restaurant-pos-lite-customers' => [__('Customers', 'obydullah-restaurant-pos-lite'), $this->customers],
                'obydullah-restaurant-pos-lite-pos' => [__('POS', 'obydullah-restaurant-pos-lite'), $this->pos],
                'obydullah-restaurant-pos-lite-sales' => [__('Sales', 'obydullah-restaurant-pos-lite'), $this->sales],
                'obydullah-restaurant-pos-lite-accounting' => [__('Accounting', 'obydullah-restaurant-pos-lite'), $this->accounting],
                'obydullah-restaurant-pos-lite-settings' => [__('Settings', 'obydullah-restaurant-pos-lite'), $this->settings],
            ];

            foreach ($submenus as $slug => $data) {
                add_submenu_page(
                    'obydullah-restaurant-pos-lite',
                    $data[0],
                    $data[0],
                    'manage_options',
                    $slug,
                    [$data[1], 'render_page']
                );
            }
        }

        /**
         * Enqueue admin scripts and styles
         *
         * @param string $hook Current admin page hook.
         */
        public function enqueue_admin_scripts($hook)
        {
            if (strpos($hook, 'obydullah-restaurant-pos-lite') === false) {
                return;
            }

            /**
             * Admin CSS
             */
            wp_enqueue_style(
                'obydullah-restaurant-pos-lite-admin',
                ORPL_URL . 'assets/css/admin.css',
                [],
                ORPL_VERSION
            );

            /**
             * Admin JS
             */
            wp_enqueue_script(
                'obydullah-restaurant-pos-lite-admin',
                ORPL_URL . 'assets/js/admin.js',
                ['jquery'],
                ORPL_VERSION,
                true
            );

            /**
             * Pass PHP data to JavaScript
             */
            wp_localize_script(
                'obydullah-restaurant-pos-lite-admin',
                'orplAdmin',
                [
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('orpl_admin_nonce'),
                ]
            );
        }
    }
}
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
            $current_page = isset($_GET['page'])
                ? sanitize_text_field(wp_unslash($_GET['page']))
                : '';

            if (
                strpos($hook, 'obydullah-restaurant-pos-lite') === false &&
                strpos($current_page, 'obydullah-restaurant-pos-lite') === false
            ) {
                return;
            }

            wp_enqueue_style(
                'obydullah-restaurant-pos-lite-main',
                ORPL_URL . 'assets/css/main.css',
                [],
                ORPL_VERSION
            );

            wp_enqueue_style(
                'obydullah-restaurant-pos-lite-pos-style',
                ORPL_URL . 'assets/css/pos-style.css',
                ['obydullah-restaurant-pos-lite-main'],
                ORPL_VERSION
            );

            // Page-specific JS
            switch ($current_page) {
                case 'obydullah-restaurant-pos-lite-categories':
                    wp_enqueue_script(
                        'orpl-categories-js',
                        ORPL_URL . 'assets/js/categories.js',
                        ['jquery'],
                        ORPL_VERSION,
                        [
                            'in_footer' => true,
                            'strategy' => 'defer',
                        ]
                    );
                    wp_localize_script(
                        'orpl-categories-js',
                        'orplCategories',
                        [
                            'ajaxUrl' => admin_url('admin-ajax.php'),
                            'addNonce' => wp_create_nonce('orpl_add_product_category'),
                            'editNonce' => wp_create_nonce('orpl_edit_product_category'),
                            'deleteNonce' => wp_create_nonce('orpl_delete_product_category'),
                            'getNonce' => wp_create_nonce('orpl_get_product_categories'),
                            'strings' => [
                                'confirmDelete' => __('Are you sure you want to delete this category?', 'obydullah-restaurant-pos-lite'),
                                'saving' => __('Saving...', 'obydullah-restaurant-pos-lite'),
                                'updating' => __('Updating...', 'obydullah-restaurant-pos-lite'),
                                'deleting' => __('Deleting...', 'obydullah-restaurant-pos-lite'),
                                'error' => __('Error occurred. Please try again.', 'obydullah-restaurant-pos-lite'),
                                'requestFailed' => __('Request failed. Please try again.', 'obydullah-restaurant-pos-lite'),
                                'deleteFailed' => __('Delete request failed. Please try again.', 'obydullah-restaurant-pos-lite'),
                                'nameRequired' => __('Please enter a category name', 'obydullah-restaurant-pos-lite'),
                                'noCategories' => __('No categories found.', 'obydullah-restaurant-pos-lite'),
                                'loadError' => __('Failed to load categories.', 'obydullah-restaurant-pos-lite'),
                                'addNewCategory' => __('Add New Category', 'obydullah-restaurant-pos-lite'),
                                'editCategory' => __('Edit Category', 'obydullah-restaurant-pos-lite'),
                                'saveCategory' => __('Save Category', 'obydullah-restaurant-pos-lite'),
                                'updateCategory' => __('Update Category', 'obydullah-restaurant-pos-lite'),
                                'edit' => __('Edit', 'obydullah-restaurant-pos-lite'),
                                'delete' => __('Delete', 'obydullah-restaurant-pos-lite'),
                            ]
                        ]
                    );
                    break;

                case 'obydullah-restaurant-pos-lite-products':
                    wp_enqueue_script(
                        'orpl-products-js',
                        ORPL_URL . 'assets/js/products.js',
                        ['jquery'],
                        ORPL_VERSION,
                        [
                            'in_footer' => true,
                            'strategy' => 'defer',
                        ]
                    );

                    wp_localize_script(
                        'orpl-products-js',
                        'orplProducts',
                        [
                            'ajaxUrl' => admin_url('admin-ajax.php'),
                            'addNonce' => wp_create_nonce('orpl_add_product'),
                            'editNonce' => wp_create_nonce('orpl_edit_product'),
                            'deleteNonce' => wp_create_nonce('orpl_delete_product'),
                            'getNonce' => wp_create_nonce('orpl_get_products'),
                            'getCategoriesNonce' => wp_create_nonce('orpl_get_categories_for_products'),
                            'strings' => [
                                'selectCategory' => __('Select Category', 'obydullah-restaurant-pos-lite'),
                                'addNewProduct' => __('Add New Product', 'obydullah-restaurant-pos-lite'),
                                'editProduct' => __('Edit Product', 'obydullah-restaurant-pos-lite'),
                                'saveProduct' => __('Save Product', 'obydullah-restaurant-pos-lite'),
                                'updateProduct' => __('Update Product', 'obydullah-restaurant-pos-lite'),
                                'cancel' => __('Cancel', 'obydullah-restaurant-pos-lite'),
                                'loadingProducts' => __('Loading products...', 'obydullah-restaurant-pos-lite'),
                                'noResults' => __('No products found matching', 'obydullah-restaurant-pos-lite'),
                                'noProducts' => __('No products found.', 'obydullah-restaurant-pos-lite'),
                                'noImage' => __('No Image', 'obydullah-restaurant-pos-lite'),
                                'edit' => __('Edit', 'obydullah-restaurant-pos-lite'),
                                'delete' => __('Delete', 'obydullah-restaurant-pos-lite'),
                                'items' => __('items', 'obydullah-restaurant-pos-lite'),
                                'enterName' => __('Please enter a product name', 'obydullah-restaurant-pos-lite'),
                                'selectCategoryError' => __('Please select a category', 'obydullah-restaurant-pos-lite'),
                                'error' => __('Error:', 'obydullah-restaurant-pos-lite'),
                                'requestFailed' => __('Request failed. Please try again.', 'obydullah-restaurant-pos-lite'),
                                'saving' => __('Saving...', 'obydullah-restaurant-pos-lite'),
                                'updating' => __('Updating...', 'obydullah-restaurant-pos-lite'),
                                'deleting' => __('Deleting...', 'obydullah-restaurant-pos-lite'),
                                'confirmDelete' => __('Are you sure you want to delete this product?', 'obydullah-restaurant-pos-lite'),
                                'deleteFailed' => __('Delete request failed. Please try again.', 'obydullah-restaurant-pos-lite'),
                                'loadError' => __('Failed to load products.', 'obydullah-restaurant-pos-lite'),
                            ]
                        ]
                    );
                    break;

                case 'obydullah-restaurant-pos-lite-stocks':
                    wp_enqueue_script(
                        'orpl-stocks-js',
                        ORPL_URL . 'assets/js/stocks.js',
                        ['jquery'],
                        ORPL_VERSION,
                        [
                            'in_footer' => true,
                            'strategy' => 'defer',
                        ]
                    );

                    wp_localize_script(
                        'orpl-stocks-js',
                        'orplStocks',
                        [
                            'ajaxUrl' => admin_url('admin-ajax.php'),
                            'addNonce' => wp_create_nonce('orpl_add_stock'),
                            'getNonce' => wp_create_nonce('orpl_get_stocks'),
                            'deleteNonce' => wp_create_nonce('orpl_delete_stock'),
                            'productsNonce' => wp_create_nonce('orpl_get_products_for_stocks'),
                            'strings' => [
                                'selectProduct' => __('Select Product', 'obydullah-restaurant-pos-lite'),
                                'selectProductRequired' => __('Please select a product', 'obydullah-restaurant-pos-lite'),
                                'validNetCost' => __('Please enter a valid net cost', 'obydullah-restaurant-pos-lite'),
                                'validSalePrice' => __('Please enter a valid sale price', 'obydullah-restaurant-pos-lite'),
                                'validQuantity' => __('Please enter a valid quantity greater than 0', 'obydullah-restaurant-pos-lite'),
                                'loadingStocks' => __('Loading stocks...', 'obydullah-restaurant-pos-lite'),
                                'noStocks' => __('No stock entries found.', 'obydullah-restaurant-pos-lite'),
                                'inStock' => __('In Stock', 'obydullah-restaurant-pos-lite'),
                                'outStock' => __('Out of Stock', 'obydullah-restaurant-pos-lite'),
                                'lowStock' => __('Low Stock', 'obydullah-restaurant-pos-lite'),
                                'delete' => __('Delete', 'obydullah-restaurant-pos-lite'),
                                'items' => __('items', 'obydullah-restaurant-pos-lite'),
                                'error' => __('Error', 'obydullah-restaurant-pos-lite'),
                                'requestFailed' => __('Request failed. Please try again.', 'obydullah-restaurant-pos-lite'),
                                'confirmDelete' => __('Are you sure you want to delete this stock entry?', 'obydullah-restaurant-pos-lite'),
                                'deleting' => __('Deleting...', 'obydullah-restaurant-pos-lite'),
                                'deleteFailed' => __('Delete request failed. Please try again.', 'obydullah-restaurant-pos-lite'),
                                'saving' => __('Saving...', 'obydullah-restaurant-pos-lite'),
                                'saveStock' => __('Save Stock', 'obydullah-restaurant-pos-lite'),
                                'loadError' => __('Failed to load stocks.', 'obydullah-restaurant-pos-lite'),
                            ]
                        ]
                    );
                    break;
            }
        }
    }
}
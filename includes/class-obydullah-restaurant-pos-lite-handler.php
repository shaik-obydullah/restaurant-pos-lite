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

                case 'obydullah-restaurant-pos-lite-stock_adjustments':
                    wp_enqueue_script(
                        'orpl-stock-adjustments-js',
                        ORPL_URL . 'assets/js/stock-adjustments.js',
                        ['jquery'],
                        ORPL_VERSION,
                        [
                            'in_footer' => true,
                            'strategy' => 'defer',
                        ]
                    );
                    wp_localize_script(
                        'orpl-stock-adjustments-js',
                        'orplStockAdjustments',
                        [
                            'ajaxUrl' => admin_url('admin-ajax.php'),
                            'addNonce' => wp_create_nonce('orpl_add_stock_adjustment'),
                            'getNonce' => wp_create_nonce('orpl_get_stock_adjustments'),
                            'deleteNonce' => wp_create_nonce('orpl_delete_stock_adjustment'),
                            'getProductsNonce' => wp_create_nonce('orpl_get_products_for_adjustments'),
                            'getStockNonce' => wp_create_nonce('orpl_get_current_stock'),
                            'strings' => [
                                'selectStock' => __('Select Stock', 'obydullah-restaurant-pos-lite'),
                                'loadingAdjustments' => __('Loading adjustments...', 'obydullah-restaurant-pos-lite'),
                                'noAdjustments' => __('No adjustments found.', 'obydullah-restaurant-pos-lite'),
                                'loadError' => __('Failed to load adjustments.', 'obydullah-restaurant-pos-lite'),
                                'increase' => __('Increase', 'obydullah-restaurant-pos-lite'),
                                'decrease' => __('Decrease', 'obydullah-restaurant-pos-lite'),
                                'delete' => __('Delete', 'obydullah-restaurant-pos-lite'),
                                'items' => __('items', 'obydullah-restaurant-pos-lite'),
                                'selectStockError' => __('Please select a stock', 'obydullah-restaurant-pos-lite'),
                                'invalidQuantity' => __('Please enter a valid quantity', 'obydullah-restaurant-pos-lite'),
                                'negativeStockConfirm' => __('This adjustment will result in negative stock. Are you sure you want to continue?', 'obydullah-restaurant-pos-lite'),
                                'error' => __('Error', 'obydullah-restaurant-pos-lite'),
                                'requestFailed' => __('Request failed. Please try again.', 'obydullah-restaurant-pos-lite'),
                                'confirmDelete' => __('Are you sure you want to delete this adjustment?', 'obydullah-restaurant-pos-lite'),
                                'deleting' => __('Deleting...', 'obydullah-restaurant-pos-lite'),
                                'deleteFailed' => __('Delete request failed. Please try again.', 'obydullah-restaurant-pos-lite'),
                                'applying' => __('Applying...', 'obydullah-restaurant-pos-lite'),
                                'applyAdjustment' => __('Apply Adjustment', 'obydullah-restaurant-pos-lite'),
                            ]
                        ]
                    );
                    break;

                case 'obydullah-restaurant-pos-lite-customers':
                    wp_enqueue_script(
                        'orpl-customers-js',
                        ORPL_URL . 'assets/js/customers.js',
                        ['jquery'],
                        ORPL_VERSION,
                        [
                            'in_footer' => true,
                            'strategy' => 'defer',
                        ]
                    );
                    wp_localize_script(
                        'orpl-customers-js',
                        'orplCustomersData',
                        [
                            'ajaxUrl' => admin_url('admin-ajax.php'),
                            'nonce_get_customers' => wp_create_nonce('orpl_get_customers'),
                            'nonce_add_customer' => wp_create_nonce('orpl_add_customer'),
                            'nonce_edit_customer' => wp_create_nonce('orpl_edit_customer'),
                            'nonce_delete_customer' => wp_create_nonce('orpl_delete_customer'),
                            'strings' => [
                                'items' => __('items', 'obydullah-restaurant-pos-lite'),
                                'loading_customers' => __('Loading customers...', 'obydullah-restaurant-pos-lite'),
                                'no_customers' => __('No customers found.', 'obydullah-restaurant-pos-lite'),
                                'failed_load' => __('Failed to load customers.', 'obydullah-restaurant-pos-lite'),
                                'active' => __('Active', 'obydullah-restaurant-pos-lite'),
                                'inactive' => __('Inactive', 'obydullah-restaurant-pos-lite'),
                                'edit' => __('Edit', 'obydullah-restaurant-pos-lite'),
                                'delete' => __('Delete', 'obydullah-restaurant-pos-lite'),
                                'add_new_customer' => __('Add New Customer', 'obydullah-restaurant-pos-lite'),
                                'edit_customer' => __('Edit Customer', 'obydullah-restaurant-pos-lite'),
                                'save_customer' => __('Save Customer', 'obydullah-restaurant-pos-lite'),
                                'update_customer' => __('Update Customer', 'obydullah-restaurant-pos-lite'),
                                'name_required' => __('Please enter customer name', 'obydullah-restaurant-pos-lite'),
                                'email_required' => __('Please enter customer email', 'obydullah-restaurant-pos-lite'),
                                'email_invalid' => __('Please enter a valid email address', 'obydullah-restaurant-pos-lite'),
                                'error' => __('Error:', 'obydullah-restaurant-pos-lite'),
                                'request_failed' => __('Request failed. Please try again.', 'obydullah-restaurant-pos-lite'),
                                'saving' => __('Saving...', 'obydullah-restaurant-pos-lite'),
                                'updating' => __('Updating...', 'obydullah-restaurant-pos-lite'),
                                'deleting' => __('Deleting...', 'obydullah-restaurant-pos-lite'),
                                'confirm_delete' => __('Are you sure you want to delete this customer?', 'obydullah-restaurant-pos-lite'),
                                'delete_failed' => __('Delete request failed. Please try again.', 'obydullah-restaurant-pos-lite'),
                            ]
                        ]
                    );
                    break;

                case 'obydullah-restaurant-pos-lite-pos':
                    // Create helpers instance
                    $helpers = new Obydullah_Restaurant_POS_Lite_Helpers();

                    wp_enqueue_script(
                        'orpl-pos-js',
                        ORPL_URL . 'assets/js/pos.js',
                        ['jquery'],
                        ORPL_VERSION,
                        [
                            'in_footer' => true,
                            'strategy' => 'defer',
                        ]
                    );

                    wp_localize_script(
                        'orpl-pos-js',
                        'orpl_pos',
                        [
                            'ajaxUrl' => admin_url('admin-ajax.php'),
                            'currencySymbol' => $helpers->get_currency_symbol(),
                            'vatRate' => $helpers->get_vat_rate(),
                            'taxRate' => $helpers->get_tax_rate(),
                            'nonces' => [
                                'categories' => wp_create_nonce('orpl_get_categories_for_pos'),
                                'customers' => wp_create_nonce('orpl_get_customers_for_pos'),
                                'stocks' => wp_create_nonce('orpl_get_products_by_category'),
                                'saved' => wp_create_nonce('orpl_get_saved_sales'),
                                'load' => wp_create_nonce('orpl_load_saved_sale'),
                                'process' => wp_create_nonce('orpl_process_sale')
                            ],
                            'strings' => [
                                'allStocks' => __('All Stocks', 'obydullah-restaurant-pos-lite'),
                                'loadingStocks' => __('Loading stocks...', 'obydullah-restaurant-pos-lite'),
                                'noStocks' => __('No stocks found', 'obydullah-restaurant-pos-lite'),
                                'inStock' => __('in stock', 'obydullah-restaurant-pos-lite'),
                                'cartEmpty' => __('Cart is empty', 'obydullah-restaurant-pos-lite'),
                                'confirmLoadSaved' => __('Loading saved sale will clear current cart. Continue?', 'obydullah-restaurant-pos-lite'),
                                'confirmRemove' => __('Remove this item from cart?', 'obydullah-restaurant-pos-lite'),
                                'confirmClear' => __('Clear cart?', 'obydullah-restaurant-pos-lite'),
                                'cartEmptyAlert' => __('Cart is empty!', 'obydullah-restaurant-pos-lite'),
                                'saleLoaded' => __('Saved sale loaded!', 'obydullah-restaurant-pos-lite'),
                                'error' => __('Error:', 'obydullah-restaurant-pos-lite'),
                                'loadingSaved' => __('Loading saved sales...', 'obydullah-restaurant-pos-lite'),
                                'noSaved' => __('No saved sales', 'obydullah-restaurant-pos-lite'),
                                'requestFailed' => __('An error occurred. Please try again.', 'obydullah-restaurant-pos-lite')
                            ]
                        ]
                    );
                    break;

                case 'obydullah-restaurant-pos-lite-sales':
                    // Get currency format directly
                    $currency = get_option('orpl_currency', '$');
                    $position = get_option('orpl_currency_position', 'left');
                    $formatted_amount = number_format(0, 2, '.', ',');

                    switch ($position) {
                        case 'right':
                            $currency_template = $formatted_amount . $currency;
                            break;
                        case 'left_space':
                            $currency_template = $currency . ' ' . $formatted_amount;
                            break;
                        case 'right_space':
                            $currency_template = $formatted_amount . ' ' . $currency;
                            break;
                        default: // left
                            $currency_template = $currency . $formatted_amount;
                    }

                    $helpers = new Obydullah_Restaurant_POS_Lite_Helpers();
                    $shop_info = $helpers->get_shop_info();

                    wp_enqueue_script(
                        'orpl-sales-js',
                        ORPL_URL . 'assets/js/sales.js',
                        ['jquery'],
                        ORPL_VERSION,
                        [
                            'in_footer' => true,
                            'strategy' => 'defer',
                        ]
                    );

                    wp_localize_script(
                        'orpl-sales-js',
                        'orplSalesData',
                        [
                            'ajaxUrl' => admin_url('admin-ajax.php'),
                            'nonce_get_sales' => wp_create_nonce('orpl_get_sales'),
                            'nonce_print_sale' => wp_create_nonce('orpl_print_sale'),
                            'nonce_delete_sale' => wp_create_nonce('orpl_delete_sale'),
                            'currency_template' => $currency_template,
                            'shop_info' => $shop_info,
                            'strings' => [
                                'items' => __('items', 'obydullah-restaurant-pos-lite'),
                                'loading_sales' => __('Loading sales...', 'obydullah-restaurant-pos-lite'),
                                'no_sales_matching' => __('No sales found matching your criteria.', 'obydullah-restaurant-pos-lite'),
                                'no_sales' => __('No sales found.', 'obydullah-restaurant-pos-lite'),
                                'failed_load' => __('Failed to load sales.', 'obydullah-restaurant-pos-lite'),
                                'na' => __('N/A', 'obydullah-restaurant-pos-lite'),
                                'walkin_customer' => __('Walk-in Customer', 'obydullah-restaurant-pos-lite'),
                                'saved' => __('Saved', 'obydullah-restaurant-pos-lite'),
                                'print' => __('Print', 'obydullah-restaurant-pos-lite'),
                                'delete' => __('Delete', 'obydullah-restaurant-pos-lite'),
                                'cannot_print' => __('Cannot print: No sale ID available', 'obydullah-restaurant-pos-lite'),
                                'error' => __('Error:', 'obydullah-restaurant-pos-lite'),
                                'request_failed' => __('Request failed:', 'obydullah-restaurant-pos-lite'),
                                'confirm_delete' => __('Are you sure you want to delete this sale? This action cannot be undone.', 'obydullah-restaurant-pos-lite'),
                                'deleting' => __('Deleting...', 'obydullah-restaurant-pos-lite'),
                                'delete_failed' => __('Delete request failed. Please try again.', 'obydullah-restaurant-pos-lite'),

                                // Invoice strings
                                'invoice' => __('Invoice', 'obydullah-restaurant-pos-lite'),
                                'phone' => __('Phone:', 'obydullah-restaurant-pos-lite'),
                                'customer' => __('Customer:', 'obydullah-restaurant-pos-lite'),
                                'mobile' => __('Mobile:', 'obydullah-restaurant-pos-lite'),
                                'email' => __('Email:', 'obydullah-restaurant-pos-lite'),
                                'address' => __('Address:', 'obydullah-restaurant-pos-lite'),
                                'order_type' => __('Order Type:', 'obydullah-restaurant-pos-lite'),
                                'cooking_instructions' => __('Cooking Instructions:', 'obydullah-restaurant-pos-lite'),
                                'date' => __('Date:', 'obydullah-restaurant-pos-lite'),
                                'status' => __('Status:', 'obydullah-restaurant-pos-lite'),
                                'item' => __('Item', 'obydullah-restaurant-pos-lite'),
                                'quantity' => __('Quantity', 'obydullah-restaurant-pos-lite'),
                                'price' => __('Price', 'obydullah-restaurant-pos-lite'),
                                'total' => __('Total', 'obydullah-restaurant-pos-lite'),
                                'no_items' => __('No items found', 'obydullah-restaurant-pos-lite'),
                                'net_price' => __('Net Price:', 'obydullah-restaurant-pos-lite'),
                                'tax' => __('Tax:', 'obydullah-restaurant-pos-lite'),
                                'vat' => __('VAT:', 'obydullah-restaurant-pos-lite'),
                                'shipping' => __('Shipping:', 'obydullah-restaurant-pos-lite'),
                                'discount' => __('Discount:', 'obydullah-restaurant-pos-lite'),
                                'grand_total' => __('Grand Total:', 'obydullah-restaurant-pos-lite'),
                                'note' => __('Note:', 'obydullah-restaurant-pos-lite'),
                            ]
                        ]
                    );
                    break;

                case 'obydullah-restaurant-pos-lite-accounting':
                    $currency = get_option('orpl_currency', '$');
                    $position = get_option('orpl_currency_position', 'left');
                    $formatted_amount = number_format(0, 2, '.', ',');

                    switch ($position) {
                        case 'right':
                            $currency_template = $formatted_amount . $currency;
                            break;
                        case 'left_space':
                            $currency_template = $currency . ' ' . $formatted_amount;
                            break;
                        case 'right_space':
                            $currency_template = $formatted_amount . ' ' . $currency;
                            break;
                        default:
                            $currency_template = $currency . $formatted_amount;
                    }

                    // Get current date
                    $date_format = get_option('orpl_date_format', 'Y-m-d');
                    $current_date = gmdate($date_format);

                    wp_enqueue_script(
                        'orpl-accounting-js',
                        ORPL_URL . 'assets/js/accounting.js',
                        ['jquery'],
                        ORPL_VERSION,
                        [
                            'in_footer' => true,
                            'strategy' => 'defer',
                        ]
                    );
                    wp_localize_script(
                        'orpl-accounting-js',
                        'orplAccountingData',
                        [
                            'ajaxUrl' => admin_url('admin-ajax.php'),
                            'nonce_get_entries' => wp_create_nonce('orpl_get_accounting_entries'),
                            'nonce_add_entry' => wp_create_nonce('orpl_add_accounting_entry'),
                            'nonce_delete_entry' => wp_create_nonce('orpl_delete_accounting_entry'),
                            'currency_template' => $currency_template,
                            'current_date' => $current_date,
                            'strings' => [
                                'items' => __('items', 'obydullah-restaurant-pos-lite'),
                                'saving' => __('Saving...', 'obydullah-restaurant-pos-lite'),
                                'save_entry' => __('Save Entry', 'obydullah-restaurant-pos-lite'),
                                'loading_entries' => __('Loading accounting entries...', 'obydullah-restaurant-pos-lite'),
                                'no_entries' => __('No accounting entries found.', 'obydullah-restaurant-pos-lite'),
                                'failed_load' => __('Failed to load accounting entries.', 'obydullah-restaurant-pos-lite'),
                                'amount_required' => __('Please enter either income or expense amount', 'obydullah-restaurant-pos-lite'),
                                'error' => __('Error:', 'obydullah-restaurant-pos-lite'),
                                'request_failed' => __('Request failed. Please try again.', 'obydullah-restaurant-pos-lite'),
                                'confirm_delete' => __('Are you sure you want to delete this accounting entry?', 'obydullah-restaurant-pos-lite'),
                                'deleting' => __('Deleting...', 'obydullah-restaurant-pos-lite'),
                                'delete_failed' => __('Delete request failed. Please try again.', 'obydullah-restaurant-pos-lite'),
                                'delete' => __('Delete', 'obydullah-restaurant-pos-lite'),
                            ]
                        ]
                    );
                    break;
            }
        }
    }
}
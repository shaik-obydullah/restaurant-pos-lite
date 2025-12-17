<?php
/**
 * Stock Management
 *
 * @package Obydullah_Restaurant_POS_Lite
 * @since   1.0.0
 */

if (!defined('ABSPATH'))
    exit;

class Obydullah_Restaurant_POS_Lite_Stocks
{
    public function __construct()
    {
        add_action('wp_ajax_orpl_add_stock', [$this, 'ajax_add_orpl_stock']);
        add_action('wp_ajax_orpl_get_stocks', [$this, 'ajax_get_orpl_stocks']);
        add_action('wp_ajax_orpl_delete_stock', [$this, 'ajax_orpl_delete_stock']);
        add_action('wp_ajax_orpl_get_products_for_stocks', [$this, 'ajax_get_products_for_stocks']);
    }

    /**
     * Get currency symbol from helper class
     *
     * @return string
     */
    private function get_currency_symbol()
    {
        if (class_exists('Obydullah_Restaurant_POS_Lite_Helpers')) {
            return Obydullah_Restaurant_POS_Lite_Helpers::get_currency_symbol();
        }

        // Fallback currency symbol.
        return '$';
    }

    /**
     * Render the stocks page
     */
    public function render_page()
    {
        // echo '<pre>WP_DEBUG: ' . (WP_DEBUG ? 'ON' : 'OFF') . '</pre>';
        // echo '<pre>WP_DEBUG_LOG: ' . (defined("WP_DEBUG_LOG") && WP_DEBUG_LOG ? 'ON' : 'OFF') . '</pre>';
        // echo '<pre>WP_DEBUG_DISPLAY: ' . (defined("WP_DEBUG_DISPLAY") && WP_DEBUG_DISPLAY ? 'ON' : 'OFF') . '</pre>';

        ?>
        <div class="wrap orpl-stocks-page">
            <h1 class="wp-heading-inline mb-3">
                <?php esc_html_e('Stock Management', 'obydullah-restaurant-pos-lite'); ?>
            </h1>
            <hr class="wp-header-end">
            <!-- Stock Summary Cards -->
            <div class="row mb-4">
                <div class="col-sm-6 col-lg-3 mb-3">
                    <div class="stock-summary-card text-center">
                        <h3 class="text-muted">In Stock</h3>
                        <p id="in-stock-count" class="summary-number text-primary">0</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3 mb-3">
                    <div class="stock-summary-card text-center">
                        <h3 class="text-muted">Out of Stock</h3>
                        <p id="out-stock-count" class="summary-number text-danger">0</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3 mb-3">
                    <div class="stock-summary-card text-center">
                        <h3 class="text-muted">Low Stock</h3>
                        <p id="low-stock-count" class="summary-number text-warning">0</p>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-3 mb-3">
                    <div class="stock-summary-card text-center">
                        <h3 class="text-muted">Total Products</h3>
                        <p id="total-stocks-count" class="summary-number text-info">0</p>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <!-- Left: Add Stock Form -->
                <div class="col-lg-4">
                    <div class="bg-light p-4 rounded shadow-sm">
                        <h2 class="mb-3 mt-1">
                            <?php esc_html_e('Add Stock Entry', 'obydullah-restaurant-pos-lite'); ?>
                        </h2>
                        <form id="add-stock-form" method="post">
                            <?php wp_nonce_field('orpl_add_stock', 'stock_nonce'); ?>

                            <!-- Product Selection -->
                            <div class="mb-3">
                                <label for="stock-product" class="form-label d-block mb-1">
                                    <?php esc_html_e('Product', 'obydullah-restaurant-pos-lite'); ?> <span class="text-danger">*</span>
                                </label>
                                <select name="fk_product_id" id="stock-product" class="form-control" required>
                                    <option value=""><?php esc_html_e('Select Product', 'obydullah-restaurant-pos-lite'); ?></option>
                                </select>
                            </div>

                            <!-- Cost and Price -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <label for="net-cost" class="form-label d-block mb-1">
                                        <?php esc_html_e('Net Cost', 'obydullah-restaurant-pos-lite'); ?><span class="currency-symbol mr-2"> (<?php echo esc_html($this->get_currency_symbol()); ?>)</span><span class="text-danger">*</span>
                                    </label>
                                    <div class="d-flex align-items-center">
                                        <input name="net_cost" id="net-cost" type="number" step="0.01" min="0" value="0.00" class="form-control currency-input" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="sale-cost" class="form-label d-block mb-1">
                                        <?php esc_html_e('Sale Price', 'obydullah-restaurant-pos-lite'); ?><span class="currency-symbol mr-2"> (<?php echo esc_html($this->get_currency_symbol()); ?>)</span><span class="text-danger">*</span>
                                    </label>
                                    <div class="d-flex align-items-center">
                                        <input name="sale_cost" id="sale-cost" type="number" step="0.01" min="0" value="0.00" class="form-control currency-input" required>
                                    </div>
                                </div>
                            </div>

                            <!-- Quantity and Status -->
                            <div class="row mb-3">
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <label for="stock-quantity" class="form-label d-block mb-1">
                                        <?php esc_html_e('Quantity', 'obydullah-restaurant-pos-lite'); ?> <span class="text-danger">*</span>
                                    </label>
                                    <input name="quantity" id="stock-quantity" type="number" min="0" value="1" class="form-control" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="stock-status" class="form-label d-block mb-1">
                                        <?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?>
                                    </label>
                                    <select name="status" id="stock-status" class="form-control">
                                        <option value="inStock"><?php esc_html_e('In Stock', 'obydullah-restaurant-pos-lite'); ?></option>
                                        <option value="outStock"><?php esc_html_e('Out of Stock', 'obydullah-restaurant-pos-lite'); ?></option>
                                        <option value="lowStock"><?php esc_html_e('Low Stock', 'obydullah-restaurant-pos-lite'); ?></option>
                                    </select>
                                </div>
                            </div>

                            <!-- Profit Calculation -->
                            <div class="bg-white p-3 border rounded mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="font-weight-bold"><?php esc_html_e('Profit Margin:', 'obydullah-restaurant-pos-lite'); ?> &nbsp;</span>
                                    <span id="profit-margin" class="profit-value font-weight-bold">0.00%</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="font-weight-bold"><?php esc_html_e('Total Profit: ', 'obydullah-restaurant-pos-lite'); ?></span>
                                    <div class="d-flex align-items-center">
                                        <span class="mr-2">(<?php echo esc_html($this->get_currency_symbol()); ?>)</span>
                                        <span id="total-profit" class="profit-value font-weight-bold">0.00</span>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex mt-4">
                                <button type="submit" id="submit-stock" class="btn-primary mr-2">
                                    <span class="btn-text"><?php esc_html_e('Save Stock', 'obydullah-restaurant-pos-lite'); ?></span>
                                    <span class="spinner" style="display: none; margin-left: 5px;"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right: Stocks Table -->
                <div class="col-lg-8">
                    <div class="bg-light p-4 rounded shadow-sm">
                        <!-- Search and Filter Section -->
                        <div class="row mb-3">
                            <div class="col-md-4 mb-2 mb-md-0">
                                <label for="stock-search" class="form-label font-weight-bold d-block mb-1">
                                    <?php esc_html_e('Search', 'obydullah-restaurant-pos-lite'); ?>
                                </label>
                                <input type="text" id="stock-search" class="form-control"
                                    placeholder="<?php esc_attr_e('Search by product name...', 'obydullah-restaurant-pos-lite'); ?>">
                            </div>
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label for="status-filter" class="form-label font-weight-bold d-block mb-1">
                                    <?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?>
                                </label>
                                <select id="status-filter" class="form-control">
                                    <option value=""><?php esc_html_e('All Status', 'obydullah-restaurant-pos-lite'); ?></option>
                                    <option value="inStock"><?php esc_html_e('In Stock', 'obydullah-restaurant-pos-lite'); ?></option>
                                    <option value="outStock"><?php esc_html_e('Out of Stock', 'obydullah-restaurant-pos-lite'); ?></option>
                                    <option value="lowStock"><?php esc_html_e('Low Stock', 'obydullah-restaurant-pos-lite'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label for="quantity-filter" class="form-label font-weight-bold d-block mb-1">
                                    <?php esc_html_e('Quantity', 'obydullah-restaurant-pos-lite'); ?>
                                </label>
                                <select id="quantity-filter" class="form-control">
                                    <option value=""><?php esc_html_e('All Quantities', 'obydullah-restaurant-pos-lite'); ?></option>
                                    <option value="zero"><?php esc_html_e('Zero Quantity', 'obydullah-restaurant-pos-lite'); ?></option>
                                    <option value="low"><?php esc_html_e('Low (1-10)', 'obydullah-restaurant-pos-lite'); ?></option>
                                    <option value="high"><?php esc_html_e('High (10+)', 'obydullah-restaurant-pos-lite'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-2 mb-md-0">
                                <label class="form-label font-weight-bold d-block mb-1"></label>
                                <button id="refresh-stocks" class="btn-primary">Refresh</button>
                            </div>
                        </div>
                        <!-- Stocks Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover stocks-table">
                                <thead>
                                    <tr class="bg-primary">
                                        <th><?php esc_html_e('Product', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th><?php esc_html_e('Net Cost', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th><?php esc_html_e('Sale Price', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th><?php esc_html_e('Quantity', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th><?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th class="text-right"><?php esc_html_e('Actions', 'obydullah-restaurant-pos-lite'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="stock-list">
                                    <tr>
                                        <td colspan="6" class="text-center">
                                            <span class="spinner is-active"></span>
                                            <?php esc_html_e('Loading stocks...', 'obydullah-restaurant-pos-lite'); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div class="d-flex align-items-center">
                                <span id="displaying-num" class="text-muted">
                                    0 <?php esc_html_e('items', 'obydullah-restaurant-pos-lite'); ?>
                                </span>
                                <div class="ml-3">
                                    <select id="per-page-select" class="form-control form-control-sm d-inline-block" style="width: auto;">
                                        <option value="10">10 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?></option>
                                        <option value="20">20 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?></option>
                                        <option value="50">50 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?></option>
                                        <option value="100">100 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?></option>
                                    </select>
                                </div>
                            </div>

                            <div class="pagination-links">
                                <button id="first-page" class="btn-secondary btn-sm" disabled>«</button>
                                <button id="prev-page" class="btn-secondary btn-sm ml-1" disabled>‹</button>
                                <span class="mx-2">
                                    <input id="current-page-selector" type="text" value="1" size="3" class="text-center" style="width: 40px;">
                                    <?php esc_html_e('of', 'obydullah-restaurant-pos-lite'); ?>
                                    <span id="total-pages">1</span>
                                </span>
                                <button id="next-page" class="btn-secondary btn-sm mr-1" disabled>›</button>
                                <button id="last-page" class="btn-secondary btn-sm" disabled>»</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /** Get products for stock form */
    public function ajax_get_products_for_stocks()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_get_products_for_stocks')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;
        $products_table = $wpdb->prefix . 'orpl_products';
        $categories_table = $wpdb->prefix . 'orpl_categories';
        $stocks_table = $wpdb->prefix . 'orpl_stocks';

        $product_status = 'active';

        // Get products with caching
        $cache_key = 'orpl_products_without_stock';
        $products = wp_cache_get($cache_key, 'obydullah-restaurant-pos-lite');

        if (false === $products) {
            // Get products that don't have stock entries
            $query = $wpdb->prepare(
                "SELECT p.id, p.name, c.name as category_name 
            FROM {$products_table} p 
            LEFT JOIN {$categories_table} c ON p.fk_category_id = c.id 
            LEFT JOIN {$stocks_table} s ON p.id = s.fk_product_id 
            WHERE p.status = %s AND s.id IS NULL 
            ORDER BY p.name ASC",
                $product_status
            );
            $products = $wpdb->get_results($query);

            // Cache for 5 minutes
            wp_cache_set($cache_key, $products, 'obydullah-restaurant-pos-lite', 300);
        }

        wp_send_json_success($products);
    }

    /** Get all stocks with pagination and filters */
    public function ajax_get_orpl_stocks()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_get_stocks')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;
        $stocks_table = $wpdb->prefix . 'orpl_stocks';
        $products_table = $wpdb->prefix . 'orpl_products';

        // Get parameters - sanitize inputs
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
        $search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
        $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        $quantity = isset($_GET['quantity']) ? sanitize_text_field(wp_unslash($_GET['quantity'])) : '';

        // Generate cache key based on all parameters
        $cache_key = 'orpl_stocks_' . md5($page . '_' . $per_page . '_' . $search . '_' . $status . '_' . $quantity);
        $cached_data = wp_cache_get($cache_key, 'obydullah-restaurant-pos-lite');

        if (false !== $cached_data) {
            wp_send_json_success($cached_data);
        }

        // Build WHERE conditions
        $where_conditions = [];
        $query_params = [];

        // Search filter
        if (!empty($search)) {
            $where_conditions[] = "p.name LIKE %s";
            $query_params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        // Status filter
        if (!empty($status)) {
            $where_conditions[] = "s.status = %s";
            $query_params[] = $status;
        }

        // Quantity filter
        if (!empty($quantity)) {
            if ($quantity === 'zero') {
                $where_conditions[] = "s.quantity = 0";
            } elseif ($quantity === 'low') {
                $where_conditions[] = "s.quantity BETWEEN 1 AND 10";
            } elseif ($quantity === 'high') {
                $where_conditions[] = "s.quantity > 10";
            }
        }

        // Build WHERE clause
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }

        $count_query = "SELECT COUNT(*) FROM {$stocks_table} s 
               LEFT JOIN {$products_table} p ON s.fk_product_id = p.id 
               {$where_clause}";

        if (!empty($query_params)) {
            $total_items = $wpdb->get_var($wpdb->prepare($count_query, $query_params));
        } else {
            $total_items = $wpdb->get_var($count_query);
        }

        // Calculate pagination
        $total_pages = ceil($total_items / $per_page);
        $offset = ($page - 1) * $per_page;

        $main_query = "SELECT s.*, p.name as product_name 
              FROM {$stocks_table} s 
              LEFT JOIN {$products_table} p ON s.fk_product_id = p.id 
              {$where_clause} 
              ORDER BY s.created_at DESC 
              LIMIT %d OFFSET %d";

        // Add pagination parameters to query params
        $pagination_params = $query_params;
        $pagination_params[] = $per_page;
        $pagination_params[] = $offset;

        if (!empty($pagination_params)) {
            $stocks = $wpdb->get_results($wpdb->prepare($main_query, $pagination_params));
        } else {
            $stocks = $wpdb->get_results($main_query);
        }

        $response_data = [
            'stocks' => $stocks,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => $total_items,
                'total_pages' => $total_pages
            ]
        ];

        // Cache the results for 2 minutes
        wp_cache_set($cache_key, $response_data, 'obydullah-restaurant-pos-lite', 120);

        wp_send_json_success($response_data);
    }

    /** Add stock */
    public function ajax_add_orpl_stock()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_add_stock')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;
        $stock_table = $wpdb->prefix . 'orpl_stocks';
        $accounting_table = $wpdb->prefix . 'orpl_accounting';

        $fk_product_id = isset($_POST['fk_product_id']) ? intval($_POST['fk_product_id']) : 0;
        $net_cost = isset($_POST['net_cost']) ? floatval($_POST['net_cost']) : 0.0;
        $sale_cost = isset($_POST['sale_cost']) ? floatval($_POST['sale_cost']) : 0.0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

        $status = 'inStock';
        if (isset($_POST['status'])) {
            $allowed_statuses = ['inStock', 'outStock', 'lowStock'];
            $submitted_status = sanitize_text_field(wp_unslash($_POST['status']));
            if (in_array($submitted_status, $allowed_statuses)) {
                $status = $submitted_status;
            }
        }

        // Validate required fields
        if ($fk_product_id <= 0) {
            wp_send_json_error(__('Please select a valid product', 'obydullah-restaurant-pos-lite'));
        }
        if ($net_cost <= 0) {
            wp_send_json_error(__('Net cost must be greater than 0', 'obydullah-restaurant-pos-lite'));
        }
        if ($sale_cost <= 0) {
            wp_send_json_error(__('Sale price must be greater than 0', 'obydullah-restaurant-pos-lite'));
        }
        if ($quantity <= 0) {
            wp_send_json_error(__('Quantity must be greater than 0', 'obydullah-restaurant-pos-lite'));
        }

        // Check if stock already exists for this product
        $existing_stock = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$stock_table} WHERE fk_product_id = %d",
            $fk_product_id
        ));

        if ($existing_stock > 0) {
            wp_send_json_error(__('Stock already exists for this product. Please use Stock Adjustments to modify quantity.', 'obydullah-restaurant-pos-lite'));
        }

        // Calculate total investment
        $total_investment = $net_cost * $quantity;

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Insert stock record
            $result = $wpdb->insert($stock_table, [
                'fk_product_id' => $fk_product_id,
                'net_cost' => $net_cost,
                'sale_cost' => $sale_cost,
                'quantity' => $quantity,
                'status' => $status,
                'created_at' => current_time('mysql')
            ], ['%d', '%f', '%f', '%d', '%s', '%s']);

            if ($result === false) {
                throw new Exception(__('Failed to add stock', 'obydullah-restaurant-pos-lite'));
            }

            $accounting_result = $wpdb->insert($accounting_table, [
                'out_amount' => $total_investment,
                'description' => 'Stock Purchase',
                'created_at' => current_time('mysql')
            ], ['%f', '%s', '%s']);

            if ($accounting_result === false) {
                throw new Exception(__('Failed to create accounting record', 'obydullah-restaurant-pos-lite'));
            }

            // Commit transaction
            $wpdb->query('COMMIT');

            // Clear stock caches
            $this->clear_stock_caches();

            wp_send_json_success(__('Stock added successfully', 'obydullah-restaurant-pos-lite'));

        } catch (Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            wp_send_json_error($e->getMessage());
        }
    }

    /** Delete stock */
    public function ajax_orpl_delete_stock()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_delete_stock')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'orpl_stocks';

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(__('Invalid stock ID', 'obydullah-restaurant-pos-lite'));
        }

        $result = $wpdb->delete($table, ['id' => $id], ['%d']);

        if ($result === false) {
            wp_send_json_error(__('Failed to delete stock', 'obydullah-restaurant-pos-lite'));
        }

        // Clear stock caches
        $this->clear_stock_caches();

        wp_send_json_success(__('Stock deleted successfully', 'obydullah-restaurant-pos-lite'));
    }

    /**
     * Clear all stock-related caches
     */
    private function clear_stock_caches()
    {
        global $wpdb;

        // Clear stock caches
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_orpl_stocks_%')
        );
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_orpl_stocks_%')
        );

        // Clear product caches
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_orpl_active_products_for_stocks%')
        );
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_orpl_active_products_for_stocks%')
        );

        // Clear products without stock cache
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_orpl_products_without_stock%')
        );
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_orpl_products_without_stock%')
        );
    }
}
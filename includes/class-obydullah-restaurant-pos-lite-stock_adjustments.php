<?php
/**
 * Stock Adjustments Management
 *
 * @package Obydullah_Restaurant_POS_Lite
 * @since   1.0.0
 */
if (!defined('ABSPATH')) {
    exit;
}

class Obydullah_Restaurant_POS_Lite_Stock_Adjustments
{
    const CACHE_GROUP = 'orpl_stock_adjustments';
    const CACHE_EXPIRATION = 15 * MINUTE_IN_SECONDS;

    private $adjustments_table;
    private $stocks_table;
    private $products_table;
    private $categories_table;
    private $accounting_table;

    public function __construct()
    {
        global $wpdb;

        $this->adjustments_table = $wpdb->prefix . 'orpl_stock_adjustments';
        $this->stocks_table = $wpdb->prefix . 'orpl_stocks';
        $this->products_table = $wpdb->prefix . 'orpl_products';
        $this->categories_table = $wpdb->prefix . 'orpl_categories';
        $this->accounting_table = $wpdb->prefix . 'orpl_accounting';

        add_action('wp_ajax_orpl_add_stock_adjustment', [$this, 'ajax_add_orpl_stock_adjustment']);
        add_action('wp_ajax_orpl_get_stock_adjustments', [$this, 'ajax_get_orpl_stock_adjustments']);
        add_action('wp_ajax_orpl_delete_stock_adjustment', [$this, 'ajax_delete_orpl_stock_adjustment']);
        add_action('wp_ajax_orpl_get_products_for_adjustments', [$this, 'ajax_get_orpl_products_for_adjustments']);
        add_action('wp_ajax_orpl_get_current_stock', [$this, 'ajax_get_current_orpl_stock']);
    }

    /**
     * Render the stock adjustments page
     */
    public function render_page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline mb-4">
                <?php esc_html_e('Stock Adjustments', 'obydullah-restaurant-pos-lite'); ?>
            </h1>
            <hr class="wp-header-end">

            <div class="row">
                <!-- Left: Add Adjustment Form -->
                <div class="col-md-4">
                    <div class="bg-light p-4 rounded shadow-sm mb-4">
                        <h2 class="h4 mb-3 mt-1">
                            <?php esc_html_e('New Stock Adjustment', 'obydullah-restaurant-pos-lite'); ?>
                        </h2>
                        <form id="add-adjustment-form" method="post">
                            <?php wp_nonce_field('orpl_add_stock_adjustment', 'adjustment_nonce'); ?>

                            <div class="mb-3">
                                <!-- Stock Selection -->
                                <div class="form-group mb-3">
                                    <label for="adjustment-product" class="form-label fw-semibold">
                                        <?php esc_html_e('Stock', 'obydullah-restaurant-pos-lite'); ?>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select name="stock_id" id="adjustment-product" class="form-control" required>
                                        <option value=""><?php esc_html_e('Select Stock', 'obydullah-restaurant-pos-lite'); ?></option>
                                    </select>
                                </div>

                                <!-- Adjustment Type and Quantity -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="adjustment-type" class="form-label fw-semibold">
                                                <?php esc_html_e('Type', 'obydullah-restaurant-pos-lite'); ?>
                                                <span class="text-danger">*</span>
                                            </label>
                                            <select name="adjustment_type" id="adjustment-type" class="form-control" required>
                                                <option value="increase"><?php esc_html_e('Increase', 'obydullah-restaurant-pos-lite'); ?></option>
                                                <option value="decrease"><?php esc_html_e('Decrease', 'obydullah-restaurant-pos-lite'); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="adjustment-quantity" class="form-label fw-semibold">
                                                <?php esc_html_e('Quantity', 'obydullah-restaurant-pos-lite'); ?>
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input name="quantity" id="adjustment-quantity" type="number" min="1" value="1" class="form-control" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- New Stock Calculation -->
                                <div class="alert alert-light border mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-dark"><?php esc_html_e('Current Stock:', 'obydullah-restaurant-pos-lite'); ?></span>
                                        <span id="current-stock" class="fw-bold text-dark ml-1">0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-dark"><?php esc_html_e('Adjustment:', 'obydullah-restaurant-pos-lite'); ?></span>
                                        <span id="adjustment-display" class="fw-bold text-success ml-1">+0</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-dark"><?php esc_html_e('New Stock:', 'obydullah-restaurant-pos-lite'); ?></span>
                                        <span id="new-stock" class="fw-bold text-danger ml-1">0</span>
                                    </div>
                                </div>

                                <!-- Note -->
                                <div class="form-group mb-3">
                                    <label for="adjustment-note" class="form-label fw-semibold">
                                        <?php esc_html_e('Note', 'obydullah-restaurant-pos-lite'); ?>
                                    </label>
                                    <textarea name="note" id="adjustment-note" rows="3" class="form-control"
                                        placeholder="<?php esc_attr_e('Reason for adjustment...', 'obydullah-restaurant-pos-lite'); ?>"></textarea>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" id="submit-adjustment" class="btn btn-primary w-100">
                                    <span class="btn-text"><?php esc_html_e('Apply Adjustment', 'obydullah-restaurant-pos-lite'); ?></span>
                                    <span class="spinner" style="display:none;"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right: Adjustments History Table -->
                <div class="col-md-8">
                    <div class="bg-light p-3 rounded shadow-sm border">
                        <h2 class="h5 mb-3 fw-semibold">
                            <?php esc_html_e('Adjustments History', 'obydullah-restaurant-pos-lite'); ?>
                        </h2>

                        <!-- Search and Filter Section -->
                        <div class="search-section mb-3">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <div class="search-group flex-grow-1">
                                    <label for="adjustment-search" class="form-label mb-1">
                                        <?php esc_html_e('Search Adjustments', 'obydullah-restaurant-pos-lite'); ?>
                                    </label>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="position-relative flex-grow-1">
                                            <input type="text" id="adjustment-search"
                                                class="form-control form-control-sm"
                                                placeholder="<?php esc_attr_e('Stock name', 'obydullah-restaurant-pos-lite'); ?>">
                                            <button type="button" id="clear-adjustment-search"
                                                class="btn btn-sm btn-link text-decoration-none position-absolute end-0 top-50 translate-middle-y"
                                                style="display: none; padding: 0;">
                                                <span class="text-muted fs-5">×</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-text">
                                        <?php esc_html_e('Search by stock name', 'obydullah-restaurant-pos-lite'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Filters Row -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label for="type-filter" class="form-label small mb-1">
                                    <?php esc_html_e('Adjustment Type', 'obydullah-restaurant-pos-lite'); ?>
                                </label>
                                <select id="type-filter" class="form-control form-control-sm">
                                    <option value=""><?php esc_html_e('All Types', 'obydullah-restaurant-pos-lite'); ?></option>
                                    <option value="increase"><?php esc_html_e('Increase', 'obydullah-restaurant-pos-lite'); ?></option>
                                    <option value="decrease"><?php esc_html_e('Decrease', 'obydullah-restaurant-pos-lite'); ?></option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="date-filter" class="form-label small mt-1 mb-1">
                                    <?php esc_html_e('Adjustment Date', 'obydullah-restaurant-pos-lite'); ?>
                                </label>
                                <input type="date" id="date-filter" class="form-control form-control-sm">
                            </div>
                        </div>

                        <!-- Adjustments Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-bordered mb-2">
                                <thead>
                                    <tr class="bg-primary text-white">
                                        <th><?php esc_html_e('Date', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th><?php esc_html_e('Stock', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th><?php esc_html_e('Type', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th><?php esc_html_e('Quantity', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th><?php esc_html_e('Note', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th class="text-right"><?php esc_html_e('Actions', 'obydullah-restaurant-pos-lite'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="adjustment-list" class="bg-white">
                                    <tr>
                                        <td colspan="6" class="text-center p-4">
                                            <span class="spinner is-active"></span>
                                            <?php esc_html_e('Loading adjustments...', 'obydullah-restaurant-pos-lite'); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex flex-wrap justify-content-between align-items-center mt-2">
                            <div class="tablenav-pages">
                                <span class="displaying-num" id="displaying-num">0 <?php esc_html_e('items', 'obydullah-restaurant-pos-lite'); ?></span>
                                <span class="pagination-links ms-2">
                                    <a class="first-page btn btn-sm btn-dark" href="#" title="<?php esc_attr_e('First page', 'obydullah-restaurant-pos-lite'); ?>">«</a>
                                    <a class="prev-page btn btn-sm btn-dark" href="#" title="<?php esc_attr_e('Previous page', 'obydullah-restaurant-pos-lite'); ?>">‹</a>
                                    <span class="paging-input">
                                        <input class="current-page form-control form-control-sm" id="current-page-selector" type="text" name="paged" value="1">
                                        <span class="tablenav-paging-text"><?php esc_html_e('of', 'obydullah-restaurant-pos-lite'); ?> <span class="total-pages">1</span></span>
                                    </span>
                                    <a class="next-page btn btn-sm btn-dark" href="#" title="<?php esc_attr_e('Next page', 'obydullah-restaurant-pos-lite'); ?>">›</a>
                                    <a class="last-page btn btn-sm btn-dark" href="#" title="<?php esc_attr_e('Last page', 'obydullah-restaurant-pos-lite'); ?>">»</a>
                                </span>
                            </div>
                            <div class="tablenav-pages">
                                <select id="per-page-select" class="form-control form-control-sm">
                                    <option value="10">10 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?></option>
                                    <option value="20">20 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?></option>
                                    <option value="50">50 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?></option>
                                    <option value="100">100 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?></option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /** Get products for adjustments form */
    public function ajax_get_orpl_products_for_adjustments()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_get_products_for_adjustments')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;

        $cache_key = 'orpl_stocks_for_adjustments';
        $stocks = wp_cache_get($cache_key, self::CACHE_GROUP);

        if (false === $stocks) {
            // Query to get individual stock entries with product info
            $query = "SELECT s.id as stock_id, p.id as product_id, p.name, 
                   s.quantity, s.net_cost, s.sale_cost, s.status,
                   c.name as category_name 
            FROM {$this->stocks_table} s 
            INNER JOIN {$this->products_table} p ON s.fk_product_id = p.id 
            LEFT JOIN {$this->categories_table} c ON p.fk_category_id = c.id 
            ORDER BY p.name ASC, s.id ASC";

            $stocks = $wpdb->get_results($query);

            wp_cache_set($cache_key, $stocks, self::CACHE_GROUP, self::CACHE_EXPIRATION);
        }

        wp_send_json_success($stocks);
    }

    /** Get current stock for a product */
    public function ajax_get_current_orpl_stock()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_get_current_stock')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        $stock_id = intval($_GET['stock_id'] ?? 0);

        if ($stock_id <= 0) {
            wp_send_json_error(__('Invalid stock ID', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;

        $cache_key = 'orpl_current_stock_' . $stock_id;
        $current_stock = wp_cache_get($cache_key, self::CACHE_GROUP);

        if (false === $current_stock) {
            $current_stock = $wpdb->get_var($wpdb->prepare(
                "SELECT quantity FROM {$this->stocks_table} WHERE id = %d",
                $stock_id
            ));

            wp_cache_set($cache_key, $current_stock, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS);
        }

        wp_send_json_success(['current_stock' => $current_stock ? intval($current_stock) : 0]);
    }

    /** Get stock adjustments with pagination and filters */
    public function ajax_get_orpl_stock_adjustments()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_get_stock_adjustments')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        // Get parameters - sanitize inputs
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
        $search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
        $type = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '';
        $date = isset($_GET['date']) ? sanitize_text_field(wp_unslash($_GET['date'])) : '';

        // Generate cache key based on filters
        $cache_key = 'orpl_adjustments_' . md5(serialize([$page, $per_page, $search, $type, $date]));
        $cached_data = wp_cache_get($cache_key, self::CACHE_GROUP);

        if (false !== $cached_data) {
            wp_send_json_success($cached_data);
        }

        global $wpdb;

        // Build WHERE conditions
        $where_conditions = [];
        $query_params = [];

        // Search filter
        if (!empty($search)) {
            $where_conditions[] = "p.name LIKE %s";
            $query_params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        // Type filter
        if (!empty($type)) {
            $where_conditions[] = "a.adjustment_type = %s";
            $query_params[] = $type;
        }

        // Date filter
        if (!empty($date)) {
            $where_conditions[] = "DATE(a.created_at) = %s";
            $query_params[] = $date;
        }

        // Build WHERE clause
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }

        // COUNT query
        $count_query = "SELECT COUNT(*) FROM {$this->adjustments_table} a 
           LEFT JOIN {$this->stocks_table} s ON a.fk_stock_id = s.id 
           LEFT JOIN {$this->products_table} p ON s.fk_product_id = p.id 
           {$where_clause}";

        // Execute count query with or without parameters
        if (!empty($query_params)) {
            $total_items = $wpdb->get_var($wpdb->prepare($count_query, $query_params));
        } else {
            $total_items = $wpdb->get_var($count_query);
        }

        // Calculate pagination
        $total_pages = ceil($total_items / $per_page);
        $offset = ($page - 1) * $per_page;

        // Main query
        $main_query = "SELECT a.*, p.name as product_name 
          FROM {$this->adjustments_table} a 
          LEFT JOIN {$this->stocks_table} s ON a.fk_stock_id = s.id 
          LEFT JOIN {$this->products_table} p ON s.fk_product_id = p.id 
          {$where_clause} 
          ORDER BY a.created_at DESC 
          LIMIT %d OFFSET %d";

        // Add pagination parameters
        $pagination_params = $query_params;
        $pagination_params[] = $per_page;
        $pagination_params[] = $offset;

        // Execute main query
        if (!empty($query_params)) {
            $adjustments = $wpdb->get_results($wpdb->prepare($main_query, $pagination_params));
        } else {
            // When no search/filter parameters, only prepare pagination parameters
            $adjustments = $wpdb->get_results($wpdb->prepare($main_query, $per_page, $offset));
        }

        $response_data = [
            'adjustments' => $adjustments,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => intval($total_items),
                'total_pages' => $total_pages
            ]
        ];

        // Cache for 5 minutes
        wp_cache_set($cache_key, $response_data, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS);

        wp_send_json_success($response_data);
    }

    /** Add stock adjustment with full accounting */
    public function ajax_add_orpl_stock_adjustment()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_add_stock_adjustment')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        // Unslash and sanitize input
        $stock_id = intval($_POST['stock_id'] ?? 0);
        $adjustment_type = in_array(wp_unslash($_POST['adjustment_type'] ?? ''), ['increase', 'decrease']) ?
            sanitize_text_field(wp_unslash($_POST['adjustment_type'])) : 'increase';
        $quantity = intval($_POST['quantity'] ?? 0);
        $note = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));

        // Validate required fields
        if ($stock_id <= 0) {
            wp_send_json_error(__('Please select a valid stock', 'obydullah-restaurant-pos-lite'));
        }
        if ($quantity <= 0) {
            wp_send_json_error(__('Quantity must be greater than 0', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;

        // Get product ID from stock entry
        $stock_data = $wpdb->get_row($wpdb->prepare(
            "SELECT fk_product_id, quantity, net_cost FROM {$this->stocks_table} WHERE id = %d",
            $stock_id
        ));

        if (!$stock_data) {
            wp_send_json_error(__('Invalid stock entry selected', 'obydullah-restaurant-pos-lite'));
        }

        $current_quantity = $stock_data->quantity;
        $net_cost = $stock_data->net_cost;

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Add adjustment record
            $adjustment_result = $wpdb->insert($this->adjustments_table, [
                'fk_stock_id' => $stock_id,
                'adjustment_type' => $adjustment_type,
                'quantity' => $quantity,
                'note' => $note,
                'created_at' => current_time('mysql')
            ], ['%d', '%s', '%d', '%s', '%s']);

            if ($adjustment_result === false) {
                throw new Exception(__('Failed to add adjustment record', 'obydullah-restaurant-pos-lite'));
            }

            $adjustment_value = $net_cost * $quantity;

            // Calculate new quantity
            $new_quantity = $adjustment_type === 'increase' ?
                $current_quantity + $quantity : $current_quantity - $quantity;

            // Determine status based on new quantity
            $status = $new_quantity > 10 ? 'inStock' : ($new_quantity > 0 ? 'lowStock' : 'outStock');

            // Update stock quantity
            $stock_result = $wpdb->update($this->stocks_table, [
                'quantity' => $new_quantity,
                'status' => $status
            ], ['id' => $stock_id], ['%d', '%s'], ['%d']);

            if ($stock_result === false) {
                throw new Exception(__('Failed to update stock', 'obydullah-restaurant-pos-lite'));
            }

            if ($adjustment_value > 0) {
                $description = $adjustment_type === 'increase' ?
                    'Stock Adjustment (Increase)' : 'Stock Adjustment (Decrease)';

                if ($adjustment_type === 'decrease') {
                    // Stock decrease = loss/wastage (money lost)
                    $accounting_data = [
                        'out_amount' => $adjustment_value,
                        'description' => $description,
                        'created_at' => current_time('mysql')
                    ];
                    $accounting_format = ['%f', '%s', '%s'];
                } else {
                    // Stock increase = gain/return (money gained)
                    $accounting_data = [
                        'in_amount' => $adjustment_value,
                        'description' => $description,
                        'created_at' => current_time('mysql')
                    ];
                    $accounting_format = ['%f', '%s', '%s'];
                }

                $accounting_result = $wpdb->insert($this->accounting_table, $accounting_data, $accounting_format);

                if ($accounting_result === false) {
                    throw new Exception(__('Failed to create accounting record', 'obydullah-restaurant-pos-lite'));
                }
            }

            // Commit transaction
            $wpdb->query('COMMIT');

            // Clear relevant caches
            $this->clear_adjustment_caches();
            wp_cache_delete('orpl_current_stock_' . $stock_id, self::CACHE_GROUP);

            wp_send_json_success(__('Stock adjustment applied successfully', 'obydullah-restaurant-pos-lite'));

        } catch (Exception $e) {
            // Rollback transaction on error
            $wpdb->query('ROLLBACK');
            wp_send_json_error($e->getMessage());
        }
    }

    /** Delete stock adjustment */
    public function ajax_delete_orpl_stock_adjustment()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_delete_stock_adjustment')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error(__('Invalid adjustment ID', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;

        $adjustment = $wpdb->get_row($wpdb->prepare(
            "SELECT fk_stock_id FROM {$this->adjustments_table} WHERE id = %d",
            $id
        ));

        $result = $wpdb->delete($this->adjustments_table, ['id' => $id], ['%d']);

        if ($result === false) {
            wp_send_json_error(__('Failed to delete adjustment', 'obydullah-restaurant-pos-lite'));
        }

        // Clear relevant caches
        $this->clear_adjustment_caches();
        if ($adjustment && $adjustment->fk_stock_id) {
            wp_cache_delete('orpl_current_stock_' . $adjustment->fk_stock_id, self::CACHE_GROUP);
        }

        wp_send_json_success(__('Adjustment deleted successfully', 'obydullah-restaurant-pos-lite'));
    }

    /**
     * Clear all adjustment-related caches
     */
    private function clear_adjustment_caches()
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_orpl_adjustments_%')
        );
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_orpl_adjustments_%')
        );

        // Clear object cache
        wp_cache_delete('orpl_products_for_adjustments', self::CACHE_GROUP);
    }
}
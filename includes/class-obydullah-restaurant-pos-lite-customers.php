<?php
/**
 * Customer Management
 *
 * @package Obydullah_Restaurant_POS_Lite
 * @since   1.0.0
 */
if (!defined('ABSPATH')) {
    exit;
}

class Obydullah_Restaurant_POS_Lite_Customers
{
    const CACHE_GROUP = 'orpl_customers';
    const CACHE_EXPIRATION = 15 * MINUTE_IN_SECONDS;

    private $customers_table;

    public function __construct()
    {
        global $wpdb;
        $this->customers_table = $wpdb->prefix . 'orpl_customers';

        add_action('wp_ajax_orpl_add_customer', [$this, 'ajax_add_orpl_customer']);
        add_action('wp_ajax_orpl_get_customers', [$this, 'ajax_get_orpl_customers']);
        add_action('wp_ajax_orpl_edit_customer', [$this, 'ajax_edit_orpl_customer']);
        add_action('wp_ajax_orpl_delete_customer', [$this, 'ajax_delete_orpl_customer']);
    }

    /**
     * Render the customers page
     */
    public function render_page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline mb-4">
                <?php esc_html_e('Customers', 'obydullah-restaurant-pos-lite'); ?>
            </h1>
            <hr class="wp-header-end">

            <!-- Customer Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div id="active-customers-card" class="stock-summary-card">
                        <h3><?php esc_html_e('Active Customers', 'obydullah-restaurant-pos-lite'); ?></h3>
                        <p id="active-customers-count" class="summary-number text-success">0</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div id="inactive-customers-card" class="stock-summary-card">
                        <h3><?php esc_html_e('Inactive Customers', 'obydullah-restaurant-pos-lite'); ?></h3>
                        <p id="inactive-customers-count" class="summary-number text-danger">0</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div id="total-customers-card" class="stock-summary-card">
                        <h3><?php esc_html_e('Total Customers', 'obydullah-restaurant-pos-lite'); ?></h3>
                        <p id="total-customers-count" class="summary-number text-primary">0</p>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- Left: Add/Edit Customer Form -->
                <div class="col-md-4">
                    <div class="bg-light p-4 rounded shadow-sm mb-4">
                        <h2 id="form-title" class="h4 mb-3 mt-1">
                            <?php esc_html_e('Add New Customer', 'obydullah-restaurant-pos-lite'); ?>
                        </h2>
                        <form id="add-customer-form" method="post">
                            <?php wp_nonce_field('orpl_add_customer', 'customer_nonce'); ?>
                            <input type="hidden" id="customer-id" name="id" value="">

                            <div class="mb-3">
                                <!-- Name and Email -->
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="customer-name" class="form-label fw-semibold">
                                                <?php esc_html_e('Name', 'obydullah-restaurant-pos-lite'); ?>
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input name="name" id="customer-name" type="text" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="customer-email" class="form-label fw-semibold">
                                                <?php esc_html_e('Email', 'obydullah-restaurant-pos-lite'); ?>
                                                <span class="text-danger">*</span>
                                            </label>
                                            <input name="email" id="customer-email" type="email" class="form-control" required>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mobile -->
                                <div class="form-group mb-3">
                                    <label for="customer-mobile" class="form-label fw-semibold">
                                        <?php esc_html_e('Mobile', 'obydullah-restaurant-pos-lite'); ?>
                                    </label>
                                    <input name="mobile" id="customer-mobile" type="text" class="form-control">
                                </div>

                                <!-- Address -->
                                <div class="form-group mb-3">
                                    <label for="customer-address" class="form-label fw-semibold">
                                        <?php esc_html_e('Address', 'obydullah-restaurant-pos-lite'); ?>
                                    </label>
                                    <textarea name="address" id="customer-address" rows="2" class="form-control"
                                        placeholder="<?php esc_attr_e('Customer address...', 'obydullah-restaurant-pos-lite'); ?>"></textarea>
                                </div>

                                <!-- Status -->
                                <div class="form-group mb-3">
                                    <label for="customer-status" class="form-label fw-semibold">
                                        <?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?>
                                    </label>
                                    <select name="status" id="customer-status" class="form-control">
                                        <option value="active"><?php esc_html_e('Active', 'obydullah-restaurant-pos-lite'); ?></option>
                                        <option value="inactive"><?php esc_html_e('Inactive', 'obydullah-restaurant-pos-lite'); ?></option>
                                    </select>
                                </div>
                            </div>
                            <div class="d-flex mt-4">
                                <button type="submit" id="submit-customer" class="btn-primary mr-2">
                                    <span class="btn-text"><?php esc_html_e('Save Customer', 'obydullah-restaurant-pos-lite'); ?></span>
                                    <span class="spinner" style="display: none; margin-left: 5px;"></span>
                                </button>
                                <button type="button" id="cancel-edit" class="btn-secondary" style="display: none;">
                                    <?php esc_html_e('Cancel', 'obydullah-restaurant-pos-lite'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right: Customers Table -->
                <div class="col-lg-8">
                    <div class="bg-light p-3 rounded shadow-sm border">
                        <h2 class="h5 mb-3 fw-semibold">
                            <?php esc_html_e('Customer Management', 'obydullah-restaurant-pos-lite'); ?>
                        </h2>

                        <!-- Search and Filter Section -->
                        <div class="search-section mb-3">
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <div class="search-group flex-grow-1">
                                    <label for="customer-search" class="form-label mb-1">
                                        <?php esc_html_e('Search Customers', 'obydullah-restaurant-pos-lite'); ?>
                                    </label>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="position-relative flex-grow-1">
                                            <input type="text" id="customer-search"
                                                class="form-control form-control-sm"
                                                placeholder="<?php esc_attr_e('Customer name or mobile', 'obydullah-restaurant-pos-lite'); ?>">
                                            <button type="button" id="clear-customer-search"
                                                class="btn btn-sm btn-link text-decoration-none position-absolute end-0 top-50 translate-middle-y"
                                                style="display: none; padding: 0;">
                                                <span class="text-muted fs-5">×</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-text">
                                        <?php esc_html_e('Search by customer name, email or mobile', 'obydullah-restaurant-pos-lite'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Filters Row - Fixed button alignment -->
                        <div class="row g-2 mb-3">
                            <div class="col-md-4">
                                <label for="status-filter" class="form-label small mb-1">
                                    <?php esc_html_e('Customer Status', 'obydullah-restaurant-pos-lite'); ?>
                                </label>
                                <select id="status-filter" class="form-control form-control-sm">
                                    <option value=""><?php esc_html_e('All Status', 'obydullah-restaurant-pos-lite'); ?></option>
                                    <option value="active"><?php esc_html_e('Active', 'obydullah-restaurant-pos-lite'); ?></option>
                                    <option value="inactive"><?php esc_html_e('Inactive', 'obydullah-restaurant-pos-lite'); ?></option>
                                </select>
                            </div>
                        </div>

                        <!-- Customers Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-bordered mb-2">
                                <thead>
                                    <tr class="bg-primary text-white">
                                        <th><?php esc_html_e('Name', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th><?php esc_html_e('Email', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th><?php esc_html_e('Mobile', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th><?php esc_html_e('Address', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th width="100"><?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th width="100" class="text-right"><?php esc_html_e('Actions', 'obydullah-restaurant-pos-lite'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="customer-list" class="bg-white">
                                    <tr>
                                        <td colspan="7" class="text-center p-4">
                                            <span class="spinner is-active"></span>
                                            <?php esc_html_e('Loading customers...', 'obydullah-restaurant-pos-lite'); ?>
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

    /** Get customers with pagination and filters */
    public function ajax_get_orpl_customers()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_get_customers')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        // Get parameters - sanitize inputs
        $customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
        $search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
        $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';

        if ($customer_id > 0) {
            // Single customer request - cache individual customer
            $cache_key = 'orpl_customer_' . $customer_id;
            $customers = wp_cache_get($cache_key, self::CACHE_GROUP);

            if (false === $customers) {
                global $wpdb;

                $customers = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, name, email, mobile, address, status, created_at 
                FROM {$this->customers_table} 
                WHERE id = %d",
                    $customer_id
                ));

                wp_cache_set($cache_key, $customers, self::CACHE_GROUP, self::CACHE_EXPIRATION);
            }

            $response_data = [
                'customers' => $customers,
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 1,
                    'total_items' => count($customers),
                    'total_pages' => 1
                ]
            ];
        } else {
            // Multiple customers with pagination - cache based on filters
            $cache_key = 'orpl_customers_' . md5(serialize([$page, $per_page, $search, $status]));
            $cached_data = wp_cache_get($cache_key, self::CACHE_GROUP);

            if (false !== $cached_data) {
                wp_send_json_success($cached_data);
            }

            global $wpdb;

            $where_conditions = [];
            $query_params = [];

            // Build WHERE conditions
            if (!empty($search)) {
                $where_conditions[] = "(name LIKE %s OR mobile LIKE %s OR email LIKE %s)";
                $search_like = '%' . $wpdb->esc_like($search) . '%';
                $query_params[] = $search_like;
                $query_params[] = $search_like;
                $query_params[] = $search_like;
            }

            if (!empty($status)) {
                $where_conditions[] = "status = %s";
                $query_params[] = $status;
            }

            // Build the WHERE clause
            $where_clause = '';
            if (!empty($where_conditions)) {
                $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
            }

            $count_query = "SELECT COUNT(*) FROM {$this->customers_table} {$where_clause}";
            if (!empty($query_params)) {
                $total_items = $wpdb->get_var($wpdb->prepare($count_query, $query_params));
            } else {
                $total_items = $wpdb->get_var($count_query);
            }

            // Calculate pagination
            $total_pages = ceil($total_items / $per_page);
            $offset = ($page - 1) * $per_page;

            $main_query = "SELECT id, name, email, mobile, address, status, created_at 
                  FROM {$this->customers_table} 
                  {$where_clause} 
                  ORDER BY created_at DESC 
                  LIMIT %d OFFSET %d";

            // Add pagination parameters
            $pagination_params = $query_params;
            $pagination_params[] = $per_page;
            $pagination_params[] = $offset;

            // Execute main query
            if (!empty($pagination_params)) {
                $customers = $wpdb->get_results($wpdb->prepare($main_query, $pagination_params));
            } else {
                $customers = $wpdb->get_results($main_query);
            }

            $response_data = [
                'customers' => $customers,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total_items' => $total_items,
                    'total_pages' => $total_pages
                ]
            ];

            // Cache for 5 minutes
            wp_cache_set($cache_key, $response_data, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS);
        }

        wp_send_json_success($response_data);
    }

    /** Add customer */
    public function ajax_add_orpl_customer()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_add_customer')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        // Unslash and sanitize input
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $mobile = sanitize_text_field(wp_unslash($_POST['mobile'] ?? ''));
        $address = sanitize_text_field(wp_unslash($_POST['address'] ?? ''));
        $status = in_array(wp_unslash($_POST['status'] ?? ''), ['active', 'inactive']) ?
            sanitize_text_field(wp_unslash($_POST['status'])) : 'active';

        // Validate required fields
        if (empty($name)) {
            wp_send_json_error(__('Customer name is required', 'obydullah-restaurant-pos-lite'));
        }
        if (empty($email) || !is_email($email)) {
            wp_send_json_error(__('Valid email address is required', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;

        // Check if email already exists
        $cache_key = 'orpl_customer_email_' . md5($email);
        $existing = wp_cache_get($cache_key, self::CACHE_GROUP);

        if (false === $existing) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$this->customers_table} WHERE email = %s",
                $email
            ));
            wp_cache_set($cache_key, $existing, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS);
        }

        if ($existing) {
            wp_send_json_error(__('Customer email already exists', 'obydullah-restaurant-pos-lite'));
        }

        $result = $wpdb->insert($this->customers_table, [
            'name' => $name,
            'email' => $email,
            'mobile' => $mobile,
            'address' => $address,
            'status' => $status,
            'created_at' => current_time('mysql')
        ], ['%s', '%s', '%s', '%s', '%s', '%s']);

        if ($result === false) {
            wp_send_json_error(__('Failed to add customer', 'obydullah-restaurant-pos-lite'));
        }

        // Clear relevant caches
        $this->clear_customer_caches();

        wp_send_json_success(__('Customer added successfully', 'obydullah-restaurant-pos-lite'));
    }

    /** Edit customer */
    public function ajax_edit_orpl_customer()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_edit_customer')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        $id = intval($_POST['id'] ?? 0);
        // Unslash and sanitize input
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $mobile = sanitize_text_field(wp_unslash($_POST['mobile'] ?? ''));
        $address = sanitize_text_field(wp_unslash($_POST['address'] ?? ''));
        $status = in_array(wp_unslash($_POST['status'] ?? ''), ['active', 'inactive']) ?
            sanitize_text_field(wp_unslash($_POST['status'])) : 'active';

        if (!$id || empty($name) || empty($email) || !is_email($email)) {
            wp_send_json_error(__('Invalid data provided', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;

        // Check if email already exists (excluding current customer)
        $cache_key = 'orpl_customer_email_' . md5($email . '_' . $id);
        $existing = wp_cache_get($cache_key, self::CACHE_GROUP);

        if (false === $existing) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$this->customers_table} WHERE email = %s AND id != %d",
                $email,
                $id
            ));
            wp_cache_set($cache_key, $existing, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS);
        }

        if ($existing) {
            wp_send_json_error(__('Customer email already exists', 'obydullah-restaurant-pos-lite'));
        }

        $result = $wpdb->update($this->customers_table, [
            'name' => $name,
            'email' => $email,
            'mobile' => $mobile,
            'address' => $address,
            'status' => $status
        ], ['id' => $id], ['%s', '%s', '%s', '%s', '%s'], ['%d']);

        if ($result === false) {
            wp_send_json_error(__('Failed to update customer', 'obydullah-restaurant-pos-lite'));
        }

        // Clear relevant caches
        $this->clear_customer_caches();
        wp_cache_delete('orpl_customer_' . $id, self::CACHE_GROUP);

        wp_send_json_success(__('Customer updated successfully', 'obydullah-restaurant-pos-lite'));
    }

    /** Delete customer */
    public function ajax_delete_orpl_customer()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_delete_customer')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error(__('Invalid customer ID', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;

        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT email FROM {$this->customers_table} WHERE id = %d",
            $id
        ));

        $result = $wpdb->delete($this->customers_table, ['id' => $id], ['%d']);

        if ($result === false) {
            wp_send_json_error(__('Failed to delete customer', 'obydullah-restaurant-pos-lite'));
        }

        // Clear relevant caches
        $this->clear_customer_caches();
        if ($customer) {
            wp_cache_delete('orpl_customer_email_' . md5($customer->email), self::CACHE_GROUP);
            wp_cache_delete('orpl_customer_' . $id, self::CACHE_GROUP);
        }

        wp_send_json_success(__('Customer deleted successfully', 'obydullah-restaurant-pos-lite'));
    }

    /**
     * Clear all customer-related caches
     */
    private function clear_customer_caches()
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_orpl_customers_%')
        );
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_orpl_customers_%')
        );

        wp_cache_delete('orpl_customer_email_', self::CACHE_GROUP);
    }
}
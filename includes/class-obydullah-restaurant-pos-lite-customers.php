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

                            <div class="mt-4 d-flex gap-2">
                                <button type="submit" id="submit-customer" class="btn btn-primary flex-grow-1">
                                    <span class="btn-text"><?php esc_html_e('Save Customer', 'obydullah-restaurant-pos-lite'); ?></span>
                                    <span class="spinner" style="display:none;"></span>
                                </button>
                                <button type="button" id="cancel-edit" class="btn btn-secondary" style="display:none;">
                                    <?php esc_html_e('Cancel', 'obydullah-restaurant-pos-lite'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right: Customers Table -->
                <div class="col-md-8">
                    <div class="bg-light p-4 rounded shadow-sm border">
                        <!-- Search and Filter Section -->
                        <div class="customer-filters mb-4 p-3 bg-white border rounded shadow-sm">
                            <div class="d-flex flex-wrap align-items-center gap-4">
                                <!-- Search -->
                                <div class="filter-group">
                                    <label for="customer-search" class="form-label small text-muted mb-1">
                                        <?php esc_html_e('Search Customer', 'obydullah-restaurant-pos-lite'); ?>
                                    </label>
                                    <input type="text" id="customer-search" class="form-control form-control-sm" style="width: 220px;"
                                        placeholder="<?php esc_attr_e('Name or mobile...', 'obydullah-restaurant-pos-lite'); ?>">
                                </div>

                                <!-- Status Filter -->
                                <div class="filter-group">
                                    <label for="status-filter" class="form-label small text-muted mb-1">
                                        <?php esc_html_e('Customer Status', 'obydullah-restaurant-pos-lite'); ?>
                                    </label>
                                    <select id="status-filter" class="form-control form-control-sm" style="width: 160px;">
                                        <option value=""><?php esc_html_e('All Status', 'obydullah-restaurant-pos-lite'); ?></option>
                                        <option value="active"><?php esc_html_e('Active', 'obydullah-restaurant-pos-lite'); ?></option>
                                        <option value="inactive"><?php esc_html_e('Inactive', 'obydullah-restaurant-pos-lite'); ?></option>
                                    </select>
                                </div>

                                <!-- Action Buttons -->
                                <div class="filter-group align-self-end">
                                    <div class="d-flex align-items-center gap-2 mt-2">
                                        <button type="button" id="refresh-customers" class="btn btn-primary btn-sm px-3">
                                            <?php esc_html_e('Search', 'obydullah-restaurant-pos-lite'); ?>
                                        </button>
                                        <button type="button" id="reset-filters" class="btn btn-outline-secondary btn-sm px-3">
                                            <?php esc_html_e('Reset', 'obydullah-restaurant-pos-lite'); ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Customers Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-bordered mb-3">
                                <thead>
                                    <tr class="bg-primary text-white">
                                        <th><?php esc_html_e('Name', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th><?php esc_html_e('Email', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th><?php esc_html_e('Mobile', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th><?php esc_html_e('Address', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th><?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th class="text-center"><?php esc_html_e('Actions', 'obydullah-restaurant-pos-lite'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="customer-list">
                                    <tr>
                                        <td colspan="6" class="text-center p-4">
                                            <span class="spinner is-active"></span>
                                            <?php esc_html_e('Loading customers...', 'obydullah-restaurant-pos-lite'); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="tablenav-pages">
                                <span class="displaying-num" id="displaying-num">0
                                    <?php esc_html_e('items', 'obydullah-restaurant-pos-lite'); ?></span>
                                <span class="pagination-links d-inline-flex align-items-center gap-1 ms-2">
                                    <a class="first-page btn btn-sm btn-secondary" href="#">
                                        <span class="screen-reader-text"><?php esc_html_e('First page', 'obydullah-restaurant-pos-lite'); ?></span>
                                        <span aria-hidden="true">«</span>
                                    </a>
                                    <a class="prev-page btn btn-sm btn-secondary" href="#">
                                        <span class="screen-reader-text"><?php esc_html_e('Previous page', 'obydullah-restaurant-pos-lite'); ?></span>
                                        <span aria-hidden="true">‹</span>
                                    </a>
                                    <span class="paging-input d-inline-flex align-items-center gap-1">
                                        <label for="current-page-selector" class="screen-reader-text"><?php esc_html_e('Current Page', 'obydullah-restaurant-pos-lite'); ?></label>
                                        <input class="current-page form-control form-control-sm" style="width: 50px;" id="current-page-selector" type="text" name="paged" value="1" size="3" aria-describedby="table-paging">
                                        <span class="tablenav-paging-text m-1">
                                            <?php esc_html_e('of', 'obydullah-restaurant-pos-lite'); ?> <span class="total-pages">1</span></span>
                                    </span>
                                    <a class="next-page btn btn-sm btn-secondary" href="#">
                                        <span class="screen-reader-text"><?php esc_html_e('Next page', 'obydullah-restaurant-pos-lite'); ?></span>
                                        <span aria-hidden="true">›</span>
                                    </a>
                                    <a class="last-page btn btn-sm btn-secondary" href="#">
                                        <span class="screen-reader-text"><?php esc_html_e('Last page', 'obydullah-restaurant-pos-lite'); ?></span>
                                        <span aria-hidden="true">»</span>
                                    </a>
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

        <script type="text/javascript">
            var orplCustomersData = {
                ajaxUrl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                nonce_get_customers: '<?php echo esc_attr(wp_create_nonce('orpl_get_customers')); ?>',
                nonce_add_customer: '<?php echo esc_attr(wp_create_nonce('orpl_add_customer')); ?>',
                nonce_edit_customer: '<?php echo esc_attr(wp_create_nonce('orpl_edit_customer')); ?>',
                nonce_delete_customer: '<?php echo esc_attr(wp_create_nonce('orpl_delete_customer')); ?>',
                strings: {
                    loading_customers: '<?php echo esc_js(__('Loading customers...', 'obydullah-restaurant-pos-lite')); ?>',
                    no_customers: '<?php echo esc_js(__('No customers found.', 'obydullah-restaurant-pos-lite')); ?>',
                    failed_load: '<?php echo esc_js(__('Failed to load customers.', 'obydullah-restaurant-pos-lite')); ?>',
                    active: '<?php echo esc_js(__('Active', 'obydullah-restaurant-pos-lite')); ?>',
                    inactive: '<?php echo esc_js(__('Inactive', 'obydullah-restaurant-pos-lite')); ?>',
                    edit: '<?php echo esc_js(__('Edit', 'obydullah-restaurant-pos-lite')); ?>',
                    delete: '<?php echo esc_js(__('Delete', 'obydullah-restaurant-pos-lite')); ?>',
                    items: '<?php echo esc_js(__('items', 'obydullah-restaurant-pos-lite')); ?>',
                    name_required: '<?php echo esc_js(__('Customer name is required', 'obydullah-restaurant-pos-lite')); ?>',
                    email_required: '<?php echo esc_js(__('Email is required', 'obydullah-restaurant-pos-lite')); ?>',
                    email_invalid: '<?php echo esc_js(__('Please enter a valid email address', 'obydullah-restaurant-pos-lite')); ?>',
                    error: '<?php echo esc_js(__('Error', 'obydullah-restaurant-pos-lite')); ?>',
                    request_failed: '<?php echo esc_js(__('Request failed. Please try again.', 'obydullah-restaurant-pos-lite')); ?>',
                    confirm_delete: '<?php echo esc_js(__('Are you sure you want to delete this customer?', 'obydullah-restaurant-pos-lite')); ?>',
                    deleting: '<?php echo esc_js(__('Deleting...', 'obydullah-restaurant-pos-lite')); ?>',
                    delete_failed: '<?php echo esc_js(__('Delete request failed. Please try again.', 'obydullah-restaurant-pos-lite')); ?>',
                    updating: '<?php echo esc_js(__('Updating...', 'obydullah-restaurant-pos-lite')); ?>',
                    saving: '<?php echo esc_js(__('Saving...', 'obydullah-restaurant-pos-lite')); ?>',
                    update_customer: '<?php echo esc_js(__('Update Customer', 'obydullah-restaurant-pos-lite')); ?>',
                    save_customer: '<?php echo esc_js(__('Save Customer', 'obydullah-restaurant-pos-lite')); ?>',
                    edit_customer: '<?php echo esc_js(__('Edit Customer', 'obydullah-restaurant-pos-lite')); ?>',
                    add_new_customer: '<?php echo esc_js(__('Add New Customer', 'obydullah-restaurant-pos-lite')); ?>'
                }
            };
        </script>
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
                $where_conditions[] = "(name LIKE %s OR mobile LIKE %s)";
                $search_like = '%' . $wpdb->esc_like($search) . '%';
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
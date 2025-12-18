<?php
/**
 * Product Management
 *
 * @package Obydullah_Restaurant_POS_Lite
 * @since   1.0.0
 */
if (!defined('ABSPATH'))
    exit;

class Obydullah_Restaurant_POS_Lite_Products
{
    public function __construct()
    {
        add_action('wp_ajax_orpl_add_product', [$this, 'ajax_add_orpl_product']);
        add_action('wp_ajax_orpl_get_products', [$this, 'ajax_get_orpl_products']);
        add_action('wp_ajax_orpl_edit_product', [$this, 'ajax_edit_orpl_product']);
        add_action('wp_ajax_orpl_delete_product', [$this, 'ajax_delete_orpl_product']);
        add_action('wp_ajax_orpl_get_categories_for_products', [$this, 'ajax_get_orpl_categories_for_products']);
    }

    /**
     * Render the products page
     */
    public function render_page()
    {
        ?>
        <div class="wrap orpl-products-page">
            <h1 class="wp-heading-inline mb-3">
                <?php esc_html_e('Products', 'obydullah-restaurant-pos-lite'); ?>
            </h1>
            <hr class="wp-header-end">

            <div class="row mt-3">
                <!-- Left: Add/Edit Product Form -->
                <div class="col-lg-4">
                    <div class="bg-light p-4 rounded shadow-sm">
                        <h2 id="form-title" class="mb-3 mt-1">
                            <?php esc_html_e('Add New Product', 'obydullah-restaurant-pos-lite'); ?>
                        </h2>
                        <form id="add-product-form" method="post" enctype="multipart/form-data">
                            <?php wp_nonce_field('orpl_add_product', 'product_nonce'); ?>
                            <input type="hidden" id="product-id" name="id" value="">

                            <div class="mb-3">
                                <label for="product-name" class="form-label">
                                    <?php esc_html_e('Product Name', 'obydullah-restaurant-pos-lite'); ?> <span class="text-danger">*</span>
                                </label>
                                <input name="name" id="product-name" type="text" class="form-control" value="" required>
                            </div>

                            <div class="mb-3">
                                <label for="product-category" class="form-label">
                                    <?php esc_html_e('Category', 'obydullah-restaurant-pos-lite'); ?> <span class="text-danger">*</span>
                                </label>
                                <select name="fk_category_id" id="product-category" class="form-control" required>
                                    <option value="">
                                        <?php esc_html_e('Select Category', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="product-image" class="form-label">
                                    <?php esc_html_e('Product Image', 'obydullah-restaurant-pos-lite'); ?>
                                </label>
                                <input name="image" id="product-image" type="file" class="form-control" accept="image/*">
                                <div id="image-preview" class="mt-2" style="display: none;">
                                    <img id="preview-img" class="img-thumbnail" style="max-height: 150px;">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="product-status" class="form-label">
                                    <?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?>
                                </label>
                                <select name="status" id="product-status" class="form-control">
                                    <option value="active">
                                        <?php esc_html_e('Active', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                    <option value="inactive">
                                        <?php esc_html_e('Inactive', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                </select>
                            </div>

                            <div class="d-flex mt-4">
                                <button type="submit" id="submit-product" class="btn-primary mr-2">
                                    <span class="btn-text"><?php esc_html_e('Save Product', 'obydullah-restaurant-pos-lite'); ?></span>
                                    <span class="spinner" style="display: none; margin-left: 5px;"></span>
                                </button>
                                <button type="button" id="cancel-edit" class="btn-secondary" style="display: none;">
                                    <?php esc_html_e('Cancel', 'obydullah-restaurant-pos-lite'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right: Products Table -->
                <div class="col-lg-8">
                    <div class="bg-light p-4 rounded shadow-sm border">
                        <!-- Search Box -->
                        <div class="search-section mb-4 p-3 bg-white border rounded shadow-sm">
                            <div class="d-flex flex-wrap align-items-center gap-3">
                                <div class="search-group flex-grow-1">
                                    <label for="product-search" class="form-label small text-muted mb-1">
                                        <?php esc_html_e('Search Products', 'obydullah-restaurant-pos-lite'); ?>
                                    </label>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="position-relative flex-grow-1">
                                            <input type="text" id="product-search"
                                                class="form-control form-control-sm"
                                                placeholder="<?php esc_attr_e('Enter product name...', 'obydullah-restaurant-pos-lite'); ?>">
                                            <button type="button" id="clear-search" class="btn btn-sm btn-link text-decoration-none position-absolute end-0 top-50 translate-middle-y me-2" style="display: none; padding: 0.25rem;">
                                                <span class="text-muted">×</span>
                                            </button>
                                        </div>
                                        <button type="button" id="search-button" class="btn btn-primary btn-sm px-3">
                                            <?php esc_html_e('Search', 'obydullah-restaurant-pos-lite'); ?>
                                        </button>
                                    </div>
                                    <div class="form-text small text-muted mt-1">
                                        <?php esc_html_e('Search by product name, SKU, or description', 'obydullah-restaurant-pos-lite'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Products Table -->
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-bordered mb-3">
                                <thead>
                                    <tr class="bg-primary text-white">
                                        <th><?php esc_html_e('Image', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th><?php esc_html_e('Name', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th><?php esc_html_e('Category', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th><?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th class="text-center"><?php esc_html_e('Actions', 'obydullah-restaurant-pos-lite'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="product-list">
                                    <tr>
                                        <td colspan="5" class="text-center p-4">
                                            <span class="spinner is-active"></span>
                                            <?php esc_html_e('Loading products...', 'obydullah-restaurant-pos-lite'); ?>
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
        <?php
    }

    /** Get categories for product form */
    public function ajax_get_orpl_categories_for_products()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_get_categories_for_products')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'), 403);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'orpl_categories';
        $category_status = 'active';

        // Get categories with caching
        $cache_key = 'orpl_active_categories';
        $categories = wp_cache_get($cache_key, 'obydullah-restaurant-pos-lite');

        if (false === $categories) {
            $categories = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT id, name FROM {$table_name} WHERE status = %s ORDER BY name ASC",
                    $category_status
                )
            );

            // Cache for 5 minutes
            wp_cache_set($cache_key, $categories, 'obydullah-restaurant-pos-lite', 300);
        }

        wp_send_json_success($categories);
    }

    /** Get all products with pagination and search */
    public function ajax_get_orpl_products()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_get_products')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'), 403);
        }

        global $wpdb;
        $products_table = $wpdb->prefix . 'orpl_products';
        $categories_table = $wpdb->prefix . 'orpl_categories';

        // Get parameters with defaults
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
        $search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';

        // Check if specific product is requested
        $product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($product_id > 0) {
            $products = $wpdb->get_results($wpdb->prepare(
                "SELECT p.*, c.name as category_name 
            FROM {$products_table} p 
            LEFT JOIN {$categories_table} c ON p.fk_category_id = c.id 
            WHERE p.id = %d",
                $product_id
            ));

            wp_send_json_success(['products' => $products]);
        }

        // Generate cache key based on search and pagination
        $cache_key = 'orpl_products_' . md5($search . '_' . $page . '_' . $per_page);
        $cached_data = wp_cache_get($cache_key, 'obydullah-restaurant-pos-lite');

        if (false !== $cached_data) {
            wp_send_json_success($cached_data);
        }

        // Build query based on search
        if (!empty($search)) {
            $search_like = '%' . $wpdb->esc_like($search) . '%';
            $total_items = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$products_table} p WHERE p.name LIKE %s",
                $search_like
            ));

            // Calculate pagination
            $total_pages = ceil($total_items / $per_page);
            $total_pages = max(1, $total_pages);
            $offset = ($page - 1) * $per_page;

            $products = $wpdb->get_results($wpdb->prepare(
                "SELECT p.*, c.name as category_name 
            FROM {$products_table} p 
            LEFT JOIN {$categories_table} c ON p.fk_category_id = c.id 
            WHERE p.name LIKE %s 
            ORDER BY p.created_at DESC 
            LIMIT %d OFFSET %d",
                $search_like,
                $per_page,
                $offset
            ));
        } else {
            $total_items = $wpdb->get_var("SELECT COUNT(*) FROM {$products_table} p");

            // Calculate pagination
            $total_pages = ceil($total_items / $per_page);
            $total_pages = max(1, $total_pages);
            $offset = ($page - 1) * $per_page;

            $products = $wpdb->get_results($wpdb->prepare(
                "SELECT p.*, c.name as category_name 
            FROM {$products_table} p 
            LEFT JOIN {$categories_table} c ON p.fk_category_id = c.id 
            ORDER BY p.created_at DESC 
            LIMIT %d OFFSET %d",
                $per_page,
                $offset
            ));
        }

        $response_data = [
            'products' => $products ?: [],
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => (int) $total_items,
                'total_pages' => $total_pages
            ]
        ];

        // Cache the results for 2 minutes
        wp_cache_set($cache_key, $response_data, 'obydullah-restaurant-pos-lite', 120);

        wp_send_json_success($response_data);
    }

    /** Add product */
    public function ajax_add_orpl_product()
    {
        // Verify nonce
        if (!check_ajax_referer('orpl_add_product', 'nonce', false)) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'), 403);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'orpl_products';

        // Get and sanitize input
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $fk_category_id = isset($_POST['fk_category_id']) ? intval($_POST['fk_category_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'active';

        // Validate required fields
        if (empty($name)) {
            wp_send_json_error(__('Product name is required', 'obydullah-restaurant-pos-lite'));
        }

        if ($fk_category_id <= 0) {
            wp_send_json_error(__('Please select a valid category', 'obydullah-restaurant-pos-lite'));
        }

        // Check for duplicate product name in same category
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE name = %s AND fk_category_id = %d",
            $name,
            $fk_category_id
        ));

        if ($existing) {
            wp_send_json_error(__('Product name already exists in this category', 'obydullah-restaurant-pos-lite'));
        }

        // Handle image upload if provided
        $image_url = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = wp_handle_upload($_FILES['image'], array('test_form' => false));
            if (isset($upload['url']) && !isset($upload['error'])) {
                $image_url = $upload['url'];
            }
        }

        // Insert the product
        $result = $wpdb->insert($table_name, [
            'name' => $name,
            'fk_category_id' => $fk_category_id,
            'image' => $image_url,
            'status' => $status,
            'created_at' => current_time('mysql')
        ], ['%s', '%d', '%s', '%s', '%s']);

        if ($result === false) {
            wp_send_json_error(__('Database error: Failed to add product', 'obydullah-restaurant-pos-lite'));
        }

        $this->clear_orpl_product_caches();

        wp_send_json_success(__('Product added successfully', 'obydullah-restaurant-pos-lite'));
    }

    /** Edit product */
    public function ajax_edit_orpl_product()
    {
        // Verify nonce
        if (!check_ajax_referer('orpl_edit_product', 'nonce', false)) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'), 403);
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'orpl_products';

        // Get and validate ID
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        if ($id <= 0) {
            wp_send_json_error(__('Invalid product ID', 'obydullah-restaurant-pos-lite'));
        }

        // Get and sanitize input
        $name = isset($_POST['name']) ? sanitize_text_field(wp_unslash($_POST['name'])) : '';
        $fk_category_id = isset($_POST['fk_category_id']) ? intval($_POST['fk_category_id']) : 0;
        $status = isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'active';

        // Validate required fields
        if (empty($name)) {
            wp_send_json_error(__('Product name is required', 'obydullah-restaurant-pos-lite'));
        }

        if ($fk_category_id <= 0) {
            wp_send_json_error(__('Please select a valid category', 'obydullah-restaurant-pos-lite'));
        }

        // Check if product name already exists (excluding current product)
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$table_name} WHERE name = %s AND fk_category_id = %d AND id != %d",
            $name,
            $fk_category_id,
            $id
        ));

        if ($existing) {
            wp_send_json_error(__('Product name already exists in this category', 'obydullah-restaurant-pos-lite'));
        }

        // Prepare update data
        $update_data = [
            'name' => $name,
            'fk_category_id' => $fk_category_id,
            'status' => $status
        ];
        $format = ['%s', '%d', '%s'];

        // Handle image upload if new image provided
        if (isset($_FILES['image']['error']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload = wp_handle_upload($_FILES['image'], array('test_form' => false));
            if (isset($upload['url']) && !isset($upload['error'])) {
                $update_data['image'] = $upload['url'];
                $format[] = '%s';
            }
        }

        $result = $wpdb->update($table_name, $update_data, ['id' => $id], $format, ['%d']);

        if ($result === false) {
            wp_send_json_error(__('Database error: Failed to update product', 'obydullah-restaurant-pos-lite'));
        }

        $this->clear_orpl_product_caches();

        wp_send_json_success(__('Product updated successfully', 'obydullah-restaurant-pos-lite'));
    }

    /** Delete product */
    public function ajax_delete_orpl_product()
    {
        // Verify nonce
        if (!check_ajax_referer('orpl_delete_product', 'nonce', false)) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'), 403);
        }

        global $wpdb;
        $table = $wpdb->prefix . 'orpl_products';
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if ($id <= 0) {
            wp_send_json_error(__('Invalid product ID', 'obydullah-restaurant-pos-lite'));
        }

        $result = $wpdb->delete($table, ['id' => $id], ['%d']);

        if ($result === false) {
            wp_send_json_error(__('Database error: Failed to delete product', 'obydullah-restaurant-pos-lite'));
        }

        $this->clear_orpl_product_caches();

        wp_send_json_success(__('Product deleted successfully', 'obydullah-restaurant-pos-lite'));
    }

    /**
     * Clear all product-related caches
     */
    private function clear_orpl_product_caches()
    {
        // Delete object cache entries
        wp_cache_delete('orpl_products_all', 'obydullah-restaurant-pos-lite');
        wp_cache_delete('orpl_products_active', 'obydullah-restaurant-pos-lite');
        wp_cache_delete('orpl_active_categories', 'obydullah-restaurant-pos-lite');

        // Clear transients pattern
        $patterns = [
            '_transient_orpl_products_%',
            '_transient_timeout_orpl_products_%',
            '_transient_orpl_product_exists_%',
            '_transient_timeout_orpl_product_exists_%',
            '_transient_orpl_product_category_%',
            '_transient_timeout_orpl_product_category_%'
        ];

        global $wpdb;
        foreach ($patterns as $pattern) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $pattern
            ));
        }
    }
}
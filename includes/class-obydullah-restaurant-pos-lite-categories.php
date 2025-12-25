<?php
/**
 * Product Categories Management
 *
 * @package Obydullah_Restaurant_POS_Lite
 * @since   1.0.0
 */
if (!defined('ABSPATH'))
    exit;

class Obydullah_Restaurant_POS_Lite_Categories
{
    public function __construct()
    {
        add_action('wp_ajax_orpl_add_product_category', [$this, 'ajax_add_orpl_category']);
        add_action('wp_ajax_orpl_get_product_categories', [$this, 'ajax_get_orpl_categories']);
        add_action('wp_ajax_orpl_edit_product_category', [$this, 'ajax_edit_orpl_category']);
        add_action('wp_ajax_orpl_delete_product_category', [$this, 'ajax_delete_orpl_category']);
    }

    /**
     * Render the categories page
     */
    public function render_page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline mb-3">
                <?php esc_html_e('Product Categories', 'obydullah-restaurant-pos-lite'); ?>
            </h1>
            <hr class="wp-header-end">

            <div class="row">
                <!-- Left Column: Form -->
                <div class="col-lg-4">
                    <div class="bg-light p-4 rounded shadow-sm mb-3">
                        <h2 id="form-title" class="mb-3 mt-1">
                            <?php esc_html_e('Add New Category', 'obydullah-restaurant-pos-lite'); ?>
                        </h2>
                        <form id="add-category-form" method="post" class="form-group">
                            <?php wp_nonce_field('orpl_add_product_category', 'product_category_nonce'); ?>
                            <input type="hidden" id="category-id" name="id" value="">

                            <div class="form-group mb-3">
                                <label for="category-name" class="form-label">
                                    <?php esc_html_e('Name', 'obydullah-restaurant-pos-lite'); ?>
                                    <span class="text-danger">*</span>
                                </label>
                                <input name="name" id="category-name" type="text" class="form-control" value="" required>
                            </div>

                            <div class="form-group mb-3">
                                <label for="category-status" class="form-label">
                                    <?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?>
                                </label>
                                <select name="status" id="category-status" class="form-control">
                                    <option value="active">
                                        <?php esc_html_e('Active', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                    <option value="inactive">
                                        <?php esc_html_e('Inactive', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                </select>
                            </div>

                            <div class="d-flex mt-3">
                                <button type="submit" id="submit-category" class="btn btn-primary mr-2">
                                    <span class="btn-text">
                                        <?php esc_html_e('Save Category', 'obydullah-restaurant-pos-lite'); ?>
                                    </span>
                                    <span class="spinner" style="display: none;"></span>
                                </button>

                                <button type="button" id="cancel-edit" class="btn btn-secondary" style="display: none;">
                                    <?php esc_html_e('Cancel', 'obydullah-restaurant-pos-lite'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Table -->
                <div class="col-lg-8">
                    <div class="bg-light p-4 rounded shadow-sm">
                        <h2 class="mb-3 mt-1">
                            <?php esc_html_e('All Categories', 'obydullah-restaurant-pos-lite'); ?>
                        </h2>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr class="bg-primary text-white">
                                        <th><?php esc_html_e('Name', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th><?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?></th>
                                        <th class="text-right"><?php esc_html_e('Actions', 'obydullah-restaurant-pos-lite'); ?></th>
                                    </tr>
                                </thead>
                                <tbody id="category-list" class="bg-white">
                                    <tr>
                                        <td colspan="3" class="text-center">
                                            <span class="spinner is-active"></span>
                                            <?php esc_html_e('Loading categories...', 'obydullah-restaurant-pos-lite'); ?>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /** Get all categories */
    public function ajax_get_orpl_categories()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_get_product_categories')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'orpl_categories';

        // Get categories with caching
        $cache_key = 'orpl_categories_all';
        $categories = wp_cache_get($cache_key, 'obydullah-restaurant-pos-lite');

        if (false === $categories) {
            $categories = $wpdb->get_results(
                "SELECT id, name, status FROM {$table_name} ORDER BY id DESC"
            );

            // Cache for 5 minutes
            wp_cache_set($cache_key, $categories, 'obydullah-restaurant-pos-lite', 300);
        }

        // Send response
        wp_send_json_success($categories);
    }

    /** Add category */
    public function ajax_add_orpl_category()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_add_product_category')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'orpl_categories';

        // Unslash and sanitize input
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $status = in_array(wp_unslash($_POST['status'] ?? ''), ['active', 'inactive']) ?
            sanitize_text_field(wp_unslash($_POST['status'])) : 'active';

        if (empty($name)) {
            wp_send_json_error(__('Category name is required', 'obydullah-restaurant-pos-lite'));
        }

        // Check if category name already exists with caching
        $cache_key = 'orpl_category_exists_' . md5($name);
        $existing = wp_cache_get($cache_key, 'obydullah-restaurant-pos-lite');

        if (false === $existing) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE name = %s",
                $name
            ));

            wp_cache_set($cache_key, $existing, 'obydullah-restaurant-pos-lite', 300);
        }

        if ($existing) {
            wp_send_json_error(__('Category name already exists', 'obydullah-restaurant-pos-lite'));
        }

        $result = $wpdb->insert(
            $table_name,
            array(
                'name' => $name,
                'status' => $status
            ),
            array(
                '%s',
                '%s'
            )
        );

        if ($result === false) {
            wp_send_json_error(__('Failed to add category', 'obydullah-restaurant-pos-lite'));
        }

        // Clear all related caches
        $this->clear_orpl_category_caches();

        wp_send_json_success(__('Category added successfully', 'obydullah-restaurant-pos-lite'));
    }

    /** Edit category */
    public function ajax_edit_orpl_category()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_edit_product_category')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'orpl_categories';

        $id = intval($_POST['id'] ?? 0);
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $status = in_array(wp_unslash($_POST['status'] ?? ''), ['active', 'inactive']) ?
            sanitize_text_field(wp_unslash($_POST['status'])) : 'active';

        if (!$id || empty($name)) {
            wp_send_json_error('Invalid data provided');
        }

        // Check if category name already exists (excluding current category) with caching
        $cache_key = 'orpl_category_exists_' . md5($name . '_' . $id);
        $existing = wp_cache_get($cache_key, 'obydullah-restaurant-pos-lite');

        if (false === $existing) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE name = %s AND id != %d",
                $name,
                $id
            ));
            wp_cache_set($cache_key, $existing, 'obydullah-restaurant-pos-lite', 300);
        }

        if ($existing) {
            wp_send_json_error(__('Category name already exists', 'obydullah-restaurant-pos-lite'));
        }

        $result = $wpdb->update(
            "{$table_name}",
            array(
                'name' => $name,
                'status' => $status
            ),
            array(
                'id' => $id
            ),
            array(
                '%s',
                '%s'
            ),
            array(
                '%d'
            )
        );

        if ($result === false) {
            wp_send_json_error('Failed to update category');
        }

        // Clear all related caches
        $this->clear_orpl_category_caches();

        wp_send_json_success('Category updated successfully');
    }

    /** Delete category */
    public function ajax_delete_orpl_category()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_delete_product_category')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'orpl_categories';
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error('Invalid category ID');
        }

        $result = $wpdb->delete(
            $table_name,
            array('id' => $id),
            array('%d')
        );

        if ($result === false) {
            wp_send_json_error('Failed to delete category');
        }

        // Clear all related caches
        $this->clear_orpl_category_caches();

        wp_send_json_success('Category deleted successfully');
    }

    /**
     * Clear all category-related caches
     */
    private function clear_orpl_category_caches()
    {
        // Delete cache
        wp_cache_delete('orpl_categories_all', 'obydullah-restaurant-pos-lite');
        wp_cache_delete('orpl_categories_active', 'obydullah-restaurant-pos-lite');

        global $wpdb;

        // Remove transient entries
        $transient_like = '_transient_orpl_category_exists_%';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $transient_like
            )
        );

        $transient_timeout_like = '_transient_timeout_orpl_category_exists_%';
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $transient_timeout_like
            )
        );
    }
}
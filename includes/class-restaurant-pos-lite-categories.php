<?php
/**
 * Product Categories Management
 *
 * @package Restaurant_POS_Lite
 * @since   1.0.0
 */
if (!defined('ABSPATH'))
    exit;

class Restaurant_POS_Lite_Categories
{
    public function __construct()
    {
        add_action('wp_ajax_add_product_category', [$this, 'ajax_add_category']);
        add_action('wp_ajax_get_product_categories', [$this, 'ajax_get_categories']);
        add_action('wp_ajax_edit_product_category', [$this, 'ajax_edit_category']);
        add_action('wp_ajax_delete_product_category', [$this, 'ajax_delete_category']);
    }

    /**
     * Render the categories page
     */
    public function render_page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline" style="margin-bottom:20px;">Product Categories</h1>
            <hr class="wp-header-end">

            <div id="col-container" class="wp-clearfix" style="display:flex;gap:24px;">
                <!-- Left: Add/Edit Category Form -->
                <div id="col-left" style="flex:1; max-width:380px;">
                    <div class="col-wrap"
                        style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">
                        <h2 id="form-title" style="margin:0 0 20px 0;padding:0;font-size:16px;font-weight:600;color:#1d2327;">
                            Add New Category
                        </h2>
                        <form id="add-category-form" method="post">
                            <?php wp_nonce_field('add_product_category', 'product_category_nonce'); ?>
                            <input type="hidden" id="category-id" name="id" value="">

                            <!-- Horizontal layout with equal width -->
                            <div style="display:flex;flex-direction:column;gap:15px;">
                                <div style="display:flex;gap:12px;align-items:center;">
                                    <div class="form-field form-required" style="flex:1;">
                                        <label for="category-name"
                                            style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                            Name <span style="color:#d63638;">*</span>
                                        </label>
                                        <input name="name" id="category-name" type="text" value="" required
                                            style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;">
                                    </div>

                                    <div class="form-field form-required" style="flex:1;">
                                        <label for="category-status"
                                            style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                            Status
                                        </label>
                                        <select name="status" id="category-status"
                                            style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;cursor:pointer;">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top:20px;display:flex;gap:10px;">
                                <button type="submit" id="submit-category" class="button button-primary"
                                    style="flex:1;padding:8px 12px;font-size:13px;font-weight:500;">
                                    <span class="btn-text">Save Category</span>
                                    <span class="spinner" style="float:none;margin:0;display:none;"></span>
                                </button>
                                <button type="button" id="cancel-edit" class="button"
                                    style="display:none;flex:1;padding:8px 12px;font-size:13px;font-weight:500;">
                                    Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right: Categories Table -->
                <div id="col-right" style="flex:2;">
                    <div class="col-wrap"
                        style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">
                        <table class="wp-list-table widefat fixed striped table-view-list">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Status</th>
                                    <th style="text-align:center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="category-list">
                                <tr>
                                    <td colspan="3" class="loading-categories" style="text-align:center;">
                                        <span class="spinner is-active"></span>
                                        Loading categories...
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <script>
                jQuery(document).ready(function ($) {
                    let isSubmitting = false;

                    loadCategories();

                    function loadCategories() {
                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'get_product_categories',
                                nonce: '<?php echo esc_attr(wp_create_nonce("get_product_categories")); ?>'
                            },
                            success: function (response) {
                                let tbody = $('#category-list').empty();
                                if (response.success) {
                                    if (!response.data.length) {
                                        tbody.append('<tr><td colspan="3" style="text-align:center;">No categories found.</td></tr>');
                                        return;
                                    }

                                    $.each(response.data, function (_, cat) {
                                        let row = $('<tr>').attr('data-category-id', cat.id);
                                        row.append($('<td>').text(cat.name));

                                        // Status with color coding
                                        let statusClass = cat.status === 'active' ? 'status-active' : 'status-inactive';
                                        let statusText = cat.status.charAt(0).toUpperCase() + cat.status.slice(1);
                                        row.append($('<td>').append(
                                            $('<span>').addClass(statusClass).text(statusText)
                                        ));

                                        row.append($('<td style="text-align:center;">')
                                            .append('<button class="button button-small edit-category" style="margin-right:5px;">Edit</button>')
                                            .append('<button class="button button-small button-link-delete delete-category">Delete</button>')
                                        );
                                        tbody.append(row);
                                    });
                                } else {
                                    tbody.append('<tr><td colspan="3" style="color:red;text-align:center;">' + response.data + '</td></tr>');
                                }
                            },
                            error: function () {
                                $('#category-list').html('<tr><td colspan="3" style="color:red;text-align:center;">Failed to load categories.</td></tr>');
                            }
                        });
                    }

                    $('#add-category-form').on('submit', function (e) {
                        e.preventDefault();

                        // Prevent double submission
                        if (isSubmitting) {
                            return false;
                        }

                        let id = $('#category-id').val();
                        let action = id ? 'edit_product_category' : 'add_product_category';
                        let name = $('#category-name').val().trim();
                        let status = $('#category-status').val();

                        if (!name) {
                            alert('Please enter a category name');
                            return false;
                        }

                        // Set submitting state
                        isSubmitting = true;
                        setButtonLoading(true);

                        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            action: action,
                            id: id,
                            name: name,
                            status: status,
                            nonce: '<?php echo esc_attr(wp_create_nonce("add_product_category")); ?>'
                        }, function (res) {
                            if (res.success) {
                                resetForm();
                                loadCategories();
                            } else {
                                alert('Error: ' + res.data);
                            }
                        }).fail(() => alert('Request failed. Please try again.'))
                            .always(function () {
                                // Reset submitting state
                                isSubmitting = false;
                                setButtonLoading(false);
                            });
                    });

                    $('#cancel-edit').on('click', function () {
                        resetForm();
                    });

                    $(document).on('click', '.edit-category', function () {
                        let row = $(this).closest('tr');
                        $('#category-id').val(row.data('category-id'));
                        $('#category-name').val(row.find('td').eq(0).text());
                        $('#category-status').val(row.find('td').eq(1).find('span').hasClass('status-active') ? 'active' : 'inactive');
                        $('#form-title').text('Edit Category');
                        $('#submit-category').find('.btn-text').text('Update Category');
                        $('#cancel-edit').show();
                    });

                    $(document).on('click', '.delete-category', function () {
                        if (!confirm('Are you sure you want to delete this category?')) return;

                        let button = $(this);
                        let originalText = button.text();
                        let id = $(this).closest('tr').data('category-id');

                        // Disable button and show loading
                        button.prop('disabled', true).text('Deleting...');

                        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            action: 'delete_product_category',
                            id: id,
                            nonce: '<?php echo esc_attr(wp_create_nonce("delete_product_category")); ?>'
                        }, function (res) {
                            if (res.success) {
                                loadCategories();
                            } else {
                                alert(res.data);
                            }
                        }).fail(() => alert('Delete request failed. Please try again.'))
                            .always(function () {
                                // Re-enable button
                                button.prop('disabled', false).text(originalText);
                            });
                    });

                    function setButtonLoading(loading) {
                        let button = $('#submit-category');
                        let spinner = button.find('.spinner');
                        let btnText = button.find('.btn-text');

                        if (loading) {
                            button.prop('disabled', true).addClass('button-loading');
                            spinner.show();
                            btnText.text(button.hasClass('button-loading') ? 'Saving...' : 'Updating...');
                        } else {
                            button.prop('disabled', false).removeClass('button-loading');
                            spinner.hide();
                            btnText.text(button.find('.btn-text').text().includes('Update') ? 'Update Category' : 'Save Category');
                        }
                    }

                    function resetForm() {
                        $('#category-id').val('');
                        $('#category-name').val('');
                        $('#category-status').val('active');
                        $('#form-title').text('Add New Category');
                        $('#submit-category').find('.btn-text').text('Save Category');
                        $('#cancel-edit').hide();
                        $('#category-name').focus();

                        // Ensure button is enabled
                        setButtonLoading(false);
                    }
                });
            </script>
        </div>
        <?php
    }

    /** Get all categories */
    public function ajax_get_categories()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'get_product_categories')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'pos_categories';

        // Get categories with caching
        $cache_key = 'pos_categories_all';
        $categories = wp_cache_get($cache_key, 'wp-restaurant-pos');

        if (false === $categories) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $categories = $wpdb->get_results(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT id, name, status FROM $table_name ORDER BY id DESC"
            );

            // Cache for 5 minutes
            wp_cache_set($cache_key, $categories, 'wp-restaurant-pos', 300);
        }

        // Send response
        wp_send_json_success($categories);
    }

    /** Add category */
    public function ajax_add_category()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'add_product_category')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'pos_categories';

        // Unslash and sanitize input
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $status = in_array(wp_unslash($_POST['status'] ?? ''), ['active', 'inactive']) ?
            sanitize_text_field(wp_unslash($_POST['status'])) : 'active';

        if (empty($name)) {
            wp_send_json_error('Category name is required');
        }

        // Check if category name already exists with caching
        $cache_key = 'pos_category_exists_' . md5($name);
        $existing = wp_cache_get($cache_key, 'wp-restaurant-pos');

        if (false === $existing) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE name = %s",
                $name
            ));
            wp_cache_set($cache_key, $existing, 'wp-restaurant-pos', 300);
        }

        if ($existing) {
            wp_send_json_error('Category name already exists');
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $result = $wpdb->insert($table_name, ['name' => $name, 'status' => $status], ['%s', '%s']);

        if ($result === false) {
            wp_send_json_error('Failed to add category');
        }

        // Clear all related caches
        $this->clear_category_caches();

        wp_send_json_success('Category added successfully');
    }

    /** Edit category */
    public function ajax_edit_category()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'add_product_category')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'pos_categories';

        $id = intval($_POST['id'] ?? 0);
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $status = in_array(wp_unslash($_POST['status'] ?? ''), ['active', 'inactive']) ?
            sanitize_text_field(wp_unslash($_POST['status'])) : 'active';

        if (!$id || empty($name)) {
            wp_send_json_error('Invalid data provided');
        }

        // Check if category name already exists (excluding current category) with caching
        $cache_key = 'pos_category_exists_' . md5($name . '_' . $id);
        $existing = wp_cache_get($cache_key, 'wp-restaurant-pos');

        if (false === $existing) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_name WHERE name = %s AND id != %d",
                $name,
                $id
            ));
            wp_cache_set($cache_key, $existing, 'wp-restaurant-pos', 300);
        }

        if ($existing) {
            wp_send_json_error('Category name already exists');
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $result = $wpdb->update(
            $table_name,
            ['name' => $name, 'status' => $status],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );

        if ($result === false) {
            wp_send_json_error('Failed to update category');
        }

        // Clear all related caches
        $this->clear_category_caches();

        wp_send_json_success('Category updated successfully');
    }

    /** Delete category */
    public function ajax_delete_category()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'delete_product_category')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'pos_categories';
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error('Invalid category ID');
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $result = $wpdb->delete($table_name, ['id' => $id], ['%d']);

        if ($result === false) {
            wp_send_json_error('Failed to delete category');
        }

        // Clear all related caches
        $this->clear_category_caches();

        wp_send_json_success('Category deleted successfully');
    }

    /**
     * Clear all category-related caches
     */
    private function clear_category_caches()
    {
        wp_cache_delete('pos_categories_all', 'wp-restaurant-pos');
        wp_cache_delete('pos_categories_active', 'wp-restaurant-pos');

        // Clear any category existence caches by pattern
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_pos_category_exists_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_pos_category_exists_%'");
    }
}
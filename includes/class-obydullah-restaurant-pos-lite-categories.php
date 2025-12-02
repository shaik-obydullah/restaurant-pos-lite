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
        // Create nonces for JavaScript
        $add_nonce = wp_create_nonce('orpl_add_product_category');
        $edit_nonce = wp_create_nonce('orpl_edit_product_category');
        $delete_nonce = wp_create_nonce('orpl_delete_product_category');
        $get_nonce = wp_create_nonce('orpl_get_product_categories');
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline" style="margin-bottom:20px;">
                <?php esc_html_e('Product Categories', 'obydullah-restaurant-pos-lite'); ?>
            </h1>
            <hr class="wp-header-end">

            <div id="col-container" class="wp-clearfix" style="display:flex;gap:24px;">
                <!-- Left: Add/Edit Category Form -->
                <div id="col-left" style="flex:1; max-width:380px;">
                    <div class="col-wrap"
                        style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">
                        <h2 id="form-title" style="margin:0 0 20px 0;padding:0;font-size:16px;font-weight:600;color:#1d2327;">
                            <?php esc_html_e('Add New Category', 'obydullah-restaurant-pos-lite'); ?>
                        </h2>
                        <form id="add-category-form" method="post">
                            <?php wp_nonce_field('orpl_add_product_category', 'product_category_nonce'); ?>
                            <input type="hidden" id="category-id" name="id" value="">

                            <!-- Horizontal layout with equal width -->
                            <div style="display:flex;flex-direction:column;gap:15px;">
                                <div style="display:flex;gap:12px;align-items:center;">
                                    <div class="form-field form-required" style="flex:1;">
                                        <label for="category-name"
                                            style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                            <?php esc_html_e('Name', 'obydullah-restaurant-pos-lite'); ?> <span
                                                style="color:#d63638;">*</span>
                                        </label>
                                        <input name="name" id="category-name" type="text" value="" required
                                            style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;">
                                    </div>

                                    <div class="form-field form-required" style="flex:1;">
                                        <label for="category-status"
                                            style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                            <?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?>
                                        </label>
                                        <select name="status" id="category-status"
                                            style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;cursor:pointer;">
                                            <option value="active">
                                                <?php esc_html_e('Active', 'obydullah-restaurant-pos-lite'); ?>
                                            </option>
                                            <option value="inactive">
                                                <?php esc_html_e('Inactive', 'obydullah-restaurant-pos-lite'); ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top:20px;display:flex;gap:10px;">
                                <button type="submit" id="submit-category" class="button button-primary"
                                    style="flex:1;padding:8px 12px;font-size:13px;font-weight:500;">
                                    <span
                                        class="btn-text"><?php esc_html_e('Save Category', 'obydullah-restaurant-pos-lite'); ?></span>
                                    <span class="spinner" style="float:none;margin:0;display:none;"></span>
                                </button>
                                <button type="button" id="cancel-edit" class="button"
                                    style="display:none;flex:1;padding:8px 12px;font-size:13px;font-weight:500;">
                                    <?php esc_html_e('Cancel', 'obydullah-restaurant-pos-lite'); ?>
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
                                    <th><?php esc_html_e('Name', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th style="text-align:center;">
                                        <?php esc_html_e('Actions', 'obydullah-restaurant-pos-lite'); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="category-list">
                                <tr>
                                    <td colspan="3" class="loading-categories" style="text-align:center;">
                                        <span class="spinner is-active"></span>
                                        <?php esc_html_e('Loading categories...', 'obydullah-restaurant-pos-lite'); ?>
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
                    const addNonce = '<?php echo esc_js($add_nonce); ?>';
                    const editNonce = '<?php echo esc_js($edit_nonce); ?>';
                    const deleteNonce = '<?php echo esc_js($delete_nonce); ?>';
                    const getNonce = '<?php echo esc_js($get_nonce); ?>';

                    loadORPLCategories();

                    function loadORPLCategories() {
                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'orpl_get_product_categories',
                                nonce: getNonce
                            },
                            success: function (response) {
                                let tbody = $('#category-list').empty();
                                if (response.success) {
                                    if (!response.data.length) {
                                        tbody.append('<tr><td colspan="3" style="text-align:center;"><?php echo esc_js(__('No categories found.', 'obydullah-restaurant-pos-lite')); ?></td></tr>');
                                        return;
                                    }

                                    $.each(response.data, function (_, cat) {
                                        let row = $('<tr>').attr('data-category-id', cat.id);
                                        row.append($('<td>').text(cat.name));
                                        let statusClass = cat.status === 'active' ? 'status-active' : 'status-inactive';
                                        let statusText = cat.status.charAt(0).toUpperCase() + cat.status.slice(1);
                                        row.append($('<td>').append(
                                            $('<span>').addClass(statusClass).text(statusText)
                                        ));

                                        row.append($('<td style="text-align:center;">')
                                            .append('<button class="button button-small edit-category" style="margin-right:5px;"><?php echo esc_js(__('Edit', 'obydullah-restaurant-pos-lite')); ?></button>')
                                            .append('<button class="button button-small button-link-delete delete-category"><?php echo esc_js(__('Delete', 'obydullah-restaurant-pos-lite')); ?></button>')
                                        );
                                        tbody.append(row);
                                    });
                                } else {
                                    tbody.append('<tr><td colspan="3" style="color:red;text-align:center;">' + response.data + '</td></tr>');
                                }
                            },
                            error: function () {
                                $('#category-list').html('<tr><td colspan="3" style="color:red;text-align:center;"><?php echo esc_js(__('Failed to load categories.', 'obydullah-restaurant-pos-lite')); ?></td></tr>');
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
                        let action = id ? 'orpl_edit_product_category' : 'orpl_add_product_category';
                        let name = $('#category-name').val().trim();
                        let status = $('#category-status').val();
                        let nonce = id ? editNonce : addNonce;

                        if (!name) {
                            alert('<?php echo esc_js(__('Please enter a category name', 'obydullah-restaurant-pos-lite')); ?>');
                            return false;
                        }

                        // Set submitting state
                        isSubmitting = true;
                        setORPLButtonLoading(true);

                        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            action: action,
                            id: id,
                            name: name,
                            status: status,
                            nonce: nonce
                        }, function (res) {
                            if (res.success) {
                                resetForm();
                                loadORPLCategories();
                            } else {
                                alert('<?php echo esc_js(__('Error:', 'obydullah-restaurant-pos-lite')); ?> ' + res.data);
                            }
                        }).fail(() => alert('<?php echo esc_js(__('Request failed. Please try again.', 'obydullah-restaurant-pos-lite')); ?>'))
                            .always(function () {
                                // Reset submitting state
                                isSubmitting = false;
                                setORPLButtonLoading(false);
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
                        $('#form-title').text('<?php echo esc_js(__('Edit Category', 'obydullah-restaurant-pos-lite')); ?>');
                        $('#submit-category').find('.btn-text').text('<?php echo esc_js(__('Update Category', 'obydullah-restaurant-pos-lite')); ?>');
                        $('#cancel-edit').show();
                    });

                    $(document).on('click', '.delete-category', function () {
                        if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this category?', 'obydullah-restaurant-pos-lite')); ?>')) return;

                        let button = $(this);
                        let originalText = button.text();
                        let id = $(this).closest('tr').data('category-id');

                        // Disable button and show loading
                        button.prop('disabled', true).text('<?php echo esc_js(__('Deleting...', 'obydullah-restaurant-pos-lite')); ?>');

                        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            action: 'orpl_delete_product_category',
                            id: id,
                            nonce: deleteNonce
                        }, function (res) {
                            if (res.success) {
                                loadORPLCategories();
                            } else {
                                alert(res.data);
                            }
                        }).fail(() => alert('<?php echo esc_js(__('Delete request failed. Please try again.', 'obydullah-restaurant-pos-lite')); ?>'))
                            .always(function () {
                                // Re-enable button
                                button.prop('disabled', false).text(originalText);
                            });
                    });

                    function setORPLButtonLoading(loading) {
                        let button = $('#submit-category');
                        let spinner = button.find('.spinner');
                        let btnText = button.find('.btn-text');

                        if (loading) {
                            button.prop('disabled', true).addClass('button-loading');
                            spinner.show();
                            btnText.text(button.hasClass('button-loading') ? '<?php echo esc_js(__('Saving...', 'obydullah-restaurant-pos-lite')); ?>' : '<?php echo esc_js(__('Updating...', 'obydullah-restaurant-pos-lite')); ?>');
                        } else {
                            button.prop('disabled', false).removeClass('button-loading');
                            spinner.hide();
                            btnText.text(button.find('.btn-text').text().includes('Update') ? '<?php echo esc_js(__('Update Category', 'obydullah-restaurant-pos-lite')); ?>' : '<?php echo esc_js(__('Save Category', 'obydullah-restaurant-pos-lite')); ?>');
                        }
                    }

                    function resetForm() {
                        $('#category-id').val('');
                        $('#category-name').val('');
                        $('#category-status').val('active');
                        $('#form-title').text('<?php echo esc_js(__('Add New Category', 'obydullah-restaurant-pos-lite')); ?>');
                        $('#submit-category').find('.btn-text').text('<?php echo esc_js(__('Save Category', 'obydullah-restaurant-pos-lite')); ?>');
                        $('#cancel-edit').hide();
                        $('#category-name').focus();

                        // Ensure button is enabled
                        setORPLButtonLoading(false);
                    }
                });
            </script>
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
                $wpdb->prepare("SELECT id, name, status FROM {$table_name} ORDER BY id DESC")
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

        $result = $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$table_name}
        (name, status)
        VALUES ( %s, %s)",
                $name,
                $status
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
            $table_name,
            ['name' => $name, 'status' => $status],
            ['id' => $id],
            ['%s', '%s'],
            ['%d']
        );

        $wpdb->update(
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

        $result = $wpdb->delete($table_name, ['id' => $id], ['%d']);

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
        wp_cache_delete('orpl_categories_all', 'obydullah-restaurant-pos-lite');
        wp_cache_delete('orpl_categories_active', 'obydullah-restaurant-pos-lite');

        global $wpdb;
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_orpl_category_exists_%')
        );
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_orpl_category_exists_%')
        );
    }
}
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
        <div class="wrap">
            <h1 class="wp-heading-inline" style="margin-bottom:20px;">
                <?php esc_html_e('Products', 'obydullah-restaurant-pos-lite'); ?>
            </h1>
            <hr class="wp-header-end">

            <div id="col-container" class="wp-clearfix" style="display:flex;gap:24px;">
                <!-- Left: Add/Edit Product Form -->
                <div id="col-left" style="flex:1; max-width:420px;">
                    <div class="col-wrap"
                        style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">
                        <h2 id="form-title" style="margin:0 0 20px 0;padding:0;font-size:16px;font-weight:600;color:#1d2327;">
                            <?php esc_html_e('Add New Product', 'obydullah-restaurant-pos-lite'); ?>
                        </h2>
                        <form id="add-product-form" method="post" enctype="multipart/form-data">
                            <?php wp_nonce_field('orpl_add_product', 'product_nonce'); ?>
                            <input type="hidden" id="product-id" name="id" value="">

                            <div style="display:flex;flex-direction:column;gap:15px;">
                                <!-- Product Name -->
                                <div class="form-field form-required">
                                    <label for="product-name"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        <?php esc_html_e('Product Name', 'obydullah-restaurant-pos-lite'); ?> <span
                                            style="color:#d63638;">*</span>
                                    </label>
                                    <input name="name" id="product-name" type="text" value="" required
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;">
                                </div>

                                <!-- Category Selection -->
                                <div class="form-field form-required">
                                    <label for="product-category"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        <?php esc_html_e('Category', 'obydullah-restaurant-pos-lite'); ?> <span
                                            style="color:#d63638;">*</span>
                                    </label>
                                    <select name="fk_category_id" id="product-category" required
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;cursor:pointer;">
                                        <option value="">
                                            <?php esc_html_e('Select Category', 'obydullah-restaurant-pos-lite'); ?>
                                        </option>
                                    </select>
                                </div>

                                <!-- Image Upload -->
                                <div class="form-field">
                                    <label for="product-image"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        <?php esc_html_e('Product Image', 'obydullah-restaurant-pos-lite'); ?>
                                    </label>
                                    <input name="image" id="product-image" type="file" accept="image/*"
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;">
                                    <div id="image-preview" style="margin-top:8px;display:none;">
                                        <img id="preview-img" src=""
                                            style="max-width:100px;max-height:100px;border:1px solid #ddd;border-radius:4px;">
                                    </div>
                                </div>

                                <!-- Status -->
                                <div class="form-field">
                                    <label for="product-status"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        <?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?>
                                    </label>
                                    <select name="status" id="product-status"
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;cursor:pointer;">
                                        <option value="active"><?php esc_html_e('Active', 'obydullah-restaurant-pos-lite'); ?>
                                        </option>
                                        <option value="inactive">
                                            <?php esc_html_e('Inactive', 'obydullah-restaurant-pos-lite'); ?>
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <div style="margin-top:20px;display:flex;gap:10px;">
                                <button type="submit" id="submit-product" class="button button-primary"
                                    style="flex:1;padding:8px 12px;font-size:13px;font-weight:500;">
                                    <span
                                        class="btn-text"><?php esc_html_e('Save Product', 'obydullah-restaurant-pos-lite'); ?></span>
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

                <!-- Right: Products Table -->
                <div id="col-right" style="flex:2;">
                    <div class="col-wrap"
                        style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">

                        <!-- Search and Filters -->
                        <div style="margin-bottom:20px;display:flex;gap:12px;align-items:flex-end;">
                            <div style="flex:1;">
                                <label for="product-search"
                                    style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                    <?php esc_html_e('Search Products', 'obydullah-restaurant-pos-lite'); ?>
                                </label>
                                <input type="text" id="product-search"
                                    placeholder="<?php esc_attr_e('Search by product name...', 'obydullah-restaurant-pos-lite'); ?>"
                                    style="width:100%;padding:8px 12px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;">
                            </div>
                            <div>
                                <button type="button" id="clear-search" class="button" style="padding:8px 12px;font-size:13px;">
                                    <?php esc_html_e('Clear', 'obydullah-restaurant-pos-lite'); ?>
                                </button>
                            </div>
                        </div>

                        <!-- Products Table -->
                        <table class="wp-list-table widefat fixed striped table-view-list">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Image', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Name', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Category', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th style="text-align:center;">
                                        <?php esc_html_e('Actions', 'obydullah-restaurant-pos-lite'); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="product-list">
                                <tr>
                                    <td colspan="5" class="loading-products" style="text-align:center;">
                                        <span class="spinner is-active"></span>
                                        <?php esc_html_e('Loading products...', 'obydullah-restaurant-pos-lite'); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="tablenav bottom"
                            style="margin-top:20px;display:flex;justify-content:space-between;align-items:center;">
                            <div class="tablenav-pages">
                                <span class="displaying-num" id="displaying-num">0
                                    <?php esc_html_e('items', 'obydullah-restaurant-pos-lite'); ?></span>
                                <span class="pagination-links">
                                    <a class="first-page button" href="#">
                                        <span
                                            class="screen-reader-text"><?php esc_html_e('First page', 'obydullah-restaurant-pos-lite'); ?></span>
                                        <span aria-hidden="true">«</span>
                                    </a>
                                    <a class="prev-page button" href="#">
                                        <span
                                            class="screen-reader-text"><?php esc_html_e('Previous page', 'obydullah-restaurant-pos-lite'); ?></span>
                                        <span aria-hidden="true">‹</span>
                                    </a>
                                    <span class="paging-input">
                                        <label for="current-page-selector"
                                            class="screen-reader-text"><?php esc_html_e('Current Page', 'obydullah-restaurant-pos-lite'); ?></label>
                                        <input class="current-page" id="current-page-selector" type="text" name="paged"
                                            value="1" size="3" aria-describedby="table-paging">
                                        <span class="tablenav-paging-text">
                                            <?php esc_html_e('of', 'obydullah-restaurant-pos-lite'); ?> <span
                                                class="total-pages">1</span></span>
                                    </span>
                                    <a class="next-page button" href="#">
                                        <span
                                            class="screen-reader-text"><?php esc_html_e('Next page', 'obydullah-restaurant-pos-lite'); ?></span>
                                        <span aria-hidden="true">›</span>
                                    </a>
                                    <a class="last-page button" href="#">
                                        <span
                                            class="screen-reader-text"><?php esc_html_e('Last page', 'obydullah-restaurant-pos-lite'); ?></span>
                                        <span aria-hidden="true">»</span>
                                    </a>
                                </span>
                            </div>
                            <div class="tablenav-pages" style="float:none;">
                                <select id="per-page-select" style="padding:4px 16px;border:1px solid #ddd;border-radius:4px;">
                                    <option value="10">10 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                    <option value="20">20 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                    <option value="50">50 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                    <option value="100">100 <?php esc_html_e('per page', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                jQuery(document).ready(function ($) {
                    let isSubmitting = false;
                    let currentPage = 1;
                    let perPage = 10;
                    let totalPages = 1;
                    let totalItems = 0;
                    let searchTerm = '';
                    let searchTimeout = null;

                    // Load categories and products on page load
                    loadORPLCategories();
                    loadORPLProducts();

                    function loadORPLCategories() {
                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'orpl_get_categories_for_products',
                                nonce: '<?php echo esc_attr(wp_create_nonce("orpl_get_categories_for_products")); ?>'
                            },
                            success: function (response) {
                                if (response.success) {
                                    let select = $('#product-category');
                                    select.empty().append('<option value=""><?php echo esc_js(__('Select Category', 'obydullah-restaurant-pos-lite')); ?></option>');

                                    $.each(response.data, function (_, category) {
                                        select.append(
                                            $('<option>').val(category.id).text(category.name)
                                        );
                                    });
                                }
                            }
                        });
                    }

                    function loadORPLProducts(page = 1) {
                        currentPage = page;

                        let tbody = $('#product-list');
                        tbody.html('<tr><td colspan="5" class="loading-products" style="text-align:center;"><span class="spinner is-active"></span> <?php echo esc_js(__('Loading products...', 'obydullah-restaurant-pos-lite')); ?></td></tr>');

                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'orpl_get_products',
                                page: currentPage,
                                per_page: perPage,
                                search: searchTerm,
                                nonce: '<?php echo esc_attr(wp_create_nonce("orpl_get_products")); ?>'
                            },
                            success: function (response) {
                                tbody.empty();
                                if (response.success) {
                                    if (!response.data.products.length) {
                                        let message = searchTerm ?
                                            '<?php echo esc_js(__('No products found matching', 'obydullah-restaurant-pos-lite')); ?> "' + searchTerm + '".' :
                                            '<?php echo esc_js(__('No products found.', 'obydullah-restaurant-pos-lite')); ?>';
                                        tbody.append('<tr><td colspan="5" style="text-align:center;padding:20px;color:#666;">' + message + '</td></tr>');
                                        updateORPLPagination(response.data.pagination);
                                        return;
                                    }

                                    $.each(response.data.products, function (_, product) {
                                        let row = $('<tr>').attr('data-product-id', product.id);

                                        // Image column
                                        let imageTd = $('<td>');
                                        if (product.image) {
                                            imageTd.append(
                                                $('<img>').addClass('product-image').attr('src', product.image)
                                            );
                                        } else {
                                            imageTd.append(
                                                $('<div>').addClass('no-image').text('<?php echo esc_js(__('No Image', 'obydullah-restaurant-pos-lite')); ?>')
                                            );
                                        }
                                        row.append(imageTd);

                                        // Name column
                                        row.append($('<td>').text(product.name));

                                        // Category column
                                        row.append($('<td>').text(product.category_name || 'N/A'));

                                        // Status column
                                        let statusClass = product.status === 'active' ? 'status-active' : 'status-inactive';
                                        let statusText = product.status.charAt(0).toUpperCase() + product.status.slice(1);
                                        row.append($('<td>').append(
                                            $('<span>').addClass(statusClass).text(statusText)
                                        ));

                                        // Actions column
                                        row.append($('<td style="text-align:center;">')
                                            .append('<button class="button button-small edit-product" style="margin-right:5px;"><?php echo esc_js(__('Edit', 'obydullah-restaurant-pos-lite')); ?></button>')
                                            .append('<button class="button button-small button-link-delete delete-product"><?php echo esc_js(__('Delete', 'obydullah-restaurant-pos-lite')); ?></button>')
                                        );

                                        tbody.append(row);
                                    });

                                    updateORPLPagination(response.data.pagination);
                                } else {
                                    tbody.append('<tr><td colspan="5" style="color:red;text-align:center;">' + response.data + '</td></tr>');
                                }
                            },
                            error: function () {
                                $('#product-list').html('<tr><td colspan="5" style="color:red;text-align:center;"><?php echo esc_js(__('Failed to load products.', 'obydullah-restaurant-pos-lite')); ?></td></tr>');
                            }
                        });
                    }

                    function updateORPLPagination(pagination) {
                        totalPages = pagination.total_pages;
                        totalItems = pagination.total_items;

                        // Update displaying text
                        $('#displaying-num').text(pagination.total_items + ' <?php echo esc_js(__('items', 'obydullah-restaurant-pos-lite')); ?>');

                        // Update page input and total pages
                        $('#current-page-selector').val(currentPage);
                        $('.total-pages').text(totalPages);

                        // Update pagination buttons state
                        $('.first-page, .prev-page').prop('disabled', currentPage === 1);
                        $('.next-page, .last-page').prop('disabled', currentPage === totalPages);
                    }

                    // Search functionality
                    $('#product-search').on('input', function () {
                        clearTimeout(searchTimeout);
                        searchTerm = $(this).val().trim();

                        searchTimeout = setTimeout(function () {
                            loadORPLProducts(1); // Reset to first page when searching
                        }, 500); // 500ms delay
                    });

                    // Clear search
                    $('#clear-search').on('click', function () {
                        $('#product-search').val('');
                        searchTerm = '';
                        loadORPLProducts(1);
                    });

                    // Per page change
                    $('#per-page-select').on('change', function () {
                        perPage = parseInt($(this).val());
                        loadORPLProducts(1);
                    });

                    // Pagination handlers
                    $('.first-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage > 1) loadORPLProducts(1);
                    });

                    $('.prev-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage > 1) loadORPLProducts(currentPage - 1);
                    });

                    $('.next-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage < totalPages) loadORPLProducts(currentPage + 1);
                    });

                    $('.last-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage < totalPages) loadORPLProducts(totalPages);
                    });

                    $('#current-page-selector').on('keypress', function (e) {
                        if (e.which === 13) { // Enter key
                            let page = parseInt($(this).val());
                            if (page >= 1 && page <= totalPages) {
                                loadORPLProducts(page);
                            }
                        }
                    });

                    // Image preview
                    $('#product-image').on('change', function (e) {
                        var file = e.target.files[0];
                        if (file) {
                            var reader = new FileReader();
                            reader.onload = function (e) {
                                $('#preview-img').attr('src', e.target.result);
                                $('#image-preview').show();
                            }
                            reader.readAsDataURL(file);
                        }
                    });

                    $('#add-product-form').on('submit', function (e) {
                        e.preventDefault();

                        // Prevent double submission
                        if (isSubmitting) {
                            return false;
                        }

                        let id = $('#product-id').val();
                        let action = id ? 'orpl_edit_product' : 'orpl_add_product';
                        let name = $('#product-name').val().trim();
                        let fk_category_id = $('#product-category').val();
                        let status = $('#product-status').val();

                        if (!name) {
                            alert('<?php echo esc_js(__('Please enter a product name', 'obydullah-restaurant-pos-lite')); ?>');
                            return false;
                        }
                        if (!fk_category_id) {
                            alert('<?php echo esc_js(__('Please select a category', 'obydullah-restaurant-pos-lite')); ?>');
                            return false;
                        }

                        // Set submitting state
                        isSubmitting = true;
                        setButtonLoading(true);

                        // Create FormData for file upload
                        let formData = new FormData(this);
                        formData.append('action', action);
                        formData.append('id', id);

                        // FIX: Use correct nonce based on whether we're adding or editing
                        if (id) {
                            // Editing product - use edit product nonce
                            formData.append('nonce', '<?php echo esc_attr(wp_create_nonce("orpl_edit_product")); ?>');
                        } else {
                            // Adding product - use add product nonce
                            formData.append('nonce', '<?php echo esc_attr(wp_create_nonce("orpl_add_product")); ?>');
                        }

                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (res) {
                                if (res.success) {
                                    resetForm();
                                    loadORPLProducts(currentPage);
                                } else {
                                    alert('<?php echo esc_js(__('Error:', 'obydullah-restaurant-pos-lite')); ?> ' + res.data);
                                }
                            },
                            error: function () {
                                alert('<?php echo esc_js(__('Request failed. Please try again.', 'obydullah-restaurant-pos-lite')); ?>');
                            },
                            complete: function () {
                                // Reset submitting state
                                isSubmitting = false;
                                setButtonLoading(false);
                            }
                        });
                    });
                    
                    $('#cancel-edit').on('click', function () {
                        resetForm();
                    });

                    $(document).on('click', '.edit-product', function () {
                        let row = $(this).closest('tr');
                        let productId = row.data('product-id');

                        // Get product details
                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'orpl_get_products',
                                id: productId,
                                nonce: '<?php echo esc_attr(wp_create_nonce("orpl_get_products")); ?>'
                            },
                            success: function (response) {
                                if (response.success && response.data.products.length > 0) {
                                    let product = response.data.products.find(p => p.id == productId);
                                    if (product) {
                                        $('#product-id').val(product.id);
                                        $('#product-name').val(product.name);
                                        $('#product-category').val(product.fk_category_id);
                                        $('#product-status').val(product.status);

                                        if (product.image) {
                                            $('#preview-img').attr('src', product.image);
                                            $('#image-preview').show();
                                        } else {
                                            $('#image-preview').hide();
                                        }

                                        $('#form-title').text('<?php echo esc_js(__('Edit Product', 'obydullah-restaurant-pos-lite')); ?>');
                                        $('#submit-product').find('.btn-text').text('<?php echo esc_js(__('Update Product', 'obydullah-restaurant-pos-lite')); ?>');
                                        $('#cancel-edit').show();
                                    }
                                }
                            }
                        });
                    });

                    $(document).on('click', '.delete-product', function () {
                        if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this product?', 'obydullah-restaurant-pos-lite')); ?>')) return;

                        let button = $(this);
                        let originalText = button.text();
                        let id = $(this).closest('tr').data('product-id');

                        // Disable button and show loading
                        button.prop('disabled', true).text('<?php echo esc_js(__('Deleting...', 'obydullah-restaurant-pos-lite')); ?>');

                        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            action: 'orpl_delete_product',
                            id: id,
                            nonce: '<?php echo esc_attr(wp_create_nonce("orpl_delete_product")); ?>'
                        }, function (res) {
                            if (res.success) {
                                loadORPLProducts(currentPage);
                            } else {
                                alert(res.data);
                            }
                        }).fail(() => alert('<?php echo esc_js(__('Delete request failed. Please try again.', 'obydullah-restaurant-pos-lite')); ?>'))
                            .always(function () {
                                // Re-enable button
                                button.prop('disabled', false).text(originalText);
                            });
                    });

                    function setButtonLoading(loading) {
                        let button = $('#submit-product');
                        let spinner = button.find('.spinner');
                        let btnText = button.find('.btn-text');

                        if (loading) {
                            button.prop('disabled', true).addClass('button-loading');
                            spinner.show();
                            btnText.text(button.hasClass('button-loading') ? '<?php echo esc_js(__('Saving...', 'obydullah-restaurant-pos-lite')); ?>' : '<?php echo esc_js(__('Updating...', 'obydullah-restaurant-pos-lite')); ?>');
                        } else {
                            button.prop('disabled', false).removeClass('button-loading');
                            spinner.hide();
                            btnText.text(button.find('.btn-text').text().includes('Update') ? '<?php echo esc_js(__('Update Product', 'obydullah-restaurant-pos-lite')); ?>' : '<?php echo esc_js(__('Save Product', 'obydullah-restaurant-pos-lite')); ?>');
                        }
                    }

                    function resetForm() {
                        $('#product-id').val('');
                        $('#product-name').val('');
                        $('#product-category').val('');
                        $('#product-status').val('active');
                        $('#product-image').val('');
                        $('#image-preview').hide();
                        $('#form-title').text('<?php echo esc_js(__('Add New Product', 'obydullah-restaurant-pos-lite')); ?>');
                        $('#submit-product').find('.btn-text').text('<?php echo esc_js(__('Save Product', 'obydullah-restaurant-pos-lite')); ?>');
                        $('#cancel-edit').hide();
                        $('#product-name').focus();

                        // Ensure button is enabled
                        setButtonLoading(false);
                    }
                });
            </script>
        </div>
        <?php
    }

    /** Get categories for product form */
    public function ajax_get_orpl_categories_for_products()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_get_categories_for_products')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
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
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;
        $products_table = $wpdb->prefix . 'orpl_products';
        $categories_table = $wpdb->prefix . 'orpl_categories';

        // Get parameters
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
        $search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';

        // Check if specific product is requested
        $product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if ($product_id > 0) {
            // Return single product for editing
            $products = $wpdb->get_results($wpdb->prepare(
                "SELECT p.*, c.name as category_name 
                FROM {$products_table} p 
                LEFT JOIN {$categories_table} c ON p.fk_category_id = c.id 
                WHERE p.id = %d",
                $product_id
            ));

            wp_send_json_success(['products' => $products]);
        } else {
            // Generate cache key based on search and pagination
            $cache_key = 'orpl_products_' . md5($search . '_' . $page . '_' . $per_page);
            $cached_data = wp_cache_get($cache_key, 'obydullah-restaurant-pos-lite');

            if (false !== $cached_data) {
                wp_send_json_success($cached_data);
            }

            // Build query based on search
            if (!empty($search)) {
                // With search
                $total_items = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$products_table} p WHERE p.name LIKE %s",
                    '%' . $wpdb->esc_like($search) . '%'
                ));

                // Calculate pagination
                $total_pages = ceil($total_items / $per_page);
                $offset = ($page - 1) * $per_page;

                $products = $wpdb->get_results($wpdb->prepare(
                    "SELECT p.*, c.name as category_name 
                    FROM {$products_table} p 
                    LEFT JOIN {$categories_table} c ON p.fk_category_id = c.id 
                    WHERE p.name LIKE %s 
                    ORDER BY p.created_at DESC 
                    LIMIT %d OFFSET %d",
                    '%' . $wpdb->esc_like($search) . '%',
                    $per_page,
                    $offset
                ));
            } else {
                // Without search
                $total_items = $wpdb->get_var(
                    $wpdb->prepare("SELECT COUNT(*) FROM {$products_table} p")
                );

                // Calculate pagination
                $total_pages = ceil($total_items / $per_page);
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
                'products' => $products,
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
    }

    /** Add product */
    public function ajax_add_orpl_product()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_add_product')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'orpl_products';

        // Unslash and sanitize input
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $fk_category_id = intval($_POST['fk_category_id'] ?? 0);
        $status = in_array(wp_unslash($_POST['status'] ?? ''), ['active', 'inactive']) ?
            sanitize_text_field(wp_unslash($_POST['status'])) : 'active';

        // Validate required fields
        if (empty($name)) {
            wp_send_json_error(__('Product name is required', 'obydullah-restaurant-pos-lite'));
        }
        if ($fk_category_id <= 0) {
            wp_send_json_error(__('Please select a valid category', 'obydullah-restaurant-pos-lite'));
        }

        // Check if product name already exists with caching
        $cache_key = 'orpl_product_exists_' . md5($name);
        $existing = wp_cache_get($cache_key, 'obydullah-restaurant-pos-lite');

        if (false === $existing) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table_name} WHERE name = %s",
                $name
            ));
            wp_cache_set($cache_key, $existing, 'obydullah-restaurant-pos-lite', 300);
        }

        if ($existing) {
            wp_send_json_error(__('Product name already exists', 'obydullah-restaurant-pos-lite'));
        }

        // Handle image upload
        $image_url = '';
        if (!empty($_FILES['image']['name'])) {
            $upload = wp_handle_upload($_FILES['image'], array('test_form' => false));
            if (isset($upload['url'])) {
                $image_url = $upload['url'];
            }
        }

        $result = $wpdb->insert($table_name, [
            'name' => $name,
            'fk_category_id' => $fk_category_id,
            'image' => $image_url,
            'status' => $status
        ], ['%s', '%d', '%s', '%s']);

        if ($result === false) {
            wp_send_json_error(__('Failed to add product', 'obydullah-restaurant-pos-lite'));
        }

        // Clear product caches
        $this->clear_product_caches();

        wp_send_json_success(__('Product added successfully', 'obydullah-restaurant-pos-lite'));
    }

    /** Edit product */
    public function ajax_edit_orpl_product()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_edit_product')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'orpl_products';

        $id = intval($_POST['id'] ?? 0);
        // Unslash and sanitize input
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $fk_category_id = intval($_POST['fk_category_id'] ?? 0);
        $status = in_array(wp_unslash($_POST['status'] ?? ''), ['active', 'inactive']) ?
            sanitize_text_field(wp_unslash($_POST['status'])) : 'active';

        if (!$id || empty($name) || $fk_category_id <= 0) {
            wp_send_json_error(__('Invalid data provided', 'obydullah-restaurant-pos-lite'));
        }

        // Check if product name already exists (excluding current product) with caching
        $cache_key = 'orpl_product_exists_' . md5($name . '_' . $id);
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
            wp_send_json_error(__('Product name already exists', 'obydullah-restaurant-pos-lite'));
        }

        // Prepare update data
        $update_data = [
            'name' => $name,
            'fk_category_id' => $fk_category_id,
            'status' => $status
        ];
        $format = ['%s', '%d', '%s'];

        // Handle image upload if new image provided
        if (!empty($_FILES['image']['name'])) {
            $upload = wp_handle_upload($_FILES['image'], array('test_form' => false));
            if (isset($upload['url'])) {
                $update_data['image'] = $upload['url'];
                $format[] = '%s';
            }
        }

        $result = $wpdb->update($table_name, $update_data, ['id' => $id], $format, ['%d']);

        if ($result === false) {
            wp_send_json_error(__('Failed to update product', 'obydullah-restaurant-pos-lite'));
        }

        // Clear product caches
        $this->clear_product_caches();

        wp_send_json_success(__('Product updated successfully', 'obydullah-restaurant-pos-lite'));
    }

    /** Delete product */
    public function ajax_delete_orpl_product()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_delete_product')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'orpl_products';
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error(__('Invalid product ID', 'obydullah-restaurant-pos-lite'));
        }

        $result = $wpdb->delete($table, ['id' => $id], ['%d']);

        if ($result === false) {
            wp_send_json_error(__('Failed to delete product', 'obydullah-restaurant-pos-lite'));
        }

        // Clear product caches
        $this->clear_product_caches();

        wp_send_json_success(__('Product deleted successfully', 'obydullah-restaurant-pos-lite'));
    }

    /**
     * Clear all product-related caches
     */
    private function clear_product_caches()
    {
        global $wpdb;
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_orpl_products_%')
        );
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_orpl_products_%')
        );

        // Clear product existence caches
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_orpl_product_exists_%')
        );
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_orpl_product_exists_%')
        );
    }
}
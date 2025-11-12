<?php
/**
 * Products Management for Restaurant POS
 * 
 * Note: Direct database queries and table name interpolation are necessary
 * for custom plugin functionality and follow WordPress development practices.
 * Table names are safely constructed using $wpdb->prefix.
 */
if (!defined('ABSPATH'))
    exit;

class Restaurant_POS_Lite_Products
{
    public function __construct()
    {
        add_action('wp_ajax_add_product', [$this, 'ajax_add_product']);
        add_action('wp_ajax_get_products', [$this, 'ajax_get_products']);
        add_action('wp_ajax_edit_product', [$this, 'ajax_edit_product']);
        add_action('wp_ajax_delete_product', [$this, 'ajax_delete_product']);
        add_action('wp_ajax_get_categories_for_products', [$this, 'ajax_get_categories_for_products']);
    }

    /**
     * Render the products page
     */
    public function render_page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline" style="margin-bottom:20px;">Products</h1>
            <hr class="wp-header-end">

            <div id="col-container" class="wp-clearfix" style="display:flex;gap:24px;">
                <!-- Left: Add/Edit Product Form -->
                <div id="col-left" style="flex:1; max-width:420px;">
                    <div class="col-wrap"
                        style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">
                        <h2 id="form-title" style="margin:0 0 20px 0;padding:0;font-size:16px;font-weight:600;color:#1d2327;">
                            Add New Product
                        </h2>
                        <form id="add-product-form" method="post" enctype="multipart/form-data">
                            <?php wp_nonce_field('add_product', 'product_nonce'); ?>
                            <input type="hidden" id="product-id" name="id" value="">

                            <div style="display:flex;flex-direction:column;gap:15px;">
                                <!-- Product Name -->
                                <div class="form-field form-required">
                                    <label for="product-name"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        Product Name <span style="color:#d63638;">*</span>
                                    </label>
                                    <input name="name" id="product-name" type="text" value="" required
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;">
                                </div>

                                <!-- Category Selection -->
                                <div class="form-field form-required">
                                    <label for="product-category"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        Category <span style="color:#d63638;">*</span>
                                    </label>
                                    <select name="fk_category_id" id="product-category" required
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;cursor:pointer;">
                                        <option value="">Select Category</option>
                                    </select>
                                </div>

                                <!-- Image Upload -->
                                <div class="form-field">
                                    <label for="product-image"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        Product Image
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
                                        Status
                                    </label>
                                    <select name="status" id="product-status"
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;cursor:pointer;">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div style="margin-top:20px;display:flex;gap:10px;">
                                <button type="submit" id="submit-product" class="button button-primary"
                                    style="flex:1;padding:8px 12px;font-size:13px;font-weight:500;">
                                    <span class="btn-text">Save Product</span>
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

                <!-- Right: Products Table -->
                <div id="col-right" style="flex:2;">
                    <div class="col-wrap"
                        style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">

                        <!-- Search and Filters -->
                        <div style="margin-bottom:20px;display:flex;gap:12px;align-items:flex-end;">
                            <div style="flex:1;">
                                <label for="product-search"
                                    style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                    Search Products
                                </label>
                                <input type="text" id="product-search" placeholder="Search by product name..."
                                    style="width:100%;padding:8px 12px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;">
                            </div>
                            <div>
                                <button type="button" id="clear-search" class="button" style="padding:8px 12px;font-size:13px;">
                                    Clear
                                </button>
                            </div>
                        </div>

                        <!-- Products Table -->
                        <table class="wp-list-table widefat fixed striped table-view-list">
                            <thead>
                                <tr>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th style="text-align:center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="product-list">
                                <tr>
                                    <td colspan="5" class="loading-products" style="text-align:center;">
                                        <span class="spinner is-active"></span>
                                        Loading products...
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="tablenav bottom"
                            style="margin-top:20px;display:flex;justify-content:space-between;align-items:center;">
                            <div class="tablenav-pages">
                                <span class="displaying-num" id="displaying-num">0 items</span>
                                <span class="pagination-links">
                                    <a class="first-page button" href="#">
                                        <span class="screen-reader-text">First page</span>
                                        <span aria-hidden="true">«</span>
                                    </a>
                                    <a class="prev-page button" href="#">
                                        <span class="screen-reader-text">Previous page</span>
                                        <span aria-hidden="true">‹</span>
                                    </a>
                                    <span class="paging-input">
                                        <label for="current-page-selector" class="screen-reader-text">Current Page</label>
                                        <input class="current-page" id="current-page-selector" type="text" name="paged"
                                            value="1" size="3" aria-describedby="table-paging">
                                        <span class="tablenav-paging-text"> of <span class="total-pages">1</span></span>
                                    </span>
                                    <a class="next-page button" href="#">
                                        <span class="screen-reader-text">Next page</span>
                                        <span aria-hidden="true">›</span>
                                    </a>
                                    <a class="last-page button" href="#">
                                        <span class="screen-reader-text">Last page</span>
                                        <span aria-hidden="true">»</span>
                                    </a>
                                </span>
                            </div>
                            <div class="tablenav-pages" style="float:none;">
                                <select id="per-page-select" style="padding:4px 8px;border:1px solid #ddd;border-radius:4px;">
                                    <option value="10">10 per page</option>
                                    <option value="20">20 per page</option>
                                    <option value="50">50 per page</option>
                                    <option value="100">100 per page</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <style>
                /* Status styling */
                .status-active {
                    color: #0a7c38;
                    font-weight: 600;
                    padding: 4px 8px;
                    border-radius: 3px;
                    background: #edfaef;
                    display: inline-block;
                }

                .status-inactive {
                    color: #d63638;
                    font-weight: 600;
                    padding: 4px 8px;
                    border-radius: 3px;
                    background: #fef0f1;
                    display: inline-block;
                }

                /* Form field focus states */
                #product-name:focus,
                #product-category:focus,
                #product-status:focus,
                #product-image:focus,
                #product-search:focus {
                    border-color: #2271b1 !important;
                    box-shadow: 0 0 0 1px #2271b1 !important;
                    outline: none !important;
                }

                /* Product image in table */
                .product-image {
                    width: 40px;
                    height: 40px;
                    object-fit: cover;
                    border-radius: 4px;
                    border: 1px solid #ddd;
                }

                .no-image {
                    width: 40px;
                    height: 40px;
                    background: #f6f7f7;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    color: #8c8f94;
                    font-size: 10px;
                    text-align: center;
                }

                /* Button loading state */
                .button-loading {
                    position: relative;
                    color: transparent !important;
                }

                .button-loading .spinner {
                    display: inline-block !important;
                    visibility: visible !important;
                }

                /* Pagination styles */
                .tablenav-pages .button:disabled {
                    opacity: 0.5;
                    cursor: default;
                }

                .current-page {
                    width: 40px;
                    text-align: center;
                    padding: 4px;
                }

                #per-page-select:focus {
                    border-color: #2271b1;
                    box-shadow: 0 0 0 1px #2271b1;
                    outline: none;
                }
            </style>

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
                    loadCategories();
                    loadProducts();

                    function loadCategories() {
                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'get_categories_for_products',
                                nonce: '<?php echo esc_attr(wp_create_nonce("get_categories_for_products")); ?>'
                            },
                            success: function (response) {
                                if (response.success) {
                                    let select = $('#product-category');
                                    select.empty().append('<option value="">Select Category</option>');

                                    $.each(response.data, function (_, category) {
                                        select.append(
                                            $('<option>').val(category.id).text(category.name)
                                        );
                                    });
                                }
                            }
                        });
                    }

                    function loadProducts(page = 1) {
                        currentPage = page;

                        let tbody = $('#product-list');
                        tbody.html('<tr><td colspan="5" class="loading-products" style="text-align:center;"><span class="spinner is-active"></span> Loading products...</td></tr>');

                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'get_products',
                                page: currentPage,
                                per_page: perPage,
                                search: searchTerm,
                                nonce: '<?php echo esc_attr(wp_create_nonce("get_products")); ?>'
                            },
                            success: function (response) {
                                tbody.empty();
                                if (response.success) {
                                    if (!response.data.products.length) {
                                        let message = searchTerm ?
                                            'No products found matching "' + searchTerm + '".' :
                                            'No products found.';
                                        tbody.append('<tr><td colspan="5" style="text-align:center;padding:20px;color:#666;">' + message + '</td></tr>');
                                        updatePagination(response.data.pagination);
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
                                                $('<div>').addClass('no-image').text('No Image')
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
                                            .append('<button class="button button-small edit-product" style="margin-right:5px;">Edit</button>')
                                            .append('<button class="button button-small button-link-delete delete-product">Delete</button>')
                                        );

                                        tbody.append(row);
                                    });

                                    updatePagination(response.data.pagination);
                                } else {
                                    tbody.append('<tr><td colspan="5" style="color:red;text-align:center;">' + response.data + '</td></tr>');
                                }
                            },
                            error: function () {
                                $('#product-list').html('<tr><td colspan="5" style="color:red;text-align:center;">Failed to load products.</td></tr>');
                            }
                        });
                    }

                    function updatePagination(pagination) {
                        totalPages = pagination.total_pages;
                        totalItems = pagination.total_items;

                        // Update displaying text
                        $('#displaying-num').text(pagination.total_items + ' items');

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
                            loadProducts(1); // Reset to first page when searching
                        }, 500); // 500ms delay
                    });

                    // Clear search
                    $('#clear-search').on('click', function () {
                        $('#product-search').val('');
                        searchTerm = '';
                        loadProducts(1);
                    });

                    // Per page change
                    $('#per-page-select').on('change', function () {
                        perPage = parseInt($(this).val());
                        loadProducts(1);
                    });

                    // Pagination handlers
                    $('.first-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage > 1) loadProducts(1);
                    });

                    $('.prev-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage > 1) loadProducts(currentPage - 1);
                    });

                    $('.next-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage < totalPages) loadProducts(currentPage + 1);
                    });

                    $('.last-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage < totalPages) loadProducts(totalPages);
                    });

                    $('#current-page-selector').on('keypress', function (e) {
                        if (e.which === 13) { // Enter key
                            let page = parseInt($(this).val());
                            if (page >= 1 && page <= totalPages) {
                                loadProducts(page);
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
                        let action = id ? 'edit_product' : 'add_product';
                        let name = $('#product-name').val().trim();
                        let fk_category_id = $('#product-category').val();
                        let status = $('#product-status').val();

                        if (!name) {
                            alert('Please enter a product name');
                            return false;
                        }
                        if (!fk_category_id) {
                            alert('Please select a category');
                            return false;
                        }

                        // Set submitting state
                        isSubmitting = true;
                        setButtonLoading(true);

                        // Create FormData for file upload
                        let formData = new FormData(this);
                        formData.append('action', action);
                        formData.append('id', id);
                        formData.append('nonce', '<?php echo esc_attr(wp_create_nonce("add_product")); ?>');

                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            success: function (res) {
                                if (res.success) {
                                    resetForm();
                                    loadProducts(currentPage); // Reload current page
                                } else {
                                    alert('Error: ' + res.data);
                                }
                            },
                            error: function () {
                                alert('Request failed. Please try again.');
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
                                action: 'get_products',
                                id: productId,
                                nonce: '<?php echo esc_attr(wp_create_nonce("get_products")); ?>'
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

                                        $('#form-title').text('Edit Product');
                                        $('#submit-product').find('.btn-text').text('Update Product');
                                        $('#cancel-edit').show();
                                    }
                                }
                            }
                        });
                    });

                    $(document).on('click', '.delete-product', function () {
                        if (!confirm('Are you sure you want to delete this product?')) return;

                        let button = $(this);
                        let originalText = button.text();
                        let id = $(this).closest('tr').data('product-id');

                        // Disable button and show loading
                        button.prop('disabled', true).text('Deleting...');

                        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            action: 'delete_product',
                            id: id,
                            nonce: '<?php echo esc_attr(wp_create_nonce("delete_product")); ?>'
                        }, function (res) {
                            if (res.success) {
                                loadProducts(currentPage); // Reload current page
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
                        let button = $('#submit-product');
                        let spinner = button.find('.spinner');
                        let btnText = button.find('.btn-text');

                        if (loading) {
                            button.prop('disabled', true).addClass('button-loading');
                            spinner.show();
                            btnText.text(button.hasClass('button-loading') ? 'Saving...' : 'Updating...');
                        } else {
                            button.prop('disabled', false).removeClass('button-loading');
                            spinner.hide();
                            btnText.text(button.find('.btn-text').text().includes('Update') ? 'Update Product' : 'Save Product');
                        }
                    }

                    function resetForm() {
                        $('#product-id').val('');
                        $('#product-name').val('');
                        $('#product-category').val('');
                        $('#product-status').val('active');
                        $('#product-image').val('');
                        $('#image-preview').hide();
                        $('#form-title').text('Add New Product');
                        $('#submit-product').find('.btn-text').text('Save Product');
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
    public function ajax_get_categories_for_products()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'get_categories_for_products')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'pos_categories';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $categories = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT id, name FROM $table_name WHERE status = 'active' ORDER BY name ASC"
        );

        wp_send_json_success($categories);
    }

/** Get all products with pagination and search */
public function ajax_get_products() {
    // Verify nonce - sanitize the input first
    $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
    if (!wp_verify_nonce($nonce, 'get_products')) {
        wp_send_json_error('Security verification failed');
    }

    global $wpdb;
    $products_table = $wpdb->prefix . 'pos_products';
    $categories_table = $wpdb->prefix . 'pos_categories';

    // Get parameters
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
    $search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
    
    // Check if specific product is requested
    $product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($product_id > 0) {
        // Return single product for editing
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $products = $wpdb->get_results($wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT p.*, c.name as category_name 
            FROM $products_table p 
            LEFT JOIN $categories_table c ON p.fk_category_id = c.id 
            WHERE p.id = %d",
            $product_id
        ));
        
        wp_send_json_success(['products' => $products]);
    } else {
        // Build query based on search
        if (!empty($search)) {
            // With search
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $total_items = $wpdb->get_var($wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT COUNT(*) FROM $products_table p WHERE p.name LIKE %s",
                '%' . $wpdb->esc_like($search) . '%'
            ));

            // Calculate pagination
            $total_pages = ceil($total_items / $per_page);
            $offset = ($page - 1) * $per_page;

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $products = $wpdb->get_results($wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT p.*, c.name as category_name 
                FROM $products_table p 
                LEFT JOIN $categories_table c ON p.fk_category_id = c.id 
                WHERE p.name LIKE %s 
                ORDER BY p.created_at DESC 
                LIMIT %d OFFSET %d",
                '%' . $wpdb->esc_like($search) . '%',
                $per_page,
                $offset
            ));
        } else {
            // Without search
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $total_items = $wpdb->get_var(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT COUNT(*) FROM $products_table p"
            );

            // Calculate pagination
            $total_pages = ceil($total_items / $per_page);
            $offset = ($page - 1) * $per_page;

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $products = $wpdb->get_results($wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT p.*, c.name as category_name 
                FROM $products_table p 
                LEFT JOIN $categories_table c ON p.fk_category_id = c.id 
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

        wp_send_json_success($response_data);
    }
}

    /** Add product */
    public function ajax_add_product()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'add_product')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'pos_products';

        // Unslash and sanitize input
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $fk_category_id = intval($_POST['fk_category_id'] ?? 0);
        $status = in_array(wp_unslash($_POST['status'] ?? ''), ['active', 'inactive']) ?
            sanitize_text_field(wp_unslash($_POST['status'])) : 'active';

        // Validate required fields
        if (empty($name)) {
            wp_send_json_error('Product name is required');
        }
        if ($fk_category_id <= 0) {
            wp_send_json_error('Please select a valid category');
        }

        // Check if product name already exists
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $existing = $wpdb->get_var($wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT id FROM $table WHERE name = %s",
            $name
        ));

        if ($existing) {
            wp_send_json_error('Product name already exists');
        }

        // Handle image upload
        $image_url = '';
        if (!empty($_FILES['image']['name'])) {
            $upload = wp_handle_upload($_FILES['image'], array('test_form' => false));
            if (isset($upload['url'])) {
                $image_url = $upload['url'];
            }
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $result = $wpdb->insert($table, [
            'name' => $name,
            'fk_category_id' => $fk_category_id,
            'image' => $image_url,
            'status' => $status
        ], ['%s', '%d', '%s', '%s']);

        if ($result === false) {
            wp_send_json_error('Failed to add product');
        }

        wp_send_json_success('Product added successfully');
    }

    /** Edit product */
    public function ajax_edit_product()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'add_product')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'pos_products';

        $id = intval($_POST['id'] ?? 0);
        // Unslash and sanitize input
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $fk_category_id = intval($_POST['fk_category_id'] ?? 0);
        $status = in_array(wp_unslash($_POST['status'] ?? ''), ['active', 'inactive']) ?
            sanitize_text_field(wp_unslash($_POST['status'])) : 'active';

        if (!$id || empty($name) || $fk_category_id <= 0) {
            wp_send_json_error('Invalid data provided');
        }

        // Check if product name already exists (excluding current product)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $existing = $wpdb->get_var($wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT id FROM $table WHERE name = %s AND id != %d",
            $name,
            $id
        ));

        if ($existing) {
            wp_send_json_error('Product name already exists');
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

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $result = $wpdb->update($table, $update_data, ['id' => $id], $format, ['%d']);


        if ($result === false) {
            wp_send_json_error('Failed to update product');
        }

        wp_send_json_success('Product updated successfully');
    }



    /** Delete product */
    public function ajax_delete_product()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'delete_product')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'pos_products';
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error('Invalid product ID');
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $result = $wpdb->delete($table, ['id' => $id], ['%d']);

        if ($result === false) {
            wp_send_json_error('Failed to delete product');
        }

        wp_send_json_success('Product deleted successfully');
    }
}
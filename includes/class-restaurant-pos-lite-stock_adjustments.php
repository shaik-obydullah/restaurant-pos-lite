<?php
/**
 * Stock Adjustments
 *
 * @package Restaurant_POS_Lite
 * @since   1.0.0
 */

if (!defined('ABSPATH'))
    exit;

class Restaurant_POS_Lite_Stock_Adjustments
{
    public function __construct()
    {
        add_action('wp_ajax_add_stock_adjustment', [$this, 'ajax_add_stock_adjustment']);
        add_action('wp_ajax_get_stock_adjustments', [$this, 'ajax_get_stock_adjustments']);
        add_action('wp_ajax_delete_stock_adjustment', [$this, 'ajax_delete_stock_adjustment']);
        add_action('wp_ajax_get_products_for_adjustments', [$this, 'ajax_get_products_for_adjustments']);
        add_action('wp_ajax_get_current_stock', [$this, 'ajax_get_current_stock']);
    }

    /**
     * Render the stock adjustments page
     */
    public function render_page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline" style="margin-bottom:20px;">Stock Adjustments</h1>
            <hr class="wp-header-end">

            <div id="col-container" class="wp-clearfix" style="display:flex;gap:24px;">
                <!-- Left: Add Adjustment Form -->
                <div id="col-left" style="flex:1; max-width:480px;">
                    <div class="col-wrap"
                        style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">
                        <h2 style="margin:0 0 20px 0;padding:0;font-size:16px;font-weight:600;color:#1d2327;">
                            Make Stock Adjustment
                        </h2>
                        <form id="add-adjustment-form" method="post">
                            <?php wp_nonce_field('add_stock_adjustment', 'adjustment_nonce'); ?>

                            <div style="display:flex;flex-direction:column;gap:15px;">
                                <!-- Stock Selection -->
                                <div class="form-field form-required">
                                    <label for="adjustment-product"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        Stock <span style="color:#d63638;">*</span>
                                    </label>
                                    <select name="fk_product_id" id="adjustment-product" required
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;cursor:pointer;">
                                        <option value="">Select Stock</option>
                                    </select>
                                </div>

                                <!-- Current Stock Display -->
                                <div style="background:#f6f7f7;padding:12px;border-radius:4px;border:1px solid #e2e4e7;">
                                    <div style="display:flex;justify-content:space-between;font-size:12px;">
                                        <span style="font-weight:600;">Current Stock:</span>
                                        <span id="current-stock" style="font-weight:600;color:#2271b1;">0</span>
                                    </div>
                                </div>

                                <!-- Adjustment Type and Quantity -->
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                    <div class="form-field form-required">
                                        <label for="adjustment-type"
                                            style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                            Type <span style="color:#d63638;">*</span>
                                        </label>
                                        <select name="adjustment_type" id="adjustment-type" required
                                            style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;cursor:pointer;">
                                            <option value="increase">Increase</option>
                                            <option value="decrease">Decrease</option>
                                        </select>
                                    </div>

                                    <div class="form-field form-required">
                                        <label for="adjustment-quantity"
                                            style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                            Quantity <span style="color:#d63638;">*</span>
                                        </label>
                                        <input name="quantity" id="adjustment-quantity" type="number" min="1" value="1" required
                                            style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;">
                                    </div>
                                </div>

                                <!-- New Stock Calculation -->
                                <div style="background:#f6f7f7;padding:12px;border-radius:4px;border:1px solid #e2e4e7;">
                                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px;">
                                        <span style="font-weight:600;">Adjustment:</span>
                                        <span id="adjustment-display" style="font-weight:600;color:#0a7c38;">+0</span>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;font-size:12px;">
                                        <span style="font-weight:600;">New Stock:</span>
                                        <span id="new-stock" style="font-weight:600;color:#2271b1;">0</span>
                                    </div>
                                </div>

                                <!-- Note -->
                                <div class="form-field">
                                    <label for="adjustment-note"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        Note
                                    </label>
                                    <textarea name="note" id="adjustment-note" rows="3"
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;resize:vertical;"
                                        placeholder="Reason for adjustment..."></textarea>
                                </div>
                            </div>

                            <div style="margin-top:20px;">
                                <button type="submit" id="submit-adjustment" class="button button-primary"
                                    style="width:100%;padding:8px 12px;font-size:13px;font-weight:500;">
                                    <span class="btn-text">Apply Adjustment</span>
                                    <span class="spinner" style="float:none;margin:0;display:none;"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right: Adjustments History Table -->
                <div id="col-right" style="flex:2;">
                    <div class="col-wrap"
                        style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">

                        <!-- Search and Filter Section -->
                        <div style="margin-bottom:20px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                            <!-- Search Input -->
                            <div style="flex:1;min-width:200px;">
                                <input type="text" id="adjustment-search" placeholder="Search by stock name..."
                                    style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;">
                            </div>

                            <!-- Type Filter -->
                            <div style="min-width:150px;">
                                <select id="type-filter"
                                    style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;">
                                    <option value="">All Types</option>
                                    <option value="increase">Increase</option>
                                    <option value="decrease">Decrease</option>
                                </select>
                            </div>

                            <!-- Date Filter -->
                            <div style="min-width:150px;">
                                <input type="date" id="date-filter"
                                    style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;">
                            </div>

                            <!-- Refresh Button -->
                            <button id="refresh-adjustments" class="button" style="padding:6px 12px;">
                                Refresh
                            </button>
                        </div>

                        <!-- Adjustments Table -->
                        <table class="wp-list-table widefat fixed striped table-view-list">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Stock</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Note</th>
                                    <th style="text-align:center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="adjustment-list">
                                <tr>
                                    <td colspan="6" class="loading-adjustments" style="text-align:center;">
                                        <span class="spinner is-active"></span>
                                        Loading adjustments...
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

            <script>
                jQuery(document).ready(function ($) {
                    let isSubmitting = false;
                    let currentPage = 1;
                    let perPage = 10;
                    let totalPages = 1;
                    let totalItems = 0;
                    let searchTerm = '';
                    let typeFilter = '';
                    let dateFilter = '';

                    // Load products and adjustments on page load
                    loadStocks();
                    loadAdjustments();

                    function loadStocks() {
                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'get_products_for_adjustments',
                                nonce: '<?php echo esc_attr(wp_create_nonce("get_products_for_adjustments")); ?>'
                            },
                            success: function (response) {
                                if (response.success) {
                                    let select = $('#adjustment-product');
                                    select.empty().append('<option value="">Select Stock</option>');

                                    $.each(response.data, function (_, product) {
                                        select.append(
                                            $('<option>').val(product.id).text(product.name)
                                        );
                                    });
                                }
                            }
                        });
                    }

                    function loadAdjustments(page = 1) {
                        currentPage = page;

                        let tbody = $('#adjustment-list');
                        tbody.html('<tr><td colspan="6" class="loading-adjustments" style="text-align:center;"><span class="spinner is-active"></span> Loading adjustments...</td></tr>');

                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'get_stock_adjustments',
                                page: currentPage,
                                per_page: perPage,
                                search: searchTerm,
                                type: typeFilter,
                                date: dateFilter,
                                nonce: '<?php echo esc_attr(wp_create_nonce("get_stock_adjustments")); ?>'
                            },
                            success: function (response) {
                                tbody.empty();
                                if (response.success) {
                                    if (!response.data.adjustments.length) {
                                        tbody.append('<tr><td colspan="6" style="text-align:center;padding:20px;color:#666;">No adjustments found.</td></tr>');
                                        updatePagination(response.data.pagination);
                                        return;
                                    }

                                    $.each(response.data.adjustments, function (_, adjustment) {
                                        let row = $('<tr>').attr('data-adjustment-id', adjustment.id);

                                        // Date column
                                        let date = new Date(adjustment.created_at);
                                        let formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                                        row.append($('<td>').text(formattedDate));

                                        // Stock column
                                        row.append($('<td>').text(adjustment.product_name || 'N/A'));

                                        // Type column
                                        let typeClass = 'adjustment-' + adjustment.adjustment_type;
                                        let typeText = adjustment.adjustment_type === 'increase' ? 'Increase' : 'Decrease';
                                        row.append($('<td>').append(
                                            $('<span>').addClass(typeClass).text(typeText)
                                        ));

                                        // Quantity column
                                        let quantityText = (adjustment.adjustment_type === 'increase' ? '+' : '-') + adjustment.quantity;
                                        row.append($('<td>').text(quantityText));

                                        // Note column
                                        row.append($('<td>').text(adjustment.note || '-'));

                                        // Actions column - Only Delete button
                                        row.append($('<td style="text-align:center;">')
                                            .append('<button class="button button-small button-link-delete delete-adjustment">Delete</button>')
                                        );

                                        tbody.append(row);
                                    });

                                    updatePagination(response.data.pagination);
                                } else {
                                    tbody.append('<tr><td colspan="6" style="color:red;text-align:center;">' + response.data + '</td></tr>');
                                }
                            },
                            error: function () {
                                $('#adjustment-list').html('<tr><td colspan="6" style="color:red;text-align:center;">Failed to load adjustments.</td></tr>');
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
                    let searchTimeout;
                    $('#adjustment-search').on('input', function () {
                        clearTimeout(searchTimeout);
                        searchTerm = $(this).val().trim();

                        searchTimeout = setTimeout(() => {
                            loadAdjustments(1);
                        }, 500);
                    });

                    // Type filter
                    $('#type-filter').on('change', function () {
                        typeFilter = $(this).val();
                        loadAdjustments(1);
                    });

                    // Date filter
                    $('#date-filter').on('change', function () {
                        dateFilter = $(this).val();
                        loadAdjustments(1);
                    });

                    // Per page change
                    $('#per-page-select').on('change', function () {
                        perPage = parseInt($(this).val());
                        loadAdjustments(1);
                    });

                    // Refresh button
                    $('#refresh-adjustments').on('click', function () {
                        loadAdjustments(currentPage);
                    });

                    // Pagination handlers
                    $('.first-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage > 1) loadAdjustments(1);
                    });

                    $('.prev-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage > 1) loadAdjustments(currentPage - 1);
                    });

                    $('.next-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage < totalPages) loadAdjustments(currentPage + 1);
                    });

                    $('.last-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage < totalPages) loadAdjustments(totalPages);
                    });

                    $('#current-page-selector').on('keypress', function (e) {
                        if (e.which === 13) { // Enter key
                            let page = parseInt($(this).val());
                            if (page >= 1 && page <= totalPages) {
                                loadAdjustments(page);
                            }
                        }
                    });

                    // Load current stock when product is selected
                    $('#adjustment-product').on('change', function () {
                        let productId = $(this).val();
                        if (productId) {
                            $.ajax({
                                url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                                type: 'GET',
                                data: {
                                    action: 'get_current_stock',
                                    product_id: productId,
                                    nonce: '<?php echo esc_attr(wp_create_nonce("get_current_stock")); ?>'
                                },
                                success: function (response) {
                                    if (response.success) {
                                        $('#current-stock').text(response.data.current_stock || 0);
                                        calculateNewStock();
                                    }
                                }
                            });
                        } else {
                            $('#current-stock').text('0');
                            calculateNewStock();
                        }
                    });

                    // Calculate new stock when type or quantity changes
                    function calculateNewStock() {
                        let currentStock = parseInt($('#current-stock').text()) || 0;
                        let adjustmentType = $('#adjustment-type').val();
                        let quantity = parseInt($('#adjustment-quantity').val()) || 0;

                        let adjustmentDisplay = (adjustmentType === 'increase' ? '+' : '-') + quantity;
                        let newStock = adjustmentType === 'increase' ? currentStock + quantity : currentStock - quantity;

                        $('#adjustment-display').text(adjustmentDisplay);
                        $('#new-stock').text(newStock);

                        // Color coding for new stock
                        if (newStock < 0) {
                            $('#new-stock').css('color', '#d63638');
                        } else if (newStock === 0) {
                            $('#new-stock').css('color', '#ffb900');
                        } else {
                            $('#new-stock').css('color', '#2271b1');
                        }

                        // Color coding for adjustment display
                        if (adjustmentType === 'increase') {
                            $('#adjustment-display').css('color', '#0a7c38');
                        } else {
                            $('#adjustment-display').css('color', '#d63638');
                        }
                    }

                    // Event listeners for calculation
                    $('#adjustment-type, #adjustment-quantity').on('change input', calculateNewStock);

                    $('#add-adjustment-form').on('submit', function (e) {
                        e.preventDefault();

                        // Prevent double submission
                        if (isSubmitting) {
                            return false;
                        }

                        let fk_product_id = $('#adjustment-product').val();
                        let adjustment_type = $('#adjustment-type').val();
                        let quantity = $('#adjustment-quantity').val();
                        let note = $('#adjustment-note').val();

                        if (!fk_product_id) {
                            alert('Please select a product');
                            return false;
                        }
                        if (quantity <= 0) {
                            alert('Please enter a valid quantity');
                            return false;
                        }

                        // Check if decrease would result in negative stock
                        let currentStock = parseInt($('#current-stock').text()) || 0;
                        let newStock = adjustment_type === 'increase' ? currentStock + parseInt(quantity) : currentStock - parseInt(quantity);

                        if (newStock < 0) {
                            if (!confirm('This adjustment will result in negative stock. Are you sure you want to continue?')) {
                                return false;
                            }
                        }

                        // Set submitting state
                        isSubmitting = true;
                        setButtonLoading(true);

                        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            action: 'add_stock_adjustment',
                            fk_product_id: fk_product_id,
                            adjustment_type: adjustment_type,
                            quantity: quantity,
                            note: note,
                            nonce: '<?php echo esc_attr(wp_create_nonce("add_stock_adjustment")); ?>'
                        }, function (res) {
                            if (res.success) {
                                resetForm();
                                loadAdjustments(1); // Reload to first page
                                // Reload current stock for the selected product
                                if (fk_product_id) {
                                    $('#adjustment-product').trigger('change');
                                }
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

                    $(document).on('click', '.delete-adjustment', function () {
                        if (!confirm('Are you sure you want to delete this adjustment?')) return;

                        let button = $(this);
                        let originalText = button.text();
                        let id = $(this).closest('tr').data('adjustment-id');

                        // Disable button and show loading
                        button.prop('disabled', true).text('Deleting...');

                        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            action: 'delete_stock_adjustment',
                            id: id,
                            nonce: '<?php echo esc_attr(wp_create_nonce("delete_stock_adjustment")); ?>'
                        }, function (res) {
                            if (res.success) {
                                loadAdjustments(currentPage);
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
                        let button = $('#submit-adjustment');
                        let spinner = button.find('.spinner');
                        let btnText = button.find('.btn-text');

                        if (loading) {
                            button.prop('disabled', true).addClass('button-loading');
                            spinner.show();
                            btnText.text('Applying...');
                        } else {
                            button.prop('disabled', false).removeClass('button-loading');
                            spinner.hide();
                            btnText.text('Apply Adjustment');
                        }
                    }

                    function resetForm() {
                        $('#adjustment-quantity').val('1');
                        $('#adjustment-note').val('');
                        calculateNewStock();
                        setButtonLoading(false);
                    }

                    // Initial calculation
                    calculateNewStock();
                });
            </script>
        </div>
        <?php
    }

    /** Get products for adjustments form */
    public function ajax_get_products_for_adjustments()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'get_products_for_adjustments')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $products_table = $wpdb->prefix . 'pos_products';
        $categories_table = $wpdb->prefix . 'pos_categories';

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $products = $wpdb->get_results(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT p.id, p.name, c.name as category_name 
            FROM $products_table p 
            LEFT JOIN $categories_table c ON p.fk_category_id = c.id 
            WHERE p.status = 'active' 
            ORDER BY p.name ASC"
        );

        wp_send_json_success($products);
    }

    /** Get current stock for a product */
    public function ajax_get_current_stock()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'get_current_stock')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $stocks_table = $wpdb->prefix . 'pos_stocks';
        $product_id = intval($_GET['product_id'] ?? 0);

        if ($product_id <= 0) {
            wp_send_json_error('Invalid product ID');
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $current_stock = $wpdb->get_var($wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT quantity FROM $stocks_table WHERE fk_product_id = %d",
            $product_id
        ));

        wp_send_json_success(['current_stock' => $current_stock ? intval($current_stock) : 0]);
    }

    /** Get stock adjustments with pagination and filters */
    public function ajax_get_stock_adjustments()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'get_stock_adjustments')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $adjustments_table = $wpdb->prefix . 'pos_stock_adjustments';
        $products_table = $wpdb->prefix . 'pos_products';

        // Get parameters - sanitize inputs
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
        $search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
        $type = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : '';
        $date = isset($_GET['date']) ? sanitize_text_field(wp_unslash($_GET['date'])) : '';

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

        // Count total items
        $count_query = "SELECT COUNT(*) FROM $adjustments_table a 
                   LEFT JOIN $products_table p ON a.fk_product_id = p.id 
                   $where_clause";

        if (!empty($query_params)) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $total_items = $wpdb->get_var($wpdb->prepare($count_query, $query_params));
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $total_items = $wpdb->get_var($count_query);
        }

        // Calculate pagination
        $total_pages = ceil($total_items / $per_page);
        $offset = ($page - 1) * $per_page;

        // Build main query
        $main_query = "SELECT a.*, p.name as product_name 
                  FROM $adjustments_table a 
                  LEFT JOIN $products_table p ON a.fk_product_id = p.id 
                  $where_clause 
                  ORDER BY a.created_at DESC 
                  LIMIT %d OFFSET %d";

        // Add pagination parameters to query params
        $query_params[] = $per_page;
        $query_params[] = $offset;

        // Execute main query
        if (!empty($query_params)) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $adjustments = $wpdb->get_results($wpdb->prepare($main_query, $query_params));
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $adjustments = $wpdb->get_results($main_query);
        }

        $response_data = [
            'adjustments' => $adjustments,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $per_page,
                'total_items' => $total_items,
                'total_pages' => $total_pages
            ]
        ];

        wp_send_json_success($response_data);
    }

    /** Add stock adjustment with full accounting */
    public function ajax_add_stock_adjustment()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'add_stock_adjustment')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $adjustments_table = $wpdb->prefix . 'pos_stock_adjustments';
        $stocks_table = $wpdb->prefix . 'pos_stocks';
        $accounting_table = $wpdb->prefix . 'pos_accounting';

        // Unslash and sanitize input
        $fk_product_id = intval($_POST['fk_product_id'] ?? 0);
        $adjustment_type = in_array(wp_unslash($_POST['adjustment_type'] ?? ''), ['increase', 'decrease']) ?
            sanitize_text_field(wp_unslash($_POST['adjustment_type'])) : 'increase';
        $quantity = intval($_POST['quantity'] ?? 0);
        $note = sanitize_textarea_field(wp_unslash($_POST['note'] ?? ''));

        // Validate required fields
        if ($fk_product_id <= 0) {
            wp_send_json_error('Please select a valid product');
        }
        if ($quantity <= 0) {
            wp_send_json_error('Quantity must be greater than 0');
        }

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Add adjustment record
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $adjustment_result = $wpdb->insert($adjustments_table, [
                'fk_product_id' => $fk_product_id,
                'adjustment_type' => $adjustment_type,
                'quantity' => $quantity,
                'note' => $note
            ], ['%d', '%s', '%d', '%s']);

            if ($adjustment_result === false) {
                throw new Exception('Failed to add adjustment record');
            }

            // Get current stock data for accounting calculation
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $current_stock_data = $wpdb->get_row($wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT quantity, net_cost FROM $stocks_table WHERE fk_product_id = %d",
                $fk_product_id
            ));

            $net_cost = $current_stock_data ? $current_stock_data->net_cost : 0;
            $adjustment_value = $net_cost * $quantity;

            // Update stock quantity
            if ($current_stock_data === null) {
                // Create stock entry if it doesn't exist
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $stock_result = $wpdb->insert($stocks_table, [
                    'fk_product_id' => $fk_product_id,
                    'quantity' => $adjustment_type === 'increase' ? $quantity : -$quantity,
                    'net_cost' => 0.00,
                    'sale_cost' => 0.00,
                    'status' => 'inStock'
                ], ['%d', '%d', '%f', '%f', '%s']);
            } else {
                // Update existing stock
                $new_quantity = $adjustment_type === 'increase' ?
                    $current_stock_data->quantity + $quantity : $current_stock_data->quantity - $quantity;

                // Determine status based on new quantity
                $status = $new_quantity > 10 ? 'inStock' : ($new_quantity > 0 ? 'lowStock' : 'outStock');

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                $stock_result = $wpdb->update($stocks_table, [
                    'quantity' => $new_quantity,
                    'status' => $status
                ], ['fk_product_id' => $fk_product_id], ['%d', '%s'], ['%d']);
            }

            if ($stock_result === false) {
                throw new Exception('Failed to update stock');
            }

            // ✅ ADD ACCOUNTING RECORDS FOR STOCK ADJUSTMENTS
            if ($adjustment_value > 0) {
                if ($adjustment_type === 'decrease') {
                    // Stock decrease = loss/wastage (money lost)
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $accounting_result = $wpdb->insert($accounting_table, [
                        'out_amount' => $adjustment_value,
                        'amount_receivable' => $adjustment_value,
                        'created_at' => current_time('mysql')
                    ], ['%f', '%f', '%s']);
                } else {
                    // Stock increase = gain/return (money gained/returned)
                    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
                    $accounting_result = $wpdb->insert($accounting_table, [
                        'in_amount' => $adjustment_value,
                        'amount_payable' => $adjustment_value,
                        'created_at' => current_time('mysql')
                    ], ['%f', '%f', '%s']);
                }

                if ($accounting_result === false) {
                    throw new Exception('Failed to create accounting record');
                }
            }

            // Commit transaction
            $wpdb->query('COMMIT');
            wp_send_json_success('Stock adjustment applied successfully');

        } catch (Exception $e) {
            // Rollback transaction on error
            $wpdb->query('ROLLBACK');
            wp_send_json_error($e->getMessage());
        }
    }

    /** Delete stock adjustment */
    public function ajax_delete_stock_adjustment()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'delete_stock_adjustment')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'pos_stock_adjustments';
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error('Invalid adjustment ID');
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->delete($table, ['id' => $id], ['%d']);

        if ($result === false) {
            wp_send_json_error('Failed to delete adjustment');
        }

        wp_send_json_success('Adjustment deleted successfully');
    }
}
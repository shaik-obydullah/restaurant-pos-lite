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
            <h1 class="wp-heading-inline" style="margin-bottom:20px;">
                <?php esc_html_e('Stock Adjustments', 'obydullah-restaurant-pos-lite'); ?>
            </h1>
            <hr class="wp-header-end">

            <div id="col-container" class="wp-clearfix" style="display:flex;gap:24px;">
                <!-- Left: Add Adjustment Form -->
                <div id="col-left" style="flex:1; max-width:480px;">
                    <div class="col-wrap"
                        style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">
                        <h2 style="margin:0 0 20px 0;padding:0;font-size:16px;font-weight:600;color:#1d2327;">
                            <?php esc_html_e('Make Stock Adjustment', 'obydullah-restaurant-pos-lite'); ?>
                        </h2>
                        <form id="add-adjustment-form" method="post">
                            <?php wp_nonce_field('orpl_add_stock_adjustment', 'adjustment_nonce'); ?>

                            <div style="display:flex;flex-direction:column;gap:15px;">
                                <!-- Stock Selection -->
                                <div class="form-field form-required">
                                    <label for="adjustment-product"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        <?php esc_html_e('Stock', 'obydullah-restaurant-pos-lite'); ?> <span
                                            style="color:#d63638;">*</span>
                                    </label>
                                    <select name="stock_id" id="adjustment-product" required
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;cursor:pointer;">
                                        <option value=""><?php esc_html_e('Select Stock', 'obydullah-restaurant-pos-lite'); ?>
                                        </option>
                                    </select>
                                </div>

                                <!-- Current Stock Display -->
                                <div style="background:#f6f7f7;padding:12px;border-radius:4px;border:1px solid #e2e4e7;">
                                    <div style="display:flex;justify-content:space-between;font-size:12px;">
                                        <span
                                            style="font-weight:600;"><?php esc_html_e('Current Stock:', 'obydullah-restaurant-pos-lite'); ?></span>
                                        <span id="current-stock" style="font-weight:600;color:#2271b1;">0</span>
                                    </div>
                                </div>

                                <!-- Adjustment Type and Quantity -->
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                    <div class="form-field form-required">
                                        <label for="adjustment-type"
                                            style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                            <?php esc_html_e('Type', 'obydullah-restaurant-pos-lite'); ?> <span
                                                style="color:#d63638;">*</span>
                                        </label>
                                        <select name="adjustment_type" id="adjustment-type" required
                                            style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;cursor:pointer;">
                                            <option value="increase">
                                                <?php esc_html_e('Increase', 'obydullah-restaurant-pos-lite'); ?>
                                            </option>
                                            <option value="decrease">
                                                <?php esc_html_e('Decrease', 'obydullah-restaurant-pos-lite'); ?>
                                            </option>
                                        </select>
                                    </div>

                                    <div class="form-field form-required">
                                        <label for="adjustment-quantity"
                                            style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                            <?php esc_html_e('Quantity', 'obydullah-restaurant-pos-lite'); ?> <span
                                                style="color:#d63638;">*</span>
                                        </label>
                                        <input name="quantity" id="adjustment-quantity" type="number" min="1" value="1" required
                                            style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;">
                                    </div>
                                </div>

                                <!-- New Stock Calculation -->
                                <div style="background:#f6f7f7;padding:12px;border-radius:4px;border:1px solid #e2e4e7;">
                                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px;">
                                        <span
                                            style="font-weight:600;"><?php esc_html_e('Adjustment:', 'obydullah-restaurant-pos-lite'); ?></span>
                                        <span id="adjustment-display" style="font-weight:600;color:#0a7c38;">+0</span>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;font-size:12px;">
                                        <span
                                            style="font-weight:600;"><?php esc_html_e('New Stock:', 'obydullah-restaurant-pos-lite'); ?></span>
                                        <span id="new-stock" style="font-weight:600;color:#2271b1;">0</span>
                                    </div>
                                </div>

                                <!-- Note -->
                                <div class="form-field">
                                    <label for="adjustment-note"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        <?php esc_html_e('Note', 'obydullah-restaurant-pos-lite'); ?>
                                    </label>
                                    <textarea name="note" id="adjustment-note" rows="3"
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;resize:vertical;"
                                        placeholder="<?php esc_attr_e('Reason for adjustment...', 'obydullah-restaurant-pos-lite'); ?>"></textarea>
                                </div>
                            </div>

                            <div style="margin-top:20px;">
                                <button type="submit" id="submit-adjustment" class="button button-primary"
                                    style="width:100%;padding:8px 12px;font-size:13px;font-weight:500;">
                                    <span
                                        class="btn-text"><?php esc_html_e('Apply Adjustment', 'obydullah-restaurant-pos-lite'); ?></span>
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
                                <input type="text" id="adjustment-search"
                                    placeholder="<?php esc_attr_e('Search by stock name...', 'obydullah-restaurant-pos-lite'); ?>"
                                    style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;">
                            </div>

                            <!-- Type Filter -->
                            <div style="min-width:150px;">
                                <select id="type-filter"
                                    style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;">
                                    <option value=""><?php esc_html_e('All Types', 'obydullah-restaurant-pos-lite'); ?></option>
                                    <option value="increase"><?php esc_html_e('Increase', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                    <option value="decrease"><?php esc_html_e('Decrease', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                </select>
                            </div>

                            <!-- Date Filter -->
                            <div style="min-width:150px;">
                                <input type="date" id="date-filter"
                                    style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;">
                            </div>

                            <!-- Refresh Button -->
                            <button id="refresh-adjustments" class="button" style="padding:6px 12px;">
                                <?php esc_html_e('Refresh', 'obydullah-restaurant-pos-lite'); ?>
                            </button>
                        </div>

                        <!-- Adjustments Table -->
                        <table class="wp-list-table widefat fixed striped table-view-list">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Date', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Stock', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Type', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Quantity', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Note', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th style="text-align:center;">
                                        <?php esc_html_e('Actions', 'obydullah-restaurant-pos-lite'); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="adjustment-list">
                                <tr>
                                    <td colspan="6" class="loading-adjustments" style="text-align:center;">
                                        <span class="spinner is-active"></span>
                                        <?php esc_html_e('Loading adjustments...', 'obydullah-restaurant-pos-lite'); ?>
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
                    let typeFilter = '';
                    let dateFilter = '';

                    // Load products and adjustments on page load
                    loadORPLStocks();
                    loadORPLAdjustments();

                    function loadORPLStocks() {
                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'orpl_get_products_for_adjustments',
                                nonce: '<?php echo esc_attr(wp_create_nonce("orpl_get_products_for_adjustments")); ?>'
                            },
                            success: function (response) {
                                if (response.success) {
                                    let select = $('#adjustment-product');
                                    select.empty().append('<option value=""><?php echo esc_js(__('Select Stock', 'obydullah-restaurant-pos-lite')); ?></option>');

                                    $.each(response.data, function (_, stock) {
                                        let displayText = stock.name;
                                        select.append(
                                            $('<option>').val(stock.stock_id).text(displayText)
                                        );
                                    });
                                }
                            }
                        });
                    }

                    function loadORPLAdjustments(page = 1) {
                        currentPage = page;

                        let tbody = $('#adjustment-list');
                        tbody.html('<tr><td colspan="6" class="loading-adjustments" style="text-align:center;"><span class="spinner is-active"></span> <?php echo esc_js(__('Loading adjustments...', 'obydullah-restaurant-pos-lite')); ?></td></tr>');

                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'orpl_get_stock_adjustments',
                                page: currentPage,
                                per_page: perPage,
                                search: searchTerm,
                                type: typeFilter,
                                date: dateFilter,
                                nonce: '<?php echo esc_attr(wp_create_nonce("orpl_get_stock_adjustments")); ?>'
                            },
                            success: function (response) {
                                tbody.empty();
                                if (response.success) {
                                    if (!response.data.adjustments.length) {
                                        tbody.append('<tr><td colspan="6" style="text-align:center;padding:20px;color:#666;"><?php echo esc_js(__('No adjustments found.', 'obydullah-restaurant-pos-lite')); ?></td></tr>');
                                        updateORPLPagination(response.data.pagination);
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
                                        let typeText = adjustment.adjustment_type === 'increase' ? '<?php echo esc_js(__('Increase', 'obydullah-restaurant-pos-lite')); ?>' : '<?php echo esc_js(__('Decrease', 'obydullah-restaurant-pos-lite')); ?>';
                                        row.append($('<td>').append(
                                            $('<span>').addClass(typeClass).text(typeText)
                                        ));

                                        // Quantity column
                                        let quantityText = (adjustment.adjustment_type === 'increase' ? '+' : '-') + adjustment.quantity;
                                        row.append($('<td>').text(quantityText));

                                        // Note column
                                        row.append($('<td>').text(adjustment.note || '-'));

                                        // Actions column
                                        row.append($('<td style="text-align:center;">')
                                            .append('<button class="button button-small button-link-delete delete-adjustment"><?php echo esc_js(__('Delete', 'obydullah-restaurant-pos-lite')); ?></button>')
                                        );

                                        tbody.append(row);
                                    });

                                    updateORPLPagination(response.data.pagination);
                                } else {
                                    tbody.append('<tr><td colspan="6" style="color:red;text-align:center;">' + response.data + '</td></tr>');
                                }
                            },
                            error: function () {
                                $('#adjustment-list').html('<tr><td colspan="6" style="color:red;text-align:center;"><?php echo esc_js(__('Failed to load adjustments.', 'obydullah-restaurant-pos-lite')); ?></td></tr>');
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
                    let searchTimeout;
                    $('#adjustment-search').on('input', function () {
                        clearTimeout(searchTimeout);
                        searchTerm = $(this).val().trim();

                        searchTimeout = setTimeout(() => {
                            loadORPLAdjustments(1);
                        }, 500);
                    });

                    // Type filter
                    $('#type-filter').on('change', function () {
                        typeFilter = $(this).val();
                        loadORPLAdjustments(1);
                    });

                    // Date filter
                    $('#date-filter').on('change', function () {
                        dateFilter = $(this).val();
                        loadORPLAdjustments(1);
                    });

                    // Per page change
                    $('#per-page-select').on('change', function () {
                        perPage = parseInt($(this).val());
                        loadORPLAdjustments(1);
                    });

                    // Refresh button
                    $('#refresh-adjustments').on('click', function () {
                        loadORPLAdjustments(currentPage);
                    });

                    // Pagination handlers
                    $('.first-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage > 1) loadORPLAdjustments(1);
                    });

                    $('.prev-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage > 1) loadORPLAdjustments(currentPage - 1);
                    });

                    $('.next-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage < totalPages) loadORPLAdjustments(currentPage + 1);
                    });

                    $('.last-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage < totalPages) loadORPLAdjustments(totalPages);
                    });

                    $('#current-page-selector').on('keypress', function (e) {
                        if (e.which === 13) { // Enter key
                            let page = parseInt($(this).val());
                            if (page >= 1 && page <= totalPages) {
                                loadORPLAdjustments(page);
                            }
                        }
                    });

                    // Load current stock when product is selected
                    $('#adjustment-product').on('change', function () {
                        let stockId = $(this).val();
                        if (stockId) {
                            $.ajax({
                                url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                                type: 'GET',
                                data: {
                                    action: 'orpl_get_current_stock',
                                    stock_id: stockId,
                                    nonce: '<?php echo esc_attr(wp_create_nonce("orpl_get_current_stock")); ?>'
                                },
                                success: function (response) {
                                    if (response.success) {
                                        $('#current-stock').text(response.data.current_stock || 0);
                                        calculateNewORPLStock();
                                    }
                                }
                            });
                        } else {
                            $('#current-stock').text('0');
                            calculateNewORPLStock();
                        }
                    });

                    // Calculate new stock when type or quantity changes
                    function calculateNewORPLStock() {
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
                    $('#adjustment-type, #adjustment-quantity').on('change input', calculateNewORPLStock);

                    $('#add-adjustment-form').on('submit', function (e) {
                        e.preventDefault();

                        // Prevent double submission
                        if (isSubmitting) {
                            return false;
                        }

                        let stock_id = $('#adjustment-product').val();
                        let adjustment_type = $('#adjustment-type').val();
                        let quantity = $('#adjustment-quantity').val();
                        let note = $('#adjustment-note').val();

                        if (!stock_id) {
                            alert('<?php echo esc_js(__('Please select a stock', 'obydullah-restaurant-pos-lite')); ?>');
                            return false;
                        }
                        if (quantity <= 0) {
                            alert('<?php echo esc_js(__('Please enter a valid quantity', 'obydullah-restaurant-pos-lite')); ?>');
                            return false;
                        }

                        // Check if decrease would result in negative stock
                        let currentStock = parseInt($('#current-stock').text()) || 0;
                        let newStock = adjustment_type === 'increase' ? currentStock + parseInt(quantity) : currentStock - parseInt(quantity);

                        if (newStock < 0) {
                            if (!confirm('<?php echo esc_js(__('This adjustment will result in negative stock. Are you sure you want to continue?', 'obydullah-restaurant-pos-lite')); ?>')) {
                                return false;
                            }
                        }

                        // Set submitting state
                        isSubmitting = true;
                        setButtonLoading(true);

                        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            action: 'orpl_add_stock_adjustment',
                            stock_id: stock_id,
                            adjustment_type: adjustment_type,
                            quantity: quantity,
                            note: note,
                            nonce: '<?php echo esc_attr(wp_create_nonce("orpl_add_stock_adjustment")); ?>'
                        }, function (res) {
                            if (res.success) {
                                resetForm();
                                loadORPLAdjustments(1); // Reload to first page
                                // Reload current stock for the selected stock
                                if (stock_id) {
                                    $('#adjustment-product').trigger('change');
                                }
                            } else {
                                alert('<?php echo esc_js(__('Error:', 'obydullah-restaurant-pos-lite')); ?> ' + res.data);
                            }
                        }).fail(() => alert('<?php echo esc_js(__('Request failed. Please try again.', 'obydullah-restaurant-pos-lite')); ?>'))
                            .always(function () {
                                // Reset submitting state
                                isSubmitting = false;
                                setButtonLoading(false);
                            });
                    });

                    $(document).on('click', '.delete-adjustment', function () {
                        if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this adjustment?', 'obydullah-restaurant-pos-lite')); ?>')) return;

                        let button = $(this);
                        let originalText = button.text();
                        let id = $(this).closest('tr').data('adjustment-id');

                        // Disable button and show loading
                        button.prop('disabled', true).text('<?php echo esc_js(__('Deleting...', 'obydullah-restaurant-pos-lite')); ?>');

                        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            action: 'orpl_delete_stock_adjustment',
                            id: id,
                            nonce: '<?php echo esc_attr(wp_create_nonce("orpl_delete_stock_adjustment")); ?>'
                        }, function (res) {
                            if (res.success) {
                                loadORPLAdjustments(currentPage);
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
                        let button = $('#submit-adjustment');
                        let spinner = button.find('.spinner');
                        let btnText = button.find('.btn-text');

                        if (loading) {
                            button.prop('disabled', true).addClass('button-loading');
                            spinner.show();
                            btnText.text('<?php echo esc_js(__('Applying...', 'obydullah-restaurant-pos-lite')); ?>');
                        } else {
                            button.prop('disabled', false).removeClass('button-loading');
                            spinner.hide();
                            btnText.text('<?php echo esc_js(__('Apply Adjustment', 'obydullah-restaurant-pos-lite')); ?>');
                        }
                    }

                    function resetForm() {
                        $('#adjustment-quantity').val('1');
                        $('#adjustment-note').val('');
                        calculateNewORPLStock();
                        setButtonLoading(false);
                    }

                    // Initial calculation
                    calculateNewORPLStock();
                });
            </script>
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
            $query = $wpdb->prepare(
                "SELECT s.id as stock_id, p.id as product_id, p.name, 
                   s.quantity, s.net_cost, s.sale_cost, s.status,
                   c.name as category_name 
            FROM {$this->stocks_table} s 
            INNER JOIN {$this->products_table} p ON s.fk_product_id = p.id 
            LEFT JOIN {$this->categories_table} c ON p.fk_category_id = c.id 
            ORDER BY p.name ASC, s.id ASC"
            );

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

        // Updated COUNT query with fk_stock_id
        $count_query = "SELECT COUNT(*) FROM {$this->adjustments_table} a 
           LEFT JOIN {$this->stocks_table} s ON a.fk_stock_id = s.id 
           LEFT JOIN {$this->products_table} p ON s.fk_product_id = p.id 
           {$where_clause}";

        if (!empty($query_params)) {
            $total_items = $wpdb->get_var($wpdb->prepare($count_query, $query_params));
        } else {
            $total_items = $wpdb->get_var($count_query);
        }

        // Calculate pagination
        $total_pages = ceil($total_items / $per_page);
        $offset = ($page - 1) * $per_page;

        // Updated main query with fk_stock_id
        $main_query = "SELECT a.*, p.name as product_name 
          FROM {$this->adjustments_table} a 
          LEFT JOIN {$this->stocks_table} s ON a.fk_stock_id = s.id 
          LEFT JOIN {$this->products_table} p ON s.fk_product_id = p.id 
          {$where_clause} 
          ORDER BY a.created_at DESC 
          LIMIT %d OFFSET %d";

        // Add pagination parameters to query params
        $pagination_params = $query_params;
        $pagination_params[] = $per_page;
        $pagination_params[] = $offset;

        // Execute main query
        if (!empty($pagination_params)) {
            $adjustments = $wpdb->get_results($wpdb->prepare($main_query, $pagination_params));
        } else {
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
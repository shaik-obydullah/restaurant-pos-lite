<?php
/**
 * Stock Management
 *
 * @package Obydullah_Restaurant_POS_Lite
 * @since   1.0.0
 */

if (!defined('ABSPATH'))
    exit;

class Obydullah_Restaurant_POS_Lite_Stocks
{
    public function __construct()
    {
        add_action('wp_ajax_orpl_add_stock', [$this, 'ajax_add_orpl_stock']);
        add_action('wp_ajax_orpl_get_stocks', [$this, 'ajax_get_orpl_stocks']);
        add_action('wp_ajax_orpl_delete_stock', [$this, 'ajax_orpl_delete_stock']);
        add_action('wp_ajax_orpl_get_products_for_stocks', [$this, 'ajax_get_products_for_stocks']);
    }

    /**
     * Render the stocks page
     */
    public function render_page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline" style="margin-bottom:20px;">
                <?php esc_html_e('Stock Management', 'obydullah-restaurant-pos-lite'); ?>
            </h1>
            <hr class="wp-header-end">

            <!-- Stock Summary Cards -->
            <div class="stock-summary-cards"
                style="display:grid;grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));gap:20px;margin-bottom:30px;">
                <div class="summary-card"
                    style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.05);border-left:4px solid #0a7c38;">
                    <h3 style="margin:0 0 10px 0;font-size:14px;color:#666;">
                        <?php esc_html_e('In Stock', 'obydullah-restaurant-pos-lite'); ?>
                    </h3>
                    <p class="summary-number" style="font-size:32px;font-weight:bold;margin:0;color:#0a7c38;">0</p>
                </div>
                <div class="summary-card"
                    style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.05);border-left:4px solid #d63638;">
                    <h3 style="margin:0 0 10px 0;font-size:14px;color:#666;">
                        <?php esc_html_e('Out of Stock', 'obydullah-restaurant-pos-lite'); ?>
                    </h3>
                    <p class="summary-number" style="font-size:32px;font-weight:bold;margin:0;color:#d63638;">0</p>
                </div>
                <div class="summary-card"
                    style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.05);border-left:4px solid #ffb900;">
                    <h3 style="margin:0 0 10px 0;font-size:14px;color:#666;">
                        <?php esc_html_e('Low Stock', 'obydullah-restaurant-pos-lite'); ?>
                    </h3>
                    <p class="summary-number" style="font-size:32px;font-weight:bold;margin:0;color:#ffb900;">0</p>
                </div>
                <div class="summary-card"
                    style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.05);border-left:4px solid #2271b1;">
                    <h3 style="margin:0 0 10px 0;font-size:14px;color:#666;">
                        <?php esc_html_e('Total Products', 'obydullah-restaurant-pos-lite'); ?>
                    </h3>
                    <p class="summary-number" style="font-size:32px;font-weight:bold;margin:0;color:#2271b1;">0</p>
                </div>
            </div>

            <div id="col-container" class="wp-clearfix" style="display:flex;gap:24px;">
                <!-- Left: Add Stock Form -->
                <div id="col-left" style="flex:1; max-width:480px;">
                    <div class="col-wrap"
                        style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">
                        <h2 style="margin:0 0 20px 0;padding:0;font-size:16px;font-weight:600;color:#1d2327;">
                            <?php esc_html_e('Add Stock Entry', 'obydullah-restaurant-pos-lite'); ?>
                        </h2>
                        <form id="add-stock-form" method="post">
                            <?php wp_nonce_field('orpl_add_stock', 'stock_nonce'); ?>

                            <div style="display:flex;flex-direction:column;gap:15px;">
                                <!-- Product Selection -->
                                <div class="form-field form-required">
                                    <label for="stock-product"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        <?php esc_html_e('Product', 'obydullah-restaurant-pos-lite'); ?> <span
                                            style="color:#d63638;">*</span>
                                    </label>
                                    <select name="fk_product_id" id="stock-product" required
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;cursor:pointer;">
                                        <option value=""><?php esc_html_e('Select Product', 'obydullah-restaurant-pos-lite'); ?>
                                        </option>
                                    </select>
                                </div>

                                <!-- Cost and Price -->
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                    <div class="form-field form-required">
                                        <label for="net-cost"
                                            style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                            <?php esc_html_e('Net Cost', 'obydullah-restaurant-pos-lite'); ?> <span
                                                style="color:#d63638;">*</span>
                                        </label>
                                        <input name="net_cost" id="net-cost" type="number" step="0.01" min="0" value="0.00"
                                            required
                                            style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;">
                                    </div>

                                    <div class="form-field form-required">
                                        <label for="sale-cost"
                                            style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                            <?php esc_html_e('Sale Price', 'obydullah-restaurant-pos-lite'); ?> <span
                                                style="color:#d63638;">*</span>
                                        </label>
                                        <input name="sale_cost" id="sale-cost" type="number" step="0.01" min="0" value="0.00"
                                            required
                                            style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;">
                                    </div>
                                </div>

                                <!-- Quantity and Status -->
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                    <div class="form-field form-required">
                                        <label for="stock-quantity"
                                            style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                            <?php esc_html_e('Quantity', 'obydullah-restaurant-pos-lite'); ?> <span
                                                style="color:#d63638;">*</span>
                                        </label>
                                        <input name="quantity" id="stock-quantity" type="number" min="0" value="0" required
                                            style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;">
                                    </div>

                                    <div class="form-field">
                                        <label for="stock-status"
                                            style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                            <?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?>
                                        </label>
                                        <select name="status" id="stock-status"
                                            style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;cursor:pointer;">
                                            <option value="inStock">
                                                <?php esc_html_e('In Stock', 'obydullah-restaurant-pos-lite'); ?>
                                            </option>
                                            <option value="outStock">
                                                <?php esc_html_e('Out of Stock', 'obydullah-restaurant-pos-lite'); ?>
                                            </option>
                                            <option value="lowStock">
                                                <?php esc_html_e('Low Stock', 'obydullah-restaurant-pos-lite'); ?>
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Profit Calculation -->
                                <div style="background:#f6f7f7;padding:12px;border-radius:4px;border:1px solid #e2e4e7;">
                                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px;">
                                        <span
                                            style="font-weight:600;"><?php esc_html_e('Profit Margin:', 'obydullah-restaurant-pos-lite'); ?></span>
                                        <span id="profit-margin" style="font-weight:600;color:#0a7c38;">0.00%</span>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;font-size:12px;">
                                        <span
                                            style="font-weight:600;"><?php esc_html_e('Total Profit:', 'obydullah-restaurant-pos-lite'); ?></span>
                                        <span id="total-profit" style="font-weight:600;color:#0a7c38;">0.00</span>
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top:20px;">
                                <button type="submit" id="submit-stock" class="button button-primary"
                                    style="width:100%;padding:8px 12px;font-size:13px;font-weight:500;">
                                    <span
                                        class="btn-text"><?php esc_html_e('Save Stock', 'obydullah-restaurant-pos-lite'); ?></span>
                                    <span class="spinner" style="float:none;margin:0;display:none;"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right: Stocks Table -->
                <div id="col-right" style="flex:2;">
                    <div class="col-wrap"
                        style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">

                        <!-- Search and Filter Section -->
                        <div style="margin-bottom:20px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                            <!-- Search Input -->
                            <div style="flex:1;min-width:200px;">
                                <input type="text" id="stock-search"
                                    placeholder="<?php esc_attr_e('Search by product name...', 'obydullah-restaurant-pos-lite'); ?>"
                                    style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;">
                            </div>

                            <!-- Status Filter -->
                            <div style="min-width:150px;">
                                <select id="status-filter"
                                    style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;">
                                    <option value=""><?php esc_html_e('All Status', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                    <option value="inStock"><?php esc_html_e('In Stock', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                    <option value="outStock">
                                        <?php esc_html_e('Out of Stock', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                    <option value="lowStock"><?php esc_html_e('Low Stock', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                </select>
                            </div>

                            <!-- Quantity Filter -->
                            <div style="min-width:150px;">
                                <select id="quantity-filter"
                                    style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;">
                                    <option value=""><?php esc_html_e('All Quantities', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                    <option value="zero"><?php esc_html_e('Zero Quantity', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                    <option value="low">
                                        <?php esc_html_e('Low Quantity (1-10)', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                    <option value="high">
                                        <?php esc_html_e('High Quantity (10+)', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                </select>
                            </div>

                            <!-- Refresh Button -->
                            <button id="refresh-stocks" class="button" style="padding:6px 12px;">
                                <?php esc_html_e('Refresh', 'obydullah-restaurant-pos-lite'); ?>
                            </button>
                        </div>

                        <!-- Stocks Table -->
                        <table class="wp-list-table widefat fixed striped table-view-list">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Product', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Net Cost', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Sale Price', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Quantity', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th style="text-align:center;">
                                        <?php esc_html_e('Actions', 'obydullah-restaurant-pos-lite'); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="stock-list">
                                <tr>
                                    <td colspan="6" class="loading-stocks" style="text-align:center;">
                                        <span class="spinner is-active"></span>
                                        <?php esc_html_e('Loading stocks...', 'obydullah-restaurant-pos-lite'); ?>
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
                                <span class="pagination-links" style="margin-left: 15px;">
                                    <a class="first-page button" href="#" style="margin-right: 5px;">
                                        <span
                                            class="screen-reader-text"><?php esc_html_e('First page', 'obydullah-restaurant-pos-lite'); ?></span>
                                        <span aria-hidden="true">«</span>
                                    </a>
                                    <a class="prev-page button" href="#" style="margin-right: 5px;">
                                        <span
                                            class="screen-reader-text"><?php esc_html_e('Previous page', 'obydullah-restaurant-pos-lite'); ?></span>
                                        <span aria-hidden="true">‹</span>
                                    </a>
                                    <span class="paging-input" style="margin: 0 10px;">
                                        <label for="current-page-selector"
                                            class="screen-reader-text"><?php esc_html_e('Current Page', 'obydullah-restaurant-pos-lite'); ?></label>
                                        <input class="current-page" id="current-page-selector" type="text" name="paged"
                                            value="1" size="3" aria-describedby="table-paging">
                                        <span class="tablenav-paging-text">
                                            <?php esc_html_e('of', 'obydullah-restaurant-pos-lite'); ?> <span
                                                class="total-pages">1</span></span>
                                    </span>
                                    <a class="next-page button" href="#" style="margin-left: 5px;">
                                        <span
                                            class="screen-reader-text"><?php esc_html_e('Next page', 'obydullah-restaurant-pos-lite'); ?></span>
                                        <span aria-hidden="true">›</span>
                                    </a>
                                    <a class="last-page button" href="#" style="margin-left: 5px;">
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
                    let statusFilter = '';
                    let quantityFilter = '';

                    // Load products and stocks on page load
                    loadORPLProducts();
                    loadORPLStocks();

                    function loadORPLProducts() {
                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'orpl_get_products_for_stocks',
                                nonce: '<?php echo esc_attr(wp_create_nonce("orpl_get_products_for_stocks")); ?>'
                            },
                            success: function (response) {
                                if (response.success) {
                                    let select = $('#stock-product');
                                    select.empty().append('<option value=""><?php echo esc_js(__('Select Product', 'obydullah-restaurant-pos-lite')); ?></option>');

                                    $.each(response.data, function (_, product) {
                                        select.append(
                                            $('<option>').val(product.id).text(product.name)
                                        );
                                    });
                                }
                            }
                        });
                    }

                    function loadORPLStocks(page = 1) {
                        currentPage = page;

                        let tbody = $('#stock-list');
                        tbody.html('<tr><td colspan="6" class="loading-stocks" style="text-align:center;"><span class="spinner is-active"></span> <?php echo esc_js(__('Loading stocks...', 'obydullah-restaurant-pos-lite')); ?></td></tr>');

                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'orpl_get_stocks',
                                page: currentPage,
                                per_page: perPage,
                                search: searchTerm,
                                status: statusFilter,
                                quantity: quantityFilter,
                                nonce: '<?php echo esc_attr(wp_create_nonce("orpl_get_stocks")); ?>'
                            },
                            success: function (response) {
                                tbody.empty();
                                if (response.success) {
                                    if (!response.data.stocks.length) {
                                        tbody.append('<tr><td colspan="6" style="text-align:center;padding:20px;color:#666;"><?php echo esc_js(__('No stock entries found.', 'obydullah-restaurant-pos-lite')); ?></td></tr>');
                                        updateORPLSummaryCards();
                                        updateORPLPagination(response.data.pagination);
                                        return;
                                    }

                                    $.each(response.data.stocks, function (_, stock) {
                                        let row = $('<tr>').attr('data-stock-id', stock.id);

                                        // Product column
                                        row.append($('<td>').text(stock.product_name || 'N/A'));

                                        // Net Cost column
                                        row.append($('<td>').text(parseFloat(stock.net_cost).toFixed(2)));

                                        // Sale Price column
                                        row.append($('<td>').text(parseFloat(stock.sale_cost).toFixed(2)));

                                        // Quantity column
                                        row.append($('<td>').text(stock.quantity));

                                        // Status column
                                        let statusClass = 'status-' + stock.status;
                                        let statusText = stock.status === 'inStock' ? '<?php echo esc_js(__('In Stock', 'obydullah-restaurant-pos-lite')); ?>' :
                                            stock.status === 'outStock' ? '<?php echo esc_js(__('Out of Stock', 'obydullah-restaurant-pos-lite')); ?>' : '<?php echo esc_js(__('Low Stock', 'obydullah-restaurant-pos-lite')); ?>';
                                        row.append($('<td>').append(
                                            $('<span>').addClass(statusClass).text(statusText)
                                        ));

                                        // Actions column - Only Delete button
                                        row.append($('<td style="text-align:center;">')
                                            .append('<button class="button button-small button-link-delete delete-stock"><?php echo esc_js(__('Delete', 'obydullah-restaurant-pos-lite')); ?></button>')
                                        );

                                        tbody.append(row);
                                    });

                                    updateORPLSummaryCards(response.data.stocks);
                                    updateORPLPagination(response.data.pagination);
                                } else {
                                    tbody.append('<tr><td colspan="6" style="color:red;text-align:center;">' + response.data + '</td></tr>');
                                }
                            },
                            error: function () {
                                $('#stock-list').html('<tr><td colspan="6" style="color:red;text-align:center;"><?php echo esc_js(__('Failed to load stocks.', 'obydullah-restaurant-pos-lite')); ?></td></tr>');
                            }
                        });
                    }

                    function updateORPLSummaryCards(stocks = []) {
                        let inStock = 0, outStock = 0, lowStock = 0;

                        if (stocks.length > 0) {
                            stocks.forEach(stock => {
                                if (stock.status === 'inStock') inStock++;
                                else if (stock.status === 'outStock') outStock++;
                                else if (stock.status === 'lowStock') lowStock++;
                            });
                        }

                        $('.summary-card:nth-child(1) .summary-number').text(inStock);
                        $('.summary-card:nth-child(2) .summary-number').text(outStock);
                        $('.summary-card:nth-child(3) .summary-number').text(lowStock);
                        $('.summary-card:nth-child(4) .summary-number').text(stocks.length);
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
                    $('#stock-search').on('input', function () {
                        clearTimeout(searchTimeout);
                        searchTerm = $(this).val().trim();

                        searchTimeout = setTimeout(() => {
                            loadORPLStocks(1);
                        }, 500);
                    });

                    // Status filter
                    $('#status-filter').on('change', function () {
                        statusFilter = $(this).val();
                        loadORPLStocks(1);
                    });

                    // Quantity filter
                    $('#quantity-filter').on('change', function () {
                        quantityFilter = $(this).val();
                        loadORPLStocks(1);
                    });

                    // Per page change
                    $('#per-page-select').on('change', function () {
                        perPage = parseInt($(this).val());
                        loadORPLStocks(1);
                    });

                    // Refresh button
                    $('#refresh-stocks').on('click', function () {
                        loadORPLStocks(currentPage);
                    });

                    // Pagination handlers
                    $('.first-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage > 1) loadORPLStocks(1);
                    });

                    $('.prev-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage > 1) loadORPLStocks(currentPage - 1);
                    });

                    $('.next-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage < totalPages) loadORPLStocks(currentPage + 1);
                    });

                    $('.last-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage < totalPages) loadORPLStocks(totalPages);
                    });

                    $('#current-page-selector').on('keypress', function (e) {
                        if (e.which === 13) { // Enter key
                            let page = parseInt($(this).val());
                            if (page >= 1 && page <= totalPages) {
                                loadORPLStocks(page);
                            }
                        }
                    });

                    // Profit calculation
                    function calculateORPLProfit() {
                        let netCost = parseFloat($('#net-cost').val()) || 0;
                        let saleCost = parseFloat($('#sale-cost').val()) || 0;
                        let quantity = parseInt($('#stock-quantity').val()) || 0;

                        let profitPerUnit = saleCost - netCost;
                        let totalProfit = profitPerUnit * quantity;
                        let profitMargin = netCost > 0 ? ((profitPerUnit / netCost) * 100) : 0;

                        $('#profit-margin').text(profitMargin.toFixed(2) + '%');
                        $('#total-profit').text(totalProfit.toFixed(2));

                        // Color coding for profit
                        if (profitMargin > 0) {
                            $('#profit-margin, #total-profit').css('color', '#0a7c38');
                        } else if (profitMargin < 0) {
                            $('#profit-margin, #total-profit').css('color', '#d63638');
                        } else {
                            $('#profit-margin, #total-profit').css('color', '#666');
                        }
                    }

                    // Event listeners for profit calculation
                    $('#net-cost, #sale-cost, #stock-quantity').on('input', calculateORPLProfit);

                    $('#add-stock-form').on('submit', function (e) {
                        e.preventDefault();

                        // Prevent double submission
                        if (isSubmitting) {
                            return false;
                        }

                        let fk_product_id = $('#stock-product').val();
                        let net_cost = $('#net-cost').val();
                        let sale_cost = $('#sale-cost').val();
                        let quantity = $('#stock-quantity').val();
                        let status = $('#stock-status').val();

                        if (!fk_product_id) {
                            alert('<?php echo esc_js(__('Please select a product', 'obydullah-restaurant-pos-lite')); ?>');
                            return false;
                        }
                        if (net_cost <= 0) {
                            alert('<?php echo esc_js(__('Please enter a valid net cost', 'obydullah-restaurant-pos-lite')); ?>');
                            return false;
                        }
                        if (sale_cost <= 0) {
                            alert('<?php echo esc_js(__('Please enter a valid sale price', 'obydullah-restaurant-pos-lite')); ?>');
                            return false;
                        }
                        if (quantity < 0) {
                            alert('<?php echo esc_js(__('Please enter a valid quantity', 'obydullah-restaurant-pos-lite')); ?>');
                            return false;
                        }

                        // Set submitting state
                        isSubmitting = true;
                        setButtonLoading(true);

                        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            action: 'orpl_add_stock',
                            fk_product_id: fk_product_id,
                            net_cost: net_cost,
                            sale_cost: sale_cost,
                            quantity: quantity,
                            status: status,
                            nonce: '<?php echo esc_attr(wp_create_nonce("orpl_add_stock")); ?>'
                        }, function (res) {
                            if (res.success) {
                                resetForm();
                                loadORPLStocks(1); // Reload to first page
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

                    $(document).on('click', '.delete-stock', function () {
                        if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this stock entry?', 'obydullah-restaurant-pos-lite')); ?>')) return;

                        let button = $(this);
                        let originalText = button.text();
                        let id = $(this).closest('tr').data('stock-id');

                        // Disable button and show loading
                        button.prop('disabled', true).text('<?php echo esc_js(__('Deleting...', 'obydullah-restaurant-pos-lite')); ?>');

                        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            action: 'orpl_delete_stock',
                            id: id,
                            nonce: '<?php echo esc_attr(wp_create_nonce("orpl_delete_stock")); ?>'
                        }, function (res) {
                            if (res.success) {
                                loadORPLStocks(currentPage);
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
                        let button = $('#submit-stock');
                        let spinner = button.find('.spinner');
                        let btnText = button.find('.btn-text');

                        if (loading) {
                            button.prop('disabled', true).addClass('button-loading');
                            spinner.show();
                            btnText.text('<?php echo esc_js(__('Saving...', 'obydullah-restaurant-pos-lite')); ?>');
                        } else {
                            button.prop('disabled', false).removeClass('button-loading');
                            spinner.hide();
                            btnText.text('<?php echo esc_js(__('Save Stock', 'obydullah-restaurant-pos-lite')); ?>');
                        }
                    }

                    function resetForm() {
                        $('#stock-product').val('');
                        $('#net-cost').val('0.00');
                        $('#sale-cost').val('0.00');
                        $('#stock-quantity').val('0');
                        $('#stock-status').val('inStock');

                        calculateORPLProfit();
                        // Ensure button is enabled
                        setButtonLoading(false);
                    }

                    // Initial profit calculation
                    calculateORPLProfit();
                });
            </script>
        </div>
        <?php
    }

    /** Get products for stock form */
    public function ajax_get_products_for_stocks()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_get_products_for_stocks')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;
        $products_table = $wpdb->prefix . 'orpl_products';
        $categories_table = $wpdb->prefix . 'orpl_categories';

        $product_status = 'active';

        // Get products with caching
        $cache_key = 'orpl_active_products_for_stocks';
        $products = wp_cache_get($cache_key, 'obydullah-restaurant-pos-lite');

        if (false === $products) {
            $query = $wpdb->prepare(
                "SELECT p.id, p.name, c.name as category_name 
            FROM {$products_table} p 
            LEFT JOIN {$categories_table} c ON p.fk_category_id = c.id 
            WHERE p.status = %s 
            ORDER BY p.name ASC",
                $product_status
            );
            $products = $wpdb->get_results($query);

            // Cache for 5 minutes
            wp_cache_set($cache_key, $products, 'obydullah-restaurant-pos-lite', 300);
        }

        wp_send_json_success($products);
    }

    /** Get all stocks with pagination and filters */
    public function ajax_get_orpl_stocks()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_get_stocks')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;
        $stocks_table = $wpdb->prefix . 'orpl_stocks';
        $products_table = $wpdb->prefix . 'orpl_products';

        // Get parameters - sanitize inputs
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
        $search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
        $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        $quantity = isset($_GET['quantity']) ? sanitize_text_field(wp_unslash($_GET['quantity'])) : '';

        // Generate cache key based on all parameters
        $cache_key = 'orpl_stocks_' . md5($page . '_' . $per_page . '_' . $search . '_' . $status . '_' . $quantity);
        $cached_data = wp_cache_get($cache_key, 'obydullah-restaurant-pos-lite');

        if (false !== $cached_data) {
            wp_send_json_success($cached_data);
        }

        // Build WHERE conditions
        $where_conditions = [];
        $query_params = [];

        // Search filter
        if (!empty($search)) {
            $where_conditions[] = "p.name LIKE %s";
            $query_params[] = '%' . $wpdb->esc_like($search) . '%';
        }

        // Status filter
        if (!empty($status)) {
            $where_conditions[] = "s.status = %s";
            $query_params[] = $status;
        }

        // Quantity filter
        if (!empty($quantity)) {
            if ($quantity === 'zero') {
                $where_conditions[] = "s.quantity = 0";
            } elseif ($quantity === 'low') {
                $where_conditions[] = "s.quantity BETWEEN 1 AND 10";
            } elseif ($quantity === 'high') {
                $where_conditions[] = "s.quantity > 10";
            }
        }

        // Build WHERE clause
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }

        $count_query = "SELECT COUNT(*) FROM {$stocks_table} s 
               LEFT JOIN {$products_table} p ON s.fk_product_id = p.id 
               {$where_clause}";

        if (!empty($query_params)) {
            $total_items = $wpdb->get_var($wpdb->prepare($count_query, $query_params));
        } else {
            $total_items = $wpdb->get_var($count_query);
        }

        // Calculate pagination
        $total_pages = ceil($total_items / $per_page);
        $offset = ($page - 1) * $per_page;

        $main_query = "SELECT s.*, p.name as product_name 
              FROM {$stocks_table} s 
              LEFT JOIN {$products_table} p ON s.fk_product_id = p.id 
              {$where_clause} 
              ORDER BY s.created_at DESC 
              LIMIT %d OFFSET %d";

        // Add pagination parameters to query params
        $pagination_params = $query_params;
        $pagination_params[] = $per_page;
        $pagination_params[] = $offset;

        if (!empty($pagination_params)) {
            $stocks = $wpdb->get_results($wpdb->prepare($main_query, $pagination_params));
        } else {
            $stocks = $wpdb->get_results($main_query);
        }

        $response_data = [
            'stocks' => $stocks,
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

    /** Add stock */
    public function ajax_add_orpl_stock()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_add_stock')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;
        $stock_table = $wpdb->prefix . 'orpl_stocks';
        $accounting_table = $wpdb->prefix . 'orpl_accounting';

        $fk_product_id = isset($_POST['fk_product_id']) ? intval($_POST['fk_product_id']) : 0;
        $net_cost = isset($_POST['net_cost']) ? floatval($_POST['net_cost']) : 0.0;
        $sale_cost = isset($_POST['sale_cost']) ? floatval($_POST['sale_cost']) : 0.0;
        $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 0;

        $status = 'inStock';
        if (isset($_POST['status'])) {
            $allowed_statuses = ['inStock', 'outStock', 'lowStock'];
            $submitted_status = sanitize_text_field(wp_unslash($_POST['status']));
            if (in_array($submitted_status, $allowed_statuses)) {
                $status = $submitted_status;
            }
        }

        // Validate required fields
        if ($fk_product_id <= 0) {
            wp_send_json_error(__('Please select a valid product', 'obydullah-restaurant-pos-lite'));
        }
        if ($net_cost <= 0) {
            wp_send_json_error(__('Net cost must be greater than 0', 'obydullah-restaurant-pos-lite'));
        }
        if ($sale_cost <= 0) {
            wp_send_json_error(__('Sale price must be greater than 0', 'obydullah-restaurant-pos-lite'));
        }
        if ($quantity < 0) {
            wp_send_json_error(__('Quantity cannot be negative', 'obydullah-restaurant-pos-lite'));
        }

        // Calculate total investment
        $total_investment = $net_cost * $quantity;

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Insert stock record
            $result = $wpdb->insert($stock_table, [
                'fk_product_id' => $fk_product_id,
                'net_cost' => $net_cost,
                'sale_cost' => $sale_cost,
                'quantity' => $quantity,
                'status' => $status,
                'created_at' => current_time('mysql')
            ], ['%d', '%f', '%f', '%d', '%s', '%s']);

            if ($result === false) {
                throw new Exception(__('Failed to add stock', 'obydullah-restaurant-pos-lite'));
            }

            // Add accounting record
            $accounting_result = $wpdb->insert($accounting_table, [
                'in_amount' => $total_investment,
                'amount_payable' => $net_cost * $quantity,
                'description' => 'Stock In',
                'created_at' => current_time('mysql')
            ], ['%f', '%f', '%s', '%s']);

            if ($accounting_result === false) {
                throw new Exception(__('Failed to create accounting record', 'obydullah-restaurant-pos-lite'));
            }

            // Commit transaction
            $wpdb->query('COMMIT');

            // Clear stock caches
            $this->clear_stock_caches();

            wp_send_json_success(__('Stock added successfully', 'obydullah-restaurant-pos-lite'));

        } catch (Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            wp_send_json_error($e->getMessage());
        }
    }
    /** Delete stock */
    public function ajax_orpl_delete_stock()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_delete_stock')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'orpl_stocks';

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        if (!$id) {
            wp_send_json_error(__('Invalid stock ID', 'obydullah-restaurant-pos-lite'));
        }

        $result = $wpdb->delete($table, ['id' => $id], ['%d']);

        if ($result === false) {
            wp_send_json_error(__('Failed to delete stock', 'obydullah-restaurant-pos-lite'));
        }

        // Clear stock caches
        $this->clear_stock_caches();

        wp_send_json_success(__('Stock deleted successfully', 'obydullah-restaurant-pos-lite'));
    }

    /**
     * Clear all stock-related caches
     */
    private function clear_stock_caches()
    {
        global $wpdb;

        // Clear stock caches
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_orpl_stocks_%')
        );
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_orpl_stocks_%')
        );

        // Clear product caches
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_orpl_active_products_for_stocks%')
        );
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_orpl_active_products_for_stocks%')
        );
    }
}
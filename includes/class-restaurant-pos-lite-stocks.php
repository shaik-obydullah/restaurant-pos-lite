<?php
/**
 * Stock Management
 *
 * @package Restaurant_POS_Lite
 * @since   1.0.0
 */

if (!defined('ABSPATH'))
    exit;

class Restaurant_POS_Lite_Stocks
{
    public function __construct()
    {
        add_action('wp_ajax_add_stock', [$this, 'ajax_add_stock']);
        add_action('wp_ajax_get_stocks', [$this, 'ajax_get_stocks']);
        add_action('wp_ajax_delete_stock', [$this, 'ajax_delete_stock']);
        add_action('wp_ajax_get_products_for_stocks', [$this, 'ajax_get_products_for_stocks']);
    }

    /**
     * Render the stocks page
     */
    public function render_page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline" style="margin-bottom:20px;">Stock Management</h1>
            <hr class="wp-header-end">

            <!-- Stock Summary Cards -->
            <div class="stock-summary-cards"
                style="display:grid;grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));gap:20px;margin-bottom:30px;">
                <div class="summary-card"
                    style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.05);border-left:4px solid #0a7c38;">
                    <h3 style="margin:0 0 10px 0;font-size:14px;color:#666;">In Stock</h3>
                    <p class="summary-number" style="font-size:32px;font-weight:bold;margin:0;color:#0a7c38;">0</p>
                </div>
                <div class="summary-card"
                    style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.05);border-left:4px solid #d63638;">
                    <h3 style="margin:0 0 10px 0;font-size:14px;color:#666;">Out of Stock</h3>
                    <p class="summary-number" style="font-size:32px;font-weight:bold;margin:0;color:#d63638;">0</p>
                </div>
                <div class="summary-card"
                    style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.05);border-left:4px solid #ffb900;">
                    <h3 style="margin:0 0 10px 0;font-size:14px;color:#666;">Low Stock</h3>
                    <p class="summary-number" style="font-size:32px;font-weight:bold;margin:0;color:#ffb900;">0</p>
                </div>
                <div class="summary-card"
                    style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.05);border-left:4px solid #2271b1;">
                    <h3 style="margin:0 0 10px 0;font-size:14px;color:#666;">Total Products</h3>
                    <p class="summary-number" style="font-size:32px;font-weight:bold;margin:0;color:#2271b1;">0</p>
                </div>
            </div>

            <div id="col-container" class="wp-clearfix" style="display:flex;gap:24px;">
                <!-- Left: Add Stock Form -->
                <div id="col-left" style="flex:1; max-width:480px;">
                    <div class="col-wrap"
                        style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">
                        <h2 style="margin:0 0 20px 0;padding:0;font-size:16px;font-weight:600;color:#1d2327;">
                            Add Stock Entry
                        </h2>
                        <form id="add-stock-form" method="post">
                            <?php wp_nonce_field('add_stock', 'stock_nonce'); ?>

                            <div style="display:flex;flex-direction:column;gap:15px;">
                                <!-- Product Selection -->
                                <div class="form-field form-required">
                                    <label for="stock-product"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        Product <span style="color:#d63638;">*</span>
                                    </label>
                                    <select name="fk_product_id" id="stock-product" required
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;cursor:pointer;">
                                        <option value="">Select Product</option>
                                    </select>
                                </div>

                                <!-- Cost and Price -->
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                    <div class="form-field form-required">
                                        <label for="net-cost"
                                            style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                            Net Cost <span style="color:#d63638;">*</span>
                                        </label>
                                        <input name="net_cost" id="net-cost" type="number" step="0.01" min="0" value="0.00"
                                            required
                                            style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;">
                                    </div>

                                    <div class="form-field form-required">
                                        <label for="sale-cost"
                                            style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                            Sale Price <span style="color:#d63638;">*</span>
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
                                            Quantity <span style="color:#d63638;">*</span>
                                        </label>
                                        <input name="quantity" id="stock-quantity" type="number" min="0" value="0" required
                                            style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;">
                                    </div>

                                    <div class="form-field">
                                        <label for="stock-status"
                                            style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                            Status
                                        </label>
                                        <select name="status" id="stock-status"
                                            style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;cursor:pointer;">
                                            <option value="inStock">In Stock</option>
                                            <option value="outStock">Out of Stock</option>
                                            <option value="lowStock">Low Stock</option>
                                        </select>
                                    </div>
                                </div>

                                <!-- Profit Calculation -->
                                <div style="background:#f6f7f7;padding:12px;border-radius:4px;border:1px solid #e2e4e7;">
                                    <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px;">
                                        <span style="font-weight:600;">Profit Margin:</span>
                                        <span id="profit-margin" style="font-weight:600;color:#0a7c38;">0.00%</span>
                                    </div>
                                    <div style="display:flex;justify-content:space-between;font-size:12px;">
                                        <span style="font-weight:600;">Total Profit:</span>
                                        <span id="total-profit" style="font-weight:600;color:#0a7c38;">0.00</span>
                                    </div>
                                </div>
                            </div>

                            <div style="margin-top:20px;">
                                <button type="submit" id="submit-stock" class="button button-primary"
                                    style="width:100%;padding:8px 12px;font-size:13px;font-weight:500;">
                                    <span class="btn-text">Save Stock</span>
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
                                <input type="text" id="stock-search" placeholder="Search by product name..."
                                    style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;">
                            </div>

                            <!-- Status Filter -->
                            <div style="min-width:150px;">
                                <select id="status-filter"
                                    style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;">
                                    <option value="">All Status</option>
                                    <option value="inStock">In Stock</option>
                                    <option value="outStock">Out of Stock</option>
                                    <option value="lowStock">Low Stock</option>
                                </select>
                            </div>

                            <!-- Quantity Filter -->
                            <div style="min-width:150px;">
                                <select id="quantity-filter"
                                    style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;">
                                    <option value="">All Quantities</option>
                                    <option value="zero">Zero Quantity</option>
                                    <option value="low">Low Quantity (1-10)</option>
                                    <option value="high">High Quantity (10+)</option>
                                </select>
                            </div>

                            <!-- Refresh Button -->
                            <button id="refresh-stocks" class="button" style="padding:6px 12px;">
                                Refresh
                            </button>
                        </div>

                        <!-- Stocks Table -->
                        <table class="wp-list-table widefat fixed striped table-view-list">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Net Cost</th>
                                    <th>Sale Price</th>
                                    <th>Quantity</th>
                                    <th>Status</th>
                                    <th style="text-align:center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="stock-list">
                                <tr>
                                    <td colspan="6" class="loading-stocks" style="text-align:center;">
                                        <span class="spinner is-active"></span>
                                        Loading stocks...
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
                    let statusFilter = '';
                    let quantityFilter = '';

                    // Load products and stocks on page load
                    loadProducts();
                    loadStocks();

                    function loadProducts() {
                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'get_products_for_stocks',
                                nonce: '<?php echo esc_attr(wp_create_nonce("get_products_for_stocks")); ?>'
                            },
                            success: function (response) {
                                if (response.success) {
                                    let select = $('#stock-product');
                                    select.empty().append('<option value="">Select Product</option>');

                                    $.each(response.data, function (_, product) {
                                        select.append(
                                            $('<option>').val(product.id).text(product.name)
                                        );
                                    });
                                }
                            }
                        });
                    }

                    function loadStocks(page = 1) {
                        currentPage = page;

                        let tbody = $('#stock-list');
                        tbody.html('<tr><td colspan="6" class="loading-stocks" style="text-align:center;"><span class="spinner is-active"></span> Loading stocks...</td></tr>');

                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'get_stocks',
                                page: currentPage,
                                per_page: perPage,
                                search: searchTerm,
                                status: statusFilter,
                                quantity: quantityFilter,
                                nonce: '<?php echo esc_attr(wp_create_nonce("get_stocks")); ?>'
                            },
                            success: function (response) {
                                tbody.empty();
                                if (response.success) {
                                    if (!response.data.stocks.length) {
                                        tbody.append('<tr><td colspan="6" style="text-align:center;padding:20px;color:#666;">No stock entries found.</td></tr>');
                                        updateSummaryCards();
                                        updatePagination(response.data.pagination);
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
                                        let statusText = stock.status === 'inStock' ? 'In Stock' :
                                            stock.status === 'outStock' ? 'Out of Stock' : 'Low Stock';
                                        row.append($('<td>').append(
                                            $('<span>').addClass(statusClass).text(statusText)
                                        ));

                                        // Actions column - Only Delete button
                                        row.append($('<td style="text-align:center;">')
                                            .append('<button class="button button-small button-link-delete delete-stock">Delete</button>')
                                        );

                                        tbody.append(row);
                                    });

                                    updateSummaryCards(response.data.stocks);
                                    updatePagination(response.data.pagination);
                                } else {
                                    tbody.append('<tr><td colspan="6" style="color:red;text-align:center;">' + response.data + '</td></tr>');
                                }
                            },
                            error: function () {
                                $('#stock-list').html('<tr><td colspan="6" style="color:red;text-align:center;">Failed to load stocks.</td></tr>');
                            }
                        });
                    }

                    function updateSummaryCards(stocks = []) {
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
                    $('#stock-search').on('input', function () {
                        clearTimeout(searchTimeout);
                        searchTerm = $(this).val().trim();

                        searchTimeout = setTimeout(() => {
                            loadStocks(1);
                        }, 500);
                    });

                    // Status filter
                    $('#status-filter').on('change', function () {
                        statusFilter = $(this).val();
                        loadStocks(1);
                    });

                    // Quantity filter
                    $('#quantity-filter').on('change', function () {
                        quantityFilter = $(this).val();
                        loadStocks(1);
                    });

                    // Per page change
                    $('#per-page-select').on('change', function () {
                        perPage = parseInt($(this).val());
                        loadStocks(1);
                    });

                    // Refresh button
                    $('#refresh-stocks').on('click', function () {
                        loadStocks(currentPage);
                    });

                    // Pagination handlers
                    $('.first-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage > 1) loadStocks(1);
                    });

                    $('.prev-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage > 1) loadStocks(currentPage - 1);
                    });

                    $('.next-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage < totalPages) loadStocks(currentPage + 1);
                    });

                    $('.last-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage < totalPages) loadStocks(totalPages);
                    });

                    $('#current-page-selector').on('keypress', function (e) {
                        if (e.which === 13) { // Enter key
                            let page = parseInt($(this).val());
                            if (page >= 1 && page <= totalPages) {
                                loadStocks(page);
                            }
                        }
                    });

                    // Profit calculation
                    function calculateProfit() {
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
                    $('#net-cost, #sale-cost, #stock-quantity').on('input', calculateProfit);

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
                            alert('Please select a product');
                            return false;
                        }
                        if (net_cost <= 0) {
                            alert('Please enter a valid net cost');
                            return false;
                        }
                        if (sale_cost <= 0) {
                            alert('Please enter a valid sale price');
                            return false;
                        }
                        if (quantity < 0) {
                            alert('Please enter a valid quantity');
                            return false;
                        }

                        // Set submitting state
                        isSubmitting = true;
                        setButtonLoading(true);

                        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            action: 'add_stock',
                            fk_product_id: fk_product_id,
                            net_cost: net_cost,
                            sale_cost: sale_cost,
                            quantity: quantity,
                            status: status,
                            nonce: '<?php echo esc_attr(wp_create_nonce("add_stock")); ?>'
                        }, function (res) {
                            if (res.success) {
                                resetForm();
                                loadStocks(1); // Reload to first page
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

                    $(document).on('click', '.delete-stock', function () {
                        if (!confirm('Are you sure you want to delete this stock entry?')) return;

                        let button = $(this);
                        let originalText = button.text();
                        let id = $(this).closest('tr').data('stock-id');

                        // Disable button and show loading
                        button.prop('disabled', true).text('Deleting...');

                        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            action: 'delete_stock',
                            id: id,
                            nonce: '<?php echo esc_attr(wp_create_nonce("delete_stock")); ?>'
                        }, function (res) {
                            if (res.success) {
                                loadStocks(currentPage);
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
                        let button = $('#submit-stock');
                        let spinner = button.find('.spinner');
                        let btnText = button.find('.btn-text');

                        if (loading) {
                            button.prop('disabled', true).addClass('button-loading');
                            spinner.show();
                            btnText.text('Saving...');
                        } else {
                            button.prop('disabled', false).removeClass('button-loading');
                            spinner.hide();
                            btnText.text('Save Stock');
                        }
                    }

                    function resetForm() {
                        $('#stock-product').val('');
                        $('#net-cost').val('0.00');
                        $('#sale-cost').val('0.00');
                        $('#stock-quantity').val('0');
                        $('#stock-status').val('inStock');

                        calculateProfit();
                        // Ensure button is enabled
                        setButtonLoading(false);
                    }

                    // Initial profit calculation
                    calculateProfit();
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
        if (!wp_verify_nonce($nonce, 'get_products_for_stocks')) {
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

    /** Get all stocks with pagination and filters */
    public function ajax_get_stocks()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'get_stocks')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $stocks_table = $wpdb->prefix . 'pos_stocks';
        $products_table = $wpdb->prefix . 'pos_products';

        // Get parameters - sanitize inputs
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
        $search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
        $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';
        $quantity = isset($_GET['quantity']) ? sanitize_text_field(wp_unslash($_GET['quantity'])) : '';

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

        // Count total items
        $count_query = "SELECT COUNT(*) FROM $stocks_table s 
                   LEFT JOIN $products_table p ON s.fk_product_id = p.id 
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
        $main_query = "SELECT s.*, p.name as product_name 
                  FROM $stocks_table s 
                  LEFT JOIN $products_table p ON s.fk_product_id = p.id 
                  $where_clause 
                  ORDER BY s.created_at DESC 
                  LIMIT %d OFFSET %d";

        // Add pagination parameters to query params
        $query_params[] = $per_page;
        $query_params[] = $offset;

        // Execute main query
        if (!empty($query_params)) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $stocks = $wpdb->get_results($wpdb->prepare($main_query, $query_params));
        } else {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
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

        wp_send_json_success($response_data);
    }

    /** Add stock */
    public function ajax_add_stock()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'add_stock')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $stock_table = $wpdb->prefix . 'pos_stocks';
        $accounting_table = $wpdb->prefix . 'pos_accounting';

        // Unslash and sanitize input
        $fk_product_id = intval($_POST['fk_product_id'] ?? 0);
        $net_cost = floatval($_POST['net_cost'] ?? 0);
        $sale_cost = floatval($_POST['sale_cost'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 0);
        $status = in_array(wp_unslash($_POST['status'] ?? ''), ['inStock', 'outStock', 'lowStock']) ?
            sanitize_text_field(wp_unslash($_POST['status'])) : 'inStock';

        // Validate required fields
        if ($fk_product_id <= 0) {
            wp_send_json_error('Please select a valid product');
        }
        if ($net_cost <= 0) {
            wp_send_json_error('Net cost must be greater than 0');
        }
        if ($sale_cost <= 0) {
            wp_send_json_error('Sale price must be greater than 0');
        }
        if ($quantity < 0) {
            wp_send_json_error('Quantity cannot be negative');
        }

        // Calculate total investment
        $total_investment = $net_cost * $quantity;

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            // Insert stock record
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $result = $wpdb->insert($stock_table, [
                'fk_product_id' => $fk_product_id,
                'net_cost' => $net_cost,
                'sale_cost' => $sale_cost,
                'quantity' => $quantity,
                'status' => $status
            ], ['%d', '%f', '%f', '%d', '%s']);

            if ($result === false) {
                throw new Exception('Failed to add stock');
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $accounting_result = $wpdb->insert($accounting_table, [
                'in_amount' => $total_investment,
                'amount_payable' => $net_cost * $quantity,
                'description' => 'Stock In',
                'created_at' => current_time('mysql')
            ], ['%f', '%f', '%s', '%s']);

            if ($accounting_result === false) {
                throw new Exception('Failed to create accounting record');
            }

            // Commit transaction
            $wpdb->query('COMMIT');

            wp_send_json_success('Stock added successfully');

        } catch (Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            wp_send_json_error($e->getMessage());
        }
    }

    /** Delete stock */
    public function ajax_delete_stock()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'delete_stock')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'pos_stocks';
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error('Invalid stock ID');
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->delete($table, ['id' => $id], ['%d']);

        if ($result === false) {
            wp_send_json_error('Failed to delete stock');
        }

        wp_send_json_success('Stock deleted successfully');
    }
}
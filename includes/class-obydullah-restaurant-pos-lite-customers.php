<?php
/**
 * Customer Management
 *
 * @package Obydullah_Restaurant_POS_Lite
 * @since   1.0.0
 */
if (!defined('ABSPATH')) {
    exit;
}

class Obydullah_Restaurant_POS_Lite_Customers
{
    const CACHE_GROUP = 'orpl_customers';
    const CACHE_EXPIRATION = 15 * MINUTE_IN_SECONDS;

    private $customers_table;

    public function __construct()
    {
        global $wpdb;
        $this->customers_table = $wpdb->prefix . 'orpl_customers';

        add_action('wp_ajax_orpl_add_customer', [$this, 'ajax_add_orpl_customer']);
        add_action('wp_ajax_orpl_get_customers', [$this, 'ajax_get_orpl_customers']);
        add_action('wp_ajax_orpl_edit_customer', [$this, 'ajax_edit_orpl_customer']);
        add_action('wp_ajax_orpl_delete_customer', [$this, 'ajax_delete_orpl_customer']);
    }

    /**
     * Render the customers page
     */
    public function render_page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline" style="margin-bottom:20px;">
                <?php esc_html_e('Customers', 'obydullah-restaurant-pos-lite'); ?>
            </h1>
            <hr class="wp-header-end">

            <!-- Customer Summary Cards -->
            <div class="customer-summary-cards"
                style="display:grid;grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));gap:20px;margin-bottom:30px;">
                <div class="summary-card"
                    style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.05);border-left:4px solid #0a7c38;">
                    <h3 style="margin:0 0 10px 0;font-size:14px;color:#666;">
                        <?php esc_html_e('Active Customers', 'obydullah-restaurant-pos-lite'); ?>
                    </h3>
                    <p class="summary-number" style="font-size:32px;font-weight:bold;margin:0;color:#0a7c38;">0</p>
                </div>
                <div class="summary-card"
                    style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.05);border-left:4px solid #d63638;">
                    <h3 style="margin:0 0 10px 0;font-size:14px;color:#666;">
                        <?php esc_html_e('Inactive Customers', 'obydullah-restaurant-pos-lite'); ?>
                    </h3>
                    <p class="summary-number" style="font-size:32px;font-weight:bold;margin:0;color:#d63638;">0</p>
                </div>
                <div class="summary-card"
                    style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.05);border-left:4px solid #2271b1;">
                    <h3 style="margin:0 0 10px 0;font-size:14px;color:#666;">
                        <?php esc_html_e('Total Customers', 'obydullah-restaurant-pos-lite'); ?>
                    </h3>
                    <p class="summary-number" style="font-size:32px;font-weight:bold;margin:0;color:#2271b1;">0</p>
                </div>
            </div>

            <div id="col-container" class="wp-clearfix" style="display:flex;gap:24px;">
                <!-- Left: Add/Edit Customer Form -->
                <div id="col-left" style="flex:1; max-width:480px;">
                    <div class="col-wrap"
                        style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">
                        <h2 id="form-title" style="margin:0 0 20px 0;padding:0;font-size:16px;font-weight:600;color:#1d2327;">
                            <?php esc_html_e('Add New Customer', 'obydullah-restaurant-pos-lite'); ?>
                        </h2>
                        <form id="add-customer-form" method="post">
                            <?php wp_nonce_field('orpl_add_customer', 'customer_nonce'); ?>
                            <input type="hidden" id="customer-id" name="id" value="">

                            <div style="display:flex;flex-direction:column;gap:15px;">
                                <!-- Name and Email -->
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                    <div class="form-field form-required">
                                        <label for="customer-name"
                                            style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                            <?php esc_html_e('Name', 'obydullah-restaurant-pos-lite'); ?> <span
                                                style="color:#d63638;">*</span>
                                        </label>
                                        <input name="name" id="customer-name" type="text" value="" required
                                            style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;">
                                    </div>

                                    <div class="form-field form-required">
                                        <label for="customer-email"
                                            style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                            <?php esc_html_e('Email', 'obydullah-restaurant-pos-lite'); ?> <span
                                                style="color:#d63638;">*</span>
                                        </label>
                                        <input name="email" id="customer-email" type="email" value="" required
                                            style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;">
                                    </div>
                                </div>

                                <!-- Mobile -->
                                <div class="form-field">
                                    <label for="customer-mobile"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        <?php esc_html_e('Mobile', 'obydullah-restaurant-pos-lite'); ?>
                                    </label>
                                    <input name="mobile" id="customer-mobile" type="text" value=""
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;">
                                </div>

                                <!-- Address -->
                                <div class="form-field">
                                    <label for="customer-address"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        <?php esc_html_e('Address', 'obydullah-restaurant-pos-lite'); ?>
                                    </label>
                                    <textarea name="address" id="customer-address" rows="2"
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;resize:vertical;"
                                        placeholder="<?php esc_attr_e('Customer address...', 'obydullah-restaurant-pos-lite'); ?>"></textarea>
                                </div>

                                <!-- Status -->
                                <div class="form-field">
                                    <label for="customer-status"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        <?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?>
                                    </label>
                                    <select name="status" id="customer-status"
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
                                <button type="submit" id="submit-customer" class="button button-primary"
                                    style="flex:1;padding:8px 12px;font-size:13px;font-weight:500;">
                                    <span
                                        class="btn-text"><?php esc_html_e('Save Customer', 'obydullah-restaurant-pos-lite'); ?></span>
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

                <!-- Right: Customers Table -->
                <div id="col-right" style="flex:2;">
                    <div class="col-wrap"
                        style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">

                        <!-- Search and Filter Section -->
                        <div style="margin-bottom:20px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                            <!-- Search Input -->
                            <div style="flex:1;min-width:200px;">
                                <input type="text" id="customer-search"
                                    placeholder="<?php esc_attr_e('Search by name or mobile...', 'obydullah-restaurant-pos-lite'); ?>"
                                    style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;">
                            </div>

                            <!-- Status Filter -->
                            <div style="min-width:150px;">
                                <select id="status-filter"
                                    style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;">
                                    <option value=""><?php esc_html_e('All Status', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                    <option value="active"><?php esc_html_e('Active', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                    <option value="inactive"><?php esc_html_e('Inactive', 'obydullah-restaurant-pos-lite'); ?>
                                    </option>
                                </select>
                            </div>

                            <!-- Refresh Button -->
                            <button id="refresh-customers" class="button" style="padding:6px 12px;">
                                <?php esc_html_e('Refresh', 'obydullah-restaurant-pos-lite'); ?>
                            </button>
                        </div>

                        <!-- Customers Table -->
                        <table class="wp-list-table widefat fixed striped table-view-list">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Name', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Email', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Mobile', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Address', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Status', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th style="text-align:center;">
                                        <?php esc_html_e('Actions', 'obydullah-restaurant-pos-lite'); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="customer-list">
                                <tr>
                                    <td colspan="6" class="loading-customers" style="text-align:center;">
                                        <span class="spinner is-active"></span>
                                        <?php esc_html_e('Loading customers...', 'obydullah-restaurant-pos-lite'); ?>
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
                    let statusFilter = '';

                    // Load customers on page load
                    loadORPLCustomers();

                    function loadORPLCustomers(page = 1) {
                        currentPage = page;

                        let tbody = $('#customer-list');
                        tbody.html('<tr><td colspan="6" class="loading-customers" style="text-align:center;"><span class="spinner is-active"></span> <?php echo esc_js(__('Loading customers...', 'obydullah-restaurant-pos-lite')); ?></td></tr>');

                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'orpl_get_customers',
                                page: currentPage,
                                per_page: perPage,
                                search: searchTerm,
                                status: statusFilter,
                                nonce: '<?php echo esc_attr(wp_create_nonce("orpl_get_customers")); ?>'
                            },
                            success: function (response) {
                                tbody.empty();
                                if (response.success) {
                                    if (!response.data.customers.length) {
                                        tbody.append('<tr><td colspan="6" style="text-align:center;padding:20px;color:#666;"><?php echo esc_js(__('No customers found.', 'obydullah-restaurant-pos-lite')); ?></td></tr>');
                                        updateSummaryORPLCards();
                                        updateORPLPagination(response.data.pagination);
                                        return;
                                    }

                                    $.each(response.data.customers, function (_, customer) {
                                        let row = $('<tr>').attr('data-customer-id', customer.id);

                                        // Name column
                                        row.append($('<td>').text(customer.name));

                                        // Email column
                                        row.append($('<td>').text(customer.email));

                                        // Mobile column
                                        row.append($('<td>').text(customer.mobile || '-'));

                                        // Address column
                                        row.append($('<td>').text(customer.address || '-'));

                                        // Status column
                                        let statusClass = customer.status === 'active' ? 'status-active' : 'status-inactive';
                                        let statusText = customer.status === 'active' ? '<?php echo esc_js(__('Active', 'obydullah-restaurant-pos-lite')); ?>' : '<?php echo esc_js(__('Inactive', 'obydullah-restaurant-pos-lite')); ?>';
                                        row.append($('<td>').append(
                                            $('<span>').addClass(statusClass).text(statusText)
                                        ));

                                        // Actions column
                                        row.append($('<td style="text-align:center;">')
                                            .append('<button class="button button-small edit-customer" style="margin-right:5px;"><?php echo esc_js(__('Edit', 'obydullah-restaurant-pos-lite')); ?></button>')
                                            .append('<button class="button button-small button-link-delete delete-customer"><?php echo esc_js(__('Delete', 'obydullah-restaurant-pos-lite')); ?></button>')
                                        );

                                        tbody.append(row);
                                    });

                                    updateSummaryORPLCards(response.data.customers);
                                    updateORPLPagination(response.data.pagination);
                                } else {
                                    tbody.append('<tr><td colspan="6" style="color:red;text-align:center;">' + response.data + '</td></tr>');
                                }
                            },
                            error: function () {
                                $('#customer-list').html('<tr><td colspan="6" style="color:red;text-align:center;"><?php echo esc_js(__('Failed to load customers.', 'obydullah-restaurant-pos-lite')); ?></td></tr>');
                            }
                        });
                    }

                    function updateSummaryORPLCards(customers = []) {
                        let active = 0, inactive = 0;

                        if (customers.length > 0) {
                            customers.forEach(customer => {
                                if (customer.status === 'active') active++;
                                else inactive++;
                            });
                        }

                        $('.summary-card:nth-child(1) .summary-number').text(active);
                        $('.summary-card:nth-child(2) .summary-number').text(inactive);
                        $('.summary-card:nth-child(3) .summary-number').text(customers.length);
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
                    $('#customer-search').on('input', function () {
                        clearTimeout(searchTimeout);
                        searchTerm = $(this).val().trim();

                        searchTimeout = setTimeout(() => {
                            loadORPLCustomers(1);
                        }, 500);
                    });

                    // Status filter
                    $('#status-filter').on('change', function () {
                        statusFilter = $(this).val();
                        loadORPLCustomers(1);
                    });

                    // Per page change
                    $('#per-page-select').on('change', function () {
                        perPage = parseInt($(this).val());
                        loadORPLCustomers(1);
                    });

                    // Refresh button
                    $('#refresh-customers').on('click', function () {
                        loadORPLCustomers(currentPage);
                    });

                    // Pagination handlers
                    $('.first-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage > 1) loadORPLCustomers(1);
                    });

                    $('.prev-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage > 1) loadORPLCustomers(currentPage - 1);
                    });

                    $('.next-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage < totalPages) loadORPLCustomers(currentPage + 1);
                    });

                    $('.last-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage < totalPages) loadORPLCustomers(totalPages);
                    });

                    $('#current-page-selector').on('keypress', function (e) {
                        if (e.which === 13) { // Enter key
                            let page = parseInt($(this).val());
                            if (page >= 1 && page <= totalPages) {
                                loadORPLCustomers(page);
                            }
                        }
                    });

                    $('#add-customer-form').on('submit', function (e) {
                        e.preventDefault();

                        // Prevent double submission
                        if (isSubmitting) {
                            return false;
                        }

                        let id = $('#customer-id').val();
                        let action = id ? 'orpl_edit_customer' : 'orpl_add_customer';
                        let name = $('#customer-name').val().trim();
                        let email = $('#customer-email').val().trim();
                        let mobile = $('#customer-mobile').val().trim();
                        let address = $('#customer-address').val().trim();
                        let status = $('#customer-status').val();

                        if (!name) {
                            alert('<?php echo esc_js(__('Please enter customer name', 'obydullah-restaurant-pos-lite')); ?>');
                            return false;
                        }
                        if (!email) {
                            alert('<?php echo esc_js(__('Please enter customer email', 'obydullah-restaurant-pos-lite')); ?>');
                            return false;
                        }
                        if (!isValidEmail(email)) {
                            alert('<?php echo esc_js(__('Please enter a valid email address', 'obydullah-restaurant-pos-lite')); ?>');
                            return false;
                        }

                        // Set submitting state
                        isSubmitting = true;
                        setButtonLoading(true);

                        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            action: action,
                            id: id,
                            name: name,
                            email: email,
                            mobile: mobile,
                            address: address,
                            status: status,
                            nonce: '<?php echo esc_attr(wp_create_nonce("orpl_add_customer")); ?>'
                        }, function (res) {
                            if (res.success) {
                                resetForm();
                                loadORPLCustomers(currentPage);
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

                    $('#cancel-edit').on('click', function () {
                        resetForm();
                    });

                    $(document).on('click', '.edit-customer', function () {
                        let row = $(this).closest('tr');
                        let customerId = row.data('customer-id');

                        // Get customer details
                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'orpl_get_customers',
                                id: customerId,
                                nonce: '<?php echo esc_attr(wp_create_nonce("orpl_get_customers")); ?>'
                            },
                            success: function (response) {
                                if (response.success && response.data.customers.length > 0) {
                                    let customer = response.data.customers.find(c => c.id == customerId);
                                    if (customer) {
                                        $('#customer-id').val(customer.id);
                                        $('#customer-name').val(customer.name);
                                        $('#customer-email').val(customer.email);
                                        $('#customer-mobile').val(customer.mobile || '');
                                        $('#customer-address').val(customer.address || '');
                                        $('#customer-status').val(customer.status);

                                        $('#form-title').text('<?php echo esc_js(__('Edit Customer', 'obydullah-restaurant-pos-lite')); ?>');
                                        $('#submit-customer').find('.btn-text').text('<?php echo esc_js(__('Update Customer', 'obydullah-restaurant-pos-lite')); ?>');
                                        $('#cancel-edit').show();
                                    }
                                }
                            }
                        });
                    });

                    $(document).on('click', '.delete-customer', function () {
                        if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this customer?', 'obydullah-restaurant-pos-lite')); ?>')) return;

                        let button = $(this);
                        let originalText = button.text();
                        let id = $(this).closest('tr').data('customer-id');

                        // Disable button and show loading
                        button.prop('disabled', true).text('<?php echo esc_js(__('Deleting...', 'obydullah-restaurant-pos-lite')); ?>');

                        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            action: 'orpl_delete_customer',
                            id: id,
                            nonce: '<?php echo esc_attr(wp_create_nonce("orpl_delete_customer")); ?>'
                        }, function (res) {
                            if (res.success) {
                                loadORPLCustomers(currentPage);
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
                        let button = $('#submit-customer');
                        let spinner = button.find('.spinner');
                        let btnText = button.find('.btn-text');

                        if (loading) {
                            button.prop('disabled', true).addClass('button-loading');
                            spinner.show();
                            btnText.text(button.hasClass('button-loading') ? '<?php echo esc_js(__('Saving...', 'obydullah-restaurant-pos-lite')); ?>' : '<?php echo esc_js(__('Updating...', 'obydullah-restaurant-pos-lite')); ?>');
                        } else {
                            button.prop('disabled', false).removeClass('button-loading');
                            spinner.hide();
                            btnText.text(button.find('.btn-text').text().includes('Update') ? '<?php echo esc_js(__('Update Customer', 'obydullah-restaurant-pos-lite')); ?>' : '<?php echo esc_js(__('Save Customer', 'obydullah-restaurant-pos-lite')); ?>');
                        }
                    }

                    function isValidEmail(email) {
                        var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                        return emailRegex.test(email);
                    }

                    function resetForm() {
                        $('#customer-id').val('');
                        $('#customer-name').val('');
                        $('#customer-email').val('');
                        $('#customer-mobile').val('');
                        $('#customer-address').val('');
                        $('#customer-status').val('active');
                        $('#form-title').text('<?php echo esc_js(__('Add New Customer', 'obydullah-restaurant-pos-lite')); ?>');
                        $('#submit-customer').find('.btn-text').text('<?php echo esc_js(__('Save Customer', 'obydullah-restaurant-pos-lite')); ?>');
                        $('#cancel-edit').hide();
                        $('#customer-name').focus();

                        // Ensure button is enabled
                        setButtonLoading(false);
                    }
                });
            </script>
        </div>
        <?php
    }

    /** Get customers with pagination and filters */
    public function ajax_get_orpl_customers()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_get_customers')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        // Get parameters - sanitize inputs
        $customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
        $search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
        $status = isset($_GET['status']) ? sanitize_text_field(wp_unslash($_GET['status'])) : '';

        if ($customer_id > 0) {
            // Single customer request - cache individual customer
            $cache_key = 'orpl_customer_' . $customer_id;
            $customers = wp_cache_get($cache_key, self::CACHE_GROUP);

            if (false === $customers) {
                global $wpdb;

                $customers = $wpdb->get_results($wpdb->prepare(
                    "SELECT id, name, email, mobile, address, status, created_at 
                FROM {$this->customers_table} 
                WHERE id = %d",
                    $customer_id
                ));

                wp_cache_set($cache_key, $customers, self::CACHE_GROUP, self::CACHE_EXPIRATION);
            }

            $response_data = [
                'customers' => $customers,
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => 1,
                    'total_items' => count($customers),
                    'total_pages' => 1
                ]
            ];
        } else {
            // Multiple customers with pagination - cache based on filters
            $cache_key = 'orpl_customers_' . md5(serialize([$page, $per_page, $search, $status]));
            $cached_data = wp_cache_get($cache_key, self::CACHE_GROUP);

            if (false !== $cached_data) {
                wp_send_json_success($cached_data);
            }

            global $wpdb;

            $where_conditions = [];
            $query_params = [];

            // Build WHERE conditions
            if (!empty($search)) {
                $where_conditions[] = "(name LIKE %s OR mobile LIKE %s)";
                $search_like = '%' . $wpdb->esc_like($search) . '%';
                $query_params[] = $search_like;
                $query_params[] = $search_like;
            }

            if (!empty($status)) {
                $where_conditions[] = "status = %s";
                $query_params[] = $status;
            }

            // Build the WHERE clause
            $where_clause = '';
            if (!empty($where_conditions)) {
                $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
            }

            $count_query = "SELECT COUNT(*) FROM {$this->customers_table} {$where_clause}";
            if (!empty($query_params)) {
                $total_items = $wpdb->get_var($wpdb->prepare($count_query, $query_params));
            } else {
                $total_items = $wpdb->get_var($count_query);
            }

            // Calculate pagination
            $total_pages = ceil($total_items / $per_page);
            $offset = ($page - 1) * $per_page;

            $main_query = "SELECT id, name, email, mobile, address, status, created_at 
                  FROM {$this->customers_table} 
                  {$where_clause} 
                  ORDER BY created_at DESC 
                  LIMIT %d OFFSET %d";

            // Add pagination parameters
            $pagination_params = $query_params;
            $pagination_params[] = $per_page;
            $pagination_params[] = $offset;

            // Execute main query
            if (!empty($pagination_params)) {
                $customers = $wpdb->get_results($wpdb->prepare($main_query, $pagination_params));
            } else {
                $customers = $wpdb->get_results($main_query);
            }

            $response_data = [
                'customers' => $customers,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total_items' => $total_items,
                    'total_pages' => $total_pages
                ]
            ];

            // Cache for 5 minutes
            wp_cache_set($cache_key, $response_data, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS);
        }

        wp_send_json_success($response_data);
    }

    /** Add customer */
    public function ajax_add_orpl_customer()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_add_customer')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        // Unslash and sanitize input
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $mobile = sanitize_text_field(wp_unslash($_POST['mobile'] ?? ''));
        $address = sanitize_text_field(wp_unslash($_POST['address'] ?? ''));
        $status = in_array(wp_unslash($_POST['status'] ?? ''), ['active', 'inactive']) ?
            sanitize_text_field(wp_unslash($_POST['status'])) : 'active';

        // Validate required fields
        if (empty($name)) {
            wp_send_json_error(__('Customer name is required', 'obydullah-restaurant-pos-lite'));
        }
        if (empty($email) || !is_email($email)) {
            wp_send_json_error(__('Valid email address is required', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;

        // Check if email already exists
        $cache_key = 'orpl_customer_email_' . md5($email);
        $existing = wp_cache_get($cache_key, self::CACHE_GROUP);

        if (false === $existing) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$this->customers_table} WHERE email = %s",
                $email
            ));
            wp_cache_set($cache_key, $existing, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS);
        }

        if ($existing) {
            wp_send_json_error(__('Customer email already exists', 'obydullah-restaurant-pos-lite'));
        }

        $result = $wpdb->insert($this->customers_table, [
            'name' => $name,
            'email' => $email,
            'mobile' => $mobile,
            'address' => $address,
            'status' => $status,
            'created_at' => current_time('mysql')
        ], ['%s', '%s', '%s', '%s', '%s', '%s']);

        if ($result === false) {
            wp_send_json_error(__('Failed to add customer', 'obydullah-restaurant-pos-lite'));
        }

        // Clear relevant caches
        $this->clear_customer_caches();

        wp_send_json_success(__('Customer added successfully', 'obydullah-restaurant-pos-lite'));
    }
    /** Edit customer */
    public function ajax_edit_orpl_customer()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_add_customer')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        $id = intval($_POST['id'] ?? 0);
        // Unslash and sanitize input
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $mobile = sanitize_text_field(wp_unslash($_POST['mobile'] ?? ''));
        $address = sanitize_text_field(wp_unslash($_POST['address'] ?? ''));
        $status = in_array(wp_unslash($_POST['status'] ?? ''), ['active', 'inactive']) ?
            sanitize_text_field(wp_unslash($_POST['status'])) : 'active';

        if (!$id || empty($name) || empty($email) || !is_email($email)) {
            wp_send_json_error(__('Invalid data provided', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;

        // Check if email already exists (excluding current customer)
        $cache_key = 'orpl_customer_email_' . md5($email . '_' . $id);
        $existing = wp_cache_get($cache_key, self::CACHE_GROUP);

        if (false === $existing) {
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$this->customers_table} WHERE email = %s AND id != %d",
                $email,
                $id
            ));
            wp_cache_set($cache_key, $existing, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS);
        }

        if ($existing) {
            wp_send_json_error(__('Customer email already exists', 'obydullah-restaurant-pos-lite'));
        }

        $result = $wpdb->update($this->customers_table, [
            'name' => $name,
            'email' => $email,
            'mobile' => $mobile,
            'address' => $address,
            'status' => $status
        ], ['id' => $id], ['%s', '%s', '%s', '%s', '%s'], ['%d']);

        if ($result === false) {
            wp_send_json_error(__('Failed to update customer', 'obydullah-restaurant-pos-lite'));
        }

        // Clear relevant caches
        $this->clear_customer_caches();
        wp_cache_delete('orpl_customer_' . $id, self::CACHE_GROUP);

        wp_send_json_success(__('Customer updated successfully', 'obydullah-restaurant-pos-lite'));
    }

    /** Delete customer */
    public function ajax_delete_orpl_customer()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_POST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'orpl_delete_customer')) {
            wp_send_json_error(__('Security verification failed', 'obydullah-restaurant-pos-lite'));
        }

        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error(__('Invalid customer ID', 'obydullah-restaurant-pos-lite'));
        }

        global $wpdb;

        $customer = $wpdb->get_row($wpdb->prepare(
            "SELECT email FROM {$this->customers_table} WHERE id = %d",
            $id
        ));

        $result = $wpdb->delete($this->customers_table, ['id' => $id], ['%d']);

        if ($result === false) {
            wp_send_json_error(__('Failed to delete customer', 'obydullah-restaurant-pos-lite'));
        }

        // Clear relevant caches
        $this->clear_customer_caches();
        if ($customer) {
            wp_cache_delete('orpl_customer_email_' . md5($customer->email), self::CACHE_GROUP);
            wp_cache_delete('orpl_customer_' . $id, self::CACHE_GROUP);
        }

        wp_send_json_success(__('Customer deleted successfully', 'obydullah-restaurant-pos-lite'));
    }

    /**
     * Clear all customer-related caches
     */
    private function clear_customer_caches()
    {
        global $wpdb;

        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_orpl_customers_%')
        );
        $wpdb->query(
            $wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_orpl_customers_%')
        );

        wp_cache_delete('orpl_customer_email_', self::CACHE_GROUP);
    }
}
<?php
/**
 * Customer Management
 *
 * @package Restaurant_POS_Lite
 * @since   1.0.0
 */
if (!defined('ABSPATH'))
    exit;

class Restaurant_POS_Lite_Customers
{
    public function __construct()
    {
        add_action('wp_ajax_add_customer', [$this, 'ajax_add_customer']);
        add_action('wp_ajax_get_customers', [$this, 'ajax_get_customers']);
        add_action('wp_ajax_edit_customer', [$this, 'ajax_edit_customer']);
        add_action('wp_ajax_delete_customer', [$this, 'ajax_delete_customer']);
    }

    /**
     * Render the customers page
     */
    public function render_page()
    {
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline" style="margin-bottom:20px;">Customers</h1>
            <hr class="wp-header-end">

            <!-- Customer Summary Cards -->
            <div class="customer-summary-cards"
                style="display:grid;grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));gap:20px;margin-bottom:30px;">
                <div class="summary-card"
                    style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.05);border-left:4px solid #0a7c38;">
                    <h3 style="margin:0 0 10px 0;font-size:14px;color:#666;">Active Customers</h3>
                    <p class="summary-number" style="font-size:32px;font-weight:bold;margin:0;color:#0a7c38;">0</p>
                </div>
                <div class="summary-card"
                    style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.05);border-left:4px solid #d63638;">
                    <h3 style="margin:0 0 10px 0;font-size:14px;color:#666;">Inactive Customers</h3>
                    <p class="summary-number" style="font-size:32px;font-weight:bold;margin:0;color:#d63638;">0</p>
                </div>
                <div class="summary-card"
                    style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.05);border-left:4px solid #2271b1;">
                    <h3 style="margin:0 0 10px 0;font-size:14px;color:#666;">Total Customers</h3>
                    <p class="summary-number" style="font-size:32px;font-weight:bold;margin:0;color:#2271b1;">0</p>
                </div>
                <div class="summary-card"
                    style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.05);border-left:4px solid #ffb900;">
                    <h3 style="margin:0 0 10px 0;font-size:14px;color:#666;">Total Balance</h3>
                    <p class="summary-number" style="font-size:32px;font-weight:bold;margin:0;color:#ffb900;">0.00</p>
                </div>
            </div>

            <div id="col-container" class="wp-clearfix" style="display:flex;gap:24px;">
                <!-- Left: Add/Edit Customer Form -->
                <div id="col-left" style="flex:1; max-width:480px;">
                    <div class="col-wrap"
                        style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">
                        <h2 id="form-title" style="margin:0 0 20px 0;padding:0;font-size:16px;font-weight:600;color:#1d2327;">
                            Add New Customer
                        </h2>
                        <form id="add-customer-form" method="post">
                            <?php wp_nonce_field('add_customer', 'customer_nonce'); ?>
                            <input type="hidden" id="customer-id" name="id" value="">

                            <div style="display:flex;flex-direction:column;gap:15px;">
                                <!-- Name and Email -->
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                                    <div class="form-field form-required">
                                        <label for="customer-name"
                                            style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                            Name <span style="color:#d63638;">*</span>
                                        </label>
                                        <input name="name" id="customer-name" type="text" value="" required
                                            style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;">
                                    </div>

                                    <div class="form-field form-required">
                                        <label for="customer-email"
                                            style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                            Email <span style="color:#d63638;">*</span>
                                        </label>
                                        <input name="email" id="customer-email" type="email" value="" required
                                            style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;">
                                    </div>
                                </div>

                                <!-- Mobile -->
                                <div class="form-field">
                                    <label for="customer-mobile"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        Mobile
                                    </label>
                                    <input name="mobile" id="customer-mobile" type="text" value=""
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;">
                                </div>

                                <!-- Address -->
                                <div class="form-field">
                                    <label for="customer-address"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        Address
                                    </label>
                                    <textarea name="address" id="customer-address" rows="2"
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;resize:vertical;"
                                        placeholder="Customer address..."></textarea>
                                </div>

                                <!-- Status -->
                                <div class="form-field">
                                    <label for="customer-status"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        Status
                                    </label>
                                    <select name="status" id="customer-status"
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;cursor:pointer;">
                                        <option value="active">Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>

                            <div style="margin-top:20px;display:flex;gap:10px;">
                                <button type="submit" id="submit-customer" class="button button-primary"
                                    style="flex:1;padding:8px 12px;font-size:13px;font-weight:500;">
                                    <span class="btn-text">Save Customer</span>
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

                <!-- Right: Customers Table -->
                <div id="col-right" style="flex:2;">
                    <div class="col-wrap"
                        style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">

                        <!-- Search and Filter Section -->
                        <div style="margin-bottom:20px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                            <!-- Search Input -->
                            <div style="flex:1;min-width:200px;">
                                <input type="text" id="customer-search" placeholder="Search by name or mobile..."
                                    style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;">
                            </div>

                            <!-- Status Filter -->
                            <div style="min-width:150px;">
                                <select id="status-filter"
                                    style="width:100%;padding:8px 12px;border:1px solid #ddd;border-radius:4px;font-size:13px;">
                                    <option value="">All Status</option>
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>

                            <!-- Refresh Button -->
                            <button id="refresh-customers" class="button" style="padding:6px 12px;">
                                Refresh
                            </button>
                        </div>

                        <!-- Customers Table -->
                        <table class="wp-list-table widefat fixed striped table-view-list">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Mobile</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                    <th style="text-align:center;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="customer-list">
                                <tr>
                                    <td colspan="6" class="loading-customers" style="text-align:center;">
                                        <span class="spinner is-active"></span>
                                        Loading customers...
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

                    // Load customers on page load
                    loadCustomers();

                    function loadCustomers(page = 1) {
                        currentPage = page;

                        let tbody = $('#customer-list');
                        tbody.html('<tr><td colspan="6" class="loading-customers" style="text-align:center;"><span class="spinner is-active"></span> Loading customers...</td></tr>');

                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'get_customers',
                                page: currentPage,
                                per_page: perPage,
                                search: searchTerm,
                                status: statusFilter,
                                nonce: '<?php echo esc_attr(wp_create_nonce("get_customers")); ?>'
                            },
                            success: function (response) {
                                tbody.empty();
                                if (response.success) {
                                    if (!response.data.customers.length) {
                                        tbody.append('<tr><td colspan="6" style="text-align:center;padding:20px;color:#666;">No customers found.</td></tr>');
                                        updateSummaryCards();
                                        updatePagination(response.data.pagination);
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

                                        // Balance column
                                        let balance = parseFloat(customer.balance);
                                        let balanceClass = balance > 0 ? 'balance-positive' :
                                            balance < 0 ? 'balance-negative' : 'balance-zero';
                                        row.append($('<td>').append(
                                            $('<span>').addClass(balanceClass).text(balance.toFixed(2))
                                        ));

                                        // Status column
                                        let statusClass = customer.status === 'active' ? 'status-active' : 'status-inactive';
                                        let statusText = customer.status === 'active' ? 'Active' : 'Inactive';
                                        row.append($('<td>').append(
                                            $('<span>').addClass(statusClass).text(statusText)
                                        ));

                                        // Actions column
                                        row.append($('<td style="text-align:center;">')
                                            .append('<button class="button button-small edit-customer" style="margin-right:5px;">Edit</button>')
                                            .append('<button class="button button-small button-link-delete delete-customer">Delete</button>')
                                        );

                                        tbody.append(row);
                                    });

                                    updateSummaryCards(response.data.customers);
                                    updatePagination(response.data.pagination);
                                } else {
                                    tbody.append('<tr><td colspan="6" style="color:red;text-align:center;">' + response.data + '</td></tr>');
                                }
                            },
                            error: function () {
                                $('#customer-list').html('<tr><td colspan="6" style="color:red;text-align:center;">Failed to load customers.</td></tr>');
                            }
                        });
                    }

                    function updateSummaryCards(customers = []) {
                        let active = 0, inactive = 0, totalBalance = 0;

                        if (customers.length > 0) {
                            customers.forEach(customer => {
                                if (customer.status === 'active') active++;
                                else inactive++;

                                totalBalance += parseFloat(customer.balance);
                            });
                        }

                        $('.summary-card:nth-child(1) .summary-number').text(active);
                        $('.summary-card:nth-child(2) .summary-number').text(inactive);
                        $('.summary-card:nth-child(3) .summary-number').text(customers.length);
                        $('.summary-card:nth-child(4) .summary-number').text(totalBalance.toFixed(2));
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
                    $('#customer-search').on('input', function () {
                        clearTimeout(searchTimeout);
                        searchTerm = $(this).val().trim();

                        searchTimeout = setTimeout(() => {
                            loadCustomers(1);
                        }, 500);
                    });

                    // Status filter
                    $('#status-filter').on('change', function () {
                        statusFilter = $(this).val();
                        loadCustomers(1);
                    });

                    // Per page change
                    $('#per-page-select').on('change', function () {
                        perPage = parseInt($(this).val());
                        loadCustomers(1);
                    });

                    // Refresh button
                    $('#refresh-customers').on('click', function () {
                        loadCustomers(currentPage);
                    });

                    // Pagination handlers
                    $('.first-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage > 1) loadCustomers(1);
                    });

                    $('.prev-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage > 1) loadCustomers(currentPage - 1);
                    });

                    $('.next-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage < totalPages) loadCustomers(currentPage + 1);
                    });

                    $('.last-page').on('click', function (e) {
                        e.preventDefault();
                        if (currentPage < totalPages) loadCustomers(totalPages);
                    });

                    $('#current-page-selector').on('keypress', function (e) {
                        if (e.which === 13) { // Enter key
                            let page = parseInt($(this).val());
                            if (page >= 1 && page <= totalPages) {
                                loadCustomers(page);
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
                        let action = id ? 'edit_customer' : 'add_customer';
                        let name = $('#customer-name').val().trim();
                        let email = $('#customer-email').val().trim();
                        let mobile = $('#customer-mobile').val().trim();
                        let address = $('#customer-address').val().trim();
                        let status = $('#customer-status').val();

                        if (!name) {
                            alert('Please enter customer name');
                            return false;
                        }
                        if (!email) {
                            alert('Please enter customer email');
                            return false;
                        }
                        if (!isValidEmail(email)) {
                            alert('Please enter a valid email address');
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
                            nonce: '<?php echo esc_attr(wp_create_nonce("add_customer")); ?>'
                        }, function (res) {
                            if (res.success) {
                                resetForm();
                                loadCustomers(currentPage);
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

                    $(document).on('click', '.edit-customer', function () {
                        let row = $(this).closest('tr');
                        let customerId = row.data('customer-id');

                        // Get customer details
                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'get_customers',
                                id: customerId,
                                nonce: '<?php echo esc_attr(wp_create_nonce("get_customers")); ?>'
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

                                        $('#form-title').text('Edit Customer');
                                        $('#submit-customer').find('.btn-text').text('Update Customer');
                                        $('#cancel-edit').show();
                                    }
                                }
                            }
                        });
                    });

                    $(document).on('click', '.delete-customer', function () {
                        if (!confirm('Are you sure you want to delete this customer?')) return;

                        let button = $(this);
                        let originalText = button.text();
                        let id = $(this).closest('tr').data('customer-id');

                        // Disable button and show loading
                        button.prop('disabled', true).text('Deleting...');

                        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            action: 'delete_customer',
                            id: id,
                            nonce: '<?php echo esc_attr(wp_create_nonce("delete_customer")); ?>'
                        }, function (res) {
                            if (res.success) {
                                loadCustomers(currentPage);
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
                        let button = $('#submit-customer');
                        let spinner = button.find('.spinner');
                        let btnText = button.find('.btn-text');

                        if (loading) {
                            button.prop('disabled', true).addClass('button-loading');
                            spinner.show();
                            btnText.text(button.hasClass('button-loading') ? 'Saving...' : 'Updating...');
                        } else {
                            button.prop('disabled', false).removeClass('button-loading');
                            spinner.hide();
                            btnText.text(button.find('.btn-text').text().includes('Update') ? 'Update Customer' : 'Save Customer');
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
                        $('#form-title').text('Add New Customer');
                        $('#submit-customer').find('.btn-text').text('Save Customer');
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
    public function ajax_get_customers()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'get_customers')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'pos_customers';

        // Get parameters - sanitize inputs
        $customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? intval($_GET['per_page']) : 10;
        $search = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';

        // Build query based on filters
        if ($customer_id > 0) {
            // Single customer request
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $customers = $wpdb->get_results($wpdb->prepare(
                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
                "SELECT * FROM $table_name WHERE id = %d",
                $customer_id
            ));

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
            // Multiple customers with pagination
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

            // Count total items
            $count_query = "SELECT COUNT(*) FROM $table_name $where_clause";
            if (!empty($query_params)) {
                $count_query = $wpdb->prepare($count_query, $query_params);
            }
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $total_items = $wpdb->get_var($count_query);

            // Calculate pagination
            $total_pages = ceil($total_items / $per_page);
            $offset = ($page - 1) * $per_page;

            // Build main query
            $main_query = "SELECT * FROM $table_name $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";

            // Add pagination parameters
            $query_params[] = $per_page;
            $query_params[] = $offset;

            // Prepare and execute main query
            if (!empty($query_params)) {
                $main_query = $wpdb->prepare($main_query, $query_params);
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $customers = $wpdb->get_results($main_query);

            $response_data = [
                'customers' => $customers,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total_items' => $total_items,
                    'total_pages' => $total_pages
                ]
            ];
        }

        wp_send_json_success($response_data);
    }

    /** Add customer */
    public function ajax_add_customer()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'add_customer')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'pos_customers';

        // Unslash and sanitize input
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $mobile = sanitize_text_field(wp_unslash($_POST['mobile'] ?? ''));
        $address = sanitize_text_field(wp_unslash($_POST['address'] ?? ''));
        $status = in_array(wp_unslash($_POST['status'] ?? ''), ['active', 'inactive']) ?
            sanitize_text_field(wp_unslash($_POST['status'])) : 'active';

        // Validate required fields
        if (empty($name)) {
            wp_send_json_error('Customer name is required');
        }
        if (empty($email) || !is_email($email)) {
            wp_send_json_error('Valid email address is required');
        }

        // Check if email already exists
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $existing = $wpdb->get_var($wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT id FROM $table WHERE email = %s",
            $email
        ));

        if ($existing) {
            wp_send_json_error('Customer email already exists');
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->insert($table, [
            'name' => $name,
            'email' => $email,
            'mobile' => $mobile,
            'address' => $address,
            'status' => $status
        ], ['%s', '%s', '%s', '%s', '%s']);

        if ($result === false) {
            wp_send_json_error('Failed to add customer');
        }

        wp_send_json_success('Customer added successfully');
    }

    /** Edit customer */
    public function ajax_edit_customer()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'add_customer')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'pos_customers';

        $id = intval($_POST['id'] ?? 0);
        // Unslash and sanitize input
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        $email = sanitize_email(wp_unslash($_POST['email'] ?? ''));
        $mobile = sanitize_text_field(wp_unslash($_POST['mobile'] ?? ''));
        $address = sanitize_text_field(wp_unslash($_POST['address'] ?? ''));
        $status = in_array(wp_unslash($_POST['status'] ?? ''), ['active', 'inactive']) ?
            sanitize_text_field(wp_unslash($_POST['status'])) : 'active';

        if (!$id || empty($name) || empty($email) || !is_email($email)) {
            wp_send_json_error('Invalid data provided');
        }

        // Check if email already exists (excluding current customer)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $existing = $wpdb->get_var($wpdb->prepare(
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            "SELECT id FROM $table WHERE email = %s AND id != %d",
            $email,
            $id
        ));

        if ($existing) {
            wp_send_json_error('Customer email already exists');
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->update($table, [
            'name' => $name,
            'email' => $email,
            'mobile' => $mobile,
            'address' => $address,
            'status' => $status
        ], ['id' => $id], ['%s', '%s', '%s', '%s', '%s'], ['%d']);

        if ($result === false) {
            wp_send_json_error('Failed to update customer');
        }

        wp_send_json_success('Customer updated successfully');
    }

    /** Delete customer */
    public function ajax_delete_customer()
    {
        // Verify nonce - sanitize the input first
        $nonce = sanitize_text_field(wp_unslash($_REQUEST['nonce'] ?? ''));
        if (!wp_verify_nonce($nonce, 'delete_customer')) {
            wp_send_json_error('Security verification failed');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'pos_customers';
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error('Invalid customer ID');
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->delete($table, ['id' => $id], ['%d']);

        if ($result === false) {
            wp_send_json_error('Failed to delete customer');
        }

        wp_send_json_success('Customer deleted successfully');
    }
}
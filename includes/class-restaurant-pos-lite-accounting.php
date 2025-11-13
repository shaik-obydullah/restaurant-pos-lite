<?php
/**
 * Accounting Management
 *
 * @package Restaurant_POS_Lite
 * @since   1.0.0
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}
class Restaurant_POS_Lite_Accounting
{
    public function __construct()
    {
        add_action('wp_ajax_add_accounting_entry', array($this, 'ajax_add_accounting_entry'));
        add_action('wp_ajax_get_accounting_entries', array($this, 'ajax_get_accounting_entries'));
        add_action('wp_ajax_delete_accounting_entry', array($this, 'ajax_delete_accounting_entry'));
    }

    /**
     * Format currency using helper class
     *
     * @param float $amount The amount to format.
     * @return string
     */
    private function format_currency($amount)
    {
        return Restaurant_POS_Lite_Helpers::format_currency($amount);
    }

    /**
     * Format date using helper class
     *
     * @param string $date_string The date string to format.
     * @return string
     */
    private function format_date($date_string)
    {
        return Restaurant_POS_Lite_Helpers::format_date($date_string);
    }

    /**
     * Get shop name using helper class
     *
     * @return string
     */
    private function get_shop_name()
    {
        return Restaurant_POS_Lite_Helpers::get_shop_name();
    }

    /**
     * Get accounting table name
     *
     * @return string
     */
    private function get_table_name()
    {
        global $wpdb;
        return $wpdb->prefix . 'pos_accounting';
    }

    /**
     * Render the accounting page
     */
    public function render_page()
    {
        $shop_name = $this->get_shop_name();
        $current_date = $this->format_date(gmdate('Y-m-d'));
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline" style="margin-bottom:20px;">
                <?php echo esc_html($shop_name); ?> - <?php esc_html_e('Accounting', 'restaurant-pos-lite'); ?>
            </h1>
            <hr class="wp-header-end">

            <!-- Accounting Summary Cards -->
            <div class="accounting-summary-cards"
                style="display:grid;grid-template-columns:repeat(auto-fit, minmax(250px, 1fr));gap:20px;margin-bottom:30px;">
                <div class="summary-card"
                    style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.05);border-left:4px solid #0a7c38;">
                    <h3 style="margin:0 0 10px 0;font-size:14px;color:#666;">
                        <?php esc_html_e('Total Income', 'restaurant-pos-lite'); ?>
                    </h3>
                    <p class="summary-number" style="font-size:32px;font-weight:bold;margin:0;color:#0a7c38;" id="total-income">
                        <?php echo esc_html($this->format_currency(0)); ?>
                    </p>
                </div>
                <div class="summary-card"
                    style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.05);border-left:4px solid #d63638;">
                    <h3 style="margin:0 0 10px 0;font-size:14px;color:#666;">
                        <?php esc_html_e('Total Expense', 'restaurant-pos-lite'); ?>
                    </h3>
                    <p class="summary-number" style="font-size:32px;font-weight:bold;margin:0;color:#d63638;"
                        id="total-expense"><?php echo esc_html($this->format_currency(0)); ?></p>
                </div>
                <div class="summary-card"
                    style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.05);border-left:4px solid #2271b1;">
                    <h3 style="margin:0 0 10px 0;font-size:14px;color:#666;">
                        <?php esc_html_e('Total Payable', 'restaurant-pos-lite'); ?>
                    </h3>
                    <p class="summary-number" style="font-size:32px;font-weight:bold;margin:0;color:#2271b1;"
                        id="total-payable"><?php echo esc_html($this->format_currency(0)); ?></p>
                </div>
                <div class="summary-card"
                    style="background:#fff;padding:20px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.05);border-left:4px solid #ffb900;">
                    <h3 style="margin:0 0 10px 0;font-size:14px;color:#666;">
                        <?php esc_html_e('Total Receivable', 'restaurant-pos-lite'); ?>
                    </h3>
                    <p class="summary-number" style="font-size:32px;font-weight:bold;margin:0;color:#ffb900;"
                        id="total-receivable"><?php echo esc_html($this->format_currency(0)); ?></p>
                </div>
            </div>

            <div id="col-container" class="wp-clearfix" style="display:flex;gap:24px;">
                <!-- Left: Add Accounting Entry Form -->
                <div id="col-left" style="flex:1; max-width:480px;">
                    <div class="col-wrap"
                        style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">
                        <h2 style="margin:0 0 20px 0;padding:0;font-size:16px;font-weight:600;color:#1d2327;">
                            <?php esc_html_e('Add Accounting Entry', 'restaurant-pos-lite'); ?>
                        </h2>
                        <form id="add-accounting-form" method="post">
                            <?php wp_nonce_field('add_accounting_entry', 'accounting_nonce'); ?>

                            <div style="display:flex;flex-direction:column;gap:15px;">
                                <!-- Entry Type -->
                                <div class="form-field form-required">
                                    <label for="entry-type"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        <?php esc_html_e('Entry Type', 'restaurant-pos-lite'); ?> <span
                                            style="color:#d63638;">*</span>
                                    </label>
                                    <select name="entry_type" id="entry-type" required
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;cursor:pointer;">
                                        <option value=""><?php esc_html_e('Select Type', 'restaurant-pos-lite'); ?></option>
                                        <option value="income"><?php esc_html_e('Income', 'restaurant-pos-lite'); ?></option>
                                        <option value="expense"><?php esc_html_e('Expense', 'restaurant-pos-lite'); ?>
                                        </option>
                                        <option value="payable"><?php esc_html_e('Payable', 'restaurant-pos-lite'); ?>
                                        </option>
                                        <option value="receivable"><?php esc_html_e('Receivable', 'restaurant-pos-lite'); ?>
                                        </option>
                                    </select>
                                </div>

                                <!-- Amount -->
                                <div class="form-field form-required">
                                    <label for="entry-amount"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        <?php esc_html_e('Amount', 'restaurant-pos-lite'); ?> <span
                                            style="color:#d63638;">*</span>
                                    </label>
                                    <input name="amount" id="entry-amount" type="number" step="0.01" min="0" value="" required
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;"
                                        placeholder="0.00">
                                </div>

                                <!-- Description -->
                                <div class="form-field">
                                    <label for="entry-description"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        <?php esc_html_e('Description', 'restaurant-pos-lite'); ?>
                                    </label>
                                    <textarea name="description" id="entry-description" rows="3"
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;resize:vertical;"
                                        placeholder="<?php esc_attr_e('Enter description for this entry (optional)', 'restaurant-pos-lite'); ?>"></textarea>
                                </div>

                                <!-- Date -->
                                <div class="form-field">
                                    <label for="entry-date"
                                        style="display:block;font-weight:600;margin-bottom:6px;font-size:12px;color:#1d2327;text-transform:uppercase;letter-spacing:0.5px;">
                                        <?php esc_html_e('Date', 'restaurant-pos-lite'); ?>
                                    </label>
                                    <input name="entry_date" id="entry-date" type="date"
                                        value="<?php echo esc_attr($current_date); ?>"
                                        style="width:100%;padding:8px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;background:#fff;transition:border-color 0.2s ease;">
                                </div>
                            </div>

                            <div style="margin-top:20px;">
                                <button type="submit" id="submit-accounting" class="button button-primary"
                                    style="width:100%;padding:8px 12px;font-size:13px;font-weight:500;">
                                    <span class="btn-text"><?php esc_html_e('Save Entry', 'restaurant-pos-lite'); ?></span>
                                    <span class="spinner" style="float:none;margin:0;display:none;"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Right: Accounting Table -->
                <div id="col-right" style="flex:2;">
                    <div class="col-wrap"
                        style="background:#fff;padding:20px;border:1px solid #ddd;border-radius:6px;box-shadow:0 2px 6px rgba(0,0,0,0.05);">

                        <!-- Date Filter -->
                        <div class="accounting-filters"
                            style="margin-bottom:20px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
                            <div class="date-filters" style="display:flex;gap:8px;align-items:center;">
                                <input type="date" id="date-from"
                                    style="padding:6px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;">
                                <span><?php esc_html_e('to', 'restaurant-pos-lite'); ?></span>
                                <input type="date" id="date-to"
                                    style="padding:6px 10px;font-size:13px;border:1px solid #8c8f94;border-radius:3px;">
                            </div>

                            <button type="button" id="search-entries" class="button button-primary" style="padding:6px 12px;">
                                <?php esc_html_e('Filter', 'restaurant-pos-lite'); ?>
                            </button>
                            <button type="button" id="reset-filters" class="button" style="padding:6px 12px;">
                                <?php esc_html_e('Reset', 'restaurant-pos-lite'); ?>
                            </button>
                        </div>

                        <table class="wp-list-table widefat fixed striped table-view-list">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e('Date', 'restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Description', 'restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Income', 'restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Expense', 'restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Payable', 'restaurant-pos-lite'); ?></th>
                                    <th><?php esc_html_e('Receivable', 'restaurant-pos-lite'); ?></th>
                                    <th style="text-align:center;"><?php esc_html_e('Actions', 'restaurant-pos-lite'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="accounting-list">
                                <tr>
                                    <td colspan="8" class="loading-entries" style="text-align:center;">
                                        <span class="spinner is-active"></span>
                                        <?php esc_html_e('Loading accounting entries...', 'restaurant-pos-lite'); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        <!-- Pagination -->
                        <div class="accounting-pagination"
                            style="margin-top:20px;display:flex;justify-content:space-between;align-items:center;">
                            <div class="pagination-info" style="font-size:13px;color:#646970;">
                                <?php
                                printf(
                                    /* translators: 1: showing from, 2: showing to, 3: total entries */
                                    esc_html__('Showing %1$s - %2$s of %3$s entries', 'restaurant-pos-lite'),
                                    '<span id="showing-from">0</span>',
                                    '<span id="showing-to">0</span>',
                                    '<span id="total-entries">0</span>'
                                );
                                ?>
                            </div>
                            <div class="pagination-links" style="display:flex;gap:5px;">
                                <button class="button" id="prev-page"
                                    disabled><?php esc_html_e('Previous', 'restaurant-pos-lite'); ?></button>
                                <span class="pagination-numbers" style="display:flex;gap:5px;align-items:center;"></span>
                                <button class="button" id="next-page"
                                    disabled><?php esc_html_e('Next', 'restaurant-pos-lite'); ?></button>
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
                    let dateFrom = '';
                    let dateTo = '';

                    // Helper function to determine entry type from data
                    function getEntryType(entry) {
                        if (parseFloat(entry.in_amount) > 0) return 'income';
                        if (parseFloat(entry.out_amount) > 0) return 'expense';
                        if (parseFloat(entry.amount_payable) > 0) return 'payable';
                        if (parseFloat(entry.amount_receivable) > 0) return 'receivable';
                        return 'unknown';
                    }

                    // Helper function to get entry type text
                    function getEntryTypeText(entry) {
                        if (parseFloat(entry.in_amount) > 0) return '<?php echo esc_js(__('Income', 'restaurant-pos-lite')); ?>';
                        if (parseFloat(entry.out_amount) > 0) return '<?php echo esc_js(__('Expense', 'restaurant-pos-lite')); ?>';
                        if (parseFloat(entry.amount_payable) > 0) return '<?php echo esc_js(__('Payable', 'restaurant-pos-lite')); ?>';
                        if (parseFloat(entry.amount_receivable) > 0) return '<?php echo esc_js(__('Receivable', 'restaurant-pos-lite')); ?>';
                        return '<?php echo esc_js(__('Unknown', 'restaurant-pos-lite')); ?>';
                    }

                    // Format currency using PHP helper output
                    function formatCurrency(amount) {
                        // Use the PHP formatted currency as template
                        const template = '<?php echo esc_js($this->format_currency(0)); ?>';
                        const amountFormatted = parseFloat(amount).toFixed(2);
                        return template.replace('0.00', amountFormatted);
                    }

                    function updateSummaryCards(totals) {
                        $('#total-income').text(totals.total_income || formatCurrency(0));
                        $('#total-expense').text(totals.total_expense || formatCurrency(0));
                        $('#total-payable').text(totals.total_payable || formatCurrency(0));
                        $('#total-receivable').text(totals.total_receivable || formatCurrency(0));
                    }

                    function updatePagination(data) {
                        totalPages = Math.ceil(data.total / perPage);

                        // Update showing info
                        $('#showing-from').text(data.showing_from);
                        $('#showing-to').text(data.showing_to);
                        $('#total-entries').text(data.total);

                        // Update pagination buttons
                        $('#prev-page').prop('disabled', currentPage === 1);
                        $('#next-page').prop('disabled', currentPage === totalPages || totalPages === 0);

                        // Update page numbers
                        let paginationNumbers = $('.pagination-numbers').empty();
                        let startPage = Math.max(1, currentPage - 2);
                        let endPage = Math.min(totalPages, startPage + 4);

                        for (let i = startPage; i <= endPage; i++) {
                            let pageBtn = $('<button>').addClass('button page-number')
                                .text(i)
                                .toggleClass('current', i === currentPage);

                            if (i !== currentPage) {
                                pageBtn.on('click', function () {
                                    currentPage = i;
                                    loadAccountingEntries();
                                });
                            }

                            paginationNumbers.append(pageBtn);
                        }
                    }

                    function setButtonLoading(loading) {
                        let button = $('#submit-accounting');
                        let spinner = button.find('.spinner');
                        let btnText = button.find('.btn-text');

                        if (loading) {
                            button.prop('disabled', true).addClass('button-loading');
                            spinner.show();
                            btnText.text('<?php echo esc_js(__('Saving...', 'restaurant-pos-lite')); ?>');
                        } else {
                            button.prop('disabled', false).removeClass('button-loading');
                            spinner.hide();
                            btnText.text('<?php echo esc_js(__('Save Entry', 'restaurant-pos-lite')); ?>');
                        }
                    }

                    function resetForm() {
                        $('#entry-type').val('');
                        $('#entry-amount').val('');
                        $('#entry-description').val('');
                        $('#entry-date').val('<?php echo esc_js($current_date); ?>');
                        $('#entry-type').focus();

                        // Ensure button is enabled
                        setButtonLoading(false);
                    }

                    // Load initial entries
                    loadAccountingEntries();

                    // Filter functionality
                    $('#search-entries').on('click', function () {
                        dateFrom = $('#date-from').val();
                        dateTo = $('#date-to').val();
                        currentPage = 1;
                        loadAccountingEntries();
                    });

                    // Reset filters
                    $('#reset-filters').on('click', function () {
                        $('#date-from').val('');
                        $('#date-to').val('');
                        dateFrom = '';
                        dateTo = '';
                        currentPage = 1;
                        loadAccountingEntries();
                    });

                    // Pagination
                    $('#prev-page').on('click', function () {
                        if (currentPage > 1) {
                            currentPage--;
                            loadAccountingEntries();
                        }
                    });

                    $('#next-page').on('click', function () {
                        if (currentPage < totalPages) {
                            currentPage++;
                            loadAccountingEntries();
                        }
                    });

                    function loadAccountingEntries() {
                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'get_accounting_entries',
                                page: currentPage,
                                per_page: perPage,
                                date_from: dateFrom,
                                date_to: dateTo,
                                nonce: '<?php echo esc_js(wp_create_nonce('get_accounting_entries')); ?>'
                            },
                            success: function (response) {
                                let tbody = $('#accounting-list').empty();
                                if (response.success) {
                                    updatePagination(response.data);
                                    updateSummaryCards(response.data.totals);

                                    if (!response.data.entries.length) {
                                        tbody.append('<tr><td colspan="8" style="text-align:center;"><?php echo esc_js(__('No accounting entries found.', 'restaurant-pos-lite')); ?></td></tr>');
                                        return;
                                    }

                                    $.each(response.data.entries, function (_, entry) {
                                        let row = $('<tr>').attr('data-entry-id', entry.id);

                                        // Date column - use formatted date from server
                                        let formattedDate = entry.formatted_date || new Date(entry.created_at).toLocaleDateString();
                                        row.append($('<td>').text(formattedDate));

                                        // Description column
                                        let description = entry.description || '-';
                                        row.append($('<td>').append(
                                            $('<span>').addClass('entry-description').attr('title', description).text(description)
                                        ));

                                        // Income column - use formatted amount from server
                                        let incomeAmount = parseFloat(entry.in_amount || 0);
                                        let formattedIncome = entry.formatted_in_amount || formatCurrency(incomeAmount);
                                        row.append($('<td>').append(
                                            $('<span>').addClass(incomeAmount > 0 ? 'amount-positive' : 'amount-zero')
                                                .text(formattedIncome)
                                        ));

                                        // Expense column - use formatted amount from server
                                        let expenseAmount = parseFloat(entry.out_amount || 0);
                                        let formattedExpense = entry.formatted_out_amount || formatCurrency(expenseAmount);
                                        row.append($('<td>').append(
                                            $('<span>').addClass(expenseAmount > 0 ? 'amount-negative' : 'amount-zero')
                                                .text(formattedExpense)
                                        ));

                                        // Payable column - use formatted amount from server
                                        let payableAmount = parseFloat(entry.amount_payable || 0);
                                        let formattedPayable = entry.formatted_payable || formatCurrency(payableAmount);
                                        row.append($('<td>').append(
                                            $('<span>').addClass(payableAmount > 0 ? 'amount-negative' : 'amount-zero')
                                                .text(formattedPayable)
                                        ));

                                        // Receivable column - use formatted amount from server
                                        let receivableAmount = parseFloat(entry.amount_receivable || 0);
                                        let formattedReceivable = entry.formatted_receivable || formatCurrency(receivableAmount);
                                        row.append($('<td>').append(
                                            $('<span>').addClass(receivableAmount > 0 ? 'amount-positive' : 'amount-zero')
                                                .text(formattedReceivable)
                                        ));

                                        // Actions column - Only delete button
                                        row.append($('<td style="text-align:center;">')
                                            .append('<button class="button button-small button-link-delete delete-entry"><?php echo esc_js(__('Delete', 'restaurant-pos-lite')); ?></button>')
                                        );

                                        tbody.append(row);
                                    });
                                } else {
                                    tbody.append('<tr><td colspan="8" style="color:red;text-align:center;">' + response.data + '</td></tr>');
                                }
                            },
                            error: function () {
                                $('#accounting-list').html('<tr><td colspan="8" style="color:red;text-align:center;"><?php echo esc_js(__('Failed to load accounting entries.', 'restaurant-pos-lite')); ?></td></tr>');
                            }
                        });
                    }

                    $('#add-accounting-form').on('submit', function (e) {
                        e.preventDefault();

                        // Prevent double submission
                        if (isSubmitting) {
                            return false;
                        }

                        let entryType = $('#entry-type').val();
                        let amount = parseFloat($('#entry-amount').val());
                        let description = $('#entry-description').val();
                        let entryDate = $('#entry-date').val();

                        if (!entryType) {
                            alert('<?php echo esc_js(__('Please select entry type', 'restaurant-pos-lite')); ?>');
                            return false;
                        }
                        if (!amount || amount <= 0) {
                            alert('<?php echo esc_js(__('Please enter a valid amount', 'restaurant-pos-lite')); ?>');
                            return false;
                        }

                        // Set submitting state
                        isSubmitting = true;
                        setButtonLoading(true);

                        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            action: 'add_accounting_entry',
                            entry_type: entryType,
                            amount: amount,
                            description: description,
                            entry_date: entryDate,
                            nonce: '<?php echo esc_js(wp_create_nonce('add_accounting_entry')); ?>'
                        }, function (res) {
                            if (res.success) {
                                resetForm();
                                loadAccountingEntries();
                            } else {
                                alert('<?php echo esc_js(__('Error:', 'restaurant-pos-lite')); ?> ' + res.data);
                            }
                        }).fail(() => alert('<?php echo esc_js(__('Request failed. Please try again.', 'restaurant-pos-lite')); ?>'))
                            .always(function () {
                                // Reset submitting state
                                isSubmitting = false;
                                setButtonLoading(false);
                            });
                    });

                    $(document).on('click', '.delete-entry', function () {
                        if (!confirm('<?php echo esc_js(__('Are you sure you want to delete this accounting entry?', 'restaurant-pos-lite')); ?>')) return;

                        let button = $(this);
                        let originalText = button.text();
                        let id = $(this).closest('tr').data('entry-id');

                        // Disable button and show loading
                        button.prop('disabled', true).text('<?php echo esc_js(__('Deleting...', 'restaurant-pos-lite')); ?>');

                        $.post('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                            action: 'delete_accounting_entry',
                            id: id,
                            nonce: '<?php echo esc_js(wp_create_nonce('delete_accounting_entry')); ?>'
                        }, function (res) {
                            if (res.success) {
                                loadAccountingEntries();
                            } else {
                                alert(res.data);
                            }
                        }).fail(() => alert('<?php echo esc_js(__('Delete request failed. Please try again.', 'restaurant-pos-lite')); ?>'))
                            .always(function () {
                                // Re-enable button
                                button.prop('disabled', false).text(originalText);
                            });
                    });
                });
            </script>
        </div>
        <?php
    }

    /** Get accounting entries with pagination and date filter */
    public function ajax_get_accounting_entries()
    {
        // Check nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'] ?? '')), 'get_accounting_entries')) {
            wp_send_json_error(__('Security verification failed', 'restaurant-pos-lite'));
        }

        global $wpdb;
        $accounting_table = $this->get_table_name();

        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $per_page = isset($_GET['per_page']) ? max(1, intval($_GET['per_page'])) : 10;
        $date_from = isset($_GET['date_from']) ? sanitize_text_field(wp_unslash($_GET['date_from'])) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field(wp_unslash($_GET['date_to'])) : '';

        $offset = ($page - 1) * $per_page;

        // Build WHERE clause
        $where_clause = '1=1';
        $prepare_args = array();

        if (!empty($date_from)) {
            $where_clause .= ' AND DATE(created_at) >= %s';
            $prepare_args[] = $date_from;
        }

        if (!empty($date_to)) {
            $where_clause .= ' AND DATE(created_at) <= %s';
            $prepare_args[] = $date_to;
        }

        // Get total count
        $count_query = "SELECT COUNT(*) FROM $accounting_table WHERE $where_clause";
        if (!empty($prepare_args)) {
            $count_query = $wpdb->prepare($count_query, $prepare_args);
        }
        $total = $wpdb->get_var($count_query);

        // Get entries data
        $query = "SELECT * FROM $accounting_table WHERE $where_clause ORDER BY created_at DESC LIMIT %d OFFSET %d";

        $prepare_args[] = $per_page;
        $prepare_args[] = $offset;

        $query = $wpdb->prepare($query, $prepare_args);
        $entries = $wpdb->get_results($query);

        // Format entries with helper functions
        if ($entries) {
            foreach ($entries as $entry) {
                $entry->formatted_date = $this->format_date($entry->created_at);
                $entry->formatted_in_amount = $this->format_currency($entry->in_amount);
                $entry->formatted_out_amount = $this->format_currency($entry->out_amount);
                $entry->formatted_payable = $this->format_currency($entry->amount_payable);
                $entry->formatted_receivable = $this->format_currency($entry->amount_receivable);
            }
        }

        // Calculate totals
        $totals_query = "SELECT 
			COALESCE(SUM(in_amount), 0) as total_income,
			COALESCE(SUM(out_amount), 0) as total_expense,
			COALESCE(SUM(amount_payable), 0) as total_payable,
			COALESCE(SUM(amount_receivable), 0) as total_receivable
		FROM $accounting_table WHERE $where_clause";

        if (!empty($prepare_args)) {
            // Remove the LIMIT and OFFSET parameters for totals query
            $totals_prepare_args = array_slice($prepare_args, 0, -2);
            $totals_query = $wpdb->prepare($totals_query, $totals_prepare_args);
        }
        $totals = $wpdb->get_row($totals_query);

        // Format totals
        $formatted_totals = array(
            'total_income' => $this->format_currency($totals->total_income),
            'total_expense' => $this->format_currency($totals->total_expense),
            'total_payable' => $this->format_currency($totals->total_payable),
            'total_receivable' => $this->format_currency($totals->total_receivable),
            'raw_totals' => $totals, // Keep raw values for calculations.
        );

        // Calculate showing range.
        $showing_from = $total > 0 ? $offset + 1 : 0;
        $showing_to = min($offset + $per_page, $total);

        wp_send_json_success(
            array(
                'entries' => $entries,
                'total' => $total,
                'showing_from' => $showing_from,
                'showing_to' => $showing_to,
                'current_page' => $page,
                'per_page' => $per_page,
                'totals' => $formatted_totals,
            )
        );
    }

    /** Add accounting entry */
    public function ajax_add_accounting_entry()
    {
        // Check nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'add_accounting_entry')) {
            wp_send_json_error(__('Security verification failed', 'restaurant-pos-lite'));
        }

        global $wpdb;
        $table = $this->get_table_name();

        $entry_type = sanitize_text_field(wp_unslash($_POST['entry_type'] ?? ''));
        $amount = floatval($_POST['amount'] ?? 0);
        $description = sanitize_textarea_field(wp_unslash($_POST['description'] ?? ''));
        $entry_date = sanitize_text_field(wp_unslash($_POST['entry_date'] ?? ''));

        // Validate required fields.
        if (empty($entry_type)) {
            wp_send_json_error(__('Entry type is required', 'restaurant-pos-lite'));
        }
        if ($amount <= 0) {
            wp_send_json_error(__('Valid amount is required', 'restaurant-pos-lite'));
        }

        // Prepare data based on entry type.
        $data = array();

        // Set the appropriate amount field based on entry type.
        switch ($entry_type) {
            case 'income':
                $data['in_amount'] = $amount;
                $data['out_amount'] = 0;
                $data['amount_payable'] = 0;
                $data['amount_receivable'] = 0;
                break;
            case 'expense':
                $data['in_amount'] = 0;
                $data['out_amount'] = $amount;
                $data['amount_payable'] = 0;
                $data['amount_receivable'] = 0;
                break;
            case 'payable':
                $data['in_amount'] = 0;
                $data['out_amount'] = 0;
                $data['amount_payable'] = $amount;
                $data['amount_receivable'] = 0;
                break;
            case 'receivable':
                $data['in_amount'] = 0;
                $data['out_amount'] = 0;
                $data['amount_payable'] = 0;
                $data['amount_receivable'] = $amount;
                break;
            default:
                wp_send_json_error(__('Invalid entry type', 'restaurant-pos-lite'));
        }

        // Add description.
        $data['description'] = $description;

        // Set custom date if provided.
        if (!empty($entry_date)) {
            $data['created_at'] = $entry_date . ' ' . gmdate('H:i:s');
        }

        $result = $wpdb->insert($table, $data, array('%f', '%f', '%f', '%f', '%s', '%s'));

        if (false === $result) {
            wp_send_json_error(__('Failed to add accounting entry', 'restaurant-pos-lite'));
        }

        wp_send_json_success(__('Accounting entry added successfully', 'restaurant-pos-lite'));
    }

    /** Delete accounting entry */
    public function ajax_delete_accounting_entry()
    {
        // Check nonce
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'] ?? '')), 'delete_accounting_entry')) {
            wp_send_json_error(__('Security verification failed', 'restaurant-pos-lite'));
        }

        global $wpdb;
        $table = $this->get_table_name();
        $id = intval($_POST['id'] ?? 0);

        if (!$id) {
            wp_send_json_error(__('Invalid accounting entry ID', 'restaurant-pos-lite'));
        }

        $result = $wpdb->delete($table, array('id' => $id), array('%d'));

        if (false === $result) {
            wp_send_json_error(__('Failed to delete accounting entry', 'restaurant-pos-lite'));
        }

        wp_send_json_success(__('Accounting entry deleted successfully', 'restaurant-pos-lite'));
    }
}
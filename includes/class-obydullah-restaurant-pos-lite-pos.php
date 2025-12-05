<?php
/**
 * Point of Sales (POS)
 *
 * @package Obydullah_Restaurant_POS_Lite
 * @since   1.0.0
 */
if (!defined('ABSPATH')) {
    exit;
}

class Obydullah_Restaurant_POS_Lite_POS
{
    const CACHE_GROUP = 'orpl_pos';
    const CACHE_EXPIRATION = 10 * MINUTE_IN_SECONDS;

    private $helpers;
    private $sales_table;
    private $sale_details_table;
    private $stocks_table;
    private $accounting_table;
    private $products_table;
    private $categories_table;
    private $customers_table;

    public function __construct()
    {
        global $wpdb;
        $this->helpers = new Obydullah_Restaurant_POS_Lite_Helpers();

        $this->sales_table = $wpdb->prefix . 'orpl_sales';
        $this->sale_details_table = $wpdb->prefix . 'orpl_sale_details';
        $this->stocks_table = $wpdb->prefix . 'orpl_stocks';
        $this->accounting_table = $wpdb->prefix . 'orpl_accounting';
        $this->products_table = $wpdb->prefix . 'orpl_products';
        $this->categories_table = $wpdb->prefix . 'orpl_categories';
        $this->customers_table = $wpdb->prefix . 'orpl_customers';

        add_action('wp_ajax_orpl_get_categories_for_pos', [$this, 'ajax_get_categories_for_pos']);
        add_action('wp_ajax_orpl_get_products_by_category', [$this, 'ajax_get_products_by_category']);
        add_action('wp_ajax_orpl_get_customers_for_pos', [$this, 'ajax_get_customers_for_pos']);
        add_action('wp_ajax_orpl_process_sale', [$this, 'ajax_process_sale']);
        add_action('wp_ajax_orpl_get_saved_sales', [$this, 'ajax_get_saved_sales']);
        add_action('wp_ajax_orpl_load_saved_sale', [$this, 'ajax_load_saved_sale']);
    }

    /**
     * Render the POS page
     */
    public function render_page()
    {
        $currency_symbol = $this->helpers->get_currency_symbol();
        $currency_position = $this->helpers->get_currency_position();
        $vat_rate = $this->helpers->get_vat_rate();
        $is_vat_enabled = $this->helpers->is_vat_enabled();
        $shop_info = $this->helpers->get_shop_info();
        ?>
        <div class="wrap">
            <h1 style="margin-bottom:20px;"><?php esc_html_e('Restaurant POS System', 'obydullah-restaurant-pos-lite'); ?></h1>

            <!-- Saved Sales Panel -->
            <div id="saved-sales-panel"
                style="display: none; margin-bottom: 20px; background: white; padding: 15px; border-radius: 5px; border: 1px solid #ddd;">
                <h3 style="margin-top: 0;"><?php esc_html_e('Saved Sales', 'obydullah-restaurant-pos-lite'); ?></h3>
                <div id="saved-sales-list" style="max-height: 200px; overflow-y: auto;">
                    <!-- Saved sales will be loaded here -->
                </div>
                <button id="close-saved-sales" class="button"
                    style="margin-top: 10px;"><?php esc_html_e('Close', 'obydullah-restaurant-pos-lite'); ?></button>
            </div>

            <div id="pos-container" style="display: flex; height: calc(100vh - 120px); min-height: 600px; background: #f5f5f5;">
                <!-- Stock Items Panel -->
                <div style="flex: 3; border-right: 2px solid #ccc; padding: 20px; overflow-y: auto; background-color: #f9f9f9;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h2 style="margin: 0; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 10px;">
                            <?php esc_html_e('Stock Items', 'obydullah-restaurant-pos-lite'); ?>
                        </h2>
                        <button id="show-saved-sales"
                            class="button"><?php esc_html_e('Load Saved Sale', 'obydullah-restaurant-pos-lite'); ?></button>
                    </div>

                    <!-- Customer Selection -->
                    <div
                        style="margin-bottom: 20px; background: white; padding: 15px; border-radius: 5px; border: 1px solid #ddd;">
                        <label
                            style="display: block; margin-bottom: 8px; font-weight: bold;"><?php esc_html_e('Select Customer', 'obydullah-restaurant-pos-lite'); ?></label>
                        <select id="customer-select"
                            style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value=""><?php esc_html_e('Walk-in Customer', 'obydullah-restaurant-pos-lite'); ?></option>
                        </select>
                    </div>

                    <!-- Product Categories -->
                    <div style="margin-bottom: 20px;" id="category-buttons">
                        <!-- Categories will be loaded via AJAX -->
                    </div>

                    <!-- Product Grid -->
                    <div id="product-grid"
                        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <div class="spinner is-active" style="float: none; margin: 0 auto;"></div>
                            <p><?php esc_html_e('Loading stock items...', 'obydullah-restaurant-pos-lite'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Cart & Checkout Panel -->
                <div style="flex: 2; padding: 20px; background-color: #f5f5f5;">
                    <h2 style="margin-top: 0; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 10px;">
                        <?php esc_html_e('Current Sale', 'obydullah-restaurant-pos-lite'); ?>
                    </h2>

                    <!-- Cart Items -->
                    <div style="margin-bottom: 20px; max-height: 300px; overflow-y: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background-color: #e0e0e0;">
                                    <th style="padding: 10px; text-align: left; border-bottom: 1px solid #ddd;">
                                        <?php esc_html_e('Item', 'obydullah-restaurant-pos-lite'); ?>
                                    </th>
                                    <th style="padding: 10px; text-align: center; border-bottom: 1px solid #ddd;">
                                        <?php esc_html_e('Qty', 'obydullah-restaurant-pos-lite'); ?>
                                    </th>
                                    <th style="padding: 10px; text-align: right; border-bottom: 1px solid #ddd;">
                                        <?php esc_html_e('Price', 'obydullah-restaurant-pos-lite'); ?>
                                    </th>
                                    <th style="padding: 10px; text-align: right; border-bottom: 1px solid #ddd;">
                                        <?php esc_html_e('Total', 'obydullah-restaurant-pos-lite'); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="cart-items">
                                <tr>
                                    <td colspan="4" style="padding: 20px; text-align: center; color: #666;">
                                        <?php esc_html_e('No items in cart', 'obydullah-restaurant-pos-lite'); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Order Type Tabs -->
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; border-bottom: 1px solid #ddd;">
                            <button id="dineInTab"
                                style="flex: 1; padding: 12px; background-color: #4CAF50; color: white; border: none; border-radius: 4px 4px 0 0; margin-right: 2px;"><?php esc_html_e('Dine In', 'obydullah-restaurant-pos-lite'); ?></button>
                            <button id="takeAwayTab"
                                style="flex: 1; padding: 12px; background-color: #e0e0e0; border: none; border-radius: 4px 4px 0 0; margin-right: 2px;"><?php esc_html_e('Take Away', 'obydullah-restaurant-pos-lite'); ?></button>
                            <button id="pickupTab"
                                style="flex: 1; padding: 12px; background-color: #e0e0e0; border: none; border-radius: 4px 4px 0 0;"><?php esc_html_e('Pickup', 'obydullah-restaurant-pos-lite'); ?></button>
                        </div>

                        <!-- Dine In Options -->
                        <div id="dineInOptions"
                            style="background-color: white; padding: 15px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; border-top: none;">
                            <div style="margin-bottom: 10px;">
                                <label
                                    style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Table Number', 'obydullah-restaurant-pos-lite'); ?></label>
                                <input type="text" id="table-number"
                                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"
                                    placeholder="<?php esc_attr_e('Enter table number', 'obydullah-restaurant-pos-lite'); ?>">
                            </div>
                            <div>
                                <label
                                    style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Cooking Instructions', 'obydullah-restaurant-pos-lite'); ?></label>
                                <textarea id="dinein-instructions"
                                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; min-height: 60px;"
                                    placeholder="<?php esc_attr_e('Add special cooking instructions...', 'obydullah-restaurant-pos-lite'); ?>"></textarea>
                            </div>
                        </div>

                        <!-- Take Away Options -->
                        <div id="takeAwayOptions"
                            style="background-color: white; padding: 15px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; border-top: none; display: none;">
                            <div style="margin-bottom: 10px;">
                                <label
                                    style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Customer Name', 'obydullah-restaurant-pos-lite'); ?></label>
                                <input type="text" id="takeaway-name"
                                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"
                                    placeholder="<?php esc_attr_e('Enter customer name', 'obydullah-restaurant-pos-lite'); ?>">
                            </div>
                            <div style="margin-bottom: 10px;">
                                <label
                                    style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Delivery Address', 'obydullah-restaurant-pos-lite'); ?></label>
                                <textarea id="takeaway-address"
                                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; min-height: 60px;"
                                    placeholder="<?php esc_attr_e('Enter delivery address', 'obydullah-restaurant-pos-lite'); ?>"></textarea>
                            </div>

                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <div style="flex: 1;">
                                    <label
                                        style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Email', 'obydullah-restaurant-pos-lite'); ?></label>
                                    <input type="email" id="takeaway-email"
                                        style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"
                                        placeholder="<?php esc_attr_e('Enter email address', 'obydullah-restaurant-pos-lite'); ?>">
                                </div>
                                <div style="flex: 1;">
                                    <label
                                        style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Mobile', 'obydullah-restaurant-pos-lite'); ?></label>
                                    <input type="text" id="takeaway-mobile"
                                        style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"
                                        placeholder="<?php esc_attr_e('Enter mobile number', 'obydullah-restaurant-pos-lite'); ?>">
                                </div>
                            </div>

                            <div style="margin-bottom: 10px;">
                                <label
                                    style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Cooking Instructions', 'obydullah-restaurant-pos-lite'); ?></label>
                                <textarea id="takeaway-instructions"
                                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; min-height: 60px;"
                                    placeholder="<?php esc_attr_e('Enter Cooking Instructions', 'obydullah-restaurant-pos-lite'); ?>"></textarea>
                            </div>
                        </div>

                        <!-- Pickup Options -->
                        <div id="pickupOptions"
                            style="background-color: white; padding: 15px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; border-top: none; display: none;">
                            <div style="margin-bottom: 10px;">
                                <label
                                    style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Customer Name', 'obydullah-restaurant-pos-lite'); ?></label>
                                <input type="text" id="pickup-name"
                                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"
                                    placeholder="<?php esc_attr_e('Enter customer name', 'obydullah-restaurant-pos-lite'); ?>">
                            </div>
                            <div style="margin-bottom: 10px;">
                                <label
                                    style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Mobile', 'obydullah-restaurant-pos-lite'); ?></label>
                                <input type="tel" id="pickup-mobile"
                                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"
                                    placeholder="<?php esc_attr_e('Enter mobile number', 'obydullah-restaurant-pos-lite'); ?>">
                            </div>
                            <div>
                                <label
                                    style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Cooking Instructions', 'obydullah-restaurant-pos-lite'); ?></label>
                                <textarea id="pickup-instructions"
                                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; min-height: 60px;"
                                    placeholder="<?php esc_attr_e('Add special cooking instructions...', 'obydullah-restaurant-pos-lite'); ?>"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div style="background-color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span><?php esc_html_e('Subtotal:', 'obydullah-restaurant-pos-lite'); ?></span>
                            <span id="subtotal"><?php echo esc_html($this->helpers->format_currency(0)); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span><?php esc_html_e('Discount:', 'obydullah-restaurant-pos-lite'); ?></span>
                            <div style="display: flex; align-items: center;">
                                <input type="text" id="discount-amount"
                                    style="width: 80px; padding: 5px; border: 1px solid #ccc; border-radius: 4px; text-align: right;"
                                    placeholder="0.00">
                                <button id="apply-discount"
                                    style="margin-left: 5px; padding: 5px 10px; background-color: #2196F3; color: white; border: none; border-radius: 4px;"><?php esc_html_e('Apply', 'obydullah-restaurant-pos-lite'); ?></button>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span><?php esc_html_e('Delivery Cost:', 'obydullah-restaurant-pos-lite'); ?></span>
                            <div style="display: flex; align-items: center;">
                                <input type="text" id="delivery-cost"
                                    style="width: 80px; padding: 5px; border: 1px solid #ccc; border-radius: 4px; text-align: right;"
                                    placeholder="0.00" value="0.00">
                            </div>
                        </div>
                        <!-- VAT Line (only shows if VAT is enabled) -->
                        <div style="display: <?php echo $is_vat_enabled ? 'flex' : 'none'; ?>; justify-content: space-between; margin-bottom: 10px;"
                            id="vat-line">
                            <span
                                id="vat-label"><?php printf(esc_html__('VAT (%s%%):', 'obydullah-restaurant-pos-lite'), esc_html($vat_rate)); ?></span>
                            <span id="vat-amount"><?php echo esc_html($this->helpers->format_currency(0)); ?></span>
                        </div>

                        <!-- TAX Line (only shows if TAX is enabled) -->
                        <div style="display: <?php echo esc_attr($this->helpers->is_tax_enabled() ? 'flex' : 'none'); ?>; justify-content: space-between; margin-bottom: 10px;"
                            id="tax-line">
                            <span
                                id="tax-label"><?php printf(esc_html__('TAX (%s%%):', 'obydullah-restaurant-pos-lite'), esc_html($this->helpers->get_tax_rate())); ?></span>
                            <span id="tax-amount"><?php echo esc_html($this->helpers->format_currency(0)); ?></span>
                        </div>
                        <div
                            style="display: flex; justify-content: space-between; font-weight: bold; font-size: 18px; border-top: 1px solid #ddd; padding-top: 10px;">
                            <span><?php esc_html_e('Total:', 'obydullah-restaurant-pos-lite'); ?></span>
                            <span id="total-amount"><?php echo esc_html($this->helpers->format_currency(0)); ?></span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div style="display: flex; gap: 10px;">
                        <button id="clear-cart"
                            style="flex: 1; padding: 12px; background-color: #f44336; color: white; border: none; border-radius: 4px; font-size: 16px;"><?php esc_html_e('Cancel', 'obydullah-restaurant-pos-lite'); ?></button>
                        <button id="save-sale"
                            style="flex: 1; padding: 12px; background-color: #FF9800; color: white; border: none; border-radius: 4px; font-size: 16px;"><?php esc_html_e('Save Sale', 'obydullah-restaurant-pos-lite'); ?></button>
                        <button id="complete-sale"
                            style="flex: 1; padding: 12px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; font-size: 16px;"><?php esc_html_e('Complete Sale', 'obydullah-restaurant-pos-lite'); ?></button>
                    </div>
                </div>
            </div>

            <!-- Success Modal -->
            <div id="success-modal"
                style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; justify-content: center; align-items: center;">
                <div
                    style="background: white; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-width: 400px; width: 90%;">
                    <div
                        style="width: 80px; height: 80px; background: #4CAF50; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"
                            stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>

                    <h2 style="margin: 0 0 10px 0; color: #333; font-size: 24px;">
                        <?php esc_html_e('Sale Completed Successfully!', 'obydullah-restaurant-pos-lite'); ?>
                    </h2>
                    <p style="margin: 0 0 25px 0; color: #666; font-size: 16px; line-height: 1.5;">
                        <?php esc_html_e('Invoice:', 'obydullah-restaurant-pos-lite'); ?> <strong
                            id="modal-invoice-id">-</strong><br>
                        <?php esc_html_e('Total:', 'obydullah-restaurant-pos-lite'); ?> <strong
                            id="modal-total-amount"><?php echo esc_html($this->helpers->format_currency(0)); ?></strong>
                    </p>

                    <div style="display: flex; gap: 12px; justify-content: center;">
                        <button id="modal-print-receipt"
                            style="padding: 12px 20px; background-color: #2196F3; color: white; border: none; border-radius: 6px; font-size: 15px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 500;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                <rect x="6" y="14" width="12" height="8"></rect>
                            </svg>
                            <?php esc_html_e('Print Receipt', 'obydullah-restaurant-pos-lite'); ?>
                        </button>

                        <button id="modal-new-order"
                            style="padding: 12px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 6px; font-size: 15px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 500;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <?php esc_html_e('New Order', 'obydullah-restaurant-pos-lite'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            jQuery(document).ready(function ($) {
                let cart = [];
                let selectedCustomer = null;
                let currentOrderType = 'dineIn';
                let currentCookingInstructions = '';
                let currentSavedSaleId = null;

                // Currency settings from PHP
                const currencySettings = {
                    symbol: '<?php echo esc_js($currency_symbol); ?>',
                    position: '<?php echo esc_js($currency_position); ?>'
                };

                const shopInfo = {
                    name: '<?php echo esc_js($shop_info['name']); ?>',
                    address: '<?php echo esc_js($shop_info['address']); ?>',
                    phone: '<?php echo esc_js($shop_info['phone']); ?>'
                };

                const vatSettings = {
                    rate: <?php echo esc_js($vat_rate); ?>,
                    enabled: <?php echo $is_vat_enabled ? 'true' : 'false'; ?>
                };

                const taxSettings = {
                    rate: <?php echo esc_js($this->helpers->get_tax_rate()); ?>,
                    enabled: <?php echo $this->helpers->is_tax_enabled() ? 'true' : 'false'; ?>
                };

                // Currency formatting function
                function formatCurrency(amount) {
                    const formattedAmount = parseFloat(amount).toFixed(2);

                    switch (currencySettings.position) {
                        case 'right':
                            return formattedAmount + currencySettings.symbol;
                        case 'left_space':
                            return currencySettings.symbol + ' ' + formattedAmount;
                        case 'right_space':
                            return formattedAmount + ' ' + currencySettings.symbol;
                        case 'left':
                        default:
                            return currencySettings.symbol + formattedAmount;
                    }
                }

                // Load categories and customers
                loadCategories();
                loadCustomers();

                // Show/hide delivery cost based on order type
                function toggleDeliveryCost() {
                    if (currentOrderType === 'takeAway') {
                        $('#delivery-cost').closest('div').show();
                    } else {
                        $('#delivery-cost').closest('div').hide();
                        $('#delivery-cost').val('0.00');
                    }

                    // Update totals
                    let subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                    updateTotals(subtotal);
                }

                // Tab switching functionality
                function switchTab(activeTab) {
                    // Reset all tabs
                    $('#dineInTab').css('background-color', '#e0e0e0');
                    $('#takeAwayTab').css('background-color', '#e0e0e0');
                    $('#pickupTab').css('background-color', '#e0e0e0');

                    // Hide all options
                    $('#dineInOptions').hide();
                    $('#takeAwayOptions').hide();
                    $('#pickupOptions').hide();

                    // Activate selected tab
                    $('#' + activeTab + 'Tab').css('background-color', '#4CAF50');
                    $('#' + activeTab + 'Options').show();

                    // Update current order type
                    currentOrderType = activeTab;

                    // Toggle delivery cost visibility
                    toggleDeliveryCost();
                }

                // Load customers from database
                function loadCustomers() {
                    $.ajax({
                        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                        type: 'GET',
                        data: {
                            action: 'orpl_get_customers_for_pos',
                            nonce: '<?php echo esc_js(wp_create_nonce("orpl_get_customers_for_pos")); ?>'
                        },
                        success: function (response) {
                            if (response.success) {
                                let select = $('#customer-select');
                                select.empty();
                                select.append('<option value=""><?php esc_html_e('Walk-in Customer', 'obydullah-restaurant-pos-lite'); ?></option>');

                                $.each(response.data, function (_, customer) {
                                    select.append(
                                        $('<option>').val(customer.id).text(customer.name + ' (' + customer.email + ')')
                                    );
                                });
                            }
                        }
                    });
                }

                // Load categories from database
                function loadCategories() {
                    $.ajax({
                        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                        type: 'GET',
                        data: {
                            action: 'orpl_get_categories_for_pos',
                            nonce: '<?php echo esc_js(wp_create_nonce("orpl_get_categories_for_pos")); ?>'
                        },
                        success: function (response) {
                            if (response.success) {
                                let container = $('#category-buttons');
                                container.empty();

                                // Add "All" category button
                                container.append(
                                    '<button class="category-btn active" data-category-id="all" style="padding: 8px 12px; margin-right: 5px; background-color: #4CAF50; color: white; border: none; border-radius: 4px;"><?php esc_html_e('All', 'obydullah-restaurant-pos-lite'); ?></button>'
                                );

                                // Add category buttons
                                $.each(response.data, function (_, category) {
                                    container.append(
                                        '<button class="category-btn" data-category-id="' + category.id + '" style="padding: 8px 12px; margin-right: 5px; background-color: #e0e0e0; border: none; border-radius: 4px;">' + category.name + '</button>'
                                    );
                                });

                                // Load initial products (all)
                                loadProducts('all');
                            }
                        }
                    });
                }

                // Load products by category (or all if categoryId is 'all')
                function loadProducts(categoryId) {
                    $('#product-grid').html('<div style="text-align: center; padding: 40px; color: #666;"><div class="spinner is-active" style="float: none; margin: 0 auto;"></div><p><?php esc_html_e('Loading stock items...', 'obydullah-restaurant-pos-lite'); ?></p></div>');

                    $.ajax({
                        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                        type: 'GET',
                        data: {
                            action: 'orpl_get_products_by_category',
                            category_id: categoryId,
                            nonce: '<?php echo esc_js(wp_create_nonce("orpl_get_products_by_category")); ?>'
                        },
                        success: function (response) {
                            let container = $('#product-grid').empty();
                            if (response.success && response.data.length > 0) {
                                $.each(response.data, function (_, product) {
                                    let outOfStock = product.quantity <= 0;
                                    let productCard = $(
                                        '<div class="product-card" style="border: 1px solid #ddd; padding: 12px; text-align: center; background-color: white; border-radius: 5px; position: relative; cursor: pointer; transition: all 0.2s ease; min-height: 120px; display: flex; flex-direction: column; justify-content: center;' + (outOfStock ? ' opacity: 0.5; cursor: not-allowed;' : '') + '" data-product-id="' + product.id + '" data-product-name="' + product.name + '" data-product-price="' + product.sale_cost + '" data-stock="' + product.quantity + '">' +
                                        '<h4 style="margin: 8px 0; font-size: 14px; font-weight: 600; color: #333; line-height: 1.3; height: auto; min-height: 36px; display: flex; align-items: center; justify-content: center; overflow: hidden; text-overflow: ellipsis;">' + product.name + '</h4>' +
                                        '<p style="margin: 5px 0; color: #4CAF50; font-weight: bold; font-size: 15px;">' + formatCurrency(product.sale_cost) + '</p>' +
                                        '<p style="margin: 3px 0; font-size: 11px; color: #666;">Stock: ' + product.quantity + '</p>' +
                                        (outOfStock ?
                                            '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); background: #f44336; color: white; padding: 3px 12px; font-size: 10px; font-weight: bold; z-index: 2;"><?php esc_html_e('SOLD OUT', 'obydullah-restaurant-pos-lite'); ?></div>' :
                                            ''
                                        ) +
                                        '</div>'
                                    );

                                    container.append(productCard);
                                });
                            } else {
                                container.html('<div style="text-align: center; padding: 40px; color: #666;"><p><?php esc_html_e('No stock items found in this category', 'obydullah-restaurant-pos-lite'); ?></p></div>');
                            }

                            // Add click handlers for product cards
                            initializeProductCardHandlers();
                        }
                    });
                }

                function initializeProductCardHandlers() {
                    // Add click handlers for entire product cards
                    $('.product-card').on('click', function (e) {
                        if ($(this).css('opacity') !== '0.5') { // Check if not out of stock
                            let productId = $(this).data('product-id');
                            let productName = $(this).data('product-name');
                            let productPrice = $(this).data('product-price');
                            let stock = $(this).data('stock');

                            if (productId) {
                                addToCart(productId, productName, productPrice, stock);

                                // Add visual feedback
                                $(this).css('transform', 'scale(0.95)');
                                setTimeout(() => {
                                    $(this).css('transform', 'scale(1)');
                                }, 150);
                            }
                        }
                    });
                }

                // Customer selection change handler
                $('#customer-select').on('change', function () {
                    let customerId = $(this).val();

                    if (customerId) {
                        // Find selected customer from loaded data
                        $.ajax({
                            url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                            type: 'GET',
                            data: {
                                action: 'orpl_get_customers_for_pos',
                                nonce: '<?php echo esc_js(wp_create_nonce("orpl_get_customers_for_pos")); ?>'
                            },
                            success: function (response) {
                                if (response.success) {
                                    let customer = response.data.find(c => c.id == customerId);
                                    if (customer) {
                                        selectedCustomer = customer;
                                        autoFillCustomerDetails(customer);
                                    }
                                }
                            }
                        });
                    } else {
                        selectedCustomer = null;
                        clearCustomerDetails();
                    }
                });

                // Auto-fill customer details in all forms
                function autoFillCustomerDetails(customer) {
                    // Take Away form
                    $('#takeaway-name').val(customer.name);
                    $('#takeaway-email').val(customer.email);
                    $('#takeaway-mobile').val(customer.mobile || '');
                    $('#takeaway-address').val(customer.address || '');

                    // Pickup form
                    $('#pickup-name').val(customer.name);
                    $('#pickup-mobile').val(customer.mobile || '');
                }

                // Clear customer details
                function clearCustomerDetails() {
                    // Take Away form
                    $('#takeaway-name').val('');
                    $('#takeaway-email').val('');
                    $('#takeaway-mobile').val('');
                    $('#takeaway-address').val('');

                    // Pickup form
                    $('#pickup-name').val('');
                    $('#pickup-mobile').val('');
                }

                // Category button click handlers
                $(document).on('click', '.category-btn', function () {
                    $('.category-btn').css('background-color', '#e0e0e0').css('color', '#000');
                    $(this).css('background-color', '#4CAF50').css('color', 'white');

                    let categoryId = $(this).data('category-id');
                    loadProducts(categoryId);
                });

                // Add to cart function
                function addToCart(productId, productName, price, stock) {
                    let existingItem = cart.find(item => item.id === productId);

                    if (existingItem) {
                        if (existingItem.quantity < stock) {
                            existingItem.quantity++;
                        } else {
                            alert('<?php esc_html_e('Not enough stock available!', 'obydullah-restaurant-pos-lite'); ?>');
                            return;
                        }
                    } else {
                        cart.push({
                            id: productId,
                            name: productName,
                            price: parseFloat(price),
                            quantity: 1,
                            stock: parseInt(stock)
                        });
                    }

                    updateCartDisplay();
                }

                // Update cart display
                function updateCartDisplay() {
                    let container = $('#cart-items');

                    if (cart.length === 0) {
                        container.html('<tr><td colspan="4" style="padding: 20px; text-align: center; color: #666;"><?php esc_html_e('No items in cart', 'obydullah-restaurant-pos-lite'); ?></td></tr>');
                    } else {
                        container.empty();
                        let subtotal = 0;

                        $.each(cart, function (_, item) {
                            let itemTotal = item.price * item.quantity;
                            subtotal += itemTotal;

                            container.append(
                                '<tr>' +
                                '<td style="padding: 10px; border-bottom: 1px solid #eee;">' + item.name + '</td>' +
                                '<td style="padding: 10px; text-align: center; border-bottom: 1px solid #eee;">' +
                                '<button class="decrease-qty" data-product-id="' + item.id + '" style="padding: 2px 8px; background-color: #f44336; color: white; border: none; border-radius: 3px;">-</button>' +
                                '<span style="margin: 0 10px;">' + item.quantity + '</span>' +
                                '<button class="increase-qty" data-product-id="' + item.id + '" style="padding: 2px 8px; background-color: #4CAF50; color: white; border: none; border-radius: 3px;">+</button>' +
                                '</td>' +
                                '<td style="padding: 10px; text-align: right; border-bottom: 1px solid #eee;">' + formatCurrency(item.price) + '</td>' +
                                '<td style="padding: 10px; text-align: right; border-bottom: 1px solid #eee;">' + formatCurrency(itemTotal) + '</td>' +
                                '</tr>'
                            );
                        });

                        updateTotals(subtotal);
                    }

                    // Add quantity button handlers
                    $('.decrease-qty').on('click', function () {
                        let productId = $(this).data('product-id');
                        updateQuantity(productId, -1);
                    });

                    $('.increase-qty').on('click', function () {
                        let productId = $(this).data('product-id');
                        updateQuantity(productId, 1);
                    });
                }

                // Update item quantity
                function updateQuantity(productId, change) {
                    let item = cart.find(item => item.id === productId);

                    if (item) {
                        item.quantity += change;

                        if (item.quantity <= 0) {
                            cart = cart.filter(item => item.id !== productId);
                        }

                        if (item.quantity > item.stock) {
                            item.quantity = item.stock;
                            alert('<?php esc_html_e('Not enough stock available!', 'obydullah-restaurant-pos-lite'); ?>');
                        }

                        updateCartDisplay();
                    }
                }

                // Update totals with clean VAT and TAX calculations
                function updateTotals(subtotal) {
                    let discount = parseFloat($('#discount-amount').val()) || 0;
                    let deliveryCost = parseFloat($('#delivery-cost').val()) || 0;
                    let taxableAmount = subtotal - discount;

                    // VAT calculation
                    let vatAmount = 0;
                    if (vatSettings.enabled) {
                        vatAmount = (taxableAmount * vatSettings.rate) / 100;
                    }

                    // TAX calculation
                    let taxAmount = 0;
                    if (taxSettings.enabled) {
                        taxAmount = (taxableAmount * taxSettings.rate) / 100;
                    }

                    let total = taxableAmount + vatAmount + taxAmount + deliveryCost;

                    // Update display
                    $('#subtotal').text(formatCurrency(subtotal));

                    // Update VAT
                    if (vatSettings.enabled) {
                        $('#vat-line').show();
                        $('#vat-amount').text(formatCurrency(vatAmount));
                    } else {
                        $('#vat-line').hide();
                    }

                    // Update TAX
                    if (taxSettings.enabled) {
                        $('#tax-line').show();
                        $('#tax-amount').text(formatCurrency(taxAmount));
                    } else {
                        $('#tax-line').hide();
                    }

                    $('#total-amount').text(formatCurrency(total));
                }

                // Apply discount
                $('#apply-discount').on('click', function () {
                    let subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                    updateTotals(subtotal);
                });

                // Delivery cost change
                $('#delivery-cost').on('input', function () {
                    let subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                    updateTotals(subtotal);
                });

                // Clear cart
                $('#clear-cart').on('click', function () {
                    if (confirm('<?php esc_html_e('Clear all items from cart?', 'obydullah-restaurant-pos-lite'); ?>')) {
                        cart = [];
                        currentSavedSaleId = null;
                        updateCartDisplay();
                        $('#discount-amount').val('');
                        $('#delivery-cost').val('0.00');
                    }
                });

                // Tab click handlers
                $('#dineInTab').on('click', function () {
                    switchTab('dineIn');
                });

                $('#takeAwayTab').on('click', function () {
                    switchTab('takeAway');
                });

                $('#pickupTab').on('click', function () {
                    switchTab('pickup');
                });

                // Cooking instructions tracking
                $('#dinein-instructions, #takeaway-instructions, #pickup-instructions').on('input', function () {
                    currentCookingInstructions = $(this).val();
                });

                // Show/hide saved sales panel
                $('#show-saved-sales').on('click', function () {
                    loadSavedSales();
                    $('#saved-sales-panel').show();
                });

                $('#close-saved-sales').on('click', function () {
                    $('#saved-sales-panel').hide();
                });

                // Load saved sales
                function loadSavedSales() {
                    $.ajax({
                        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                        type: 'GET',
                        data: {
                            action: 'orpl_get_saved_sales',
                            nonce: '<?php echo esc_js(wp_create_nonce("orpl_get_saved_sales")); ?>'
                        },
                        success: function (response) {
                            if (response.success) {
                                let container = $('#saved-sales-list').empty();

                                if (response.data.length === 0) {
                                    container.html('<p style="text-align: center; color: #666;"><?php esc_html_e('No saved sales found.', 'obydullah-restaurant-pos-lite'); ?></p>');
                                    return;
                                }

                                $.each(response.data, function (_, sale) {
                                    let saleItem = $(
                                        '<div style="border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 4px; background: #f9f9f9;">' +
                                        '<div style="display: flex; justify-content: space-between; align-items: center;">' +
                                        '<div>' +
                                        '<strong>#' + sale.invoice_id + '</strong><br>' +
                                        '<small>' + sale.customer_name + '</small><br>' +
                                        '<small>' + formatCurrency(sale.grand_total) + '</small>' +
                                        '</div>' +
                                        '<button class="button button-small load-saved-sale" data-sale-id="' + sale.id + '"><?php esc_html_e('Load', 'obydullah-restaurant-pos-lite'); ?></button>' +
                                        '</div>' +
                                        '</div>'
                                    );
                                    container.append(saleItem);
                                });

                                // Add click handlers for load buttons
                                $('.load-saved-sale').on('click', function () {
                                    let saleId = $(this).data('sale-id');
                                    loadSavedSale(saleId);
                                });
                            }
                        }
                    });
                }

                // Load a specific saved sale
                function loadSavedSale(saleId) {
                    $.ajax({
                        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                        type: 'GET',
                        data: {
                            action: 'orpl_load_saved_sale',
                            sale_id: saleId,
                            nonce: '<?php echo esc_js(wp_create_nonce("orpl_load_saved_sale")); ?>'
                        },
                        success: function (response) {
                            if (response.success) {
                                // Clear current cart
                                cart = [];
                                currentSavedSaleId = saleId;

                                // Load items into cart
                                $.each(response.data.items, function (_, item) {
                                    cart.push({
                                        id: item.fk_product_id,
                                        name: item.product_name,
                                        price: parseFloat(item.unit_price),
                                        quantity: parseInt(item.quantity),
                                        stock: parseInt(item.stock_quantity)
                                    });
                                });

                                // Load customer if exists
                                if (response.data.sale.fk_customer_id) {
                                    $('#customer-select').val(response.data.sale.fk_customer_id);
                                }

                                // Load order type and details
                                currentOrderType = response.data.sale.sale_type;
                                switchTab(currentOrderType);

                                // Load form data based on order type
                                if (currentOrderType === 'dineIn') {
                                    $('#table-number').val(response.data.sale.note || '');
                                    $('#dinein-instructions').val(response.data.sale.cooking_instructions || '');
                                } else if (currentOrderType === 'takeAway') {
                                    $('#takeaway-instructions').val(response.data.sale.cooking_instructions || '');
                                } else if (currentOrderType === 'pickup') {
                                    $('#pickup-instructions').val(response.data.sale.cooking_instructions || '');
                                }

                                // Load discount and delivery
                                $('#discount-amount').val(response.data.sale.discount_amount || '');
                                $('#delivery-cost').val(response.data.sale.shipping_cost || '0.00');

                                // Update display
                                updateCartDisplay();
                                $('#saved-sales-panel').hide();

                                alert('<?php esc_html_e('Saved sale loaded. You can now modify and complete it.', 'obydullah-restaurant-pos-lite'); ?>');
                            } else {
                                alert('Error: ' + response.data);
                            }
                        }
                    });
                }

                // Complete Sale button handler
                $('#complete-sale').on('click', function () {
                    processSale('complete');
                });

                // Save Sale button handler  
                $('#save-sale').on('click', function () {
                    processSale('save');
                });


                // Process sale function
                function processSale(action) {
                    if (cart.length === 0) {
                        alert('<?php esc_html_e('Please add items to cart before processing sale.', 'obydullah-restaurant-pos-lite'); ?>');
                        return;
                    }

                    // Validate required fields based on order type
                    if (!validateOrderDetails()) {
                        return;
                    }

                    // Get cooking instructions based on current order type
                    let cookingInstructions = '';
                    if (currentOrderType === 'dineIn') {
                        cookingInstructions = $('#dinein-instructions').val();
                    } else if (currentOrderType === 'takeAway') {
                        cookingInstructions = $('#takeaway-instructions').val();
                    } else if (currentOrderType === 'pickup') {
                        cookingInstructions = $('#pickup-instructions').val();
                    }

                    // Store current cart and form data BEFORE sending AJAX
                    const currentCartData = [...cart];
                    const currentFormData = {
                        orderType: currentOrderType,
                        tableNumber: $('#table-number').val().trim(),
                        takeawayName: $('#takeaway-name').val().trim(),
                        takeawayAddress: $('#takeaway-address').val().trim(),
                        takeawayEmail: $('#takeaway-email').val().trim(),
                        takeawayMobile: $('#takeaway-mobile').val().trim(),
                        pickupName: $('#pickup-name').val().trim(),
                        pickupMobile: $('#pickup-mobile').val().trim(),
                        cookingInstructions: cookingInstructions
                    };

                    // Prepare sale data
                    let saleData = {
                        customer_id: $('#customer-select').val() || null,
                        items: currentCartData.map(item => ({
                            product_id: item.id,
                            name: item.name,
                            price: item.price,
                            quantity: item.quantity
                        })),
                        discount: $('#discount-amount').val() || 0,
                        delivery_cost: $('#delivery-cost').val() || 0,
                        order_type: currentOrderType,
                        cooking_instructions: cookingInstructions,
                        note: getOrderSpecificNotes(),
                        action: action,
                        saved_sale_id: currentSavedSaleId
                    };

                    // Show processing indicator
                    const button = $(`#${action === 'complete' ? 'complete-sale' : 'save-sale'}`);
                    const originalText = button.text();
                    button.text('<?php esc_html_e('Processing...', 'obydullah-restaurant-pos-lite'); ?>').prop('disabled', true);

                    // Send AJAX request
                    $.ajax({
                        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                        type: 'POST',
                        data: {
                            action: 'orpl_process_sale',
                            sale_data: JSON.stringify(saleData),
                            nonce: '<?php echo esc_js(wp_create_nonce("orpl_process_sale")); ?>'
                        },
                        success: function (response) {
                            if (response.success) {
                                if (action === 'complete') {
                                    showInvoiceSummary(
                                        response.data.invoice_id,
                                        response.data.sale_id,
                                        currentCartData,
                                        currentFormData,
                                        saleData
                                    );
                                } else {
                                    resetPOS();
                                    alert('<?php esc_html_e('Sale saved successfully!', 'obydullah-restaurant-pos-lite'); ?>');
                                }
                            } else {
                                alert('<?php esc_html_e('Error:', 'obydullah-restaurant-pos-lite'); ?> ' + response.data);
                            }
                        },
                        error: function (xhr, status, error) {
                            alert('<?php esc_html_e('Error processing sale:', 'obydullah-restaurant-pos-lite'); ?> ' + error);
                        },
                        complete: function () {
                            button.text(originalText).prop('disabled', false);
                        }
                    });
                }

                // Validate order details based on order type
                function validateOrderDetails() {
                    if (currentOrderType === 'dineIn') {
                        const tableNumber = $('#table-number').val().trim();
                        if (!tableNumber) {
                            alert('<?php esc_html_e('Please enter table number for Dine In order.', 'obydullah-restaurant-pos-lite'); ?>');
                            $('#table-number').focus();
                            return false;
                        }
                    } else if (currentOrderType === 'takeAway') {
                        const customerName = $('#takeaway-name').val().trim();
                        if (!customerName) {
                            alert('<?php esc_html_e('Please enter customer name for Take Away order.', 'obydullah-restaurant-pos-lite'); ?>');
                            $('#takeaway-name').focus();
                            return false;
                        }
                    } else if (currentOrderType === 'pickup') {
                        const customerName = $('#pickup-name').val().trim();
                        if (!customerName) {
                            alert('<?php esc_html_e('Please enter customer name for Pickup order.', 'obydullah-restaurant-pos-lite'); ?>');
                            $('#pickup-name').focus();
                            return false;
                        }
                    }
                    return true;
                }

                // Get order-specific notes
                function getOrderSpecificNotes() {
                    let notes = [];

                    if (currentOrderType === 'dineIn') {
                        const tableNumber = $('#table-number').val().trim();
                        if (tableNumber) notes.push(`<?php esc_html_e('Table:', 'obydullah-restaurant-pos-lite'); ?> ${tableNumber}`);
                    } else if (currentOrderType === 'takeAway') {
                        const customerName = $('#takeaway-name').val().trim();
                        const address = $('#takeaway-address').val().trim();
                        const email = $('#takeaway-email').val().trim();
                        const mobile = $('#takeaway-mobile').val().trim();

                        if (customerName) notes.push(`<?php esc_html_e('Customer:', 'obydullah-restaurant-pos-lite'); ?> ${customerName}`);
                        if (address) notes.push(`<?php esc_html_e('Address:', 'obydullah-restaurant-pos-lite'); ?> ${address}`);
                        if (email) notes.push(`<?php esc_html_e('Email:', 'obydullah-restaurant-pos-lite'); ?> ${email}`);
                        if (mobile) notes.push(`<?php esc_html_e('Mobile:', 'obydullah-restaurant-pos-lite'); ?> ${mobile}`);
                    } else if (currentOrderType === 'pickup') {
                        const customerName = $('#pickup-name').val().trim();
                        const mobile = $('#pickup-mobile').val().trim();

                        if (customerName) notes.push(`<?php esc_html_e('Customer:', 'obydullah-restaurant-pos-lite'); ?> ${customerName}`);
                        if (mobile) notes.push(`<?php esc_html_e('Mobile:', 'obydullah-restaurant-pos-lite'); ?> ${mobile}`);
                    }

                    return notes.join(' | ');
                }

                // Reset POS after successful sale
                function resetPOS() {
                    cart = [];
                    currentSavedSaleId = null;
                    updateCartDisplay();
                    $('#discount-amount').val('');
                    $('#delivery-cost').val('0.00');
                    $('#table-number').val('');
                    $('#dinein-instructions').val('');
                    $('#takeaway-name').val('');
                    $('#takeaway-address').val('');
                    $('#takeaway-email').val('');
                    $('#takeaway-mobile').val('');
                    $('#takeaway-instructions').val('');
                    $('#pickup-name').val('');
                    $('#pickup-mobile').val('');
                    $('#pickup-instructions').val('');
                    $('#customer-select').val('');
                    selectedCustomer = null;
                    switchTab('dineIn');
                }

                // Show success modal after sale completion
                function showInvoiceSummary(invoiceId, saleId, cartData, formData, saleData) {
                    // Calculate totals from the cart data that was passed
                    const subtotal = cartData.reduce((sum, item) => sum + (item.price * item.quantity), 0);
                    const discount = parseFloat(saleData.discount) || 0;
                    const deliveryCost = parseFloat(saleData.delivery_cost) || 0;
                    const taxableAmount = subtotal - discount;

                    // Clean VAT and TAX calculations
                    let vatAmount = 0;
                    if (vatSettings.enabled) {
                        vatAmount = (taxableAmount * vatSettings.rate) / 100;
                    }

                    let taxAmount = 0;
                    if (taxSettings.enabled) {
                        taxAmount = (taxableAmount * taxSettings.rate) / 100;
                    }

                    const total = taxableAmount + vatAmount + taxAmount + deliveryCost;

                    // Store sale info for receipt printing
                    window.lastSaleInfo = {
                        invoiceId: invoiceId,
                        saleId: saleId,
                        subtotal: subtotal,
                        discount: discount,
                        deliveryCost: deliveryCost,
                        vatAmount: vatAmount,
                        taxAmount: taxAmount,
                        total: total,
                        cartData: cartData,
                        formData: formData,
                        saleData: saleData,
                        vatSettings: vatSettings,
                        taxSettings: taxSettings
                    };

                    // Update modal content
                    $('#modal-invoice-id').text(invoiceId);
                    $('#modal-total-amount').text(formatCurrency(total));

                    // Show modal
                    $('#success-modal').css('display', 'flex');

                    // Reset POS only after storing all the data
                    resetPOS();
                }
                // Print receipt function
                function printReceipt() {
                    if (!window.lastSaleInfo) {
                        alert('<?php esc_html_e('No sale information available for printing.', 'obydullah-restaurant-pos-lite'); ?>');
                        return;
                    }

                    const saleInfo = window.lastSaleInfo;
                    const cartData = saleInfo.cartData;
                    const formData = saleInfo.formData;

                    // Create receipt content
                    let receiptContent = `
        <div style="width: 300px; font-family: 'Courier New', monospace; font-size: 12px; padding: 20px;">
            <div style="text-align: center; margin-bottom: 15px;">
                <h2 style="margin: 0 0 5px 0; font-size: 18px;">${shopInfo.name}</h2>
                ${shopInfo.address ? '<p style="margin: 0; font-size: 11px;">' + shopInfo.address + '</p>' : ''}
                ${shopInfo.phone ? '<p style="margin: 0; font-size: 11px;"><?php esc_html_e('Phone:', 'obydullah-restaurant-pos-lite'); ?> ' + shopInfo.phone + '</p>' : ''}
            </div>
            
            <hr style="border: 1px dashed #000; margin: 10px 0;">
            
            <div style="margin-bottom: 10px;">
                <div style="display: flex; justify-content: space-between;">
                    <span><?php esc_html_e('Invoice #:', 'obydullah-restaurant-pos-lite'); ?></span>
                    <span>${saleInfo.invoiceId}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span><?php esc_html_e('Date:', 'obydullah-restaurant-pos-lite'); ?></span>
                    <span>${new Date().toLocaleString()}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span><?php esc_html_e('Order Type:', 'obydullah-restaurant-pos-lite'); ?></span>
                    <span>${formData.orderType.charAt(0).toUpperCase() + formData.orderType.slice(1)}</span>
                </div>
                <div>
    `;

                    // Add customer info based on order type
                    if (formData.orderType === 'dineIn') {
                        receiptContent += formData.tableNumber ? `<div><?php esc_html_e('Table:', 'obydullah-restaurant-pos-lite'); ?> ${formData.tableNumber}</div>` : '<div><?php esc_html_e('Walk-in Customer', 'obydullah-restaurant-pos-lite'); ?></div>';
                    } else if (formData.orderType === 'takeAway') {
                        receiptContent += `<div><?php esc_html_e('Customer:', 'obydullah-restaurant-pos-lite'); ?> ${formData.takeawayName || '<?php esc_html_e('Walk-in', 'obydullah-restaurant-pos-lite'); ?>'}</div>`;
                        if (formData.takeawayAddress) receiptContent += `<div><?php esc_html_e('Address:', 'obydullah-restaurant-pos-lite'); ?> ${formData.takeawayAddress}</div>`;
                        if (formData.takeawayMobile) receiptContent += `<div><?php esc_html_e('Mobile:', 'obydullah-restaurant-pos-lite'); ?> ${formData.takeawayMobile}</div>`;
                    } else if (formData.orderType === 'pickup') {
                        receiptContent += `<div><?php esc_html_e('Customer:', 'obydullah-restaurant-pos-lite'); ?> ${formData.pickupName || '<?php esc_html_e('Walk-in', 'obydullah-restaurant-pos-lite'); ?>'}</div>`;
                        if (formData.pickupMobile) receiptContent += `<div><?php esc_html_e('Mobile:', 'obydullah-restaurant-pos-lite'); ?> ${formData.pickupMobile}</div>`;
                    }

                    receiptContent += `
                </div>
            </div>
            
            <hr style="border: 1px dashed #000; margin: 10px 0;">
            
            <div style="margin-bottom: 10px;">
                <div style="display: flex; justify-content: space-between; font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 5px;">
                    <span style="flex: 2;"><?php esc_html_e('Item', 'obydullah-restaurant-pos-lite'); ?></span>
                    <span style="flex: 1; text-align: center;"><?php esc_html_e('Qty x Price', 'obydullah-restaurant-pos-lite'); ?></span>
                    <span style="flex: 1; text-align: right;"><?php esc_html_e('Total', 'obydullah-restaurant-pos-lite'); ?></span>
                </div>
    `;

                    // Add items from the stored cart data
                    cartData.forEach(item => {
                        const itemTotal = item.price * item.quantity;
                        receiptContent += `
            <div style="display: flex; justify-content: space-between; margin-bottom: 3px;">
                <div style="flex: 2; font-weight: bold;">${item.name}</div>
                <div style="flex: 1; text-align: center;">${item.quantity} x ${formatCurrency(item.price)}</div>
                <div style="flex: 1; text-align: right;">${formatCurrency(itemTotal)}</div>
            </div>
        `;
                    });

                    receiptContent += `
            </div>
            
            <hr style="border: 1px dashed #000; margin: 10px 0;">
            
            <div style="margin-bottom: 10px;">
                <div style="display: flex; justify-content: space-between;">
                    <span><?php esc_html_e('Subtotal:', 'obydullah-restaurant-pos-lite'); ?></span>
                    <span>${formatCurrency(saleInfo.subtotal)}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span><?php esc_html_e('Discount:', 'obydullah-restaurant-pos-lite'); ?></span>
                    <span>${formatCurrency(saleInfo.discount || 0)}</span>
                </div>
    `;

                    // Only show delivery if it has value greater than 0
                    if (saleInfo.deliveryCost && saleInfo.deliveryCost > 0) {
                        receiptContent += `
                <div style="display: flex; justify-content: space-between;">
                    <span><?php esc_html_e('Delivery Cost:', 'obydullah-restaurant-pos-lite'); ?></span>
                    <span>${formatCurrency(saleInfo.deliveryCost)}</span>
                </div>
        `;
                    }

                    // Always show VAT (use 0 if undefined)
                    receiptContent += `
                <div style="display: flex; justify-content: space-between;">
              <span><?php printf(esc_html__('VAT (%s%%):', 'obydullah-restaurant-pos-lite'), esc_html($vat_rate)); ?></span>
                    <span>${formatCurrency(saleInfo.vatAmount || 0)}</span>
                </div>
    `;

                    // Always show TAX (use 0 if undefined)
                    receiptContent += `
                <div style="display: flex; justify-content: space-between;">
<span><?php printf(esc_html__('TAX (%s%%):', 'obydullah-restaurant-pos-lite'), esc_html($this->helpers->get_tax_rate())); ?></span>
                    <span>${formatCurrency(saleInfo.taxAmount || 0)}</span>
                </div>
    `;

                    receiptContent += `
                <div style="display: flex; justify-content: space-between; font-weight: bold; border-top: 1px solid #000; padding-top: 5px;">
                    <span><?php esc_html_e('TOTAL:', 'obydullah-restaurant-pos-lite'); ?></span>
                    <span>${formatCurrency(saleInfo.total)}</span>
                </div>
            </div>
    `;

                    // Add cooking instructions if available
                    if (formData.cookingInstructions) {
                        receiptContent += `
            <hr style="border: 1px dashed #000; margin: 10px 0;">
            <div style="margin-bottom: 10px;">
                <div style="font-weight: bold; margin-bottom: 5px;"><?php esc_html_e('Cooking Instructions:', 'obydullah-restaurant-pos-lite'); ?></div>
                <div style="font-size: 11px;">${formData.cookingInstructions}</div>
            </div>
        `;
                    }

                    receiptContent += `
            <div style="text-align: center; margin-top: 20px;">
                <p style="margin: 5px 0; font-size: 10px;">*** <?php esc_html_e('ORDER COMPLETED', 'obydullah-restaurant-pos-lite'); ?> ***</p>
            </div>
        </div>
    `;

                    // Print the receipt
                    const printWindow = window.open('', '_blank', 'width=350,height=600');

                    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title><?php esc_html_e('Receipt', 'obydullah-restaurant-pos-lite'); ?> - ${saleInfo.invoiceId}</title>
            <style>
                body { 
                    font-family: 'Courier New', monospace; 
                    font-size: 12px; 
                    margin: 0; 
                    padding: 10px;
                    -webkit-print-color-adjust: exact;
                }
                @media print {
                    body { margin: 0; padding: 0; }
                    @page { margin: 0; }
                }
            </style>
        </head>
        <body onload="window.print(); window.close();">
            ${receiptContent}
        </body>
        </html>
    `);

            printWindow.document.close();

            // Close modal after printing
            $('#success-modal').hide();
        }

        // Modal button handlers
        $('#modal-print-receipt').on('click', function () {
            printReceipt();
        });

        $('#modal-new-order').on('click', function () {
            resetPOS();
            $('#success-modal').hide();
        });

        // Close modal when clicking outside
        $('#success-modal').on('click', function (e) {
            if (e.target === this) {
                $(this).hide();
            }
        });

        // Initialize delivery cost visibility on page load
        toggleDeliveryCost();
    });
</script>
<?php
    }

    /** Get categories for POS with caching */
    public function ajax_get_categories_for_pos()
    {
        check_ajax_referer('orpl_get_categories_for_pos', 'nonce');

        $cache_key = 'orpl_categories_pos';
        $categories = wp_cache_get($cache_key, self::CACHE_GROUP);
        $category_status = 'active';

        if (false === $categories) {
            global $wpdb;

            $categories = $wpdb->get_results($wpdb->prepare(
                "SELECT id, name 
            FROM {$this->categories_table} 
            WHERE status = %s 
            ORDER BY name ASC",
                $category_status
            ));

            wp_cache_set($cache_key, $categories, self::CACHE_GROUP, self::CACHE_EXPIRATION);
        }

        wp_send_json_success($categories);
    }
    /** Get customers for POS (no cache) */
    public function ajax_get_customers_for_pos()
    {
        check_ajax_referer('orpl_get_customers_for_pos', 'nonce');
        global $wpdb;
        $customer_status = 'active';

        $customers = $wpdb->get_results($wpdb->prepare(
            "SELECT id, name, email, mobile, address
        FROM {$this->customers_table} 
        WHERE status = %s 
        ORDER BY name ASC",
            $customer_status
        ));

        wp_send_json_success($customers);
    }

    /** Get products by category (no cache) */
    public function ajax_get_products_by_category()
    {
        check_ajax_referer('orpl_get_products_by_category', 'nonce');
        global $wpdb;

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }

        $category_id = sanitize_text_field(wp_unslash($_GET['category_id'] ?? 'all'));
        $product_status = 'active';

        $query = $wpdb->prepare(
            "
        SELECT p.id, p.name, p.image, s.sale_cost, s.quantity, s.status as stock_status
        FROM {$this->products_table} p 
        LEFT JOIN {$this->stocks_table} s ON p.id = s.fk_product_id 
        WHERE p.status = %s",
            $product_status
        );

        $query_params = array();

        if ($category_id !== 'all') {
            $query .= " AND p.fk_category_id = %d";
            $query_params[] = absint($category_id);
        }

        $query .= " ORDER BY p.name ASC LIMIT 20";

        if (!empty($query_params)) {
            $query = $wpdb->prepare($query, $query_params);
        }

        $products = $wpdb->get_results($query);

        wp_send_json_success($products);
    }

    /** Get saved sales */
    public function ajax_get_saved_sales()
    {
        check_ajax_referer('orpl_get_saved_sales', 'nonce');
        global $wpdb;
        $sale_status = 'saveSale';

        $sales = $wpdb->get_results($wpdb->prepare(
            "SELECT s.id, s.invoice_id, s.grand_total, s.created_at,
               COALESCE(c.name, 'Walk-in') as customer_name
        FROM {$this->sales_table} s
        LEFT JOIN {$this->customers_table} c ON s.fk_customer_id = c.id
        WHERE s.status = %s
        ORDER BY s.created_at DESC
        LIMIT 20",
            $sale_status
        ));

        wp_send_json_success($sales);
    }

    /** Load saved sale details */
    public function ajax_load_saved_sale()
    {
        check_ajax_referer('orpl_load_saved_sale', 'nonce');
        global $wpdb;
        $sale_status = 'saveSale';

        $sale_id = intval($_GET['sale_id'] ?? 0);

        if (!$sale_id) {
            wp_send_json_error('Invalid sale ID');
        }

        // Get sale details
        $sale = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM {$this->sales_table} WHERE id = %d AND status = '%s'
        ", $sale_id, $sale_status));

        if (!$sale) {
            wp_send_json_error('Saved sale not found');
        }

        // Get sale items with current stock
        $items = $wpdb->get_results($wpdb->prepare("
            SELECT sd.*, s.quantity as stock_quantity
            FROM {$this->sale_details_table} sd
            LEFT JOIN {$this->stocks_table} s ON sd.fk_product_id = s.fk_product_id
            WHERE sd.fk_sale_id = %d
        ", $sale_id));

        wp_send_json_success([
            'sale' => $sale,
            'items' => $items
        ]);
    }

    /** Process sale completion or save */
    public function ajax_process_sale()
    {
        check_ajax_referer('orpl_process_sale', 'nonce');
        global $wpdb;
        $sale_status = 'saveSale';

        try {
            // Validate sale data
            if (!isset($_POST['sale_data']) || empty($_POST['sale_data'])) {
                throw new Exception(__('Sale data is required', 'obydullah-restaurant-pos-lite'));
            }

            $sale_data_raw = sanitize_text_field(wp_unslash($_POST['sale_data'] ?? ''));

            // Decode JSON data
            $data = json_decode($sale_data_raw, true);

            // Check for JSON decoding errors
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception(__('Invalid JSON data: ', 'obydullah-restaurant-pos-lite') . json_last_error_msg());
            }

            if (!$data || !is_array($data)) {
                throw new Exception(__('Invalid sale data structure', 'obydullah-restaurant-pos-lite'));
            }

            if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
                throw new Exception(__('Sale items are required', 'obydullah-restaurant-pos-lite'));
            }

            $wpdb->query('START TRANSACTION');

            // Check if we're updating a saved sale
            $is_updating_saved_sale = !empty($data['saved_sale_id']);
            $sale_id = $is_updating_saved_sale ? intval($data['saved_sale_id']) : null;
            $action = isset($data['action']) ? $data['action'] : 'save';

            // Generate invoice ID
            if ($is_updating_saved_sale && $action === 'complete') {
                // For completing saved sale, use existing sale record
                $existing_sale = $wpdb->get_row($wpdb->prepare(
                    "SELECT * FROM {$this->sales_table} WHERE id = %d AND status = '%s'",
                    $sale_id,
                    $sale_status
                ));

                if (!$existing_sale) {
                    throw new Exception(__('Saved sale not found', 'obydullah-restaurant-pos-lite'));
                }

                $invoice_id = $existing_sale->invoice_id;
            } else {
                // Generate new invoice ID for new sales
                $invoice_id = 'INV-' . gmdate('Ymd') . '-' . str_pad(wp_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            }

            // Calculate totals
            $subtotal = 0;
            $buy_price_total = 0;

            foreach ($data['items'] as $item) {
                if (!isset($item['product_id'], $item['price'], $item['quantity'], $item['name'])) {
                    throw new Exception(__('Invalid item data', 'obydullah-restaurant-pos-lite'));
                }

                $item_price = floatval($item['price']);
                $item_quantity = intval($item['quantity']);
                $subtotal += $item_price * $item_quantity;

                // Get product net cost
                $stock_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT net_cost, quantity FROM {$this->stocks_table} WHERE fk_product_id = %d",
                    intval($item['product_id'])
                ));

                if ($stock_data) {
                    $buy_price_total += floatval($stock_data->net_cost) * $item_quantity;

                    // Check stock availability only for completed sales
                    if ($action === 'complete' && $stock_data->quantity < $item_quantity) {
                        throw new Exception(sprintf(
                            __('Insufficient stock for product: %1$s. Available: %2$d, Requested: %3$d', 'obydullah-restaurant-pos-lite'),
                            esc_html($item['name']),
                            $stock_data->quantity,
                            $item_quantity
                        ));
                    }
                }
            }

            // Calculate financials
            $discount = isset($data['discount']) ? floatval($data['discount']) : 0;
            $delivery_cost = isset($data['delivery_cost']) ? floatval($data['delivery_cost']) : 0;
            $taxable_amount = $subtotal - $discount;

            $vat_amount = $this->helpers->is_vat_enabled() ? ($taxable_amount * $this->helpers->get_vat_rate() / 100) : 0;
            $tax_amount = $this->helpers->is_tax_enabled() ? ($taxable_amount * $this->helpers->get_tax_rate() / 100) : 0;

            $grand_total = $taxable_amount + $vat_amount + $tax_amount + $delivery_cost;
            $paid_amount = ($action === 'complete') ? $grand_total : 0;

            // Calculate income (selling price - buying price)
            $income = $grand_total - $buy_price_total;

            // Sanitize text inputs
            $cooking_instructions = isset($data['cooking_instructions']) ? sanitize_textarea_field($data['cooking_instructions']) : '';
            $note = isset($data['note']) ? sanitize_textarea_field($data['note']) : '';

            // Prepare sale data
            $sale_data = [
                'fk_customer_id' => !empty($data['customer_id']) ? intval($data['customer_id']) : null,
                'invoice_id' => $invoice_id,
                'net_price' => $subtotal,
                'vat_amount' => $vat_amount,
                'tax_amount' => $tax_amount,
                'shipping_cost' => $delivery_cost,
                'discount_amount' => $discount,
                'grand_total' => $grand_total,
                'paid_amount' => $paid_amount,
                'buy_price' => $buy_price_total,
                'sale_type' => isset($data['order_type']) ? sanitize_text_field($data['order_type']) : 'dineIn',
                'cooking_instructions' => $cooking_instructions,
                'status' => ($action === 'complete') ? 'completed' : 'saveSale',
                'note' => $note,
                'created_at' => current_time('mysql'),
            ];

            // Define formats for sale data
            $sale_formats = [
                '%d',  // fk_customer_id (integer or NULL)
                '%s',  // invoice_id (string)
                '%f',  // net_price (float)
                '%f',  // vat_amount (float)
                '%f',  // tax_amount (float)
                '%f',  // shipping_cost (float)
                '%f',  // discount_amount (float)
                '%f',  // grand_total (float)
                '%f',  // paid_amount (float)
                '%f',  // buy_price (float)
                '%s',  // sale_type (string)
                '%s',  // cooking_instructions (string)
                '%s',  // status (string)
                '%s',  // note (string)
                '%s',  // created_at (datetime string)
            ];

            if ($is_updating_saved_sale && $action === 'complete') {
                // Update existing saved sale to completed
                $wpdb->update(
                    $this->sales_table,
                    $sale_data,
                    ['id' => $sale_id],  // WHERE clause
                    $sale_formats,        // Data formats
                    ['%d']                // WHERE format (id is integer)
                );
            } else {
                // Insert new sale record
                $wpdb->insert(
                    $this->sales_table,
                    $sale_data,
                    $sale_formats
                );
                $sale_id = $wpdb->insert_id;
            }

            if (!$sale_id) {
                throw new Exception(__('Failed to create sale record', 'obydullah-restaurant-pos-lite'));
            }

            // Handle sale details
            if ($is_updating_saved_sale) {
                // Delete existing sale details for update
                $wpdb->delete(
                    $this->sale_details_table,
                    ['fk_sale_id' => $sale_id],
                    ['%d']  // WHERE format (fk_sale_id is integer)
                );
            }

            // Insert sale details and update stock
            foreach ($data['items'] as $item) {
                $item_quantity = intval($item['quantity']);
                $item_price = floatval($item['price']);
                $total_price = $item_price * $item_quantity;

                $stock_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT id, net_cost, sale_cost, quantity FROM {$this->stocks_table} WHERE fk_product_id = %d",
                    intval($item['product_id'])
                ));

                if (!$stock_data) {
                    throw new Exception(sprintf(__('Stock not found for product ID: %d', 'obydullah-restaurant-pos-lite'), intval($item['product_id'])));
                }

                // Insert sale detail
                $sale_detail_data = [
                    'fk_sale_id' => $sale_id,
                    'fk_product_id' => intval($item['product_id']),
                    'fk_stock_id' => $stock_data->id,
                    'product_name' => sanitize_text_field($item['name']),
                    'quantity' => $item_quantity,
                    'unit_price' => $item_price,
                    'total_price' => $total_price,
                    'created_at' => current_time('mysql'),
                ];

                // Define formats for sale detail data
                $sale_detail_formats = [
                    '%d',  // fk_sale_id (integer)
                    '%d',  // fk_product_id (integer)
                    '%d',  // fk_stock_id (integer)
                    '%s',  // product_name (string)
                    '%d',  // quantity (integer)
                    '%f',  // unit_price (float)
                    '%f',  // total_price (float)
                    '%s',  // created_at (datetime string)
                ];

                $wpdb->insert(
                    $this->sale_details_table,
                    $sale_detail_data,
                    $sale_detail_formats
                );

                // Update stock quantity only for completed sales
                if ($action === 'complete') {
                    $new_quantity = intval($stock_data->quantity) - $item_quantity;
                    $new_status = $new_quantity <= 0 ? 'outStock' : ($new_quantity <= 10 ? 'lowStock' : 'inStock');

                    $wpdb->update(
                        $this->stocks_table,
                        [
                            'quantity' => $new_quantity,
                            'status' => $new_status,
                            'updated_at' => current_time('mysql')
                        ],
                        ['id' => $stock_data->id],
                        [
                            '%d',  // quantity (integer)
                            '%s',  // status (string)
                            '%s',  // updated_at (datetime string)
                        ],
                        ['%d']  // WHERE format (id is integer)
                    );
                }
            }

            // Create accounting record only for completed sales
            if ($action === 'complete') {
                // For accounting:
                // - out_amount = buy_price_total (cost of goods sold)
                // - in_amount = grand_total (revenue from sale)
                // Income is calculated as: in_amount - out_amount

                $accounting_data = [
                    'in_amount' => $grand_total,     // Revenue from sale
                    'out_amount' => $buy_price_total, // Cost of goods sold
                    'description' => sprintf('Sale #%s completed (Income: %s)', $invoice_id, number_format($income, 2)),
                    'created_at' => current_time('mysql')
                ];

                $accounting_formats = [
                    '%f',  // in_amount (float)
                    '%f',  // out_amount (float)
                    '%s',  // description (string)
                    '%s',  // created_at (datetime string)
                ];

                $wpdb->insert(
                    $this->accounting_table,
                    $accounting_data,
                    $accounting_formats
                );
            }

            $wpdb->query('COMMIT');

            wp_send_json_success([
                'sale_id' => $sale_id,
                'invoice_id' => $invoice_id,
                'income' => $income,
                'message' => $action === 'complete' ? __('Sale completed successfully!', 'obydullah-restaurant-pos-lite') : __('Sale saved successfully!', 'obydullah-restaurant-pos-lite')
            ]);

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            wp_send_json_error($e->getMessage());
        }
    }
}
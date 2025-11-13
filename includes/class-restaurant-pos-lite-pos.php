<?php
/**
 * Point of Sales (POS)
 *
 * @package Restaurant_POS_Lite
 * @since   1.0.0
 */
if (!defined('ABSPATH'))
    exit;

class Restaurant_POS_Lite_POS
{
    private $helpers;

    public function __construct()
    {
        $this->helpers = new Restaurant_POS_Lite_Helpers();
        add_action('wp_ajax_get_categories_for_pos', [$this, 'ajax_get_categories_for_pos']);
        add_action('wp_ajax_get_products_by_category', [$this, 'ajax_get_products_by_category']);
        add_action('wp_ajax_get_customers_for_pos', [$this, 'ajax_get_customers_for_pos']);
        add_action('wp_ajax_process_sale', [$this, 'ajax_process_sale']);
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
            <h1 style="margin-bottom:20px;"><?php esc_html_e('Restaurant POS System', 'restaurant-pos-lite'); ?></h1>

            <div id="pos-container" style="display: flex; height: calc(100vh - 120px); min-height: 600px; background: #f5f5f5;">
                <!-- Products Panel -->
                <div style="flex: 3; border-right: 2px solid #ccc; padding: 20px; overflow-y: auto; background-color: #f9f9f9;">
                    <h2 style="margin-top: 0; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 10px;">
                        <?php esc_html_e('Products', 'restaurant-pos-lite'); ?>
                    </h2>

                    <!-- Customer Selection -->
                    <div
                        style="margin-bottom: 20px; background: white; padding: 15px; border-radius: 5px; border: 1px solid #ddd;">
                        <label
                            style="display: block; margin-bottom: 8px; font-weight: bold;"><?php esc_html_e('Select Customer', 'restaurant-pos-lite'); ?></label>
                        <select id="customer-select"
                            style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value=""><?php esc_html_e('Walk-in Customer', 'restaurant-pos-lite'); ?></option>
                            <!-- Customers will be loaded via AJAX -->
                        </select>
                    </div>

                    <!-- Product Categories -->
                    <div style="margin-bottom: 20px;" id="category-buttons">
                        <!-- Categories will be loaded via AJAX -->
                    </div>

                    <!-- Product Grid -->
                    <div id="product-grid"
                        style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;">
                        <!-- Products will be loaded via AJAX -->
                        <div style="text-align: center; padding: 40px; color: #666;">
                            <div class="spinner is-active" style="float: none; margin: 0 auto;"></div>
                            <p><?php esc_html_e('Loading stocks...', 'restaurant-pos-lite'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Cart & Checkout Panel -->
                <div style="flex: 2; padding: 20px; background-color: #f5f5f5;">
                    <h2 style="margin-top: 0; color: #333; border-bottom: 1px solid #ddd; padding-bottom: 10px;">
                        <?php esc_html_e('Current Sale', 'restaurant-pos-lite'); ?>
                    </h2>

                    <!-- Cart Items -->
                    <div style="margin-bottom: 20px; max-height: 300px; overflow-y: auto;">
                        <table style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background-color: #e0e0e0;">
                                    <th style="padding: 10px; text-align: left; border-bottom: 1px solid #ddd;">
                                        <?php esc_html_e('Item', 'restaurant-pos-lite'); ?>
                                    </th>
                                    <th style="padding: 10px; text-align: center; border-bottom: 1px solid #ddd;">
                                        <?php esc_html_e('Qty', 'restaurant-pos-lite'); ?>
                                    </th>
                                    <th style="padding: 10px; text-align: right; border-bottom: 1px solid #ddd;">
                                        <?php esc_html_e('Price', 'restaurant-pos-lite'); ?>
                                    </th>
                                    <th style="padding: 10px; text-align: right; border-bottom: 1px solid #ddd;">
                                        <?php esc_html_e('Total', 'restaurant-pos-lite'); ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody id="cart-items">
                                <tr>
                                    <td colspan="4" style="padding: 20px; text-align: center; color: #666;">
                                        <?php esc_html_e('No items in cart', 'restaurant-pos-lite'); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Order Type Tabs -->
                    <div style="margin-bottom: 20px;">
                        <div style="display: flex; border-bottom: 1px solid #ddd;">
                            <button id="dineInTab"
                                style="flex: 1; padding: 12px; background-color: #4CAF50; color: white; border: none; border-radius: 4px 4px 0 0; margin-right: 2px;"><?php esc_html_e('Dine In', 'restaurant-pos-lite'); ?></button>
                            <button id="takeAwayTab"
                                style="flex: 1; padding: 12px; background-color: #e0e0e0; border: none; border-radius: 4px 4px 0 0; margin-right: 2px;"><?php esc_html_e('Take Away', 'restaurant-pos-lite'); ?></button>
                            <button id="pickupTab"
                                style="flex: 1; padding: 12px; background-color: #e0e0e0; border: none; border-radius: 4px 4px 0 0;"><?php esc_html_e('Pickup', 'restaurant-pos-lite'); ?></button>
                        </div>

                        <!-- Dine In Options -->
                        <div id="dineInOptions"
                            style="background-color: white; padding: 15px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; border-top: none;">
                            <div style="margin-bottom: 10px;">
                                <label
                                    style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Table Number', 'restaurant-pos-lite'); ?></label>
                                <input type="text" id="table-number"
                                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"
                                    placeholder="<?php esc_html_e('Enter table number', 'restaurant-pos-lite'); ?>">
                            </div>
                            <div>
                                <label
                                    style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Cooking Instructions', 'restaurant-pos-lite'); ?></label>
                                <textarea id="dinein-instructions"
                                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; min-height: 60px;"
                                    placeholder="<?php esc_html_e('Add special cooking instructions...', 'restaurant-pos-lite'); ?>"></textarea>
                            </div>
                        </div>

                        <!-- Take Away Options -->
                        <div id="takeAwayOptions"
                            style="background-color: white; padding: 15px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; border-top: none; display: none;">
                            <div style="margin-bottom: 10px;">
                                <label
                                    style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Customer Name', 'restaurant-pos-lite'); ?></label>
                                <input type="text" id="takeaway-name"
                                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"
                                    placeholder="<?php esc_html_e('Enter customer name', 'restaurant-pos-lite'); ?>">
                            </div>
                            <div style="margin-bottom: 10px;">
                                <label
                                    style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Delivery Address', 'restaurant-pos-lite'); ?></label>
                                <textarea id="takeaway-address"
                                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; min-height: 60px;"
                                    placeholder="<?php esc_html_e('Enter delivery address', 'restaurant-pos-lite'); ?>"></textarea>
                            </div>

                            <div style="display: flex; gap: 10px; margin-bottom: 10px;">
                                <div style="flex: 1;">
                                    <label
                                        style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Email', 'restaurant-pos-lite'); ?></label>
                                    <input type="email" id="takeaway-email"
                                        style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"
                                        placeholder="<?php esc_html_e('Enter email address', 'restaurant-pos-lite'); ?>">
                                </div>
                                <div style="flex: 1;">
                                    <label
                                        style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Mobile', 'restaurant-pos-lite'); ?></label>
                                    <input type="text" id="takeaway-mobile"
                                        style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"
                                        placeholder="<?php esc_html_e('Enter mobile number', 'restaurant-pos-lite'); ?>">
                                </div>
                            </div>

                            <div style="margin-bottom: 10px;">
                                <label
                                    style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Cooking Instructions', 'restaurant-pos-lite'); ?></label>
                                <textarea id="takeaway-instructions"
                                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; min-height: 60px;"
                                    placeholder="<?php esc_html_e('Enter Cooking Instructions', 'restaurant-pos-lite'); ?>"></textarea>
                            </div>
                        </div>

                        <!-- Pickup Options -->
                        <div id="pickupOptions"
                            style="background-color: white; padding: 15px; border-radius: 0 0 5px 5px; border: 1px solid #ddd; border-top: none; display: none;">
                            <div style="margin-bottom: 10px;">
                                <label
                                    style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Customer Name', 'restaurant-pos-lite'); ?></label>
                                <input type="text" id="pickup-name"
                                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"
                                    placeholder="<?php esc_html_e('Enter customer name', 'restaurant-pos-lite'); ?>">
                            </div>
                            <div style="margin-bottom: 10px;">
                                <label
                                    style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Mobile', 'restaurant-pos-lite'); ?></label>
                                <input type="tel" id="pickup-mobile"
                                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;"
                                    placeholder="<?php esc_html_e('Enter mobile number', 'restaurant-pos-lite'); ?>">
                            </div>
                            <div>
                                <label
                                    style="display: block; margin-bottom: 5px; font-weight: bold;"><?php esc_html_e('Cooking Instructions', 'restaurant-pos-lite'); ?></label>
                                <textarea id="pickup-instructions"
                                    style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px; min-height: 60px;"
                                    placeholder="<?php esc_html_e('Add special cooking instructions...', 'restaurant-pos-lite'); ?>"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div style="background-color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span><?php esc_html_e('Subtotal:', 'restaurant-pos-lite'); ?></span>
                            <span id="subtotal"><?php echo esc_html($this->helpers->format_currency(0)); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span><?php esc_html_e('Discount:', 'restaurant-pos-lite'); ?></span>
                            <div style="display: flex; align-items: center;">
                                <input type="text" id="discount-amount"
                                    style="width: 80px; padding: 5px; border: 1px solid #ccc; border-radius: 4px; text-align: right;"
                                    placeholder="0.00">
                                <button id="apply-discount"
                                    style="margin-left: 5px; padding: 5px 10px; background-color: #2196F3; color: white; border: none; border-radius: 4px;"><?php esc_html_e('Apply', 'restaurant-pos-lite'); ?></button>
                            </div>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span><?php esc_html_e('Delivery Cost:', 'restaurant-pos-lite'); ?></span>
                            <div style="display: flex; align-items: center;">
                                <input type="text" id="delivery-cost"
                                    style="width: 80px; padding: 5px; border: 1px solid #ccc; border-radius: 4px; text-align: right;"
                                    placeholder="0.00" value="0.00">
                            </div>
                        </div>
                        <!-- VAT Line (only shows if VAT is enabled) -->
                        <div style="display: <?php echo $is_vat_enabled ? 'flex' : 'none'; ?>; justify-content: space-between; margin-bottom: 10px;"
                            id="vat-line">
                            <span id="vat-label"><?php /* translators: %s: VAT rate percentage */ ?>
                                <?php printf(esc_html__('VAT (%s%%):', 'restaurant-pos-lite'), esc_html($vat_rate)); ?></span>
                            <span id="vat-amount"><?php echo esc_html($this->helpers->format_currency(0)); ?></span>
                        </div>

                        <!-- TAX Line (only shows if TAX is enabled) -->
                        <div style="display: <?php echo esc_attr($this->helpers->is_tax_enabled() ? 'flex' : 'none'); ?>; justify-content: space-between; margin-bottom: 10px;"
                            id="tax-line">
                            <span id="tax-label"><?php /* translators: %s: TAX rate percentage */ ?>
                                <?php printf(esc_html__('TAX (%s%%):', 'restaurant-pos-lite'), esc_html($this->helpers->get_tax_rate())); ?></span>
                            <span id="tax-amount"><?php echo esc_html($this->helpers->format_currency(0)); ?></span>
                        </div>
                        <div
                            style="display: flex; justify-content: space-between; font-weight: bold; font-size: 18px; border-top: 1px solid #ddd; padding-top: 10px;">
                            <span><?php esc_html_e('Total:', 'restaurant-pos-lite'); ?></span>
                            <span id="total-amount"><?php echo esc_html($this->helpers->format_currency(0)); ?></span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div style="display: flex; gap: 10px;">
                        <button id="clear-cart"
                            style="flex: 1; padding: 12px; background-color: #f44336; color: white; border: none; border-radius: 4px; font-size: 16px;"><?php esc_html_e('Cancel', 'restaurant-pos-lite'); ?></button>
                        <button id="save-sale"
                            style="flex: 1; padding: 12px; background-color: #FF9800; color: white; border: none; border-radius: 4px; font-size: 16px;"><?php esc_html_e('Save Sale', 'restaurant-pos-lite'); ?></button>
                        <button id="complete-sale"
                            style="flex: 1; padding: 12px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; font-size: 16px;"><?php esc_html_e('Complete Sale', 'restaurant-pos-lite'); ?></button>
                    </div>

                </div>
            </div>

            <!-- Success Modal -->
            <div id="success-modal"
                style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; justify-content: center; align-items: center;">
                <div
                    style="background: white; padding: 30px; border-radius: 10px; text-align: center; box-shadow: 0 4px 20px rgba(0,0,0,0.3); max-width: 400px; width: 90%;">
                    <!-- Professional checkmark icon -->
                    <div
                        style="width: 80px; height: 80px; background: #4CAF50; border-radius: 50%; margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"
                            stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>

                    <h2 style="margin: 0 0 10px 0; color: #333; font-size: 24px;">
                        <?php esc_html_e('Sale Completed Successfully!', 'restaurant-pos-lite'); ?>
                    </h2>
                    <p style="margin: 0 0 25px 0; color: #666; font-size: 16px; line-height: 1.5;">
                        <?php esc_html_e('Invoice:', 'restaurant-pos-lite'); ?> <strong id="modal-invoice-id">-</strong><br>
                        <?php esc_html_e('Total:', 'restaurant-pos-lite'); ?> <strong
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
                            <?php esc_html_e('Print Receipt', 'restaurant-pos-lite'); ?>
                        </button>

                        <button id="modal-new-order"
                            style="padding: 12px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 6px; font-size: 15px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 500;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <?php esc_html_e('New Order', 'restaurant-pos-lite'); ?>
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

                // Separate VAT and TAX settings for clarity
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
                            action: 'get_customers_for_pos',
                            nonce: '<?php echo esc_js(wp_create_nonce("get_customers_for_pos")); ?>'
                        },
                        success: function (response) {
                            if (response.success) {
                                let select = $('#customer-select');
                                select.empty(); // Clear existing options
                                select.append('<option value=""><?php esc_html_e('Walk-in Customer', 'restaurant-pos-lite'); ?></option>');

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
                            action: 'get_categories_for_pos',
                            nonce: '<?php echo esc_js(wp_create_nonce("get_categories_for_pos")); ?>'
                        },
                        success: function (response) {
                            if (response.success) {
                                let container = $('#category-buttons');
                                container.empty();

                                // Add "All" category button
                                container.append(
                                    '<button class="category-btn active" data-category-id="all" style="padding: 8px 12px; margin-right: 5px; background-color: #4CAF50; color: white; border: none; border-radius: 4px;"><?php esc_html_e('All', 'restaurant-pos-lite'); ?></button>'
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
                    $('#product-grid').html('<div style="text-align: center; padding: 40px; color: #666;"><div class="spinner is-active" style="float: none; margin: 0 auto;"></div><p><?php esc_html_e('Loading products...', 'restaurant-pos-lite'); ?></p></div>');

                    $.ajax({
                        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                        type: 'GET',
                        data: {
                            action: 'get_products_by_category',
                            category_id: categoryId,
                            nonce: '<?php echo esc_js(wp_create_nonce("get_products_by_category")); ?>'
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
                                            '<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); background: #f44336; color: white; padding: 3px 12px; font-size: 10px; font-weight: bold; z-index: 2;"><?php esc_html_e('SOLD OUT', 'restaurant-pos-lite'); ?></div>' :
                                            ''
                                        ) +
                                        '</div>'
                                    );

                                    container.append(productCard);
                                });
                            } else {
                                container.html('<div style="text-align: center; padding: 40px; color: #666;"><p><?php esc_html_e('No products found in this category', 'restaurant-pos-lite'); ?></p></div>');
                            }

                            // Add click handlers for product cards
                            initializeProductCardHandlers();
                        }
                    });
                }

                // Initialize product card click handlers
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
                                action: 'get_customers_for_pos',
                                nonce: '<?php echo esc_js(wp_create_nonce("get_customers_for_pos")); ?>'
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
                            alert('<?php esc_html_e('Not enough stock available!', 'restaurant-pos-lite'); ?>');
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
                        container.html('<tr><td colspan="4" style="padding: 20px; text-align: center; color: #666;"><?php esc_html_e('No items in cart', 'restaurant-pos-lite'); ?></td></tr>');
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
                            alert('<?php esc_html_e('Not enough stock available!', 'restaurant-pos-lite'); ?>');
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
                    if (confirm('<?php esc_html_e('Clear all items from cart?', 'restaurant-pos-lite'); ?>')) {
                        cart = [];
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
                        alert('<?php esc_html_e('Please add items to cart before processing sale.', 'restaurant-pos-lite'); ?>');
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
                    const currentCartData = [...cart]; // Copy the cart
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
                        action: action
                    };

                    // Show processing indicator
                    const button = $(`#${action === 'complete' ? 'complete-sale' : 'save-sale'}`);
                    const originalText = button.text();
                    button.text('<?php esc_html_e('Processing...', 'restaurant-pos-lite'); ?>').prop('disabled', true);

                    // Send AJAX request
                    $.ajax({
                        url: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
                        type: 'POST',
                        data: {
                            action: 'process_sale',
                            sale_data: JSON.stringify(saleData),
                            nonce: '<?php echo esc_js(wp_create_nonce("process_sale")); ?>'
                        },
                        success: function (response) {
                            if (response.success) {
                                // Show success modal with invoice details BEFORE resetting
                                if (action === 'complete') {
                                    showInvoiceSummary(
                                        response.data.invoice_id,
                                        response.data.sale_id,
                                        currentCartData, // Pass the cart data
                                        currentFormData,  // Pass the form data
                                        saleData          // Pass the sale data
                                    );
                                } else {
                                    resetPOS();
                                    alert('<?php esc_html_e('Sale saved successfully!', 'restaurant-pos-lite'); ?>');
                                }
                            } else {
                                alert('<?php esc_html_e('Error:', 'restaurant-pos-lite'); ?> ' + response.data);
                            }
                        },
                        error: function (xhr, status, error) {
                            alert('<?php esc_html_e('Error processing sale:', 'restaurant-pos-lite'); ?> ' + error);
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
                            alert('<?php esc_html_e('Please enter table number for Dine In order.', 'restaurant-pos-lite'); ?>');
                            $('#table-number').focus();
                            return false;
                        }
                    } else if (currentOrderType === 'takeAway') {
                        const customerName = $('#takeaway-name').val().trim();
                        if (!customerName) {
                            alert('<?php esc_html_e('Please enter customer name for Take Away order.', 'restaurant-pos-lite'); ?>');
                            $('#takeaway-name').focus();
                            return false;
                        }
                    } else if (currentOrderType === 'pickup') {
                        const customerName = $('#pickup-name').val().trim();
                        if (!customerName) {
                            alert('<?php esc_html_e('Please enter customer name for Pickup order.', 'restaurant-pos-lite'); ?>');
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
                        if (tableNumber) notes.push(`<?php esc_html_e('Table:', 'restaurant-pos-lite'); ?> ${tableNumber}`);
                    } else if (currentOrderType === 'takeAway') {
                        const customerName = $('#takeaway-name').val().trim();
                        const address = $('#takeaway-address').val().trim();
                        const email = $('#takeaway-email').val().trim();
                        const mobile = $('#takeaway-mobile').val().trim();

                        if (customerName) notes.push(`<?php esc_html_e('Customer:', 'restaurant-pos-lite'); ?> ${customerName}`);
                        if (address) notes.push(`<?php esc_html_e('Address:', 'restaurant-pos-lite'); ?> ${address}`);
                        if (email) notes.push(`<?php esc_html_e('Email:', 'restaurant-pos-lite'); ?> ${email}`);
                        if (mobile) notes.push(`<?php esc_html_e('Mobile:', 'restaurant-pos-lite'); ?> ${mobile}`);
                    } else if (currentOrderType === 'pickup') {
                        const customerName = $('#pickup-name').val().trim();
                        const mobile = $('#pickup-mobile').val().trim();

                        if (customerName) notes.push(`<?php esc_html_e('Customer:', 'restaurant-pos-lite'); ?> ${customerName}`);
                        if (mobile) notes.push(`<?php esc_html_e('Mobile:', 'restaurant-pos-lite'); ?> ${mobile}`);
                    }

                    return notes.join(' | ');
                }

                // Reset POS after successful sale
                function resetPOS() {
                    cart = [];
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
                        vatSettings: vatSettings,    // Store settings for receipt
                        taxSettings: taxSettings     // Store settings for receipt
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
                        alert('<?php esc_html_e('No sale information available for printing.', 'restaurant-pos-lite'); ?>');
                        return;
                    }

                    const saleInfo = window.lastSaleInfo;
                    const cartData = saleInfo.cartData;
                    const formData = saleInfo.formData;

                    // Create receipt content
                    let receiptContent = `
        <div style="width: 300px; font-family: 'Courier New', monospace; font-size: 12px; padding: 20px;">
            <div style="text-align: center; margin-bottom: 15px;">
                <h2 style="margin: 0 0 5px 0; font-size: 18px;"><?php echo esc_js($this->helpers->get_shop_name()); ?></h2>
                <?php
                $shop_info = $this->helpers->get_shop_info();
                if (!empty($shop_info['address'])) {
                    echo '<p style="margin: 0; font-size: 11px;">' . esc_js($shop_info['address']) . '</p>';
                }
                if (!empty($shop_info['phone'])) {
                    /* translators: %s: Phone number */
                    echo '<p style="margin: 0; font-size: 11px;">' . sprintf(esc_html__('Phone: %s', 'restaurant-pos-lite'), esc_html($shop_info['phone'])) . '</p>';
                }
                ?>
            </div>
            
            <hr style="border: 1px dashed #000; margin: 10px 0;">
            
            <div style="margin-bottom: 10px;">
                <div style="display: flex; justify-content: space-between;">
                    <span><?php esc_html_e('Invoice #:', 'restaurant-pos-lite'); ?></span>
                    <span>${saleInfo.invoiceId}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span><?php esc_html_e('Date:', 'restaurant-pos-lite'); ?></span>
                    <span>${new Date().toLocaleString()}</span>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span><?php esc_html_e('Order Type:', 'restaurant-pos-lite'); ?></span>
                    <span>${formData.orderType.charAt(0).toUpperCase() + formData.orderType.slice(1)}</span>
                </div>
                <div>
    `;

                    // Add customer info based on order type
                    if (formData.orderType === 'dineIn') {
                        receiptContent += formData.tableNumber ? `<div><?php esc_html_e('Table:', 'restaurant-pos-lite'); ?> ${formData.tableNumber}</div>` : '<div><?php esc_html_e('Walk-in Customer', 'restaurant-pos-lite'); ?></div>';
                    } else if (formData.orderType === 'takeAway') {
                        receiptContent += `<div><?php esc_html_e('Customer:', 'restaurant-pos-lite'); ?> ${formData.takeawayName || '<?php esc_html_e('Walk-in', 'restaurant-pos-lite'); ?>'}</div>`;
                        if (formData.takeawayAddress) receiptContent += `<div><?php esc_html_e('Address:', 'restaurant-pos-lite'); ?> ${formData.takeawayAddress}</div>`;
                        if (formData.takeawayMobile) receiptContent += `<div><?php esc_html_e('Mobile:', 'restaurant-pos-lite'); ?> ${formData.takeawayMobile}</div>`;
                    } else if (formData.orderType === 'pickup') {
                        receiptContent += `<div><?php esc_html_e('Customer:', 'restaurant-pos-lite'); ?> ${formData.pickupName || '<?php esc_html_e('Walk-in', 'restaurant-pos-lite'); ?>'}</div>`;
                        if (formData.pickupMobile) receiptContent += `<div><?php esc_html_e('Mobile:', 'restaurant-pos-lite'); ?> ${formData.pickupMobile}</div>`;
                    }

                    receiptContent += `
                </div>
            </div>
            
            <hr style="border: 1px dashed #000; margin: 10px 0;">
            
            <div style="margin-bottom: 10px;">
                <div style="display: flex; justify-content: space-between; font-weight: bold; border-bottom: 1px solid #000; padding-bottom: 5px;">
                    <span style="flex: 2;"><?php esc_html_e('Item', 'restaurant-pos-lite'); ?></span>
                    <span style="flex: 1; text-align: center;"><?php esc_html_e('Qty x Price', 'restaurant-pos-lite'); ?></span>
                    <span style="flex: 1; text-align: right;"><?php esc_html_e('Total', 'restaurant-pos-lite'); ?></span>
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
                    <span><?php esc_html_e('Subtotal:', 'restaurant-pos-lite'); ?></span>
                    <span>${formatCurrency(saleInfo.subtotal)}</span>
                </div>
    `;

                    // Always show discount, VAT, and tax lines
                    receiptContent += `
                <div style="display: flex; justify-content: space-between;">
                    <span><?php esc_html_e('Discount:', 'restaurant-pos-lite'); ?></span>
                    <span>${formatCurrency(saleInfo.discount || 0)}</span>
                </div>
    `;

                    // Only show delivery if it has value greater than 0
                    if (saleInfo.deliveryCost && saleInfo.deliveryCost > 0) {
                        receiptContent += `
                <div style="display: flex; justify-content: space-between;">
                    <span><?php esc_html_e('Delivery Cost:', 'restaurant-pos-lite'); ?></span>
                    <span>${formatCurrency(saleInfo.deliveryCost)}</span>
                </div>
        `;
                    }

                    // Always show VAT (use 0 if undefined)
                    receiptContent += `
                <div style="display: flex; justify-content: space-between;">
                    <span><?php esc_html_e('VAT (3%):', 'restaurant-pos-lite'); ?></span>
                    <span>${formatCurrency(saleInfo.vatAmount || 0)}</span>
                </div>
    `;

                    // Always show TAX (use 0 if undefined)
                    receiptContent += `
                <div style="display: flex; justify-content: space-between;">
                    <span><?php esc_html_e('TAX (2%):', 'restaurant-pos-lite'); ?></span>
                    <span>${formatCurrency(saleInfo.taxAmount || 0)}</span>
                </div>
    `;

                    receiptContent += `
                <div style="display: flex; justify-content: space-between; font-weight: bold; border-top: 1px solid #000; padding-top: 5px;">
                    <span><?php esc_html_e('TOTAL:', 'restaurant-pos-lite'); ?></span>
                    <span>${formatCurrency(saleInfo.total)}</span>
                </div>
            </div>
    `;

                    // Add cooking instructions if available
                    if (formData.cookingInstructions) {
                        receiptContent += `
            <hr style="border: 1px dashed #000; margin: 10px 0;">
            <div style="margin-bottom: 10px;">
                <div style="font-weight: bold; margin-bottom: 5px;"><?php esc_html_e('Cooking Instructions:', 'restaurant-pos-lite'); ?></div>
                <div style="font-size: 11px;">${formData.cookingInstructions}</div>
            </div>
        `;
                    }

                    receiptContent += `
            <div style="text-align: center; margin-top: 20px;">
                <p style="margin: 5px 0; font-size: 10px;">*** <?php esc_html_e('ORDER COMPLETED', 'restaurant-pos-lite'); ?> ***</p>
            </div>
        </div>
    `;

                    // Print the receipt
                    const printWindow = window.open('', '_blank', 'width=350,height=600');

                    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title><?php esc_html_e('Receipt', 'restaurant-pos-lite'); ?> - ${saleInfo.invoiceId}</title>
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

    /** Get categories for POS */
    public function ajax_get_categories_for_pos()
    {
        check_ajax_referer('get_categories_for_pos', 'nonce');
        global $wpdb;
        $table_name = $wpdb->prefix . 'pos_categories';

        $categories = $wpdb->get_results("
            SELECT id, name 
            FROM $table_name 
            WHERE status = 'active' 
            ORDER BY name ASC
        ");

        wp_send_json_success($categories);
    }

    /** Get customers for POS */
    public function ajax_get_customers_for_pos()
    {
        check_ajax_referer('get_customers_for_pos', 'nonce');
        global $wpdb;
        $table_name = $wpdb->prefix . 'pos_customers';

        $customers = $wpdb->get_results("
            SELECT id, name, email, mobile, address, balance
            FROM $table_name 
            WHERE status = 'active' 
            ORDER BY name ASC
        ");

        wp_send_json_success($customers);
    }

    /** Get products by category */
    public function ajax_get_products_by_category()
    {
        check_ajax_referer('get_products_by_category', 'nonce');
        global $wpdb;

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized access');
        }

        $products_table = $wpdb->prefix . 'pos_products';
        $stocks_table = $wpdb->prefix . 'pos_stocks';
        $category_id = sanitize_text_field(wp_unslash($_GET['category_id'] ?? 'all'));

        $query = "
        SELECT p.id, p.name, p.image, s.sale_cost, s.quantity, s.status as stock_status
        FROM $products_table p 
        LEFT JOIN $stocks_table s ON p.id = s.fk_product_id 
        WHERE p.status = 'active'
    ";

        $query_params = array();

        if ($category_id !== 'all') {
            $query .= " AND p.fk_category_id = %d";
            $query_params[] = absint($category_id);
        }

        $query .= " ORDER BY p.name ASC LIMIT 20";

        // Only prepare if we have parameters
        if (!empty($query_params)) {
            $query = $wpdb->prepare($query, $query_params);
        }

        $products = $wpdb->get_results($query);

        wp_send_json_success($products);
    }

    /** Process sale completion */
    public function ajax_process_sale()
    {
        check_ajax_referer('process_sale', 'nonce');

        global $wpdb;

        try {
            // FIXED: Proper input validation and sanitization
            if (!isset($_POST['sale_data']) || empty($_POST['sale_data'])) {
                throw new Exception(__('Sale data is required', 'restaurant-pos-lite'));
            }

            $sale_data_raw = sanitize_text_field(wp_unslash($_POST['sale_data']));
            $data = json_decode($sale_data_raw, true);

            if (json_last_error() !== JSON_ERROR_NONE || !$data || !is_array($data)) {
                throw new Exception(__('Invalid sale data', 'restaurant-pos-lite'));
            }

            // FIXED: Validate required data structure
            if (!isset($data['items']) || !is_array($data['items']) || empty($data['items'])) {
                throw new Exception(__('Sale items are required', 'restaurant-pos-lite'));
            }

            // Start transaction
            $wpdb->query('START TRANSACTION');

            // Generate invoice ID - FIXED: Use gmdate() and wp_rand()
            $invoice_id = 'INV-' . gmdate('Ymd') . '-' . str_pad(wp_rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Calculate totals
            $subtotal = 0;
            $buy_price_total = 0;

            foreach ($data['items'] as $item) {
                // FIXED: Validate item structure
                if (!isset($item['product_id'], $item['price'], $item['quantity'], $item['name'])) {
                    throw new Exception(__('Invalid item data', 'restaurant-pos-lite'));
                }

                // FIXED: Sanitize numeric values
                $item_price = floatval($item['price']);
                $item_quantity = intval($item['quantity']);
                $subtotal += $item_price * $item_quantity;

                // Get product net cost (buy price) from stocks table
                $stock_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT net_cost, quantity FROM {$wpdb->prefix}pos_stocks WHERE fk_product_id = %d",
                    intval($item['product_id'])
                ));

                if ($stock_data) {
                    $buy_price_total += floatval($stock_data->net_cost) * $item_quantity;

                    // Check stock availability for completed sales
                    if ($data['action'] === 'complete' && $stock_data->quantity < $item_quantity) {
                        /* translators: 1: Product name, 2: Available quantity, 3: Requested quantity */
                        throw new Exception(sprintf(esc_html__('Insufficient stock for product: %1$s. Available: %2$d, Requested: %3$d', 'restaurant-pos-lite'), esc_html($item['name']), $stock_data->quantity, $item_quantity));
                    }
                }
            }

            // FIXED: Sanitize all numeric inputs
            $discount = floatval($data['discount'] ?? 0);
            $delivery_cost = floatval($data['delivery_cost'] ?? 0);
            $taxable_amount = $subtotal - $discount;
            $tax_amount = $taxable_amount * 0.08; // 8% tax
            $grand_total = $taxable_amount + $tax_amount + $delivery_cost;
            $paid_amount = ($data['action'] === 'complete') ? $grand_total : 0;
            $sale_due = $grand_total - $paid_amount;

            // FIXED: Sanitize text inputs
            $cooking_instructions = sanitize_textarea_field($data['cooking_instructions'] ?? '');
            $note = sanitize_textarea_field($data['note'] ?? '');

            // Insert sale record
            $sale_data = [
                'fk_customer_id' => !empty($data['customer_id']) ? intval($data['customer_id']) : null,
                'invoice_id' => $invoice_id,
                'net_price' => $subtotal,
                'vat_amount' => 0, // Adjust if you have VAT
                'tax_amount' => $tax_amount,
                'shipping_cost' => $delivery_cost,
                'discount_amount' => $discount,
                'grand_total' => $grand_total,
                'paid_amount' => $paid_amount,
                'buy_price' => $buy_price_total,
                'sale_due' => $sale_due,
                'sale_type' => sanitize_text_field($data['order_type'] ?? ''),
                'cooking_instructions' => $cooking_instructions,
                'status' => ($data['action'] === 'complete') ? 'completed' : 'saveSale',
                'note' => $note,
                'created_at' => current_time('mysql'),
            ];

            $wpdb->insert("{$wpdb->prefix}pos_sales", $sale_data);
            $sale_id = $wpdb->insert_id;

            if (!$sale_id) {
                throw new Exception(__('Failed to create sale record', 'restaurant-pos-lite'));
            }

            // Insert sale details and update stock
            foreach ($data['items'] as $item) {
                $item_quantity = intval($item['quantity']);
                $item_price = floatval($item['price']);
                $total_price = $item_price * $item_quantity;

                // Get stock ID and current stock data
                $stock_data = $wpdb->get_row($wpdb->prepare(
                    "SELECT id, net_cost, sale_cost, quantity FROM {$wpdb->prefix}pos_stocks WHERE fk_product_id = %d",
                    intval($item['product_id'])
                ));

                if (!$stock_data) {
                    /* translators: %d: Product ID */
                    throw new Exception(sprintf(esc_html__('Stock not found for product ID: %d', 'restaurant-pos-lite'), intval($item['product_id'])));
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

                $wpdb->insert("{$wpdb->prefix}pos_sale_details", $sale_detail_data);

                // Update stock quantity (only for completed sales)
                if ($data['action'] === 'complete') {
                    $new_quantity = intval($stock_data->quantity) - $item_quantity;
                    $new_status = $new_quantity <= 0 ? 'outStock' : ($new_quantity <= 10 ? 'lowStock' : 'inStock');

                    $wpdb->update(
                        "{$wpdb->prefix}pos_stocks",
                        [
                            'quantity' => $new_quantity,
                            'status' => $new_status,
                            'updated_at' => current_time('mysql')
                        ],
                        ['id' => $stock_data->id]
                    );
                }
            }

            if ($data['action'] === 'complete') {
                $accounting_data = [
                    'out_amount' => $buy_price_total, // Buy Price
                    'amount_receivable' => $grand_total, // Sale Price
                    'description' => 'Stock Out',
                    'created_at' => current_time('mysql')
                ];

                $wpdb->insert("{$wpdb->prefix}pos_accounting", $accounting_data);
            }

            // Commit transaction
            $wpdb->query('COMMIT');

            wp_send_json_success([
                'sale_id' => $sale_id,
                'invoice_id' => $invoice_id,
                'message' => $data['action'] === 'complete' ? __('Sale completed successfully!', 'restaurant-pos-lite') : __('Sale saved successfully!', 'restaurant-pos-lite')
            ]);

        } catch (Exception $e) {
            // Rollback on error
            $wpdb->query('ROLLBACK');
            wp_send_json_error($e->getMessage());
        }
    }
}
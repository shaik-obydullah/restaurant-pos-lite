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
        $vat_rate = $this->helpers->get_vat_rate();
        $is_vat_enabled = $this->helpers->is_vat_enabled();
        ?>
        <div class="wrap">
            <h1 class="mb-3"><?php esc_html_e('Restaurant POS System', 'obydullah-restaurant-pos-lite'); ?></h1>

            <!-- Saved Sales Panel -->
            <div id="saved-sales-panel" class="bg-light p-4 rounded shadow-sm mb-3" style="display: none;">
                <h3 class="mt-0 mb-2"><?php esc_html_e('Saved Sales', 'obydullah-restaurant-pos-lite'); ?></h3>
                <div id="saved-sales-list" class="mb-2" style="max-height: 200px; overflow-y: auto;">
                    <!-- Saved sales will be loaded here -->
                </div>
                <button id="close-saved-sales" class="btn btn-secondary">
                    <?php esc_html_e('Close', 'obydullah-restaurant-pos-lite'); ?>
                </button>
            </div>

            <div id="pos-container" class="pos-container">
                <!-- Stock Items Panel -->
                <div class="pos-stock-panel">
                    <div class="pos-panel-header">
                        <h2 class="pos-panel-title"><?php esc_html_e('Stock Items', 'obydullah-restaurant-pos-lite'); ?></h2>
                        <button id="show-saved-sales" class="btn btn-primary">
                            <?php esc_html_e('Load Saved Sale', 'obydullah-restaurant-pos-lite'); ?>
                        </button>
                    </div>

                    <!-- Customer Selection -->
                    <div class="pos-customer-selection">
                        <label class="pos-label"><?php esc_html_e('Select Customer', 'obydullah-restaurant-pos-lite'); ?></label>
                        <select id="customer-select" class="form-control">
                            <option value=""><?php esc_html_e('Walk-in Customer', 'obydullah-restaurant-pos-lite'); ?></option>
                        </select>
                    </div>

                    <!-- Product Categories -->
                    <div class="pos-categories" id="category-buttons">
                        <!-- Categories will be loaded via AJAX -->
                    </div>

                    <!-- Product Grid -->
                    <div id="product-grid" class="pos-product-grid">
                        <div class="pos-loading">
                            <div class="spinner is-active"></div>
                            <p><?php esc_html_e('Loading stock items...', 'obydullah-restaurant-pos-lite'); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Cart & Checkout Panel -->
                <div class="pos-cart-panel">
                    <h2 class="pos-panel-title"><?php esc_html_e('Current Sale', 'obydullah-restaurant-pos-lite'); ?></h2>

                    <!-- Cart Items -->
                    <div class="pos-cart-items">
                        <table class="table table-striped table-hover mb-0">
                            <thead>
                                <tr class="bg-primary">
                                    <th class="text-left"><?php esc_html_e('Item', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th class="text-center"><?php esc_html_e('Qty', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th class="text-right"><?php esc_html_e('Price', 'obydullah-restaurant-pos-lite'); ?></th>
                                    <th class="text-right"><?php esc_html_e('Total', 'obydullah-restaurant-pos-lite'); ?></th>
                                </tr>
                            </thead>
                            <tbody id="cart-items">
                                <tr>
                                    <td colspan="4" class="text-center p-4 text-muted">
                                        <?php esc_html_e('No items in cart', 'obydullah-restaurant-pos-lite'); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Order Type Tabs -->
                    <div class="pos-order-tabs">
                        <div class="pos-tab-buttons">
                            <button id="dineInTab" class="pos-tab-btn active" data-order-type="dineIn">
                                <?php esc_html_e('Dine In', 'obydullah-restaurant-pos-lite'); ?>
                            </button>
                            <button id="takeAwayTab" class="pos-tab-btn" data-order-type="takeAway">
                                <?php esc_html_e('Take Away', 'obydullah-restaurant-pos-lite'); ?>
                            </button>
                            <button id="pickupTab" class="pos-tab-btn" data-order-type="pickup">
                                <?php esc_html_e('Pickup', 'obydullah-restaurant-pos-lite'); ?>
                            </button>
                        </div>

                        <!-- Dine In Options -->
                        <div id="dineInOptions" class="pos-tab-content">
                            <div class="form-group">
                                <label class="pos-label"><?php esc_html_e('Table Number', 'obydullah-restaurant-pos-lite'); ?></label>
                                <input type="text" id="table-number" class="form-control"
                                    placeholder="<?php esc_attr_e('Enter table number', 'obydullah-restaurant-pos-lite'); ?>">
                            </div>
                            <div class="form-group">
                                <label class="pos-label"><?php esc_html_e('Cooking Instructions', 'obydullah-restaurant-pos-lite'); ?></label>
                                <textarea id="dinein-instructions" class="form-control" rows="3"
                                    placeholder="<?php esc_attr_e('Add special cooking instructions...', 'obydullah-restaurant-pos-lite'); ?>"></textarea>
                            </div>
                        </div>

                        <!-- Take Away Options -->
                        <div id="takeAwayOptions" class="pos-tab-content" style="display: none;">
                            <div class="form-group">
                                <label class="pos-label"><?php esc_html_e('Customer Name', 'obydullah-restaurant-pos-lite'); ?></label>
                                <input type="text" id="takeaway-name" class="form-control"
                                    placeholder="<?php esc_attr_e('Enter customer name', 'obydullah-restaurant-pos-lite'); ?>">
                            </div>
                            <div class="form-group">
                                <label class="pos-label"><?php esc_html_e('Delivery Address', 'obydullah-restaurant-pos-lite'); ?></label>
                                <textarea id="takeaway-address" class="form-control" rows="2"
                                    placeholder="<?php esc_attr_e('Enter delivery address', 'obydullah-restaurant-pos-lite'); ?>"></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="pos-label"><?php esc_html_e('Email', 'obydullah-restaurant-pos-lite'); ?></label>
                                        <input type="email" id="takeaway-email" class="form-control"
                                            placeholder="<?php esc_attr_e('Enter email address', 'obydullah-restaurant-pos-lite'); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="pos-label"><?php esc_html_e('Mobile', 'obydullah-restaurant-pos-lite'); ?></label>
                                        <input type="text" id="takeaway-mobile" class="form-control"
                                            placeholder="<?php esc_attr_e('Enter mobile number', 'obydullah-restaurant-pos-lite'); ?>">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="pos-label"><?php esc_html_e('Cooking Instructions', 'obydullah-restaurant-pos-lite'); ?></label>
                                <textarea id="takeaway-instructions" class="form-control" rows="3"
                                    placeholder="<?php esc_attr_e('Enter Cooking Instructions', 'obydullah-restaurant-pos-lite'); ?>"></textarea>
                            </div>
                        </div>

                        <!-- Pickup Options -->
                        <div id="pickupOptions" class="pos-tab-content" style="display: none;">
                            <div class="form-group">
                                <label class="pos-label"><?php esc_html_e('Customer Name', 'obydullah-restaurant-pos-lite'); ?></label>
                                <input type="text" id="pickup-name" class="form-control"
                                    placeholder="<?php esc_attr_e('Enter customer name', 'obydullah-restaurant-pos-lite'); ?>">
                            </div>
                            <div class="form-group">
                                <label class="pos-label"><?php esc_html_e('Mobile', 'obydullah-restaurant-pos-lite'); ?></label>
                                <input type="tel" id="pickup-mobile" class="form-control"
                                    placeholder="<?php esc_attr_e('Enter mobile number', 'obydullah-restaurant-pos-lite'); ?>">
                            </div>
                            <div class="form-group">
                                <label class="pos-label"><?php esc_html_e('Cooking Instructions', 'obydullah-restaurant-pos-lite'); ?></label>
                                <textarea id="pickup-instructions" class="form-control" rows="3"
                                    placeholder="<?php esc_attr_e('Add special cooking instructions...', 'obydullah-restaurant-pos-lite'); ?>"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Totals -->
                    <div class="pos-totals">
                        <div class="pos-totals-row">
                            <span><?php esc_html_e('Subtotal:', 'obydullah-restaurant-pos-lite'); ?></span>
                            <span id="subtotal"><?php echo esc_html($this->helpers->format_currency(0)); ?></span>
                        </div>
                        <div class="pos-totals-row">
                            <span><?php esc_html_e('Discount:', 'obydullah-restaurant-pos-lite'); ?></span>
                            <div class="pos-discount-input">
                                <input type="text" id="discount-amount" class="form-control form-control-sm" placeholder="0.00">
                                <button id="apply-discount" class="btn btn-primary btn-sm">
                                    <?php esc_html_e('Apply', 'obydullah-restaurant-pos-lite'); ?>
                                </button>
                            </div>
                        </div>
                        <div class="pos-totals-row">
                            <span><?php esc_html_e('Delivery Cost:', 'obydullah-restaurant-pos-lite'); ?></span>
                            <div class="pos-delivery-input">
                                <input type="text" id="delivery-cost" class="form-control form-control-sm" placeholder="0.00" value="0.00">
                            </div>
                        </div>
                        <!-- VAT Line -->
                        <div class="pos-totals-row" id="vat-line" style="display: <?php echo $is_vat_enabled ? 'flex' : 'none'; ?>;">
                            <span id="vat-label"><?php printf(esc_html__('VAT (%s%%):', 'obydullah-restaurant-pos-lite'), esc_html($vat_rate)); ?></span>
                            <span id="vat-amount"><?php echo esc_html($this->helpers->format_currency(0)); ?></span>
                        </div>
                        <!-- TAX Line -->
                        <div class="pos-totals-row" id="tax-line" style="display: <?php echo esc_attr($this->helpers->is_tax_enabled() ? 'flex' : 'none'); ?>;">
                            <span id="tax-label"><?php printf(esc_html__('TAX (%s%%):', 'obydullah-restaurant-pos-lite'), esc_html($this->helpers->get_tax_rate())); ?></span>
                            <span id="tax-amount"><?php echo esc_html($this->helpers->format_currency(0)); ?></span>
                        </div>
                        <div class="pos-totals-row pos-total">
                            <span><?php esc_html_e('Total:', 'obydullah-restaurant-pos-lite'); ?></span>
                            <span id="total-amount"><?php echo esc_html($this->helpers->format_currency(0)); ?></span>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="pos-action-buttons">
                        <button id="clear-cart" class="btn btn-danger">
                            <?php esc_html_e('Cancel', 'obydullah-restaurant-pos-lite'); ?>
                        </button>
                        <button id="save-sale" class="btn btn-warning">
                            <?php esc_html_e('Save Sale', 'obydullah-restaurant-pos-lite'); ?>
                        </button>
                        <button id="complete-sale" class="btn btn-success">
                            <?php esc_html_e('Complete Sale', 'obydullah-restaurant-pos-lite'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Success Modal -->
            <div id="success-modal" class="pos-success-modal">
                <div class="pos-modal-content">
                    <div class="pos-success-icon">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="3"
                            stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"></polyline>
                        </svg>
                    </div>
                    <h2 class="pos-modal-title"><?php esc_html_e('Sale Completed Successfully!', 'obydullah-restaurant-pos-lite'); ?></h2>
                    <p class="pos-modal-text">
                        <?php esc_html_e('Invoice:', 'obydullah-restaurant-pos-lite'); ?> <strong id="modal-invoice-id">-</strong><br>
                        <?php esc_html_e('Total:', 'obydullah-restaurant-pos-lite'); ?> <strong id="modal-total-amount"><?php echo esc_html($this->helpers->format_currency(0)); ?></strong>
                    </p>
                    <div class="pos-modal-actions">
                        <button id="modal-print-receipt" class="btn btn-primary">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                <rect x="6" y="14" width="12" height="8"></rect>
                            </svg>
                            <?php esc_html_e('Print Receipt', 'obydullah-restaurant-pos-lite'); ?>
                        </button>
                        <button id="modal-new-order" class="btn btn-success">
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
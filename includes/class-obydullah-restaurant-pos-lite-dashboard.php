<?php
/**
 * Obydullah Restaurant POS Lite Dashboard Class
 *
 * @package Obydullah_Restaurant_POS_Lite
 * @since 1.0.0
 * @version 1.0.2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard class for Obydullah Restaurant POS Lite
 */
class Obydullah_Restaurant_POS_Lite_Dashboard
{
    /**
     * Database instance
     *
     * @var wpdb
     */
    private $wpdb;

    /**
     * Table names
     *
     * @var string
     */
    private $table_sales;
    private $table_sale_details;
    private $table_stocks;
    private $table_accounting;
    private $table_products;

    /**
     * Constructor
     */
    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_sales = $wpdb->prefix . 'orpl_sales';
        $this->table_sale_details = $wpdb->prefix . 'orpl_sale_details';
        $this->table_stocks = $wpdb->prefix . 'orpl_stocks';
        $this->table_accounting = $wpdb->prefix . 'orpl_accounting';
        $this->table_products = $wpdb->prefix . 'orpl_products';
    }

    /**
     * Format currency using helper class
     *
     * @param float $amount Amount to format.
     * @return string
     */
    private function format_currency($amount)
    {
        if (class_exists('Obydullah_Restaurant_POS_Lite_Helpers')) {
            return Obydullah_Restaurant_POS_Lite_Helpers::format_currency($amount);
        }

        // Fallback formatting.
        return number_format(floatval($amount), 2) . ' ' . $this->get_currency_symbol();
    }

    /**
     * Format number with thousands separator
     *
     * @param int $number Number to format.
     * @return string
     */
    private function format_number($number)
    {
        return number_format(intval($number), 0, '.', ',');
    }

    /**
     * Get currency symbol from helper class
     *
     * @return string
     */
    private function get_currency_symbol()
    {
        if (class_exists('Obydullah_Restaurant_POS_Lite_Helpers')) {
            return Obydullah_Restaurant_POS_Lite_Helpers::get_currency_symbol();
        }

        // Fallback currency symbol.
        return '$';
    }

    /**
     * Get stock value with proper SQL injection protection
     *
     * @return float
     */
    private function get_stock_value()
    {
        $status = 'outStock';
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT SUM(quantity * net_cost) AS total_value 
             FROM {$this->table_stocks} 
             WHERE status != %s",
                $status
            )
        );

        return $result ? floatval($result) : 0;
    }

    /**
     * Get today's sales count with proper date handling
     *
     * @return int
     */
    private function get_today_sales_count()
    {
        $today = current_time('Y-m-d');
        $status = 'completed';

        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) 
             FROM {$this->table_sales} 
             WHERE status = '%s' AND DATE(created_at) = %s",
                $status,
                $today
            )
        );

        return $result ? intval($result) : 0;
    }

    /**
     * Get monthly sales count
     *
     * @return int
     */
    private function get_month_sales_count()
    {
        $first_day = current_time('Y-m-01');
        $last_day = current_time('Y-m-t');
        $status = 'completed';

        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) 
             FROM {$this->table_sales} 
             WHERE status = '%s' AND DATE(created_at) BETWEEN %s AND %s",
                $status,
                $first_day,
                $last_day
            )
        );

        return $result ? intval($result) : 0;
    }

    /**
     * Get today's income
     *
     * @return float
     */
    private function get_today_income()
    {
        $today = current_time('Y-m-d');
        $status = 'completed';

        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT SUM(paid_amount - buy_price) as today_income
             FROM {$this->table_sales} 
             WHERE status = '%s' AND DATE(created_at) = %s",
                $status,
                $today
            )
        );

        return $result ? floatval($result) : 0;
    }

    /**
     * Get monthly income
     *
     * @return float
     */
    private function get_month_income()
    {
        $first_day = current_time('Y-m-01');
        $last_day = current_time('Y-m-t');
        $status = 'completed';

        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT SUM(paid_amount - buy_price) as month_income 
            FROM {$this->table_sales} 
            WHERE DATE(created_at) BETWEEN %s AND %s 
            AND status = '%s'",
                $first_day,
                $last_day,
                $status
            )
        );

        return $result ? floatval($result) : 0;
    }

    /**
     * Get today's expenses
     *
     * @return float
     */
    private function get_today_expense()
    {
        $today = current_time('Y-m-d');

        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT SUM(out_amount) 
            FROM {$this->table_accounting} 
            WHERE DATE(created_at) = %s",
                $today
            )
        );

        return $result ? floatval($result) : 0;
    }

    /**
     * Get monthly expenses
     *
     * @return float
     */
    private function get_month_expense()
    {
        $first_day = current_time('Y-m-01');
        $last_day = current_time('Y-m-t');

        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT SUM(out_amount) 
            FROM {$this->table_accounting} 
            WHERE DATE(created_at) BETWEEN %s AND %s",
                $first_day,
                $last_day
            )
        );

        return $result ? floatval($result) : 0;
    }

    /**
     * Get number of low stock items
     *
     * @return int
     */
    private function get_low_stock_count()
    {
        $result = $this->wpdb->get_var(
            "SELECT COUNT(*) 
         FROM {$this->table_stocks} 
         WHERE status = 'lowStock'"
        );

        return $result ? intval($result) : 0;
    }

    /**
     * Get top selling products
     *
     * @param int $limit Number of products to return
     * @return array
     */
    private function get_top_products($limit = 5)
    {
        $query = $this->wpdb->prepare(
            "SELECT 
            p.id,
            p.name,
            p.image,
            p.status as product_status,
            COUNT(DISTINCT sd.fk_sale_id) as total_orders,
            SUM(sd.quantity) as total_quantity_sold,
            SUM(sd.total_price) as total_revenue,
            AVG(sd.unit_price) as avg_unit_price
         FROM {$this->table_products} p
         INNER JOIN {$this->table_sale_details} sd ON p.id = sd.fk_product_id
         INNER JOIN {$this->table_sales} s ON sd.fk_sale_id = s.id 
            AND s.status = 'completed'
         WHERE p.status = 'active'
         GROUP BY p.id
         ORDER BY total_quantity_sold DESC
         LIMIT %d",
            $limit
        );

        $results = $this->wpdb->get_results($query);

        if (!$results) {
            return array();
        }

        return array_map(function ($product) {
            return array(
                'id' => $product->id,
                'name' => $product->name,
                'image' => $product->image,
                'product_status' => $product->product_status,
                'total_orders' => intval($product->total_orders),
                'total_sold' => intval($product->total_quantity_sold),
                'total_revenue' => floatval($product->total_revenue),
                'avg_price' => floatval($product->avg_unit_price)
            );
        }, $results);
    }

    /**
     * Render the dashboard page
     */
    public function render_page()
    {
        $dashboard_data = array(
            'stock_value' => $this->get_stock_value(),
            'today_sale' => $this->get_today_sales_count(),
            'month_sale' => $this->get_month_sales_count(),
            'today_income' => $this->get_today_income(),
            'month_income' => $this->get_month_income(),
            'today_expense' => $this->get_today_expense(),
            'month_expense' => $this->get_month_expense(),
            'low_stock_count' => $this->get_low_stock_count(),
        );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline mb-3"><?php esc_html_e('Restaurant POS Dashboard', 'obydullah-restaurant-pos-lite'); ?></h1>
            <hr class="wp-header-end">

            <!-- Main Metrics Grid -->
            <div class="row mb-4">
                <!-- Low Stock Count -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-info">
                        <h3 class="fs-6 fw-normal text-muted mb-2">
                            <?php esc_html_e('Low Stock Items', 'obydullah-restaurant-pos-lite'); ?>
                        </h3>
                        <p class="summary-number text-info mb-0 fs-3 fw-bold">
                            <?php echo esc_html($dashboard_data['low_stock_count']); ?>
                        </p>
                        <small class="text-muted mb-3">
                            <?php esc_html_e('Items with low stock', 'obydullah-restaurant-pos-lite'); ?>
                        </small>
                    </div>
                </div>

                <!-- Stock Value -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-info">
                        <h3 class="fs-6 fw-normal text-muted mb-2"><?php esc_html_e('Stock Value', 'obydullah-restaurant-pos-lite'); ?></h3>
                        <p class="summary-number text-info mb-0 fs-3 fw-bold"><?php echo esc_html($this->format_currency($dashboard_data['stock_value'])); ?></p>
                        <small class="text-muted mb-3"><?php esc_html_e('Current inventory value', 'obydullah-restaurant-pos-lite'); ?></small>
                    </div>
                </div>

                <!-- Today's Sales -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-success">
                        <h3 class="fs-6 fw-normal text-muted mb-2"><?php esc_html_e("Today's Sales", 'obydullah-restaurant-pos-lite'); ?></h3>
                        <p class="summary-number text-success mb-0 fs-3 fw-bold"><?php echo esc_html($this->format_number($dashboard_data['today_sale'])); ?></p>
                        <small class="text-muted mb-3"><?php esc_html_e('Completed orders today', 'obydullah-restaurant-pos-lite'); ?></small>
                    </div>
                </div>

                <!-- Monthly Sales -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-primary">
                        <h3 class="fs-6 fw-normal text-muted mb-2"><?php esc_html_e('Monthly Sales', 'obydullah-restaurant-pos-lite'); ?></h3>
                        <p class="summary-number text-success mb-0 fs-3 fw-bold"><?php echo esc_html($this->format_number($dashboard_data['month_sale'])); ?></p>
                        <small class="text-muted mb-3"><?php esc_html_e('Total orders this month', 'obydullah-restaurant-pos-lite'); ?></small>
                    </div>
                </div>

                <!-- Today's Income -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-lime">
                        <h3 class="fs-6 fw-normal text-muted mb-2"><?php esc_html_e("Today's Income", 'obydullah-restaurant-pos-lite'); ?></h3>
                        <p class="summary-number text-lime mb-0 fs-3 fw-bold"><?php echo esc_html($this->format_currency($dashboard_data['today_income'])); ?></p>
                        <small class="text-muted mb-3"><?php esc_html_e('Revenue generated today', 'obydullah-restaurant-pos-lite'); ?></small>
                    </div>
                </div>

                <!-- Monthly Income -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-success">
                        <h3 class="fs-6 fw-normal text-muted mb-2"><?php esc_html_e('Monthly Income', 'obydullah-restaurant-pos-lite'); ?></h3>
                        <p class="summary-number text-success mb-0 fs-3 fw-bold"><?php echo esc_html($this->format_currency($dashboard_data['month_income'])); ?></p>
                        <small class="text-muted mb-3"><?php esc_html_e('Total revenue this month', 'obydullah-restaurant-pos-lite'); ?></small>
                    </div>
                </div>

                <!-- Today's Expense -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-warning">
                        <h3 class="fs-6 fw-normal text-muted mb-2"><?php esc_html_e("Today's Expense", 'obydullah-restaurant-pos-lite'); ?></h3>
                        <p class="summary-number text-warning mb-0 fs-3 fw-bold"><?php echo esc_html($this->format_currency($dashboard_data['today_expense'])); ?></p>
                        <small class="text-muted mb-3"><?php esc_html_e('Expenses incurred today', 'obydullah-restaurant-pos-lite'); ?></small>
                    </div>
                </div>

                <!-- Monthly Expense -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-danger">
                        <h3 class="fs-6 fw-normal text-muted mb-2"><?php esc_html_e('Monthly Expense', 'obydullah-restaurant-pos-lite'); ?></h3>
                        <p class="summary-number text-danger mb-0 fs-3 fw-bold"><?php echo esc_html($this->format_currency($dashboard_data['month_expense'])); ?></p>
                        <small class="text-muted mb-3"><?php esc_html_e('Total expenses this month', 'obydullah-restaurant-pos-lite'); ?></small>
                    </div>
                </div>
            </div>

            <!-- Top Selling Products -->
            <div class="row mt-4">
                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="bg-light p-4 rounded shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3 class="fs-6 fw-semibold mb-0">
                                <?php esc_html_e('Top Selling Products', 'obydullah-restaurant-pos-lite'); ?>
                            </h3>
                        </div>

                        <?php $top_products = $this->get_top_products(5); ?>

                        <?php if (!empty($top_products)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr class="bg-primary text-white">
                                            <th class="ps-4"><?php esc_html_e('Product', 'obydullah-restaurant-pos-lite'); ?></th>
                                            <th class="text-center"><?php esc_html_e('Orders', 'obydullah-restaurant-pos-lite'); ?></th>
                                            <th class="text-center"><?php esc_html_e('Qty Sold', 'obydullah-restaurant-pos-lite'); ?></th>
                                            <th class="text-center"><?php esc_html_e('Revenue', 'obydullah-restaurant-pos-lite'); ?></th>
                                            <th class="text-center pe-4"><?php esc_html_e('Avg. Price', 'obydullah-restaurant-pos-lite'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($top_products as $product): ?>
                                            <tr>
                                                <td>
                                                    <?php echo esc_html($product['name']); ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        Status: <?php echo esc_html(ucfirst($product['product_status'])); ?>
                                                    </small>
                                                </td>

                                                <td class="text-center fw-bold">
                                                    <?php echo esc_html($this->format_number($product['total_orders'])); ?>
                                                </td>

                                                <td class="text-center fw-bold text-primary">
                                                    <?php echo esc_html($this->format_number($product['total_sold'])); ?>
                                                </td>

                                                <td class="text-center fw-bold text-success">
                                                    <?php echo esc_html($this->format_currency($product['total_revenue'])); ?>
                                                </td>

                                                <td class="text-center text-muted pe-4">
                                                    <?php echo esc_html($this->format_currency($product['avg_price'])); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <div class="py-4">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="text-muted mb-3">
                                        <path d="M3 3h18v18H3zM8 8v8m8-8v8m-4-4v4" />
                                    </svg>
                                    <p class="mb-0 text-muted"><?php esc_html_e('No sales data available.', 'obydullah-restaurant-pos-lite'); ?></p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
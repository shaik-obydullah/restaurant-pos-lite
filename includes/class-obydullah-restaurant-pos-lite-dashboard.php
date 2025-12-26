<?php
/**
 * Obydullah Restaurant POS Lite Dashboard Class
 * Handles all dashboard functionality for the Restaurant POS plugin
 *
 * @package Obydullah_Restaurant_POS_Lite
 * @since 1.0.0
 */

// Prevent direct access
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
                "SELECT SUM(paid_amount)
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
                "SELECT SUM(paid_amount) 
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
        );
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline mb-3"><?php esc_html_e('Restaurant POS Dashboard', 'obydullah-restaurant-pos-lite'); ?></h1>
            <hr class="wp-header-end">

            <!-- Main Metrics Grid -->
            <div class="row mb-4">
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
                        <p class="summary-number text-primary mb-0 fs-3 fw-bold"><?php echo esc_html($this->format_number($dashboard_data['month_sale'])); ?></p>
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

                <!-- Monthly Profit -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="bg-light p-4 rounded shadow-sm stock-summary-card border-left border-lime">
                        <h3 class="fs-6 fw-normal text-muted mb-2"><?php esc_html_e('Monthly Profit', 'obydullah-restaurant-pos-lite'); ?></h3>
                        <p class="summary-number text-lime mb-0 fs-3 fw-bold">
                            <?php echo esc_html($this->format_currency($dashboard_data['month_income'] - $dashboard_data['month_expense'])); ?>
                        </p>
                        <small class="text-muted mb-3"><?php esc_html_e('Net profit this month', 'obydullah-restaurant-pos-lite'); ?></small>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
<?php
/**
 * Restaurant POS Lite Dashboard Class
 * Handles all dashboard functionality for the Restaurant POS plugin
 *
 * @package Restaurant_POS_Lite
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Dashboard class for Restaurant POS Lite
 */
class Restaurant_POS_Lite_Dashboard
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
        $this->table_sales = $wpdb->prefix . 'pos_sales';
        $this->table_sale_details = $wpdb->prefix . 'pos_sale_details';
        $this->table_stocks = $wpdb->prefix . 'pos_stocks';
        $this->table_accounting = $wpdb->prefix . 'pos_accounting';
        $this->table_products = $wpdb->prefix . 'pos_products';
    }

    /**
     * Format currency using helper class
     *
     * @param float $amount Amount to format.
     * @return string
     */
    private function format_currency($amount)
    {
        if (class_exists('Restaurant_POS_Lite_Helpers')) {
            return Restaurant_POS_Lite_Helpers::format_currency($amount);
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
        if (class_exists('Restaurant_POS_Lite_Helpers')) {
            return Restaurant_POS_Lite_Helpers::get_currency_symbol();
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
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT SUM(quantity * net_cost) AS total_value 
             FROM {$this->table_stocks} 
             WHERE status != %s",
                'outStock'
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

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) 
             FROM {$this->table_sales} 
             WHERE status = 'completed' AND DATE(created_at) = %s",
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

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT COUNT(*) 
             FROM {$this->table_sales} 
             WHERE status = 'completed' AND DATE(created_at) BETWEEN %s AND %s",
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

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT SUM(paid_amount)
             FROM {$this->table_sales} 
             WHERE status = 'completed' AND DATE(created_at) = %s",
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

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
        $result = $this->wpdb->get_var(
            $this->wpdb->prepare(
                "SELECT SUM(paid_amount) 
            FROM {$this->table_sales} 
            WHERE DATE(created_at) BETWEEN %s AND %s 
            AND status = 'completed'",
                $first_day,
                $last_day
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

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
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

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
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
     * Get weekly sales trend data
     *
     * @return array
     */
    private function get_weekly_sales_trend()
    {
        $weekly_sales = array();

        // Get sales for the last 7 days using WordPress timezone.
        for ($i = 6; $i >= 0; $i--) {
            $date = gmdate('Y-m-d', strtotime("-$i days", current_time('timestamp')));
            $day_name = gmdate('D', strtotime($date));

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
            $result = $this->wpdb->get_var(
                $this->wpdb->prepare(
                    "SELECT SUM(paid_amount) as daily_sales
                FROM {$this->table_sales} 
                WHERE DATE(created_at) = %s 
                AND status = 'completed'",
                    $date
                )
            );

            $weekly_sales[] = array(
                'day' => $day_name,
                'sales' => $result ? floatval($result) : 0,
            );
        }

        return $weekly_sales;
    }

    /**
     * Get top selling products
     *
     * @return array
     */
    private function get_top_products()
    {
        $thirty_days_ago = gmdate('Y-m-d', strtotime('-30 days', current_time('timestamp')));

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.NotPrepared
        $result = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT 
                p.name as product_name,
                COUNT(sd.id) as sales_count,
                SUM(sd.quantity) as total_quantity,
                SUM(sd.quantity * sd.unit_price) as total_revenue
            FROM {$this->table_sale_details} sd
            INNER JOIN {$this->table_sales} s ON sd.fk_sale_id = s.id
            INNER JOIN {$this->table_products} p ON sd.fk_product_id = p.id
            WHERE s.status = 'completed'
            AND DATE(s.created_at) >= %s
            GROUP BY p.id, p.name
            ORDER BY total_revenue DESC
            LIMIT 5",
                $thirty_days_ago
            )
        );

        return $result ?: array();
    }

    /**
     * Enqueue dashboard styles
     */
    public function enqueue_styles()
    {
        wp_enqueue_style(
            'restaurant-pos-dashboard',
            plugin_dir_url(__FILE__) . 'assets/css/dashboard.css',
            array(),
            '1.0.0'
        );
    }

    /**
     * Render the dashboard page
     */
    public function render_page()
    {
        // Enqueue styles.
        $this->enqueue_styles();

        // Get real data.
        $dashboard_data = array(
            'stock_value' => $this->get_stock_value(),
            'today_sale' => $this->get_today_sales_count(),
            'month_sale' => $this->get_month_sales_count(),
            'today_income' => $this->get_today_income(),
            'month_income' => $this->get_month_income(),
            'today_expense' => $this->get_today_expense(),
            'month_expense' => $this->get_month_expense(),
        );

        // Get chart data.
        $income_expense = array(
            'income' => $this->get_month_income(),
            'expense' => $this->get_month_expense(),
        );

        $weekly_sales = $this->get_weekly_sales_trend();
        $top_products = $this->get_top_products();

        // Calculate bar heights (max height 80% for the highest value).
        $max_value = max($income_expense['income'], $income_expense['expense']);
        $income_height = $max_value > 0 ? ($income_expense['income'] / $max_value) * 80 : 0;
        $expense_height = $max_value > 0 ? ($income_expense['expense'] / $max_value) * 80 : 0;

        // Calculate weekly sales max for chart scaling.
        $weekly_sales_values = array_column($weekly_sales, 'sales');
        $weekly_sales_max = !empty($weekly_sales_values) ? max($weekly_sales_values) : 0;
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Restaurant POS Dashboard', 'restaurant-pos-lite'); ?></h1>

            <div class="wp-restaurant-pos-dashboard">
                <!-- Main Metrics Grid -->
                <div class="dashboard-grid">
                    <div class="dashboard-card financial-card">
                        <div class="card-icon">💰</div>
                        <div class="card-content">
                            <h3><?php esc_html_e('Stock Value', 'restaurant-pos-lite'); ?></h3>
                            <p class="number"><?php echo esc_html($this->format_currency($dashboard_data['stock_value'])); ?>
                            </p>
                            <span
                                class="card-description"><?php esc_html_e('Current inventory value', 'restaurant-pos-lite'); ?></span>
                        </div>
                    </div>

                    <div class="dashboard-card sales-card">
                        <div class="card-icon">📊</div>
                        <div class="card-content">
                            <h3><?php esc_html_e("Today's Sales", 'restaurant-pos-lite'); ?></h3>
                            <p class="number"><?php echo esc_html($this->format_number($dashboard_data['today_sale'])); ?></p>

                            <span
                                class="card-description"><?php esc_html_e('Completed orders today', 'restaurant-pos-lite'); ?></span>
                        </div>
                    </div>

                    <div class="dashboard-card sales-card">
                        <div class="card-icon">📈</div>
                        <div class="card-content">
                            <h3><?php esc_html_e('Monthly Sales', 'restaurant-pos-lite'); ?></h3>
                            <p class="number"><?php echo esc_html($this->format_number($dashboard_data['month_sale'])); ?></p>

                            <span
                                class="card-description"><?php esc_html_e('Total orders this month', 'restaurant-pos-lite'); ?></span>
                        </div>
                    </div>

                    <div class="dashboard-card income-card">
                        <div class="card-icon">💵</div>
                        <div class="card-content">
                            <h3><?php esc_html_e("Today's Income", 'restaurant-pos-lite'); ?></h3>
                            <p class="number"><?php echo esc_html($this->format_currency($dashboard_data['today_income'])); ?>
                            </p>

                            <span
                                class="card-description"><?php esc_html_e('Revenue generated today', 'restaurant-pos-lite'); ?></span>
                        </div>
                    </div>

                    <div class="dashboard-card income-card">
                        <div class="card-icon">💰</div>
                        <div class="card-content">
                            <h3><?php esc_html_e('Monthly Income', 'restaurant-pos-lite'); ?></h3>
                            <p class="number"><?php echo esc_html($this->format_currency($dashboard_data['month_income'])); ?>
                            </p>
                            <span
                                class="card-description"><?php esc_html_e('Total revenue this month', 'restaurant-pos-lite'); ?></span>
                        </div>
                    </div>

                    <div class="dashboard-card expense-card">
                        <div class="card-icon">💸</div>
                        <div class="card-content">
                            <h3><?php esc_html_e("Today's Expense", 'restaurant-pos-lite'); ?></h3>
                            <p class="number"><?php echo esc_html($this->format_currency($dashboard_data['today_expense'])); ?>
                            </p>
                            <span
                                class="card-description"><?php esc_html_e('Expenses incurred today', 'restaurant-pos-lite'); ?></span>
                        </div>
                    </div>

                    <div class="dashboard-card expense-card">
                        <div class="card-icon">📉</div>
                        <div class="card-content">
                            <h3><?php esc_html_e('Monthly Expense', 'restaurant-pos-lite'); ?></h3>
                            <p class="number"><?php echo esc_html($this->format_currency($dashboard_data['month_expense'])); ?>
                            </p>
                            <span
                                class="card-description"><?php esc_html_e('Total expenses this month', 'restaurant-pos-lite'); ?></span>
                        </div>
                    </div>

                    <div class="dashboard-card profit-card">
                        <div class="card-icon">📊</div>
                        <div class="card-content">
                            <h3><?php esc_html_e('Monthly Profit', 'restaurant-pos-lite'); ?></h3>
                            <p class="number">
                                <?php echo esc_html($this->format_currency($dashboard_data['month_income'] - $dashboard_data['month_expense'])); ?>
                            </p>
                            <span
                                class="card-description"><?php esc_html_e('Net profit this month', 'restaurant-pos-lite'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="charts-section">
                    <div class="chart-container">
                        <h3><?php esc_html_e('Income vs Expense (This Month)', 'restaurant-pos-lite'); ?></h3>
                        <div class="bar-chart">
                            <div class="bar-income" style="height: <?php echo esc_attr($income_height); ?>%">
                                <span class="bar-label"><?php esc_html_e('Income', 'restaurant-pos-lite'); ?></span>
                                <span
                                    class="bar-value"><?php echo esc_html($this->format_currency($income_expense['income'])); ?></span>
                            </div>
                            <div class="bar-expense" style="height: <?php echo esc_attr($expense_height); ?>%">
                                <span class="bar-label"><?php esc_html_e('Expense', 'restaurant-pos-lite'); ?></span>
                                <span
                                    class="bar-value"><?php echo esc_html($this->format_currency($income_expense['expense'])); ?></span>
                            </div>
                        </div>
                        <div class="chart-amounts">
                            <div class="amount-item">
                                <span class="amount-label"><?php esc_html_e('Total Income:', 'restaurant-pos-lite'); ?></span>
                                <span
                                    class="amount-value income"><?php echo esc_html($this->format_currency($income_expense['income'])); ?></span>
                            </div>
                            <div class="amount-item">
                                <span class="amount-label"><?php esc_html_e('Total Expense:', 'restaurant-pos-lite'); ?></span>
                                <span
                                    class="amount-value expense"><?php echo esc_html($this->format_currency($income_expense['expense'])); ?></span>
                            </div>
                            <div class="amount-item">
                                <span class="amount-label"><?php esc_html_e('Net Profit:', 'restaurant-pos-lite'); ?></span>
                                <span
                                    class="amount-value profit"><?php echo esc_html($this->format_currency($income_expense['income'] - $income_expense['expense'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="chart-container">
                        <h3><?php esc_html_e('Weekly Sales Trend', 'restaurant-pos-lite'); ?></h3>
                        <div class="line-chart">
                            <?php foreach ($weekly_sales as $day_data): ?>
                                <?php
                                $height = $weekly_sales_max > 0 ? ($day_data['sales'] / $weekly_sales_max) * 80 : 0;
                                ?>
                                <div class="line-chart-bar">
                                    <div class="line-chart-value" style="height: <?php echo esc_attr($height); ?>%">
                                        <span
                                            class="line-tooltip"><?php echo esc_html($this->format_currency($day_data['sales'])); ?></span>
                                    </div>
                                    <span class="line-chart-label"><?php echo esc_html($day_data['day']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Top Products Section -->
                <div class="top-products-section">
                    <h3><?php esc_html_e('Top Selling Products (Last 30 Days)', 'restaurant-pos-lite'); ?></h3>
                    <div class="top-products-grid">
                        <?php if (!empty($top_products)): ?>
                            <?php foreach ($top_products as $index => $product): ?>
                                <div class="product-card">
                                    <div class="product-rank">#<?php echo esc_html($index + 1); ?></div>
                                    <div class="product-info">
                                        <h4 class="product-name"><?php echo esc_html($product->product_name); ?></h4>
                                        <div class="product-stats">
                                            <span class="product-stat">
                                                <strong><?php echo esc_html($this->format_number($product->sales_count)); ?></strong>
                                                <?php esc_html_e('sales', 'restaurant-pos-lite'); ?>
                                            </span>
                                            <span class="product-stat">
                                                <strong><?php echo esc_html($this->format_number($product->total_quantity)); ?></strong>
                                                <?php esc_html_e('units', 'restaurant-pos-lite'); ?>
                                            </span>
                                            <span class="product-stat revenue">
                                                <?php echo esc_html($this->format_currency($product->total_revenue)); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="no-products">
                                <p><?php esc_html_e('No sales data available for the last 30 days.', 'restaurant-pos-lite'); ?>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
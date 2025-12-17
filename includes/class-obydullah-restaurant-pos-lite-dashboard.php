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
     * Get hourly sales heatmap data for today
     *
     * @return array
     */
    private function get_hourly_sales_heatmap()
    {
        $today = current_time('Y-m-d');
        
        // Get hourly sales data for today
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare(
                "SELECT 
                    HOUR(created_at) as hour,
                    COUNT(*) as order_count,
                    SUM(paid_amount) as revenue
                FROM {$this->table_sales} 
                WHERE DATE(created_at) = %s 
                AND status = 'completed'
                GROUP BY HOUR(created_at)
                ORDER BY hour ASC",
                $today
            )
        );
        
        // Create array for all 24 hours with default values
        $hourly_data = array();
        for ($i = 0; $i < 24; $i++) {
            $hour_label = '';
            if ($i == 0) {
                $hour_label = '12 AM';
            } elseif ($i < 12) {
                $hour_label = $i . ' AM';
            } elseif ($i == 12) {
                $hour_label = '12 PM';
            } else {
                $hour_label = ($i - 12) . ' PM';
            }
            
            $hourly_data[$i] = array(
                'hour' => $i,
                'order_count' => 0,
                'revenue' => 0,
                'label' => $hour_label,
                'period' => $i < 12 ? 'AM' : 'PM'
            );
        }
        
        // Fill with actual data from database
        foreach ($results as $row) {
            $hour = intval($row->hour);
            if (isset($hourly_data[$hour])) {
                $hourly_data[$hour]['order_count'] = intval($row->order_count);
                $hourly_data[$hour]['revenue'] = floatval($row->revenue);
            }
        }
        
        return array_values($hourly_data);
    }

    /**
     * Get top selling products
     *
     * @return array
     */
    private function get_top_products()
    {
        $thirty_days_ago = gmdate('Y-m-d', strtotime('-30 days', current_time('timestamp')));

        $status = 'completed';
        $limit = 5;

        $query = $this->wpdb->prepare(
            "SELECT 
            p.name as product_name,
            COUNT(sd.id) as sales_count,
            SUM(sd.quantity) as total_quantity,
            SUM(sd.quantity * sd.unit_price) as total_revenue
        FROM {$this->table_sale_details} sd
        INNER JOIN {$this->table_sales} s ON sd.fk_sale_id = s.id
        INNER JOIN {$this->table_products} p ON sd.fk_product_id = p.id
        WHERE s.status = '%s'
        AND DATE(s.created_at) >= %s
        GROUP BY p.id, p.name
        ORDER BY total_revenue DESC
        LIMIT %d",
            $status,
            $thirty_days_ago,
            $limit
        );

        $result = $this->wpdb->get_results($query);

        return $result ?: array();
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

        $income_expense = array(
            'income' => $this->get_month_income(),
            'expense' => $this->get_month_expense(),
        );

        $hourly_sales = $this->get_hourly_sales_heatmap();
        $top_products = $this->get_top_products();

        // Calculate bar heights for income vs expense
        $max_value = max($income_expense['income'], $income_expense['expense']);
        $income_height = $max_value > 0 ? ($income_expense['income'] / $max_value) * 80 : 0;
        $expense_height = $max_value > 0 ? ($income_expense['expense'] / $max_value) * 80 : 0;

        // Calculate max values for hourly heatmap
        $hourly_order_counts = array_column($hourly_sales, 'order_count');
        $hourly_revenues = array_column($hourly_sales, 'revenue');
        $max_order_count = !empty($hourly_order_counts) ? max($hourly_order_counts) : 0;
        $max_revenue = !empty($hourly_revenues) ? max($hourly_revenues) : 0;
        $total_today_revenue = array_sum($hourly_revenues);
        ?>
        <div class="wrap orpl-admin-page">
            <h1><?php esc_html_e('Restaurant POS Dashboard', 'obydullah-restaurant-pos-lite'); ?></h1>

            <div class="wp-restaurant-pos-dashboard">
                <!-- Main Metrics Grid -->
                <div class="dashboard-grid">
                    <div class="orpl-card dashboard-card financial-card">
                        <div class="card-content">
                            <h3><?php esc_html_e('Stock Value', 'obydullah-restaurant-pos-lite'); ?></h3>
                            <p class="orpl-summary-number"><?php echo esc_html($this->format_currency($dashboard_data['stock_value'])); ?></p>
                            <span class="card-description"><?php esc_html_e('Current inventory value', 'obydullah-restaurant-pos-lite'); ?></span>
                        </div>
                    </div>
                    <div class="orpl-card dashboard-card sales-card">
                        <div class="card-content">
                            <h3><?php esc_html_e("Today's Sales", 'obydullah-restaurant-pos-lite'); ?></h3>
                            <p class="orpl-summary-number"><?php echo esc_html($this->format_number($dashboard_data['today_sale'])); ?></p>
                            <span class="card-description"><?php esc_html_e('Completed orders today', 'obydullah-restaurant-pos-lite'); ?></span>
                        </div>
                    </div>

                    <div class="orpl-card dashboard-card sales-card">
                        <div class="card-content">
                            <h3><?php esc_html_e('Monthly Sales', 'obydullah-restaurant-pos-lite'); ?></h3>
                            <p class="orpl-summary-number"><?php echo esc_html($this->format_number($dashboard_data['month_sale'])); ?></p>
                            <span class="card-description"><?php esc_html_e('Total orders this month', 'obydullah-restaurant-pos-lite'); ?></span>
                        </div>
                    </div>

                    <div class="orpl-card dashboard-card income-card">
                        <div class="card-content">
                            <h3><?php esc_html_e("Today's Income", 'obydullah-restaurant-pos-lite'); ?></h3>
                            <p class="orpl-summary-number"><?php echo esc_html($this->format_currency($dashboard_data['today_income'])); ?></p>
                            <span class="card-description"><?php esc_html_e('Revenue generated today', 'obydullah-restaurant-pos-lite'); ?></span>
                        </div>
                    </div>

                    <div class="orpl-card dashboard-card income-card">
                        <div class="card-content">
                            <h3><?php esc_html_e('Monthly Income', 'obydullah-restaurant-pos-lite'); ?></h3>
                            <p class="orpl-summary-number"><?php echo esc_html($this->format_currency($dashboard_data['month_income'])); ?></p>
                            <span class="card-description"><?php esc_html_e('Total revenue this month', 'obydullah-restaurant-pos-lite'); ?></span>
                        </div>
                    </div>

                    <div class="orpl-card dashboard-card expense-card">
                        <div class="card-content">
                            <h3><?php esc_html_e("Today's Expense", 'obydullah-restaurant-pos-lite'); ?></h3>
                            <p class="orpl-summary-number"><?php echo esc_html($this->format_currency($dashboard_data['today_expense'])); ?></p>
                            <span class="card-description"><?php esc_html_e('Expenses incurred today', 'obydullah-restaurant-pos-lite'); ?></span>
                        </div>
                    </div>

                    <div class="orpl-card dashboard-card expense-card">
                        <div class="card-content">
                            <h3><?php esc_html_e('Monthly Expense', 'obydullah-restaurant-pos-lite'); ?></h3>
                            <p class="orpl-summary-number"><?php echo esc_html($this->format_currency($dashboard_data['month_expense'])); ?></p>
                            <span class="card-description"><?php esc_html_e('Total expenses this month', 'obydullah-restaurant-pos-lite'); ?></span>
                        </div>
                    </div>

                    <div class="orpl-card dashboard-card profit-card">
                        <div class="card-content">
                            <h3><?php esc_html_e('Monthly Profit', 'obydullah-restaurant-pos-lite'); ?></h3>
                            <p class="orpl-summary-number">
                                <?php echo esc_html($this->format_currency($dashboard_data['month_income'] - $dashboard_data['month_expense'])); ?>
                            </p>
                            <span class="card-description"><?php esc_html_e('Net profit this month', 'obydullah-restaurant-pos-lite'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="charts-section">
                    <div class="orpl-card chart-container">
                        <h3><?php esc_html_e('Income vs Expense (This Month)', 'obydullah-restaurant-pos-lite'); ?></h3>
                        <div class="bar-chart">
                            <div class="bar-income" style="height: <?php echo esc_attr($income_height); ?>%">
                                <span class="bar-label"><?php esc_html_e('Income', 'obydullah-restaurant-pos-lite'); ?></span>
                                <span class="bar-value"><?php echo esc_html($this->format_currency($income_expense['income'])); ?></span>
                            </div>
                            <div class="bar-expense" style="height: <?php echo esc_attr($expense_height); ?>%">
                                <span class="bar-label"><?php esc_html_e('Expense', 'obydullah-restaurant-pos-lite'); ?></span>
                                <span class="bar-value"><?php echo esc_html($this->format_currency($income_expense['expense'])); ?></span>
                            </div>
                        </div>
                        <div class="chart-amounts">
                            <div class="amount-item">
                                <span class="amount-label"><?php esc_html_e('Total Income:', 'obydullah-restaurant-pos-lite'); ?></span>
                                <span class="amount-value orpl-profit-positive"><?php echo esc_html($this->format_currency($income_expense['income'])); ?></span>
                            </div>
                            <div class="amount-item">
                                <span class="amount-label"><?php esc_html_e('Total Expense:', 'obydullah-restaurant-pos-lite'); ?></span>
                                <span class="amount-value orpl-profit-negative"><?php echo esc_html($this->format_currency($income_expense['expense'])); ?></span>
                            </div>
                            <div class="amount-item">
                                <span class="amount-label"><?php esc_html_e('Net Profit:', 'obydullah-restaurant-pos-lite'); ?></span>
                                <span class="amount-value orpl-profit-positive"><?php echo esc_html($this->format_currency($income_expense['income'] - $income_expense['expense'])); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hourly Sales Heatmap Chart -->
                    <div class="orpl-card chart-container">
                        <h3><?php esc_html_e('Today\'s Hourly Sales Heatmap', 'obydullah-restaurant-pos-lite'); ?></h3>
                        <div class="hourly-heatmap">
                            <div class="heatmap-header">
                                <div class="heatmap-time-periods">
                                    <span class="time-period am-period">AM</span>
                                    <span class="time-period pm-period">PM</span>
                                </div>
                                <div class="heatmap-stats">
                                    <span class="heatmap-stat">
                                        <strong><?php echo esc_html($this->format_number($dashboard_data['today_sale'])); ?></strong>
                                        <?php esc_html_e('Orders', 'obydullah-restaurant-pos-lite'); ?>
                                    </span>
                                    <span class="heatmap-stat">
                                        <strong><?php echo esc_html($this->format_currency($total_today_revenue)); ?></strong>
                                        <?php esc_html_e('Revenue', 'obydullah-restaurant-pos-lite'); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="heatmap-grid">
                                <?php foreach ($hourly_sales as $hour_data): ?>
                                    <?php
                                    // Calculate intensity based on revenue (green intensity)
                                    $revenue_intensity = $max_revenue > 0 ? ($hour_data['revenue'] / $max_revenue) * 100 : 0;
                                    $bg_color = $revenue_intensity > 0 ? 
                                        'hsl(120, ' . min(100, 30 + $revenue_intensity * 0.7) . '%, ' . max(30, 90 - $revenue_intensity * 0.5) . '%)' : 
                                        '#f0f0f0';
                                    
                                    // Calculate height based on order count
                                    $order_height = $max_order_count > 0 ? ($hour_data['order_count'] / $max_order_count) * 80 : 0;
                                    ?>
                                    <div class="heatmap-hour" style="background-color: <?php echo esc_attr($bg_color); ?>">
                                        <div class="heatmap-bar" style="height: <?php echo esc_attr($order_height); ?>%">
                                            <div class="heatmap-tooltip">
                                                <div class="tooltip-time"><?php echo esc_html($hour_data['label']); ?></div>
                                                <div class="tooltip-orders">
                                                    <span class="tooltip-label"><?php esc_html_e('Orders:', 'obydullah-restaurant-pos-lite'); ?></span>
                                                    <span class="tooltip-value"><?php echo esc_html($this->format_number($hour_data['order_count'])); ?></span>
                                                </div>
                                                <div class="tooltip-revenue">
                                                    <span class="tooltip-label"><?php esc_html_e('Revenue:', 'obydullah-restaurant-pos-lite'); ?></span>
                                                    <span class="tooltip-value"><?php echo esc_html($this->format_currency($hour_data['revenue'])); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="heatmap-label"><?php echo esc_html($hour_data['label']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="heatmap-legend">
                                <div class="legend-item">
                                    <span class="legend-color" style="background-color: #f0f0f0;"></span>
                                    <span class="legend-text"><?php esc_html_e('No Sales', 'obydullah-restaurant-pos-lite'); ?></span>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color" style="background-color: hsl(120, 30%, 90%);"></span>
                                    <span class="legend-text"><?php esc_html_e('Low', 'obydullah-restaurant-pos-lite'); ?></span>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color" style="background-color: hsl(120, 65%, 70%);"></span>
                                    <span class="legend-text"><?php esc_html_e('Medium', 'obydullah-restaurant-pos-lite'); ?></span>
                                </div>
                                <div class="legend-item">
                                    <span class="legend-color" style="background-color: hsl(120, 100%, 40%);"></span>
                                    <span class="legend-text"><?php esc_html_e('High', 'obydullah-restaurant-pos-lite'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Products Section -->
                <div class="orpl-card top-products-section">
                    <h3><?php esc_html_e('Top Selling Products (Last 30 Days)', 'obydullah-restaurant-pos-lite'); ?></h3>
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
                                                <?php esc_html_e('sales', 'obydullah-restaurant-pos-lite'); ?>
                                            </span>
                                            <span class="product-stat">
                                                <strong><?php echo esc_html($this->format_number($product->total_quantity)); ?></strong>
                                                <?php esc_html_e('units', 'obydullah-restaurant-pos-lite'); ?>
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
                                <p><?php esc_html_e('No sales data available for the last 30 days.', 'obydullah-restaurant-pos-lite'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
<?php
/**
 * Restaurant POS Lite Settings Class
 *
 * @package Restaurant_POS_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Restaurant_POS_Lite_Settings
{

    public function __construct()
    {
        add_action('admin_init', array($this, 'register_settings'));
    }

    public function register_settings()
    {
        register_setting('restaurant_pos_lite_settings_group', 'restaurant_pos_lite_settings', array($this, 'sanitize_settings'));

        // General Settings Section
        add_settings_section(
            'restaurant_pos_lite_general_section',
            __('General Settings', 'restaurant-pos-lite'),
            array($this, 'general_section_callback'),
            'restaurant-pos-lite-settings'
        );

        add_settings_field(
            'date_format',
            __('Date Format', 'restaurant-pos-lite'),
            array($this, 'date_format_callback'),
            'restaurant-pos-lite-settings',
            'restaurant_pos_lite_general_section'
        );

        add_settings_field(
            'currency',
            __('Currency', 'restaurant-pos-lite'),
            array($this, 'currency_callback'),
            'restaurant-pos-lite-settings',
            'restaurant_pos_lite_general_section'
        );

        add_settings_field(
            'currency_position',
            __('Currency Position', 'restaurant-pos-lite'),
            array($this, 'currency_position_callback'),
            'restaurant-pos-lite-settings',
            'restaurant_pos_lite_general_section'
        );

        add_settings_field(
            'vat_rate',
            __('VAT Rate (%)', 'restaurant-pos-lite'),
            array($this, 'vat_rate_callback'),
            'restaurant-pos-lite-settings',
            'restaurant_pos_lite_general_section'
        );

        add_settings_field(
            'tax_rate',
            __('Tax Rate (%)', 'restaurant-pos-lite'),
            array($this, 'tax_rate_callback'),
            'restaurant-pos-lite-settings',
            'restaurant_pos_lite_general_section'
        );

        // Shop Information Section
        add_settings_section(
            'restaurant_pos_lite_shop_section',
            __('Shop Information', 'restaurant-pos-lite'),
            array($this, 'shop_section_callback'),
            'restaurant-pos-lite-settings'
        );

        add_settings_field(
            'shop_name',
            __('Restaurant Name', 'restaurant-pos-lite'),
            array($this, 'shop_name_callback'),
            'restaurant-pos-lite-settings',
            'restaurant_pos_lite_shop_section'
        );

        add_settings_field(
            'shop_address',
            __('Address', 'restaurant-pos-lite'),
            array($this, 'shop_address_callback'),
            'restaurant-pos-lite-settings',
            'restaurant_pos_lite_shop_section'
        );

        add_settings_field(
            'shop_phone',
            __('Phone Number', 'restaurant-pos-lite'),
            array($this, 'shop_phone_callback'),
            'restaurant-pos-lite-settings',
            'restaurant_pos_lite_shop_section'
        );

        add_settings_field(
            'shop_logo',
            __('Shop Logo', 'restaurant-pos-lite'),
            array($this, 'shop_logo_callback'),
            'restaurant-pos-lite-settings',
            'restaurant_pos_lite_shop_section'
        );
    }

    public function sanitize_settings($input)
    {
        $sanitized = array();

        // General Settings
        $sanitized['date_format'] = sanitize_text_field($input['date_format'] ?? 'Y-m-d');
        $sanitized['currency'] = sanitize_text_field($input['currency'] ?? '$');
        $sanitized['currency_position'] = sanitize_text_field($input['currency_position'] ?? 'left');
        $sanitized['vat_rate'] = floatval($input['vat_rate'] ?? '0');
        $sanitized['tax_rate'] = floatval($input['tax_rate'] ?? '0');

        // Shop Information
        $sanitized['shop_name'] = sanitize_text_field($input['shop_name'] ?? '');
        $sanitized['shop_address'] = sanitize_textarea_field($input['shop_address'] ?? '');
        $sanitized['shop_phone'] = sanitize_text_field($input['shop_phone'] ?? '');
        $sanitized['shop_logo'] = esc_url_raw($input['shop_logo'] ?? '');

        // Update individual options for helper class compatibility
        update_option('oby_restaurant_pos_lite_date_format', $sanitized['date_format']);
        update_option('oby_restaurant_pos_lite_currency', $sanitized['currency']);
        update_option('oby_restaurant_pos_lite_currency_position', $sanitized['currency_position']);
        update_option('oby_restaurant_pos_lite_vat_rate', $sanitized['vat_rate']);
        update_option('oby_restaurant_pos_lite_tax_rate', $sanitized['tax_rate']);
        update_option('oby_restaurant_pos_lite_shop_name', $sanitized['shop_name']);
        update_option('oby_restaurant_pos_lite_shop_address', $sanitized['shop_address']);
        update_option('oby_restaurant_pos_lite_shop_phone', $sanitized['shop_phone']);
        update_option('oby_restaurant_pos_lite_shop_logo', $sanitized['shop_logo']);

        // Add settings updated notice
        add_settings_error(
            'restaurant_pos_lite_settings',
            'restaurant_pos_lite_settings_updated',
            __('Settings saved successfully.', 'restaurant-pos-lite'),
            'success'
        );

        return $sanitized;
    }

    public function general_section_callback()
    {
        echo '<p>' . esc_html__('Configure general POS system settings.', 'restaurant-pos-lite') . '</p>';
    }

    public function shop_section_callback()
    {
        echo '<p>' . esc_html__('Enter your restaurant/shop information that will be used on receipts and reports.', 'restaurant-pos-lite') . '</p>';
    }

    public function date_format_callback()
    {
        $date_format = get_option('oby_restaurant_pos_lite_date_format', 'Y-m-d');

        $date_formats = array(
            'Y-m-d' => 'YYYY-MM-DD (2024-01-15)',
            'd/m/Y' => 'DD/MM/YYYY (15/01/2024)',
            'm/d/Y' => 'MM/DD/YYYY (01/15/2024)',
            'd-m-Y' => 'DD-MM-YYYY (15-01-2024)',
            'm-d-Y' => 'MM-DD-YYYY (01-15-2024)',
        );
        ?>
        <select name="restaurant_pos_lite_settings[date_format]" style="min-width: 200px;">
            <?php foreach ($date_formats as $value => $label): ?>
                <option value="<?php echo esc_attr($value); ?>" <?php selected($date_format, $value); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Select the date format to be used throughout the system.', 'restaurant-pos-lite'); ?>
        </p>
        <?php
    }

    public function currency_callback()
    {
        $currency = get_option('oby_restaurant_pos_lite_currency', '$');

        $currencies = array(
            '$' => 'US Dollar ($)',
            '€' => 'Euro (€)',
            '£' => 'British Pound (£)',
            '¥' => 'Japanese Yen (¥)',
            '₹' => 'Indian Rupee (₹)',
            '₽' => 'Russian Ruble (₽)',
            '₩' => 'Korean Won (₩)',
            '₪' => 'Israeli Shekel (₪)',
            '₫' => 'Vietnamese Dong (₫)',
            '฿' => 'Thai Baht (฿)',
            '₱' => 'Philippine Peso (₱)',
        );
        ?>
        <select name="restaurant_pos_lite_settings[currency]" style="min-width: 200px;">
            <?php foreach ($currencies as $symbol => $label): ?>
                <option value="<?php echo esc_attr($symbol); ?>" <?php selected($currency, $symbol); ?>>
                    <?php echo esc_html($label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            <?php esc_html_e('Select the currency symbol for your pricing.', 'restaurant-pos-lite'); ?>
        </p>
        <?php
    }

    public function currency_position_callback()
    {
        $position = get_option('oby_restaurant_pos_lite_currency_position', 'left');
        ?>
        <select name="restaurant_pos_lite_settings[currency_position]" style="min-width: 200px;">
            <option value="left" <?php selected($position, 'left'); ?>>
                <?php esc_html_e('Left ($100)', 'restaurant-pos-lite'); ?>
            </option>
            <option value="right" <?php selected($position, 'right'); ?>>
                <?php esc_html_e('Right (100$)', 'restaurant-pos-lite'); ?>
            </option>
            <option value="left_space" <?php selected($position, 'left_space'); ?>>
                <?php esc_html_e('Left with space ($ 100)', 'restaurant-pos-lite'); ?>
            </option>
            <option value="right_space" <?php selected($position, 'right_space'); ?>>
                <?php esc_html_e('Right with space (100 $)', 'restaurant-pos-lite'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Choose where the currency symbol appears relative to the amount.', 'restaurant-pos-lite'); ?>
        </p>
        <?php
    }

    public function vat_rate_callback()
    {
        $vat_rate = get_option('oby_restaurant_pos_lite_vat_rate', '0');
        ?>
        <input type="number" name="restaurant_pos_lite_settings[vat_rate]" value="<?php echo esc_attr($vat_rate); ?>"
            step="0.01" min="0" max="100" class="small-text" />
        <span>%</span>
        <p class="description">
            <?php esc_html_e('Enter the VAT rate as a percentage (e.g., 20 for 20%). Set to 0 to disable VAT.', 'restaurant-pos-lite'); ?>
        </p>
        <?php
    }

    public function tax_rate_callback()
    {
        $tax_rate = get_option('oby_restaurant_pos_lite_tax_rate', '0');
        ?>
        <input type="number" name="restaurant_pos_lite_settings[tax_rate]" value="<?php echo esc_attr($tax_rate); ?>"
            step="0.01" min="0" max="100" class="small-text" />
        <span>%</span>
        <p class="description">
            <?php esc_html_e('Enter the general tax rate as a percentage (e.g., 8.5 for 8.5%). Set to 0 to disable tax.', 'restaurant-pos-lite'); ?>
        </p>
        <?php
    }

    public function shop_name_callback()
    {
        $shop_name = get_option('oby_restaurant_pos_lite_shop_name', '');
        ?>
        <input type="text" name="restaurant_pos_lite_settings[shop_name]" value="<?php echo esc_attr($shop_name); ?>"
            class="regular-text" placeholder="<?php esc_attr_e('Enter restaurant name', 'restaurant-pos-lite'); ?>">
        <?php
    }

    public function shop_address_callback()
    {
        $shop_address = get_option('oby_restaurant_pos_lite_shop_address', '');
        ?>
        <textarea name="restaurant_pos_lite_settings[shop_address]" rows="3" class="large-text"
            placeholder="<?php esc_attr_e('Enter full address', 'restaurant-pos-lite'); ?>"><?php echo esc_textarea($shop_address); ?></textarea>
        <?php
    }

    public function shop_phone_callback()
    {
        $shop_phone = get_option('oby_restaurant_pos_lite_shop_phone', '');
        ?>
        <input type="text" name="restaurant_pos_lite_settings[shop_phone]" value="<?php echo esc_attr($shop_phone); ?>"
            class="regular-text" placeholder="<?php esc_attr_e('Enter phone number', 'restaurant-pos-lite'); ?>">
        <?php
    }

    public function shop_logo_callback()
    {
        $shop_logo = get_option('oby_restaurant_pos_lite_shop_logo', '');
        ?>
        <input type="text" name="restaurant_pos_lite_settings[shop_logo]" value="<?php echo esc_attr($shop_logo); ?>"
            class="regular-text" placeholder="<?php esc_attr_e('Enter logo URL', 'restaurant-pos-lite'); ?>">
        <p class="description">
            <?php esc_html_e('Enter the URL of your restaurant logo. This will be used on receipts and reports.', 'restaurant-pos-lite'); ?>
        </p>
        <?php
    }

    public function render_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'restaurant-pos-lite'));
        }

        // Show settings errors (success messages)
        settings_errors('restaurant_pos_lite_settings');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Restaurant POS Settings', 'restaurant-pos-lite'); ?></h1>

            <form method="post" action="options.php">
                <?php
                settings_fields('restaurant_pos_lite_settings_group');
                do_settings_sections('restaurant-pos-lite-settings');
                submit_button(__('Save Settings', 'restaurant-pos-lite'));
                ?>
            </form>

            <!-- Settings Preview Section -->
            <div class="settings-preview" style="margin-top: 30px; padding: 20px; background: #f9f9f9; border: 1px solid #ddd;">
                <h2><?php esc_html_e('Settings Preview', 'restaurant-pos-lite'); ?></h2>
                <p><strong><?php esc_html_e('Current Date Format:', 'restaurant-pos-lite'); ?></strong>
                    <?php echo esc_html(Restaurant_POS_Lite_Helpers::get_current_date()); ?></p>
                <p><strong><?php esc_html_e('Currency Format:', 'restaurant-pos-lite'); ?></strong>
                    <?php echo esc_html(Restaurant_POS_Lite_Helpers::format_currency(100)); ?></p>
                <p><strong><?php esc_html_e('VAT Rate:', 'restaurant-pos-lite'); ?></strong>
                    <?php echo esc_html(get_option('oby_restaurant_pos_lite_vat_rate', '0')); ?>%</p>
                <p><strong><?php esc_html_e('Tax Rate:', 'restaurant-pos-lite'); ?></strong>
                    <?php echo esc_html(get_option('oby_restaurant_pos_lite_tax_rate', '0')); ?>%</p>
                <p><strong><?php esc_html_e('VAT Enabled:', 'restaurant-pos-lite'); ?></strong>
                    <?php echo Restaurant_POS_Lite_Helpers::is_vat_enabled() ? esc_html__('Yes', 'restaurant-pos-lite') : esc_html__('No', 'restaurant-pos-lite'); ?>
                </p>
                <?php if (Restaurant_POS_Lite_Helpers::is_vat_enabled()): ?>
                    <p><strong><?php esc_html_e('VAT Calculation Example (on $100):', 'restaurant-pos-lite'); ?></strong>
                        <?php
                        $totals = Restaurant_POS_Lite_Helpers::calculate_totals(100);
                        echo esc_html(Restaurant_POS_Lite_Helpers::format_currency($totals['total']) . ' (' .
                            Restaurant_POS_Lite_Helpers::format_currency($totals['subtotal']) . ' + ' .
                            Restaurant_POS_Lite_Helpers::format_currency($totals['vat_amount']) . ' VAT)');
                        ?>
                    </p>
                <?php endif; ?>
                <?php if (floatval(get_option('oby_restaurant_pos_lite_tax_rate', '0')) > 0): ?>
                    <p><strong><?php esc_html_e('Tax Calculation Example (on $100):', 'restaurant-pos-lite'); ?></strong>
                        <?php
                        $tax_rate = floatval(get_option('oby_restaurant_pos_lite_tax_rate', '0'));
                        $tax_amount = (100 * $tax_rate) / 100;
                        $total_with_tax = 100 + $tax_amount;
                        echo esc_html(Restaurant_POS_Lite_Helpers::format_currency($total_with_tax) . ' (' .
                            Restaurant_POS_Lite_Helpers::format_currency(100) . ' + ' .
                            Restaurant_POS_Lite_Helpers::format_currency($tax_amount) . ' Tax)');
                        ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
}
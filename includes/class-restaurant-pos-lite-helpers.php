<?php
/**
 * Helper functions for Restaurant POS Lite
 *
 * @package Restaurant_POS_Lite
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

/**
 * Helper functions for Restaurant POS Lite
 */
class Restaurant_POS_Lite_Helpers
{
    /**
     * Get all POS settings
     *
     * @since 1.0.0
     * @return array
     */
    public static function get_settings()
    {
        return array(
            'currency' => get_option('oby_restaurant_pos_lite_currency', '$'),
            'vat_rate' => get_option('oby_restaurant_pos_lite_vat_rate', '0'),
            'shop_name' => get_option('oby_restaurant_pos_lite_shop_name', ''),
            'shop_address' => get_option('oby_restaurant_pos_lite_shop_address', ''),
            'shop_phone' => get_option('oby_restaurant_pos_lite_shop_phone', ''),
            'shop_logo' => get_option('oby_restaurant_pos_lite_shop_logo', ''),
            'currency_position' => get_option('oby_restaurant_pos_lite_currency_position', 'left'),
            'date_format' => get_option('oby_restaurant_pos_lite_date_format', 'Y-m-d'),
        );
    }

    /**
     * Calculate VAT amount
     *
     * @since 1.0.0
     * @param float $amount The amount to calculate VAT for.
     * @return float
     */
    public static function calculate_vat($amount)
    {
        $vat_rate = floatval(get_option('oby_restaurant_pos_lite_vat_rate', '0'));
        return ($amount * $vat_rate) / 100;
    }

    /**
     * Calculate total with VAT
     *
     * @since 1.0.0
     * @param float $subtotal The subtotal amount.
     * @return array
     */
    public static function calculate_totals($subtotal)
    {
        $vat_amount = self::calculate_vat($subtotal);
        $total = $subtotal + $vat_amount;

        return array(
            'subtotal' => $subtotal,
            'vat_amount' => $vat_amount,
            'total' => $total
        );
    }

    /**
     * Check if VAT is enabled (rate > 0)
     *
     * @since 1.0.0
     * @return bool
     */
    public static function is_vat_enabled()
    {
        $vat_rate = floatval(get_option('oby_restaurant_pos_lite_vat_rate', '0'));
        return $vat_rate > 0;
    }

    /**
     * Get VAT rate
     *
     * @since 1.0.0
     * @return float
     */
    public static function get_vat_rate()
    {
        return floatval(get_option('oby_restaurant_pos_lite_vat_rate', '0'));
    }

    /**
     * Format currency based on settings
     *
     * @since 1.0.0
     * @param float|string $amount The amount to format.
     * @return string
     */
    public static function format_currency($amount)
    {
        $settings = self::get_settings();
        $currency = $settings['currency'];
        $position = $settings['currency_position'];

        $amount_formatted = number_format(floatval($amount), 2);

        switch ($position) {
            case 'right':
                return $amount_formatted . $currency;
            case 'left_space':
                return $currency . ' ' . $amount_formatted;
            case 'right_space':
                return $amount_formatted . ' ' . $currency;
            case 'left':
            default:
                return $currency . $amount_formatted;
        }
    }

    /**
     * Format date based on settings
     *
     * @since 1.0.0
     * @param string $date_string The date string to format.
     * @return string
     */
    public static function format_date($date_string)
    {
        $settings = self::get_settings();
        $date_format = $settings['date_format'];

        if (empty($date_string)) {
            return '';
        }

        $timestamp = strtotime($date_string);
        if (false === $timestamp) {
            return $date_string;
        }

        // FIX: Use gmdate() instead of date() to avoid timezone issues
        return gmdate($date_format, $timestamp);
    }

    /**
     * Get shop information
     *
     * @since 1.0.0
     * @return array
     */
    public static function get_shop_info()
    {
        $settings = self::get_settings();

        return array(
            'name' => $settings['shop_name'],
            'address' => $settings['shop_address'],
            'phone' => $settings['shop_phone'],
            'logo' => $settings['shop_logo'],
        );
    }

    /**
     * Get shop name with fallback
     *
     * @since 1.0.0
     * @return string
     */
    public static function get_shop_name()
    {
        $settings = self::get_settings();
        return !empty($settings['shop_name'])
            ? $settings['shop_name']
            : 'Restaurant POS'; // REMOVED TRANSLATION FUNCTION
    }

    /**
     * Get currency symbol
     *
     * @since 1.0.0
     * @return string
     */
    public static function get_currency_symbol()
    {
        $settings = self::get_settings();
        return $settings['currency'];
    }

    /**
     * Get currency position
     *
     * @since 1.0.0
     * @return string
     */
    public static function get_currency_position()
    {
        $settings = self::get_settings();
        return $settings['currency_position'];
    }

    /**
     * Get date format
     *
     * @since 1.0.0
     * @return string
     */
    public static function get_date_format()
    {
        $settings = self::get_settings();
        return $settings['date_format'];
    }

    /**
     * Sanitize price input
     *
     * @since 1.0.0
     * @param mixed $price The price to sanitize.
     * @return float
     */
    public static function sanitize_price($price)
    {
        return floatval(preg_replace('/[^0-9.-]/', '', $price));
    }

    /**
     * Format price for display
     *
     * @since 1.0.0
     * @param float $price The price to format.
     * @return string
     */
    public static function format_price($price)
    {
        return number_format(floatval($price), 2, '.', '');
    }

    /**
     * Check if a string is a valid date
     *
     * @since 1.0.0
     * @param string $date Date string to check.
     * @param string $format Date format to check against.
     * @return bool
     */
    public static function is_valid_date($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Get current date in shop format
     *
     * @since 1.0.0
     * @return string
     */
    public static function get_current_date()
    {
        // Use current_time with 'mysql' format which is already timezone-aware
        return self::format_date(current_time('mysql'));
    }

    /**
     * Get default settings
     *
     * @since 1.0.0
     * @return array
     */
    public static function get_default_settings()
    {
        return array(
            'date_format' => 'Y-m-d',
            'currency' => '$',
            'currency_position' => 'left',
            'shop_name' => '',
            'shop_address' => '',
            'shop_phone' => '',
            'shop_logo' => '',
            'vat_rate' => '0',
        );
    }
}
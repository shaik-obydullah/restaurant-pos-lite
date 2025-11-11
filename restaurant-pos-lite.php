<?php
/**
 * Plugin Name: Restaurant POS Lite
 * Plugin URI: https://obydullah.com/project/wordpress-restaurant-pos-lite-plugin
 * Description: A free plugin to manage restaurant orders, menu, and sales directly from your WordPress dashboard.
 * Version: 1.0.0
 * Author: Shaik Obydullah
 * Author URI: https://obydullah.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('OBY_RESTAURANT_POS_LITE_VERSION', '1.0.0');
define('OBY_RESTAURANT_POS_LITE_PATH', plugin_dir_path(__FILE__));
define('OBY_RESTAURANT_POS_LITE_URL', plugin_dir_url(__FILE__));

// Load the handler class
require_once OBY_RESTAURANT_POS_LITE_PATH . 'includes/class-restaurant-pos-lite-handler.php';
require_once OBY_RESTAURANT_POS_LITE_PATH . 'includes/class-restaurant-pos-lite-activator.php';
require_once OBY_RESTAURANT_POS_LITE_PATH . 'includes/class-restaurant-pos-lite-deactivator.php';

/**
 * Initialize the Restaurant POS Lite plugin
 */
function oby_restaurant_pos_lite_init()
{
    return new Restaurant_POS_Lite_Handler();
}

// Start the plugin
add_action('plugins_loaded', 'oby_restaurant_pos_lite_init');

// Register activation and deactivation hooks
register_activation_hook(__FILE__, ['Restaurant_POS_Lite_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['Restaurant_POS_Lite_Deactivator', 'deactivate']);
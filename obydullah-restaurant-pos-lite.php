<?php
/**
 * Plugin Name: Obydullah Restaurant POS Lite
 * Plugin URI: https://obydullah.com/project/wordpress-restaurant-pos-lite-plugin
 * Description: A free plugin to manage restaurant orders, menu, and sales directly from your WordPress dashboard.
 * Version: 1.0.0
 * Author: Shaik Obydullah
 * Author URI: https://obydullah.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: obydullah-restaurant-pos-lite
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ORPL_VERSION', '1.0.0');
define('ORPL_PATH', plugin_dir_path(__FILE__));
define('ORPL_URL', plugin_dir_url(__FILE__));

// Load the handler class
require_once ORPL_PATH . 'includes/class-obydullah-restaurant-pos-lite-handler.php';
require_once ORPL_PATH . 'includes/class-obydullah-restaurant-pos-lite-activator.php';
require_once ORPL_PATH . 'includes/class-obydullah-restaurant-pos-lite-deactivator.php';

/**
 * Load plugin textdomain for translations
 */
function orpl_load_textdomain() {
    load_plugin_textdomain(
        'obydullah-restaurant-pos-lite',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}
add_action('plugins_loaded', 'orpl_load_textdomain');


/**
 * Initialize the Obydullah Restaurant POS Lite plugin
 */
function orpl_init()
{
    return new Obydullah_Restaurant_POS_Lite_Handler();
}

// Start the plugin
add_action('plugins_loaded', 'orpl_init');

// Register activation and deactivation hooks
register_activation_hook(__FILE__, ['Obydullah_Restaurant_POS_Lite_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['Obydullah_Restaurant_POS_Lite_Deactivator', 'deactivate']);
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

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('ORPL_VERSION', '1.0.0');
define('ORPL_PATH', plugin_dir_path(__FILE__));
define('ORPL_URL', plugin_dir_url(__FILE__));

// Includes
require_once ORPL_PATH . 'includes/class-obydullah-restaurant-pos-lite-handler.php';
require_once ORPL_PATH . 'includes/class-obydullah-restaurant-pos-lite-activator.php';
require_once ORPL_PATH . 'includes/class-obydullah-restaurant-pos-lite-deactivator.php';

// Load translations
add_action('plugins_loaded', 'orpl_load_textdomain');
function orpl_load_textdomain()
{
    load_plugin_textdomain(
        'obydullah-restaurant-pos-lite',
        false,
        dirname(plugin_basename(__FILE__)) . '/languages'
    );
}

// Init plugin
add_action('plugins_loaded', 'orpl_init');
function orpl_init()
{
    static $plugin = null;

    if (null === $plugin) {
        $plugin = new Obydullah_Restaurant_POS_Lite_Handler();
    }

    return $plugin;
}

// Hooks
register_activation_hook(__FILE__, ['Obydullah_Restaurant_POS_Lite_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['Obydullah_Restaurant_POS_Lite_Deactivator', 'deactivate']);
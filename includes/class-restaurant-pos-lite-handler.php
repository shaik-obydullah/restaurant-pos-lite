<?php
if (!defined('ABSPATH'))
    exit;

require_once OBY_RESTAURANT_POS_LITE_PATH . 'includes/class-restaurant-pos-lite-helpers.php';

class Restaurant_POS_Lite_Handler
{

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
    }

    public function register_admin_menu()
    {

    }

    public function enqueue_admin_scripts($hook)
    {
        if (strpos($hook, 'restaurant-pos-lite') !== false) {
            wp_enqueue_style(
                'restaurant-pos-lite-admin',
                OBY_RESTAURANT_POS_LITE_URL . 'assets/css/admin.css',
                [],
                '1.0.0'
            );
        }
    }
}
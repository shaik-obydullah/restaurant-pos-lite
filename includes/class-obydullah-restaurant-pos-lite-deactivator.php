<?php
/**
 * Fired during plugin deactivation
 *
 * @package Obydullah_Restaurant_POS_Lite
 * @since   1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Obydullah_Restaurant_POS_Lite_Deactivator
{
    /**
     * Plugin deactivation callback
     */
    public static function deactivate()
    {
        flush_rewrite_rules();
    }
}
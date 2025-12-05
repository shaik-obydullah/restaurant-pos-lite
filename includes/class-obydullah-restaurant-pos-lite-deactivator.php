<?php
/**
 * Fired during plugin deactivation
 *
 * @package Obydullah_Restaurant_POS_Lite
 * @since   1.0.0
 */

class Obydullah_Restaurant_POS_Lite_Deactivator
{
    public static function deactivate()
    {
        if (!current_user_can('activate_plugins')) {
            wp_die('You do not have sufficient permissions to deactivate this plugin.');
        }

        flush_rewrite_rules();
    }
}
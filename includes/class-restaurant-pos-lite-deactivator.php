<?php
/**
 * Fired during plugin deactivation
 *
 * @package Restaurant_POS_Lite
 * @since   1.0.0
 */

class Restaurant_POS_Lite_Deactivator
{
    public static function deactivate()
    {
        // Check permissions
        if (!current_user_can('activate_plugins')) {
            wp_die('You do not have sufficient permissions to deactivate this plugin.');
        }

        // Flush rewrite rules to clean up any custom routes
        flush_rewrite_rules();
    }
}
<?php
/**
 * Fired during plugin deactivation
 *
 * @since      1.0.0
 * @package    Smart_Content_Scheduler
 */

class Smart_Content_Scheduler_Deactivator {

    /**
     * This function is run when the plugin is deactivated
     * - Cleans up scheduled events
     */
    public static function deactivate() {
        self::clear_scheduled_events();
    }

    /**
     * Clear all scheduled events created by the plugin
     */
    private static function clear_scheduled_events() {
        // Clear scheduled machine learning training
        wp_clear_scheduled_hook('scs_daily_ml_training');
        
        // Clear scheduled performance checks
        wp_clear_scheduled_hook('scs_check_performance');
        
        // Clear scheduled social data collection
        wp_clear_scheduled_hook('scs_collect_social_data');
        
        // Clear scheduled competitor data collection
        wp_clear_scheduled_hook('scs_collect_competitor_data');
    }
}
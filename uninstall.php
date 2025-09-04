<?php
/**
 * Smart Content Scheduler Pro Uninstall Script
 * 
 * This file is executed when the plugin is uninstalled (deleted) from WordPress.
 * It removes all plugin data from the database.
 */

// If uninstall not called from WordPress, then exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('scs_optimal_times');
delete_option('scs_auto_reschedule');
delete_option('scs_performance_threshold');
delete_option('scs_reschedule_notice');

// Delete any transients we may have set
delete_transient('scs_dashboard_stats');
delete_transient('scs_analytics_cache');

// Remove scheduled cron events
wp_clear_scheduled_hook('scs_check_performance');

// Delete database table
global $wpdb;
$table_name = $wpdb->prefix . 'scs_analytics';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Delete all post meta created by the plugin
$wpdb->delete(
    $wpdb->postmeta,
    [
        'meta_key' => '_scs_scheduled_time'
    ]
);

$wpdb->delete(
    $wpdb->postmeta,
    [
        'meta_key' => '_scs_use_optimal'
    ]
);

$wpdb->delete(
    $wpdb->postmeta,
    [
        'meta_key' => '_scs_views'
    ]
);

$wpdb->delete(
    $wpdb->postmeta,
    [
        'meta_key' => '_scs_clicks'
    ]
);

$wpdb->delete(
    $wpdb->postmeta,
    [
        'meta_key' => '_scs_shares'
    ]
);

// Delete social media tracking meta
$wpdb->query(
    "DELETE FROM {$wpdb->postmeta} 
     WHERE meta_key LIKE '_scs_shares_%'"
);

// Clear any cached data
wp_cache_flush();

// Log uninstallation for debugging purposes (only if debug is enabled)
if (defined('WP_DEBUG') && WP_DEBUG) {
    error_log('Smart Content Scheduler Pro: Plugin uninstalled and all data removed');
}
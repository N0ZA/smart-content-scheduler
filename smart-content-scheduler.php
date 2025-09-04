<?php
/**
 * Plugin Name: Smart Content Scheduler Pro
 * Plugin URI: https://yourwebsite.com/smart-content-scheduler
 * Description: AI-powered content scheduling with performance tracking and automatic optimization
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
 * License: GPL2
 * Text Domain: smart-scheduler
 * Domain Path: /languages
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('SCS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCS_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('SCS_VERSION', '1.0.0');

// Activation hook
register_activation_hook(__FILE__, 'scs_activate_plugin');
register_deactivation_hook(__FILE__, 'scs_deactivate_plugin');

function scs_activate_plugin() {
    // Create database tables
    scs_create_tables();
    
    // Set default options
    add_option('scs_optimal_times', json_encode([
        'monday' => ['09:00', '14:00', '19:00'],
        'tuesday' => ['09:00', '14:00', '19:00'],
        'wednesday' => ['09:00', '14:00', '19:00'],
        'thursday' => ['09:00', '14:00', '19:00'],
        'friday' => ['09:00', '14:00', '19:00'],
        'saturday' => ['10:00', '15:00', '20:00'],
        'sunday' => ['10:00', '15:00', '20:00']
    ]));
    
    add_option('scs_auto_reschedule', 'yes');
    add_option('scs_performance_threshold', 50);
    
    // Schedule cron events
    if (!wp_next_scheduled('scs_check_performance')) {
        wp_schedule_event(time(), 'hourly', 'scs_check_performance');
    }
}

function scs_deactivate_plugin() {
    wp_clear_scheduled_hook('scs_check_performance');
}

function scs_create_tables() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'scs_analytics';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        scheduled_time datetime NOT NULL,
        published_time datetime DEFAULT NULL,
        views int(11) DEFAULT 0,
        clicks int(11) DEFAULT 0,
        shares int(11) DEFAULT 0,
        engagement_score float DEFAULT 0,
        performance_rating varchar(20) DEFAULT 'pending',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY post_id (post_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Include required files
require_once SCS_PLUGIN_PATH . 'includes/admin-menu.php';
require_once SCS_PLUGIN_PATH . 'includes/scheduler.php';
require_once SCS_PLUGIN_PATH . 'includes/analytics.php';
require_once SCS_PLUGIN_PATH . 'includes/ajax-handlers.php';
require_once plugin_dir_path(__FILE__) . 'includes/ai/class-ai-modules.php';

// Initialize the plugin
add_action('plugins_loaded', 'scs_init');

function scs_init() {
    // Load text domain
    load_plugin_textdomain('smart-scheduler', false, dirname(plugin_basename(__FILE__)) . '/languages');
    
    // Initialize components
    if (is_admin()) {
        new SCS_Admin_Menu();
    }
    
    new SCS_Scheduler();
    new SCS_Analytics();
    new SCS_Ajax_Handlers();
}

// Enqueue scripts and styles
add_action('admin_enqueue_scripts', 'scs_enqueue_admin_scripts');

function scs_enqueue_admin_scripts($hook) {
    if (strpos($hook, 'smart-scheduler') === false && $hook !== 'post.php' && $hook !== 'post-new.php') {
        return;
    }
    
    wp_enqueue_script('scs-admin', SCS_PLUGIN_URL . 'assets/admin.js', ['jquery'], SCS_VERSION, true);
    wp_enqueue_style('scs-admin', SCS_PLUGIN_URL . 'assets/admin.css', [], SCS_VERSION);
    
    wp_localize_script('scs-admin', 'scs_ajax', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('scs_nonce')
    ]);
}
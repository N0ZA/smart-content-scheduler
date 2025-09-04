<?php
/**
 * Plugin Name: Smart Content Scheduler Pro
 * Plugin URI: https://yourwebsite.com/smart-content-scheduler
 * Description: AI-powered content scheduling with performance tracking and automatic optimization
 * Version: 2.0.0
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
define('SCS_VERSION', '2.0.0');

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
    
    $charset_collate = $wpdb->get_charset_collate();
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    // Main analytics table
    $analytics_table = $wpdb->prefix . 'scs_analytics';
    $sql_analytics = "CREATE TABLE $analytics_table (
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
    dbDelta($sql_analytics);
    
    // Social media metrics table
    $social_metrics_table = $wpdb->prefix . 'scs_social_metrics';
    $sql_social_metrics = "CREATE TABLE $social_metrics_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        platform varchar(50) NOT NULL,
        shares int(11) DEFAULT 0,
        likes int(11) DEFAULT 0,
        comments int(11) DEFAULT 0,
        clicks int(11) DEFAULT 0,
        reach int(11) DEFAULT 0,
        impressions int(11) DEFAULT 0,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY post_platform (post_id, platform),
        KEY post_id (post_id),
        KEY platform (platform)
    ) $charset_collate;";
    dbDelta($sql_social_metrics);
    
    // Social media posts table
    $social_posts_table = $wpdb->prefix . 'scs_social_posts';
    $sql_social_posts = "CREATE TABLE $social_posts_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        post_id bigint(20) NOT NULL,
        platform varchar(50) NOT NULL,
        platform_post_id varchar(255) NOT NULL,
        platform_url varchar(500),
        message text,
        posted_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY post_id (post_id),
        KEY platform (platform)
    ) $charset_collate;";
    dbDelta($sql_social_posts);
    
    // A/B testing table
    $ab_tests_table = $wpdb->prefix . 'scs_ab_tests';
    $sql_ab_tests = "CREATE TABLE $ab_tests_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        test_name varchar(255) NOT NULL,
        test_type varchar(50) NOT NULL,
        variant_a text NOT NULL,
        variant_b text NOT NULL,
        post_a_id bigint(20),
        post_b_id bigint(20),
        duration_days int(11) DEFAULT 7,
        sample_size int(11) DEFAULT 50,
        status varchar(20) DEFAULT 'active',
        winner varchar(5),
        start_date datetime DEFAULT CURRENT_TIMESTAMP,
        end_date datetime,
        completed_at datetime,
        created_by bigint(20),
        PRIMARY KEY (id),
        KEY status (status),
        KEY test_type (test_type)
    ) $charset_collate;";
    dbDelta($sql_ab_tests);
    
    // Competitors table
    $competitors_table = $wpdb->prefix . 'scs_competitors';
    $sql_competitors = "CREATE TABLE $competitors_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        competitor_name varchar(255) NOT NULL,
        website_url varchar(500) NOT NULL,
        social_profiles text,
        industry varchar(100),
        tracking_keywords text,
        status varchar(20) DEFAULT 'active',
        added_date datetime DEFAULT CURRENT_TIMESTAMP,
        last_analyzed datetime,
        PRIMARY KEY (id),
        KEY status (status)
    ) $charset_collate;";
    dbDelta($sql_competitors);
    
    // Competitor analysis table
    $competitor_analysis_table = $wpdb->prefix . 'scs_competitor_analysis';
    $sql_competitor_analysis = "CREATE TABLE $competitor_analysis_table (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        competitor_id mediumint(9) NOT NULL,
        analysis_data longtext NOT NULL,
        analysis_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY competitor_id (competitor_id),
        KEY analysis_date (analysis_date)
    ) $charset_collate;";
    dbDelta($sql_competitor_analysis);
}

// Include required files
require_once SCS_PLUGIN_PATH . 'includes/admin-menu.php';
require_once SCS_PLUGIN_PATH . 'includes/scheduler.php';
require_once SCS_PLUGIN_PATH . 'includes/analytics.php';
require_once SCS_PLUGIN_PATH . 'includes/ajax-handlers.php';
require_once SCS_PLUGIN_PATH . 'includes/machine-learning.php';
require_once SCS_PLUGIN_PATH . 'includes/nlp.php';
require_once SCS_PLUGIN_PATH . 'includes/social-media-api.php';
require_once SCS_PLUGIN_PATH . 'includes/ab-testing.php';
require_once SCS_PLUGIN_PATH . 'includes/seasonal-analysis.php';
require_once SCS_PLUGIN_PATH . 'includes/competitor-analysis.php';

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
    new SCS_Machine_Learning();
    new SCS_NLP();
    new SCS_Social_Media_API();
    new SCS_AB_Testing();
    new SCS_Seasonal_Analysis();
    new SCS_Competitor_Analysis();
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
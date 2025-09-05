<?php
/**
 * Fired during plugin activation
 *
 * @since      1.0.0
 * @package    Smart_Content_Scheduler
 */

class Smart_Content_Scheduler_Activator {

    /**
     * This function is run when the plugin is activated
     * - Creates database tables
     * - Sets up default options
     */
    public static function activate() {
        self::create_database_tables();
        self::set_default_options();
    }

    /**
     * Create database tables needed for the plugin
     */
    private static function create_database_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $table_prefix = $wpdb->prefix;
        
        // Content schedule table
        $schedules_table = $table_prefix . 'scs_schedules';
        $sql_schedules = "CREATE TABLE {$schedules_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            scheduled_time datetime NOT NULL,
            is_rescheduled tinyint(1) DEFAULT 0,
            original_time datetime DEFAULT NULL,
            ai_confidence float DEFAULT 0,
            schedule_reason varchar(255) DEFAULT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";

        // Performance metrics table
        $performance_table = $table_prefix . 'scs_performance';
        $sql_performance = "CREATE TABLE {$performance_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            views int(11) DEFAULT 0,
            engagement float DEFAULT 0,
            social_shares int(11) DEFAULT 0,
            avg_time_on_page float DEFAULT 0,
            performance_score float DEFAULT 0,
            last_updated datetime DEFAULT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";

        // A/B testing table
        $ab_tests_table = $table_prefix . 'scs_ab_tests';
        $sql_ab_tests = "CREATE TABLE {$ab_tests_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            test_name varchar(255) NOT NULL,
            post_id bigint(20) unsigned NOT NULL,
            variant varchar(50) NOT NULL,
            start_time datetime DEFAULT NULL,
            end_time datetime DEFAULT NULL,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";

        // ML data collection table
        $ml_data_table = $table_prefix . 'scs_ml_data';
        $sql_ml_data = "CREATE TABLE {$ml_data_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            publish_time datetime DEFAULT NULL,
            day_of_week tinyint(1) DEFAULT NULL,
            hour_of_day tinyint(2) DEFAULT NULL,
            content_length int(11) DEFAULT NULL,
            content_type varchar(50) DEFAULT NULL,
            category varchar(100) DEFAULT NULL,
            tags text DEFAULT NULL,
            engagement_score float DEFAULT NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id)
        ) $charset_collate;";
        
        // Load dbDelta function
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        // Create tables
        dbDelta($sql_schedules);
        dbDelta($sql_performance);
        dbDelta($sql_ab_tests);
        dbDelta($sql_ml_data);
    }

    /**
     * Set default plugin options
     */
    private static function set_default_options() {
        // General settings
        add_option('scs_default_confidence_threshold', 0.6);
        add_option('scs_auto_reschedule_enabled', 1);
        add_option('scs_performance_threshold', 0.3);
        
        // API settings (empty by default)
        add_option('scs_nlp_api_endpoint', '');
        add_option('scs_nlp_api_key', '');
        add_option('scs_facebook_enabled', 0);
        add_option('scs_twitter_enabled', 0);
        add_option('scs_linkedin_enabled', 0);
        add_option('scs_instagram_enabled', 0);
        
        // Set plugin version
        add_option('scs_version', SCS_VERSION);
    }
}
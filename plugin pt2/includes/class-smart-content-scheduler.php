<?php
/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Smart_Content_Scheduler
 */

class Smart_Content_Scheduler {

    /**
     * The loader that's responsible for maintaining and registering all hooks that power the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Smart_Content_Scheduler_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        if (defined('SCS_VERSION')) {
            $this->version = SCS_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'smart-content-scheduler';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        
        // Only init ML components if the necessary libraries are available
        if ($this->is_ml_available()) {
            $this->init_ml_components();
        }
        
        $this->init_api_integrations();
    }

    /**
     * Check if ML libraries are available
     */
    private function is_ml_available() {
        return file_exists(SCS_PLUGIN_DIR . 'vendor/autoload.php');
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        // Include the loader class
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-loader.php';
        
        // Include the internationalization class
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-i18n.php';
        
        // Include the admin class
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-admin.php';
        
        // Include the public class
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-public.php';
        
        // Include ML components - these will check for required libraries themselves
        require_once plugin_dir_path(dirname(__FILE__)) . 'ml/class-ml-scheduler.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'ml/class-nlp-analyzer.php';
        
        // Include API integrations
        if (file_exists(plugin_dir_path(dirname(__FILE__)) . 'api/class-social-api.php')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-social-api.php';
        }
        
        if (file_exists(plugin_dir_path(dirname(__FILE__)) . 'api/class-competitor-api.php')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-competitor-api.php';
        }
        
        if (file_exists(plugin_dir_path(dirname(__FILE__)) . 'api/class-data-collector.php')) {
            require_once plugin_dir_path(dirname(__FILE__)) . 'api/class-data-collector.php';
        }
        
        $this->loader = new Smart_Content_Scheduler_Loader();
    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale() {
        $plugin_i18n = new Smart_Content_Scheduler_i18n();
        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $plugin_admin = new Smart_Content_Scheduler_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
        
        // Add meta box to post editor
        $this->loader->add_action('add_meta_boxes', $plugin_admin, 'add_scheduling_meta_box');
        $this->loader->add_action('save_post', $plugin_admin, 'save_scheduling_options');
        
        // Add AJAX handlers
        $this->loader->add_action('wp_ajax_scs_get_optimal_times', $plugin_admin, 'get_optimal_times');
        $this->loader->add_action('wp_ajax_scs_analyze_content', $plugin_admin, 'analyze_content');
        $this->loader->add_action('wp_ajax_scs_setup_ab_test', $plugin_admin, 'setup_ab_test');
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks() {
        if (class_exists('Smart_Content_Scheduler_Public')) {
            $plugin_public = new Smart_Content_Scheduler_Public($this->get_plugin_name(), $this->get_version());

            $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
            $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
            
            // Track post views and engagement
            if (method_exists($plugin_public, 'track_post_view')) {
                $this->loader->add_action('wp_head', $plugin_public, 'track_post_view');
            }
            
            if (method_exists($plugin_public, 'add_engagement_tracking')) {
                $this->loader->add_filter('the_content', $plugin_public, 'add_engagement_tracking');
            }
        }
    }

    /**
     * Initialize machine learning components
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_ml_components() {
        $ml_scheduler = new Smart_Content_Scheduler_ML_Scheduler();
        $nlp_analyzer = new Smart_Content_Scheduler_NLP_Analyzer();
        
        // Schedule the ML training job to run once a day
        $this->loader->add_action('scs_daily_ml_training', $ml_scheduler, 'train_models');
        
        if (!wp_next_scheduled('scs_daily_ml_training')) {
            wp_schedule_event(time(), 'daily', 'scs_daily_ml_training');
        }
        
        // Add hooks for content analysis
        if (method_exists($nlp_analyzer, 'analyze_content_quality')) {
            $this->loader->add_filter('content_save_pre', $nlp_analyzer, 'analyze_content_quality');
        }
        
        // Add hooks for automatic rescheduling
        $this->loader->add_action('scs_check_performance', $ml_scheduler, 'check_and_reschedule');
        
        if (!wp_next_scheduled('scs_check_performance')) {
            wp_schedule_event(time(), 'hourly', 'scs_check_performance');
        }
    }

    /**
     * Initialize API integrations
     *
     * @since    1.0.0
     * @access   private
     */
    private function init_api_integrations() {
        // Only initialize components if they exist
        if (class_exists('Smart_Content_Scheduler_Social_API')) {
            $social_api = new Smart_Content_Scheduler_Social_API();
            
            // Schedule social data collection
            $this->loader->add_action('scs_collect_social_data', $social_api, 'collect_data');
            
            if (!wp_next_scheduled('scs_collect_social_data')) {
                wp_schedule_event(time(), 'hourly', 'scs_collect_social_data');
            }
        }
        
        if (class_exists('Smart_Content_Scheduler_Competitor_API')) {
            $competitor_api = new Smart_Content_Scheduler_Competitor_API();
            
            // Schedule competitor data collection
            $this->loader->add_action('scs_collect_competitor_data', $competitor_api, 'collect_data');
            
            if (!wp_next_scheduled('scs_collect_competitor_data')) {
                wp_schedule_event(time(), 'daily', 'scs_collect_competitor_data');
            }
        }
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name() {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Smart_Content_Scheduler_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader() {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version() {
        return $this->version;
    }
}
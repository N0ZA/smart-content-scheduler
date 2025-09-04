<?php
/**
 * The core plugin class.
 *
 * @since      1.0.0
 * @package    Smart_Content_Scheduler
 */

class Smart_Content_Scheduler {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Smart_Content_Scheduler_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * Define the core functionality of the plugin.
     *
     * @since    1.0.0
     */
    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies() {
        require_once SCS_PLUGIN_DIR . 'includes/class-smart-content-scheduler-loader.php';
        require_once SCS_PLUGIN_DIR . 'includes/class-smart-content-scheduler-admin.php';
        require_once SCS_PLUGIN_DIR . 'includes/class-smart-content-scheduler-ai.php';
        
        $this->loader = new Smart_Content_Scheduler_Loader();
    }

    /**
     * Register all of the hooks related to the admin area functionality.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks() {
        $admin = new Smart_Content_Scheduler_Admin();
        $ai = new Smart_Content_Scheduler_AI();
        
        // Admin hooks
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');
        
        // AI processing hooks
        $this->loader->add_action('wp_ajax_scs_analyze_content', $ai, 'analyze_content');
        $this->loader->add_action('wp_ajax_scs_generate_schedule', $ai, 'generate_optimal_schedule');
    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run() {
        $this->loader->run();
    }
}
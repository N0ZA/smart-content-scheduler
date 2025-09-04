<?php
/**
 * Plugin Name: Smart Content Scheduler
 * Plugin URI: https://github.com/N0ZA/smart-content-scheduler
 * Description: AI-powered content scheduling and optimization for WordPress.
 * Version: 1.0.0
 * Author: N0ZA
 * Author URI: https://github.com/N0ZA
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: smart-content-scheduler
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('SCS_VERSION', '1.0.0');
define('SCS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SCS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('SCS_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('SCS_THEME_COLOR', '#7a1027');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require_once SCS_PLUGIN_DIR . 'includes/class-smart-content-scheduler.php';

/**
 * Begins execution of the plugin.
 */
function run_smart_content_scheduler() {
    $plugin = new Smart_Content_Scheduler();
    $plugin->run();
}

/**
 * Load admin styles and scripts
 */
function scs_admin_enqueue_scripts($hook) {
    // Only load on our plugin pages
    if (strpos($hook, 'smart-content-scheduler') === false) {
        return;
    }
    
    wp_enqueue_style('scs-admin-styles', SCS_PLUGIN_URL . 'assets/css/admin.css', array(), SCS_VERSION);
    wp_enqueue_script('scs-admin-script', SCS_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), SCS_VERSION, true);
    
    // Pass variables to script
    wp_localize_script('scs-admin-script', 'scsData', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('scs-nonce'),
        'isAIPowered' => true,
        'messages' => array(
            'processing' => __('AI is analyzing your content...', 'smart-content-scheduler'),
            'success' => __('Analysis complete!', 'smart-content-scheduler'),
            'error' => __('Error in AI processing. Please try again.', 'smart-content-scheduler'),
        )
    ));
}
add_action('admin_enqueue_scripts', 'scs_admin_enqueue_scripts');

/**
 * Add plugin admin menu
 */
function scs_add_admin_menu() {
    $icon_url = SCS_PLUGIN_URL . 'assets/images/scs-icon.svg';
    
    add_menu_page(
        __('Smart Content Scheduler', 'smart-content-scheduler'),
        __('Smart Content', 'smart-content-scheduler'),
        'manage_options',
        'smart-content-scheduler',
        'scs_admin_page',
        $icon_url,
        30
    );
    
    add_submenu_page(
        'smart-content-scheduler',
        __('Dashboard', 'smart-content-scheduler'),
        __('Dashboard', 'smart-content-scheduler'),
        'manage_options',
        'smart-content-scheduler',
        'scs_admin_page'
    );
    
    add_submenu_page(
        'smart-content-scheduler',
        __('AI Settings', 'smart-content-scheduler'),
        __('AI Settings', 'smart-content-scheduler'),
        'manage_options',
        'scs-ai-settings',
        'scs_ai_settings_page'
    );
}
add_action('admin_menu', 'scs_add_admin_menu');

/**
 * Render admin main page
 */
function scs_admin_page() {
    require_once SCS_PLUGIN_DIR . 'includes/admin/admin-dashboard.php';
}

/**
 * Render AI settings page
 */
function scs_ai_settings_page() {
    require_once SCS_PLUGIN_DIR . 'includes/admin/admin-ai-settings.php';
}

// Run the plugin
run_smart_content_scheduler();
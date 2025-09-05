<?php
/**
 * Plugin Name: Smart Content Scheduler & Performance Tracker
 * Plugin URI: https://example.com/smart-content-scheduler
 * Description: AI-powered content scheduling optimization with machine learning, performance tracking, and automatic content rescheduling.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
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

/**
 * Check if required directories exist and create them if needed
 */
function scs_check_directories() {
    // Create the ml/models directory if it doesn't exist
    $model_dir = SCS_PLUGIN_DIR . 'ml/models';
    if (!file_exists($model_dir)) {
        wp_mkdir_p($model_dir);
    }
}

/**
 * The code that runs during plugin activation.
 */
function activate_smart_content_scheduler() {
    scs_check_directories();
    require_once SCS_PLUGIN_DIR . 'includes/class-activator.php';
    Smart_Content_Scheduler_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_smart_content_scheduler() {
    require_once SCS_PLUGIN_DIR . 'includes/class-deactivator.php';
    Smart_Content_Scheduler_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_smart_content_scheduler');
register_deactivation_hook(__FILE__, 'deactivate_smart_content_scheduler');

/**
 * Check if PHP-ML is available
 */
function scs_is_ml_available() {
    return file_exists(SCS_PLUGIN_DIR . 'vendor/autoload.php');
}

// Add admin notice if PHP-ML is not installed
function scs_ml_missing_notice() {
    if (!scs_is_ml_available()) {
        ?>
        <div class="notice notice-warning is-dismissible">
            <p><?php _e('For full functionality of Smart Content Scheduler, please install PHP-ML using Composer: <code>cd wp-content/plugins/smart-content-scheduler && composer require php-ai/php-ml</code>', 'smart-content-scheduler'); ?></p>
        </div>
        <?php
    }
}
add_action('admin_notices', 'scs_ml_missing_notice');

/**
 * Begins execution of the plugin.
 */
require_once SCS_PLUGIN_DIR . 'includes/class-smart-content-scheduler.php';

/**
 * Execute the plugin.
 */
function run_smart_content_scheduler() {
    $plugin = new Smart_Content_Scheduler();
    $plugin->run();
}

run_smart_content_scheduler();
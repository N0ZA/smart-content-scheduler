<?php
/**
 * Define the internationalization functionality
 *
 * @since      1.0.0
 * @package    Smart_Content_Scheduler
 */

class Smart_Content_Scheduler_i18n {

    /**
     * Load the plugin text domain for translation.
     *
     * @since    1.0.0
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'smart-content-scheduler',
            false,
            dirname(dirname(plugin_basename(__FILE__))) . '/languages/'
        );
    }
}
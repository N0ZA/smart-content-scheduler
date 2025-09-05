<?php
/**
 * Settings template for the plugin admin area
 *
 * @since      1.0.0
 * @package    Smart_Content_Scheduler
 */

// Save settings if form is submitted
if (isset($_POST['scs_settings_submit'])) {
    // Verify nonce
    if (check_admin_referer('scs_settings', 'scs_settings_nonce')) {
        
        // General settings
        $default_confidence_threshold = isset($_POST['scs_default_confidence_threshold']) ? 
            floatval($_POST['scs_default_confidence_threshold']) : 0.6;
        update_option('scs_default_confidence_threshold', $default_confidence_threshold);
        
        $auto_reschedule_enabled = isset($_POST['scs_auto_reschedule_enabled']) ? 1 : 0;
        update_option('scs_auto_reschedule_enabled', $auto_reschedule_enabled);
        
        $performance_threshold = isset($_POST['scs_performance_threshold']) ? 
            floatval($_POST['scs_performance_threshold']) : 0.3;
        update_option('scs_performance_threshold', $performance_threshold);
        
        // NLP API settings
        $nlp_api_endpoint = isset($_POST['scs_nlp_api_endpoint']) ? 
            sanitize_url($_POST['scs_nlp_api_endpoint']) : '';
        update_option('scs_nlp_api_endpoint', $nlp_api_endpoint);
        
        $nlp_api_key = isset($_POST['scs_nlp_api_key']) ? 
            sanitize_text_field($_POST['scs_nlp_api_key']) : '';
        update_option('scs_nlp_api_key', $nlp_api_key);
        
        // Social media settings
        $facebook_enabled = isset($_POST['scs_facebook_enabled']) ? 1 : 0;
        update_option('scs_facebook_enabled', $facebook_enabled);
        
        $facebook_app_id = isset($_POST['scs_facebook_app_id']) ? 
            sanitize_text_field($_POST['scs_facebook_app_id']) : '';
        update_option('scs_facebook_app_id', $facebook_app_id);
        
        $facebook_app_secret = isset($_POST['scs_facebook_app_secret']) ? 
            sanitize_text_field($_POST['scs_facebook_app_secret']) : '';
        update_option('scs_facebook_app_secret', $facebook_app_secret);
        
        $facebook_access_token = isset($_POST['scs_facebook_access_token']) ? 
            sanitize_text_field($_POST['scs_facebook_access_token']) : '';
        update_option('scs_facebook_access_token', $facebook_access_token);
        
        $facebook_page_id = isset($_POST['scs_facebook_page_id']) ? 
            sanitize_text_field($_POST['scs_facebook_page_id']) : '';
        update_option('scs_facebook_page_id', $facebook_page_id);
        
        // Twitter settings
        $twitter_enabled = isset($_POST['scs_twitter_enabled']) ? 1 : 0;
        update_option('scs_twitter_enabled', $twitter_enabled);
        
        $twitter_api_key = isset($_POST['scs_twitter_api_key']) ? 
            sanitize_text_field($_POST['scs_twitter_api_key']) : '';
        update_option('scs_twitter_api_key', $twitter_api_key);
        
        $twitter_api_secret = isset($_POST['scs_twitter_api_secret']) ? 
            sanitize_text_field($_POST['scs_twitter_api_secret']) : '';
        update_option('scs_twitter_api_secret', $twitter_api_secret);
        
        // LinkedIn settings
        $linkedin_enabled = isset($_POST['scs_linkedin_enabled']) ? 1 : 0;
        update_option('scs_linkedin_enabled', $linkedin_enabled);
        
        $linkedin_client_id = isset($_POST['scs_linkedin_client_id']) ? 
            sanitize_text_field($_POST['scs_linkedin_client_id']) : '';
        update_option('scs_linkedin_client_id', $linkedin_client_id);
        
        $linkedin_client_secret = isset($_POST['scs_linkedin_client_secret']) ? 
            sanitize_text_field($_POST['scs_linkedin_client_secret']) : '';
        update_option('scs_linkedin_client_secret', $linkedin_client_secret);
        
        // Display success message
        add_settings_error(
            'scs_settings',
            'scs_settings_updated',
            __('Settings saved successfully.', 'smart-content-scheduler'),
            'updated'
        );
    }
}

// Get current settings
$default_confidence_threshold = get_option('scs_default_confidence_threshold', 0.6);
$auto_reschedule_enabled = get_option('scs_auto_reschedule_enabled', 1);
$performance_threshold = get_option('scs_performance_threshold', 0.3);

$nlp_api_endpoint = get_option('scs_nlp_api_endpoint', '');
$nlp_api_key = get_option('scs_nlp_api_key', '');

$facebook_enabled = get_option('scs_facebook_enabled', 0);
$facebook_app_id = get_option('scs_facebook_app_id', '');
$facebook_app_secret = get_option('scs_facebook_app_secret', '');
$facebook_access_token = get_option('scs_facebook_access_token', '');
$facebook_page_id = get_option('scs_facebook_page_id', '');

$twitter_enabled = get_option('scs_twitter_enabled', 0);
$twitter_api_key = get_option('scs_twitter_api_key', '');
$twitter_api_secret = get_option('scs_twitter_api_secret', '');

$linkedin_enabled = get_option('scs_linkedin_enabled', 0);
$linkedin_client_id = get_option('scs_linkedin_client_id', '');
$linkedin_client_secret = get_option('scs_linkedin_client_secret', '');
?>

<div class="wrap scs-settings">
    <h1><?php _e('Smart Content Scheduler Settings', 'smart-content-scheduler'); ?></h1>
    
    <?php settings_errors('scs_settings'); ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('scs_settings', 'scs_settings_nonce'); ?>
        
        <div class="scs-settings-tabs">
            <ul class="scs-tabs-nav">
                <li><a href="#tab-general" class="active"><?php _e('General', 'smart-content-scheduler'); ?></a></li>
                <li><a href="#tab-api"><?php _e('API Integration', 'smart-content-scheduler'); ?></a></li>
                <li><a href="#tab-social"><?php _e('Social Media', 'smart-content-scheduler'); ?></a></li>
                <li><a href="#tab-advanced"><?php _e('Advanced', 'smart-content-scheduler'); ?></a></li>
            </ul>
            
            <div class="scs-tabs-content">
                <!-- General Settings Tab -->
                <div id="tab-general" class="scs-tab active">
                    <h2><?php _e('General Settings', 'smart-content-scheduler'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="scs_default_confidence_threshold"><?php _e('Default AI Confidence Threshold', 'smart-content-scheduler'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="scs_default_confidence_threshold" id="scs_default_confidence_threshold" 
                                       min="0" max="1" step="0.1" value="<?php echo esc_attr($default_confidence_threshold); ?>">
                                <p class="description">
                                    <?php _e('Minimum AI confidence level (0-1) required for scheduling suggestions.', 'smart-content-scheduler'); ?>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <?php _e('Auto-Rescheduling', 'smart-content-scheduler'); ?>
                            </th>
                            <td>
                                <label for="scs_auto_reschedule_enabled">
                                    <input type="checkbox" name="scs_auto_reschedule_enabled" id="scs_auto_reschedule_enabled" 
                                           value="1" <?php checked($auto_reschedule_enabled, 1); ?>>
                                    <?php _e('Enable automatic rescheduling of underperforming content', 'smart-content-scheduler'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="scs_performance_threshold"><?php _e('Performance Threshold', 'smart-content-scheduler'); ?></label>
                            </th>
                            <td>
                                <input type="number" name="scs_performance_threshold" id="scs_performance_threshold" 
                                       min="0" max="1" step="0.1" value="<?php echo esc_attr($performance_threshold); ?>">
                                <p class="description">
                                    <?php _e('Content with performance below this threshold will be considered for rescheduling.', 'smart-content-scheduler'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- API Integration Tab -->
                <div id="tab-api" class="scs-tab">
                    <h2><?php _e('API Integration Settings', 'smart-content-scheduler'); ?></h2>
                    
                    <div class="scs-api-section">
                        <h3><?php _e('Natural Language Processing (NLP) API', 'smart-content-scheduler'); ?></h3>
                        <p class="description">
                            <?php _e('Configure an external NLP API for enhanced content analysis.', 'smart-content-scheduler'); ?>
                        </p>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="scs_nlp_api_endpoint"><?php _e('API Endpoint URL', 'smart-content-scheduler'); ?></label>
                                </th>
                                <td>
                                    <input type="url" name="scs_nlp_api_endpoint" id="scs_nlp_api_endpoint" 
                                           class="regular-text" value="<?php echo esc_attr($nlp_api_endpoint); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="scs_nlp_api_key"><?php _e('API Key', 'smart-content-scheduler'); ?></label>
                                </th>
                                <td>
                                    <input type="password" name="scs_nlp_api_key" id="scs_nlp_api_key" 
                                           class="regular-text" value="<?php echo esc_attr($nlp_api_key); ?>">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Social Media Tab -->
                <div id="tab-social" class="scs-tab">
                    <h2><?php _e('Social Media Integration', 'smart-content-scheduler'); ?></h2>
                    
                    <!-- Facebook -->
                    <div class="scs-api-section">
                        <h3><?php _e('Facebook', 'smart-content-scheduler'); ?></h3>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <?php _e('Facebook Integration', 'smart-content-scheduler'); ?>
                                </th>
                                <td>
                                    <label for="scs_facebook_enabled">
                                        <input type="checkbox" name="scs_facebook_enabled" id="scs_facebook_enabled" 
                                               value="1" <?php checked($facebook_enabled, 1); ?>>
                                        <?php _e('Enable Facebook integration', 'smart-content-scheduler'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="scs_facebook_app_id"><?php _e('App ID', 'smart-content-scheduler'); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="scs_facebook_app_id" id="scs_facebook_app_id" 
                                           class="regular-text" value="<?php echo esc_attr($facebook_app_id); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="scs_facebook_app_secret"><?php _e('App Secret', 'smart-content-scheduler'); ?></label>
                                </th>
                                <td>
                                    <input type="password" name="scs_facebook_app_secret" id="scs_facebook_app_secret" 
                                           class="regular-text" value="<?php echo esc_attr($facebook_app_secret); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="scs_facebook_access_token"><?php _e('Access Token', 'smart-content-scheduler'); ?></label>
                                </th>
                                <td>
                                    <input type="password" name="scs_facebook_access_token" id="scs_facebook_access_token" 
                                           class="regular-text" value="<?php echo esc_attr($facebook_access_token); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="scs_facebook_page_id"><?php _e('Page ID', 'smart-content-scheduler'); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="scs_facebook_page_id" id="scs_facebook_page_id" 
                                           class="regular-text" value="<?php echo esc_attr($facebook_page_id); ?>">
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- Twitter -->
                    <div class="scs-api-section">
                        <h3><?php _e('Twitter', 'smart-content-scheduler'); ?></h3>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <?php _e('Twitter Integration', 'smart-content-scheduler'); ?>
                                </th>
                                <td>
                                    <label for="scs_twitter_enabled">
                                        <input type="checkbox" name="scs_twitter_enabled" id="scs_twitter_enabled" 
                                               value="1" <?php checked($twitter_enabled, 1); ?>>
                                        <?php _e('Enable Twitter integration', 'smart-content-scheduler'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="scs_twitter_api_key"><?php _e('API Key', 'smart-content-scheduler'); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="scs_twitter_api_key" id="scs_twitter_api_key" 
                                           class="regular-text" value="<?php echo esc_attr($twitter_api_key); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="scs_twitter_api_secret"><?php _e('API Secret', 'smart-content-scheduler'); ?></label>
                                </th>
                                <td>
                                    <input type="password" name="scs_twitter_api_secret" id="scs_twitter_api_secret" 
                                           class="regular-text" value="<?php echo esc_attr($twitter_api_secret); ?>">
                                </td>
                            </tr>
                        </table>
                    </div>
                    
                    <!-- LinkedIn -->
                    <div class="scs-api-section">
                        <h3><?php _e('LinkedIn', 'smart-content-scheduler'); ?></h3>
                        
                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <?php _e('LinkedIn Integration', 'smart-content-scheduler'); ?>
                                </th>
                                <td>
                                    <label for="scs_linkedin_enabled">
                                        <input type="checkbox" name="scs_linkedin_enabled" id="scs_linkedin_enabled" 
                                               value="1" <?php checked($linkedin_enabled, 1); ?>>
                                        <?php _e('Enable LinkedIn integration', 'smart-content-scheduler'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="scs_linkedin_client_id"><?php _e('Client ID', 'smart-content-scheduler'); ?></label>
                                </th>
                                <td>
                                    <input type="text" name="scs_linkedin_client_id" id="scs_linkedin_client_id" 
                                           class="regular-text" value="<?php echo esc_attr($linkedin_client_id); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="scs_linkedin_client_secret"><?php _e('Client Secret', 'smart-content-scheduler'); ?></label>
                                </th>
                                <td>
                                    <input type="password" name="scs_linkedin_client_secret" id="scs_linkedin_client_secret" 
                                           class="regular-text" value="<?php echo esc_attr($linkedin_client_secret); ?>">
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Advanced Tab -->
                <div id="tab-advanced" class="scs-tab">
                    <h2><?php _e('Advanced Settings', 'smart-content-scheduler'); ?></h2>
                    
                    <div class="scs-advanced-section">
                        <h3><?php _e('Machine Learning', 'smart-content-scheduler'); ?></h3>
                        
                        <div class="scs-card">
                            <h4><?php _e('Model Training', 'smart-content-scheduler'); ?></h4>
                            <p>
                                <?php _e('The machine learning model is automatically trained daily based on your content performance data.', 'smart-content-scheduler'); ?>
                            </p>
                            
                            <button type="button" id="scs_train_now" class="button">
                                <?php _e('Train Model Now', 'smart-content-scheduler'); ?>
                            </button>
                            
                            <div id="scs_training_status"></div>
                        </div>
                        
                        <div class="scs-card">
                            <h4><?php _e('Data Collection', 'smart-content-scheduler'); ?></h4>
                            <p>
                                <?php _e('The plugin collects performance data to improve scheduling recommendations.', 'smart-content-scheduler'); ?>
                            </p>
                            
                            <button type="button" id="scs_reset_data" class="button">
                                <?php _e('Reset Collected Data', 'smart-content-scheduler'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div class="scs-advanced-section">
                        <h3><?php _e('Troubleshooting', 'smart-content-scheduler'); ?></h3>
                        
                        <div class="scs-card">
                            <h4><?php _e('System Information', 'smart-content-scheduler'); ?></h4>
                            
                            <table class="widefat striped">
                                <tr>
                                    <th><?php _e('PHP Version', 'smart-content-scheduler'); ?></th>
                                    <td><?php echo phpversion(); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('WordPress Version', 'smart-content-scheduler'); ?></th>
                                    <td><?php echo get_bloginfo('version'); ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Plugin Version', 'smart-content-scheduler'); ?></th>
                                    <td><?php echo SCS_VERSION; ?></td>
                                </tr>
                                <tr>
                                    <th><?php _e('Database Tables', 'smart-content-scheduler'); ?></th>
                                    <td>
                                        <?php
                                        global $wpdb;
                                        $tables = [
                                            $wpdb->prefix . 'scs_schedules',
                                            $wpdb->prefix . 'scs_performance',
                                            $wpdb->prefix . 'scs_ab_tests',
                                            $wpdb->prefix . 'scs_ml_data',
                                        ];
                                        
                                        $all_tables_exist = true;
                                        foreach ($tables as $table) {
                                            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'") == $table;
                                            echo $table . ': ' . ($table_exists ? '<span style="color:green;">✓</span>' : '<span style="color:red;">✗</span>') . '<br>';
                                            if (!$table_exists) {
                                                $all_tables_exist = false;
                                            }
                                        }
                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th><?php _e('Scheduled Events', 'smart-content-scheduler'); ?></th>
                                    <td>
                                        <?php 
                                        $events = [
                                            'scs_daily_ml_training' => wp_next_scheduled('scs_daily_ml_training'),
                                            'scs_check_performance' => wp_next_scheduled('scs_check_performance'),
                                            'scs_collect_social_data' => wp_next_scheduled('scs_collect_social_data'),
                                            'scs_collect_competitor_data' => wp_next_scheduled('scs_collect_competitor_data'),
                                        ];
                                        
                                        foreach ($events as $event => $timestamp) {
                                            echo $event . ': ';
                                            if ($timestamp) {
                                                echo '<span style="color:green;">' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $timestamp) . '</span>';
                                            } else {
                                                echo '<span style="color:red;">' . __('Not scheduled', 'smart-content-scheduler') . '</span>';
                                            }
                                            echo '<br>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            </table>
                            
                            <?php if (!$all_tables_exist) : ?>
                                <div class="scs-repair-actions">
                                    <button type="button" id="scs_repair_tables" class="button button-secondary">
                                        <?php _e('Repair Database Tables', 'smart-content-scheduler'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <p class="submit">
            <input type="submit" name="scs_settings_submit" class="button button-primary" value="<?php _e('Save Settings', 'smart-content-scheduler'); ?>">
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab navigation
    $('.scs-tabs-nav a').on('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs
        $('.scs-tabs-nav a').removeClass('active');
        $('.scs-tab').removeClass('active');
        
        // Add active class to current tab
        $(this).addClass('active');
        $($(this).attr('href')).addClass('active');
    });
    
    // Train model now button
    $('#scs_train_now').on('click', function() {
        var button = $(this);
        button.prop('disabled', true).text('<?php _e('Training...', 'smart-content-scheduler'); ?>');
        
        $('#scs_training_status').html('<p><?php _e('Training in progress...', 'smart-content-scheduler'); ?></p>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'scs_train_model',
                nonce: '<?php echo wp_create_nonce('scs_advanced_settings'); ?>'
            },
            success: function(response) {
                button.prop('disabled', false).text('<?php _e('Train Model Now', 'smart-content-scheduler'); ?>');
                
                if (response.success) {
                    $('#scs_training_status').html('<p style="color:green;"><?php _e('Training completed successfully!', 'smart-content-scheduler'); ?></p>');
                } else {
                    $('#scs_training_status').html('<p style="color:red;"><?php _e('Error: ', 'smart-content-scheduler'); ?>' + response.data + '</p>');
                }
            },
            error: function() {
                button.prop('disabled', false).text('<?php _e('Train Model Now', 'smart-content-scheduler'); ?>');
                $('#scs_training_status').html('<p style="color:red;"><?php _e('An error occurred during training.', 'smart-content-scheduler'); ?></p>');
            }
        });
    });
    
    // Reset data button
    $('#scs_reset_data').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to reset all collected data? This cannot be undone.', 'smart-content-scheduler'); ?>')) {
            return;
        }
        
        var button = $(this);
        button.prop('disabled', true).text('<?php _e('Resetting...', 'smart-content-scheduler'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'scs_reset_data',
                nonce: '<?php echo wp_create_nonce('scs_advanced_settings'); ?>'
            },
            success: function(response) {
                button.prop('disabled', false).text('<?php _e('Reset Collected Data', 'smart-content-scheduler'); ?>');
                
                if (response.success) {
                    alert('<?php _e('Data reset successfully.', 'smart-content-scheduler'); ?>');
                } else {
                    alert('<?php _e('Error: ', 'smart-content-scheduler'); ?>' + response.data);
                }
            },
            error: function() {
                button.prop('disabled', false).text('<?php _e('Reset Collected Data', 'smart-content-scheduler'); ?>');
                alert('<?php _e('An error occurred while resetting data.', 'smart-content-scheduler'); ?>');
            }
        });
    });
    
    // Repair tables button
    $('#scs_repair_tables').on('click', function() {
        var button = $(this);
        button.prop('disabled', true).text('<?php _e('Repairing...', 'smart-content-scheduler'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'scs_repair_tables',
                nonce: '<?php echo wp_create_nonce('scs_advanced_settings'); ?>'
            },
            success: function(response) {
                button.prop('disabled', false).text('<?php _e('Repair Database Tables', 'smart-content-scheduler'); ?>');
                
                if (response.success) {
                    alert('<?php _e('Database tables repaired successfully. Please refresh the page.', 'smart-content-scheduler'); ?>');
                    location.reload();
                } else {
                    alert('<?php _e('Error: ', 'smart-content-scheduler'); ?>' + response.data);
                }
            },
            error: function() {
                button.prop('disabled', false).text('<?php _e('Repair Database Tables', 'smart-content-scheduler'); ?>');
                alert('<?php _e('An error occurred while repairing tables.', 'smart-content-scheduler'); ?>');
            }
        });
    });
});
</script>
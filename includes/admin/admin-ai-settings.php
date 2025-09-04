<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Get current settings
$ai_engine = get_option('scs_ai_engine', 'standard');
$analysis_depth = get_option('scs_analysis_depth', 'balanced');
$auto_schedule = get_option('scs_auto_schedule', true);
$notification_enabled = get_option('scs_notification_enabled', true);
?>

<div class="scs-admin-wrap">
    <header class="scs-header">
        <div class="scs-logo">
            <img src="<?php echo SCS_PLUGIN_URL; ?>assets/images/scs-logo.png" alt="Smart Content Scheduler Logo">
            <h1><?php _e('AI Settings', 'smart-content-scheduler'); ?></h1>
        </div>
        <div class="scs-ai-badge">
            <span class="scs-ai-indicator"></span>
            <?php _e('AI-POWERED', 'smart-content-scheduler'); ?>
        </div>
    </header>
    
    <div class="scs-admin-content">
        <form method="post" action="options.php" class="scs-settings-form">
            <?php settings_fields('scs_ai_settings'); ?>
            
            <div class="scs-card">
                <div class="scs-card-header">
                    <h2><?php _e('AI Engine Configuration', 'smart-content-scheduler'); ?></h2>
                </div>
                <div class="scs-card-body">
                    <div class="scs-form-row">
                        <label for="scs_ai_engine"><?php _e('AI Engine', 'smart-content-scheduler'); ?></label>
                        <select name="scs_ai_engine" id="scs_ai_engine">
                            <option value="standard" <?php selected($ai_engine, 'standard'); ?>><?php _e('Standard', 'smart-content-scheduler'); ?></option>
                            <option value="advanced" <?php selected($ai_engine, 'advanced'); ?>><?php _e('Advanced (Recommended)', 'smart-content-scheduler'); ?></option>
                            <option value="expert" <?php selected($ai_engine, 'expert'); ?>><?php _e('Expert', 'smart-content-scheduler'); ?></option>
                        </select>
                        <p class="scs-description"><?php _e('Select the AI engine that will power content analysis and scheduling recommendations.', 'smart-content-scheduler'); ?></p>
                    </div>
                    
                    <div class="scs-form-row">
                        <label for="scs_analysis_depth"><?php _e('Analysis Depth', 'smart-content-scheduler'); ?></label>
                        <select name="scs_analysis_depth" id="scs_analysis_depth">
                            <option value="quick" <?php selected($analysis_depth, 'quick'); ?>><?php _e('Quick', 'smart-content-scheduler'); ?></option>
                            <option value="balanced" <?php selected($analysis_depth, 'balanced'); ?>><?php _e('Balanced', 'smart-content-scheduler'); ?></option>
                            <option value="thorough" <?php selected($analysis_depth, 'thorough'); ?>><?php _e('Thorough', 'smart-content-scheduler'); ?></option>
                        </select>
                        <p class="scs-description"><?php _e('Determines how deeply the AI will analyze your content and audience data.', 'smart-content-scheduler'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="scs-card">
                <div class="scs-card-header">
                    <h2><?php _e('AI Automation Settings', 'smart-content-scheduler'); ?></h2>
                </div>
                <div class="scs-card-body">
                    <div class="scs-form-row">
                        <label class="scs-toggle-label"><?php _e('Automatic Scheduling', 'smart-content-scheduler'); ?></label>
                        <div class="scs-toggle-switch">
                            <input type="checkbox" name="scs_auto_schedule" id="scs_auto_schedule" value="1" <?php checked($auto_schedule); ?>>
                            <label for="scs_auto_schedule"></label>
                        </div>
                        <p class="scs-description"><?php _e('Allow AI to automatically schedule posts based on optimal timing analysis.', 'smart-content-scheduler'); ?></p>
                    </div>
                    
                    <div class="scs-form-row">
                        <label class="scs-toggle-label"><?php _e('AI Notifications', 'smart-content-scheduler'); ?></label>
                        <div class="scs-toggle-switch">
                            <input type="checkbox" name="scs_notification_enabled" id="scs_notification_enabled" value="1" <?php checked($notification_enabled); ?>>
                            <label for="scs_notification_enabled"></label>
                        </div>
                        <p class="scs-description"><?php _e('Receive notifications when AI has new scheduling recommendations.', 'smart-content-scheduler'); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="scs-card">
                <div class="scs-card-header">
                    <h2><?php _e('AI Learning Settings', 'smart-content-scheduler'); ?></h2>
                </div>
                <div class="scs-card-body">
                    <div class="scs-ai-learning-status">
                        <div class="scs-ai-learning-icon"></div>
                        <div class="scs-ai-learning-details">
                            <h4><?php _e('AI Model Status', 'smart-content-scheduler'); ?></h4>
                            <div class="scs-ai-progress">
                                <div class="scs-ai-progress-bar" style="width: 68%"></div>
                            </div>
                            <p><?php _e('Your AI model is learning from your site data (68% complete)', 'smart-content-scheduler'); ?></p>
                        </div>
                    </div>
                    
                    <div class="scs-form-row scs-form-actions">
                        <button type="button" class="scs-button scs-secondary-button" id="scs-reset-ai-model">
                            <?php _e('Reset AI Model', 'smart-content-scheduler'); ?>
                        </button>
                        
                        <button type="button" class="scs-button scs-primary-button" id="scs-train-ai-model">
                            <span class="scs-ai-pulse"></span>
                            <?php _e('Retrain AI Model', 'smart-content-scheduler'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="scs-form-submit">
                <?php submit_button(__('Save AI Settings', 'smart-content-scheduler'), 'scs-primary-button'); ?>
            </div>
        </form>
    </div>
</div>
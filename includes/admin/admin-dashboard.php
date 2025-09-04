<?php
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>
<div class="scs-admin-wrap">
    <header class="scs-header">
        <div class="scs-logo">
            <img src="<?php echo SCS_PLUGIN_URL; ?>assets/images/scs-logo.png" alt="Smart Content Scheduler Logo">
            <h1><?php _e('Smart Content Scheduler', 'smart-content-scheduler'); ?></h1>
        </div>
        <div class="scs-ai-badge">
            <span class="scs-ai-indicator"></span>
            <?php _e('AI-POWERED', 'smart-content-scheduler'); ?>
        </div>
    </header>
    
    <div class="scs-admin-content">
        <div class="scs-card scs-welcome-card">
            <div class="scs-card-header">
                <h2><?php _e('Welcome to AI-Powered Content Scheduling', 'smart-content-scheduler'); ?></h2>
            </div>
            <div class="scs-card-body">
                <p class="scs-intro-text">
                    <?php _e('Smart Content Scheduler uses advanced AI algorithms to analyze your content and determine the optimal publishing schedule for maximum audience engagement.', 'smart-content-scheduler'); ?>
                </p>
                
                <div class="scs-feature-highlights">
                    <div class="scs-feature">
                        <span class="dashicons dashicons-chart-area"></span>
                        <h3><?php _e('Audience Analysis', 'smart-content-scheduler'); ?></h3>
                        <p><?php _e('AI-powered analysis of your audience engagement patterns', 'smart-content-scheduler'); ?></p>
                    </div>
                    <div class="scs-feature">
                        <span class="dashicons dashicons-calendar-alt"></span>
                        <h3><?php _e('Smart Scheduling', 'smart-content-scheduler'); ?></h3>
                        <p><?php _e('Automated content scheduling for optimal reach', 'smart-content-scheduler'); ?></p>
                    </div>
                    <div class="scs-feature">
                        <span class="dashicons dashicons-performance"></span>
                        <h3><?php _e('Performance Tracking', 'smart-content-scheduler'); ?></h3>
                        <p><?php _e('Real-time monitoring of content performance', 'smart-content-scheduler'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="scs-row">
            <div class="scs-col">
                <div class="scs-card">
                    <div class="scs-card-header">
                        <h2><?php _e('Content Analysis', 'smart-content-scheduler'); ?></h2>
                    </div>
                    <div class="scs-card-body">
                        <div class="scs-ai-processing-section">
                            <div class="scs-ai-status">
                                <div class="scs-ai-icon"></div>
                                <div class="scs-ai-message"><?php _e('AI ready for content analysis', 'smart-content-scheduler'); ?></div>
                            </div>
                            
                            <button class="scs-button scs-primary-button" id="scs-analyze-content">
                                <span class="scs-ai-pulse"></span>
                                <?php _e('Analyze Content with AI', 'smart-content-scheduler'); ?>
                            </button>
                            
                            <div class="scs-analysis-results" style="display: none;">
                                <h4><?php _e('AI Analysis Results', 'smart-content-scheduler'); ?></h4>
                                <div class="scs-result-item">
                                    <span class="scs-result-label"><?php _e('Recommended Days:', 'smart-content-scheduler'); ?></span>
                                    <span class="scs-result-value" id="scs-recommended-days"></span>
                                </div>
                                <div class="scs-result-item">
                                    <span class="scs-result-label"><?php _e('Optimal Time:', 'smart-content-scheduler'); ?></span>
                                    <span class="scs-result-value" id="scs-optimal-time"></span>
                                </div>
                                <div class="scs-result-item">
                                    <span class="scs-result-label"><?php _e('Estimated Reach:', 'smart-content-scheduler'); ?></span>
                                    <span class="scs-result-value" id="scs-estimated-reach"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="scs-col">
                <div class="scs-card">
                    <div class="scs-card-header">
                        <h2><?php _e('Quick Schedule', 'smart-content-scheduler'); ?></h2>
                    </div>
                    <div class="scs-card-body">
                        <div class="scs-scheduler-widget">
                            <div class="scs-post-select">
                                <label for="scs-post-selection"><?php _e('Select Post:', 'smart-content-scheduler'); ?></label>
                                <select id="scs-post-selection">
                                    <option value=""><?php _e('-- Select a Post --', 'smart-content-scheduler'); ?></option>
                                    <?php
                                    $posts = get_posts(array(
                                        'post_status' => 'draft',
                                        'numberposts' => 10
                                    ));
                                    
                                    foreach ($posts as $post) {
                                        echo '<option value="' . esc_attr($post->ID) . '">' . esc_html($post->post_title) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <button class="scs-button scs-secondary-button" id="scs-ai-schedule">
                                <span class="scs-ai-pulse"></span>
                                <?php _e('Generate AI Schedule', 'smart-content-scheduler'); ?>
                            </button>
                            
                            <div class="scs-schedule-preview">
                                <h4><?php _e('Upcoming Schedule', 'smart-content-scheduler'); ?></h4>
                                <div class="scs-schedule-timeline">
                                    <div class="scs-timeline-empty-state">
                                        <?php _e('No scheduled posts. Use AI to generate an optimal schedule.', 'smart-content-scheduler'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
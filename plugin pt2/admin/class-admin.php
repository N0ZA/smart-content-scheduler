<?php
/**
 * The admin-specific functionality of the plugin
 *
 * @since      1.0.0
 * @package    Smart_Content_Scheduler
 */

class Smart_Content_Scheduler_Admin {

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;
    
    /**
     * Database tables used by the plugin
     *
     * @since    1.0.0
     * @access   private
     * @var      array    $tables    The database tables
     */
    private $tables;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    string    $plugin_name    The name of this plugin.
     * @param    string    $version        The version of this plugin.
     */
    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        
        // Setup table references
        global $wpdb;
        $this->tables = [
            'schedules' => $wpdb->prefix . 'scs_schedules',
            'performance' => $wpdb->prefix . 'scs_performance',
            'ab_tests' => $wpdb->prefix . 'scs_ab_tests',
            'ml_data' => $wpdb->prefix . 'scs_ml_data',
        ];
        
        // Register AJAX handlers
        add_action('wp_ajax_scs_get_optimal_times', [$this, 'get_optimal_times']);
        add_action('wp_ajax_scs_analyze_content', [$this, 'analyze_content']);
        add_action('wp_ajax_scs_setup_ab_test', [$this, 'setup_ab_test']);
        add_action('wp_ajax_scs_get_post_content', [$this, 'get_post_content']);
        add_action('wp_ajax_scs_get_test_results', [$this, 'get_test_results']);
        add_action('wp_ajax_scs_end_ab_test', [$this, 'end_ab_test']);
        add_action('wp_ajax_scs_apply_ab_winner', [$this, 'apply_ab_winner']);
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles() {
        wp_enqueue_style('flatpickr', plugin_dir_url(__FILE__) . 'css/flatpickr.min.css', [], '4.6.9');
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/admin.css', [], $this->version);
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts() {
        // Core scripts
        wp_enqueue_script('flatpickr', plugin_dir_url(__FILE__) . 'js/flatpickr.min.js', [], '4.6.9');
        wp_enqueue_script('chart-js', plugin_dir_url(__FILE__) . 'js/chart.min.js', [], '3.7.0');
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/admin.js', ['jquery', 'jquery-ui-slider'], $this->version, true);
        
        // Pass data to JS
        wp_localize_script($this->plugin_name, 'scs_data', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scs_admin_nonce'),
            'ab_nonce' => wp_create_nonce('scs_ab_testing')
        ]);
    }
    
    /**
     * Add plugin admin menu items
     */
    public function add_plugin_admin_menu() {
        // Main menu item
        add_menu_page(
            __('Smart Content Scheduler', 'smart-content-scheduler'),
            __('Smart Content', 'smart-content-scheduler'),
            'manage_options',
            $this->plugin_name,
            [$this, 'display_plugin_dashboard_page'],
            'dashicons-calendar-alt',
            26
        );
        
        // Dashboard submenu
        add_submenu_page(
            $this->plugin_name,
            __('Dashboard', 'smart-content-scheduler'),
            __('Dashboard', 'smart-content-scheduler'),
            'manage_options',
            $this->plugin_name,
            [$this, 'display_plugin_dashboard_page']
        );
        
        // Content Calendar submenu
        add_submenu_page(
            $this->plugin_name,
            __('Content Calendar', 'smart-content-scheduler'),
            __('Content Calendar', 'smart-content-scheduler'),
            'manage_options',
            $this->plugin_name . '-calendar',
            [$this, 'display_plugin_calendar_page']
        );
        
        // Performance Analytics submenu
        add_submenu_page(
            $this->plugin_name,
            __('Performance Analytics', 'smart-content-scheduler'),
            __('Analytics', 'smart-content-scheduler'),
            'manage_options',
            $this->plugin_name . '-analytics',
            [$this, 'display_plugin_analytics_page']
        );
        
        // A/B Testing submenu
        add_submenu_page(
            $this->plugin_name,
            __('A/B Testing', 'smart-content-scheduler'),
            __('A/B Testing', 'smart-content-scheduler'),
            'manage_options',
            $this->plugin_name . '-ab-testing',
            [$this, 'display_plugin_ab_testing_page']
        );
        
        // Settings submenu
        add_submenu_page(
            $this->plugin_name,
            __('Settings', 'smart-content-scheduler'),
            __('Settings', 'smart-content-scheduler'),
            'manage_options',
            $this->plugin_name . '-settings',
            [$this, 'display_plugin_settings_page']
        );
    }

    /**
     * Display the dashboard page
     */
    public function display_plugin_dashboard_page() {
        include_once SCS_PLUGIN_DIR . 'admin/partials/dashboard.php';
    }
    
    /**
     * Display the calendar page
     */
    public function display_plugin_calendar_page() {
        include_once SCS_PLUGIN_DIR . 'admin/partials/calendar.php';
    }
    
    /**
     * Display the analytics page
     */
    public function display_plugin_analytics_page() {
        include_once SCS_PLUGIN_DIR . 'admin/partials/analytics.php';
    }
    
    /**
     * Display the A/B testing page
     */
    public function display_plugin_ab_testing_page() {
        include_once SCS_PLUGIN_DIR . 'admin/partials/ab-testing.php';
    }
    
    /**
     * Display the settings page
     */
    public function display_plugin_settings_page() {
        include_once SCS_PLUGIN_DIR . 'admin/partials/settings.php';
    }

    /**
     * Add meta box to post editor
     */
    public function add_scheduling_meta_box() {
        add_meta_box(
            'scs_scheduling_meta_box',
            __('Smart Content Scheduler', 'smart-content-scheduler'),
            [$this, 'render_scheduling_meta_box'],
            'post',
            'side',
            'high'
        );
    }
    
    /**
     * Render scheduling meta box content
     */
    public function render_scheduling_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('scs_scheduling_meta_box', 'scs_scheduling_meta_box_nonce');
        
        // Get current values if they exist
        $use_smart_scheduling = get_post_meta($post->ID, '_scs_use_smart_scheduling', true);
        $scheduled_time = get_post_meta($post->ID, '_scs_scheduled_time', true);
        
        ?>
        <div class="scs-meta-box">
            <div class="scs-option">
                <label for="scs_use_smart_scheduling">
                    <input type="checkbox" id="scs_use_smart_scheduling" name="scs_use_smart_scheduling" value="1" <?php checked($use_smart_scheduling, 1); ?> />
                    <?php _e('Use AI-powered scheduling', 'smart-content-scheduler'); ?>
                </label>
            </div>
            
            <div class="scs-option scs-manual-scheduling" style="<?php echo ($use_smart_scheduling == 1) ? 'display:none;' : ''; ?>">
                <label for="scs_scheduled_time"><?php _e('Schedule for:', 'smart-content-scheduler'); ?></label>
                <input type="text" id="scs_scheduled_time" name="scs_scheduled_time" class="scs-datetime-picker" value="<?php echo esc_attr($scheduled_time); ?>" />
            </div>
            
            <div class="scs-option scs-smart-scheduling" style="<?php echo ($use_smart_scheduling != 1) ? 'display:none;' : ''; ?>">
                <p><?php _e('AI will determine the optimal publishing time based on:', 'smart-content-scheduler'); ?></p>
                <ul>
                    <li><?php _e('Historical performance data', 'smart-content-scheduler'); ?></li>
                    <li><?php _e('Content type and category', 'smart-content-scheduler'); ?></li>
                    <li><?php _e('Audience engagement patterns', 'smart-content-scheduler'); ?></li>
                </ul>
                
                <button type="button" id="scs_get_optimal_times" class="button"><?php _e('Suggest optimal times', 'smart-content-scheduler'); ?></button>
                
                <div id="scs_optimal_times_container" style="display:none; margin-top:10px;">
                    <p><?php _e('Select one of the suggested times:', 'smart-content-scheduler'); ?></p>
                    <div id="scs_optimal_times_list"></div>
                    <input type="hidden" id="scs_selected_optimal_time" name="scs_selected_optimal_time" value="" />
                </div>
            </div>
            
            <hr />
            
            <div class="scs-option">
                <button type="button" id="scs_analyze_content" class="button"><?php _e('Analyze Content', 'smart-content-scheduler'); ?></button>
                
                <div id="scs_content_analysis" style="display:none; margin-top:10px;">
                    <h4><?php _e('Content Analysis', 'smart-content-scheduler'); ?></h4>
                    <div class="scs-score-container">
                        <div class="scs-score-label"><?php _e('Overall Score', 'smart-content-scheduler'); ?></div>
                        <div class="scs-score-value" id="scs_overall_score">-</div>
                    </div>
                    
                    <div class="scs-score-details">
                        <div class="scs-score-item">
                            <div class="scs-score-item-label"><?php _e('Readability', 'smart-content-scheduler'); ?></div>
                            <div class="scs-score-item-value" id="scs_readability_score">-</div>
                        </div>
                        <div class="scs-score-item">
                            <div class="scs-score-item-label"><?php _e('SEO', 'smart-content-scheduler'); ?></div>
                            <div class="scs-score-item-value" id="scs_seo_score">-</div>
                        </div>
                        <div class="scs-score-item">
                            <div class="scs-score-item-label"><?php _e('Engagement', 'smart-content-scheduler'); ?></div>
                            <div class="scs-score-item-value" id="scs_engagement_score">-</div>
                        </div>
                    </div>
                    
                    <div id="scs_improvement_suggestions"></div>
                </div>
            </div>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Initialize datetime picker
            $('.scs-datetime-picker').flatpickr({
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                minDate: "today"
            });
            
            // Toggle between smart and manual scheduling
            $('#scs_use_smart_scheduling').on('change', function() {
                if ($(this).is(':checked')) {
                    $('.scs-manual-scheduling').hide();
                    $('.scs-smart-scheduling').show();
                } else {
                    $('.scs-manual-scheduling').show();
                    $('.scs-smart-scheduling').hide();
                }
            });
            
            // Get optimal times button
            $('#scs_get_optimal_times').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('<?php _e('Analyzing...', 'smart-content-scheduler'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'scs_get_optimal_times',
                        post_id: <?php echo $post->ID; ?>,
                        nonce: $('#scs_scheduling_meta_box_nonce').val()
                    },
                    success: function(response) {
                        button.prop('disabled', false).text('<?php _e('Suggest optimal times', 'smart-content-scheduler'); ?>');
                        
                        if (response.success) {
                            var times = response.data;
                            var html = '';
                            
                            if (times.length === 0) {
                                html = '<p><?php _e('No optimal times found. Please try again later when more data is available.', 'smart-content-scheduler'); ?></p>';
                            } else {
                                $.each(times, function(i, time) {
                                    var confidencePercent = Math.round(time.confidence * 100);
                                    html += '<div class="scs-optimal-time-item">';
                                    html += '<label>';
                                    html += '<input type="radio" name="scs_optimal_time_radio" value="' + time.datetime + '" ' + (i === 0 ? 'checked' : '') + '>';
                                    html += '<span class="scs-time-display">' + time.day_name + ', ' + time.hour_display + '</span>';
                                    html += '<span class="scs-confidence-display">(' + confidencePercent + '% <?php _e('confidence', 'smart-content-scheduler'); ?>)</span>';
                                    html += '</label>';
                                    html += '</div>';
                                });
                                
                                // Set the first time as default
                                $('#scs_selected_optimal_time').val(times[0].datetime);
                            }
                            
                            $('#scs_optimal_times_list').html(html);
                            $('#scs_optimal_times_container').show();
                            
                            // Handle selection of optimal times
                            $('input[name="scs_optimal_time_radio"]').on('change', function() {
                                $('#scs_selected_optimal_time').val($(this).val());
                            });
                        } else {
                            alert(response.data);
                        }
                    },
                    error: function() {
                        button.prop('disabled', false).text('<?php _e('Suggest optimal times', 'smart-content-scheduler'); ?>');
                        alert('<?php _e('An error occurred. Please try again.', 'smart-content-scheduler'); ?>');
                    }
                });
            });
            
            // Analyze content button
            $('#scs_analyze_content').on('click', function() {
                var button = $(this);
                button.prop('disabled', true).text('<?php _e('Analyzing...', 'smart-content-scheduler'); ?>');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'scs_analyze_content',
                        post_id: <?php echo $post->ID; ?>,
                        nonce: $('#scs_scheduling_meta_box_nonce').val()
                    },
                    success: function(response) {
                        button.prop('disabled', false).text('<?php _e('Analyze Content', 'smart-content-scheduler'); ?>');
                        
                        if (response.success) {
                            var analysis = response.data;
                            
                            // Update scores
                            $('#scs_overall_score').text(Math.round(analysis.overall_score * 100) + '%');
                            $('#scs_readability_score').text(Math.round(analysis.readability.score * 100) + '%');
                            $('#scs_seo_score').text(Math.round(analysis.seo.score * 100) + '%');
                            $('#scs_engagement_score').text(Math.round(analysis.engagement_prediction * 100) + '%');
                            
                            // Add color classes based on scores
                            $('#scs_overall_score').removeClass('score-low score-medium score-high')
                                .addClass(getScoreClass(analysis.overall_score));
                            $('#scs_readability_score').removeClass('score-low score-medium score-high')
                                .addClass(getScoreClass(analysis.readability.score));
                            $('#scs_seo_score').removeClass('score-low score-medium score-high')
                                .addClass(getScoreClass(analysis.seo.score));
                            $('#scs_engagement_score').removeClass('score-low score-medium score-high')
                                .addClass(getScoreClass(analysis.engagement_prediction));
                            
                            // Display improvement suggestions
                            var suggestionsHtml = '<h4><?php _e('Improvement Suggestions', 'smart-content-scheduler'); ?></h4><ul>';
                            if (analysis.improvement_suggestions.length > 0) {
                                $.each(analysis.improvement_suggestions, function(i, suggestion) {
                                    var importanceClass = 'suggestion-' + suggestion.importance;
                                    suggestionsHtml += '<li class="' + importanceClass + '">' + suggestion.suggestion + '</li>';
                                });
                            } else {
                                suggestionsHtml += '<li><?php _e('No suggestions available.', 'smart-content-scheduler'); ?></li>';
                            }
                            suggestionsHtml += '</ul>';
                            
                            $('#scs_improvement_suggestions').html(suggestionsHtml);
                            $('#scs_content_analysis').show();
                        } else {
                            alert(response.data);
                        }
                    },
                    error: function() {
                        button.prop('disabled', false).text('<?php _e('Analyze Content', 'smart-content-scheduler'); ?>');
                        alert('<?php _e('An error occurred. Please try again.', 'smart-content-scheduler'); ?>');
                    }
                });
            });
            
            // Helper function to get score class
            function getScoreClass(score) {
                if (score < 0.4) return 'score-low';
                if (score < 0.7) return 'score-medium';
                return 'score-high';
            }
        });
        </script>
        <?php
    }
    
    /**
     * Save scheduling options when post is saved
     */
    public function save_scheduling_options($post_id) {
        // Check if nonce is set
        if (!isset($_POST['scs_scheduling_meta_box_nonce'])) {
            return;
        }
        
        // Verify nonce
        if (!wp_verify_nonce($_POST['scs_scheduling_meta_box_nonce'], 'scs_scheduling_meta_box')) {
            return;
        }
        
        // If this is autosave, don't do anything
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if ('post' === $_POST['post_type']) {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }
        
        // Save use_smart_scheduling option
        $use_smart_scheduling = isset($_POST['scs_use_smart_scheduling']) ? 1 : 0;
        update_post_meta($post_id, '_scs_use_smart_scheduling', $use_smart_scheduling);
        
        // Save scheduled time based on selection
        if ($use_smart_scheduling && isset($_POST['scs_selected_optimal_time']) && !empty($_POST['scs_selected_optimal_time'])) {
            $scheduled_time = sanitize_text_field($_POST['scs_selected_optimal_time']);
            update_post_meta($post_id, '_scs_scheduled_time', $scheduled_time);
            
            // Update post date if it's not published yet
            $post = get_post($post_id);
            if ($post->post_status == 'future' || $post->post_status == 'draft') {
                wp_update_post([
                    'ID' => $post_id,
                    'post_date' => $scheduled_time,
                    'post_date_gmt' => get_gmt_from_date($scheduled_time),
                    'edit_date' => true,
                ]);
            }
            
            // Save to our custom schedule table
            $this->save_to_schedule_table($post_id, $scheduled_time, 'ai_suggested');
            
        } elseif (isset($_POST['scs_scheduled_time']) && !empty($_POST['scs_scheduled_time'])) {
            $scheduled_time = sanitize_text_field($_POST['scs_scheduled_time']);
            update_post_meta($post_id, '_scs_scheduled_time', $scheduled_time);
            
            // Update post date if it's not published yet
            $post = get_post($post_id);
            if ($post->post_status == 'future' || $post->post_status == 'draft') {
                wp_update_post([
                    'ID' => $post_id,
                    'post_date' => $scheduled_time,
                    'post_date_gmt' => get_gmt_from_date($scheduled_time),
                    'edit_date' => true,
                ]);
            }
            
            // Save to our custom schedule table
            $this->save_to_schedule_table($post_id, $scheduled_time, 'manual');
        }
    }
    
    /**
     * Save scheduling data to our custom table
     */
    private function save_to_schedule_table($post_id, $scheduled_time, $reason) {
        global $wpdb;
        
        // Check if we already have a record
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM {$this->tables['schedules']} WHERE post_id = %d",
            $post_id
        ));
        
        $confidence = ($reason == 'ai_suggested') ? get_post_meta($post_id, '_scs_schedule_confidence', true) : 0;
        
        if ($existing) {
            // Update existing record
            $wpdb->update(
                $this->tables['schedules'],
                [
                    'scheduled_time' => $scheduled_time,
                    'is_rescheduled' => 0,
                    'ai_confidence' => $confidence,
                    'schedule_reason' => $reason
                ],
                ['post_id' => $post_id],
                ['%s', '%d', '%f', '%s'],
                ['%d']
            );
        } else {
            // Insert new record
            $wpdb->insert(
                $this->tables['schedules'],
                [
                    'post_id' => $post_id,
                    'scheduled_time' => $scheduled_time,
                    'is_rescheduled' => 0,
                    'ai_confidence' => $confidence,
                    'schedule_reason' => $reason
                ],
                ['%d', '%s', '%d', '%f', '%s']
            );
        }
    }
    
    /**
     * AJAX handler for getting optimal times
     */
    public function get_optimal_times() {
        // Check nonce
        if (!check_ajax_referer('scs_scheduling_meta_box', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'smart-content-scheduler'));
        }
        
        // Check post ID
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'smart-content-scheduler'));
        }
        
        // Get the post
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(__('Post not found', 'smart-content-scheduler'));
        }
        
        // Load ML Scheduler
        require_once SCS_PLUGIN_DIR . 'ml/class-ml-scheduler.php';
        $ml_scheduler = new Smart_Content_Scheduler_ML_Scheduler();
        
        // Get post data
        $post_data = [
            'category' => wp_get_post_categories($post_id, ['fields' => 'names'])[0] ?? 'uncategorized',
            'content_length' => strlen(strip_tags($post->post_content)),
            'content_type' => get_post_type($post_id)
        ];
        
        // Get optimal times
        $optimal_times = $ml_scheduler->get_optimal_times($post_id, $post_data);
        
        // Store confidence for the best time
        if (!empty($optimal_times)) {
            update_post_meta($post_id, '_scs_schedule_confidence', $optimal_times[0]['confidence']);
        }
        
        wp_send_json_success($optimal_times);
    }
    
    /**
     * AJAX handler for analyzing content
     */
    public function analyze_content() {
        // Check nonce
        if (!check_ajax_referer('scs_scheduling_meta_box', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'smart-content-scheduler'));
        }
        
        // Check post ID
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'smart-content-scheduler'));
        }
        
        // Get the post
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(__('Post not found', 'smart-content-scheduler'));
        }
        
        // Load NLP Analyzer
        require_once SCS_PLUGIN_DIR . 'ml/class-nlp-analyzer.php';
        $nlp_analyzer = new Smart_Content_Scheduler_NLP_Analyzer();
        
        // Analyze content
        $analysis = $nlp_analyzer->analyze_content_quality($post->post_content);
        
        // Save analysis to post meta
        update_post_meta($post_id, '_scs_content_analysis', $analysis);
        
        wp_send_json_success($analysis);
    }
    
    /**
     * AJAX handler for getting post content
     */
    public function get_post_content() {
        // Check nonce
        if (!check_ajax_referer('scs_ab_testing', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'smart-content-scheduler'));
        }
        
        // Check post ID
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id) {
            wp_send_json_error(__('Invalid post ID', 'smart-content-scheduler'));
        }
        
        // Get the post
        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(__('Post not found', 'smart-content-scheduler'));
        }
        
        wp_send_json_success([
            'title' => $post->post_title,
            'content' => $post->post_content
        ]);
    }
    
    /**
     * AJAX handler for setting up A/B tests
     */
    public function setup_ab_test() {
        // Check nonce
        if (!check_ajax_referer('scs_ab_testing', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'smart-content-scheduler'));
        }
        
        // Get data
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $test_name = isset($_POST['test_name']) ? sanitize_text_field($_POST['test_name']) : '';
        $variants = isset($_POST['variants']) ? $_POST['variants'] : [];
        
        if (!$post_id || empty($test_name) || empty($variants)) {
            wp_send_json_error(__('Missing required data', 'smart-content-scheduler'));
        }
        
        global $wpdb;
        
        // Save test data
        $test_id = wp_insert_post([
            'post_title' => $test_name,
            'post_type' => 'scs_ab_test',
            'post_status' => 'publish'
        ]);
        
        if (!$test_id) {
            wp_send_json_error(__('Failed to create test', 'smart-content-scheduler'));
        }
        
        // Save variants
        foreach ($variants as $variant_id => $variant_data) {
            // Save variant to our custom table
            $wpdb->insert(
                $this->tables['ab_tests'],
                [
                    'test_name' => $test_name,
                    'post_id' => $post_id,
                    'variant' => $variant_id,
                    'start_time' => current_time('mysql'),
                    'is_active' => 1
                ],
                ['%s', '%d', '%s', '%s', '%d']
            );
            
            // Save variant content as post meta
            update_post_meta($post_id, '_scs_ab_variant_' . $variant_id, $variant_data['content']);
            update_post_meta($post_id, '_scs_ab_variant_' . $variant_id . '_title', $variant_data['title']);
        }
        
        // Save test metadata
        update_post_meta($post_id, '_scs_ab_test_active', 1);
        update_post_meta($post_id, '_scs_ab_test_id', $test_id);
        update_post_meta($post_id, '_scs_ab_test_name', $test_name);
        update_post_meta($post_id, '_scs_ab_test_variants', array_keys($variants));
        
        wp_send_json_success([
            'test_id' => $test_id,
            'message' => __('A/B test setup successfully', 'smart-content-scheduler')
        ]);
    }

    /**
     * AJAX handler for getting test results
     */
    public function get_test_results() {
        // Check nonce
        if (!check_ajax_referer('scs_ab_testing', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'smart-content-scheduler'));
        }
        
        $test_id = isset($_POST['test_id']) ? intval($_POST['test_id']) : 0;
        $test_name = isset($_POST['test_name']) ? sanitize_text_field($_POST['test_name']) : '';
        
        if (!$test_id || empty($test_name)) {
            wp_send_json_error(__('Invalid test data', 'smart-content-scheduler'));
        }
        
        // Get test status (active or not)
        $is_active = get_post_meta($test_id, '_scs_ab_test_active', true);
        
        // Load results data
        $results_html = $this->generate_test_results_html($test_id, $test_name);
        
        // Get variants performance data 
        // This would come from a database table in real implementation
        // For now using mock data
        $variants_data = $this->get_test_performance_data($test_id, $test_name);
        
        // Determine winner based on conversion rates
        $winner = null;
        $best_rate = 0;
        foreach ($variants_data as $variant => $data) {
            if ($data['conversion_rate'] > $best_rate) {
                $best_rate = $data['conversion_rate'];
                $winner = $variant;
            }
        }
        
        // Prepare chart data
        $chart_data = [
            'labels' => [],
            'conversionRates' => [],
            'trafficSplit' => [],
            'colors' => [],
            'borderColors' => []
        ];
        
        $color_map = [
            'A' => ['rgba(54, 162, 235, 0.5)', 'rgba(54, 162, 235, 1)'],
            'B' => ['rgba(255, 99, 132, 0.5)', 'rgba(255, 99, 132, 1)'],
            'C' => ['rgba(75, 192, 192, 0.5)', 'rgba(75, 192, 192, 1)'],
            'D' => ['rgba(255, 206, 86, 0.5)', 'rgba(255, 206, 86, 1)'],
            'E' => ['rgba(153, 102, 255, 0.5)', 'rgba(153, 102, 255, 1)']
        ];
        
        foreach ($variants_data as $variant => $data) {
            $chart_data['labels'][] = 'Variant ' . $variant;
            $chart_data['conversionRates'][] = $data['conversion_rate'];
            $chart_data['trafficSplit'][] = $data['traffic_percent'];
            $chart_data['colors'][] = $color_map[$variant][0];
            $chart_data['borderColors'][] = $color_map[$variant][1];
        }
        
        wp_send_json_success([
            'html' => $results_html,
            'hasCharts' => true,
            'chartData' => $chart_data,
            'hasWinner' => !empty($winner),
            'winner' => $winner,
            'isActive' => $is_active == 1
        ]);
    }
    
    /**
     * Get test performance data
     * In a real implementation, this would fetch actual data from database
     */
    private function get_test_performance_data($test_id, $test_name) {
        // Mock data - in real implementation this would come from database
        return [
            'A' => [
                'visitors' => 160,
                'conversions' => 7,
                'conversion_rate' => 4.5,
                'traffic_percent' => 50
            ],
            'B' => [
                'visitors' => 160,
                'conversions' => 8,
                'conversion_rate' => 5.2,
                'traffic_percent' => 50
            ]
        ];
    }

    /**
     * Generate HTML for test results
     */
    private function generate_test_results_html($test_id, $test_name) {
        // Get test data
        $variants_data = $this->get_test_performance_data($test_id, $test_name);
        $total_visitors = array_sum(array_column($variants_data, 'visitors'));
        $total_conversions = array_sum(array_column($variants_data, 'conversions'));
        $overall_rate = ($total_visitors > 0) ? ($total_conversions / $total_visitors) * 100 : 0;
        
        // Get test duration
        $start_time = current_time('timestamp') - (7 * DAY_IN_SECONDS); // Mock data - 7 days ago
        $duration = human_time_diff($start_time, current_time('timestamp'));
        
        $html = '<div class="scs-test-results">';
        $html .= '<h3>' . esc_html($test_name) . ' - Results</h3>';
        
        $html .= '<div class="scs-results-summary">';
        $html .= '<p>Test running for ' . $duration . ' with ' . count($variants_data) . ' variants.</p>';
        $html .= '<p><strong>Total visitors:</strong> ' . $total_visitors . '</p>';
        $html .= '<p><strong>Overall conversion rate:</strong> ' . number_format($overall_rate, 2) . '%</p>';
        $html .= '</div>';
        
        $html .= '<div class="scs-charts-container">';
        $html .= '<div class="scs-chart-row">';
        $html .= '<div class="scs-chart-column">';
        $html .= '<h4>Conversion Rates</h4>';
        $html .= '<canvas id="scs-conversion-chart"></canvas>';
        $html .= '</div>';
        
        $html .= '<div class="scs-chart-column">';
        $html .= '<h4>Traffic Split</h4>';
        $html .= '<canvas id="scs-traffic-chart"></canvas>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        $html .= '<table class="widefat striped">';
        $html .= '<thead><tr><th>Variant</th><th>Visitors</th><th>Conversions</th><th>Rate</th></tr></thead>';
        $html .= '<tbody>';
        
        // Determine winner for highlighting
        $best_rate = 0;
        $winner = '';
        foreach ($variants_data as $variant => $data) {
            if ($data['conversion_rate'] > $best_rate) {
                $best_rate = $data['conversion_rate'];
                $winner = $variant;
            }
        }
        
        foreach ($variants_data as $variant => $data) {
            $is_winner = ($variant === $winner);
            $html .= '<tr' . ($is_winner ? ' class="scs-winner-row"' : '') . '>';
            $html .= '<td>Variant ' . $variant . ($is_winner ? ' (Winner)' : '') . '</td>';
            $html .= '<td>' . $data['visitors'] . '</td>';
            $html .= '<td>' . $data['conversions'] . '</td>';
            $html .= '<td>' . number_format($data['conversion_rate'], 1) . '%</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody>';
        $html .= '</table>';
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * AJAX handler for ending A/B test
     */
    public function end_ab_test() {
        // Check nonce
        if (!check_ajax_referer('scs_ab_testing', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'smart-content-scheduler'));
        }
        
        $test_id = isset($_POST['test_id']) ? intval($_POST['test_id']) : 0;
        $test_name = isset($_POST['test_name']) ? sanitize_text_field($_POST['test_name']) : '';
        
        if (!$test_id || empty($test_name)) {
            wp_send_json_error(__('Invalid test data', 'smart-content-scheduler'));
        }
        
        global $wpdb;
        
        // Update test status
        $wpdb->update(
            $this->tables['ab_tests'],
            [
                'is_active' => 0,
                'end_time' => current_time('mysql')
            ],
            [
                'post_id' => $test_id,
                'test_name' => $test_name
            ],
            ['%d', '%s'],
            ['%d', '%s']
        );
        
        // Update post meta
        update_post_meta($test_id, '_scs_ab_test_active', 0);
        
        wp_send_json_success(__('Test ended successfully', 'smart-content-scheduler'));
    }

    /**
     * AJAX handler for applying winning variant
     */
    public function apply_ab_winner() {
        // Check nonce
        if (!check_ajax_referer('scs_ab_testing', 'nonce', false)) {
            wp_send_json_error(__('Security check failed', 'smart-content-scheduler'));
        }
        
        $test_id = isset($_POST['test_id']) ? intval($_POST['test_id']) : 0;
        $winner = isset($_POST['winner']) ? sanitize_text_field($_POST['winner']) : '';
        
        if (!$test_id || empty($winner)) {
            wp_send_json_error(__('Invalid data', 'smart-content-scheduler'));
        }
        
        // Get winner content
        $title = get_post_meta($test_id, '_scs_ab_variant_' . $winner . '_title', true);
        $content = get_post_meta($test_id, '_scs_ab_variant_' . $winner, true);
        
        // Update post with winning content
        $update_data = ['ID' => $test_id];
        
        if (!empty($title)) {
            $update_data['post_title'] = $title;
        }
        
        if (!empty($content)) {
            $update_data['post_content'] = $content;
        }
        
        $result = wp_update_post($update_data);
        
        if ($result) {
            wp_send_json_success(__('Winning variant applied successfully', 'smart-content-scheduler'));
        } else {
            wp_send_json_error(__('Failed to apply winning variant', 'smart-content-scheduler'));
        }
    }
}
<?php
if (!defined('ABSPATH')) {
    exit;
}

class SCS_Admin_Menu {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_box_data']);
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('Smart Scheduler', 'smart-scheduler'),
            __('Smart Scheduler', 'smart-scheduler'),
            'manage_options',
            'smart-scheduler',
            [$this, 'dashboard_page'],
            'dashicons-calendar-alt',
            30
        );
        
        add_submenu_page(
            'smart-scheduler',
            __('Analytics', 'smart-scheduler'),
            __('Analytics', 'smart-scheduler'),
            'manage_options',
            'smart-scheduler-analytics',
            [$this, 'analytics_page']
        );
        
        add_submenu_page(
            'smart-scheduler',
            __('ML & NLP', 'smart-scheduler'),
            __('ML & NLP', 'smart-scheduler'),
            'manage_options',
            'smart-scheduler-ml-nlp',
            [$this, 'ml_nlp_page']
        );
        
        add_submenu_page(
            'smart-scheduler',
            __('Social Media', 'smart-scheduler'),
            __('Social Media', 'smart-scheduler'),
            'manage_options',
            'smart-scheduler-social',
            [$this, 'social_media_page']
        );
        
        add_submenu_page(
            'smart-scheduler',
            __('A/B Testing', 'smart-scheduler'),
            __('A/B Testing', 'smart-scheduler'),
            'manage_options',
            'smart-scheduler-ab-testing',
            [$this, 'ab_testing_page']
        );
        
        add_submenu_page(
            'smart-scheduler',
            __('Seasonal Analysis', 'smart-scheduler'),
            __('Seasonal Analysis', 'smart-scheduler'),
            'manage_options',
            'smart-scheduler-seasonal',
            [$this, 'seasonal_analysis_page']
        );
        
        add_submenu_page(
            'smart-scheduler',
            __('Competitor Analysis', 'smart-scheduler'),
            __('Competitor Analysis', 'smart-scheduler'),
            'manage_options',
            'smart-scheduler-competitors',
            [$this, 'competitor_analysis_page']
        );
        
        add_submenu_page(
            'smart-scheduler',
            __('Settings', 'smart-scheduler'),
            __('Settings', 'smart-scheduler'),
            'manage_options',
            'smart-scheduler-settings',
            [$this, 'settings_page']
        );
    }
    
    public function dashboard_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Content Scheduler Dashboard', 'smart-scheduler'); ?></h1>
            
            <div class="scs-dashboard">
                <div class="scs-stats-row">
                    <div class="scs-stat-card">
                        <h3><?php _e('Scheduled Posts', 'smart-scheduler'); ?></h3>
                        <div class="scs-stat-number" id="scheduled-count">
                            <?php echo $this->get_scheduled_posts_count(); ?>
                        </div>
                    </div>
                    
                    <div class="scs-stat-card">
                        <h3><?php _e('Published Today', 'smart-scheduler'); ?></h3>
                        <div class="scs-stat-number" id="published-today">
                            <?php echo $this->get_published_today_count(); ?>
                        </div>
                    </div>
                    
                    <div class="scs-stat-card">
                        <h3><?php _e('Avg Performance', 'smart-scheduler'); ?></h3>
                        <div class="scs-stat-number" id="avg-performance">
                            <?php echo $this->get_average_performance(); ?>%
                        </div>
                    </div>
                </div>
                
                <div class="scs-content-row">
                    <div class="scs-upcoming-posts">
                        <h2><?php _e('Upcoming Posts', 'smart-scheduler'); ?></h2>
                        <div id="upcoming-posts-list">
                            <?php $this->render_upcoming_posts(); ?>
                        </div>
                    </div>
                    
                    <div class="scs-optimal-times">
                        <h2><?php _e('Optimal Posting Times', 'smart-scheduler'); ?></h2>
                        <div id="optimal-times-chart">
                            <?php $this->render_optimal_times(); ?>
                        </div>
                        <button class="button button-primary" id="refresh-optimal-times">
                            <?php _e('Refresh Analysis', 'smart-scheduler'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="scs-quick-schedule">
                    <h2><?php _e('Quick Schedule', 'smart-scheduler'); ?></h2>
                    <form id="quick-schedule-form">
                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Post Title', 'smart-scheduler'); ?></th>
                                <td><input type="text" name="post_title" class="regular-text" required></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Content', 'smart-scheduler'); ?></th>
                                <td><textarea name="post_content" rows="4" class="large-text"></textarea></td>
                            </tr>
                            <tr>
                                <th scope="row"><?php _e('Schedule Option', 'smart-scheduler'); ?></th>
                                <td>
                                    <label><input type="radio" name="schedule_type" value="optimal" checked> <?php _e('Use AI Optimal Time', 'smart-scheduler'); ?></label><br>
                                    <label><input type="radio" name="schedule_type" value="custom"> <?php _e('Custom Date/Time', 'smart-scheduler'); ?></label>
                                    <input type="datetime-local" name="custom_datetime" style="margin-left: 20px;">
                                </td>
                            </tr>
                        </table>
                        <?php wp_nonce_field('scs_quick_schedule', 'scs_nonce'); ?>
                        <p class="submit">
                            <input type="submit" class="button-primary" value="<?php _e('Schedule Post', 'smart-scheduler'); ?>">
                        </p>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function analytics_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Analytics & Performance', 'smart-scheduler'); ?></h1>
            
            <div class="scs-analytics">
                <div class="scs-filters">
                    <select id="analytics-period">
                        <option value="7"><?php _e('Last 7 days', 'smart-scheduler'); ?></option>
                        <option value="30"><?php _e('Last 30 days', 'smart-scheduler'); ?></option>
                        <option value="90"><?php _e('Last 90 days', 'smart-scheduler'); ?></option>
                    </select>
                    <button class="button" id="update-analytics"><?php _e('Update', 'smart-scheduler'); ?></button>
                </div>
                
                <div class="scs-charts">
                    <div class="scs-chart-container">
                        <canvas id="performance-chart" width="400" height="200"></canvas>
                    </div>
                    <div class="scs-chart-container">
                        <canvas id="engagement-chart" width="400" height="200"></canvas>
                    </div>
                </div>
                
                <div class="scs-posts-table">
                    <h2><?php _e('Post Performance', 'smart-scheduler'); ?></h2>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Post Title', 'smart-scheduler'); ?></th>
                                <th><?php _e('Scheduled', 'smart-scheduler'); ?></th>
                                <th><?php _e('Published', 'smart-scheduler'); ?></th>
                                <th><?php _e('Views', 'smart-scheduler'); ?></th>
                                <th><?php _e('Engagement', 'smart-scheduler'); ?></th>
                                <th><?php _e('Performance', 'smart-scheduler'); ?></th>
                                <th><?php _e('Actions', 'smart-scheduler'); ?></th>
                            </tr>
                        </thead>
                        <tbody id="analytics-table-body">
                            <?php $this->render_analytics_table(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function settings_page() {
        if (isset($_POST['submit'])) {
            $this->save_settings();
        }
        
        $optimal_times = json_decode(get_option('scs_optimal_times', '[]'), true);
        $auto_reschedule = get_option('scs_auto_reschedule', 'yes');
        $performance_threshold = get_option('scs_performance_threshold', 50);
        ?>
        <div class="wrap">
            <h1><?php _e('Smart Scheduler Settings', 'smart-scheduler'); ?></h1>
            
            <form method="post" action="">
                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Auto-Reschedule Posts', 'smart-scheduler'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_reschedule" value="yes" <?php checked($auto_reschedule, 'yes'); ?>>
                                <?php _e('Automatically reschedule underperforming posts', 'smart-scheduler'); ?>
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php _e('Performance Threshold', 'smart-scheduler'); ?></th>
                        <td>
                            <input type="number" name="performance_threshold" value="<?php echo esc_attr($performance_threshold); ?>" min="0" max="100">
                            <p class="description"><?php _e('Posts below this performance score will be considered for rescheduling', 'smart-scheduler'); ?></p>
                        </td>
                    </tr>
                </table>
                
                <h2><?php _e('Optimal Posting Times', 'smart-scheduler'); ?></h2>
                <table class="form-table">
                    <?php
                    $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                    foreach ($days as $day) {
                        $times = isset($optimal_times[$day]) ? $optimal_times[$day] : ['09:00', '14:00', '19:00'];
                        ?>
                        <tr>
                            <th scope="row"><?php echo esc_html(ucfirst($day)); ?></th>
                            <td>
                                <?php foreach ($times as $i => $time): ?>
                                    <input type="time" name="optimal_times[<?php echo $day; ?>][]" value="<?php echo esc_attr($time); ?>">
                                <?php endforeach; ?>
                            </td>
                        </tr>
                        <?php
                    }
                    ?>
                </table>
                
                <?php wp_nonce_field('scs_settings', 'scs_settings_nonce'); ?>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
    
    public function add_meta_boxes() {
        add_meta_box(
            'scs-scheduler',
            __('Smart Scheduler', 'smart-scheduler'),
            [$this, 'scheduler_meta_box'],
            ['post', 'page'],
            'side',
            'high'
        );
    }
    
    public function scheduler_meta_box($post) {
        wp_nonce_field('scs_meta_box', 'scs_meta_box_nonce');
        
        $scheduled_time = get_post_meta($post->ID, '_scs_scheduled_time', true);
        $use_optimal = get_post_meta($post->ID, '_scs_use_optimal', true);
        ?>
        <p>
            <label>
                <input type="checkbox" name="scs_use_optimal" value="1" <?php checked($use_optimal, 1); ?>>
                <?php _e('Use AI Optimal Time', 'smart-scheduler'); ?>
            </label>
        </p>
        <p>
            <label for="scs_scheduled_time"><?php _e('Custom Schedule:', 'smart-scheduler'); ?></label>
            <input type="datetime-local" name="scs_scheduled_time" value="<?php echo esc_attr($scheduled_time); ?>">
        </p>
        <div id="scs-optimal-suggestion">
            <p><strong><?php _e('AI Suggestion:', 'smart-scheduler'); ?></strong></p>
            <div id="optimal-time-suggestion">
                <?php echo $this->get_optimal_time_suggestion(); ?>
            </div>
        </div>
        <?php
    }
    
    public function save_meta_box_data($post_id) {
        if (!isset($_POST['scs_meta_box_nonce']) || !wp_verify_nonce($_POST['scs_meta_box_nonce'], 'scs_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        $use_optimal = isset($_POST['scs_use_optimal']) ? 1 : 0;
        $scheduled_time = sanitize_text_field($_POST['scs_scheduled_time']);
        
        update_post_meta($post_id, '_scs_use_optimal', $use_optimal);
        update_post_meta($post_id, '_scs_scheduled_time', $scheduled_time);
        
        // Store analytics data
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_analytics';
        
        $wpdb->replace(
            $table_name,
            [
                'post_id' => $post_id,
                'scheduled_time' => $scheduled_time ? $scheduled_time : current_time('mysql'),
                'performance_rating' => 'scheduled'
            ],
            ['%d', '%s', '%s']
        );
    }
    
    private function save_settings() {
        if (!wp_verify_nonce($_POST['scs_settings_nonce'], 'scs_settings')) {
            return;
        }
        
        $auto_reschedule = isset($_POST['auto_reschedule']) ? 'yes' : 'no';
        $performance_threshold = intval($_POST['performance_threshold']);
        $optimal_times = $_POST['optimal_times'];
        
        update_option('scs_auto_reschedule', $auto_reschedule);
        update_option('scs_performance_threshold', $performance_threshold);
        update_option('scs_optimal_times', json_encode($optimal_times));
        
        echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'smart-scheduler') . '</p></div>';
    }
    
    // Helper methods for dashboard data
    private function get_scheduled_posts_count() {
        return wp_count_posts()->future;
    }
    
    private function get_published_today_count() {
        $today = date('Y-m-d');
        $posts = get_posts([
            'post_status' => 'publish',
            'date_query' => [
                [
                    'after' => $today,
                    'before' => $today . ' 23:59:59',
                    'inclusive' => true,
                ]
            ],
            'numberposts' => -1
        ]);
        return count($posts);
    }
    
    private function get_average_performance() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_analytics';
        $result = $wpdb->get_var("SELECT AVG(engagement_score) FROM $table_name WHERE engagement_score > 0");
        return $result ? round($result) : 0;
    }
    
    private function render_upcoming_posts() {
        $posts = get_posts([
            'post_status' => 'future',
            'numberposts' => 5,
            'orderby' => 'date',
            'order' => 'ASC'
        ]);
        
        if (empty($posts)) {
            echo '<p>' . __('No upcoming posts scheduled.', 'smart-scheduler') . '</p>';
            return;
        }
        
        echo '<ul class="scs-upcoming-list">';
        foreach ($posts as $post) {
            $scheduled_time = get_the_date('M j, Y g:i A', $post);
            echo '<li>';
            echo '<strong>' . esc_html($post->post_title) . '</strong><br>';
            echo '<small>' . sprintf(__('Scheduled for: %s', 'smart-scheduler'), $scheduled_time) . '</small>';
            echo '</li>';
        }
        echo '</ul>';
    }
    
    private function render_optimal_times() {
        $optimal_times = json_decode(get_option('scs_optimal_times', '[]'), true);
        
        echo '<div class="scs-optimal-times-grid">';
        foreach ($optimal_times as $day => $times) {
            echo '<div class="scs-day-times">';
            echo '<strong>' . ucfirst($day) . '</strong><br>';
            echo implode(', ', $times);
            echo '</div>';
        }
        echo '</div>';
    }
    
    private function render_analytics_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_analytics';
        
        $results = $wpdb->get_results("
            SELECT a.*, p.post_title 
            FROM $table_name a 
            LEFT JOIN {$wpdb->posts} p ON a.post_id = p.ID 
            ORDER BY a.created_at DESC 
            LIMIT 20
        ");
        
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . esc_html($row->post_title) . '</td>';
            echo '<td>' . esc_html($row->scheduled_time) . '</td>';
            echo '<td>' . esc_html($row->published_time ?: 'Not yet') . '</td>';
            echo '<td>' . esc_html($row->views) . '</td>';
            echo '<td>' . esc_html(round($row->engagement_score, 1)) . '</td>';
            echo '<td><span class="scs-performance-' . esc_attr($row->performance_rating) . '">' . esc_html(ucfirst($row->performance_rating)) . '</span></td>';
            echo '<td><button class="button button-small scs-reschedule" data-post-id="' . esc_attr($row->post_id) . '">' . __('Reschedule', 'smart-scheduler') . '</button></td>';
            echo '</tr>';
        }
    }
    
    private function get_optimal_time_suggestion() {
        $optimal_times = json_decode(get_option('scs_optimal_times', '[]'), true);
        $current_day = strtolower(date('l'));
        
        if (isset($optimal_times[$current_day])) {
            $times = $optimal_times[$current_day];
            $next_time = null;
            $current_time = date('H:i');
            
            foreach ($times as $time) {
                if ($time > $current_time) {
                    $next_time = $time;
                    break;
                }
            }
            
            if (!$next_time) {
                $next_time = $times[0];
                $current_day = date('l', strtotime('+1 day'));
            }
            
            return sprintf(__('Recommended: %s at %s', 'smart-scheduler'), ucfirst($current_day), $next_time);
        }
        
        return __('No optimal time data available', 'smart-scheduler');
    }
    
    // New admin pages for enhanced features
    
    public function ml_nlp_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Machine Learning & NLP Analysis', 'smart-scheduler'); ?></h1>
            
            <div class="scs-ml-nlp-dashboard">
                <div class="scs-section">
                    <h2><?php _e('Content Analysis', 'smart-scheduler'); ?></h2>
                    <div class="scs-content-analyzer">
                        <textarea id="content-to-analyze" placeholder="Enter content to analyze..." rows="6" style="width: 100%;"></textarea>
                        <br><br>
                        <input type="text" id="content-title" placeholder="Enter title..." style="width: 100%; margin-bottom: 10px;">
                        <button id="analyze-content" class="button button-primary"><?php _e('Analyze Content', 'smart-scheduler'); ?></button>
                        <button id="extract-keywords" class="button"><?php _e('Extract Keywords', 'smart-scheduler'); ?></button>
                        <button id="predict-performance" class="button"><?php _e('Predict Performance', 'smart-scheduler'); ?></button>
                    </div>
                    <div id="analysis-results"></div>
                </div>
                
                <div class="scs-section">
                    <h2><?php _e('ML Model Training', 'smart-scheduler'); ?></h2>
                    <p><?php _e('Train the machine learning model with your historical data to improve predictions.', 'smart-scheduler'); ?></p>
                    <button id="train-ml-model" class="button button-secondary"><?php _e('Train Model', 'smart-scheduler'); ?></button>
                    <div id="training-results"></div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function social_media_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Social Media Integration', 'smart-scheduler'); ?></h1>
            
            <div class="scs-social-dashboard">
                <div class="scs-section">
                    <h2><?php _e('Connected Platforms', 'smart-scheduler'); ?></h2>
                    <div class="scs-social-platforms">
                        <?php $this->render_social_platforms(); ?>
                    </div>
                </div>
                
                <div class="scs-section">
                    <h2><?php _e('Social Media Metrics', 'smart-scheduler'); ?></h2>
                    <div id="social-metrics-container">
                        <button id="sync-social-data" class="button"><?php _e('Sync Social Data', 'smart-scheduler'); ?></button>
                        <div id="social-metrics-display"></div>
                    </div>
                </div>
                
                <div class="scs-section">
                    <h2><?php _e('Auto-Post Settings', 'smart-scheduler'); ?></h2>
                    <form method="post" action="" id="auto-post-settings">
                        <label>
                            <input type="checkbox" name="auto_post_enabled" value="1"> 
                            <?php _e('Automatically post to social media when content is published', 'smart-scheduler'); ?>
                        </label>
                        <br><br>
                        <label><?php _e('Default Platforms:', 'smart-scheduler'); ?></label><br>
                        <label><input type="checkbox" name="platforms[]" value="facebook"> Facebook</label><br>
                        <label><input type="checkbox" name="platforms[]" value="twitter"> Twitter</label><br>
                        <label><input type="checkbox" name="platforms[]" value="linkedin"> LinkedIn</label><br>
                        <label><input type="checkbox" name="platforms[]" value="instagram"> Instagram</label><br>
                        <br>
                        <button type="submit" class="button button-primary"><?php _e('Save Settings', 'smart-scheduler'); ?></button>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function ab_testing_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('A/B Testing', 'smart-scheduler'); ?></h1>
            
            <div class="scs-ab-testing-dashboard">
                <div class="scs-section">
                    <h2><?php _e('Create New A/B Test', 'smart-scheduler'); ?></h2>
                    <form id="create-ab-test" method="post">
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Test Name', 'smart-scheduler'); ?></th>
                                <td><input type="text" name="test_name" required style="width: 100%;"></td>
                            </tr>
                            <tr>
                                <th><?php _e('Test Type', 'smart-scheduler'); ?></th>
                                <td>
                                    <select name="test_type" required>
                                        <option value=""><?php _e('Select Test Type', 'smart-scheduler'); ?></option>
                                        <option value="title"><?php _e('Title Test', 'smart-scheduler'); ?></option>
                                        <option value="content"><?php _e('Content Test', 'smart-scheduler'); ?></option>
                                        <option value="timing"><?php _e('Timing Test', 'smart-scheduler'); ?></option>
                                        <option value="platform"><?php _e('Platform Test', 'smart-scheduler'); ?></option>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Duration (days)', 'smart-scheduler'); ?></th>
                                <td><input type="number" name="test_duration" value="7" min="1" max="30"></td>
                            </tr>
                        </table>
                        
                        <div id="variant-fields">
                            <h3><?php _e('Variant A', 'smart-scheduler'); ?></h3>
                            <textarea name="variant_a[content]" placeholder="Variant A content..." rows="4" style="width: 100%;"></textarea>
                            
                            <h3><?php _e('Variant B', 'smart-scheduler'); ?></h3>
                            <textarea name="variant_b[content]" placeholder="Variant B content..." rows="4" style="width: 100%;"></textarea>
                        </div>
                        
                        <button type="submit" class="button button-primary"><?php _e('Create A/B Test', 'smart-scheduler'); ?></button>
                    </form>
                </div>
                
                <div class="scs-section">
                    <h2><?php _e('Active Tests', 'smart-scheduler'); ?></h2>
                    <div id="active-tests-list">
                        <button id="load-ab-tests" class="button"><?php _e('Load Tests', 'smart-scheduler'); ?></button>
                        <div id="ab-tests-display"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function seasonal_analysis_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Seasonal Pattern Analysis', 'smart-scheduler'); ?></h1>
            
            <div class="scs-seasonal-dashboard">
                <div class="scs-section">
                    <h2><?php _e('Current Season Analysis', 'smart-scheduler'); ?></h2>
                    <div id="current-season-info">
                        <button id="get-seasonal-insights" class="button button-primary"><?php _e('Get Current Seasonal Insights', 'smart-scheduler'); ?></button>
                        <div id="seasonal-insights-display"></div>
                    </div>
                </div>
                
                <div class="scs-section">
                    <h2><?php _e('Seasonal Trends', 'smart-scheduler'); ?></h2>
                    <div class="scs-trend-controls">
                        <label><?php _e('Analysis Period:', 'smart-scheduler'); ?></label>
                        <select id="trend-years">
                            <option value="1"><?php _e('Last 1 Year', 'smart-scheduler'); ?></option>
                            <option value="2"><?php _e('Last 2 Years', 'smart-scheduler'); ?></option>
                            <option value="3"><?php _e('Last 3 Years', 'smart-scheduler'); ?></option>
                        </select>
                        <button id="analyze-trends" class="button"><?php _e('Analyze Trends', 'smart-scheduler'); ?></button>
                    </div>
                    <div id="seasonal-trends-display"></div>
                </div>
                
                <div class="scs-section">
                    <h2><?php _e('Seasonal Recommendations', 'smart-scheduler'); ?></h2>
                    <div id="seasonal-recommendations">
                        <button id="get-seasonal-recommendations" class="button"><?php _e('Get Recommendations', 'smart-scheduler'); ?></button>
                        <div id="recommendations-display"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function competitor_analysis_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Competitor Analysis', 'smart-scheduler'); ?></h1>
            
            <div class="scs-competitor-dashboard">
                <div class="scs-section">
                    <h2><?php _e('Add Competitor', 'smart-scheduler'); ?></h2>
                    <form id="add-competitor" method="post">
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Competitor Name', 'smart-scheduler'); ?></th>
                                <td><input type="text" name="competitor_name" required style="width: 100%;"></td>
                            </tr>
                            <tr>
                                <th><?php _e('Website URL', 'smart-scheduler'); ?></th>
                                <td><input type="url" name="website_url" required style="width: 100%;"></td>
                            </tr>
                            <tr>
                                <th><?php _e('Industry', 'smart-scheduler'); ?></th>
                                <td><input type="text" name="industry" style="width: 100%;"></td>
                            </tr>
                            <tr>
                                <th><?php _e('Tracking Keywords', 'smart-scheduler'); ?></th>
                                <td>
                                    <textarea name="tracking_keywords" placeholder="Enter keywords separated by commas..." rows="3" style="width: 100%;"></textarea>
                                    <p class="description"><?php _e('Keywords to track for this competitor', 'smart-scheduler'); ?></p>
                                </td>
                            </tr>
                        </table>
                        
                        <h3><?php _e('Social Media Profiles', 'smart-scheduler'); ?></h3>
                        <table class="form-table">
                            <tr>
                                <th>Facebook</th>
                                <td><input type="url" name="social_profiles[facebook]" style="width: 100%;"></td>
                            </tr>
                            <tr>
                                <th>Twitter</th>
                                <td><input type="url" name="social_profiles[twitter]" style="width: 100%;"></td>
                            </tr>
                            <tr>
                                <th>LinkedIn</th>
                                <td><input type="url" name="social_profiles[linkedin]" style="width: 100%;"></td>
                            </tr>
                            <tr>
                                <th>Instagram</th>
                                <td><input type="url" name="social_profiles[instagram]" style="width: 100%;"></td>
                            </tr>
                        </table>
                        
                        <button type="submit" class="button button-primary"><?php _e('Add Competitor', 'smart-scheduler'); ?></button>
                    </form>
                </div>
                
                <div class="scs-section">
                    <h2><?php _e('Competitor Insights', 'smart-scheduler'); ?></h2>
                    <div id="competitor-insights">
                        <button id="get-competitor-insights" class="button"><?php _e('Get Insights', 'smart-scheduler'); ?></button>
                        <button id="compare-performance" class="button"><?php _e('Compare Performance', 'smart-scheduler'); ?></button>
                        <button id="get-content-gaps" class="button"><?php _e('Find Content Gaps', 'smart-scheduler'); ?></button>
                        <div id="competitor-insights-display"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    private function render_social_platforms() {
        $platforms = ['facebook', 'twitter', 'linkedin', 'instagram'];
        
        foreach ($platforms as $platform) {
            $credentials = get_option("scs_social_{$platform}_credentials");
            $connected = $credentials && $credentials['status'] === 'active';
            
            echo '<div class="scs-platform-card">';
            echo '<h3>' . ucfirst($platform) . '</h3>';
            
            if ($connected) {
                echo '<span class="scs-status connected">' . __('Connected', 'smart-scheduler') . '</span>';
                echo '<button class="button disconnect-platform" data-platform="' . $platform . '">' . __('Disconnect', 'smart-scheduler') . '</button>';
            } else {
                echo '<span class="scs-status disconnected">' . __('Not Connected', 'smart-scheduler') . '</span>';
                echo '<button class="button button-primary connect-platform" data-platform="' . $platform . '">' . __('Connect', 'smart-scheduler') . '</button>';
            }
            
            echo '</div>';
        }
    }
}
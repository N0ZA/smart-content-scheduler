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
}
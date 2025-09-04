<?php
if (!defined('ABSPATH')) {
    exit;
}

class SCS_Ajax_Handlers {
    
    public function __construct() {
        // Admin AJAX handlers
        add_action('wp_ajax_scs_quick_schedule', [$this, 'handle_quick_schedule']);
        add_action('wp_ajax_scs_reschedule_post', [$this, 'handle_reschedule_post']);
        add_action('wp_ajax_scs_get_optimal_suggestion', [$this, 'get_optimal_suggestion']);
        add_action('wp_ajax_scs_update_analytics', [$this, 'update_analytics']);
        add_action('wp_ajax_scs_export_data', [$this, 'export_data']);
        add_action('wp_ajax_scs_get_dashboard_stats', [$this, 'get_dashboard_stats']);
        
        // Public AJAX handlers (for tracking)
        add_action('wp_ajax_scs_track_click', [$this, 'track_click']);
        add_action('wp_ajax_nopriv_scs_track_click', [$this, 'track_click']);
        add_action('wp_ajax_scs_track_share', [$this, 'track_share']);
        add_action('wp_ajax_nopriv_scs_track_share', [$this, 'track_share']);
    }
    
    public function handle_quick_schedule() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        if (!current_user_can('publish_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $post_title = sanitize_text_field($_POST['post_title']);
        $post_content = wp_kses_post($_POST['post_content']);
        $schedule_type = sanitize_text_field($_POST['schedule_type']);
        $custom_datetime = sanitize_text_field($_POST['custom_datetime']);
        
        if (empty($post_title)) {
            wp_send_json_error('Post title is required');
        }
        
        $post_data = [
            'post_title' => $post_title,
            'post_content' => $post_content,
            'post_status' => 'future',
            'post_author' => get_current_user_id(),
            'post_type' => 'post'
        ];
        
        if ($schedule_type === 'optimal') {
            $scheduler = new SCS_Scheduler();
            $post_id = $scheduler->schedule_post_optimally($post_data);
            
            if ($post_id) {
                $scheduled_time = get_post_meta($post_id, '_scs_scheduled_time', true);
                wp_send_json_success([
                    'message' => __('Post scheduled successfully using AI optimal time!', 'smart-scheduler'),
                    'post_id' => $post_id,
                    'scheduled_time' => $scheduled_time
                ]);
            } else {
                wp_send_json_error('Failed to schedule post');
            }
        } else {
            // Custom scheduling
            if (empty($custom_datetime)) {
                wp_send_json_error('Custom date/time is required');
            }
            
            $post_data['post_date'] = $custom_datetime;
            $post_data['post_date_gmt'] = get_gmt_from_date($custom_datetime);
            
            $post_id = wp_insert_post($post_data);
            
            if ($post_id) {
                update_post_meta($post_id, '_scs_scheduled_time', $custom_datetime);
                
                // Add to analytics
                global $wpdb;
                $table_name = $wpdb->prefix . 'scs_analytics';
                $wpdb->insert(
                    $table_name,
                    [
                        'post_id' => $post_id,
                        'scheduled_time' => $custom_datetime,
                        'performance_rating' => 'scheduled'
                    ],
                    ['%d', '%s', '%s']
                );
                
                wp_send_json_success([
                    'message' => __('Post scheduled successfully for custom time!', 'smart-scheduler'),
                    'post_id' => $post_id,
                    'scheduled_time' => $custom_datetime
                ]);
            } else {
                wp_send_json_error('Failed to schedule post');
            }
        }
    }
    
    public function handle_reschedule_post() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }
        
        $scheduler = new SCS_Scheduler();
        $new_post_id = $scheduler->reschedule_post($post_id);
        
        if ($new_post_id) {
            $scheduled_time = get_post_meta($new_post_id, '_scs_scheduled_time', true);
            wp_send_json_success([
                'message' => __('Post rescheduled successfully!', 'smart-scheduler'),
                'new_post_id' => $new_post_id,
                'scheduled_time' => $scheduled_time
            ]);
        } else {
            wp_send_json_error('Failed to reschedule post');
        }
    }
    
    public function get_optimal_suggestion() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        $scheduler = new SCS_Scheduler();
        $optimal_time = $scheduler->calculate_optimal_time();
        
        if ($optimal_time) {
            $formatted_time = date('M j, Y g:i A', strtotime($optimal_time));
            wp_send_json_success([
                'optimal_time' => $optimal_time,
                'formatted_time' => $formatted_time,
                'message' => sprintf(__('Recommended posting time: %s', 'smart-scheduler'), $formatted_time)
            ]);
        } else {
            wp_send_json_error('Unable to calculate optimal time');
        }
    }
    
    public function update_analytics() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $period = intval($_POST['period']);
        if (!in_array($period, [7, 30, 90])) {
            $period = 30;
        }
        
        $analytics = new SCS_Analytics();
        $data = $analytics->compile_analytics_data($period);
        
        wp_send_json_success($data);
    }
    
    public function export_data() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $format = sanitize_text_field($_POST['format']);
        if (!in_array($format, ['csv', 'json'])) {
            $format = 'csv';
        }
        
        $analytics = new SCS_Analytics();
        $data = $analytics->export_analytics_data($format);
        
        $filename = 'smart-scheduler-analytics-' . date('Y-m-d') . '.' . $format;
        
        wp_send_json_success([
            'data' => $data,
            'filename' => $filename,
            'mime_type' => $format === 'csv' ? 'text/csv' : 'application/json'
        ]);
    }
    
    public function get_dashboard_stats() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Get scheduled posts count
        $scheduled_count = wp_count_posts()->future;
        
        // Get published today count
        $today = date('Y-m-d');
        $published_today = count(get_posts([
            'post_status' => 'publish',
            'date_query' => [
                [
                    'after' => $today,
                    'before' => $today . ' 23:59:59',
                    'inclusive' => true,
                ]
            ],
            'numberposts' => -1
        ]));
        
        // Get average performance
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_analytics';
        $avg_performance = $wpdb->get_var("
            SELECT AVG(engagement_score) 
            FROM $table_name 
            WHERE engagement_score > 0 
            AND published_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        
        // Get upcoming posts
        $upcoming_posts = get_posts([
            'post_status' => 'future',
            'numberposts' => 5,
            'orderby' => 'date',
            'order' => 'ASC'
        ]);
        
        $upcoming_list = [];
        foreach ($upcoming_posts as $post) {
            $upcoming_list[] = [
                'id' => $post->ID,
                'title' => $post->post_title,
                'scheduled_time' => get_the_date('M j, Y g:i A', $post)
            ];
        }
        
        wp_send_json_success([
            'scheduled_count' => $scheduled_count,
            'published_today' => $published_today,
            'avg_performance' => $avg_performance ? round($avg_performance) : 0,
            'upcoming_posts' => $upcoming_list
        ]);
    }
    
    public function track_click() {
        $post_id = intval($_POST['post_id']);
        $link_url = esc_url_raw($_POST['link_url']);
        
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }
        
        $analytics = new SCS_Analytics();
        $analytics->track_external_click($post_id, $link_url);
        
        wp_send_json_success('Click tracked');
    }
    
    public function track_share() {
        $post_id = intval($_POST['post_id']);
        $platform = sanitize_text_field($_POST['platform']);
        
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }
        
        $analytics = new SCS_Analytics();
        $analytics->track_social_share($post_id, $platform);
        
        wp_send_json_success('Share tracked');
    }
}
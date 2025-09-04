<?php
if (!defined('ABSPATH')) {
    exit;
}

class SCS_Scheduler {
    
    public function __construct() {
        add_action('scs_check_performance', [$this, 'check_post_performance']);
        add_action('publish_post', [$this, 'track_post_publish']);
        add_action('wp_head', [$this, 'track_post_view']);
        add_action('init', [$this, 'handle_scheduled_posts']);
    }
    
    public function handle_scheduled_posts() {
        // Check for posts that should be published now
        $scheduled_posts = get_posts([
            'post_status' => 'future',
            'numberposts' => -1,
            'meta_query' => [
                [
                    'key' => '_scs_use_optimal',
                    'value' => '1',
                    'compare' => '='
                ]
            ]
        ]);
        
        foreach ($scheduled_posts as $post) {
            $optimal_time = $this->calculate_optimal_time($post->ID);
            $scheduled_time = get_post_meta($post->ID, '_scs_scheduled_time', true);
            
            if ($optimal_time && $scheduled_time !== $optimal_time) {
                // Update the post's publish time to optimal time
                wp_update_post([
                    'ID' => $post->ID,
                    'post_date' => $optimal_time,
                    'post_date_gmt' => get_gmt_from_date($optimal_time)
                ]);
                
                update_post_meta($post->ID, '_scs_scheduled_time', $optimal_time);
            }
        }
    }
    
    public function calculate_optimal_time($post_id = null) {
        $optimal_times = json_decode(get_option('scs_optimal_times', '[]'), true);
        
        if (empty($optimal_times)) {
            return false;
        }
        
        // Get historical performance data for better suggestions
        $performance_data = $this->get_performance_by_time();
        
        // Find the best performing time slot for the next 7 days
        $best_time = null;
        $best_score = 0;
        
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("+$i days"));
            $day = strtolower(date('l', strtotime($date)));
            
            if (!isset($optimal_times[$day])) {
                continue;
            }
            
            foreach ($optimal_times[$day] as $time) {
                $datetime = $date . ' ' . $time . ':00';
                
                // Skip past times for today
                if ($i === 0 && $time <= date('H:i')) {
                    continue;
                }
                
                // Calculate score based on historical performance
                $hour = intval(substr($time, 0, 2));
                $score = $this->calculate_time_score($day, $hour, $performance_data);
                
                if ($score > $best_score) {
                    $best_score = $score;
                    $best_time = $datetime;
                }
            }
        }
        
        return $best_time;
    }
    
    private function calculate_time_score($day, $hour, $performance_data) {
        $base_score = 50; // Default score
        
        // Check if we have performance data for this time slot
        $time_key = $day . '_' . $hour;
        if (isset($performance_data[$time_key])) {
            $base_score = $performance_data[$time_key];
        }
        
        // Apply day-of-week multipliers
        $day_multipliers = [
            'monday' => 0.8,
            'tuesday' => 1.0,
            'wednesday' => 1.1,
            'thursday' => 1.0,
            'friday' => 0.9,
            'saturday' => 0.7,
            'sunday' => 0.6
        ];
        
        // Apply hour-of-day multipliers
        $hour_multipliers = [
            6 => 0.5, 7 => 0.6, 8 => 0.8, 9 => 1.0, 10 => 1.1,
            11 => 1.0, 12 => 1.2, 13 => 1.1, 14 => 1.3, 15 => 1.2,
            16 => 1.0, 17 => 0.9, 18 => 1.1, 19 => 1.4, 20 => 1.3,
            21 => 1.0, 22 => 0.8, 23 => 0.6
        ];
        
        $day_mult = isset($day_multipliers[$day]) ? $day_multipliers[$day] : 1.0;
        $hour_mult = isset($hour_multipliers[$hour]) ? $hour_multipliers[$hour] : 1.0;
        
        return $base_score * $day_mult * $hour_mult;
    }
    
    private function get_performance_by_time() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_analytics';
        
        $results = $wpdb->get_results("
            SELECT 
                DAYNAME(published_time) as day_name,
                HOUR(published_time) as hour,
                AVG(engagement_score) as avg_score
            FROM $table_name 
            WHERE published_time IS NOT NULL 
            AND engagement_score > 0
            AND published_time >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            GROUP BY DAYNAME(published_time), HOUR(published_time)
        ");
        
        $performance_data = [];
        foreach ($results as $row) {
            $key = strtolower($row->day_name) . '_' . $row->hour;
            $performance_data[$key] = floatval($row->avg_score);
        }
        
        return $performance_data;
    }
    
    public function check_post_performance() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_analytics';
        
        // Get posts published in the last 24 hours
        $recent_posts = $wpdb->get_results("
            SELECT * FROM $table_name 
            WHERE published_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND published_time IS NOT NULL
            AND performance_rating = 'pending'
        ");
        
        foreach ($recent_posts as $post_data) {
            $performance_score = $this->calculate_performance_score($post_data->post_id);
            $rating = $this->get_performance_rating($performance_score);
            
            // Update analytics data
            $wpdb->update(
                $table_name,
                [
                    'engagement_score' => $performance_score,
                    'performance_rating' => $rating
                ],
                ['id' => $post_data->id],
                ['%f', '%s'],
                ['%d']
            );
            
            // Check if post should be rescheduled
            if (get_option('scs_auto_reschedule', 'yes') === 'yes') {
                $threshold = intval(get_option('scs_performance_threshold', 50));
                if ($performance_score < $threshold && $rating === 'poor') {
                    $this->reschedule_post($post_data->post_id);
                }
            }
        }
    }
    
    private function calculate_performance_score($post_id) {
        $views = $this->get_post_views($post_id);
        $clicks = $this->get_post_clicks($post_id);
        $shares = $this->get_post_shares($post_id);
        $comments = get_comments_number($post_id);
        
        // Weighted scoring system
        $score = 0;
        $score += min($views * 0.1, 30); // Views (max 30 points)
        $score += min($clicks * 2, 25);  // Clicks (max 25 points)
        $score += min($shares * 5, 25);  // Shares (max 25 points)
        $score += min($comments * 3, 20); // Comments (max 20 points)
        
        return min($score, 100); // Cap at 100
    }
    
    private function get_performance_rating($score) {
        if ($score >= 80) return 'excellent';
        if ($score >= 60) return 'good';
        if ($score >= 40) return 'fair';
        return 'poor';
    }
    
    private function get_post_views($post_id) {
        // Simple view tracking - in a real plugin you'd use Google Analytics API or similar
        $views = get_post_meta($post_id, '_scs_views', true);
        return intval($views);
    }
    
    private function get_post_clicks($post_id) {
        $clicks = get_post_meta($post_id, '_scs_clicks', true);
        return intval($clicks);
    }
    
    private function get_post_shares($post_id) {
        $shares = get_post_meta($post_id, '_scs_shares', true);
        return intval($shares);
    }
    
    public function track_post_view() {
        if (is_single()) {
            global $post;
            $current_views = intval(get_post_meta($post->ID, '_scs_views', true));
            update_post_meta($post->ID, '_scs_views', $current_views + 1);
            
            // Update analytics table
            global $wpdb;
            $table_name = $wpdb->prefix . 'scs_analytics';
            $wpdb->query($wpdb->prepare("
                UPDATE $table_name 
                SET views = views + 1 
                WHERE post_id = %d
            ", $post->ID));
        }
    }
    
    public function track_post_publish($post_id) {
        // Update analytics when post is published
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_analytics';
        
        $wpdb->update(
            $table_name,
            [
                'published_time' => current_time('mysql'),
                'performance_rating' => 'pending'
            ],
            ['post_id' => $post_id],
            ['%s', '%s'],
            ['%d']
        );
    }
    
    public function reschedule_post($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return false;
        }
        
        // Find optimal time for rescheduling
        $optimal_time = $this->calculate_optimal_time($post_id);
        if (!$optimal_time) {
            return false;
        }
        
        // Create a duplicate post for rescheduling
        $new_post_data = [
            'post_title' => $post->post_title . ' (Rescheduled)',
            'post_content' => $post->post_content,
            'post_status' => 'future',
            'post_date' => $optimal_time,
            'post_date_gmt' => get_gmt_from_date($optimal_time),
            'post_type' => $post->post_type,
            'post_author' => $post->post_author
        ];
        
        $new_post_id = wp_insert_post($new_post_data);
        
        if ($new_post_id) {
            // Copy meta data
            $meta_data = get_post_meta($post_id);
            foreach ($meta_data as $key => $values) {
                foreach ($values as $value) {
                    add_post_meta($new_post_id, $key, maybe_unserialize($value));
                }
            }
            
            // Add to analytics
            global $wpdb;
            $table_name = $wpdb->prefix . 'scs_analytics';
            $wpdb->insert(
                $table_name,
                [
                    'post_id' => $new_post_id,
                    'scheduled_time' => $optimal_time,
                    'performance_rating' => 'rescheduled'
                ],
                ['%d', '%s', '%s']
            );
            
            // Add admin notice
            update_option('scs_reschedule_notice', sprintf(
                __('Post "%s" has been automatically rescheduled for better performance.', 'smart-scheduler'),
                $post->post_title
            ));
            
            return $new_post_id;
        }
        
        return false;
    }
    
    public function get_optimal_times_for_week() {
        $optimal_times = [];
        $performance_data = $this->get_performance_by_time();
        
        for ($i = 0; $i < 7; $i++) {
            $date = date('Y-m-d', strtotime("+$i days"));
            $day = strtolower(date('l', strtotime($date)));
            
            $day_times = [];
            for ($hour = 6; $hour <= 23; $hour++) {
                $time_key = $day . '_' . $hour;
                $score = isset($performance_data[$time_key]) ? $performance_data[$time_key] : 50;
                
                if ($score >= 70) { // Only include high-performing times
                    $day_times[] = [
                        'time' => sprintf('%02d:00', $hour),
                        'score' => $score
                    ];
                }
            }
            
            // Sort by score and take top 3
            usort($day_times, function($a, $b) {
                return $b['score'] - $a['score'];
            });
            
            $optimal_times[$day] = array_slice($day_times, 0, 3);
        }
        
        return $optimal_times;
    }
    
    public function schedule_post_optimally($post_data) {
        $optimal_time = $this->calculate_optimal_time();
        
        if (!$optimal_time) {
            return false;
        }
        
        $post_data['post_status'] = 'future';
        $post_data['post_date'] = $optimal_time;
        $post_data['post_date_gmt'] = get_gmt_from_date($optimal_time);
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id) {
            update_post_meta($post_id, '_scs_use_optimal', 1);
            update_post_meta($post_id, '_scs_scheduled_time', $optimal_time);
            
            // Add to analytics
            global $wpdb;
            $table_name = $wpdb->prefix . 'scs_analytics';
            $wpdb->insert(
                $table_name,
                [
                    'post_id' => $post_id,
                    'scheduled_time' => $optimal_time,
                    'performance_rating' => 'scheduled'
                ],
                ['%d', '%s', '%s']
            );
            
            return $post_id;
        }
        
        return false;
    }
}
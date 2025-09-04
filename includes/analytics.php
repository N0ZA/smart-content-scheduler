<?php
if (!defined('ABSPATH')) {
    exit;
}

class SCS_Analytics {
    
    public function __construct() {
        add_action('wp_ajax_scs_get_analytics_data', [$this, 'get_analytics_data']);
        add_action('wp_ajax_scs_update_optimal_times', [$this, 'update_optimal_times']);
        add_action('admin_notices', [$this, 'show_reschedule_notice']);
    }
    
    public function get_analytics_data() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        $period = intval($_POST['period']);
        $data = $this->compile_analytics_data($period);
        
        wp_send_json_success($data);
    }
    
    public function compile_analytics_data($days = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_analytics';
        
        // Get performance data for the specified period
        $performance_data = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(published_time) as date,
                AVG(engagement_score) as avg_engagement,
                COUNT(*) as post_count,
                SUM(views) as total_views,
                SUM(clicks) as total_clicks,
                SUM(shares) as total_shares
            FROM $table_name 
            WHERE published_time >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            AND published_time IS NOT NULL
            GROUP BY DATE(published_time)
            ORDER BY date ASC
        ", $days));
        
        // Get hourly performance data
        $hourly_data = $wpdb->get_results($wpdb->prepare("
            SELECT 
                HOUR(published_time) as hour,
                AVG(engagement_score) as avg_engagement,
                COUNT(*) as post_count
            FROM $table_name 
            WHERE published_time >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND published_time IS NOT NULL
            GROUP BY HOUR(published_time)
            ORDER BY hour ASC
        ", $days));
        
        // Get day of week performance
        $weekly_data = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DAYOFWEEK(published_time) as day_num,
                DAYNAME(published_time) as day_name,
                AVG(engagement_score) as avg_engagement,
                COUNT(*) as post_count
            FROM $table_name 
            WHERE published_time >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND published_time IS NOT NULL
            GROUP BY DAYOFWEEK(published_time), DAYNAME(published_time)
            ORDER BY day_num ASC
        ", $days));
        
        // Get top performing posts
        $top_posts = $wpdb->get_results($wpdb->prepare("
            SELECT 
                a.*,
                p.post_title,
                p.post_date
            FROM $table_name a
            LEFT JOIN {$wpdb->posts} p ON a.post_id = p.ID
            WHERE a.published_time >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND a.engagement_score > 0
            ORDER BY a.engagement_score DESC
            LIMIT 10
        ", $days));
        
        return [
            'daily_performance' => $performance_data,
            'hourly_performance' => $hourly_data,
            'weekly_performance' => $weekly_data,
            'top_posts' => $top_posts,
            'summary' => $this->get_analytics_summary($days)
        ];
    }
    
    private function get_analytics_summary($days) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_analytics';
        
        $summary = $wpdb->get_row($wpdb->prepare("
            SELECT 
                COUNT(*) as total_posts,
                AVG(engagement_score) as avg_engagement,
                SUM(views) as total_views,
                SUM(clicks) as total_clicks,
                SUM(shares) as total_shares,
                COUNT(CASE WHEN performance_rating = 'excellent' THEN 1 END) as excellent_posts,
                COUNT(CASE WHEN performance_rating = 'good' THEN 1 END) as good_posts,
                COUNT(CASE WHEN performance_rating = 'fair' THEN 1 END) as fair_posts,
                COUNT(CASE WHEN performance_rating = 'poor' THEN 1 END) as poor_posts
            FROM $table_name 
            WHERE published_time >= DATE_SUB(NOW(), INTERVAL %d DAY)
            AND published_time IS NOT NULL
        ", $days));
        
        return $summary;
    }
    
    public function update_optimal_times() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $new_optimal_times = $this->calculate_optimal_times_from_data();
        update_option('scs_optimal_times', json_encode($new_optimal_times));
        
        wp_send_json_success([
            'message' => __('Optimal times updated successfully!', 'smart-scheduler'),
            'optimal_times' => $new_optimal_times
        ]);
    }
    
    private function calculate_optimal_times_from_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_analytics';
        
        // Get performance data by day and hour
        $results = $wpdb->get_results("
            SELECT 
                LOWER(DAYNAME(published_time)) as day_name,
                HOUR(published_time) as hour,
                AVG(engagement_score) as avg_score,
                COUNT(*) as post_count
            FROM $table_name 
            WHERE published_time >= DATE_SUB(NOW(), INTERVAL 90 DAY)
            AND published_time IS NOT NULL
            AND engagement_score > 0
            GROUP BY LOWER(DAYNAME(published_time)), HOUR(published_time)
            HAVING post_count >= 2
            ORDER BY day_name, avg_score DESC
        ");
        
        $optimal_times = [];
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        foreach ($days as $day) {
            $day_data = array_filter($results, function($row) use ($day) {
                return $row->day_name === $day;
            });
            
            // Sort by performance score and take top 3 hours
            usort($day_data, function($a, $b) {
                return $b->avg_score - $a->avg_score;
            });
            
            $optimal_times[$day] = [];
            $count = 0;
            foreach ($day_data as $row) {
                if ($count >= 3) break;
                
                // Only include times with good performance (score >= 60)
                if ($row->avg_score >= 60) {
                    $optimal_times[$day][] = sprintf('%02d:00', $row->hour);
                    $count++;
                }
            }
            
            // Fill with default times if not enough data
            while (count($optimal_times[$day]) < 3) {
                $default_times = ['09:00', '14:00', '19:00'];
                foreach ($default_times as $time) {
                    if (!in_array($time, $optimal_times[$day])) {
                        $optimal_times[$day][] = $time;
                        break;
                    }
                }
            }
        }
        
        return $optimal_times;
    }
    
    public function get_engagement_trends($days = 30) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_analytics';
        
        $trends = $wpdb->get_results($wpdb->prepare("
            SELECT 
                DATE(published_time) as date,
                AVG(engagement_score) as avg_engagement,
                COUNT(*) as post_count,
                AVG(views) as avg_views,
                AVG(clicks) as avg_clicks,
                AVG(shares) as avg_shares
            FROM $table_name 
            WHERE published_time >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
            AND published_time IS NOT NULL
            GROUP BY DATE(published_time)
            ORDER BY date ASC
        ", $days));
        
        return $trends;
    }
    
    public function get_best_posting_times() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_analytics';
        
        $best_times = $wpdb->get_results("
            SELECT 
                LOWER(DAYNAME(published_time)) as day_name,
                HOUR(published_time) as hour,
                AVG(engagement_score) as avg_score,
                COUNT(*) as post_count
            FROM $table_name 
            WHERE published_time >= DATE_SUB(NOW(), INTERVAL 60 DAY)
            AND published_time IS NOT NULL
            AND engagement_score > 0
            GROUP BY LOWER(DAYNAME(published_time)), HOUR(published_time)
            HAVING post_count >= 2 AND avg_score >= 70
            ORDER BY avg_score DESC
            LIMIT 20
        ");
        
        return $best_times;
    }
    
    public function generate_performance_report($post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_analytics';
        
        $post_data = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $table_name WHERE post_id = %d
        ", $post_id));
        
        if (!$post_data) {
            return false;
        }
        
        $post = get_post($post_id);
        
        // Calculate various metrics
        $engagement_rate = $post_data->views > 0 ? ($post_data->clicks / $post_data->views) * 100 : 0;
        $share_rate = $post_data->views > 0 ? ($post_data->shares / $post_data->views) * 100 : 0;
        
        // Compare with average performance
        $avg_performance = $wpdb->get_var("
            SELECT AVG(engagement_score) FROM $table_name 
            WHERE published_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            AND engagement_score > 0
        ");
        
        $performance_vs_avg = $avg_performance > 0 ? 
            (($post_data->engagement_score - $avg_performance) / $avg_performance) * 100 : 0;
        
        return [
            'post_title' => $post->post_title,
            'post_data' => $post_data,
            'engagement_rate' => round($engagement_rate, 2),
            'share_rate' => round($share_rate, 2),
            'performance_vs_average' => round($performance_vs_avg, 1),
            'recommendations' => $this->generate_recommendations($post_data)
        ];
    }
    
    private function generate_recommendations($post_data) {
        $recommendations = [];
        
        // Low views recommendation
        if ($post_data->views < 50) {
            $recommendations[] = __('Consider promoting this post on social media to increase visibility.', 'smart-scheduler');
        }
        
        // Low engagement recommendation
        if ($post_data->engagement_score < 40) {
            $recommendations[] = __('This post may benefit from rescheduling to a more optimal time.', 'smart-scheduler');
        }
        
        // Low click rate recommendation
        if ($post_data->views > 0 && ($post_data->clicks / $post_data->views) < 0.02) {
            $recommendations[] = __('Consider improving the post title or meta description to increase click-through rate.', 'smart-scheduler');
        }
        
        // Good performance recognition
        if ($post_data->engagement_score >= 80) {
            $recommendations[] = __('Excellent performance! Consider using similar content and timing for future posts.', 'smart-scheduler');
        }
        
        return $recommendations;
    }
    
    public function export_analytics_data($format = 'csv') {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_analytics';
        
        $data = $wpdb->get_results("
            SELECT 
                p.post_title,
                a.scheduled_time,
                a.published_time,
                a.views,
                a.clicks,
                a.shares,
                a.engagement_score,
                a.performance_rating
            FROM $table_name a
            LEFT JOIN {$wpdb->posts} p ON a.post_id = p.ID
            WHERE a.published_time IS NOT NULL
            ORDER BY a.published_time DESC
        ", ARRAY_A);
        
        if ($format === 'csv') {
            return $this->generate_csv($data);
        } elseif ($format === 'json') {
            return json_encode($data);
        }
        
        return $data;
    }
    
    private function generate_csv($data) {
        $csv_output = '';
        
        if (!empty($data)) {
            // Headers
            $csv_output .= implode(',', array_keys($data[0])) . "\n";
            
            // Data rows
            foreach ($data as $row) {
                $csv_output .= implode(',', array_map(function($value) {
                    return '"' . str_replace('"', '""', $value) . '"';
                }, $row)) . "\n";
            }
        }
        
        return $csv_output;
    }
    
    public function show_reschedule_notice() {
        $notice = get_option('scs_reschedule_notice');
        if ($notice) {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p>' . esc_html($notice) . '</p>';
            echo '</div>';
            delete_option('scs_reschedule_notice');
        }
    }
    
    public function track_external_click($post_id, $link_url) {
        // Track clicks on external links within posts
        $current_clicks = intval(get_post_meta($post_id, '_scs_clicks', true));
        update_post_meta($post_id, '_scs_clicks', $current_clicks + 1);
        
        // Update analytics table
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_analytics';
        $wpdb->query($wpdb->prepare("
            UPDATE $table_name 
            SET clicks = clicks + 1 
            WHERE post_id = %d
        ", $post_id));
    }
    
    public function track_social_share($post_id, $platform) {
        // Track social media shares
        $current_shares = intval(get_post_meta($post_id, '_scs_shares', true));
        update_post_meta($post_id, '_scs_shares', $current_shares + 1);
        
        // Track by platform
        $platform_key = '_scs_shares_' . sanitize_key($platform);
        $platform_shares = intval(get_post_meta($post_id, $platform_key, true));
        update_post_meta($post_id, $platform_key, $platform_shares + 1);
        
        // Update analytics table
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_analytics';
        $wpdb->query($wpdb->prepare("
            UPDATE $table_name 
            SET shares = shares + 1 
            WHERE post_id = %d
        ", $post_id));
    }
}
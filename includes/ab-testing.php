<?php
if (!defined('ABSPATH')) {
    exit;
}

class SCS_AB_Testing {
    
    public function __construct() {
        add_action('wp_ajax_scs_create_ab_test', [$this, 'create_ab_test']);
        add_action('wp_ajax_scs_get_ab_test_results', [$this, 'get_ab_test_results']);
        add_action('wp_ajax_scs_end_ab_test', [$this, 'end_ab_test']);
        add_action('wp_ajax_scs_get_ab_test_list', [$this, 'get_ab_test_list']);
        
        // Hook to track post performance for A/B tests
        add_action('scs_check_performance', [$this, 'update_ab_test_metrics']);
        
        // Hook to automatically end completed tests
        add_action('scs_check_ab_tests', [$this, 'check_completed_tests']);
        
        // Schedule A/B test checks
        if (!wp_next_scheduled('scs_check_ab_tests')) {
            wp_schedule_event(time(), 'daily', 'scs_check_ab_tests');
        }
    }
    
    /**
     * Create a new A/B test
     */
    public function create_ab_test() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        if (!current_user_can('publish_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $test_name = sanitize_text_field($_POST['test_name']);
        $test_type = sanitize_text_field($_POST['test_type']); // title, content, timing, platform
        $variant_a = $this->sanitize_variant($_POST['variant_a']);
        $variant_b = $this->sanitize_variant($_POST['variant_b']);
        $test_duration = intval($_POST['test_duration']); // days
        $sample_size = intval($_POST['sample_size']); // percentage of traffic
        
        if (empty($test_name) || empty($test_type)) {
            wp_send_json_error('Test name and type are required');
        }
        
        $test_id = $this->create_test_record($test_name, $test_type, $variant_a, $variant_b, $test_duration, $sample_size);
        
        if ($test_id) {
            // Create the test posts
            $posts_created = $this->create_test_posts($test_id, $variant_a, $variant_b, $test_type);
            
            wp_send_json_success([
                'test_id' => $test_id,
                'message' => 'A/B test created successfully',
                'posts_created' => $posts_created
            ]);
        } else {
            wp_send_json_error('Failed to create A/B test');
        }
    }
    
    /**
     * Create test record in database
     */
    private function create_test_record($name, $type, $variant_a, $variant_b, $duration, $sample_size) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_ab_tests';
        
        $result = $wpdb->insert(
            $table_name,
            [
                'test_name' => $name,
                'test_type' => $type,
                'variant_a' => json_encode($variant_a),
                'variant_b' => json_encode($variant_b),
                'duration_days' => $duration,
                'sample_size' => $sample_size,
                'status' => 'active',
                'start_date' => current_time('mysql'),
                'end_date' => date('Y-m-d H:i:s', strtotime("+{$duration} days")),
                'created_by' => get_current_user_id()
            ],
            ['%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%d']
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Create test posts based on variants
     */
    private function create_test_posts($test_id, $variant_a, $variant_b, $test_type) {
        $posts_created = [];
        
        switch ($test_type) {
            case 'title':
                $posts_created = $this->create_title_test_posts($test_id, $variant_a, $variant_b);
                break;
                
            case 'content':
                $posts_created = $this->create_content_test_posts($test_id, $variant_a, $variant_b);
                break;
                
            case 'timing':
                $posts_created = $this->create_timing_test_posts($test_id, $variant_a, $variant_b);
                break;
                
            case 'platform':
                $posts_created = $this->create_platform_test_posts($test_id, $variant_a, $variant_b);
                break;
        }
        
        return $posts_created;
    }
    
    /**
     * Create posts for title A/B test
     */
    private function create_title_test_posts($test_id, $variant_a, $variant_b) {
        $base_content = $variant_a['content'] ?? 'Test content for A/B testing.';
        
        // Create variant A post
        $post_a_id = wp_insert_post([
            'post_title' => $variant_a['title'],
            'post_content' => $base_content,
            'post_status' => 'future',
            'post_date' => $variant_a['scheduled_time'] ?? date('Y-m-d H:i:s', strtotime('+1 hour')),
            'meta_input' => [
                '_scs_ab_test_id' => $test_id,
                '_scs_ab_variant' => 'A',
                '_scs_ab_test_type' => 'title'
            ]
        ]);
        
        // Create variant B post
        $post_b_id = wp_insert_post([
            'post_title' => $variant_b['title'],
            'post_content' => $base_content,
            'post_status' => 'future',
            'post_date' => $variant_b['scheduled_time'] ?? date('Y-m-d H:i:s', strtotime('+1 hour')),
            'meta_input' => [
                '_scs_ab_test_id' => $test_id,
                '_scs_ab_variant' => 'B',
                '_scs_ab_test_type' => 'title'
            ]
        ]);
        
        // Update test record with post IDs
        $this->update_test_posts($test_id, $post_a_id, $post_b_id);
        
        return ['variant_a' => $post_a_id, 'variant_b' => $post_b_id];
    }
    
    /**
     * Create posts for content A/B test
     */
    private function create_content_test_posts($test_id, $variant_a, $variant_b) {
        $base_title = $variant_a['title'] ?? 'A/B Test Post';
        $scheduled_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Create variant A post
        $post_a_id = wp_insert_post([
            'post_title' => $base_title . ' (A)',
            'post_content' => $variant_a['content'],
            'post_status' => 'future',
            'post_date' => $scheduled_time,
            'meta_input' => [
                '_scs_ab_test_id' => $test_id,
                '_scs_ab_variant' => 'A',
                '_scs_ab_test_type' => 'content'
            ]
        ]);
        
        // Create variant B post
        $post_b_id = wp_insert_post([
            'post_title' => $base_title . ' (B)',
            'post_content' => $variant_b['content'],
            'post_status' => 'future',
            'post_date' => $scheduled_time,
            'meta_input' => [
                '_scs_ab_test_id' => $test_id,
                '_scs_ab_variant' => 'B',
                '_scs_ab_test_type' => 'content'
            ]
        ]);
        
        $this->update_test_posts($test_id, $post_a_id, $post_b_id);
        
        return ['variant_a' => $post_a_id, 'variant_b' => $post_b_id];
    }
    
    /**
     * Create posts for timing A/B test
     */
    private function create_timing_test_posts($test_id, $variant_a, $variant_b) {
        $base_title = $variant_a['title'] ?? 'Timing A/B Test Post';
        $base_content = $variant_a['content'] ?? 'Test content for timing optimization.';
        
        // Create variant A post (different timing)
        $post_a_id = wp_insert_post([
            'post_title' => $base_title,
            'post_content' => $base_content,
            'post_status' => 'future',
            'post_date' => $variant_a['scheduled_time'],
            'meta_input' => [
                '_scs_ab_test_id' => $test_id,
                '_scs_ab_variant' => 'A',
                '_scs_ab_test_type' => 'timing'
            ]
        ]);
        
        // Create variant B post (different timing)
        $post_b_id = wp_insert_post([
            'post_title' => $base_title,
            'post_content' => $base_content,
            'post_status' => 'future',
            'post_date' => $variant_b['scheduled_time'],
            'meta_input' => [
                '_scs_ab_test_id' => $test_id,
                '_scs_ab_variant' => 'B',
                '_scs_ab_test_type' => 'timing'
            ]
        ]);
        
        $this->update_test_posts($test_id, $post_a_id, $post_b_id);
        
        return ['variant_a' => $post_a_id, 'variant_b' => $post_b_id];
    }
    
    /**
     * Create posts for platform A/B test
     */
    private function create_platform_test_posts($test_id, $variant_a, $variant_b) {
        $base_title = $variant_a['title'] ?? 'Platform A/B Test Post';
        $base_content = $variant_a['content'] ?? 'Test content for platform optimization.';
        $scheduled_time = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        // Create single post for platform testing
        $post_id = wp_insert_post([
            'post_title' => $base_title,
            'post_content' => $base_content,
            'post_status' => 'future',
            'post_date' => $scheduled_time,
            'meta_input' => [
                '_scs_ab_test_id' => $test_id,
                '_scs_ab_test_type' => 'platform',
                '_scs_ab_platform_a' => json_encode($variant_a['platforms']),
                '_scs_ab_platform_b' => json_encode($variant_b['platforms'])
            ]
        ]);
        
        $this->update_test_posts($test_id, $post_id, null);
        
        return ['post_id' => $post_id];
    }
    
    /**
     * Update test record with post IDs
     */
    private function update_test_posts($test_id, $post_a_id, $post_b_id = null) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_ab_tests';
        
        $wpdb->update(
            $table_name,
            [
                'post_a_id' => $post_a_id,
                'post_b_id' => $post_b_id
            ],
            ['id' => $test_id],
            ['%d', '%d'],
            ['%d']
        );
    }
    
    /**
     * Get A/B test results
     */
    public function get_ab_test_results() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        $test_id = intval($_POST['test_id']);
        
        if (!$test_id) {
            wp_send_json_error('Invalid test ID');
        }
        
        $results = $this->calculate_test_results($test_id);
        
        wp_send_json_success($results);
    }
    
    /**
     * Calculate test results
     */
    private function calculate_test_results($test_id) {
        global $wpdb;
        $ab_table = $wpdb->prefix . 'scs_ab_tests';
        $analytics_table = $wpdb->prefix . 'scs_analytics';
        
        // Get test details
        $test = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $ab_table WHERE id = %d
        ", $test_id));
        
        if (!$test) {
            return ['error' => 'Test not found'];
        }
        
        $results = [
            'test' => $test,
            'variant_a' => [],
            'variant_b' => [],
            'winner' => null,
            'confidence' => 0,
            'statistical_significance' => false
        ];
        
        // Get metrics for variant A
        if ($test->post_a_id) {
            $results['variant_a'] = $this->get_post_metrics($test->post_a_id);
        }
        
        // Get metrics for variant B
        if ($test->post_b_id) {
            $results['variant_b'] = $this->get_post_metrics($test->post_b_id);
        }
        
        // Determine winner
        $results['winner'] = $this->determine_winner($results['variant_a'], $results['variant_b']);
        $results['confidence'] = $this->calculate_confidence($results['variant_a'], $results['variant_b']);
        $results['statistical_significance'] = $results['confidence'] >= 95;
        
        return $results;
    }
    
    /**
     * Get metrics for a specific post
     */
    private function get_post_metrics($post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_analytics';
        
        $metrics = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $table_name WHERE post_id = %d
        ", $post_id));
        
        if ($metrics) {
            return [
                'views' => intval($metrics->views),
                'clicks' => intval($metrics->clicks),
                'shares' => intval($metrics->shares),
                'engagement_score' => floatval($metrics->engagement_score),
                'click_through_rate' => $metrics->views > 0 ? round(($metrics->clicks / $metrics->views) * 100, 2) : 0,
                'share_rate' => $metrics->views > 0 ? round(($metrics->shares / $metrics->views) * 100, 2) : 0
            ];
        }
        
        return [
            'views' => 0,
            'clicks' => 0,
            'shares' => 0,
            'engagement_score' => 0,
            'click_through_rate' => 0,
            'share_rate' => 0
        ];
    }
    
    /**
     * Determine the winner between two variants
     */
    private function determine_winner($variant_a, $variant_b) {
        if (empty($variant_a) || empty($variant_b)) {
            return null;
        }
        
        // Compare based on engagement score
        $score_a = $variant_a['engagement_score'];
        $score_b = $variant_b['engagement_score'];
        
        if ($score_a > $score_b) {
            return 'A';
        } elseif ($score_b > $score_a) {
            return 'B';
        }
        
        return 'tie';
    }
    
    /**
     * Calculate statistical confidence
     */
    private function calculate_confidence($variant_a, $variant_b) {
        if (empty($variant_a) || empty($variant_b)) {
            return 0;
        }
        
        // Simple confidence calculation based on sample size and difference
        $views_a = $variant_a['views'];
        $views_b = $variant_b['views'];
        $score_a = $variant_a['engagement_score'];
        $score_b = $variant_b['engagement_score'];
        
        if ($views_a === 0 || $views_b === 0) {
            return 0;
        }
        
        $total_views = $views_a + $views_b;
        $score_diff = abs($score_a - $score_b);
        
        // Simple confidence calculation (not statistically rigorous)
        $confidence = min(95, ($total_views / 100) * ($score_diff / 10) * 10);
        
        return round($confidence, 2);
    }
    
    /**
     * End an A/B test
     */
    public function end_ab_test() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $test_id = intval($_POST['test_id']);
        $winner = sanitize_text_field($_POST['winner']);
        
        if (!$test_id) {
            wp_send_json_error('Invalid test ID');
        }
        
        $result = $this->end_test($test_id, $winner);
        
        wp_send_json_success($result);
    }
    
    /**
     * End a test and apply the winning variant
     */
    private function end_test($test_id, $winner) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_ab_tests';
        
        // Update test status
        $wpdb->update(
            $table_name,
            [
                'status' => 'completed',
                'winner' => $winner,
                'completed_at' => current_time('mysql')
            ],
            ['id' => $test_id],
            ['%s', '%s', '%s'],
            ['%d']
        );
        
        // Get test details
        $test = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $table_name WHERE id = %d
        ", $test_id));
        
        // Apply winning variant (implementation depends on test type)
        $this->apply_winning_variant($test, $winner);
        
        return [
            'test_id' => $test_id,
            'winner' => $winner,
            'message' => 'A/B test completed successfully'
        ];
    }
    
    /**
     * Apply the winning variant
     */
    private function apply_winning_variant($test, $winner) {
        // This would implement the logic to apply the winning variant
        // For example, updating default settings, scheduling similar content, etc.
        
        switch ($test->test_type) {
            case 'title':
                $this->apply_title_insights($test, $winner);
                break;
                
            case 'timing':
                $this->apply_timing_insights($test, $winner);
                break;
                
            case 'platform':
                $this->apply_platform_insights($test, $winner);
                break;
        }
    }
    
    /**
     * Apply title insights from A/B test
     */
    private function apply_title_insights($test, $winner) {
        // Extract successful title patterns and store as recommendations
        $winning_post_id = ($winner === 'A') ? $test->post_a_id : $test->post_b_id;
        $winning_title = get_the_title($winning_post_id);
        
        // Store insights
        $insights = get_option('scs_title_insights', []);
        $insights[] = [
            'pattern' => $this->extract_title_pattern($winning_title),
            'test_id' => $test->id,
            'confidence' => 85, // Could be calculated from actual test results
            'date' => current_time('mysql')
        ];
        
        update_option('scs_title_insights', $insights);
    }
    
    /**
     * Apply timing insights from A/B test
     */
    private function apply_timing_insights($test, $winner) {
        $winning_post_id = ($winner === 'A') ? $test->post_a_id : $test->post_b_id;
        $winning_post = get_post($winning_post_id);
        
        if ($winning_post) {
            $optimal_time = $winning_post->post_date;
            $hour = date('H', strtotime($optimal_time));
            $day = strtolower(date('l', strtotime($optimal_time)));
            
            // Update optimal times
            $current_optimal = json_decode(get_option('scs_optimal_times', '{}'), true);
            if (!isset($current_optimal[$day])) {
                $current_optimal[$day] = [];
            }
            
            if (!in_array($hour . ':00', $current_optimal[$day])) {
                $current_optimal[$day][] = $hour . ':00';
                sort($current_optimal[$day]);
                update_option('scs_optimal_times', json_encode($current_optimal));
            }
        }
    }
    
    /**
     * Apply platform insights from A/B test
     */
    private function apply_platform_insights($test, $winner) {
        // Store successful platform combinations
        $winning_platforms = ($winner === 'A') ? 
            json_decode($test->variant_a, true)['platforms'] : 
            json_decode($test->variant_b, true)['platforms'];
        
        $insights = get_option('scs_platform_insights', []);
        $insights[] = [
            'platforms' => $winning_platforms,
            'test_id' => $test->id,
            'confidence' => 85,
            'date' => current_time('mysql')
        ];
        
        update_option('scs_platform_insights', $insights);
    }
    
    /**
     * Get list of A/B tests
     */
    public function get_ab_test_list() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_ab_tests';
        
        $tests = $wpdb->get_results("
            SELECT * FROM $table_name 
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        
        wp_send_json_success($tests);
    }
    
    /**
     * Update A/B test metrics
     */
    public function update_ab_test_metrics() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_ab_tests';
        
        // Get active tests
        $active_tests = $wpdb->get_results("
            SELECT * FROM $table_name 
            WHERE status = 'active' 
            AND end_date > NOW()
        ");
        
        foreach ($active_tests as $test) {
            // Update metrics for each test
            $this->update_single_test_metrics($test);
        }
    }
    
    /**
     * Update metrics for a single test
     */
    private function update_single_test_metrics($test) {
        // This would update real-time metrics
        // Implementation depends on specific tracking requirements
    }
    
    /**
     * Check for completed tests
     */
    public function check_completed_tests() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_ab_tests';
        
        // Get tests that should be completed
        $completed_tests = $wpdb->get_results("
            SELECT * FROM $table_name 
            WHERE status = 'active' 
            AND end_date <= NOW()
        ");
        
        foreach ($completed_tests as $test) {
            // Auto-determine winner and end test
            $results = $this->calculate_test_results($test->id);
            $winner = $results['winner'] ?? 'tie';
            
            $this->end_test($test->id, $winner);
        }
    }
    
    /**
     * Helper functions
     */
    private function sanitize_variant($variant) {
        if (!is_array($variant)) {
            return [];
        }
        
        $sanitized = [];
        
        if (isset($variant['title'])) {
            $sanitized['title'] = sanitize_text_field($variant['title']);
        }
        
        if (isset($variant['content'])) {
            $sanitized['content'] = wp_kses_post($variant['content']);
        }
        
        if (isset($variant['scheduled_time'])) {
            $sanitized['scheduled_time'] = sanitize_text_field($variant['scheduled_time']);
        }
        
        if (isset($variant['platforms'])) {
            $sanitized['platforms'] = array_map('sanitize_text_field', $variant['platforms']);
        }
        
        return $sanitized;
    }
    
    private function extract_title_pattern($title) {
        // Simple pattern extraction - could be more sophisticated
        $patterns = [];
        
        if (strpos($title, '?') !== false) {
            $patterns[] = 'question';
        }
        
        if (preg_match('/\d+/', $title)) {
            $patterns[] = 'contains_numbers';
        }
        
        if (strlen($title) > 50) {
            $patterns[] = 'long_title';
        } else {
            $patterns[] = 'short_title';
        }
        
        return $patterns;
    }
}
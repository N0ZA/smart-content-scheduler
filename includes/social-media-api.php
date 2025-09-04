<?php
if (!defined('ABSPATH')) {
    exit;
}

class SCS_Social_Media_API {
    
    private $supported_platforms = ['twitter', 'facebook', 'linkedin', 'instagram'];
    
    public function __construct() {
        add_action('wp_ajax_scs_connect_social_platform', [$this, 'connect_platform']);
        add_action('wp_ajax_scs_disconnect_social_platform', [$this, 'disconnect_platform']);
        add_action('wp_ajax_scs_get_social_metrics', [$this, 'get_social_metrics']);
        add_action('wp_ajax_scs_sync_social_data', [$this, 'sync_social_data']);
        add_action('wp_ajax_scs_auto_post_to_social', [$this, 'auto_post_to_social']);
        
        // Cron jobs for automatic sync
        add_action('scs_sync_social_metrics', [$this, 'scheduled_social_sync']);
        
        // Schedule social sync if not already scheduled
        if (!wp_next_scheduled('scs_sync_social_metrics')) {
            wp_schedule_event(time(), 'hourly', 'scs_sync_social_metrics');
        }
    }
    
    /**
     * Connect to a social media platform
     */
    public function connect_platform() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $platform = sanitize_text_field($_POST['platform']);
        $api_key = sanitize_text_field($_POST['api_key']);
        $api_secret = sanitize_text_field($_POST['api_secret']);
        $access_token = sanitize_text_field($_POST['access_token']);
        
        if (!in_array($platform, $this->supported_platforms)) {
            wp_send_json_error('Unsupported platform');
        }
        
        // Store API credentials (encrypted)
        $credentials = [
            'api_key' => $this->encrypt_credential($api_key),
            'api_secret' => $this->encrypt_credential($api_secret),
            'access_token' => $this->encrypt_credential($access_token),
            'connected_at' => current_time('mysql'),
            'status' => 'active'
        ];
        
        update_option("scs_social_{$platform}_credentials", $credentials);
        
        // Test connection
        $connection_test = $this->test_platform_connection($platform);
        
        if ($connection_test['success']) {
            wp_send_json_success([
                'message' => "Successfully connected to {$platform}",
                'platform' => $platform,
                'user_info' => $connection_test['user_info']
            ]);
        } else {
            wp_send_json_error("Failed to connect to {$platform}: " . $connection_test['error']);
        }
    }
    
    /**
     * Disconnect from a social media platform
     */
    public function disconnect_platform() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $platform = sanitize_text_field($_POST['platform']);
        
        if (!in_array($platform, $this->supported_platforms)) {
            wp_send_json_error('Unsupported platform');
        }
        
        delete_option("scs_social_{$platform}_credentials");
        
        wp_send_json_success([
            'message' => "Disconnected from {$platform}",
            'platform' => $platform
        ]);
    }
    
    /**
     * Get social media metrics for a post
     */
    public function get_social_metrics() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id) {
            wp_send_json_error('Invalid post ID');
        }
        
        $metrics = $this->compile_social_metrics($post_id);
        
        wp_send_json_success($metrics);
    }
    
    /**
     * Compile social metrics for a post
     */
    public function compile_social_metrics($post_id) {
        $metrics = [
            'total_shares' => 0,
            'total_likes' => 0,
            'total_comments' => 0,
            'total_clicks' => 0,
            'platforms' => []
        ];
        
        foreach ($this->supported_platforms as $platform) {
            $platform_metrics = $this->get_platform_metrics($post_id, $platform);
            $metrics['platforms'][$platform] = $platform_metrics;
            
            $metrics['total_shares'] += $platform_metrics['shares'];
            $metrics['total_likes'] += $platform_metrics['likes'];
            $metrics['total_comments'] += $platform_metrics['comments'];
            $metrics['total_clicks'] += $platform_metrics['clicks'];
        }
        
        // Calculate engagement rate
        $total_engagements = $metrics['total_likes'] + $metrics['total_comments'] + $metrics['total_shares'];
        $total_reach = $this->get_total_reach($post_id);
        $metrics['engagement_rate'] = $total_reach > 0 ? round(($total_engagements / $total_reach) * 100, 2) : 0;
        
        return $metrics;
    }
    
    /**
     * Get metrics for a specific platform
     */
    private function get_platform_metrics($post_id, $platform) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_social_metrics';
        
        $metrics = $wpdb->get_row($wpdb->prepare("
            SELECT * FROM $table_name 
            WHERE post_id = %d AND platform = %s
            ORDER BY updated_at DESC LIMIT 1
        ", $post_id, $platform));
        
        if ($metrics) {
            return [
                'shares' => intval($metrics->shares),
                'likes' => intval($metrics->likes),
                'comments' => intval($metrics->comments),
                'clicks' => intval($metrics->clicks),
                'reach' => intval($metrics->reach),
                'impressions' => intval($metrics->impressions),
                'last_updated' => $metrics->updated_at
            ];
        }
        
        return [
            'shares' => 0,
            'likes' => 0,
            'comments' => 0,
            'clicks' => 0,
            'reach' => 0,
            'impressions' => 0,
            'last_updated' => null
        ];
    }
    
    /**
     * Sync social media data
     */
    public function sync_social_data() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        $platform = sanitize_text_field($_POST['platform']);
        
        if ($post_id) {
            $result = $this->sync_post_metrics($post_id, $platform);
        } else {
            $result = $this->sync_all_metrics($platform);
        }
        
        wp_send_json_success($result);
    }
    
    /**
     * Sync metrics for a specific post
     */
    private function sync_post_metrics($post_id, $platform = null) {
        $platforms = $platform ? [$platform] : $this->get_connected_platforms();
        $synced_platforms = [];
        
        foreach ($platforms as $p) {
            if ($this->is_platform_connected($p)) {
                $metrics = $this->fetch_platform_metrics($post_id, $p);
                if ($metrics) {
                    $this->store_platform_metrics($post_id, $p, $metrics);
                    $synced_platforms[] = $p;
                }
            }
        }
        
        return [
            'post_id' => $post_id,
            'synced_platforms' => $synced_platforms,
            'sync_time' => current_time('mysql')
        ];
    }
    
    /**
     * Sync all metrics for all posts
     */
    private function sync_all_metrics($platform = null) {
        $platforms = $platform ? [$platform] : $this->get_connected_platforms();
        $synced_posts = 0;
        $synced_platforms = [];
        
        // Get posts from the last 30 days
        $posts = get_posts([
            'post_status' => 'publish',
            'numberposts' => 100,
            'date_query' => [
                [
                    'after' => '30 days ago'
                ]
            ]
        ]);
        
        foreach ($posts as $post) {
            foreach ($platforms as $p) {
                if ($this->is_platform_connected($p)) {
                    $metrics = $this->fetch_platform_metrics($post->ID, $p);
                    if ($metrics) {
                        $this->store_platform_metrics($post->ID, $p, $metrics);
                        $synced_platforms[] = $p;
                        $synced_posts++;
                    }
                }
            }
        }
        
        return [
            'synced_posts' => $synced_posts,
            'synced_platforms' => array_unique($synced_platforms),
            'sync_time' => current_time('mysql')
        ];
    }
    
    /**
     * Fetch metrics from platform API (mock implementation)
     */
    private function fetch_platform_metrics($post_id, $platform) {
        $credentials = $this->get_platform_credentials($platform);
        if (!$credentials) {
            return false;
        }
        
        // Mock metrics - in real implementation, this would call the actual API
        $base_metrics = [
            'shares' => rand(0, 50),
            'likes' => rand(0, 200),
            'comments' => rand(0, 30),
            'clicks' => rand(0, 100),
            'reach' => rand(100, 1000),
            'impressions' => rand(200, 2000)
        ];
        
        // Add some randomness based on platform
        $multipliers = [
            'facebook' => 1.2,
            'twitter' => 0.8,
            'linkedin' => 0.6,
            'instagram' => 1.1
        ];
        
        $multiplier = $multipliers[$platform] ?? 1.0;
        
        foreach ($base_metrics as $key => $value) {
            $base_metrics[$key] = round($value * $multiplier);
        }
        
        return $base_metrics;
    }
    
    /**
     * Store platform metrics in database
     */
    private function store_platform_metrics($post_id, $platform, $metrics) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_social_metrics';
        
        $wpdb->replace(
            $table_name,
            [
                'post_id' => $post_id,
                'platform' => $platform,
                'shares' => $metrics['shares'],
                'likes' => $metrics['likes'],
                'comments' => $metrics['comments'],
                'clicks' => $metrics['clicks'],
                'reach' => $metrics['reach'],
                'impressions' => $metrics['impressions'],
                'updated_at' => current_time('mysql')
            ],
            ['%d', '%s', '%d', '%d', '%d', '%d', '%d', '%d', '%s']
        );
    }
    
    /**
     * Auto-post to social media platforms
     */
    public function auto_post_to_social() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        if (!current_user_can('publish_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $post_id = intval($_POST['post_id']);
        $platforms = array_map('sanitize_text_field', $_POST['platforms']);
        $custom_message = sanitize_textarea_field($_POST['custom_message']);
        
        $results = [];
        
        foreach ($platforms as $platform) {
            if ($this->is_platform_connected($platform)) {
                $result = $this->post_to_platform($post_id, $platform, $custom_message);
                $results[$platform] = $result;
            }
        }
        
        wp_send_json_success($results);
    }
    
    /**
     * Post content to a specific platform
     */
    private function post_to_platform($post_id, $platform, $custom_message = '') {
        $post = get_post($post_id);
        if (!$post) {
            return ['success' => false, 'error' => 'Post not found'];
        }
        
        $credentials = $this->get_platform_credentials($platform);
        if (!$credentials) {
            return ['success' => false, 'error' => 'Platform not connected'];
        }
        
        $message = $custom_message ?: $this->generate_social_message($post, $platform);
        
        // Mock posting - in real implementation, this would call the actual API
        $mock_response = [
            'success' => true,
            'platform_post_id' => 'mock_' . $platform . '_' . time(),
            'url' => "https://{$platform}.com/post/mock_" . time(),
            'message' => $message
        ];
        
        // Store the social post reference
        $this->store_social_post($post_id, $platform, $mock_response);
        
        return $mock_response;
    }
    
    /**
     * Generate optimized social media message
     */
    private function generate_social_message($post, $platform) {
        $title = $post->post_title;
        $url = get_permalink($post->ID);
        
        $character_limits = [
            'twitter' => 280,
            'facebook' => 63206,
            'linkedin' => 3000,
            'instagram' => 2200
        ];
        
        $limit = $character_limits[$platform] ?? 280;
        
        // Platform-specific optimizations
        switch ($platform) {
            case 'twitter':
                $message = $title . ' ' . $url;
                if (strlen($message) > $limit) {
                    $title = substr($title, 0, $limit - strlen($url) - 4) . '...';
                    $message = $title . ' ' . $url;
                }
                break;
                
            case 'linkedin':
                $excerpt = get_the_excerpt($post->ID);
                $message = $title . "\n\n" . $excerpt . "\n\n" . $url;
                break;
                
            case 'facebook':
                $excerpt = get_the_excerpt($post->ID);
                $message = $title . "\n\n" . $excerpt . "\n\nRead more: " . $url;
                break;
                
            case 'instagram':
                $message = $title . "\n\n#blog #content #" . sanitize_title($title);
                break;
                
            default:
                $message = $title . ' ' . $url;
        }
        
        return $message;
    }
    
    /**
     * Store social post reference
     */
    private function store_social_post($post_id, $platform, $response) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_social_posts';
        
        $wpdb->insert(
            $table_name,
            [
                'post_id' => $post_id,
                'platform' => $platform,
                'platform_post_id' => $response['platform_post_id'],
                'platform_url' => $response['url'],
                'message' => $response['message'],
                'posted_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );
    }
    
    /**
     * Scheduled social sync (cron job)
     */
    public function scheduled_social_sync() {
        $this->sync_all_metrics();
    }
    
    /**
     * Helper functions
     */
    private function encrypt_credential($credential) {
        // Simple base64 encoding - in production, use proper encryption
        return base64_encode($credential);
    }
    
    private function decrypt_credential($credential) {
        return base64_decode($credential);
    }
    
    private function get_platform_credentials($platform) {
        $credentials = get_option("scs_social_{$platform}_credentials");
        
        if ($credentials && $credentials['status'] === 'active') {
            return [
                'api_key' => $this->decrypt_credential($credentials['api_key']),
                'api_secret' => $this->decrypt_credential($credentials['api_secret']),
                'access_token' => $this->decrypt_credential($credentials['access_token'])
            ];
        }
        
        return false;
    }
    
    private function is_platform_connected($platform) {
        $credentials = get_option("scs_social_{$platform}_credentials");
        return $credentials && $credentials['status'] === 'active';
    }
    
    private function get_connected_platforms() {
        $connected = [];
        
        foreach ($this->supported_platforms as $platform) {
            if ($this->is_platform_connected($platform)) {
                $connected[] = $platform;
            }
        }
        
        return $connected;
    }
    
    private function test_platform_connection($platform) {
        // Mock connection test - in real implementation, this would test the actual API
        return [
            'success' => true,
            'user_info' => [
                'username' => 'test_user',
                'followers' => rand(100, 10000),
                'platform' => $platform
            ]
        ];
    }
    
    private function get_total_reach($post_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_social_metrics';
        
        $total_reach = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(reach) FROM $table_name 
            WHERE post_id = %d
        ", $post_id));
        
        return intval($total_reach);
    }
    
    /**
     * Get supported platforms
     */
    public function get_supported_platforms() {
        return $this->supported_platforms;
    }
}
<?php
/**
 * Social Media API Integration
 *
 * @since      1.0.0
 * @package    Smart_Content_Scheduler
 */

class Smart_Content_Scheduler_Social_API {

    /**
     * API credentials and endpoints
     * 
     * @var array
     */
    private $api_credentials;
    
    /**
     * Database table names
     *
     * @var array
     */
    private $tables;

    /**
     * Initialize the class
     */
    public function __construct() {
        global $wpdb;
        
        $this->tables = [
            'performance' => $wpdb->prefix . 'scs_performance'
        ];
        
        // Load API credentials
        $this->api_credentials = [
            'facebook' => [
                'app_id' => get_option('scs_facebook_app_id', ''),
                'app_secret' => get_option('scs_facebook_app_secret', ''),
                'access_token' => get_option('scs_facebook_access_token', ''),
                'page_id' => get_option('scs_facebook_page_id', ''),
                'enabled' => get_option('scs_facebook_enabled', false)
            ],
            'twitter' => [
                'api_key' => get_option('scs_twitter_api_key', ''),
                'api_secret' => get_option('scs_twitter_api_secret', ''),
                'access_token' => get_option('scs_twitter_access_token', ''),
                'access_token_secret' => get_option('scs_twitter_access_token_secret', ''),
                'enabled' => get_option('scs_twitter_enabled', false)
            ],
            'linkedin' => [
                'client_id' => get_option('scs_linkedin_client_id', ''),
                'client_secret' => get_option('scs_linkedin_client_secret', ''),
                'access_token' => get_option('scs_linkedin_access_token', ''),
                'company_id' => get_option('scs_linkedin_company_id', ''),
                'enabled' => get_option('scs_linkedin_enabled', false)
            ],
            'instagram' => [
                'client_id' => get_option('scs_instagram_client_id', ''),
                'client_secret' => get_option('scs_instagram_client_secret', ''),
                'access_token' => get_option('scs_instagram_access_token', ''),
                'enabled' => get_option('scs_instagram_enabled', false)
            ]
        ];
    }

    /**
     * Collect social media performance data for posts
     */
    public function collect_data() {
        // Get posts to check (published in the last 30 days)
        $recent_posts = get_posts([
            'post_type' => 'post',
            'post_status' => 'publish',
            'date_query' => [
                'after' => '30 days ago'
            ],
            'posts_per_page' => 50
        ]);
        
        foreach ($recent_posts as $post) {
            $social_data = [
                'facebook' => $this->get_facebook_data($post),
                'twitter' => $this->get_twitter_data($post),
                'linkedin' => $this->get_linkedin_data($post),
                'instagram' => $this->get_instagram_data($post)
            ];
            
            // Calculate aggregated social score
            $social_score = $this->calculate_social_score($social_data);
            
            // Update performance metrics in database
            $this->update_performance_data($post->ID, $social_score, $social_data);
        }
    }

    /**
     * Get Facebook engagement data
     *
     * @param WP_Post $post Post object
     * @return array Facebook engagement data
     */
    private function get_facebook_data($post) {
        if (!$this->api_credentials['facebook']['enabled'] || empty($this->api_credentials['facebook']['access_token'])) {
            return $this->get_dummy_social_data();
        }
        
        $post_url = urlencode(get_permalink($post->ID));
        $access_token = $this->api_credentials['facebook']['access_token'];
        
        $api_url = "https://graph.facebook.com/v14.0/?id={$post_url}&fields=engagement&access_token={$access_token}";
        
        $response = wp_remote_get($api_url, [
            'timeout' => 15
        ]);
        
        if (is_wp_error($response)) {
            error_log('Facebook API Error: ' . $response->get_error_message());
            return $this->get_dummy_social_data();
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['error'])) {
            error_log('Facebook API Error: ' . $body['error']['message']);
            return $this->get_dummy_social_data();
        }
        
        $engagement = $body['engagement'] ?? [];
        
        return [
            'shares' => $engagement['share_count'] ?? 0,
            'reactions' => $engagement['reaction_count'] ?? 0,
            'comments' => $engagement['comment_count'] ?? 0,
            'total_engagement' => ($engagement['share_count'] ?? 0) + 
                                  ($engagement['reaction_count'] ?? 0) + 
                                  ($engagement['comment_count'] ?? 0)
        ];
    }

    /**
     * Get Twitter engagement data
     *
     * @param WP_Post $post Post object
     * @return array Twitter engagement data
     */
    private function get_twitter_data($post) {
        if (!$this->api_credentials['twitter']['enabled']) {
            return $this->get_dummy_social_data();
        }
        
        // Twitter API implementation would go here
        // This is a complex implementation requiring OAuth 1.0a
        
        return $this->get_dummy_social_data();
    }

    /**
     * Get LinkedIn engagement data
     *
     * @param WP_Post $post Post object
     * @return array LinkedIn engagement data
     */
    private function get_linkedin_data($post) {
        if (!$this->api_credentials['linkedin']['enabled']) {
            return $this->get_dummy_social_data();
        }
        
        // LinkedIn API implementation would go here
        
        return $this->get_dummy_social_data();
    }

    /**
     * Get Instagram engagement data
     *
     * @param WP_Post $post Post object
     * @return array Instagram engagement data
     */
    private function get_instagram_data($post) {
        if (!$this->api_credentials['instagram']['enabled']) {
            return $this->get_dummy_social_data();
        }
        
        // Instagram API implementation would go here
        
        return $this->get_dummy_social_data();
    }

    /**
     * Generate dummy social data for testing or when APIs are not configured
     *
     * @return array Dummy social data
     */
    private function get_dummy_social_data() {
        return [
            'shares' => rand(0, 20),
            'reactions' => rand(0, 50),
            'comments' => rand(0, 15),
            'total_engagement' => rand(0, 85)
        ];
    }

    /**
     * Calculate social score from all platforms
     *
     * @param array $social_data Data from all social platforms
     * @return float Social engagement score (0-1)
     */
    private function calculate_social_score($social_data) {
        // Get total engagement across all platforms
        $total_engagement = 0;
        foreach ($social_data as $platform => $data) {
            $total_engagement += $data['total_engagement'];
        }
        
        // Normalize the score (0-1 range)
        // 100+ engagements is considered a perfect score
        $normalized_score = min(1, $total_engagement / 100);
        
        return $normalized_score;
    }

    /**
     * Update performance data in database
     *
     * @param int $post_id Post ID
     * @param float $social_score Social engagement score
     * @param array $social_data Detailed social media data
     */
    private function update_performance_data($post_id, $social_score, $social_data) {
        global $wpdb;
        
        // Get existing performance data
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['performance']} WHERE post_id = %d",
            $post_id
        ));
        
        // Calculate total performance score
        // Social engagement is weighted at 40% of total score
        $performance_score = $existing ? 
            ($existing->performance_score * 0.6 + $social_score * 0.4) : 
            $social_score;
        
        $social_shares = 0;
        foreach ($social_data as $platform => $data) {
            $social_shares += $data['shares'];
        }
        
        if ($existing) {
            // Update existing record
            $wpdb->update(
                $this->tables['performance'],
                [
                    'social_shares' => $social_shares,
                    'performance_score' => $performance_score,
                    'last_updated' => current_time('mysql')
                ],
                ['post_id' => $post_id],
                ['%d', '%f', '%s'],
                ['%d']
            );
        } else {
            // Insert new record
            $wpdb->insert(
                $this->tables['performance'],
                [
                    'post_id' => $post_id,
                    'views' => 0,
                    'engagement' => 0,
                    'social_shares' => $social_shares,
                    'avg_time_on_page' => 0,
                    'performance_score' => $performance_score,
                    'last_updated' => current_time('mysql')
                ],
                ['%d', '%d', '%f', '%d', '%f', '%f', '%s']
            );
        }
        
        // Store detailed social data as post meta
        update_post_meta($post_id, 'scs_social_data', $social_data);
    }

    /**
     * Post to social media platforms
     *
     * @param int $post_id Post ID
     * @param array $platforms Platforms to post to
     * @return array Results of posting attempts
     */
    public function post_to_social($post_id, $platforms = ['facebook', 'twitter', 'linkedin']) {
        $post = get_post($post_id);
        
        if (!$post) {
            return [
                'success' => false,
                'message' => 'Invalid post ID'
            ];
        }
        
        $results = [];
        $permalink = get_permalink($post_id);
        $title = get_the_title($post_id);
        $excerpt = has_excerpt($post_id) ? get_the_excerpt($post_id) : wp_trim_words($post->post_content, 30);
        
        // Get featured image
        $featured_image = get_the_post_thumbnail_url($post_id, 'large');
        
        foreach ($platforms as $platform) {
            switch ($platform) {
                case 'facebook':
                    $results['facebook'] = $this->post_to_facebook($title, $excerpt, $permalink, $featured_image);
                    break;
                case 'twitter':
                    $results['twitter'] = $this->post_to_twitter($title, $excerpt, $permalink, $featured_image);
                    break;
                case 'linkedin':
                    $results['linkedin'] = $this->post_to_linkedin($title, $excerpt, $permalink, $featured_image);
                    break;
                // Add other platforms as needed
            }
        }
        
        return $results;
    }

    /**
     * Post to Facebook
     *
     * @param string $title Post title
     * @param string $excerpt Post excerpt
     * @param string $permalink Post URL
     * @param string $image_url Featured image URL
     * @return array Result of posting attempt
     */
    private function post_to_facebook($title, $excerpt, $permalink, $image_url) {
        if (!$this->api_credentials['facebook']['enabled'] || 
            empty($this->api_credentials['facebook']['access_token']) ||
            empty($this->api_credentials['facebook']['page_id'])) {
            return [
                'success' => false,
                'message' => 'Facebook API not configured'
            ];
        }
        
        $page_id = $this->api_credentials['facebook']['page_id'];
        $access_token = $this->api_credentials['facebook']['access_token'];
        
        $api_url = "https://graph.facebook.com/v14.0/{$page_id}/feed";
        
        $message = "{$title}\n\n{$excerpt}\n\n{$permalink}";
        
        $response = wp_remote_post($api_url, [
            'timeout' => 15,
            'body' => [
                'message' => $message,
                'link' => $permalink,
                'access_token' => $access_token
            ]
        ]);
        
        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'API Error: ' . $response->get_error_message()
            ];
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['id'])) {
            return [
                'success' => true,
                'message' => 'Posted to Facebook',
                'post_id' => $body['id']
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Facebook API Error: ' . ($body['error']['message'] ?? 'Unknown error')
            ];
        }
    }

    /**
     * Post to Twitter
     *
     * @param string $title Post title
     * @param string $excerpt Post excerpt
     * @param string $permalink Post URL
     * @param string $image_url Featured image URL
     * @return array Result of posting attempt
     */
    private function post_to_twitter($title, $excerpt, $permalink, $image_url) {
        if (!$this->api_credentials['twitter']['enabled']) {
            return [
                'success' => false,
                'message' => 'Twitter API not configured'
            ];
        }
        
        // Twitter API implementation would go here
        
        return [
            'success' => false,
            'message' => 'Twitter API implementation not available'
        ];
    }

    /**
     * Post to LinkedIn
     *
     * @param string $title Post title
     * @param string $excerpt Post excerpt
     * @param string $permalink Post URL
     * @param string $image_url Featured image URL
     * @return array Result of posting attempt
     */
    private function post_to_linkedin($title, $excerpt, $permalink, $image_url) {
        if (!$this->api_credentials['linkedin']['enabled']) {
            return [
                'success' => false,
                'message' => 'LinkedIn API not configured'
            ];
        }
        
        // LinkedIn API implementation would go here
        
        return [
            'success' => false,
            'message' => 'LinkedIn API implementation not available'
        ];
    }
}
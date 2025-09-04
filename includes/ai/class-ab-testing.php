<?php
/**
 * A/B Testing Automation
 *
 * @package Smart_Content_Scheduler
 * @subpackage AI_Features
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * A/B Testing Automation
 * Manages A/B testing for content optimization
 */
class SCS_AB_Testing {
    
    /**
     * Initialize the A/B testing system
     */
    public function __construct() {
        add_action('init', array($this, 'register_post_meta'));
        add_action('scs_create_ab_test', array($this, 'create_ab_test'), 10, 3);
        add_action('scs_analyze_ab_test', array($this, 'analyze_ab_test'), 10, 1);
        add_action('scs_select_winning_variant', array($this, 'select_winning_variant'), 10, 1);
    }
    
    /**
     * Register post meta for A/B testing
     */
    public function register_post_meta() {
        register_post_meta('post', 'scs_ab_test_id', array(
            'type' => 'string',
            'description' => 'A/B Test ID',
            'single' => true,
            'show_in_rest' => true,
        ));
        
        register_post_meta('post', 'scs_ab_test_variant', array(
            'type' => 'string',
            'description' => 'A/B Test Variant',
            'single' => true,
            'show_in_rest' => true,
        ));
    }
    
    /**
     * Create an A/B test
     * 
     * @param int $original_post_id The original post ID
     * @param array $variants The variant configurations
     * @param array $test_config Test configuration parameters
     * @return array Test information
     */
    public function create_ab_test($original_post_id, $variants, $test_config) {
        $original_post = get_post($original_post_id);
        
        if (!$original_post) {
            return array(
                'success' => false,
                'message' => 'Original post not found',
            );
        }
        
        // Generate a unique test ID
        $test_id = 'abtest_' . uniqid();
        
        // Set test metadata on original post
        update_post_meta($original_post_id, 'scs_ab_test_id', $test_id);
        update_post_meta($original_post_id, 'scs_ab_test_variant', 'original');
        
        // Create variant posts
        $variant_ids = array();
        foreach ($variants as $variant_key => $variant_config) {
            $variant_id = $this->create_variant($original_post, $variant_key, $variant_config);
            
            if ($variant_id) {
                $variant_ids[$variant_key] = $variant_id;
                
                // Set test metadata on variant post
                update_post_meta($variant_id, 'scs_ab_test_id', $test_id);
                update_post_meta($variant_id, 'scs_ab_test_variant', $variant_key);
            }
        }
        
        // Store test configuration
        $test_data = array(
            'id' => $test_id,
            'original_post_id' => $original_post_id,
            'variant_ids' => $variant_ids,
            'config' => $test_config,
            'created_at' => current_time('mysql'),
            'status' => 'running',
        );
        
        update_option('scs_ab_test_' . $test_id, $test_data);
        
        return array(
            'success' => true,
            'test_id' => $test_id,
            'original_post_id' => $original_post_id,
            'variant_ids' => $variant_ids,
        );
    }
    
    /**
     * Analyze an A/B test
     * 
     * @param string $test_id The test ID to analyze
     * @return array Analysis results
     */
    public function analyze_ab_test($test_id) {
        $test_data = get_option('scs_ab_test_' . $test_id);
        
        if (!$test_data) {
            return array(
                'success' => false,
                'message' => 'Test not found',
            );
        }
        
        $original_post_id = $test_data['original_post_id'];
        $variant_ids = $test_data['variant_ids'];
        
        // Get performance metrics for original post
        $original_metrics = $this->get_post_metrics($original_post_id);
        
        // Get performance metrics for variants
        $variant_metrics = array();
        foreach ($variant_ids as $variant_key => $variant_id) {
            $variant_metrics[$variant_key] = $this->get_post_metrics($variant_id);
        }
        
        // Determine statistical significance
        $significance = $this->calculate_statistical_significance($original_metrics, $variant_metrics);
        
        // Identify winning variant
        $winner = $this->identify_winner($original_metrics, $variant_metrics, $significance);
        
        // Update test data with analysis results
        $test_data['analysis'] = array(
            'timestamp' => current_time('mysql'),
            'original_metrics' => $original_metrics,
            'variant_metrics' => $variant_metrics,
            'significance' => $significance,
            'winner' => $winner,
        );
        
        update_option('scs_ab_test_' . $test_id, $test_data);
        
        return array(
            'success' => true,
            'test_id' => $test_id,
            'original_metrics' => $original_metrics,
            'variant_metrics' => $variant_metrics,
            'significance' => $significance,
            'winner' => $winner,
        );
    }
    
    /**
     * Select winning variant
     * 
     * @param string $test_id The test ID
     * @return array Result of the operation
     */
    public function select_winning_variant($test_id) {
        $test_data = get_option('scs_ab_test_' . $test_id);
        
        if (!$test_data) {
            return array(
                'success' => false,
                'message' => 'Test not found',
            );
        }
        
        if (!isset($test_data['analysis']) || !isset($test_data['analysis']['winner'])) {
            return array(
                'success' => false,
                'message' => 'Test analysis not found',
            );
        }
        
        $winner = $test_data['analysis']['winner'];
        
        if ($winner === 'original') {
            // Original post won, update test status
            $test_data['status'] = 'completed';
            update_option('scs_ab_test_' . $test_id, $test_data);
            
            // Archive variants
            foreach ($test_data['variant_ids'] as $variant_id) {
                wp_update_post(array(
                    'ID' => $variant_id,
                    'post_status' => 'draft',
                ));
            }
            
            return array(
                'success' => true,
                'message' => 'Original post selected as winner',
                'winner' => 'original',
                'winner_id' => $test_data['original_post_id'],
            );
        } else {
            // Variant won, update original post with variant content
            $winner_id = $test_data['variant_ids'][$winner];
            $winner_post = get_post($winner_id);
            
            if ($winner_post) {
                // Update original post with winning content
                wp_update_post(array(
                    'ID' => $test_data['original_post_id'],
                    'post_title' => $winner_post->post_title,
                    'post_content' => $winner_post->post_content,
                    'post_excerpt' => $winner_post->post_excerpt,
                ));
                
                // Copy meta data
                $meta_keys = get_post_custom_keys($winner_id);
                if (is_array($meta_keys)) {
                    foreach ($meta_keys as $meta_key) {
                        if ($meta_key !== 'scs_ab_test_variant') {
                            $meta_values = get_post_meta($winner_id, $meta_key, false);
                            
                            foreach ($meta_values as $meta_value) {
                                update_post_meta($test_data['original_post_id'], $meta_key, $meta_value);
                            }
                        }
                    }
                }
                
                // Archive variants
                foreach ($test_data['variant_ids'] as $variant_id) {
                    wp_update_post(array(
                        'ID' => $variant_id,
                        'post_status' => 'draft',
                    ));
                }
                
                // Update test status
                $test_data['status'] = 'completed';
                update_option('scs_ab_test_' . $test_id, $test_data);
                
                return array(
                    'success' => true,
                    'message' => 'Variant "' . $winner . '" selected as winner',
                    'winner' => $winner,
                    'winner_id' => $winner_id,
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Winning variant post not found',
                );
            }
        }
    }
    
    /**
     * Create a variant post
     * 
     * @param WP_Post $original_post The original post
     * @param string $variant_key The variant key
     * @param array $variant_config The variant configuration
     * @return int|false The variant post ID or false on failure
     */
    private function create_variant($original_post, $variant_key, $variant_config) {
        // Create a new post as a copy of the original
        $variant_post_data = array(
            'post_title' => isset($variant_config['title']) ? $variant_config['title'] : $original_post->post_title,
            'post_content' => isset($variant_config['content']) ? $variant_config['content'] : $original_post->post_content,
            'post_excerpt' => isset($variant_config['excerpt']) ? $variant_config['excerpt'] : $original_post->post_excerpt,
            'post_status' => 'publish',
            'post_author' => $original_post->post_author,
            'post_type' => $original_post->post_type,
            'post_category' => wp_get_post_categories($original_post->ID),
            'tags_input' => wp_get_post_tags($original_post->ID, array('fields' => 'names')),
        );
        
        $variant_id = wp_insert_post($variant_post_data);
        
        if (is_wp_error($variant_id)) {
            return false;
        }
        
        return $variant_id;
    }
    
    /**
     * Get post performance metrics
     * 
     * @param int $post_id The post ID
     * @return array Performance metrics
     */
    private function get_post_metrics($post_id) {
        // This would integrate with analytics APIs
        // Placeholder implementation
        return array(
            'views' => rand(100, 1000),
            'unique_visitors' => rand(80, 800),
            'average_time_on_page' => rand(30, 300),
            'bounce_rate' => rand(30, 80),
            'conversion_rate' => rand(1, 10) / 100,
            'social_shares' => rand(5, 50),
        );
    }
    
    /**
     * Calculate statistical significance
     * 
     * @param array $original_metrics Original post metrics
     * @param array $variant_metrics Variant metrics
     * @return array Statistical significance results
     */
    private function calculate_statistical_significance($original_metrics, $variant_metrics) {
        // This would implement statistical tests
        // Placeholder implementation
        $significance = array();
        
        foreach ($variant_metrics as $variant_key => $metrics) {
            $significance[$variant_key] = array(
                'is_significant' => (bool) rand(0, 1),
                'confidence_level' => rand(75, 99),
                'p_value' => rand(1, 50) / 1000,
            );
        }
        
        return $significance;
    }
    
    /**
     * Identify the winning variant
     * 
     * @param array $original_metrics Original post metrics
     * @param array $variant_metrics Variant metrics
     * @param array $significance Statistical significance results
     * @return string Winner key or 'inconclusive'
     */
    private function identify_winner($original_metrics, $variant_metrics, $significance) {
        $best_conversion = $original_metrics['conversion_rate'];
        $winner = 'original';
        $has_significant_winner = false;
        
        foreach ($variant_metrics as $variant_key => $metrics) {
            if ($significance[$variant_key]['is_significant'] && $metrics['conversion_rate'] > $best_conversion) {
                $best_conversion = $metrics['conversion_rate'];
                $winner = $variant_key;
                $has_significant_winner = true;
            }
        }
        
        if (!$has_significant_winner) {
            return 'inconclusive';
        }
        
        return $winner;
    }
}
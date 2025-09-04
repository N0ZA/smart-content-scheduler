<?php
/**
 * Competitor Analysis Integration
 *
 * @package Smart_Content_Scheduler
 * @subpackage AI_Features
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Competitor Analysis Integration
 * Analyzes competitor content and strategies for optimization
 */
class SCS_Competitor_Analysis {
    
    /**
     * Competitor sites
     */
    private $competitor_sites = array();
    
    /**
     * Initialize the competitor analysis system
     */
    public function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('scs_weekly_competitor_scan', array($this, 'scan_competitor_content'));
        add_filter('scs_content_recommendations', array($this, 'add_competitor_insights'), 10, 2);
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'scs-settings',
            'scs_competitor_sites',
            array(
                'type' => 'array',
                'description' => 'Competitor sites to monitor',
                'sanitize_callback' => array($this, 'sanitize_competitor_sites'),
            )
        );
        
        add_settings_section(
            'scs_competitor_settings',
            'Competitor Analysis Settings',
            array($this, 'render_settings_section'),
            'scs-settings'
        );
        
        add_settings_field(
            'scs_competitor_sites',
            'Competitor Sites',
            array($this, 'render_competitor_sites_field'),
            'scs-settings',
            'scs_competitor_settings'
        );
    }
    
    /**
     * Render settings section
     */
    public function render_settings_section() {
        echo '<p>Configure competitor sites to monitor for content analysis</p>';
    }
    
    /**
     * Render competitor sites field
     */
    public function render_competitor_sites_field() {
        $sites = get_option('scs_competitor_sites', array());
        
        echo '<div id="scs-competitor-sites">';
        
        if (!empty($sites)) {
            foreach ($sites as $index => $site) {
                echo '<div class="scs-competitor-site">';
                echo '<input type="text" name="scs_competitor_sites[]" value="' . esc_attr($site) . '" class="regular-text" />';
                echo '<button type="button" class="button scs-remove-competitor">Remove</button>';
                echo '</div>';
            }
        } else {
            echo '<div class="scs-competitor-site">';
            echo '<input type="text" name="scs_competitor_sites[]" value="" class="regular-text" />';
            echo '<button type="button" class="button scs-remove-competitor">Remove</button>';
            echo '</div>';
        }
        
        echo '</div>';
        echo '<button type="button" class="button scs-add-competitor">Add Competitor</button>';
        
        // Add JavaScript for adding/removing competitor sites
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.scs-add-competitor').on('click', function() {
                var html = '<div class="scs-competitor-site">';
                html += '<input type="text" name="scs_competitor_sites[]" value="" class="regular-text" />';
                html += '<button type="button" class="button scs-remove-competitor">Remove</button>';
                html += '</div>';
                $('#scs-competitor-sites').append(html);
            });
            
            $('#scs-competitor-sites').on('click', '.scs-remove-competitor', function() {
                $(this).parent().remove();
            });
        });
        </script>
        <?php
    }
    
    /**
     * Sanitize competitor sites
     * 
     * @param array $sites Competitor sites
     * @return array Sanitized sites
     */
    public function sanitize_competitor_sites($sites) {
        $sanitized_sites = array();
        
        if (is_array($sites)) {
            foreach ($sites as $site) {
                $site = sanitize_text_field($site);
                
                if (!empty($site)) {
                    $sanitized_sites[] = $site;
                }
            }
        }
        
        return $sanitized_sites;
    }
    
    /**
     * Schedule the first competitor scan
     */
    public function schedule_scan() {
        if (!wp_next_scheduled('scs_weekly_competitor_scan')) {
            wp_schedule_event(time(), 'weekly', 'scs_weekly_competitor_scan');
        }
    }
    
    /**
     * Scan competitor content
     */
    public function scan_competitor_content() {
        $sites = get_option('scs_competitor_sites', array());
        
        if (empty($sites)) {
            return;
        }
        
        $data = array();
        
        foreach ($sites as $site) {
            $site_data = $this->crawl_competitor_site($site);
            
            if ($site_data) {
                $data[$site] = $site_data;
            }
        }
        
        // Store competitor data
        update_option('scs_competitor_data', $data);
        update_option('scs_competitor_last_scan', current_time('mysql'));
    }
    
    /**
     * Add competitor insights to content recommendations
     * 
     * @param array $recommendations Existing recommendations
     * @param array $params Recommendation parameters
     * @return array Enhanced recommendations
     */
    public function add_competitor_insights($recommendations, $params) {
        $competitor_data = get_option('scs_competitor_data', array());
        
        if (empty($competitor_data)) {
            return $recommendations;
        }
        
        // Get content category or keywords
        $category = isset($params['category']) ? $params['category'] : '';
        $keywords = isset($params['keywords']) ? $params['keywords'] : array();
        
        // Find relevant competitor insights
        $insights = $this->find_relevant_insights($competitor_data, $category, $keywords);
        
        // Add insights to recommendations
        if (!empty($insights)) {
            foreach ($insights as $insight) {
                $recommendations[] = array(
                    'type' => 'competitor',
                    'title' => $insight['title'],
                    'description' => $insight['description'],
                    'priority' => 'high',
                );
            }
        }
        
        return $recommendations;
    }
    
    /**
     * Crawl competitor site
     * 
     * @param string $site The competitor site URL
     * @return array|false Site data or false on failure
     */
    private function crawl_competitor_site($site) {
        // This would use crawling/scraping techniques or API calls
        // Placeholder implementation
        
        // Check if site is accessible
        $response = wp_remote_get($site);
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) != 200) {
            // Site not accessible
            return false;
        }
        
        // Generate placeholder data
        $recent_content = array();
        
        for ($i = 0; $i < 5; $i++) {
            $recent_content[] = array(
                'title' => 'Competitor Article ' . ($i + 1),
                'url' => $site . '/sample-post-' . ($i + 1),
                'publish_date' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 14) . ' days')),
                'estimated_word_count' => rand(500, 2000),
                'categories' => $this->get_random_categories(),
                'keywords' => $this->get_random_keywords(),
            );
        }
        
        $posting_frequency = array(
            'daily' => rand(0, 2),
            'weekly' => rand(3, 7),
            'monthly' => rand(10, 20),
        );
        
        $popular_topics = array(
            $this->get_random_categories()[0] => rand(3, 10),
            $this->get_random_categories()[0] => rand(3, 10),
            $this->get_random_categories()[0] => rand(3, 10),
        );
        
        $social_activity = array(
            'facebook_shares' => rand(10, 100),
            'twitter_shares' => rand(5, 50),
            'linkedin_shares' => rand(2, 30),
        );
        
        return array(
            'recent_content' => $recent_content,
            'posting_frequency' => $posting_frequency,
            'popular_topics' => $popular_topics,
            'social_activity' => $social_activity,
        );
    }
    
    /**
     * Find relevant insights from competitor data
     * 
     * @param array $competitor_data Competitor data
     * @param string $category Content category
     * @param array $keywords Content keywords
     * @return array Relevant insights
     */
    private function find_relevant_insights($competitor_data, $category, $keywords) {
        $insights = array();
        
        // Topic gap analysis
        $insights[] = $this->generate_topic_gap_insight($competitor_data, $category);
        
        // Content length comparison
        $insights[] = $this->generate_content_length_insight($competitor_data);
        
        // Posting frequency analysis
        $insights[] = $this->generate_posting_frequency_insight($competitor_data);
        
        // Social engagement analysis
        $insights[] = $this->generate_social_engagement_insight($competitor_data);
        
        // Filter out empty insights
        return array_filter($insights);
    }
    
    /**
     * Generate topic gap insight
     * 
     * @param array $competitor_data Competitor data
     * @param string $category Content category
     * @return array|false Insight or false if not relevant
     */
    private function generate_topic_gap_insight($competitor_data, $category) {
        if (empty($category)) {
            return false;
        }
        
        $competitor_topics = array();
        
        foreach ($competitor_data as $site => $data) {
            foreach ($data['popular_topics'] as $topic => $count) {
                if (!isset($competitor_topics[$topic])) {
                    $competitor_topics[$topic] = 0;
                }
                
                $competitor_topics[$topic] += $count;
            }
        }
        
        arsort($competitor_topics);
        $top_topics = array_slice($competitor_topics, 0, 3, true);
        
        if (!array_key_exists($category, $top_topics) && !empty($top_topics)) {
            $top_topic = key($top_topics);
            
            return array(
                'title' => 'Topic Gap Opportunity: ' . ucfirst($top_topic),
                'description' => 'Competitors are focusing on ' . $top_topic . ' content which is trending in your industry.',
            );
        }
        
        return false;
    }
    
    /**
     * Generate content length insight
     * 
     * @param array $competitor_data Competitor data
     * @return array Insight
     */
    private function generate_content_length_insight($competitor_data) {
        $total_word_count = 0;
        $total_articles = 0;
        
        foreach ($competitor_data as $site => $data) {
            foreach ($data['recent_content'] as $content) {
                $total_word_count += $content['estimated_word_count'];
                $total_articles++;
            }
        }
        
        if ($total_articles > 0) {
            $average_word_count = round($total_word_count / $total_articles);
            
            return array(
                'title' => 'Content Length Benchmark',
                'description' => 'Competitor content averages ' . $average_word_count . ' words. Consider this length for your articles.',
            );
        }
        
        return false;
    }
    
    /**
     * Generate posting frequency insight
     * 
     * @param array $competitor_data Competitor data
     * @return array Insight
     */
    private function generate_posting_frequency_insight($competitor_data) {
        $total_weekly = 0;
        $site_count = count($competitor_data);
        
        if ($site_count > 0) {
            foreach ($competitor_data as $site => $data) {
                if (isset($data['posting_frequency']['weekly'])) {
                    $total_weekly += $data['posting_frequency']['weekly'];
                }
            }
            
            $average_weekly = round($total_weekly / $site_count);
            
            return array(
                'title' => 'Posting Frequency Strategy',
                'description' => 'Your competitors publish approximately ' . $average_weekly . ' posts per week. Consider adjusting your schedule accordingly.',
            );
        }
        
        return false;
    }
    
    /**
     * Generate social engagement insight
     * 
     * @param array $competitor_data Competitor data
     * @return array Insight
     */
    private function generate_social_engagement_insight($competitor_data) {
        $platform_totals = array(
            'facebook' => 0,
            'twitter' => 0,
            'linkedin' => 0,
        );
        
        $site_count = count($competitor_data);
        
        if ($site_count > 0) {
            foreach ($competitor_data as $site => $data) {
                if (isset($data['social_activity'])) {
                    $platform_totals['facebook'] += isset($data['social_activity']['facebook_shares']) ? $data['social_activity']['facebook_shares'] : 0;
                    $platform_totals['twitter'] += isset($data['social_activity']['twitter_shares']) ? $data['social_activity']['twitter_shares'] : 0;
                    $platform_totals['linkedin'] += isset($data['social_activity']['linkedin_shares']) ? $data['social_activity']['linkedin_shares'] : 0;
                }
            }
            
            arsort($platform_totals);
            $top_platform = key($platform_totals);
            
            return array(
                'title' => 'Social Platform Focus',
                'description' => ucfirst($top_platform) . ' drives the most engagement for your competitors. Consider prioritizing this platform for content distribution.',
            );
        }
        
        return false;
    }
    
    /**
     * Get random categories (helper method for placeholder data)
     * 
     * @return array Random categories
     */
    private function get_random_categories() {
        $categories = array('marketing', 'technology', 'business', 'social media', 'content strategy', 'SEO');
        shuffle($categories);
        return array_slice($categories, 0, rand(1, 3));
    }
    
    /**
     * Get random keywords (helper method for placeholder data)
     * 
     * @return array Random keywords
     */
    private function get_random_keywords() {
        $keywords = array('digital', 'strategy', 'analytics', 'optimization', 'trends', 'automation', 'engagement', 'conversion');
        shuffle($keywords);
        return array_slice($keywords, 0, rand(2, 5));
    }
}
<?php
/**
 * NLP Analyzer for content quality assessment
 *
 * @since      1.0.0
 * @package    Smart_Content_Scheduler
 */

class Smart_Content_Scheduler_NLP_Analyzer {

    /**
     * External NLP API endpoint
     * 
     * @var string
     */
    private $api_endpoint;
    
    /**
     * API key
     * 
     * @var string
     */
    private $api_key;
    
    /**
     * Flag to check if ML libraries are available
     *
     * @var bool
     */
    private $ml_available = false;

    /**
     * Initialize the class
     */
    public function __construct() {
        $this->api_endpoint = get_option('scs_nlp_api_endpoint', 'https://api.example.com/nlp');
        $this->api_key = get_option('scs_nlp_api_key', '');
        
        // Check if PHP-ML is available
        if (file_exists(SCS_PLUGIN_DIR . 'vendor/autoload.php')) {
            require_once SCS_PLUGIN_DIR . 'vendor/autoload.php';
            
            if (class_exists('Phpml\Classification\SVC')) {
                $this->ml_available = true;
            }
        }
    }

    /**
     * Analyze content quality
     *
     * @param string $content Content to analyze
     * @return array Analysis results with scores
     */
    public function analyze_content_quality($content) {
        // If no API key is configured, use basic analysis
        if (empty($this->api_key)) {
            return $this->perform_basic_analysis($content);
        }
        
        // Clean the content (remove HTML, etc.)
        $cleaned_content = strip_tags($content);
        
        // Call the NLP API
        try {
            $response = wp_remote_post($this->api_endpoint, [
                'timeout' => 45,
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->api_key,
                    'Content-Type' => 'application/json'
                ],
                'body' => json_encode([
                    'text' => $cleaned_content,
                    'analysis_type' => 'content_quality',
                    'features' => [
                        'readability',
                        'sentiment',
                        'engagement_prediction',
                        'topic_relevance',
                        'seo_optimization'
                    ]
                ])
            ]);
            
            if (is_wp_error($response)) {
                error_log('NLP API Error: ' . $response->get_error_message());
                return $this->perform_basic_analysis($content);
            }
            
            $body = json_decode(wp_remote_retrieve_body($response), true);
            
            if (isset($body['error'])) {
                error_log('NLP API Error: ' . $body['error']);
                return $this->perform_basic_analysis($content);
            }
            
            return $this->format_api_response($body);
            
        } catch (Exception $e) {
            error_log('NLP Analysis Error: ' . $e->getMessage());
            return $this->perform_basic_analysis($content);
        }
    }
    
    /**
     * Perform basic text analysis without external API
     *
     * @param string $content Content to analyze
     * @return array Analysis results
     */
    private function perform_basic_analysis($content) {
        $cleaned_content = strip_tags($content);
        $word_count = str_word_count($cleaned_content);
        
        // Calculate readability using Flesch-Kincaid formula
        $sentences = preg_split('/[.!?]+/', $cleaned_content);
        $sentence_count = count(array_filter($sentences));
        
        // Avoid division by zero
        if ($sentence_count == 0) $sentence_count = 1;
        
        $words_per_sentence = $word_count / $sentence_count;
        
        // Count syllables (very basic approximation)
        $syllable_count = 0;
        $words = str_word_count($cleaned_content, 1);
        foreach ($words as $word) {
            $word = strtolower($word);
            $word = preg_replace('/[^a-z]/', '', $word);
            $syllable_count += $this->count_syllables($word);
        }
        
        // Avoid division by zero
        if ($word_count == 0) $word_count = 1;
        
        $syllables_per_word = $syllable_count / $word_count;
        
        // Calculate Flesch Reading Ease score
        $flesch_score = 206.835 - (1.015 * $words_per_sentence) - (84.6 * $syllables_per_word);
        $flesch_score = max(0, min(100, $flesch_score)); // Clamp between 0-100
        
        // Calculate keyword density
        $keyword_density = $this->calculate_keyword_density($cleaned_content);
        
        // SEO score based on basic factors
        $seo_score = 0;
        
        // Word count factor (300-1500 words is ideal)
        if ($word_count >= 300 && $word_count <= 1500) {
            $seo_score += 25;
        } elseif ($word_count > 1500) {
            $seo_score += 20;
        } elseif ($word_count >= 100) {
            $seo_score += 15;
        }
        
        // Readability factor
        if ($flesch_score >= 60 && $flesch_score <= 70) {
            $seo_score += 25; // Ideal range
        } elseif ($flesch_score >= 50 && $flesch_score <= 80) {
            $seo_score += 20; // Good range
        } else {
            $seo_score += 10; // Outside optimal range
        }
        
        // Keyword density factor (2-4% is ideal)
        foreach ($keyword_density as $keyword => $density) {
            if ($density >= 0.02 && $density <= 0.04) {
                $seo_score += 25;
                break;
            } elseif ($density > 0 && $density <= 0.06) {
                $seo_score += 15;
                break;
            }
        }
        
        // Heading usage
        if (preg_match('/<h[1-6][^>]*>/i', $content)) {
            $seo_score += 25;
        }
        
        // Clamp final score between 0-100
        $seo_score = max(0, min(100, $seo_score));
        
        // Engagement prediction (based on readability and SEO)
        $engagement_prediction = ($flesch_score * 0.4 + $seo_score * 0.6) / 100;
        
        return [
            'readability' => [
                'score' => $flesch_score / 100,
                'flesch_score' => $flesch_score,
                'words_per_sentence' => $words_per_sentence,
                'syllables_per_word' => $syllables_per_word,
                'word_count' => $word_count,
                'sentence_count' => $sentence_count
            ],
            'seo' => [
                'score' => $seo_score / 100,
                'keyword_density' => $keyword_density
            ],
            'engagement_prediction' => $engagement_prediction,
            'overall_score' => ($flesch_score + $seo_score) / 200,
            'improvement_suggestions' => $this->generate_improvement_suggestions($flesch_score, $seo_score, $word_count, $keyword_density)
        ];
    }
    
    /**
     * Format the API response
     *
     * @param array $api_response The response from the NLP API
     * @return array Formatted analysis results
     */
    private function format_api_response($api_response) {
        $formatted = [];
        
        // Map API response to our format
        if (isset($api_response['readability'])) {
            $formatted['readability'] = [
                'score' => $api_response['readability']['score'] / 100,
                'flesch_score' => $api_response['readability']['flesch_score'] ?? 0,
                'words_per_sentence' => $api_response['readability']['words_per_sentence'] ?? 0,
                'syllables_per_word' => $api_response['readability']['syllables_per_word'] ?? 0,
                'word_count' => $api_response['readability']['word_count'] ?? 0,
                'sentence_count' => $api_response['readability']['sentence_count'] ?? 0
            ];
        }
        
        if (isset($api_response['seo_optimization'])) {
            $formatted['seo'] = [
                'score' => $api_response['seo_optimization']['score'] / 100,
                'keyword_density' => $api_response['seo_optimization']['keyword_density'] ?? []
            ];
        }
        
        $formatted['sentiment'] = $api_response['sentiment'] ?? ['score' => 0.5];
        $formatted['engagement_prediction'] = $api_response['engagement_prediction']['score'] ?? 0.5;
        $formatted['topic_relevance'] = $api_response['topic_relevance'] ?? ['score' => 0.5];
        
        // Calculate overall score
        $scores = [
            $formatted['readability']['score'] ?? 0.5,
            $formatted['seo']['score'] ?? 0.5,
            $formatted['sentiment']['score'] ?? 0.5,
            $formatted['engagement_prediction'] ?? 0.5,
            $formatted['topic_relevance']['score'] ?? 0.5
        ];
        
        $formatted['overall_score'] = array_sum($scores) / count($scores);
        
        // Include improvement suggestions
        $formatted['improvement_suggestions'] = $api_response['improvement_suggestions'] ?? 
            $this->generate_improvement_suggestions(
                ($formatted['readability']['flesch_score'] ?? 60),
                ($formatted['seo']['score'] * 100 ?? 50),
                ($formatted['readability']['word_count'] ?? 0),
                ($formatted['seo']['keyword_density'] ?? [])
            );
        
        return $formatted;
    }
    
    /**
     * Generate improvement suggestions based on content analysis
     *
     * @param float $flesch_score Readability score
     * @param float $seo_score SEO score
     * @param int $word_count Word count
     * @param array $keyword_density Keyword density data
     * @return array Improvement suggestions
     */
    private function generate_improvement_suggestions($flesch_score, $seo_score, $word_count, $keyword_density) {
        $suggestions = [];
        
        // Readability suggestions
        if ($flesch_score < 50) {
            $suggestions[] = [
                'category' => 'readability',
                'suggestion' => 'Consider simplifying your language. Use shorter sentences and more common words.',
                'importance' => 'high'
            ];
        } elseif ($flesch_score > 80) {
            $suggestions[] = [
                'category' => 'readability',
                'suggestion' => 'Your content may be too simplistic for professional audiences. Consider adding more depth.',
                'importance' => 'medium'
            ];
        }
        
        // Word count suggestions
        if ($word_count < 300) {
            $suggestions[] = [
                'category' => 'length',
                'suggestion' => 'Your content is quite short. Adding more depth (aim for at least 300 words) could improve engagement.',
                'importance' => 'high'
            ];
        } elseif ($word_count > 3000) {
            $suggestions[] = [
                'category' => 'length',
                'suggestion' => 'Your content is very long. Consider breaking it into multiple posts or adding more subheadings for readability.',
                'importance' => 'medium'
            ];
        }
        
        // Keyword density suggestions
        $has_good_keyword = false;
        foreach ($keyword_density as $keyword => $density) {
            if ($density >= 0.02 && $density <= 0.04) {
                $has_good_keyword = true;
                break;
            }
        }
        
        if (!$has_good_keyword) {
            $suggestions[] = [
                'category' => 'keywords',
                'suggestion' => 'Consider optimizing your keyword usage. Aim for a keyword density between 2-4% for your main keywords.',
                'importance' => 'high'
            ];
        }
        
        // SEO suggestions
        if ($seo_score < 60) {
            $suggestions[] = [
                'category' => 'seo',
                'suggestion' => 'Your content could benefit from SEO optimization. Consider adding headings, improving keyword usage, and increasing content depth.',
                'importance' => 'high'
            ];
        }
        
        return $suggestions;
    }
    
    /**
     * Very basic syllable counter
     *
     * @param string $word Word to count syllables for
     * @return int Number of syllables
     */
    private function count_syllables($word) {
        $word = strtolower($word);
        
        // Remove common prefixes and suffixes
        $word = preg_replace('/(?:[^laeiouy]es|[^laeiouy]e)$/', '', $word);
        $word = preg_replace('/^y/', '', $word);
        
        // Count vowel groups
        preg_match_all('/[aeiouy]+/', $word, $matches);
        $count = count($matches[0]);
        
        // Return at least 1 syllable
        return max(1, $count);
    }
    
    /**
     * Calculate keyword density
     *
     * @param string $text Text to analyze
     * @return array Keyword density information
     */
    private function calculate_keyword_density($text) {
        // Remove common stop words
        $stop_words = ['the', 'and', 'a', 'an', 'in', 'on', 'at', 'to', 'for', 'with', 'by', 'about', 'as', 'of', 'is', 'was', 'were', 'be', 'been'];
        
        $words = str_word_count(strtolower($text), 1);
        $total_words = count($words);
        
        if ($total_words === 0) {
            return [];
        }
        
        // Filter out stop words and count occurrences
        $filtered_words = array_diff($words, $stop_words);
        $word_count = array_count_values($filtered_words);
        
        // Get top 5 keywords by frequency
        arsort($word_count);
        $top_keywords = array_slice($word_count, 0, 5, true);
        
        // Calculate density
        $density = [];
        foreach ($top_keywords as $word => $count) {
            if (strlen($word) > 3) { // Only include words longer than 3 characters
                $density[$word] = $count / $total_words;
            }
        }
        
        return $density;
    }
}
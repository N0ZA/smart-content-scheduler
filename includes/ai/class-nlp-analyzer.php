<?php
/**
 * NLP Content Analyzer
 *
 * @package Smart_Content_Scheduler
 * @subpackage AI_Features
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * NLP Content Analyzer
 * Analyzes content for sentiment, readability, and topic relevance
 */
class SCS_NLP_Analyzer {
    
    /**
     * Initialize the NLP system
     */
    public function __construct() {
        add_filter('scs_content_analysis', array($this, 'analyze_content'), 10, 1);
        add_filter('scs_title_suggestions', array($this, 'generate_title_suggestions'), 10, 2);
    }
    
    /**
     * Analyze content using NLP techniques
     * 
     * @param array $content The content to analyze
     * @return array Analysis results
     */
    public function analyze_content($content) {
        $text = $content['text'];
        
        $analysis = array(
            'sentiment' => $this->analyze_sentiment($text),
            'readability' => $this->analyze_readability($text),
            'topics' => $this->extract_topics($text),
            'keywords' => $this->extract_keywords($text),
            'entities' => $this->extract_entities($text),
        );
        
        return $analysis;
    }
    
    /**
     * Generate title suggestions based on content
     * 
     * @param array $suggestions Existing suggestions
     * @param string $content The content to analyze
     * @return array Enhanced suggestions
     */
    public function generate_title_suggestions($suggestions, $content) {
        $topics = $this->extract_topics($content);
        $keywords = $this->extract_keywords($content);
        
        $new_suggestions = array();
        
        // Generate engaging titles based on topics and keywords
        if (!empty($topics)) {
            $new_suggestions[] = "Ultimate Guide to " . ucfirst($topics[0]);
            $new_suggestions[] = "How to Master " . ucfirst($topics[0]) . " in 2025";
        }
        
        if (!empty($keywords)) {
            $new_suggestions[] = ucfirst($keywords[0]) . ": The Complete Guide";
            $new_suggestions[] = "Why " . ucfirst($keywords[0]) . " Matters for Your Business";
        }
        
        return array_merge($suggestions, $new_suggestions);
    }
    
    /**
     * Analyze text sentiment
     * 
     * @param string $text Text to analyze
     * @return array Sentiment analysis results
     */
    private function analyze_sentiment($text) {
        // Implementation would use a NLP library or API
        // Placeholder implementation
        $sample_words = array(
            'positive' => array('good', 'great', 'excellent', 'amazing', 'outstanding'),
            'negative' => array('bad', 'poor', 'terrible', 'awful', 'disappointing'),
        );
        
        $text_lower = strtolower($text);
        $pos_count = 0;
        $neg_count = 0;
        
        foreach ($sample_words['positive'] as $word) {
            $pos_count += substr_count($text_lower, $word);
        }
        
        foreach ($sample_words['negative'] as $word) {
            $neg_count += substr_count($text_lower, $word);
        }
        
        $total = $pos_count + $neg_count;
        if ($total == 0) {
            $sentiment_score = 0;
        } else {
            $sentiment_score = ($pos_count - $neg_count) / $total;
        }
        
        return array(
            'score' => $sentiment_score,
            'classification' => $this->classify_sentiment($sentiment_score),
        );
    }
    
    /**
     * Analyze text readability
     * 
     * @param string $text Text to analyze
     * @return array Readability analysis results
     */
    private function analyze_readability($text) {
        // Implementation would use readability formulas
        // Placeholder implementation
        $word_count = str_word_count(strip_tags($text));
        $sentence_count = preg_match_all('/[.!?]+/', $text, $matches);
        if ($sentence_count == 0) $sentence_count = 1;
        
        $words_per_sentence = $word_count / $sentence_count;
        
        // Simple readability score (lower is easier to read)
        $score = $words_per_sentence * 0.5;
        
        return array(
            'score' => $score,
            'level' => $this->classify_readability($score),
            'word_count' => $word_count,
            'sentence_count' => $sentence_count,
            'words_per_sentence' => $words_per_sentence,
        );
    }
    
    /**
     * Extract topics from text
     * 
     * @param string $text Text to analyze
     * @return array Extracted topics
     */
    private function extract_topics($text) {
        // Implementation would use topic modeling techniques
        // Placeholder implementation
        $topics = array('marketing', 'business', 'technology');
        return $topics;
    }
    
    /**
     * Extract keywords from text
     * 
     * @param string $text Text to analyze
     * @return array Extracted keywords
     */
    private function extract_keywords($text) {
        // Implementation would use keyword extraction algorithms
        // Placeholder implementation
        $text = strtolower($text);
        $words = str_word_count($text, 1);
        $stopwords = array('the', 'and', 'a', 'to', 'of', 'in', 'is', 'it');
        
        $filtered_words = array_diff($words, $stopwords);
        $word_freq = array_count_values($filtered_words);
        arsort($word_freq);
        
        return array_slice(array_keys($word_freq), 0, 5);
    }
    
    /**
     * Extract entities from text
     * 
     * @param string $text Text to analyze
     * @return array Extracted entities
     */
    private function extract_entities($text) {
        // Implementation would use named entity recognition
        // Placeholder implementation
        return array(
            'persons' => array('John Smith', 'Jane Doe'),
            'organizations' => array('Acme Inc', 'TechCorp'),
            'locations' => array('New York', 'San Francisco'),
        );
    }
    
    /**
     * Classify sentiment score
     * 
     * @param float $score Sentiment score
     * @return string Sentiment classification
     */
    private function classify_sentiment($score) {
        if ($score < -0.5) return 'very negative';
        if ($score < -0.1) return 'negative';
        if ($score < 0.1) return 'neutral';
        if ($score < 0.5) return 'positive';
        return 'very positive';
    }
    
    /**
     * Classify readability score
     * 
     * @param float $score Readability score
     * @return string Readability level
     */
    private function classify_readability($score) {
        if ($score < 5) return 'very easy';
        if ($score < 10) return 'easy';
        if ($score < 15) return 'moderate';
        if ($score < 20) return 'difficult';
        return 'very difficult';
    }
}
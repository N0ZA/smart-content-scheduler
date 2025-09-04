<?php
if (!defined('ABSPATH')) {
    exit;
}

class SCS_NLP {
    
    public function __construct() {
        add_action('wp_ajax_scs_analyze_content', [$this, 'analyze_content_ajax']);
        add_action('wp_ajax_scs_extract_keywords', [$this, 'extract_keywords_ajax']);
        add_action('wp_ajax_scs_get_content_suggestions', [$this, 'get_content_suggestions_ajax']);
    }
    
    /**
     * AJAX handler for content analysis
     */
    public function analyze_content_ajax() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        $content = sanitize_textarea_field($_POST['content']);
        $title = sanitize_text_field($_POST['title'] ?? '');
        
        $analysis = $this->analyze_content($content, $title);
        
        wp_send_json_success($analysis);
    }
    
    /**
     * Comprehensive content analysis
     */
    public function analyze_content($content, $title = '') {
        return [
            'readability' => $this->analyze_readability($content),
            'sentiment' => $this->analyze_sentiment($content),
            'keywords' => $this->extract_keywords($content, $title),
            'structure' => $this->analyze_structure($content),
            'seo_score' => $this->calculate_seo_score($content, $title),
            'engagement_factors' => $this->analyze_engagement_factors($content, $title),
            'suggestions' => $this->generate_content_suggestions($content, $title)
        ];
    }
    
    /**
     * Analyze content readability
     */
    public function analyze_readability($content) {
        $words = str_word_count($content);
        $sentences = $this->count_sentences($content);
        $syllables = $this->count_syllables($content);
        
        // Flesch Reading Ease Score
        $flesch_score = 0;
        if ($sentences > 0 && $words > 0) {
            $flesch_score = 206.835 - (1.015 * ($words / $sentences)) - (84.6 * ($syllables / $words));
        }
        
        // Flesch-Kincaid Grade Level
        $fk_grade = 0;
        if ($sentences > 0 && $words > 0) {
            $fk_grade = (0.39 * ($words / $sentences)) + (11.8 * ($syllables / $words)) - 15.59;
        }
        
        return [
            'flesch_score' => round($flesch_score, 2),
            'flesch_level' => $this->get_flesch_level($flesch_score),
            'fk_grade' => round($fk_grade, 2),
            'words' => $words,
            'sentences' => $sentences,
            'avg_words_per_sentence' => $sentences > 0 ? round($words / $sentences, 2) : 0,
            'syllables' => $syllables
        ];
    }
    
    /**
     * Get Flesch reading level description
     */
    private function get_flesch_level($score) {
        if ($score >= 90) return 'Very Easy';
        if ($score >= 80) return 'Easy';
        if ($score >= 70) return 'Fairly Easy';
        if ($score >= 60) return 'Standard';
        if ($score >= 50) return 'Fairly Difficult';
        if ($score >= 30) return 'Difficult';
        return 'Very Difficult';
    }
    
    /**
     * Analyze sentiment
     */
    public function analyze_sentiment($content) {
        $positive_words = [
            'amazing', 'excellent', 'fantastic', 'great', 'wonderful', 'awesome', 
            'brilliant', 'outstanding', 'perfect', 'superb', 'love', 'best',
            'beautiful', 'incredible', 'magnificent', 'spectacular', 'impressive',
            'good', 'nice', 'happy', 'joy', 'excited', 'thrilled', 'delighted',
            'pleased', 'satisfied', 'successful', 'victory', 'win', 'achieve'
        ];
        
        $negative_words = [
            'terrible', 'awful', 'horrible', 'bad', 'worst', 'hate', 'disgusting',
            'disappointing', 'frustrating', 'annoying', 'pathetic', 'useless',
            'boring', 'stupid', 'ridiculous', 'waste', 'failed', 'error',
            'wrong', 'problem', 'issue', 'concern', 'worry', 'fear', 'sad'
        ];
        
        $content_lower = strtolower($content);
        $words = str_word_count($content_lower, 1);
        
        $positive_count = 0;
        $negative_count = 0;
        
        foreach ($words as $word) {
            if (in_array($word, $positive_words)) {
                $positive_count++;
            } elseif (in_array($word, $negative_words)) {
                $negative_count++;
            }
        }
        
        $total_sentiment_words = $positive_count + $negative_count;
        $sentiment_score = $total_sentiment_words > 0 ? 
            (($positive_count - $negative_count) / $total_sentiment_words) * 100 : 0;
        
        return [
            'score' => round($sentiment_score, 2),
            'label' => $this->get_sentiment_label($sentiment_score),
            'positive_words' => $positive_count,
            'negative_words' => $negative_count,
            'total_words' => count($words)
        ];
    }
    
    /**
     * Get sentiment label
     */
    private function get_sentiment_label($score) {
        if ($score > 20) return 'Positive';
        if ($score < -20) return 'Negative';
        return 'Neutral';
    }
    
    /**
     * Extract keywords from content
     */
    public function extract_keywords($content, $title = '', $limit = 10) {
        // Common stop words to exclude
        $stop_words = [
            'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 'from',
            'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on', 'that', 'the',
            'to', 'was', 'will', 'with', 'would', 'you', 'your', 'have', 'had',
            'this', 'they', 'them', 'their', 'can', 'could', 'should', 'may',
            'might', 'must', 'shall', 'will', 'would', 'not', 'no', 'yes'
        ];
        
        $text = $content . ' ' . $title;
        $text = strtolower($text);
        $text = preg_replace('/[^a-z\s]/', ' ', $text);
        $words = array_filter(explode(' ', $text));
        
        // Remove stop words and short words
        $filtered_words = array_filter($words, function($word) use ($stop_words) {
            return strlen($word) > 3 && !in_array($word, $stop_words);
        });
        
        // Count word frequency
        $word_freq = array_count_values($filtered_words);
        arsort($word_freq);
        
        // Extract top keywords
        $keywords = [];
        $count = 0;
        foreach ($word_freq as $word => $frequency) {
            if ($count >= $limit) break;
            $keywords[] = [
                'word' => $word,
                'frequency' => $frequency,
                'relevance' => round(($frequency / count($words)) * 100, 2)
            ];
            $count++;
        }
        
        return $keywords;
    }
    
    /**
     * AJAX handler for keyword extraction
     */
    public function extract_keywords_ajax() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        $content = sanitize_textarea_field($_POST['content']);
        $title = sanitize_text_field($_POST['title'] ?? '');
        $limit = intval($_POST['limit'] ?? 10);
        
        $keywords = $this->extract_keywords($content, $title, $limit);
        
        wp_send_json_success($keywords);
    }
    
    /**
     * Analyze content structure
     */
    public function analyze_structure($content) {
        $paragraphs = explode("\n\n", $content);
        $paragraphs = array_filter($paragraphs, 'trim');
        
        return [
            'paragraph_count' => count($paragraphs),
            'avg_paragraph_length' => $this->calculate_avg_paragraph_length($paragraphs),
            'has_headings' => $this->has_headings($content),
            'has_lists' => $this->has_lists($content),
            'has_links' => preg_match('/https?:\/\//', $content) ? true : false,
            'structure_score' => $this->calculate_structure_score($content)
        ];
    }
    
    /**
     * Calculate SEO score
     */
    public function calculate_seo_score($content, $title) {
        $score = 0;
        $max_score = 100;
        
        // Title length (20 points)
        $title_length = strlen($title);
        if ($title_length >= 30 && $title_length <= 60) {
            $score += 20;
        } elseif ($title_length >= 20 && $title_length <= 70) {
            $score += 15;
        } elseif ($title_length > 0) {
            $score += 10;
        }
        
        // Content length (20 points)
        $content_length = strlen($content);
        if ($content_length >= 300) {
            $score += 20;
        } elseif ($content_length >= 150) {
            $score += 15;
        } elseif ($content_length > 0) {
            $score += 10;
        }
        
        // Readability (20 points)
        $readability = $this->analyze_readability($content);
        if ($readability['flesch_score'] >= 60) {
            $score += 20;
        } elseif ($readability['flesch_score'] >= 40) {
            $score += 15;
        } else {
            $score += 10;
        }
        
        // Structure (20 points)
        $structure = $this->analyze_structure($content);
        $score += min(20, $structure['structure_score']);
        
        // Keywords (20 points)
        $keywords = $this->extract_keywords($content, $title, 5);
        if (count($keywords) >= 5) {
            $score += 20;
        } elseif (count($keywords) >= 3) {
            $score += 15;
        } elseif (count($keywords) > 0) {
            $score += 10;
        }
        
        return round(($score / $max_score) * 100, 2);
    }
    
    /**
     * Analyze engagement factors
     */
    public function analyze_engagement_factors($content, $title) {
        $factors = [];
        
        // Emotional appeal
        $sentiment = $this->analyze_sentiment($content);
        $factors['emotional_appeal'] = abs($sentiment['score']) > 10 ? 'High' : 'Low';
        
        // Question usage
        $question_count = substr_count($content, '?');
        $factors['question_usage'] = $question_count > 0 ? 'Present' : 'Absent';
        
        // Call-to-action detection
        $cta_phrases = ['click here', 'read more', 'learn more', 'subscribe', 'download', 'get started'];
        $has_cta = false;
        foreach ($cta_phrases as $phrase) {
            if (stripos($content, $phrase) !== false) {
                $has_cta = true;
                break;
            }
        }
        $factors['call_to_action'] = $has_cta ? 'Present' : 'Absent';
        
        // Urgency words
        $urgency_words = ['now', 'today', 'urgent', 'limited', 'exclusive', 'last chance'];
        $urgency_count = 0;
        foreach ($urgency_words as $word) {
            $urgency_count += substr_count(strtolower($content), $word);
        }
        $factors['urgency'] = $urgency_count > 0 ? 'High' : 'Low';
        
        return $factors;
    }
    
    /**
     * Generate content suggestions
     */
    public function generate_content_suggestions($content, $title) {
        $suggestions = [];
        
        $readability = $this->analyze_readability($content);
        $sentiment = $this->analyze_sentiment($content);
        $structure = $this->analyze_structure($content);
        
        // Title suggestions
        if (strlen($title) < 30) {
            $suggestions[] = 'Consider making your title longer to improve SEO';
        }
        if (strlen($title) > 60) {
            $suggestions[] = 'Your title might be too long for search results';
        }
        
        // Content length suggestions
        if (strlen($content) < 300) {
            $suggestions[] = 'Consider adding more content for better SEO performance';
        }
        
        // Readability suggestions
        if ($readability['flesch_score'] < 60) {
            $suggestions[] = 'Try using shorter sentences and simpler words to improve readability';
        }
        if ($readability['avg_words_per_sentence'] > 20) {
            $suggestions[] = 'Break up long sentences for better readability';
        }
        
        // Structure suggestions
        if ($structure['paragraph_count'] < 3) {
            $suggestions[] = 'Consider breaking your content into more paragraphs';
        }
        if (!$structure['has_headings']) {
            $suggestions[] = 'Add headings to improve content structure';
        }
        
        // Sentiment suggestions
        if ($sentiment['score'] < -20) {
            $suggestions[] = 'Your content has negative sentiment - consider adding positive language';
        }
        
        return $suggestions;
    }
    
    /**
     * AJAX handler for content suggestions
     */
    public function get_content_suggestions_ajax() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        $content = sanitize_textarea_field($_POST['content']);
        $title = sanitize_text_field($_POST['title'] ?? '');
        
        $suggestions = $this->generate_content_suggestions($content, $title);
        
        wp_send_json_success($suggestions);
    }
    
    /**
     * Helper functions
     */
    private function count_sentences($text) {
        return preg_match_all('/[.!?]+/', $text);
    }
    
    private function count_syllables($text) {
        $words = str_word_count(strtolower($text), 1);
        $total_syllables = 0;
        
        foreach ($words as $word) {
            $syllables = preg_match_all('/[aeiouy]+/', $word);
            $total_syllables += max(1, $syllables);
        }
        
        return $total_syllables;
    }
    
    private function calculate_avg_paragraph_length($paragraphs) {
        if (empty($paragraphs)) return 0;
        
        $total_length = array_sum(array_map('strlen', $paragraphs));
        return round($total_length / count($paragraphs), 2);
    }
    
    private function has_headings($content) {
        return preg_match('/^#{1,6}\s+/m', $content) || 
               preg_match('/<h[1-6]>/i', $content);
    }
    
    private function has_lists($content) {
        return preg_match('/^\s*[-*+]\s+/m', $content) || 
               preg_match('/^\s*\d+\.\s+/m', $content) ||
               preg_match('/<[uo]l>/i', $content);
    }
    
    private function calculate_structure_score($content) {
        $score = 0;
        
        if ($this->has_headings($content)) $score += 5;
        if ($this->has_lists($content)) $score += 5;
        if (preg_match('/https?:\/\//', $content)) $score += 5;
        
        $paragraphs = explode("\n\n", $content);
        $paragraph_count = count(array_filter($paragraphs, 'trim'));
        
        if ($paragraph_count >= 3) $score += 5;
        
        return $score;
    }
}
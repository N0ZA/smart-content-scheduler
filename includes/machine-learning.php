<?php
if (!defined('ABSPATH')) {
    exit;
}

class SCS_Machine_Learning {
    
    public function __construct() {
        add_action('wp_ajax_scs_ml_predict_performance', [$this, 'predict_performance']);
        add_action('wp_ajax_scs_ml_train_model', [$this, 'train_model']);
    }
    
    /**
     * Predict content performance using machine learning
     */
    public function predict_performance() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        $content = sanitize_textarea_field($_POST['content']);
        $title = sanitize_text_field($_POST['title']);
        $scheduled_time = sanitize_text_field($_POST['scheduled_time']);
        
        $prediction = $this->analyze_content_ml($content, $title, $scheduled_time);
        
        wp_send_json_success($prediction);
    }
    
    /**
     * Analyze content using machine learning algorithms
     */
    public function analyze_content_ml($content, $title, $scheduled_time = null) {
        $features = $this->extract_features($content, $title, $scheduled_time);
        $model_data = $this->get_trained_model();
        
        $predicted_score = $this->linear_regression_predict($features, $model_data);
        
        return [
            'predicted_score' => round($predicted_score, 2),
            'confidence' => $this->calculate_confidence($features, $model_data),
            'features' => $features,
            'recommendations' => $this->generate_ml_recommendations($features, $predicted_score)
        ];
    }
    
    /**
     * Extract features from content for ML analysis
     */
    private function extract_features($content, $title, $scheduled_time = null) {
        $features = [];
        
        // Text-based features
        $features['title_length'] = strlen($title);
        $features['content_length'] = strlen($content);
        $features['word_count'] = str_word_count($content);
        $features['sentence_count'] = substr_count($content, '.') + substr_count($content, '!') + substr_count($content, '?');
        $features['paragraph_count'] = substr_count($content, "\n\n") + 1;
        
        // Readability features
        $features['avg_words_per_sentence'] = $features['sentence_count'] > 0 ? 
            $features['word_count'] / $features['sentence_count'] : 0;
        $features['avg_sentence_per_paragraph'] = $features['paragraph_count'] > 0 ? 
            $features['sentence_count'] / $features['paragraph_count'] : 0;
        
        // Content type features
        $features['has_numbers'] = preg_match('/\d/', $content) ? 1 : 0;
        $features['has_links'] = preg_match('/https?:\/\//', $content) ? 1 : 0;
        $features['has_question_marks'] = substr_count($content, '?');
        $features['has_exclamation_marks'] = substr_count($content, '!');
        
        // Emotional features (simple sentiment analysis)
        $features['positive_words'] = $this->count_sentiment_words($content, 'positive');
        $features['negative_words'] = $this->count_sentiment_words($content, 'negative');
        $features['sentiment_score'] = $features['positive_words'] - $features['negative_words'];
        
        // Timing features
        if ($scheduled_time) {
            $timestamp = strtotime($scheduled_time);
            $features['hour'] = date('H', $timestamp);
            $features['day_of_week'] = date('N', $timestamp); // 1-7, Monday-Sunday
            $features['is_weekend'] = in_array($features['day_of_week'], [6, 7]) ? 1 : 0;
        }
        
        return $features;
    }
    
    /**
     * Simple sentiment word counting
     */
    private function count_sentiment_words($text, $type) {
        $positive_words = [
            'amazing', 'excellent', 'fantastic', 'great', 'wonderful', 'awesome', 
            'brilliant', 'outstanding', 'perfect', 'superb', 'love', 'best',
            'beautiful', 'incredible', 'magnificent', 'spectacular', 'impressive'
        ];
        
        $negative_words = [
            'terrible', 'awful', 'horrible', 'bad', 'worst', 'hate', 'disgusting',
            'disappointing', 'frustrating', 'annoying', 'pathetic', 'useless',
            'boring', 'stupid', 'ridiculous', 'waste', 'failed'
        ];
        
        $words = $type === 'positive' ? $positive_words : $negative_words;
        $text_lower = strtolower($text);
        $count = 0;
        
        foreach ($words as $word) {
            $count += substr_count($text_lower, $word);
        }
        
        return $count;
    }
    
    /**
     * Train the machine learning model with historical data
     */
    public function train_model() {
        check_ajax_referer('scs_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $training_data = $this->get_training_data();
        $model = $this->linear_regression_train($training_data);
        
        update_option('scs_ml_model', $model);
        
        wp_send_json_success([
            'message' => 'Model trained successfully',
            'training_samples' => count($training_data),
            'model_accuracy' => $this->calculate_model_accuracy($model, $training_data)
        ]);
    }
    
    /**
     * Get training data from historical posts
     */
    private function get_training_data() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scs_analytics';
        
        $results = $wpdb->get_results("
            SELECT p.post_title, p.post_content, a.engagement_score, a.published_time
            FROM {$wpdb->posts} p
            JOIN $table_name a ON p.ID = a.post_id
            WHERE a.engagement_score > 0 
            AND p.post_status = 'publish'
            AND a.published_time IS NOT NULL
            ORDER BY a.published_time DESC
            LIMIT 1000
        ");
        
        $training_data = [];
        foreach ($results as $row) {
            $features = $this->extract_features($row->post_content, $row->post_title, $row->published_time);
            $training_data[] = [
                'features' => $features,
                'target' => floatval($row->engagement_score)
            ];
        }
        
        return $training_data;
    }
    
    /**
     * Simple linear regression training
     */
    private function linear_regression_train($training_data) {
        if (empty($training_data)) {
            return ['weights' => [], 'bias' => 0, 'feature_names' => []];
        }
        
        $feature_names = array_keys($training_data[0]['features']);
        $X = [];
        $y = [];
        
        foreach ($training_data as $sample) {
            $X[] = array_values($sample['features']);
            $y[] = $sample['target'];
        }
        
        // Simple gradient descent implementation
        $num_features = count($feature_names);
        $weights = array_fill(0, $num_features, 0.0);
        $bias = 0.0;
        $learning_rate = 0.001;
        $epochs = 1000;
        
        $m = count($X);
        
        for ($epoch = 0; $epoch < $epochs; $epoch++) {
            $predictions = [];
            foreach ($X as $i => $x) {
                $prediction = $bias;
                for ($j = 0; $j < $num_features; $j++) {
                    $prediction += $weights[$j] * $x[$j];
                }
                $predictions[] = $prediction;
            }
            
            // Calculate gradients
            $weight_gradients = array_fill(0, $num_features, 0.0);
            $bias_gradient = 0.0;
            
            for ($i = 0; $i < $m; $i++) {
                $error = $predictions[$i] - $y[$i];
                $bias_gradient += $error;
                
                for ($j = 0; $j < $num_features; $j++) {
                    $weight_gradients[$j] += $error * $X[$i][$j];
                }
            }
            
            // Update weights and bias
            $bias -= ($learning_rate / $m) * $bias_gradient;
            for ($j = 0; $j < $num_features; $j++) {
                $weights[$j] -= ($learning_rate / $m) * $weight_gradients[$j];
            }
        }
        
        return [
            'weights' => $weights,
            'bias' => $bias,
            'feature_names' => $feature_names
        ];
    }
    
    /**
     * Predict using trained linear regression model
     */
    private function linear_regression_predict($features, $model) {
        if (empty($model['weights']) || empty($model['feature_names'])) {
            return 50; // Default prediction
        }
        
        $prediction = $model['bias'];
        
        foreach ($model['feature_names'] as $i => $feature_name) {
            $feature_value = isset($features[$feature_name]) ? $features[$feature_name] : 0;
            $prediction += $model['weights'][$i] * $feature_value;
        }
        
        // Ensure prediction is within reasonable bounds
        return max(0, min(100, $prediction));
    }
    
    /**
     * Get trained model from database
     */
    private function get_trained_model() {
        $model = get_option('scs_ml_model', []);
        
        if (empty($model)) {
            // Train initial model if none exists
            $training_data = $this->get_training_data();
            if (!empty($training_data)) {
                $model = $this->linear_regression_train($training_data);
                update_option('scs_ml_model', $model);
            }
        }
        
        return $model;
    }
    
    /**
     * Calculate prediction confidence
     */
    private function calculate_confidence($features, $model) {
        // Simple confidence calculation based on feature completeness
        $total_features = count($model['feature_names'] ?? []);
        $present_features = 0;
        
        foreach ($model['feature_names'] ?? [] as $feature_name) {
            if (isset($features[$feature_name]) && $features[$feature_name] !== 0) {
                $present_features++;
            }
        }
        
        return $total_features > 0 ? ($present_features / $total_features) * 100 : 50;
    }
    
    /**
     * Generate ML-based recommendations
     */
    private function generate_ml_recommendations($features, $predicted_score) {
        $recommendations = [];
        
        if ($features['title_length'] < 30) {
            $recommendations[] = 'Consider making your title longer for better engagement';
        }
        
        if ($features['title_length'] > 60) {
            $recommendations[] = 'Your title might be too long - consider shortening it';
        }
        
        if ($features['content_length'] < 300) {
            $recommendations[] = 'Your content is quite short - consider adding more detail';
        }
        
        if ($features['avg_words_per_sentence'] > 20) {
            $recommendations[] = 'Your sentences are quite long - consider breaking them up for better readability';
        }
        
        if ($features['sentiment_score'] < 0) {
            $recommendations[] = 'Your content has negative sentiment - consider adding more positive language';
        }
        
        if (isset($features['is_weekend']) && $features['is_weekend'] === 1) {
            $recommendations[] = 'Weekend posts typically get lower engagement - consider scheduling for weekdays';
        }
        
        if ($predicted_score < 50) {
            $recommendations[] = 'This content is predicted to have low performance - consider revising before publishing';
        }
        
        return $recommendations;
    }
    
    /**
     * Calculate model accuracy
     */
    private function calculate_model_accuracy($model, $training_data) {
        if (empty($training_data)) {
            return 0;
        }
        
        $correct_predictions = 0;
        $total_predictions = count($training_data);
        
        foreach ($training_data as $sample) {
            $predicted = $this->linear_regression_predict($sample['features'], $model);
            $actual = $sample['target'];
            
            // Consider prediction correct if within 20% of actual value
            $error_margin = abs($predicted - $actual) / max($actual, 1);
            if ($error_margin <= 0.2) {
                $correct_predictions++;
            }
        }
        
        return ($correct_predictions / $total_predictions) * 100;
    }
}
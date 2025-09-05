<?php
/**
 * Machine Learning Scheduler
 *
 * @since      1.0.0
 * @package    Smart_Content_Scheduler
 */

class Smart_Content_Scheduler_ML_Scheduler {

    /**
     * Machine learning model for schedule optimization
     *
     * @var object
     */
    private $schedule_model;
    
    /**
     * Model file path
     *
     * @var string
     */
    private $model_path;
    
    /**
     * Database table names
     *
     * @var array
     */
    private $tables;
    
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
        global $wpdb;
        
        $this->model_path = SCS_PLUGIN_DIR . 'ml/models/schedule_model.ser';
        
        $this->tables = [
            'schedules' => $wpdb->prefix . 'scs_schedules',
            'performance' => $wpdb->prefix . 'scs_performance',
            'ml_data' => $wpdb->prefix . 'scs_ml_data',
            'ab_tests' => $wpdb->prefix . 'scs_ab_tests',
        ];
        
        // Check if PHP-ML is available
        if (file_exists(SCS_PLUGIN_DIR . 'vendor/autoload.php')) {
            require_once SCS_PLUGIN_DIR . 'vendor/autoload.php';
            
            if (class_exists('Phpml\Classification\SVC')) {
                $this->ml_available = true;
                
                // Import PHP-ML classes
                use Phpml\Classification\SVC;
                use Phpml\SupportVectorMachine\Kernel;
                use Phpml\ModelManager;
                use Phpml\Dataset\ArrayDataset;
                use Phpml\Preprocessing\Normalizer;
                
                // Initialize or load model
                $this->init_model();
            }
        }
    }

    /**
     * Initialize or load the ML model
     */
    private function init_model() {
        if (!$this->ml_available) {
            return;
        }
        
        if (file_exists($this->model_path)) {
            try {
                $modelManager = new \Phpml\ModelManager();
                $this->schedule_model = $modelManager->restoreFromFile($this->model_path);
            } catch (Exception $e) {
                // If error loading model, create a new one
                $this->create_new_model();
            }
        } else {
            $this->create_new_model();
        }
    }

    /**
     * Create a new ML model
     */
    private function create_new_model() {
        if (!$this->ml_available) {
            return;
        }
        
        // Create a basic SVM model for scheduling
        $this->schedule_model = new \Phpml\Classification\SVC(\Phpml\SupportVectorMachine\Kernel::RBF, 1.0, 3, 0.1);
        
        // Save the initial model
        try {
            if (!is_dir(dirname($this->model_path))) {
                mkdir(dirname($this->model_path), 0755, true);
            }
            
            $modelManager = new \Phpml\ModelManager();
            $modelManager->saveToFile($this->schedule_model, $this->model_path);
        } catch (Exception $e) {
            error_log('Failed to save initial model: ' . $e->getMessage());
        }
    }

    /**
     * Train the ML models using collected data
     */
    public function train_models() {
        if (!$this->ml_available) {
            error_log('ML training skipped: PHP-ML library not available');
            return false;
        }
        
        global $wpdb;
        
        // Get training data from our ML data table
        $training_data = $wpdb->get_results(
            "SELECT day_of_week, hour_of_day, content_length, content_type, category, engagement_score 
             FROM {$this->tables['ml_data']} 
             WHERE engagement_score IS NOT NULL",
            ARRAY_A
        );
        
        if (empty($training_data) || count($training_data) < 10) {
            error_log('Not enough training data available yet.');
            return false;
        }
        
        // Prepare samples and targets
        $samples = [];
        $targets = [];
        
        foreach ($training_data as $data) {
            // Convert category to numeric using one-hot encoding
            $categories = $this->get_all_categories();
            $category_features = array_fill(0, count($categories), 0);
            $category_index = array_search($data['category'], $categories);
            if ($category_index !== false) {
                $category_features[$category_index] = 1;
            }
            
            // Convert content type to numeric
            $content_types = ['post', 'page', 'product', 'custom'];
            $content_type_index = array_search($data['content_type'], $content_types);
            $content_type_numeric = ($content_type_index !== false) ? $content_type_index : 0;
            
            // Build feature vector
            $sample = [
                (int) $data['day_of_week'],
                (int) $data['hour_of_day'],
                (int) $data['content_length'] / 1000, // Normalize content length
                (int) $content_type_numeric
            ];
            
            // Add category features
            $sample = array_merge($sample, $category_features);
            
            $samples[] = $sample;
            
            // Target is 1 for high engagement (above median), 0 for low
            $targets[] = ($data['engagement_score'] >= 0.5) ? 1 : 0;
        }
        
        // Normalize features
        $normalizer = new \Phpml\Preprocessing\Normalizer();
        $samples = $normalizer->normalize($samples);
        
        // Create dataset
        $dataset = new \Phpml\Dataset\ArrayDataset($samples, $targets);
        
        // Train model
        $this->schedule_model = new \Phpml\Classification\SVC(\Phpml\SupportVectorMachine\Kernel::RBF, 1.0, 3, 0.1);
        $this->schedule_model->train($samples, $targets);
        
        // Save model
        try {
            $modelManager = new \Phpml\ModelManager();
            $modelManager->saveToFile($this->schedule_model, $this->model_path);
            
            return true;
        } catch (Exception $e) {
            error_log('Failed to save trained model: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get optimal publishing times for a post
     *
     * @param int $post_id Post ID
     * @param array $post_data Post data including content, category, etc.
     * @return array Array of optimal times with confidence scores
     */
    public function get_optimal_times($post_id, $post_data) {
        // If ML is not available, use simple heuristics
        if (!$this->ml_available) {
            return $this->get_heuristic_times();
        }
        
        // Get post category
        $category = isset($post_data['category']) ? $post_data['category'] : $this->get_post_category($post_id);
        
        // Get content length
        $content_length = isset($post_data['content_length']) ? $post_data['content_length'] : strlen(strip_tags(get_post_field('post_content', $post_id)));
        
        // Get content type
        $content_type = isset($post_data['content_type']) ? $post_data['content_type'] : get_post_type($post_id);
        
        // Convert category to feature
        $categories = $this->get_all_categories();
        $category_features = array_fill(0, count($categories), 0);
        $category_index = array_search($category, $categories);
        if ($category_index !== false) {
            $category_features[$category_index] = 1;
        }
        
        // Convert content type to numeric
        $content_types = ['post', 'page', 'product', 'custom'];
        $content_type_index = array_search($content_type, $content_types);
        $content_type_numeric = ($content_type_index !== false) ? $content_type_index : 0;
        
        // Generate predictions for all hours in the next 7 days
        $optimal_times = [];
        $now = current_time('timestamp');
        $normalizer = new \Phpml\Preprocessing\Normalizer();
        
        for ($day = 0; $day < 7; $day++) {
            $day_timestamp = $now + ($day * 86400);
            $day_of_week = (int) date('w', $day_timestamp);
            
            for ($hour = 0; $hour < 24; $hour++) {
                // Build feature vector
                $sample = [
                    $day_of_week,
                    $hour,
                    $content_length / 1000, // Normalize content length
                    $content_type_numeric
                ];
                
                // Add category features
                $sample = array_merge($sample, $category_features);
                
                // Normalize sample
                $sample = $normalizer->normalize([$sample])[0];
                
                // Predict engagement likelihood
                try {
                    $prediction = $this->schedule_model->predict($sample);
                    
                    // Get prediction confidence using SVM decision function
                    $confidence = abs($this->schedule_model->predictProbability($sample)[1]);
                    
                    if ($prediction === 1) {
                        $timestamp = strtotime(date('Y-m-d', $day_timestamp) . ' ' . $hour . ':00:00');
                        
                        $optimal_times[] = [
                            'timestamp' => $timestamp,
                            'datetime' => date('Y-m-d H:i:s', $timestamp),
                            'confidence' => $confidence,
                            'day_name' => date('l', $timestamp),
                            'hour_display' => date('g:i A', $timestamp)
                        ];
                    }
                } catch (Exception $e) {
                    // If prediction fails, use fallback method
                    continue;
                }
            }
        }
        
        // Sort by confidence
        usort($optimal_times, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });
        
        // Return top 5 times
        return array_slice($optimal_times, 0, 5);
    }
    
    /**
     * Get optimal times using simple heuristics when ML is not available
     */
    private function get_heuristic_times() {
        $optimal_times = [];
        $now = current_time('timestamp');
        
        // Common optimal publishing times based on general social media engagement patterns
        $optimal_hours = [
            1 => [9, 12, 15], // Monday: 9am, 12pm, 3pm
            2 => [8, 10, 14], // Tuesday: 8am, 10am, 2pm
            3 => [9, 13, 19], // Wednesday: 9am, 1pm, 7pm
            4 => [8, 14, 18], // Thursday: 8am, 2pm, 6pm
            5 => [9, 12, 15], // Friday: 9am, 12pm, 3pm
            6 => [11, 14, 19], // Saturday: 11am, 2pm, 7pm
            0 => [11, 15, 20]  // Sunday: 11am, 3pm, 8pm
        ];
        
        // Generate times for the next 7 days
        for ($day = 0; $day < 7; $day++) {
            $day_timestamp = $now + ($day * 86400);
            $day_of_week = (int) date('w', $day_timestamp);
            
            foreach ($optimal_hours[$day_of_week] as $hour) {
                $timestamp = strtotime(date('Y-m-d', $day_timestamp) . ' ' . $hour . ':00:00');
                
                // Skip times that are in the past
                if ($timestamp <= $now) {
                    continue;
                }
                
                $optimal_times[] = [
                    'timestamp' => $timestamp,
                    'datetime' => date('Y-m-d H:i:s', $timestamp),
                    'confidence' => 0.5 + (mt_rand(0, 20) / 100), // Random confidence between 0.5-0.7
                    'day_name' => date('l', $timestamp),
                    'hour_display' => date('g:i A', $timestamp)
                ];
            }
        }
        
        // Sort by timestamp (chronological order)
        usort($optimal_times, function($a, $b) {
            return $a['timestamp'] <=> $b['timestamp'];
        });
        
        // Return up to 5 times
        return array_slice($optimal_times, 0, 5);
    }

    /**
     * Check post performance and reschedule if needed
     */
    public function check_and_reschedule() {
        global $wpdb;
        
        // Get posts scheduled in the last 24 hours
        $recent_posts = $wpdb->get_results(
            "SELECT s.post_id, s.scheduled_time, p.performance_score, p.views 
             FROM {$this->tables['schedules']} s
             LEFT JOIN {$this->tables['performance']} p ON s.post_id = p.post_id
             WHERE s.scheduled_time < NOW() 
             AND s.scheduled_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             AND s.is_rescheduled = 0",
            ARRAY_A
        );
        
        foreach ($recent_posts as $post) {
            // If performance is poor (below threshold)
            if ($post['performance_score'] < 0.3 && $post['views'] > 10) {
                // Get new optimal time
                $post_data = [
                    'category' => $this->get_post_category($post['post_id']),
                    'content_length' => strlen(strip_tags(get_post_field('post_content', $post['post_id']))),
                    'content_type' => get_post_type($post['post_id'])
                ];
                
                $optimal_times = $this->get_optimal_times($post['post_id'], $post_data);
                
                if (!empty($optimal_times)) {
                    // Use the best time
                    $new_time = $optimal_times[0]['datetime'];
                    
                    // Update post date
                    wp_update_post([
                        'ID' => $post['post_id'],
                        'post_date' => $new_time,
                        'post_date_gmt' => get_gmt_from_date($new_time),
                    ]);
                    
                    // Update schedule record
                    $wpdb->update(
                        $this->tables['schedules'],
                        [
                            'scheduled_time' => $new_time,
                            'is_rescheduled' => 1,
                            'original_time' => $post['scheduled_time'],
                            'ai_confidence' => $optimal_times[0]['confidence'],
                            'schedule_reason' => 'auto_reschedule_low_performance'
                        ],
                        ['post_id' => $post['post_id']],
                        ['%s', '%d', '%s', '%f', '%s'],
                        ['%d']
                    );
                    
                    // Log the rescheduling
                    error_log("Rescheduled post {$post['post_id']} from {$post['scheduled_time']} to {$new_time} due to low performance.");
                }
            }
        }
    }

    /**
     * Get all categories in the system
     *
     * @return array Array of category names
     */
    private function get_all_categories() {
        $categories = get_categories(['hide_empty' => false]);
        return array_map(function($cat) {
            return $cat->name;
        }, $categories);
    }

    /**
     * Get post category
     *
     * @param int $post_id Post ID
     * @return string Main category name
     */
    private function get_post_category($post_id) {
        $categories = get_the_category($post_id);
        if (!empty($categories)) {
            return $categories[0]->name;
        }
        return 'uncategorized';
    }
}
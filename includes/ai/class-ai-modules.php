<?php
/**
 * AI Module Loader
 *
 * @package Smart_Content_Scheduler
 * @subpackage AI_Features
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Module Loader
 * Manages loading of AI feature modules
 */
class SCS_AI_Modules {
    
    /**
     * Module instances
     */
    private $modules = array();
    
    /**
     * Initialize the AI modules
     */
    public function __construct() {
        // Register the AI module init hook
        add_action('plugins_loaded', array($this, 'init_modules'));
        
        // Add settings tab for AI features
        add_filter('scs_settings_tabs', array($this, 'add_ai_settings_tab'), 10, 1);
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
    }
    
    /**
     * Initialize AI modules
     */
    public function init_modules() {
        // Load required files
        $this->load_files();
        
        // Initialize modules if enabled
        if ($this->is_ai_features_enabled()) {
            $this->init_ml_integration();
            $this->init_nlp_analyzer();
            $this->init_social_api();
            $this->init_ab_testing();
            $this->init_seasonal_analyzer();
            $this->init_competitor_analysis();
        }
    }
    
    /**
     * Load required files
     */
    private function load_files() {
        // Get the includes directory
        $dir = plugin_dir_path(__FILE__);
        
        // Load module files
        require_once $dir . 'class-ml-integration.php';
        require_once $dir . 'class-nlp-analyzer.php';
        require_once $dir . 'class-social-api-connector.php';
        require_once $dir . 'class-ab-testing.php';
        require_once $dir . 'class-seasonal-analyzer.php';
        require_once $dir . 'class-competitor-analysis.php';
    }
    
    /**
     * Initialize ML integration
     */
    private function init_ml_integration() {
        if ($this->is_module_enabled('ml')) {
            $this->modules['ml'] = new SCS_ML_Integration();
        }
    }
    
    /**
     * Initialize NLP analyzer
     */
    private function init_nlp_analyzer() {
        if ($this->is_module_enabled('nlp')) {
            $this->modules['nlp'] = new SCS_NLP_Analyzer();
        }
    }
    
    /**
     * Initialize social API connector
     */
    private function init_social_api() {
        if ($this->is_module_enabled('social_api')) {
            $this->modules['social_api'] = new SCS_Social_API_Connector();
        }
    }
    
    /**
     * Initialize A/B testing
     */
    private function init_ab_testing() {
        if ($this->is_module_enabled('ab_testing')) {
            $this->modules['ab_testing'] = new SCS_AB_Testing();
        }
    }
    
    /**
     * Initialize seasonal analyzer
     */
    private function init_seasonal_analyzer() {
        if ($this->is_module_enabled('seasonal')) {
            $this->modules['seasonal'] = new SCS_Seasonal_Analyzer();
        }
    }
    
    /**
     * Initialize competitor analysis
     */
    private function init_competitor_analysis() {
        if ($this->is_module_enabled('competitor')) {
            $this->modules['competitor'] = new SCS_Competitor_Analysis();
        }
    }
    
    /**
     * Check if AI features are enabled
     * 
     * @return bool True if enabled, false otherwise
     */
    public function is_ai_features_enabled() {
        return get_option('scs_enable_ai_features', false);
    }
    
    /**
     * Check if specific module is enabled
     * 
     * @param string $module Module name
     * @return bool True if enabled, false otherwise
     */
    public function is_module_enabled($module) {
        $enabled_modules = get_option('scs_enabled_ai_modules', array());
        return in_array($module, $enabled_modules);
    }
    
    /**
     * Add AI settings tab
     * 
     * @param array $tabs Existing tabs
     * @return array Updated tabs
     */
    public function add_ai_settings_tab($tabs) {
        $tabs['ai'] = 'AI Features';
        return $tabs;
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Register AI features master switch
        register_setting(
            'scs-settings-ai',
            'scs_enable_ai_features',
            array(
                'type' => 'boolean',
                'description' => 'Enable AI features',
                'default' => false,
            )
        );
        
        // Register enabled modules setting
        register_setting(
            'scs-settings-ai',
            'scs_enabled_ai_modules',
            array(
                'type' => 'array',
                'description' => 'Enabled AI modules',
                'default' => array(),
                'sanitize_callback' => array($this, 'sanitize_enabled_modules'),
            )
        );
        
        // Add settings section
        add_settings_section(
            'scs_ai_settings',
            'AI Features Settings',
            array($this, 'render_settings_section'),
            'scs-settings-ai'
        );
        
        // Add master switch field
        add_settings_field(
            'scs_enable_ai_features',
            'Enable AI Features',
            array($this, 'render_enable_field'),
            'scs-settings-ai',
            'scs_ai_settings'
        );
        
        // Add module selection field
        add_settings_field(
            'scs_enabled_ai_modules',
            'Enable Specific Modules',
            array($this, 'render_modules_field'),
            'scs-settings-ai',
            'scs_ai_settings'
        );
    }
    
    /**
     * Render settings section
     */
    public function render_settings_section() {
        echo '<p>Configure AI features and modules</p>';
    }
    
    /**
     * Render enable field
     */
    public function render_enable_field() {
        $enabled = get_option('scs_enable_ai_features', false);
        
        echo '<label>';
        echo '<input type="checkbox" name="scs_enable_ai_features" value="1" ' . checked($enabled, true, false) . ' />';
        echo ' Enable all AI features</label>';
    }
    
    /**
     * Render modules field
     */
    public function render_modules_field() {
        $enabled_modules = get_option('scs_enabled_ai_modules', array());
        
        $modules = array(
            'ml' => 'Machine Learning (PHP-ML) Integration',
            'nlp' => 'Natural Language Processing',
            'social_api' => 'Social Media API Integration',
            'ab_testing' => 'A/B Testing Automation',
            'seasonal' => 'Seasonal Pattern Recognition',
            'competitor' => 'Competitor Analysis',
        );
        
        foreach ($modules as $module => $label) {
            echo '<label>';
            echo '<input type="checkbox" name="scs_enabled_ai_modules[]" value="' . esc_attr($module) . '" ' . checked(in_array($module, $enabled_modules), true, false) . ' />';
            echo ' ' . esc_html($label) . '</label><br>';
        }
    }
    
    /**
     * Sanitize enabled modules
     * 
     * @param array $modules Enabled modules
     * @return array Sanitized modules
     */
    public function sanitize_enabled_modules($modules) {
        $valid_modules = array('ml', 'nlp', 'social_api', 'ab_testing', 'seasonal', 'competitor');
        
        return array_intersect($modules, $valid_modules);
    }
    
    /**
     * Get module instance
     * 
     * @param string $module Module name
     * @return object|null Module instance or null if not loaded
     */
    public function get_module($module) {
        return isset($this->modules[$module]) ? $this->modules[$module] : null;
    }
}

// Initialize AI modules
new SCS_AI_Modules();
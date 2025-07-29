<?php
/**
 * Configuration Manager Class
 *
 * @package    Askro
 * @subpackage Core/Config
 * @since      1.0.0
 * @author     Arashdi <arashdi@wratcliff.dev>
 * @copyright  2025 William Ratcliff
 * @license    GPL-3.0-or-later
 * @link       https://arashdi.com
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Askro Configuration Manager Class
 *
 * Centralizes all plugin configuration and settings management
 *
 * @since 1.0.0
 */
class Askro_Config_Manager {

    /**
     * Configuration instance
     *
     * @var Askro_Config_Manager
     * @since 1.0.0
     */
    private static $instance = null;

    /**
     * Configuration cache
     *
     * @var array
     * @since 1.0.0
     */
    private $config_cache = [];

    /**
     * Default configuration
     *
     * @var array
     * @since 1.0.0
     */
    private $defaults = [
        // General settings
        'enable_debug' => false,
        'enable_logging' => false,
        'cache_enabled' => true,
        'cache_duration' => 3600,
        
        // XP and gamification
        'xp_question_asked' => 10,
        'xp_answer_submitted' => 20,
        'xp_best_answer' => 50,
        'xp_comment_added' => 5,
        'xp_vote_cast' => 1,
        'xp_vote_received' => 3,
        
        // Voting settings
        'enable_multi_voting' => true,
        'vote_types' => [
            'useful' => 3,
            'innovative' => 2,
            'well_researched' => 2,
            'incorrect' => -2,
            'redundant' => -1
        ],
        
        // Display settings
        'questions_per_page' => 15,
        'answers_per_page' => 10,
        'comments_per_page' => 20,
        'excerpt_length' => 150,
        
        // API settings
        'api_enabled' => true,
        'api_rate_limit' => 100,
        'api_rate_limit_window' => 3600,
        
        // Security settings
        'enable_captcha' => false,
        'max_uploads_per_post' => 5,
        'max_file_size' => 5242880, // 5MB
        'allowed_file_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
        
        // Notification settings
        'email_notifications' => true,
        'browser_notifications' => true,
        'notification_sound' => true,
        
        // Performance settings
        'enable_caching' => true,
        'cache_duration' => 3600,
        'enable_minification' => true,
        'enable_compression' => true
    ];

    /**
     * Get configuration instance
     *
     * @return Askro_Config_Manager
     * @since 1.0.0
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->load_configuration();
    }

    /**
     * Load configuration from database
     *
     * @since 1.0.0
     */
    private function load_configuration() {
        $saved_config = get_option('askro_configuration', []);
        $this->config_cache = wp_parse_args($saved_config, $this->defaults);
    }

    /**
     * Get configuration value
     *
     * @param string $key Configuration key
     * @param mixed $default Default value
     * @return mixed Configuration value
     * @since 1.0.0
     */
    public function get($key, $default = null) {
        if (isset($this->config_cache[$key])) {
            return $this->config_cache[$key];
        }
        
        return $default !== null ? $default : ($this->defaults[$key] ?? null);
    }

    /**
     * Set configuration value
     *
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @since 1.0.0
     */
    public function set($key, $value) {
        $this->config_cache[$key] = $value;
        $this->save_configuration();
    }

    /**
     * Set multiple configuration values
     *
     * @param array $config Configuration array
     * @since 1.0.0
     */
    public function set_multiple($config) {
        foreach ($config as $key => $value) {
            $this->config_cache[$key] = $value;
        }
        $this->save_configuration();
    }

    /**
     * Save configuration to database
     *
     * @since 1.0.0
     */
    private function save_configuration() {
        update_option('askro_configuration', $this->config_cache);
    }

    /**
     * Reset configuration to defaults
     *
     * @since 1.0.0
     */
    public function reset_to_defaults() {
        $this->config_cache = $this->defaults;
        $this->save_configuration();
    }

    /**
     * Get all configuration
     *
     * @return array All configuration
     * @since 1.0.0
     */
    public function get_all() {
        return $this->config_cache;
    }

    /**
     * Get default configuration
     *
     * @return array Default configuration
     * @since 1.0.0
     */
    public function get_defaults() {
        return $this->defaults;
    }

    /**
     * Check if configuration key exists
     *
     * @param string $key Configuration key
     * @return bool
     * @since 1.0.0
     */
    public function has($key) {
        return isset($this->config_cache[$key]);
    }

    /**
     * Remove configuration key
     *
     * @param string $key Configuration key
     * @since 1.0.0
     */
    public function remove($key) {
        unset($this->config_cache[$key]);
        $this->save_configuration();
    }

    /**
     * Get XP settings
     *
     * @return array XP configuration
     * @since 1.0.0
     */
    public function get_xp_settings() {
        return [
            'question_asked' => $this->get('xp_question_asked'),
            'answer_submitted' => $this->get('xp_answer_submitted'),
            'best_answer' => $this->get('xp_best_answer'),
            'comment_added' => $this->get('xp_comment_added'),
            'vote_cast' => $this->get('xp_vote_cast'),
            'vote_received' => $this->get('xp_vote_received')
        ];
    }

    /**
     * Get voting settings
     *
     * @return array Voting configuration
     * @since 1.0.0
     */
    public function get_voting_settings() {
        return [
            'enable_multi_voting' => $this->get('enable_multi_voting'),
            'vote_types' => $this->get('vote_types')
        ];
    }

    /**
     * Get display settings
     *
     * @return array Display configuration
     * @since 1.0.0
     */
    public function get_display_settings() {
        return [
            'questions_per_page' => $this->get('questions_per_page'),
            'answers_per_page' => $this->get('answers_per_page'),
            'comments_per_page' => $this->get('comments_per_page'),
            'excerpt_length' => $this->get('excerpt_length')
        ];
    }

    /**
     * Get API settings
     *
     * @return array API configuration
     * @since 1.0.0
     */
    public function get_api_settings() {
        return [
            'enabled' => $this->get('api_enabled'),
            'rate_limit' => $this->get('api_rate_limit'),
            'rate_limit_window' => $this->get('api_rate_limit_window')
        ];
    }

    /**
     * Get security settings
     *
     * @return array Security configuration
     * @since 1.0.0
     */
    public function get_security_settings() {
        return [
            'enable_captcha' => $this->get('enable_captcha'),
            'max_uploads_per_post' => $this->get('max_uploads_per_post'),
            'max_file_size' => $this->get('max_file_size'),
            'allowed_file_types' => $this->get('allowed_file_types')
        ];
    }

    /**
     * Get performance settings
     *
     * @return array Performance configuration
     * @since 1.0.0
     */
    public function get_performance_settings() {
        return [
            'enable_caching' => $this->get('enable_caching'),
            'cache_duration' => $this->get('cache_duration'),
            'enable_minification' => $this->get('enable_minification'),
            'enable_compression' => $this->get('enable_compression')
        ];
    }

    /**
     * Validate configuration value
     *
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @return bool|WP_Error
     * @since 1.0.0
     */
    public function validate_value($key, $value) {
        switch ($key) {
            case 'questions_per_page':
            case 'answers_per_page':
            case 'comments_per_page':
                return is_numeric($value) && $value > 0 && $value <= 100;
                
            case 'excerpt_length':
                return is_numeric($value) && $value > 0 && $value <= 1000;
                
            case 'cache_duration':
                return is_numeric($value) && $value >= 0;
                
            case 'api_rate_limit':
                return is_numeric($value) && $value > 0;
                
            case 'max_file_size':
                return is_numeric($value) && $value > 0;
                
            case 'enable_debug':
            case 'enable_logging':
            case 'cache_enabled':
            case 'enable_multi_voting':
            case 'api_enabled':
            case 'enable_captcha':
            case 'email_notifications':
            case 'browser_notifications':
            case 'notification_sound':
            case 'enable_caching':
            case 'enable_minification':
            case 'enable_compression':
                return is_bool($value);
                
            case 'vote_types':
                return is_array($value);
                
            case 'allowed_file_types':
                return is_array($value) && !empty($value);
                
            default:
                return true;
        }
    }

    /**
     * Initialize configuration
     *
     * @since 1.0.0
     */
    public function init() {
        // Ensure configuration is loaded
        if (empty($this->config_cache)) {
            $this->load_configuration();
        }
        
        // Add configuration hooks
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wp_ajax_askro_save_config', [$this, 'handle_save_config']);
    }

    /**
     * Register WordPress settings
     *
     * @since 1.0.0
     */
    public function register_settings() {
        register_setting('askro_configuration', 'askro_configuration', [
            'sanitize_callback' => [$this, 'sanitize_configuration']
        ]);
    }

    /**
     * Sanitize configuration
     *
     * @param array $input Input data
     * @return array Sanitized data
     * @since 1.0.0
     */
    public function sanitize_configuration($input) {
        $sanitized = [];
        
        foreach ($input as $key => $value) {
            if ($this->validate_value($key, $value)) {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Handle AJAX configuration save
     *
     * @since 1.0.0
     */
    public function handle_save_config() {
        if (!wp_verify_nonce($_POST['nonce'], 'askro_config_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'askro')]);
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Insufficient permissions', 'askro')]);
        }

        $config = $_POST['config'] ?? [];
        $sanitized_config = $this->sanitize_configuration($config);
        
        $this->set_multiple($sanitized_config);
        
        wp_send_json_success([
            'message' => __('Configuration saved successfully', 'askro'),
            'config' => $sanitized_config
        ]);
    }
} 

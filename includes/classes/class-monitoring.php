<?php
/**
 * Askro Monitoring Class
 * 
 * Provides monitoring and health check functionality for the Askro plugin
 * 
 * @package Askro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Askro_Monitoring Class
 * 
 * Handles monitoring, health checks, and performance tracking for the plugin
 * 
 * @since 1.0.0
 */
class Askro_Monitoring {
    
    /**
     * Error handler instance
     */
    private $error_handler;
    
    /**
     * Testing instance
     */
    private $testing;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->error_handler = Askro_Error_Handler::get_instance();
        $this->testing = new Askro_Testing();
        
        // Register health check endpoints
        add_action('rest_api_init', [$this, 'register_health_endpoints']);
        
        // Schedule monitoring tasks
        add_action('init', [$this, 'schedule_monitoring_tasks']);
    }
    
    /**
     * Register health check endpoints
     */
    public function register_health_endpoints() {
        register_rest_route('askro/v1', '/health', [
            'methods' => 'GET',
            'callback' => [$this, 'health_check_endpoint'],
            'permission_callback' => [$this, 'health_check_permission'],
            'args' => []
        ]);
        
        register_rest_route('askro/v1', '/health/detailed', [
            'methods' => 'GET',
            'callback' => [$this, 'detailed_health_check_endpoint'],
            'permission_callback' => [$this, 'admin_permission'],
            'args' => []
        ]);
        
        register_rest_route('askro/v1', '/monitoring/stats', [
            'methods' => 'GET',
            'callback' => [$this, 'monitoring_stats_endpoint'],
            'permission_callback' => [$this, 'admin_permission'],
            'args' => []
        ]);
    }
    
    /**
     * Health check permission
     */
    public function health_check_permission() {
        return true; // Public endpoint
    }
    
    /**
     * Admin permission
     */
    public function admin_permission() {
        return current_user_can('manage_options');
    }
    
    /**
     * Health check endpoint
     */
    public function health_check_endpoint($request) {
        $health_status = $this->run_basic_health_check();
        
        return new WP_REST_Response($health_status, 200);
    }
    
    /**
     * Detailed health check endpoint
     */
    public function detailed_health_check_endpoint($request) {
        $detailed_status = $this->run_detailed_health_check();
        
        return new WP_REST_Response($detailed_status, 200);
    }
    
    /**
     * Monitoring stats endpoint
     */
    public function monitoring_stats_endpoint($request) {
        $stats = $this->get_monitoring_stats();
        
        return new WP_REST_Response($stats, 200);
    }
    
    /**
     * Run basic health check
     */
    public function run_basic_health_check() {
        $status = [
            'status' => 'healthy',
            'timestamp' => current_time('mysql'),
            'version' => ASKRO_VERSION ?? '1.0.0'
        ];
        
        // Database check
        global $wpdb;
        $db_check = $wpdb->get_var("SELECT 1");
        if (!$db_check) {
            $status['status'] = 'unhealthy';
            $status['database'] = 'error';
        } else {
            $status['database'] = 'healthy';
        }
        
        // Plugin files check
        $plugin_file = ASKRO_PLUGIN_FILE;
        if (!file_exists($plugin_file)) {
            $status['status'] = 'unhealthy';
            $status['files'] = 'error';
        } else {
            $status['files'] = 'healthy';
        }
        
        // API check
        $api_response = wp_remote_get(home_url('/wp-json/askro/v1/questions'));
        if (is_wp_error($api_response)) {
            $status['status'] = 'unhealthy';
            $status['api'] = 'error';
        } else {
            $status['api'] = 'healthy';
        }
        
        return $status;
    }
    
    /**
     * Run detailed health check
     */
    public function run_detailed_health_check() {
        $status = $this->run_basic_health_check();
        
        // Add detailed checks
        $status['detailed'] = [
            'database_tables' => $this->check_database_tables(),
            'api_endpoints' => $this->check_api_endpoints(),
            'security_features' => $this->check_security_features(),
            'performance' => $this->check_performance(),
            'error_logs' => $this->check_error_logs()
        ];
        
        return $status;
    }
    
    /**
     * Check database tables
     */
    private function check_database_tables() {
        global $wpdb;
        
        $required_tables = [
            $wpdb->prefix . 'askro_votes',
            $wpdb->prefix . 'askro_comments',
            $wpdb->prefix . 'askro_user_points',
            $wpdb->prefix . 'askro_user_badges',
            $wpdb->prefix . 'askro_notifications',
            $wpdb->prefix . 'askro_analytics',
            $wpdb->prefix . 'askro_security_logs'
        ];
        
        $results = [];
        
        foreach ($required_tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            $results[$table] = $exists ? 'exists' : 'missing';
        }
        
        return $results;
    }
    
    /**
     * Check API endpoints
     */
    private function check_api_endpoints() {
        $endpoints = [
            '/wp-json/askro/v1/questions',
            '/wp-json/askro/v1/answers',
            '/wp-json/askro/v1/users',
            '/wp-json/askro/v1/votes'
        ];
        
        $results = [];
        
        foreach ($endpoints as $endpoint) {
            $response = wp_remote_get(home_url($endpoint));
            
            if (is_wp_error($response)) {
                $results[$endpoint] = 'error';
            } else {
                $code = wp_remote_retrieve_response_code($response);
                $results[$endpoint] = $code < 400 ? 'healthy' : 'error';
            }
        }
        
        return $results;
    }
    
    /**
     * Check security features
     */
    private function check_security_features() {
        $results = [
            'nonce_creation' => wp_create_nonce('test') ? 'working' : 'error',
            'nonce_verification' => wp_verify_nonce(wp_create_nonce('test'), 'test') ? 'working' : 'error',
            'user_capabilities' => current_user_can('read') ? 'working' : 'error',
            'sanitization' => sanitize_text_field('<script>test</script>') === 'test' ? 'working' : 'error'
        ];
        
        return $results;
    }
    
    /**
     * Check performance
     */
    private function check_performance() {
        $start_time = microtime(true);
        
        // Test database query performance
        global $wpdb;
        $wpdb->get_results("SELECT * FROM {$wpdb->posts} WHERE post_type = 'askro_question' LIMIT 1");
        
        $query_time = microtime(true) - $start_time;
        
        return [
            'database_query_time' => round($query_time * 1000, 2) . 'ms',
            'performance_status' => $query_time < 1.0 ? 'good' : 'slow'
        ];
    }
    
    /**
     * Check error logs
     */
    private function check_error_logs() {
        $log_stats = $this->error_handler->get_log_stats();
        
        return [
            'total_errors' => $log_stats['total'],
            'recent_errors' => $log_stats['recent_errors'],
            'critical_errors' => $log_stats['critical_errors'],
            'error_levels' => $log_stats['by_level']
        ];
    }
    
    /**
     * Get monitoring stats
     */
    public function get_monitoring_stats() {
        global $wpdb;
        
        $stats = [
            'timestamp' => current_time('mysql'),
            'system' => [
                'php_version' => PHP_VERSION,
                'wordpress_version' => get_bloginfo('version'),
                'plugin_version' => ASKRO_VERSION ?? '1.0.0',
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time')
            ],
            'database' => [
                'total_questions' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'askro_question'"),
                'total_answers' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'askro_answer'"),
                'total_users' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),
                'total_votes' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}askro_votes"),
                'total_comments' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}askro_comments")
            ],
            'performance' => [
                'average_response_time' => $this->calculate_average_response_time(),
                'database_query_count' => $this->get_database_query_count(),
                'memory_usage' => memory_get_usage(true),
                'peak_memory_usage' => memory_get_peak_usage(true)
            ],
            'errors' => $this->error_handler->get_log_stats()
        ];
        
        return $stats;
    }
    
    /**
     * Calculate average response time
     */
    private function calculate_average_response_time() {
        // This is a simplified calculation
        // In a real implementation, you'd track actual response times
        return '0.5s'; // Placeholder
    }
    
    /**
     * Get database query count
     */
    private function get_database_query_count() {
        global $wpdb;
        
        if (defined('SAVEQUERIES') && SAVEQUERIES) {
            return count($wpdb->queries);
        }
        
        return 'unknown';
    }
    
    /**
     * Schedule monitoring tasks
     */
    public function schedule_monitoring_tasks() {
        if (!wp_next_scheduled('askro_health_check')) {
            wp_schedule_event(time(), 'hourly', 'askro_health_check');
        }
        
        if (!wp_next_scheduled('askro_error_cleanup')) {
            wp_schedule_event(time(), 'daily', 'askro_error_cleanup');
        }
        
        // Add action hooks
        add_action('askro_health_check', [$this, 'run_scheduled_health_check']);
        add_action('askro_error_cleanup', [$this, 'cleanup_old_errors']);
    }
    
    /**
     * Run scheduled health check
     */
    public function run_scheduled_health_check() {
        $health_status = $this->run_basic_health_check();
        
        if ($health_status['status'] === 'unhealthy') {
            $this->error_handler->critical('Scheduled health check failed', $health_status);
            
            // Send notification to admin
            $this->send_health_check_notification($health_status);
        }
    }
    
    /**
     * Cleanup old errors
     */
    public function cleanup_old_errors() {
        $log_entries = $this->error_handler->get_log_entries(1000);
        $cutoff_time = time() - (30 * 24 * 60 * 60); // 30 days ago
        
        $old_entries = array_filter($log_entries, function($entry) use ($cutoff_time) {
            return strtotime($entry['timestamp']) < $cutoff_time;
        });
        
        if (!empty($old_entries)) {
            // Clear the log and re-add recent entries
            $recent_entries = array_filter($log_entries, function($entry) use ($cutoff_time) {
                return strtotime($entry['timestamp']) >= $cutoff_time;
            });
            
            $this->error_handler->clear_log();
            
            foreach ($recent_entries as $entry) {
                $this->error_handler->log(
                    strtolower($entry['level']),
                    $entry['message'],
                    $entry['context'] ?? [],
                    $entry['file'] ?? '',
                    $entry['line'] ?? 0
                );
            }
        }
    }
    
    /**
     * Send health check notification
     */
    private function send_health_check_notification($health_status) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf('[%s] Askro Plugin Health Check Failed', $site_name);
        
        $message = "The Askro plugin health check has failed:\n\n";
        $message .= "Status: " . $health_status['status'] . "\n";
        $message .= "Timestamp: " . $health_status['timestamp'] . "\n\n";
        
        foreach ($health_status as $component => $status) {
            if ($component !== 'status' && $component !== 'timestamp' && $component !== 'version') {
                $message .= ucfirst($component) . ": " . $status . "\n";
            }
        }
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Get health check URL
     */
    public function get_health_check_url() {
        return rest_url('askro/v1/health');
    }
    
    /**
     * Get detailed health check URL
     */
    public function get_detailed_health_check_url() {
        return rest_url('askro/v1/health/detailed');
    }
    
    /**
     * Get monitoring stats URL
     */
    public function get_monitoring_stats_url() {
        return rest_url('askro/v1/monitoring/stats');
    }
}

// Initialize monitoring
$askro_monitoring = new Askro_Monitoring(); 

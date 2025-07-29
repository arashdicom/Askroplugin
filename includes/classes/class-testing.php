<?php
/**
 * Askro Testing Class
 * 
 * Provides testing functionality for the Askro plugin
 * 
 * @package Askro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Askro_Testing Class
 * 
 * Handles all testing functionality including unit tests, integration tests,
 * and health checks for the plugin
 * 
 * @since 1.0.0
 */
class Askro_Testing {
    
    /**
     * Test results
     */
    private $test_results = [];
    
    /**
     * Error handler instance
     */
    private $error_handler;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->error_handler = Askro_Error_Handler::get_instance();
    }
    
    /**
     * Run all tests
     */
    public function run_all_tests() {
        $this->test_results = [];
        
        // Database tests
        $this->test_database_connection();
        $this->test_database_tables();
        $this->test_database_permissions();
        
        // API tests
        $this->test_api_endpoints();
        $this->test_api_authentication();
        
        // Security tests
        $this->test_security_features();
        $this->test_nonce_verification();
        
        // Functionality tests
        $this->test_core_functions();
        $this->test_ajax_handlers();
        
        // Performance tests
        $this->test_performance();
        
        return $this->test_results;
    }
    
    /**
     * Test database connection
     */
    private function test_database_connection() {
        global $wpdb;
        
        try {
            $result = $wpdb->get_var("SELECT 1");
            $this->add_test_result('database_connection', true, 'Database connection successful');
        } catch (Exception $e) {
            $this->add_test_result('database_connection', false, 'Database connection failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Test database tables
     */
    private function test_database_tables() {
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
        
        $missing_tables = [];
        
        foreach ($required_tables as $table) {
            $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            if (!$exists) {
                $missing_tables[] = $table;
            }
        }
        
        if (empty($missing_tables)) {
            $this->add_test_result('database_tables', true, 'All required tables exist');
        } else {
            $this->add_test_result('database_tables', false, 'Missing tables: ' . implode(', ', $missing_tables));
        }
    }
    
    /**
     * Test database permissions
     */
    private function test_database_permissions() {
        global $wpdb;
        
        $test_table = $wpdb->prefix . 'askro_test_permissions';
        
        try {
            // Test CREATE
            $wpdb->query("CREATE TABLE IF NOT EXISTS $test_table (id INT)");
            
            // Test INSERT
            $wpdb->insert($test_table, ['id' => 1]);
            
            // Test SELECT
            $result = $wpdb->get_var("SELECT id FROM $test_table WHERE id = 1");
            
            // Test UPDATE
            $wpdb->update($test_table, ['id' => 2], ['id' => 1]);
            
            // Test DELETE
            $wpdb->delete($test_table, ['id' => 2]);
            
            // Clean up
            $wpdb->query("DROP TABLE IF EXISTS $test_table");
            
            $this->add_test_result('database_permissions', true, 'Database permissions working correctly');
        } catch (Exception $e) {
            $this->add_test_result('database_permissions', false, 'Database permissions failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Test API endpoints
     */
    private function test_api_endpoints() {
        $endpoints = [
            '/wp-json/askro/v1/questions',
            '/wp-json/askro/v1/answers',
            '/wp-json/askro/v1/users',
            '/wp-json/askro/v1/votes'
        ];
        
        $failed_endpoints = [];
        
        foreach ($endpoints as $endpoint) {
            $response = wp_remote_get(home_url($endpoint));
            
            if (is_wp_error($response)) {
                $failed_endpoints[] = $endpoint . ' (' . $response->get_error_message() . ')';
            } elseif (wp_remote_retrieve_response_code($response) >= 400) {
                $failed_endpoints[] = $endpoint . ' (HTTP ' . wp_remote_retrieve_response_code($response) . ')';
            }
        }
        
        if (empty($failed_endpoints)) {
            $this->add_test_result('api_endpoints', true, 'All API endpoints responding correctly');
        } else {
            $this->add_test_result('api_endpoints', false, 'Failed endpoints: ' . implode(', ', $failed_endpoints));
        }
    }
    
    /**
     * Test API authentication
     */
    private function test_api_authentication() {
        // Test without authentication
        $response = wp_remote_get(home_url('/wp-json/askro/v1/questions'));
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 401) {
            $this->add_test_result('api_authentication', true, 'API authentication working correctly');
        } else {
            $this->add_test_result('api_authentication', false, 'API authentication not properly configured');
        }
    }
    
    /**
     * Test security features
     */
    private function test_security_features() {
        $security_tests = [
            'nonce_creation' => wp_create_nonce('askro_test_nonce'),
            'nonce_verification' => wp_verify_nonce(wp_create_nonce('askro_test_nonce'), 'askro_test_nonce'),
            'user_capabilities' => current_user_can('read'),
            'sanitization' => sanitize_text_field('<script>alert("test")</script>') === 'alert("test")'
        ];
        
        $failed_tests = [];
        
        foreach ($security_tests as $test => $result) {
            if (!$result) {
                $failed_tests[] = $test;
            }
        }
        
        if (empty($failed_tests)) {
            $this->add_test_result('security_features', true, 'All security features working correctly');
        } else {
            $this->add_test_result('security_features', false, 'Failed security tests: ' . implode(', ', $failed_tests));
        }
    }
    
    /**
     * Test nonce verification
     */
    private function test_nonce_verification() {
        $nonce = wp_create_nonce('askro_test_nonce');
        
        if (wp_verify_nonce($nonce, 'askro_test_nonce')) {
            $this->add_test_result('nonce_verification', true, 'Nonce verification working correctly');
        } else {
            $this->add_test_result('nonce_verification', false, 'Nonce verification failed');
        }
    }
    
    /**
     * Test core functions
     */
    private function test_core_functions() {
        $function_tests = [
            'askro_get_user_points' => function_exists('askro_get_user_points'),
            'askro_award_points' => function_exists('askro_award_points'),
            'askro_get_question_data' => function_exists('askro_get_question_data'),
            'askro_get_answer_data' => function_exists('askro_get_answer_data')
        ];
        
        $missing_functions = [];
        
        foreach ($function_tests as $function => $exists) {
            if (!$exists) {
                $missing_functions[] = $function;
            }
        }
        
        if (empty($missing_functions)) {
            $this->add_test_result('core_functions', true, 'All core functions available');
        } else {
            $this->add_test_result('core_functions', false, 'Missing functions: ' . implode(', ', $missing_functions));
        }
    }
    
    /**
     * Test AJAX handlers
     */
    private function test_ajax_handlers() {
        $ajax_actions = [
            'askro_mark_best_answer',
            'askro_submit_question',
            'askro_submit_answer',
            'askro_cast_vote'
        ];
        
        $missing_actions = [];
        
        foreach ($ajax_actions as $action) {
            if (!has_action("wp_ajax_$action") && !has_action("wp_ajax_nopriv_$action")) {
                $missing_actions[] = $action;
            }
        }
        
        if (empty($missing_actions)) {
            $this->add_test_result('ajax_handlers', true, 'All AJAX handlers registered');
        } else {
            $this->add_test_result('ajax_handlers', false, 'Missing AJAX handlers: ' . implode(', ', $missing_actions));
        }
    }
    
    /**
     * Test performance
     */
    private function test_performance() {
        $start_time = microtime(true);
        
        // Test database query performance
        global $wpdb;
        $wpdb->get_results("SELECT * FROM {$wpdb->posts} WHERE post_type = 'askro_question' LIMIT 10");
        
        $query_time = microtime(true) - $start_time;
        
        if ($query_time < 1.0) {
            $this->add_test_result('performance', true, "Database query performance acceptable ({$query_time}s)");
        } else {
            $this->add_test_result('performance', false, "Database query performance slow ({$query_time}s)");
        }
    }
    
    /**
     * Add test result
     */
    private function add_test_result($test_name, $passed, $message) {
        $this->test_results[$test_name] = [
            'passed' => $passed,
            'message' => $message,
            'timestamp' => current_time('mysql')
        ];
        
        if (!$passed) {
            $this->error_handler->warning("Test failed: $test_name - $message");
        }
    }
    
    /**
     * Get test results
     */
    public function get_test_results() {
        return $this->test_results;
    }
    
    /**
     * Get test summary
     */
    public function get_test_summary() {
        $total_tests = count($this->test_results);
        $passed_tests = count(array_filter($this->test_results, function($result) {
            return $result['passed'];
        }));
        
        return [
            'total' => $total_tests,
            'passed' => $passed_tests,
            'failed' => $total_tests - $passed_tests,
            'success_rate' => $total_tests > 0 ? round(($passed_tests / $total_tests) * 100, 2) : 0
        ];
    }
    
    /**
     * Run health check
     */
    public function run_health_check() {
        $health_status = [
            'status' => 'healthy',
            'checks' => []
        ];
        
        // Database health
        global $wpdb;
        $db_status = $wpdb->get_var("SELECT 1") ? 'healthy' : 'unhealthy';
        $health_status['checks']['database'] = $db_status;
        
        // Plugin files health
        $plugin_file = ASKRO_PLUGIN_FILE;
        $files_status = file_exists($plugin_file) ? 'healthy' : 'unhealthy';
        $health_status['checks']['files'] = $files_status;
        
        // API health
        $api_response = wp_remote_get(home_url('/wp-json/askro/v1/questions'));
        $api_status = !is_wp_error($api_response) ? 'healthy' : 'unhealthy';
        $health_status['checks']['api'] = $api_status;
        
        // Determine overall status
        $unhealthy_checks = array_filter($health_status['checks'], function($status) {
            return $status === 'unhealthy';
        });
        
        if (!empty($unhealthy_checks)) {
            $health_status['status'] = 'unhealthy';
        }
        
        return $health_status;
    }
    
    /**
     * Generate test report
     */
    public function generate_test_report() {
        $summary = $this->get_test_summary();
        $results = $this->get_test_results();
        
        $report = [
            'summary' => $summary,
            'results' => $results,
            'timestamp' => current_time('mysql'),
            'environment' => [
                'php_version' => PHP_VERSION,
                'wordpress_version' => get_bloginfo('version'),
                'plugin_version' => ASKRO_VERSION ?? '1.0.0'
            ]
        ];
        
        return $report;
    }
    
    /**
     * Export test report to JSON
     */
    public function export_test_report($file_path = null) {
        $report = $this->generate_test_report();
        
        if (!$file_path) {
            $file_path = WP_CONTENT_DIR . '/logs/askro-test-report-' . date('Y-m-d-H-i-s') . '.json';
        }
        
        $logs_dir = dirname($file_path);
        if (!is_dir($logs_dir)) {
            wp_mkdir_p($logs_dir);
        }
        
        file_put_contents($file_path, json_encode($report, JSON_PRETTY_PRINT));
        
        return $file_path;
    }
}

// Initialize testing
$askro_testing = new Askro_Testing();

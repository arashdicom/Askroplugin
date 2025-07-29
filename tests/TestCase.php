<?php
/**
 * Base Test Case for Askro Plugin
 * 
 * @package Askro
 * @since 1.0.0
 */

use WP_UnitTestCase;

/**
 * Askro_TestCase Class
 * 
 * Base test case for all Askro plugin tests
 * 
 * @since 1.0.0
 */
abstract class Askro_TestCase extends WP_UnitTestCase {
    
    /**
     * Plugin instance
     * 
     * @var Askro_Main
     */
    protected $plugin;
    
    /**
     * Error handler instance
     * 
     * @var Askro_Error_Handler
     */
    protected $error_handler;
    
    /**
     * Security helper instance
     * 
     * @var Askro_Security_Helper
     */
    protected $security_helper;
    
    /**
     * Standards helper instance
     * 
     * @var Askro_Standards_Helper
     */
    protected $standards_helper;
    
    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Initialize plugin components
        $this->plugin = Askro_Main::get_instance();
        $this->error_handler = Askro_Error_Handler::get_instance();
        $this->security_helper = new Askro_Security_Helper();
        $this->standards_helper = new Askro_Standards_Helper();
        
        // Create test tables
        $this->create_test_tables();
    }
    
    /**
     * Tear down test environment
     */
    public function tearDown(): void {
        // Clean up test data
        $this->cleanup_test_data();
        
        parent::tearDown();
    }
    
    /**
     * Create test tables
     */
    protected function create_test_tables() {
        global $wpdb;
        
        // Create test tables if they don't exist
        $tables = [
            'askro_questions',
            'askro_answers',
            'askro_comments',
            'askro_points_log',
            'askro_user_achievements',
            'askro_achievements',
            'askro_badges',
            'askro_security_logs'
        ];
        
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
                $this->create_table($table);
            }
        }
    }
    
    /**
     * Create a specific table
     * 
     * @param string $table_name Table name without prefix
     */
    protected function create_table($table_name) {
        global $wpdb;
        
        $table = $wpdb->prefix . $table_name;
        
        switch ($table_name) {
            case 'askro_questions':
                $sql = "CREATE TABLE $table (
                    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    post_id bigint(20) unsigned NOT NULL,
                    user_id bigint(20) unsigned NOT NULL,
                    title varchar(255) NOT NULL,
                    content longtext NOT NULL,
                    status varchar(50) DEFAULT 'open',
                    views int(11) DEFAULT 0,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY post_id (post_id),
                    KEY user_id (user_id),
                    KEY status (status)
                ) " . $wpdb->get_charset_collate();
                break;
                
            case 'askro_answers':
                $sql = "CREATE TABLE $table (
                    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    question_id bigint(20) unsigned NOT NULL,
                    user_id bigint(20) unsigned NOT NULL,
                    content longtext NOT NULL,
                    is_best tinyint(1) DEFAULT 0,
                    votes int(11) DEFAULT 0,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY question_id (question_id),
                    KEY user_id (user_id),
                    KEY is_best (is_best)
                ) " . $wpdb->get_charset_collate();
                break;
                
            case 'askro_comments':
                $sql = "CREATE TABLE $table (
                    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    post_id bigint(20) unsigned NOT NULL,
                    user_id bigint(20) unsigned NOT NULL,
                    content text NOT NULL,
                    parent_id bigint(20) unsigned DEFAULT 0,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY post_id (post_id),
                    KEY user_id (user_id),
                    KEY parent_id (parent_id)
                ) " . $wpdb->get_charset_collate();
                break;
                
            case 'askro_points_log':
                $sql = "CREATE TABLE $table (
                    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    user_id bigint(20) unsigned NOT NULL,
                    points int(11) NOT NULL,
                    action varchar(100) NOT NULL,
                    description text,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY user_id (user_id),
                    KEY action (action)
                ) " . $wpdb->get_charset_collate();
                break;
                
            case 'askro_user_achievements':
                $sql = "CREATE TABLE $table (
                    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    user_id bigint(20) unsigned NOT NULL,
                    achievement_id bigint(20) unsigned NOT NULL,
                    earned_at datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY user_id (user_id),
                    KEY achievement_id (achievement_id)
                ) " . $wpdb->get_charset_collate();
                break;
                
            case 'askro_achievements':
                $sql = "CREATE TABLE $table (
                    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    name varchar(255) NOT NULL,
                    description text,
                    points int(11) DEFAULT 0,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) " . $wpdb->get_charset_collate();
                break;
                
            case 'askro_badges':
                $sql = "CREATE TABLE $table (
                    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    name varchar(255) NOT NULL,
                    icon varchar(255),
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id)
                ) " . $wpdb->get_charset_collate();
                break;
                
            case 'askro_security_logs':
                $sql = "CREATE TABLE $table (
                    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    event_type varchar(100) NOT NULL,
                    user_id bigint(20) unsigned DEFAULT 0,
                    ip_address varchar(45),
                    user_agent text,
                    data longtext,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY event_type (event_type),
                    KEY user_id (user_id),
                    KEY created_at (created_at)
                ) " . $wpdb->get_charset_collate();
                break;
        }
        
        if (isset($sql)) {
            $wpdb->query($sql);
        }
    }
    
    /**
     * Clean up test data
     */
    protected function cleanup_test_data() {
        global $wpdb;
        
        // Clean up test tables
        $tables = [
            'askro_questions',
            'askro_answers',
            'askro_comments',
            'askro_points_log',
            'askro_user_achievements',
            'askro_achievements',
            'askro_badges',
            'askro_security_logs'
        ];
        
        foreach ($tables as $table) {
            $table_name = $wpdb->prefix . $table;
            $wpdb->query("TRUNCATE TABLE $table_name");
        }
    }
    
    /**
     * Create a test user
     * 
     * @param array $user_data User data
     * @return int User ID
     */
    protected function create_test_user($user_data = []) {
        $defaults = [
            'user_login' => 'testuser' . uniqid(),
            'user_email' => 'test' . uniqid() . '@example.com',
            'user_pass' => 'password',
            'display_name' => 'Test User'
        ];
        
        $user_data = wp_parse_args($user_data, $defaults);
        
        return wp_insert_user($user_data);
    }
    
    /**
     * Create a test question
     * 
     * @param array $question_data Question data
     * @return int Question ID
     */
    protected function create_test_question($question_data = []) {
        global $wpdb;
        
        $defaults = [
            'post_id' => $this->factory->post->create([
                'post_type' => 'askro_question',
                'post_title' => 'Test Question',
                'post_content' => 'Test question content',
                'post_status' => 'publish'
            ]),
            'user_id' => $this->create_test_user(),
            'title' => 'Test Question',
            'content' => 'Test question content',
            'status' => 'open'
        ];
        
        $question_data = wp_parse_args($question_data, $defaults);
        
        $wpdb->insert(
            $wpdb->prefix . 'askro_questions',
            $question_data,
            ['%d', '%d', '%s', '%s', '%s']
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Create a test answer
     * 
     * @param array $answer_data Answer data
     * @return int Answer ID
     */
    protected function create_test_answer($answer_data = []) {
        global $wpdb;
        
        $defaults = [
            'question_id' => $this->create_test_question(),
            'user_id' => $this->create_test_user(),
            'content' => 'Test answer content',
            'is_best' => 0,
            'votes' => 0
        ];
        
        $answer_data = wp_parse_args($answer_data, $defaults);
        
        $wpdb->insert(
            $wpdb->prefix . 'askro_answers',
            $answer_data,
            ['%d', '%d', '%s', '%d', '%d']
        );
        
        return $wpdb->insert_id;
    }
    
    /**
     * Assert that a table exists
     * 
     * @param string $table_name Table name without prefix
     */
    protected function assertTableExists($table_name) {
        global $wpdb;
        
        $table = $wpdb->prefix . $table_name;
        $exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") == $table;
        
        $this->assertTrue($exists, "Table $table_name does not exist");
    }
    
    /**
     * Assert that a record exists in a table
     * 
     * @param string $table_name Table name without prefix
     * @param array $where Where conditions
     */
    protected function assertRecordExists($table_name, $where) {
        global $wpdb;
        
        $table = $wpdb->prefix . $table_name;
        $where_clause = [];
        $where_values = [];
        
        foreach ($where as $column => $value) {
            $where_clause[] = "$column = %s";
            $where_values[] = $value;
        }
        
        $sql = "SELECT COUNT(*) FROM $table WHERE " . implode(' AND ', $where_clause);
        $count = $wpdb->get_var($wpdb->prepare($sql, $where_values));
        
        $this->assertGreaterThan(0, $count, "Record not found in $table_name");
    }
} 

<?php
/**
 * Security Tests for Askro Plugin
 * 
 * @package Askro
 * @since 1.0.0
 */

/**
 * Askro_SecurityTest Class
 * 
 * Tests for security functionality
 * 
 * @since 1.0.0
 */
class Askro_SecurityTest extends Askro_TestCase {
    
    /**
     * Test nonce verification
     */
    public function test_nonce_verification() {
        // Test valid nonce
        $_POST['test_nonce'] = wp_create_nonce('test_action');
        $result = $this->security_helper->verify_nonce('test_nonce', 'test_action');
        $this->assertTrue($result);
        
        // Test invalid nonce
        $_POST['test_nonce'] = 'invalid_nonce';
        $result = $this->security_helper->verify_nonce('test_nonce', 'test_action');
        $this->assertFalse($result);
        
        // Test missing nonce
        unset($_POST['test_nonce']);
        $result = $this->security_helper->verify_nonce('test_nonce', 'test_action');
        $this->assertFalse($result);
    }
    
    /**
     * Test capability verification
     */
    public function test_capability_verification() {
        $user_id = $this->create_test_user();
        wp_set_current_user($user_id);
        
        // Test user with capability
        $result = $this->security_helper->verify_capability('read', $user_id);
        $this->assertTrue($result);
        
        // Test user without capability
        $result = $this->security_helper->verify_capability('manage_options', $user_id);
        $this->assertFalse($result);
        
        // Test non-logged in user
        wp_set_current_user(0);
        $result = $this->security_helper->verify_capability('read');
        $this->assertFalse($result);
    }
    
    /**
     * Test input sanitization
     */
    public function test_input_sanitization() {
        $data = [
            'text_field' => '<script>alert("xss")</script>Test',
            'email_field' => 'test@example.com',
            'url_field' => 'https://example.com',
            'int_field' => '123',
            'float_field' => '123.45'
        ];
        
        $rules = [
            'text_field' => [
                'type' => 'text',
                'required' => true,
                'min_length' => 5,
                'max_length' => 100
            ],
            'email_field' => [
                'type' => 'email',
                'required' => true
            ],
            'url_field' => [
                'type' => 'url',
                'required' => true
            ],
            'int_field' => [
                'type' => 'int',
                'required' => true
            ],
            'float_field' => [
                'type' => 'float',
                'required' => true
            ]
        ];
        
        $result = $this->security_helper->sanitize_input($data, $rules);
        
        $this->assertNotInstanceOf('WP_Error', $result);
        $this->assertEquals('Test', $result['text_field']); // Script tags removed
        $this->assertEquals('test@example.com', $result['email_field']);
        $this->assertEquals('https://example.com', $result['url_field']);
        $this->assertEquals(123, $result['int_field']);
        $this->assertEquals(123.45, $result['float_field']);
    }
    
    /**
     * Test input validation errors
     */
    public function test_input_validation_errors() {
        $data = [
            'text_field' => 'short',
            'email_field' => 'invalid-email',
            'required_field' => ''
        ];
        
        $rules = [
            'text_field' => [
                'type' => 'text',
                'required' => true,
                'min_length' => 10
            ],
            'email_field' => [
                'type' => 'email',
                'required' => true
            ],
            'required_field' => [
                'type' => 'text',
                'required' => true
            ]
        ];
        
        $result = $this->security_helper->sanitize_input($data, $rules);
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('validation_failed', $result->get_error_code());
    }
    
    /**
     * Test file upload validation
     */
    public function test_file_upload_validation() {
        // Test valid file
        $file = [
            'name' => 'test.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/tmp/test.jpg',
            'error' => UPLOAD_ERR_OK,
            'size' => 1024
        ];
        
        $result = $this->security_helper->validate_file_upload($file);
        $this->assertNotInstanceOf('WP_Error', $result);
        
        // Test invalid file type
        $file['name'] = 'test.exe';
        $result = $this->security_helper->validate_file_upload($file);
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('invalid_file_type', $result->get_error_code());
        
        // Test file too large
        $file['name'] = 'test.jpg';
        $file['size'] = 10 * 1024 * 1024; // 10MB
        $result = $this->security_helper->validate_file_upload($file);
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('file_too_large', $result->get_error_code());
    }
    
    /**
     * Test rate limiting
     */
    public function test_rate_limiting() {
        $user_id = $this->create_test_user();
        $action = 'test_action';
        
        // Test within limit
        for ($i = 0; $i < 5; $i++) {
            $result = $this->security_helper->check_rate_limit($action, $user_id, 10, 3600);
            $this->assertFalse($result); // Not exceeded
        }
        
        // Test exceeding limit
        for ($i = 0; $i < 10; $i++) {
            $this->security_helper->check_rate_limit($action, $user_id, 10, 3600);
        }
        
        $result = $this->security_helper->check_rate_limit($action, $user_id, 10, 3600);
        $this->assertTrue($result); // Exceeded
    }
    
    /**
     * Test security event logging
     */
    public function test_security_event_logging() {
        $event_type = 'test_event';
        $data = ['test' => 'data'];
        
        $this->security_helper->log_security_event($event_type, $data);
        
        global $wpdb;
        $table = $wpdb->prefix . 'askro_security_logs';
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE event_type = '$event_type'");
        
        $this->assertGreaterThan(0, $count);
    }
    
    /**
     * Test client IP detection
     */
    public function test_client_ip_detection() {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        
        $ip = $this->security_helper->get_client_ip();
        $this->assertEquals('192.168.1.1', $ip);
        
        // Test with X-Forwarded-For
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.1, 192.168.1.1';
        $ip = $this->security_helper->get_client_ip();
        $this->assertEquals('203.0.113.1', $ip);
    }
} 

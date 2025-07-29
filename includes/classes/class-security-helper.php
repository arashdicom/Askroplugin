<?php
/**
 * Askro Security Helper Class
 * 
 * Provides unified security checks and WordPress coding standards compliance
 * 
 * @package Askro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Askro_Security_Helper Class
 * 
 * Handles all security checks and WordPress standards compliance
 * 
 * @since 1.0.0
 */
class Askro_Security_Helper {
    
    /**
     * Plugin text domain
     */
    const TEXT_DOMAIN = 'askro';
    
    /**
     * Plugin prefix for functions and constants
     */
    const PREFIX = 'askro';
    
    /**
     * Default hook priority
     */
    const DEFAULT_PRIORITY = 10;
    
    /**
     * Verify nonce with proper error handling
     * 
     * @param string $nonce_key Nonce key from $_POST
     * @param string $action Nonce action
     * @param string $error_message Custom error message
     * @return bool Whether nonce is valid
     */
    public function verify_nonce($nonce_key, $action, $error_message = '') {
        if (empty($_POST[$nonce_key])) {
            $this->security_error('nonce_missing', $error_message ?: __('Nonce is missing.', self::TEXT_DOMAIN));
            return false;
        }
        
        if (!wp_verify_nonce($_POST[$nonce_key], $action)) {
            $this->security_error('nonce_invalid', $error_message ?: __('Security check failed.', self::TEXT_DOMAIN));
            return false;
        }
        
        return true;
    }
    
    /**
     * Verify user capabilities
     * 
     * @param string $capability Required capability
     * @param int $user_id User ID (optional, defaults to current user)
     * @param string $error_message Custom error message
     * @return bool Whether user has capability
     */
    public function verify_capability($capability, $user_id = 0, $error_message = '') {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            $this->security_error('user_not_logged_in', $error_message ?: __('User not logged in.', self::TEXT_DOMAIN));
            return false;
        }
        
        if (!user_can($user_id, $capability)) {
            $this->security_error('insufficient_permissions', $error_message ?: __('Insufficient permissions.', self::TEXT_DOMAIN));
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitize and validate input data
     * 
     * @param array $data Input data
     * @param array $rules Validation rules
     * @return array|WP_Error Sanitized data or error
     */
    public function sanitize_input($data, $rules = []) {
        $sanitized = [];
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? '';
            
            // Check if field is required
            if (!empty($rule['required']) && empty($value)) {
                $errors[] = sprintf(__('Field "%s" is required.', self::TEXT_DOMAIN), $field);
                continue;
            }
            
            // Skip empty optional fields
            if (empty($value) && !empty($rule['optional'])) {
                continue;
            }
            
            // Sanitize based on type
            switch ($rule['type']) {
                case 'text':
                    $sanitized[$field] = sanitize_text_field($value);
                    break;
                case 'textarea':
                    $sanitized[$field] = sanitize_textarea_field($value);
                    break;
                case 'email':
                    $sanitized[$field] = sanitize_email($value);
                    if (!is_email($sanitized[$field])) {
                        $errors[] = sprintf(__('Invalid email format for field "%s".', self::TEXT_DOMAIN), $field);
                    }
                    break;
                case 'url':
                    $sanitized[$field] = esc_url_raw($value);
                    break;
                case 'int':
                    $sanitized[$field] = intval($value);
                    break;
                case 'float':
                    $sanitized[$field] = floatval($value);
                    break;
                case 'array':
                    $sanitized[$field] = is_array($value) ? array_map('sanitize_text_field', $value) : [];
                    break;
                default:
                    $sanitized[$field] = sanitize_text_field($value);
            }
            
            // Validate length
            if (!empty($rule['min_length']) && strlen($sanitized[$field]) < $rule['min_length']) {
                $errors[] = sprintf(__('Field "%s" is too short (minimum %d characters).', self::TEXT_DOMAIN), $field, $rule['min_length']);
            }
            
            if (!empty($rule['max_length']) && strlen($sanitized[$field]) > $rule['max_length']) {
                $errors[] = sprintf(__('Field "%s" is too long (maximum %d characters).', self::TEXT_DOMAIN), $field, $rule['max_length']);
            }
            
            // Validate pattern
            if (!empty($rule['pattern']) && !preg_match($rule['pattern'], $sanitized[$field])) {
                $errors[] = sprintf(__('Field "%s" contains invalid characters.', self::TEXT_DOMAIN), $field);
            }
        }
        
        if (!empty($errors)) {
            return new WP_Error('validation_failed', implode(' ', $errors));
        }
        
        return $sanitized;
    }
    
    /**
     * Validate file upload
     * 
     * @param array $file File data from $_FILES
     * @param array $options Validation options
     * @return array|WP_Error Validated file data or error
     */
    public function validate_file_upload($file, $options = []) {
        $defaults = [
            'max_size' => 5 * 1024 * 1024, // 5MB
            'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'],
            'max_files' => 1
        ];
        
        $options = wp_parse_args($options, $defaults);
        
        // Check if file was uploaded
        if (empty($file['name']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
            return new WP_Error('no_file', __('No file was uploaded.', self::TEXT_DOMAIN));
        }
        
        // Check upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $error_messages = [
                UPLOAD_ERR_INI_SIZE => __('The uploaded file exceeds the upload_max_filesize directive in php.ini.', self::TEXT_DOMAIN),
                UPLOAD_ERR_FORM_SIZE => __('The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.', self::TEXT_DOMAIN),
                UPLOAD_ERR_PARTIAL => __('The uploaded file was only partially uploaded.', self::TEXT_DOMAIN),
                UPLOAD_ERR_NO_TMP_DIR => __('Missing a temporary folder.', self::TEXT_DOMAIN),
                UPLOAD_ERR_CANT_WRITE => __('Failed to write file to disk.', self::TEXT_DOMAIN),
                UPLOAD_ERR_EXTENSION => __('A PHP extension stopped the file upload.', self::TEXT_DOMAIN)
            ];
            
            $message = $error_messages[$file['error']] ?? __('Unknown upload error.', self::TEXT_DOMAIN);
            return new WP_Error('upload_error', $message);
        }
        
        // Check file size
        if ($file['size'] > $options['max_size']) {
            return new WP_Error('file_too_large', sprintf(__('File size exceeds maximum allowed size (%s).', self::TEXT_DOMAIN), size_format($options['max_size'])));
        }
        
        // Check file type
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $options['allowed_types'])) {
            return new WP_Error('invalid_file_type', sprintf(__('File type "%s" is not allowed.', self::TEXT_DOMAIN), $file_extension));
        }
        
        // Additional security checks
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        // Check for dangerous MIME types
        $dangerous_types = ['application/x-executable', 'application/x-msdownload', 'application/x-msi'];
        if (in_array($mime_type, $dangerous_types)) {
            return new WP_Error('dangerous_file_type', __('This file type is not allowed for security reasons.', self::TEXT_DOMAIN));
        }
        
        return $file;
    }
    
    /**
     * Rate limiting check
     * 
     * @param string $action Action name
     * @param int $user_id User ID
     * @param int $limit Maximum attempts
     * @param int $window Time window in seconds
     * @return bool Whether rate limit is exceeded
     */
    public function check_rate_limit($action, $user_id, $limit = 10, $window = 3600) {
        $key = sprintf('%s_rate_limit_%s_%d', self::PREFIX, $action, $user_id);
        $attempts = get_transient($key) ?: 0;
        
        if ($attempts >= $limit) {
            return true; // Rate limit exceeded
        }
        
        set_transient($key, $attempts + 1, $window);
        return false;
    }
    
    /**
     * Log security event
     * 
     * @param string $event_type Event type
     * @param array $data Event data
     */
    public function log_security_event($event_type, $data = []) {
        global $askro_error_handler;
        
        if ($askro_error_handler) {
            $askro_error_handler->warning("Security Event: $event_type", $data);
        }
        
        // Log to security table if available
        $this->log_to_security_table($event_type, $data);
    }
    
    /**
     * Log to security table
     * 
     * @param string $event_type Event type
     * @param array $data Event data
     */
    private function log_to_security_table($event_type, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'askro_security_logs';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            return;
        }
        
        $wpdb->insert(
            $table_name,
            [
                'event_type' => $event_type,
                'user_id' => get_current_user_id(),
                'ip_address' => $this->get_client_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'data' => json_encode($data),
                'created_at' => current_time('mysql')
            ],
            ['%s', '%d', '%s', '%s', '%s', '%s']
        );
    }
    
    /**
     * Get client IP address
     * 
     * @return string Client IP address
     */
    public function get_client_ip() {
        $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Security error handler
     * 
     * @param string $error_type Error type
     * @param string $message Error message
     */
    private function security_error($error_type, $message) {
        $this->log_security_event($error_type, [
            'message' => $message,
            'user_id' => get_current_user_id(),
            'ip' => $this->get_client_ip(),
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? ''
        ]);
        
        // Send error response
        global $askro_response_handler;
        if ($askro_response_handler) {
            $askro_response_handler->send_security_error($error_type);
        } else {
            wp_die($message, __('Security Error', self::TEXT_DOMAIN), ['response' => 403]);
        }
    }
    
    /**
     * Add action with proper priority
     * 
     * @param string $hook Hook name
     * @param callable $callback Callback function
     * @param int $priority Priority (optional)
     * @param int $accepted_args Number of accepted arguments (optional)
     */
    public function add_action($hook, $callback, $priority = null, $accepted_args = 1) {
        $priority = $priority ?? self::DEFAULT_PRIORITY;
        add_action($hook, $callback, $priority, $accepted_args);
    }
    
    /**
     * Add filter with proper priority
     * 
     * @param string $hook Hook name
     * @param callable $callback Callback function
     * @param int $priority Priority (optional)
     * @param int $accepted_args Number of accepted arguments (optional)
     */
    public function add_filter($hook, $callback, $priority = null, $accepted_args = 1) {
        $priority = $priority ?? self::DEFAULT_PRIORITY;
        add_filter($hook, $callback, $priority, $accepted_args);
    }
    
    /**
     * Get text domain
     * 
     * @return string Text domain
     */
    public function get_text_domain() {
        return self::TEXT_DOMAIN;
    }
    
    /**
     * Get plugin prefix
     * 
     * @return string Plugin prefix
     */
    public function get_prefix() {
        return self::PREFIX;
    }
}

// Initialize security helper
$askro_security_helper = new Askro_Security_Helper(); 

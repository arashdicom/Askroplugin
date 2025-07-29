<?php
/**
 * Askro Response Handler Class
 * 
 * Provides unified response handling and error management for the Askro plugin
 * 
 * @package Askro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Askro_Response_Handler Class
 * 
 * Handles all AJAX responses, error messages, and user feedback
 * 
 * @since 1.0.0
 */
class Askro_Response_Handler {
    
    /**
     * Error handler instance
     */
    private $error_handler;
    
    /**
     * Common error messages
     */
    private $error_messages = [
        'security_check_failed' => 'خطأ في التحقق من الأمان.',
        'login_required' => 'يجب تسجيل الدخول للمتابعة.',
        'insufficient_permissions' => 'غير مصرح لك بهذا الإجراء.',
        'invalid_data' => 'البيانات غير صحيحة.',
        'missing_data' => 'البيانات المطلوبة مفقودة.',
        'file_upload_error' => 'فشل في رفع الملف.',
        'database_error' => 'فشل في العملية.',
        'not_found' => 'العنصر المطلوب غير موجود.',
        'duplicate_content' => 'المحتوى مكرر.',
        'content_too_short' => 'المحتوى قصير جداً.',
        'content_too_long' => 'المحتوى طويل جداً.',
        'spam_detected' => 'تم رفض المحتوى كرسالة مزعجة.',
        'question_closed' => 'هذا السؤال مغلق.',
        'api_key_required' => 'مفتاح API مطلوب.',
        'api_key_invalid' => 'مفتاح API غير صحيح.',
        'rate_limit_exceeded' => 'تم تجاوز الحد المسموح.',
        'maintenance_mode' => 'النظام في وضع الصيانة.'
    ];
    
    /**
     * Common success messages
     */
    private $success_messages = [
        'question_submitted' => 'تم نشر سؤالك بنجاح!',
        'answer_submitted' => 'تم نشر إجابتك بنجاح!',
        'comment_added' => 'تم إضافة التعليق بنجاح!',
        'comment_updated' => 'تم تحديث التعليق بنجاح!',
        'comment_deleted' => 'تم حذف التعليق بنجاح!',
        'vote_cast' => 'تم تسجيل تصويتك بنجاح!',
        'profile_updated' => 'تم تحديث الملف الشخصي بنجاح!',
        'file_uploaded' => 'تم رفع الملف بنجاح!',
        'api_key_generated' => 'تم إنشاء مفتاح API بنجاح!',
        'api_key_revoked' => 'تم إلغاء مفتاح API بنجاح!',
        'notification_marked_read' => 'تم تحديث الإشعار بنجاح!',
        'best_answer_marked' => 'تم تحديد الإجابة الأفضل بنجاح!',
        'question_status_updated' => 'تم تحديث حالة السؤال بنجاح!',
        'achievement_unlocked' => 'تم فتح الإنجاز بنجاح!',
        'points_awarded' => 'تم منح النقاط بنجاح!'
    ];
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->error_handler = Askro_Error_Handler::get_instance();
    }
    
    /**
     * Send success response
     * 
     * @param string $message_key Message key or custom message
     * @param array $data Additional data
     * @param int $status_code HTTP status code
     */
    public function send_success($message_key = '', $data = [], $status_code = 200) {
        $message = $this->get_success_message($message_key);
        
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
        
        wp_send_json_success($response, $status_code);
    }
    
    /**
     * Send error response
     * 
     * @param string $error_key Error key or custom message
     * @param array $data Additional data
     * @param int $status_code HTTP status code
     */
    public function send_error($error_key = '', $data = [], $status_code = 400) {
        $message = $this->get_error_message($error_key);
        
        // Log error for debugging
        $this->error_handler->warning("AJAX Error: $message", [
            'error_key' => $error_key,
            'data' => $data,
            'status_code' => $status_code
        ]);
        
        $response = [
            'success' => false,
            'message' => $message,
            'data' => $data
        ];
        
        wp_send_json_error($response, $status_code);
    }
    
    /**
     * Send validation error response
     * 
     * @param WP_Error $error WordPress error object
     * @param array $data Additional data
     */
    public function send_validation_error($error, $data = []) {
        $message = $error->get_error_message();
        
        $this->error_handler->warning("Validation Error: $message", [
            'error_codes' => $error->get_error_codes(),
            'data' => $data
        ]);
        
        wp_send_json_error([
            'success' => false,
            'message' => $message,
            'data' => $data
        ]);
    }
    
    /**
     * Send security check error
     * 
     * @param string $action Action name for logging
     */
    public function send_security_error($action = '') {
        $this->error_handler->warning("Security check failed", [
            'action' => $action,
            'user_id' => get_current_user_id(),
            'ip' => $this->get_client_ip()
        ]);
        
        $this->send_error('security_check_failed');
    }
    
    /**
     * Send login required error
     */
    public function send_login_required_error() {
        $this->send_error('login_required');
    }
    
    /**
     * Send permission denied error
     * 
     * @param string $capability Required capability
     */
    public function send_permission_error($capability = '') {
        $this->error_handler->warning("Permission denied", [
            'capability' => $capability,
            'user_id' => get_current_user_id()
        ]);
        
        $this->send_error('insufficient_permissions');
    }
    
    /**
     * Send not found error
     * 
     * @param string $item_type Type of item not found
     */
    public function send_not_found_error($item_type = '') {
        $this->error_handler->info("Item not found", [
            'item_type' => $item_type,
            'user_id' => get_current_user_id()
        ]);
        
        $this->send_error('not_found');
    }
    
    /**
     * Send duplicate content error
     * 
     * @param string $content_type Type of duplicate content
     */
    public function send_duplicate_error($content_type = '') {
        $this->error_handler->info("Duplicate content detected", [
            'content_type' => $content_type,
            'user_id' => get_current_user_id()
        ]);
        
        $this->send_error('duplicate_content');
    }
    
    /**
     * Send file upload error
     * 
     * @param string $error_message Specific error message
     */
    public function send_file_upload_error($error_message = '') {
        $this->error_handler->warning("File upload failed", [
            'error_message' => $error_message,
            'user_id' => get_current_user_id()
        ]);
        
        $this->send_error('file_upload_error');
    }
    
    /**
     * Send database error
     * 
     * @param string $operation Operation that failed
     * @param string $error_message Specific error message
     */
    public function send_database_error($operation = '', $error_message = '') {
        $this->error_handler->error("Database operation failed", [
            'operation' => $operation,
            'error_message' => $error_message,
            'user_id' => get_current_user_id()
        ]);
        
        $this->send_error('database_error');
    }
    
    /**
     * Send rate limit error
     * 
     * @param string $action Action that was rate limited
     */
    public function send_rate_limit_error($action = '') {
        $this->error_handler->warning("Rate limit exceeded", [
            'action' => $action,
            'user_id' => get_current_user_id(),
            'ip' => $this->get_client_ip()
        ]);
        
        $this->send_error('rate_limit_exceeded');
    }
    
    /**
     * Get error message
     * 
     * @param string $key Error key
     * @return string Error message
     */
    private function get_error_message($key) {
        if (isset($this->error_messages[$key])) {
            return __($this->error_messages[$key], 'askro');
        }
        
        return $key ?: $this->error_messages['invalid_data'];
    }
    
    /**
     * Get success message
     * 
     * @param string $key Success key
     * @return string Success message
     */
    private function get_success_message($key) {
        if (isset($this->success_messages[$key])) {
            return __($this->success_messages[$key], 'askro');
        }
        
        return $key ?: __('تمت العملية بنجاح!', 'askro');
    }
    
    /**
     * Get client IP
     * 
     * @return string Client IP address
     */
    private function get_client_ip() {
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
     * Validate and sanitize input
     * 
     * @param array $data Input data
     * @param array $required_fields Required field names
     * @param array $optional_fields Optional field names with defaults
     * @return array|WP_Error Sanitized data or error
     */
    public function validate_input($data, $required_fields = [], $optional_fields = []) {
        $sanitized = [];
        
        // Validate required fields
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__('الحقل "%s" مطلوب.', 'askro'), $field));
            }
            $sanitized[$field] = sanitize_text_field($data[$field]);
        }
        
        // Handle optional fields
        foreach ($optional_fields as $field => $default) {
            $sanitized[$field] = isset($data[$field]) ? sanitize_text_field($data[$field]) : $default;
        }
        
        return $sanitized;
    }
    
    /**
     * Validate file upload
     * 
     * @param array $file File data from $_FILES
     * @param array $allowed_types Allowed file types
     * @param int $max_size Maximum file size in bytes
     * @return array|WP_Error Validated file data or error
     */
    public function validate_file_upload($file, $allowed_types = ['jpg', 'jpeg', 'png', 'gif'], $max_size = 5242880) {
        if (empty($file['name'])) {
            return new WP_Error('no_file', __('لم يتم اختيار ملف.', 'askro'));
        }
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', __('فشل في رفع الملف.', 'askro'));
        }
        
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', __('حجم الملف كبير جداً.', 'askro'));
        }
        
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_types)) {
            return new WP_Error('invalid_type', __('نوع الملف غير مدعوم.', 'askro'));
        }
        
        return $file;
    }
    
    /**
     * Check rate limiting
     * 
     * @param string $action Action name
     * @param int $user_id User ID
     * @param int $limit Maximum attempts
     * @param int $window Time window in seconds
     * @return bool Whether rate limit is exceeded
     */
    public function check_rate_limit($action, $user_id, $limit = 10, $window = 3600) {
        $key = "askro_rate_limit_{$action}_{$user_id}";
        $attempts = get_transient($key) ?: 0;
        
        if ($attempts >= $limit) {
            return true; // Rate limit exceeded
        }
        
        set_transient($key, $attempts + 1, $window);
        return false;
    }
    
    /**
     * Format response data
     * 
     * @param mixed $data Raw data
     * @param array $options Formatting options
     * @return array Formatted data
     */
    public function format_response_data($data, $options = []) {
        $defaults = [
            'include_meta' => true,
            'include_author' => true,
            'include_stats' => false,
            'limit' => 0
        ];
        
        $options = wp_parse_args($options, $defaults);
        
        if (is_array($data) && $options['limit'] > 0) {
            $data = array_slice($data, 0, $options['limit']);
        }
        
        return $data;
    }
}

// Initialize response handler
$askro_response_handler = new Askro_Response_Handler(); 

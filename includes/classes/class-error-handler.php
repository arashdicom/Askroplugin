<?php
/**
 * Askro Error Handler Class
 * 
 * Provides unified error handling and logging for the Askro plugin
 * 
 * @package Askro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Askro_Error_Handler Class
 * 
 * Handles all error logging, debugging, and error reporting for the plugin
 * 
 * @since 1.0.0
 */
class Askro_Error_Handler {
    
    /**
     * Error levels
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';
    
    /**
     * Log file path
     */
    private $log_file;
    
    /**
     * Debug mode
     */
    private $debug_mode;
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->debug_mode = defined('WP_DEBUG') && WP_DEBUG;
        $this->log_file = WP_CONTENT_DIR . '/logs/askro-errors.log';
        
        // Create logs directory if it doesn't exist
        $logs_dir = dirname($this->log_file);
        if (!is_dir($logs_dir)) {
            wp_mkdir_p($logs_dir);
        }
        
        // Set error handlers
        $this->set_error_handlers();
    }
    
    /**
     * Set error handlers
     */
    private function set_error_handlers() {
        if ($this->debug_mode) {
            set_error_handler([$this, 'handle_php_error']);
            set_exception_handler([$this, 'handle_exception']);
            register_shutdown_function([$this, 'handle_fatal_error']);
        }
    }
    
    /**
     * Log error
     * 
     * @param string $level Error level
     * @param string $message Error message
     * @param array $context Additional context
     * @param string $file File where error occurred
     * @param int $line Line number
     */
    public function log($level, $message, $context = [], $file = '', $line = 0) {
        // Don't log debug messages in production
        if ($level === self::LEVEL_DEBUG && !$this->debug_mode) {
            return;
        }
        
        $timestamp = current_time('Y-m-d H:i:s');
        $user_id = get_current_user_id();
        $user_info = $user_id ? "User: $user_id" : 'Guest';
        
        $log_entry = [
            'timestamp' => $timestamp,
            'level' => strtoupper($level),
            'message' => $message,
            'user' => $user_info,
            'file' => $file ?: $this->get_caller_file(),
            'line' => $line ?: $this->get_caller_line(),
            'context' => $context,
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'ip' => $this->get_client_ip()
        ];
        
        $log_line = json_encode($log_entry) . "\n";
        
        // Write to log file
        // Use WordPress error logging in production
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log($log_line, 3, $this->log_file);
        }
        
        // Also log to WordPress error log for critical errors
        if (in_array($level, [self::LEVEL_ERROR, self::LEVEL_CRITICAL])) {
            // Use WordPress error logging in production
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log("Askro {$level}: {$message} - File: {$log_entry['file']}:{$log_entry['line']}");
        }
        }
        
        // Send notification for critical errors
        if ($level === self::LEVEL_CRITICAL) {
            $this->send_critical_error_notification($log_entry);
        }
    }
    
    /**
     * Log debug message
     */
    public function debug($message, $context = []) {
        $this->log(self::LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Log info message
     */
    public function info($message, $context = []) {
        $this->log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Log warning message
     */
    public function warning($message, $context = []) {
        $this->log(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Log error message
     */
    public function error($message, $context = []) {
        $this->log(self::LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Log critical error message
     */
    public function critical($message, $context = []) {
        $this->log(self::LEVEL_CRITICAL, $message, $context);
    }
    
    /**
     * Handle PHP errors
     */
    public function handle_php_error($errno, $errstr, $errfile, $errline) {
        $level_map = [
            E_ERROR => self::LEVEL_ERROR,
            E_WARNING => self::LEVEL_WARNING,
            E_PARSE => self::LEVEL_CRITICAL,
            E_NOTICE => self::LEVEL_INFO,
            E_CORE_ERROR => self::LEVEL_CRITICAL,
            E_CORE_WARNING => self::LEVEL_WARNING,
            E_COMPILE_ERROR => self::LEVEL_CRITICAL,
            E_COMPILE_WARNING => self::LEVEL_WARNING,
            E_USER_ERROR => self::LEVEL_ERROR,
            E_USER_WARNING => self::LEVEL_WARNING,
            E_USER_NOTICE => self::LEVEL_INFO,
            E_STRICT => self::LEVEL_INFO,
            E_RECOVERABLE_ERROR => self::LEVEL_ERROR,
            E_DEPRECATED => self::LEVEL_WARNING,
            E_USER_DEPRECATED => self::LEVEL_WARNING
        ];
        
        $level = $level_map[$errno] ?? self::LEVEL_ERROR;
        
        $this->log($level, $errstr, [
            'errno' => $errno,
            'error_type' => $this->get_error_type($errno)
        ], $errfile, $errline);
        
        // Don't suppress errors in debug mode
        return false;
    }
    
    /**
     * Handle exceptions
     */
    public function handle_exception($exception) {
        $this->log(self::LEVEL_CRITICAL, $exception->getMessage(), [
            'exception_class' => get_class($exception),
            'trace' => $exception->getTraceAsString()
        ], $exception->getFile(), $exception->getLine());
    }
    
    /**
     * Handle fatal errors
     */
    public function handle_fatal_error() {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            $this->log(self::LEVEL_CRITICAL, $error['message'], [
                'error_type' => $this->get_error_type($error['type'])
            ], $error['file'], $error['line']);
        }
    }
    
    /**
     * Get caller file
     */
    private function get_caller_file() {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        
        foreach ($backtrace as $trace) {
            if (isset($trace['file']) && strpos($trace['file'], 'askro-plugin') !== false) {
                return str_replace(ABSPATH, '', $trace['file']);
            }
        }
        
        return 'unknown';
    }
    
    /**
     * Get caller line
     */
    private function get_caller_line() {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        
        foreach ($backtrace as $trace) {
            if (isset($trace['file']) && strpos($trace['file'], 'askro-plugin') !== false) {
                return $trace['line'];
            }
        }
        
        return 0;
    }
    
    /**
     * Get client IP
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
     * Get error type name
     */
    private function get_error_type($errno) {
        $types = [
            E_ERROR => 'E_ERROR',
            E_WARNING => 'E_WARNING',
            E_PARSE => 'E_PARSE',
            E_NOTICE => 'E_NOTICE',
            E_CORE_ERROR => 'E_CORE_ERROR',
            E_CORE_WARNING => 'E_CORE_WARNING',
            E_COMPILE_ERROR => 'E_COMPILE_ERROR',
            E_COMPILE_WARNING => 'E_COMPILE_WARNING',
            E_USER_ERROR => 'E_USER_ERROR',
            E_USER_WARNING => 'E_USER_WARNING',
            E_USER_NOTICE => 'E_USER_NOTICE',
            E_STRICT => 'E_STRICT',
            E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
            E_DEPRECATED => 'E_DEPRECATED',
            E_USER_DEPRECATED => 'E_USER_DEPRECATED'
        ];
        
        return $types[$errno] ?? 'UNKNOWN';
    }
    
    /**
     * Send critical error notification
     */
    private function send_critical_error_notification($log_entry) {
        // Only send notifications to admin users
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf('[%s] Critical Error in Askro Plugin', $site_name);
        
        $message = sprintf(
            "A critical error occurred in the Askro plugin:\n\n" .
            "Error: %s\n" .
            "File: %s:%d\n" .
            "User: %s\n" .
            "URL: %s\n" .
            "Time: %s\n\n" .
            "Please check the error logs for more details.",
            $log_entry['message'],
            $log_entry['file'],
            $log_entry['line'],
            $log_entry['user'],
            $log_entry['url'],
            $log_entry['timestamp']
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    /**
     * Get log file path
     */
    public function get_log_file() {
        return $this->log_file;
    }
    
    /**
     * Clear log file
     */
    public function clear_log() {
        if (file_exists($this->log_file)) {
            unlink($this->log_file);
        }
    }
    
    /**
     * Get log entries
     */
    public function get_log_entries($limit = 100) {
        if (!file_exists($this->log_file)) {
            return [];
        }
        
        $lines = file($this->log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $entries = [];
        
        foreach (array_reverse(array_slice($lines, -$limit)) as $line) {
            $entry = json_decode($line, true);
            if ($entry) {
                $entries[] = $entry;
            }
        }
        
        return $entries;
    }
    
    /**
     * Get log statistics
     */
    public function get_log_stats() {
        $entries = $this->get_log_entries(1000);
        
        $stats = [
            'total' => count($entries),
            'by_level' => [],
            'recent_errors' => 0,
            'critical_errors' => 0
        ];
        
        $one_hour_ago = time() - 3600;
        
        foreach ($entries as $entry) {
            $level = $entry['level'] ?? 'UNKNOWN';
            $stats['by_level'][$level] = ($stats['by_level'][$level] ?? 0) + 1;
            
            if ($level === 'CRITICAL') {
                $stats['critical_errors']++;
            }
            
            $entry_time = strtotime($entry['timestamp']);
            if ($entry_time > $one_hour_ago) {
                $stats['recent_errors']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Check if debug mode is enabled
     */
    public function is_debug_mode() {
        return $this->debug_mode;
    }
    
    /**
     * Format error message for display
     */
    public function format_error_message($message, $context = []) {
        $formatted = $message;
        
        if (!empty($context)) {
            $formatted .= ' - Context: ' . json_encode($context);
        }
        
        return $formatted;
    }
}

// Initialize error handler
Askro_Error_Handler::get_instance(); 

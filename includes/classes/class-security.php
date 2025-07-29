<?php
/**
 * Security Class
 *
 * @package    Askro
 * @subpackage Core/Security
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
 * Askro Security Class
 *
 * Handles security measures and performance optimization
 *
 * @since 1.0.0
 */
class Askro_Security {

    /**
     * Rate limiting data
     *
     * @var array
     * @since 1.0.0
     */
    private $rate_limits = [
        'question_post' => ['limit' => 5, 'window' => 3600], // 5 questions per hour
        'answer_post' => ['limit' => 10, 'window' => 3600], // 10 answers per hour
        'comment_post' => ['limit' => 20, 'window' => 3600], // 20 comments per hour
        'vote_cast' => ['limit' => 100, 'window' => 3600], // 100 votes per hour
        'login_attempt' => ['limit' => 5, 'window' => 900], // 5 login attempts per 15 minutes
        'password_reset' => ['limit' => 3, 'window' => 3600], // 3 password resets per hour
        'search_query' => ['limit' => 50, 'window' => 3600], // 50 searches per hour
        'file_upload' => ['limit' => 10, 'window' => 3600] // 10 file uploads per hour
    ];

    /**
     * Blocked IPs
     *
     * @var array
     * @since 1.0.0
     */
    private $blocked_ips = [];

    /**
     * Suspicious patterns
     *
     * @var array
     * @since 1.0.0
     */
    private $suspicious_patterns = [
        'sql_injection' => [
            '/(\bunion\b.*\bselect\b)|(\bselect\b.*\bunion\b)/i',
            '/(\bdrop\b.*\btable\b)|(\btable\b.*\bdrop\b)/i',
            '/(\binsert\b.*\binto\b)|(\binto\b.*\binsert\b)/i',
            '/(\bdelete\b.*\bfrom\b)|(\bfrom\b.*\bdelete\b)/i',
            '/(\bupdate\b.*\bset\b)|(\bset\b.*\bupdate\b)/i'
        ],
        'xss_injection' => [
            '/<script[^>]*>.*?<\/script>/i',
            '/javascript:/i',
            '/on\w+\s*=/i',
            '/<iframe[^>]*>.*?<\/iframe>/i',
            '/<object[^>]*>.*?<\/object>/i'
        ],
        'path_traversal' => [
            '/\.\.\//',
            '/\.\.\\\\/',
            '/\.\.\%2f/',
            '/\.\.\%5c/'
        ],
        'command_injection' => [
            '/;\s*(rm|del|format|fdisk)/i',
            '/\|\s*(nc|netcat|telnet)/i',
            '/`[^`]*`/',
            '/\$\([^)]*\)/'
        ]
    ];

    /**
     * Initialize the security component
     *
     * @since 1.0.0
     */
    public function init() {
        // Security hooks
        add_action('init', [$this, 'security_init']);
        add_action('wp_login_failed', [$this, 'handle_failed_login']);
        add_action('wp_authenticate_user', [$this, 'check_user_security'], 10, 2);
        
        // Content security
        add_filter('askro_before_save_question', [$this, 'sanitize_content'], 10, 2);
        add_filter('askro_before_save_answer', [$this, 'sanitize_content'], 10, 2);
        add_filter('askro_before_save_comment', [$this, 'sanitize_content'], 10, 2);
        
        // Rate limiting
        add_action('askro_before_question_post', [$this, 'check_rate_limit'], 10, 2);
        add_action('askro_before_answer_post', [$this, 'check_rate_limit'], 10, 2);
        add_action('askro_before_comment_post', [$this, 'check_rate_limit'], 10, 2);
        add_action('askro_before_vote_cast', [$this, 'check_rate_limit'], 10, 2);
        
        // File upload security
        add_filter('askro_allowed_file_types', [$this, 'filter_allowed_file_types']);
        add_action('askro_before_file_upload', [$this, 'scan_uploaded_file'], 10, 2);
        
        // AJAX security
        add_action('wp_ajax_askro_security_check', [$this, 'ajax_security_check']);
        add_action('wp_ajax_nopriv_askro_security_check', [$this, 'ajax_security_check']);
        
        // Performance optimization
        add_action('init', [$this, 'setup_caching']);
        add_action('wp_enqueue_scripts', [$this, 'optimize_assets']);
        
        // Database optimization
        add_action('askro_daily_maintenance', [$this, 'database_maintenance']);
        if (!wp_next_scheduled('askro_daily_maintenance')) {
            wp_schedule_event(time(), 'daily', 'askro_daily_maintenance');
        }
        
        // Security monitoring
        add_action('askro_security_scan', [$this, 'security_scan']);
        if (!wp_next_scheduled('askro_security_scan')) {
            wp_schedule_event(time(), 'hourly', 'askro_security_scan');
        }
    }

    /**
     * Initialize security measures
     *
     * @since 1.0.0
     */
    public function security_init() {
        // Load blocked IPs
        $this->blocked_ips = get_option('askro_blocked_ips', []);
        
        // Check if current IP is blocked
        $current_ip = $this->get_client_ip();
        if (in_array($current_ip, $this->blocked_ips)) {
            $this->block_request('IP blocked');
        }
        
        // Check for suspicious activity
        $this->check_suspicious_activity();
        
        // Set security headers
        $this->set_security_headers();
    }

    /**
     * Set security headers
     *
     * @since 1.0.0
     */
    public function set_security_headers() {
        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: SAMEORIGIN');
            header('X-XSS-Protection: 1; mode=block');
            header('Referrer-Policy: strict-origin-when-cross-origin');
            header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        }
    }

    /**
     * Check for suspicious activity
     *
     * @since 1.0.0
     */
    public function check_suspicious_activity() {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $query_string = $_SERVER['QUERY_STRING'] ?? '';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $post_data = $_POST;
        
        $suspicious_found = false;
        $threat_type = '';
        
        // Check all input for suspicious patterns
        $inputs_to_check = [
            'uri' => $request_uri,
            'query' => $query_string,
            'user_agent' => $user_agent,
            'post_data' => serialize($post_data)
        ];
        
        foreach ($this->suspicious_patterns as $pattern_type => $patterns) {
            foreach ($patterns as $pattern) {
                foreach ($inputs_to_check as $input_type => $input_value) {
                    if (preg_match($pattern, $input_value)) {
                        $suspicious_found = true;
                        $threat_type = $pattern_type;
                        break 3;
                    }
                }
            }
        }
        
        if ($suspicious_found) {
            $this->log_security_event('suspicious_activity', [
                'threat_type' => $threat_type,
                'ip' => $this->get_client_ip(),
                'user_agent' => $user_agent,
                'request_uri' => $request_uri,
                'severity' => 'high'
            ]);
            
            // Block request if threat is severe
            if (in_array($threat_type, ['sql_injection', 'command_injection'])) {
                $this->block_request('Malicious activity detected');
            }
        }
    }

    /**
     * Sanitize content
     *
     * @param string $content Content to sanitize
     * @param string $context Context (question, answer, comment)
     * @return string Sanitized content
     * @since 1.0.0
     */
    public function sanitize_content($content, $context = 'general') {
        // Remove potentially dangerous HTML tags
        $allowed_tags = [
            'p' => [],
            'br' => [],
            'strong' => [],
            'b' => [],
            'em' => [],
            'i' => [],
            'u' => [],
            'ul' => [],
            'ol' => [],
            'li' => [],
            'blockquote' => [],
            'code' => [],
            'pre' => [],
            'h1' => [],
            'h2' => [],
            'h3' => [],
            'h4' => [],
            'h5' => [],
            'h6' => [],
            'a' => ['href' => [], 'title' => [], 'target' => []],
            'img' => ['src' => [], 'alt' => [], 'title' => [], 'width' => [], 'height' => []]
        ];
        
        // Sanitize HTML
        $content = wp_kses($content, $allowed_tags);
        
        // Remove suspicious patterns
        foreach ($this->suspicious_patterns as $patterns) {
            foreach ($patterns as $pattern) {
                $content = preg_replace($pattern, '', $content);
            }
        }
        
        // Additional sanitization based on context
        switch ($context) {
            case 'question':
            case 'answer':
                // Allow more formatting for questions and answers
                $content = $this->sanitize_rich_content($content);
                break;
            case 'comment':
                // Stricter sanitization for comments
                $content = $this->sanitize_simple_content($content);
                break;
        }
        
        return $content;
    }

    /**
     * Sanitize rich content (questions/answers)
     *
     * @param string $content Content to sanitize
     * @return string Sanitized content
     * @since 1.0.0
     */
    public function sanitize_rich_content($content) {
        // Convert markdown-like syntax to HTML
        $content = $this->convert_markdown($content);
        
        // Validate and clean URLs
        $content = $this->validate_urls($content);
        
        // Limit content length
        if (strlen($content) > 50000) {
            $content = substr($content, 0, 50000) . '...';
        }
        
        return $content;
    }

    /**
     * Sanitize simple content (comments)
     *
     * @param string $content Content to sanitize
     * @return string Sanitized content
     * @since 1.0.0
     */
    public function sanitize_simple_content($content) {
        // Remove all HTML tags except basic formatting
        $allowed_tags = '<p><br><strong><b><em><i><u>';
        $content = strip_tags($content, $allowed_tags);
        
        // Limit content length
        if (strlen($content) > 5000) {
            $content = substr($content, 0, 5000) . '...';
        }
        
        return $content;
    }

    /**
     * Convert basic markdown to HTML
     *
     * @param string $content Content with markdown
     * @return string HTML content
     * @since 1.0.0
     */
    public function convert_markdown($content) {
        // Bold
        $content = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $content);
        
        // Italic
        $content = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $content);
        
        // Code
        $content = preg_replace('/`(.*?)`/', '<code>$1</code>', $content);
        
        // Links
        $content = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2" target="_blank" rel="noopener">$1</a>', $content);
        
        return $content;
    }

    /**
     * Validate URLs in content
     *
     * @param string $content Content with URLs
     * @return string Content with validated URLs
     * @since 1.0.0
     */
    public function validate_urls($content) {
        return preg_replace_callback(
            '/<a[^>]+href=["\']([^"\']+)["\'][^>]*>/',
            function($matches) {
                $url = $matches[1];
                
                // Check if URL is valid
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    return str_replace($url, '#', $matches[0]);
                }
                
                // Check if URL is from allowed domains
                $parsed_url = parse_url($url);
                $domain = $parsed_url['host'] ?? '';
                
                // Block suspicious domains
                $blocked_domains = ['bit.ly', 'tinyurl.com', 'goo.gl']; // Add more as needed
                if (in_array($domain, $blocked_domains)) {
                    return str_replace($url, '#', $matches[0]);
                }
                
                return $matches[0];
            },
            $content
        );
    }

    /**
     * Check rate limiting
     *
     * @param string $action Action being performed
     * @param int $user_id User ID (0 for guests)
     * @return bool Whether action is allowed
     * @since 1.0.0
     */
    public function check_rate_limit($action, $user_id = 0) {
        if (!isset($this->rate_limits[$action])) {
            return true; // No limit defined
        }
        
        $limit_config = $this->rate_limits[$action];
        $identifier = $user_id > 0 ? "user_{$user_id}" : "ip_" . $this->get_client_ip();
        $cache_key = "askro_rate_limit_{$action}_{$identifier}";
        
        $current_count = get_transient($cache_key) ?: 0;
        
        if ($current_count >= $limit_config['limit']) {
            $this->log_security_event('rate_limit_exceeded', [
                'action' => $action,
                'user_id' => $user_id,
                'ip' => $this->get_client_ip(),
                'current_count' => $current_count,
                'limit' => $limit_config['limit']
            ]);
            
            wp_die(__('تم تجاوز الحد المسموح. يرجى المحاولة لاحقاً.', 'askro'), __('خطأ في المعدل', 'askro'), ['response' => 429]);
        }
        
        // Increment counter
        set_transient($cache_key, $current_count + 1, $limit_config['window']);
        
        return true;
    }

    /**
     * Handle failed login attempts
     *
     * @param string $username Username
     * @since 1.0.0
     */
    public function handle_failed_login($username) {
        $ip = $this->get_client_ip();
        $cache_key = "askro_failed_login_{$ip}";
        
        $failed_attempts = get_transient($cache_key) ?: 0;
        $failed_attempts++;
        
        set_transient($cache_key, $failed_attempts, 900); // 15 minutes
        
        $this->log_security_event('failed_login', [
            'username' => $username,
            'ip' => $ip,
            'attempts' => $failed_attempts
        ]);
        
        // Block IP after 5 failed attempts
        if ($failed_attempts >= 5) {
            $this->block_ip($ip, 'Multiple failed login attempts');
        }
    }

    /**
     * Check user security before authentication
     *
     * @param WP_User|WP_Error $user User object or error
     * @param string $password Password
     * @return WP_User|WP_Error User object or error
     * @since 1.0.0
     */
    public function check_user_security($user, $password) {
        if (is_wp_error($user)) {
            return $user;
        }
        
        $ip = $this->get_client_ip();
        
        // Check if IP is blocked
        if (in_array($ip, $this->blocked_ips)) {
            return new WP_Error('blocked_ip', __('عذراً، تم حظر عنوان IP الخاص بك.', 'askro'));
        }
        
        // Check for suspicious login patterns
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($user_agent) || strlen($user_agent) < 10) {
            $this->log_security_event('suspicious_login', [
                'user_id' => $user->ID,
                'ip' => $ip,
                'user_agent' => $user_agent,
                'reason' => 'Invalid user agent'
            ]);
        }
        
        return $user;
    }

    /**
     * Filter allowed file types
     *
     * @param array $allowed_types Currently allowed types
     * @return array Filtered allowed types
     * @since 1.0.0
     */
    public function filter_allowed_file_types($allowed_types) {
        // Safe file types only
        $safe_types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        
        return array_intersect_key($allowed_types, $safe_types);
    }

    /**
     * Scan uploaded file for security threats
     *
     * @param string $file_path Path to uploaded file
     * @param array $file_info File information
     * @return bool Whether file is safe
     * @since 1.0.0
     */
    public function scan_uploaded_file($file_path, $file_info) {
        // Check file size
        $max_size = 10 * 1024 * 1024; // 10MB
        if (filesize($file_path) > $max_size) {
            wp_die(__('حجم الملف كبير جداً. الحد الأقصى 10 ميجابايت.', 'askro'));
        }
        
        // Check file extension
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'txt', 'doc', 'docx', 'xls', 'xlsx'];
        $file_extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            wp_die(__('نوع الملف غير مسموح.', 'askro'));
        }
        
        // Check MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);
        
        $allowed_mimes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp',
            'application/pdf', 'text/plain',
            'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ];
        
        if (!in_array($mime_type, $allowed_mimes)) {
            wp_die(__('نوع الملف غير صحيح.', 'askro'));
        }
        
        // Scan file content for malicious code (basic check)
        if (in_array($file_extension, ['txt', 'php', 'js', 'html', 'htm'])) {
            $content = file_get_contents($file_path);
            
            foreach ($this->suspicious_patterns as $patterns) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $content)) {
                        $this->log_security_event('malicious_file_upload', [
                            'file_name' => $file_info['name'],
                            'file_type' => $file_extension,
                            'ip' => $this->get_client_ip(),
                            'user_id' => get_current_user_id()
                        ]);
                        
                        wp_die(__('تم اكتشاف محتوى مشبوه في الملف.', 'askro'));
                    }
                }
            }
        }
        
        return true;
    }

    /**
     * AJAX security check
     *
     * @since 1.0.0
     */
    public function ajax_security_check() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'askro_security_nonce')) {
            wp_send_json_error(['message' => __('فشل التحقق الأمني.', 'askro')]);
        }
        
        $check_type = sanitize_text_field($_POST['check_type'] ?? '');
        $data = $_POST['data'] ?? [];
        
        $result = ['safe' => true, 'warnings' => []];
        
        switch ($check_type) {
            case 'content':
                $content = $data['content'] ?? '';
                $result = $this->check_content_security($content);
                break;
                
            case 'url':
                $url = $data['url'] ?? '';
                $result = $this->check_url_security($url);
                break;
                
            case 'file':
                $file_name = $data['file_name'] ?? '';
                $result = $this->check_file_security($file_name);
                break;
        }
        
        wp_send_json_success($result);
    }

    /**
     * Check content security
     *
     * @param string $content Content to check
     * @return array Security check result
     * @since 1.0.0
     */
    public function check_content_security($content) {
        $warnings = [];
        $safe = true;
        
        // Check for suspicious patterns
        foreach ($this->suspicious_patterns as $pattern_type => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $warnings[] = sprintf(__('تم اكتشاف نمط مشبوه: %s', 'askro'), $pattern_type);
                    if (in_array($pattern_type, ['sql_injection', 'xss_injection', 'command_injection'])) {
                        $safe = false;
                    }
                }
            }
        }
        
        // Check content length
        if (strlen($content) > 50000) {
            $warnings[] = __('المحتوى طويل جداً.', 'askro');
        }
        
        // Check for excessive links
        $link_count = preg_match_all('/<a[^>]+href=/i', $content);
        if ($link_count > 10) {
            $warnings[] = __('عدد كبير من الروابط.', 'askro');
        }
        
        return ['safe' => $safe, 'warnings' => $warnings];
    }

    /**
     * Check URL security
     *
     * @param string $url URL to check
     * @return array Security check result
     * @since 1.0.0
     */
    public function check_url_security($url) {
        $warnings = [];
        $safe = true;
        
        // Validate URL format
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $warnings[] = __('تنسيق الرابط غير صحيح.', 'askro');
            $safe = false;
        }
        
        // Check domain
        $parsed_url = parse_url($url);
        $domain = $parsed_url['host'] ?? '';
        
        // Check against blocked domains
        $blocked_domains = get_option('askro_blocked_domains', []);
        if (in_array($domain, $blocked_domains)) {
            $warnings[] = __('النطاق محظور.', 'askro');
            $safe = false;
        }
        
        // Check for suspicious URL patterns
        if (preg_match('/\.(exe|bat|cmd|scr|pif|com)$/i', $url)) {
            $warnings[] = __('رابط لملف قابل للتنفيذ.', 'askro');
            $safe = false;
        }
        
        return ['safe' => $safe, 'warnings' => $warnings];
    }

    /**
     * Check file security
     *
     * @param string $file_name File name to check
     * @return array Security check result
     * @since 1.0.0
     */
    public function check_file_security($file_name) {
        $warnings = [];
        $safe = true;
        
        $extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'txt', 'doc', 'docx', 'xls', 'xlsx'];
        
        if (!in_array($extension, $allowed_extensions)) {
            $warnings[] = __('نوع الملف غير مسموح.', 'askro');
            $safe = false;
        }
        
        // Check for double extensions
        if (preg_match('/\.[^.]+\.[^.]+$/', $file_name)) {
            $warnings[] = __('امتداد مزدوج مشبوه.', 'askro');
            $safe = false;
        }
        
        return ['safe' => $safe, 'warnings' => $warnings];
    }

    /**
     * Block IP address
     *
     * @param string $ip IP address to block
     * @param string $reason Reason for blocking
     * @since 1.0.0
     */
    public function block_ip($ip, $reason = '') {
        if (!in_array($ip, $this->blocked_ips)) {
            $this->blocked_ips[] = $ip;
            update_option('askro_blocked_ips', $this->blocked_ips);
            
            $this->log_security_event('ip_blocked', [
                'ip' => $ip,
                'reason' => $reason
            ]);
        }
    }

    /**
     * Block current request
     *
     * @param string $reason Reason for blocking
     * @since 1.0.0
     */
    public function block_request($reason = '') {
        $this->log_security_event('request_blocked', [
            'ip' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'request_uri' => $_SERVER['REQUEST_URI'] ?? '',
            'reason' => $reason
        ]);
        
        wp_die(__('تم حظر الطلب لأسباب أمنية.', 'askro'), __('وصول محظور', 'askro'), ['response' => 403]);
    }

    /**
     * Get client IP address
     *
     * @return string Client IP address
     * @since 1.0.0
     */
    public function get_client_ip() {
        $ip_keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Log security event
     *
     * @param string $event_type Event type
     * @param array $data Event data
     * @since 1.0.0
     */
    public function log_security_event($event_type, $data = []) {
        global $wpdb;
        
        // Check if security logs table exists
        $table_name = $wpdb->prefix . 'askro_security_logs';
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        
        if (!$table_exists) {
            // Log to error log instead
            // Table does not exist - this is expected during development
            return;
        }
        
        $log_data = [
            'event_type' => $event_type,
            'event_data' => json_encode($data),
            'ip_address' => $this->get_client_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'user_id' => is_user_logged_in() ? get_current_user_id() : 0,
            'date_created' => current_time('mysql')
        ];
        
        $wpdb->insert(
            $table_name,
            $log_data,
            ['%s', '%s', '%s', '%s', '%d', '%s']
        );
        
        // Send alert for critical events
        if (in_array($event_type, ['ip_blocked', 'malicious_file_upload', 'suspicious_activity'])) {
            $this->send_security_alert($event_type, $data);
        }
    }

    /**
     * Send security alert
     *
     * @param string $event_type Event type
     * @param array $data Event data
     * @since 1.0.0
     */
    public function send_security_alert($event_type, $data) {
        $admin_email = get_option('admin_email');
        $site_name = get_bloginfo('name');
        
        $subject = sprintf('[%s] %s', $site_name, __('تنبيه أمني', 'askro'));
        
        $message = sprintf(
            __('تم اكتشاف نشاط أمني مشبوه على موقع %s', 'askro') . "\n\n" .
            __('نوع الحدث: %s', 'askro') . "\n" .
            __('الوقت: %s', 'askro') . "\n" .
            __('عنوان IP: %s', 'askro') . "\n" .
            __('تفاصيل إضافية: %s', 'askro'),
            $site_name,
            $event_type,
            current_time('Y-m-d H:i:s'),
            $data['ip'] ?? 'غير معروف',
            json_encode($data, JSON_UNESCAPED_UNICODE)
        );
        
        wp_mail($admin_email, $subject, $message);
    }

    /**
     * Setup caching for performance
     *
     * @since 1.0.0
     */
    public function setup_caching() {
        // Enable object caching for Askro data
        if (!wp_using_ext_object_cache()) {
            // Use transients for caching when no external cache is available
            add_filter('askro_cache_get', [$this, 'transient_cache_get'], 10, 2);
            add_filter('askro_cache_set', [$this, 'transient_cache_set'], 10, 3);
            add_filter('askro_cache_delete', [$this, 'transient_cache_delete'], 10, 1);
        }
    }

    /**
     * Get cached data using transients
     *
     * @param mixed $value Default value
     * @param string $key Cache key
     * @return mixed Cached value or default
     * @since 1.0.0
     */
    public function transient_cache_get($value, $key) {
        return get_transient('askro_cache_' . $key) ?: $value;
    }

    /**
     * Set cached data using transients
     *
     * @param bool $result Default result
     * @param string $key Cache key
     * @param mixed $value Value to cache
     * @return bool Success
     * @since 1.0.0
     */
    public function transient_cache_set($result, $key, $value) {
        return set_transient('askro_cache_' . $key, $value, HOUR_IN_SECONDS);
    }

    /**
     * Delete cached data
     *
     * @param bool $result Default result
     * @param string $key Cache key
     * @return bool Success
     * @since 1.0.0
     */
    public function transient_cache_delete($result, $key) {
        return delete_transient('askro_cache_' . $key);
    }

    /**
     * Optimize assets loading
     *
     * @since 1.0.0
     */
    public function optimize_assets() {
        // Minify CSS and JS in production
        if (!WP_DEBUG) {
            add_filter('askro_minify_css', '__return_true');
            add_filter('askro_minify_js', '__return_true');
        }
        
        // Defer non-critical JavaScript
        add_filter('script_loader_tag', [$this, 'defer_scripts'], 10, 2);
        
        // Preload critical resources
        add_action('wp_head', [$this, 'preload_critical_resources']);
    }

    /**
     * Defer non-critical scripts
     *
     * @param string $tag Script tag
     * @param string $handle Script handle
     * @return string Modified script tag
     * @since 1.0.0
     */
    public function defer_scripts($tag, $handle) {
        $defer_scripts = ['askro-main-script', 'askro-admin-script'];
        
        if (in_array($handle, $defer_scripts)) {
            return str_replace(' src', ' defer src', $tag);
        }
        
        return $tag;
    }

    /**
     * Preload critical resources
     *
     * @since 1.0.0
     */
    public function preload_critical_resources() {
        $critical_resources = [
            plugins_url('assets/css/main.css', ASKRO_PLUGIN_FILE),
            plugins_url('assets/js/main.js', ASKRO_PLUGIN_FILE)
        ];
        
        foreach ($critical_resources as $resource) {
            $type = pathinfo($resource, PATHINFO_EXTENSION) === 'css' ? 'style' : 'script';
            echo '<link rel="preload" href="' . esc_url($resource) . '" as="' . $type . '">' . "\n";
        }
    }

    /**
     * Database maintenance
     *
     * @since 1.0.0
     */
    public function database_maintenance() {
        global $wpdb;
        
        // Check if security logs table exists before cleaning
        $security_logs_table = $wpdb->prefix . 'askro_security_logs';
        $security_logs_exists = $wpdb->get_var("SHOW TABLES LIKE '$security_logs_table'") === $security_logs_table;
        
        if ($security_logs_exists) {
            // Clean old security logs (keep last 30 days)
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}askro_security_logs 
                 WHERE date_created < %s",
                date('Y-m-d H:i:s', strtotime('-30 days'))
            ));
        }
        
        // Clean old analytics data (keep last 1 year)
        $analytics_table = $wpdb->prefix . 'askro_analytics';
        $analytics_exists = $wpdb->get_var("SHOW TABLES LIKE '$analytics_table'") === $analytics_table;
        
        if ($analytics_exists) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}askro_analytics 
                 WHERE date_created < %s",
                date('Y-m-d H:i:s', strtotime('-1 year'))
            ));
        }
        
        // Clean old notifications (keep last 90 days)
        $notifications_table = $wpdb->prefix . 'askro_notifications';
        $notifications_exists = $wpdb->get_var("SHOW TABLES LIKE '$notifications_table'") === $notifications_table;
        
        if ($notifications_exists) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}askro_notifications 
                 WHERE date_created < %s",
                date('Y-m-d H:i:s', strtotime('-90 days'))
            ));
        }
        
        // Optimize database tables
        $tables = [
            $wpdb->prefix . 'askro_votes',
            $wpdb->prefix . 'askro_comments',
            $wpdb->prefix . 'askro_user_points',
            $wpdb->prefix . 'askro_user_badges',
            $wpdb->prefix . 'askro_notifications',
            $wpdb->prefix . 'askro_analytics'
        ];
        
        // Add security logs table if it exists
        if ($security_logs_exists) {
            $tables[] = $wpdb->prefix . 'askro_security_logs';
        }
        
        foreach ($tables as $table) {
            $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            if ($table_exists) {
                $wpdb->query("OPTIMIZE TABLE {$table}");
            }
        }
        
        // Clear expired transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_timeout_askro_%' 
             AND option_value < UNIX_TIMESTAMP()"
        );
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_askro_%' 
             AND option_name NOT IN (
                 SELECT CONCAT('_transient_', SUBSTRING(option_name, 19))
                 FROM {$wpdb->options} 
                 WHERE option_name LIKE '_transient_timeout_askro_%'
             )"
        );
    }

    /**
     * Security scan
     *
     * @since 1.0.0
     */
    public function security_scan() {
        // Check for suspicious user accounts
        $this->scan_user_accounts();
        
        // Check for suspicious content
        $this->scan_content();
        
        // Check for file integrity
        $this->scan_file_integrity();
        
        // Update security status
        update_option('askro_last_security_scan', current_time('mysql'));
    }

    /**
     * Scan user accounts for suspicious activity
     *
     * @since 1.0.0
     */
    public function scan_user_accounts() {
        global $wpdb;
        
        // Check for users with suspicious usernames
        $suspicious_usernames = ['admin', 'administrator', 'root', 'test', 'demo'];
        
        foreach ($suspicious_usernames as $username) {
            $user = get_user_by('login', $username);
            if ($user && $user->ID !== 1) { // Skip if it's the main admin
                $this->log_security_event('suspicious_username', [
                    'user_id' => $user->ID,
                    'username' => $username
                ]);
            }
        }
        
        // Check for users with weak passwords (if possible)
        $users_with_weak_passwords = $wpdb->get_results(
            "SELECT ID, user_login FROM {$wpdb->users} 
             WHERE user_pass = MD5(CONCAT(user_login, 'password')) 
             OR user_pass = MD5('password') 
             OR user_pass = MD5('123456')"
        );
        
        foreach ($users_with_weak_passwords as $user) {
            $this->log_security_event('weak_password', [
                'user_id' => $user->ID,
                'username' => $user->user_login
            ]);
        }
    }

    /**
     * Scan content for suspicious patterns
     *
     * @since 1.0.0
     */
    public function scan_content() {
        global $wpdb;
        
        // Check recent questions and answers for suspicious content
        $recent_posts = $wpdb->get_results(
            "SELECT ID, post_title, post_content, post_author 
             FROM {$wpdb->posts} 
             WHERE post_type IN ('askro_question', 'askro_answer') 
             AND post_status = 'publish' 
             AND post_date > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
        );
        
        foreach ($recent_posts as $post) {
            $content = $post->post_title . ' ' . $post->post_content;
            
            foreach ($this->suspicious_patterns as $pattern_type => $patterns) {
                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $content)) {
                        $this->log_security_event('suspicious_content', [
                            'post_id' => $post->ID,
                            'post_author' => $post->post_author,
                            'pattern_type' => $pattern_type
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Scan file integrity
     *
     * @since 1.0.0
     */
    public function scan_file_integrity() {
        $plugin_dir = plugin_dir_path(ASKRO_PLUGIN_FILE);
        $critical_files = [
            'askro.php',
            'includes/classes/class-security.php',
            'includes/classes/class-database.php'
        ];
        
        foreach ($critical_files as $file) {
            $file_path = $plugin_dir . $file;
            
            if (!file_exists($file_path)) {
                $this->log_security_event('missing_file', [
                    'file' => $file,
                    'severity' => 'critical'
                ]);
                continue;
            }
            
            // Check file permissions
            $perms = fileperms($file_path);
            if (($perms & 0x0002) || ($perms & 0x0008)) { // World writable
                $this->log_security_event('insecure_permissions', [
                    'file' => $file,
                    'permissions' => decoct($perms & 0777)
                ]);
            }
        }
    }

    /**
     * Get security status
     *
     * @return array Security status
     * @since 1.0.0
     */
    public function get_security_status() {
        global $wpdb;
        
        $last_scan = get_option('askro_last_security_scan');
        $blocked_ips_count = count($this->blocked_ips);
        
        // Check if security logs table exists
        $security_logs_table = $wpdb->prefix . 'askro_security_logs';
        $security_logs_exists = $wpdb->get_var("SHOW TABLES LIKE '$security_logs_table'") === $security_logs_table;
        
        $recent_events = 0;
        $critical_events = 0;
        
        if ($security_logs_exists) {
            // Count recent security events
            $recent_events = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}askro_security_logs 
                 WHERE date_created > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            
            // Count critical events
            $critical_events = $wpdb->get_var(
                "SELECT COUNT(*) FROM {$wpdb->prefix}askro_security_logs 
                 WHERE event_type IN ('ip_blocked', 'malicious_file_upload', 'suspicious_activity') 
                 AND date_created > DATE_SUB(NOW(), INTERVAL 7 DAYS)"
            );
        }
        
        $status = 'good';
        if ($critical_events > 10) {
            $status = 'critical';
        } elseif ($critical_events > 5 || $recent_events > 50) {
            $status = 'warning';
        }
        
        return [
            'status' => $status,
            'last_scan' => $last_scan,
            'blocked_ips' => $blocked_ips_count,
            'recent_events' => $recent_events,
            'critical_events' => $critical_events,
            'security_logs_table_exists' => $security_logs_exists
        ];
    }
}


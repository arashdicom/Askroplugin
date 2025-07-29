<?php
/**
 * API Authentication Handler Class
 *
 * @package    Askro
 * @subpackage Core/API/Auth
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
 * Askro API Authentication Class
 *
 * Handles authentication and authorization for API endpoints
 *
 * @since 1.0.0
 */
class Askro_API_Auth {

    /**
     * API key prefix
     *
     * @var string
     * @since 1.0.0
     */
    private $api_key_prefix = 'askro_api_';

    /**
     * Token expiration time
     *
     * @var int
     * @since 1.0.0
     */
    private $token_expiration = 86400; // 24 hours

    /**
     * Initialize the authentication component
     *
     * @since 1.0.0
     */
    public function init() {
        add_action('rest_api_init', [$this, 'add_auth_headers']);
        add_filter('rest_authentication_errors', [$this, 'authenticate_request']);
        add_action('wp_ajax_askro_generate_api_key', [$this, 'handle_generate_api_key_ajax']);
        add_action('wp_ajax_askro_revoke_api_key', [$this, 'handle_revoke_api_key_ajax']);
    }

    /**
     * Add authentication headers
     *
     * @since 1.0.0
     */
    public function add_auth_headers() {
        header('Access-Control-Allow-Headers: Authorization, Content-Type');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    }

    /**
     * Authenticate API request
     *
     * @param WP_Error|null $result
     * @return WP_Error|null
     * @since 1.0.0
     */
    public function authenticate_request($result) {
        // If there's already an error, return it
        if ($result !== null) {
            return $result;
        }

        // Check if this is an Askro API request
        if (!$this->is_askro_api_request()) {
            return null;
        }

        // Get authorization header
        $auth_header = $this->get_auth_header();
        if (!$auth_header) {
            return new WP_Error(
                'askro_no_auth',
                'مطلوب مصادقة للوصول إلى API',
                ['status' => 401]
            );
        }

        // Parse authorization header
        $auth_data = $this->parse_auth_header($auth_header);
        if (is_wp_error($auth_data)) {
            return $auth_data;
        }

        // Validate authentication method
        switch ($auth_data['method']) {
            case 'Bearer':
                return $this->validate_bearer_token($auth_data['token']);
            case 'ApiKey':
                return $this->validate_api_key($auth_data['token']);
            case 'Basic':
                return $this->validate_basic_auth($auth_data['token']);
            default:
                return new WP_Error(
                    'askro_invalid_auth_method',
                    'طريقة مصادقة غير مدعومة',
                    ['status' => 401]
                );
        }
    }

    /**
     * Check if this is an Askro API request
     *
     * @return bool
     * @since 1.0.0
     */
    private function is_askro_api_request() {
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        return strpos($request_uri, '/wp-json/askro/') !== false;
    }

    /**
     * Get authorization header
     *
     * @return string|false
     * @since 1.0.0
     */
    private function get_auth_header() {
        $headers = getallheaders();
        
        // Check for Authorization header
        if (isset($headers['Authorization'])) {
            return $headers['Authorization'];
        }

        // Check for HTTP_AUTHORIZATION
        if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            return $_SERVER['HTTP_AUTHORIZATION'];
        }

        // Check for REDIRECT_HTTP_AUTHORIZATION
        if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            return $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }

        return false;
    }

    /**
     * Parse authorization header
     *
     * @param string $header
     * @return array|WP_Error
     * @since 1.0.0
     */
    private function parse_auth_header($header) {
        $parts = explode(' ', trim($header), 2);
        
        if (count($parts) !== 2) {
            return new WP_Error(
                'askro_invalid_auth_header',
                'ترويسة المصادقة غير صحيحة',
                ['status' => 401]
            );
        }

        return [
            'method' => $parts[0],
            'token' => $parts[1]
        ];
    }

    /**
     * Validate Bearer token
     *
     * @param string $token
     * @return WP_Error|null
     * @since 1.0.0
     */
    private function validate_bearer_token($token) {
        // Decode JWT token
        $payload = $this->decode_jwt_token($token);
        if (is_wp_error($payload)) {
            return $payload;
        }

        // Check if token is expired
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return new WP_Error(
                'askro_token_expired',
                'انتهت صلاحية الرمز المميز',
                ['status' => 401]
            );
        }

        // Set current user
        if (isset($payload['user_id'])) {
            wp_set_current_user($payload['user_id']);
        }

        return null;
    }

    /**
     * Validate API key
     *
     * @param string $api_key
     * @return WP_Error|null
     * @since 1.0.0
     */
    private function validate_api_key($api_key) {
        // Get user ID from API key
        $user_id = $this->get_user_id_from_api_key($api_key);
        if (!$user_id) {
            return new WP_Error(
                'askro_invalid_api_key',
                'مفتاح API غير صحيح',
                ['status' => 401]
            );
        }

        // Check if API key is active
        if (!$this->is_api_key_active($api_key)) {
            return new WP_Error(
                'askro_inactive_api_key',
                'مفتاح API غير نشط',
                ['status' => 401]
            );
        }

        // Set current user
        wp_set_current_user($user_id);

        return null;
    }

    /**
     * Validate Basic authentication
     *
     * @param string $credentials
     * @return WP_Error|null
     * @since 1.0.0
     */
    private function validate_basic_auth($credentials) {
        $decoded = base64_decode($credentials);
        $parts = explode(':', $decoded, 2);
        
        if (count($parts) !== 2) {
            return new WP_Error(
                'askro_invalid_basic_auth',
                'بيانات المصادقة الأساسية غير صحيحة',
                ['status' => 401]
            );
        }

        $username = $parts[0];
        $password = $parts[1];

        // Authenticate user
        $user = wp_authenticate($username, $password);
        if (is_wp_error($user)) {
            return new WP_Error(
                'askro_invalid_credentials',
                'اسم المستخدم أو كلمة المرور غير صحيحة',
                ['status' => 401]
            );
        }

        // Set current user
        wp_set_current_user($user->ID);

        return null;
    }

    /**
     * Decode JWT token
     *
     * @param string $token
     * @return array|WP_Error
     * @since 1.0.0
     */
    private function decode_jwt_token($token) {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return new WP_Error(
                'askro_invalid_jwt',
                'رمز JWT غير صحيح',
                ['status' => 401]
            );
        }

        $header = json_decode(base64_decode($parts[0]), true);
        $payload = json_decode(base64_decode($parts[1]), true);
        $signature = $parts[2];

        // Verify signature
        $expected_signature = hash_hmac('sha256', $parts[0] . '.' . $parts[1], $this->get_jwt_secret());
        
        if (!hash_equals($expected_signature, $signature)) {
            return new WP_Error(
                'askro_invalid_signature',
                'توقيع JWT غير صحيح',
                ['status' => 401]
            );
        }

        return $payload;
    }

    /**
     * Generate JWT token
     *
     * @param int $user_id
     * @param array $payload
     * @return string
     * @since 1.0.0
     */
    public function generate_jwt_token($user_id, $payload = []) {
        $header = [
            'alg' => 'HS256',
            'typ' => 'JWT'
        ];

        $default_payload = [
            'user_id' => $user_id,
            'iat' => time(),
            'exp' => time() + $this->token_expiration
        ];

        $payload = array_merge($default_payload, $payload);

        $header_encoded = base64_encode(json_encode($header));
        $payload_encoded = base64_encode(json_encode($payload));
        
        $signature = hash_hmac('sha256', $header_encoded . '.' . $payload_encoded, $this->get_jwt_secret());
        
        return $header_encoded . '.' . $payload_encoded . '.' . $signature;
    }

    /**
     * Get JWT secret
     *
     * @return string
     * @since 1.0.0
     */
    private function get_jwt_secret() {
        $secret = get_option('askro_jwt_secret');
        
        if (!$secret) {
            $secret = wp_generate_password(64, true, true);
            update_option('askro_jwt_secret', $secret);
        }

        return $secret;
    }

    /**
     * Generate API key for user
     *
     * @param int $user_id
     * @return string
     * @since 1.0.0
     */
    public function generate_api_key($user_id) {
        $api_key = $this->api_key_prefix . wp_generate_password(32, false);
        
        // Store API key
        $this->store_api_key($user_id, $api_key);
        
        return $api_key;
    }

    /**
     * Store API key
     *
     * @param int $user_id
     * @param string $api_key
     * @since 1.0.0
     */
    private function store_api_key($user_id, $api_key) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'askro_user_settings';
        
        $wpdb->replace(
            $table_name,
            [
                'user_id' => $user_id,
                'setting_key' => 'api_key',
                'setting_value' => $api_key,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s']
        );
    }

    /**
     * Get user ID from API key
     *
     * @param string $api_key
     * @return int|false
     * @since 1.0.0
     */
    private function get_user_id_from_api_key($api_key) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'askro_user_settings';
        
        $user_id = $wpdb->get_var($wpdb->prepare(
            "SELECT user_id FROM {$table_name} WHERE setting_key = 'api_key' AND setting_value = %s",
            $api_key
        ));
        
        return $user_id ? (int) $user_id : false;
    }

    /**
     * Check if API key is active
     *
     * @param string $api_key
     * @return bool
     * @since 1.0.0
     */
    private function is_api_key_active($api_key) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'askro_user_settings';
        
        $active = $wpdb->get_var($wpdb->prepare(
            "SELECT setting_value FROM {$table_name} WHERE setting_key = 'api_key_active' AND setting_value = %s",
            $api_key
        ));
        
        return $active !== null;
    }

    /**
     * Revoke API key
     *
     * @param string $api_key
     * @return bool
     * @since 1.0.0
     */
    public function revoke_api_key($api_key) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'askro_user_settings';
        
        $result = $wpdb->delete(
            $table_name,
            [
                'setting_key' => 'api_key',
                'setting_value' => $api_key
            ],
            ['%s', '%s']
        );
        
        return $result !== false;
    }

    /**
     * Generate API key AJAX handler
     *
     * @since 1.0.0
     */
    public function handle_generate_api_key_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'askro_generate_api_key')) {
            wp_send_json_error(['message' => 'فشل التحقق من الأمان']);
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => 'يجب تسجيل الدخول']);
        }

        $api_key = $this->generate_api_key($user_id);
        
        wp_send_json_success([
            'api_key' => $api_key,
            'message' => 'تم إنشاء مفتاح API بنجاح'
        ]);
    }

    /**
     * Revoke API key AJAX handler
     *
     * @since 1.0.0
     */
    public function handle_revoke_api_key_ajax() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'askro_revoke_api_key')) {
            wp_send_json_error(['message' => 'فشل التحقق من الأمان']);
        }

        $user_id = get_current_user_id();
        if (!$user_id) {
            wp_send_json_error(['message' => 'يجب تسجيل الدخول']);
        }

        $api_key = sanitize_text_field($_POST['api_key'] ?? '');
        if (!$api_key) {
            wp_send_json_error(['message' => 'مفتاح API مطلوب']);
        }

        // Verify API key belongs to user
        $key_user_id = $this->get_user_id_from_api_key($api_key);
        if ($key_user_id !== $user_id) {
            wp_send_json_error(['message' => 'غير مصرح لك بإلغاء هذا المفتاح']);
        }

        $result = $this->revoke_api_key($api_key);
        
        if ($result) {
            wp_send_json_success(['message' => 'تم إلغاء مفتاح API بنجاح']);
        } else {
            wp_send_json_error(['message' => 'فشل في إلغاء مفتاح API']);
        }
    }

    /**
     * Get user API keys
     *
     * @param int $user_id
     * @return array
     * @since 1.0.0
     */
    public function get_user_api_keys($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'askro_user_settings';
        
        $keys = $wpdb->get_results($wpdb->prepare(
            "SELECT setting_value, created_at FROM {$table_name} WHERE user_id = %d AND setting_key = 'api_key'",
            $user_id
        ));
        
        return array_map(function($key) {
            return [
                'key' => $key->setting_value,
                'created_at' => $key->created_at
            ];
        }, $keys);
    }

    /**
     * Check if user has API access
     *
     * @param int $user_id
     * @return bool
     * @since 1.0.0
     */
    public function user_has_api_access($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return false;
        }

        // Check user role
        $allowed_roles = ['administrator', 'editor', 'author'];
        $user_roles = $user->roles;
        
        foreach ($user_roles as $role) {
            if (in_array($role, $allowed_roles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get API usage statistics
     *
     * @param int $user_id
     * @return array
     * @since 1.0.0
     */
    public function get_api_usage_stats($user_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'askro_analytics';
        
        $stats = $wpdb->get_results($wpdb->prepare(
            "SELECT 
                DATE(created_at) as date,
                COUNT(*) as requests,
                SUM(CASE WHEN response_code >= 200 AND response_code < 300 THEN 1 ELSE 0 END) as success_requests,
                SUM(CASE WHEN response_code >= 400 THEN 1 ELSE 0 END) as error_requests
            FROM {$table_name} 
            WHERE user_id = %d AND event_type = 'api_request'
            GROUP BY DATE(created_at)
            ORDER BY date DESC
            LIMIT 30",
            $user_id
        ));
        
        return $stats;
    }

    /**
     * Log API request
     *
     * @param string $endpoint
     * @param int $response_code
     * @param int $user_id
     * @param array $data
     * @since 1.0.0
     */
    public function log_api_request($endpoint, $response_code, $user_id = 0, $data = []) {
        askro_log_analytics('api_request', $user_id, 'api', 0, [
            'endpoint' => $endpoint,
            'response_code' => $response_code,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'data' => $data
        ]);
    }
} 

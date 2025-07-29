<?php
/**
 * API Helper Functions
 *
 * @package    Askro
 * @subpackage Functions/API
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
 * Get API base URL
 *
 * @return string
 * @since 1.0.0
 */
function askro_get_api_url() {
    return get_rest_url(null, 'askro/v1');
}

/**
 * Generate JWT token for user
 *
 * @param int $user_id
 * @param array $payload
 * @return string
 * @since 1.0.0
 */
function askro_generate_jwt_token($user_id, $payload = []) {
    $api_auth = askro()->get_component('api_auth');
    if ($api_auth) {
        return $api_auth->generate_jwt_token($user_id, $payload);
    }
    return '';
}

/**
 * Generate API key for user
 *
 * @param int $user_id
 * @return string
 * @since 1.0.0
 */
function askro_generate_api_key($user_id) {
    $api_auth = askro()->get_component('api_auth');
    if ($api_auth) {
        return $api_auth->generate_api_key($user_id);
    }
    return '';
}

/**
 * Get user API keys
 *
 * @param int $user_id
 * @return array
 * @since 1.0.0
 */
function askro_get_user_api_keys($user_id) {
    $api_auth = askro()->get_component('api_auth');
    if ($api_auth) {
        return $api_auth->get_user_api_keys($user_id);
    }
    return [];
}

/**
 * Check if user has API access
 *
 * @param int $user_id
 * @return bool
 * @since 1.0.0
 */
function askro_user_has_api_access($user_id) {
    $api_auth = askro()->get_component('api_auth');
    if ($api_auth) {
        return $api_auth->user_has_api_access($user_id);
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
function askro_get_api_usage_stats($user_id) {
    $api_auth = askro()->get_component('api_auth');
    if ($api_auth) {
        return $api_auth->get_api_usage_stats($user_id);
    }
    return [];
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
function askro_log_api_request($endpoint, $response_code, $user_id = 0, $data = []) {
    $api_auth = askro()->get_component('api_auth');
    if ($api_auth) {
        $api_auth->log_api_request($endpoint, $response_code, $user_id, $data);
    }
}

/**
 * Cache API response
 *
 * @param string $key
 * @param mixed $data
 * @param int $expiration
 * @return bool
 * @since 1.0.0
 */
function askro_cache_api_response($key, $data, $expiration = null) {
    $api_cache = askro()->get_component('api_cache');
    if ($api_cache) {
        return $api_cache->set($key, $data, $expiration);
    }
    return false;
}

/**
 * Get cached API response
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 * @since 1.0.0
 */
function askro_get_cached_api_response($key, $default = false) {
    $api_cache = askro()->get_component('api_cache');
    if ($api_cache) {
        return $api_cache->get($key, $default);
    }
    return $default;
}

/**
 * Clear API cache
 *
 * @param string $pattern
 * @since 1.0.0
 */
function askro_clear_api_cache($pattern = '') {
    $api_cache = askro()->get_component('api_cache');
    if ($api_cache) {
        if ($pattern) {
            $api_cache->delete_pattern($pattern);
        } else {
            $api_cache->clear_all();
        }
    }
}

/**
 * Get API cache statistics
 *
 * @return array
 * @since 1.0.0
 */
function askro_get_api_cache_stats() {
    $api_cache = askro()->get_component('api_cache');
    if ($api_cache) {
        return $api_cache->get_cache_stats();
    }
    return [];
}

/**
 * Warm up API cache
 *
 * @since 1.0.0
 */
function askro_warm_up_api_cache() {
    $api_cache = askro()->get_component('api_cache');
    if ($api_cache) {
        $api_cache->warm_up_cache();
    }
}

/**
 * Format API response
 *
 * @param mixed $data
 * @param string $message
 * @param int $status
 * @return array
 * @since 1.0.0
 */
function askro_format_api_response($data, $message = '', $status = 200) {
    $response = [
        'success' => $status >= 200 && $status < 300,
        'data' => $data
    ];

    if ($message) {
        $response['message'] = $message;
    }

    if ($status !== 200) {
        $response['error'] = [
            'code' => $status,
            'message' => $message
        ];
    }

    return $response;
}

/**
 * Validate API request
 *
 * @param array $required_fields
 * @param array $data
 * @return WP_Error|true
 * @since 1.0.0
 */
function askro_validate_api_request($required_fields, $data) {
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            return new WP_Error(
                'askro_missing_field',
                sprintf('الحقل "%s" مطلوب', $field),
                ['status' => 400]
            );
        }
    }
    return true;
}

/**
 * Sanitize API input
 *
 * @param mixed $input
 * @param string $type
 * @return mixed
 * @since 1.0.0
 */
function askro_sanitize_api_input($input, $type = 'text') {
    switch ($type) {
        case 'text':
            return sanitize_text_field($input);
        case 'textarea':
            return sanitize_textarea_field($input);
        case 'html':
            return wp_kses_post($input);
        case 'email':
            return sanitize_email($input);
        case 'url':
            return esc_url_raw($input);
        case 'int':
            return intval($input);
        case 'float':
            return floatval($input);
        case 'array':
            return is_array($input) ? array_map('sanitize_text_field', $input) : [];
        default:
            return sanitize_text_field($input);
    }
}

/**
 * Get API rate limit info
 *
 * @param string $endpoint
 * @return array
 * @since 1.0.0
 */
function askro_get_api_rate_limit($endpoint) {
    $limits = [
        'questions' => ['requests' => 100, 'window' => 3600],
        'answers' => ['requests' => 50, 'window' => 3600],
        'votes' => ['requests' => 200, 'window' => 3600],
        'comments' => ['requests' => 100, 'window' => 3600],
        'search' => ['requests' => 300, 'window' => 3600]
    ];

    return $limits[$endpoint] ?? ['requests' => 100, 'window' => 3600];
}

/**
 * Check API rate limit
 *
 * @param string $endpoint
 * @param int $user_id
 * @return bool
 * @since 1.0.0
 */
function askro_check_api_rate_limit($endpoint, $user_id) {
    $limit_info = askro_get_api_rate_limit($endpoint);
    $key = "askro_rate_limit_{$endpoint}_{$user_id}";
    $current = get_transient($key) ?: 0;

    if ($current >= $limit_info['requests']) {
        return false;
    }

    set_transient($key, $current + 1, $limit_info['window']);
    return true;
}

/**
 * Get API documentation
 *
 * @return array
 * @since 1.0.0
 */
function askro_get_api_documentation() {
    $api_docs = askro()->get_component('api_docs');
    if ($api_docs) {
        return $api_docs->generate_api_docs();
    }
    return [];
}

/**
 * Test API endpoint
 *
 * @param string $endpoint
 * @param string $method
 * @param array $data
 * @param string $auth_token
 * @return array
 * @since 1.0.0
 */
function askro_test_api_endpoint($endpoint, $method = 'GET', $data = [], $auth_token = '') {
    $api_url = askro_get_api_url() . $endpoint;
    
    $args = [
        'method' => $method,
        'headers' => [
            'Content-Type' => 'application/json',
            'X-WP-Nonce' => wp_create_nonce('wp_rest')
        ]
    ];

    if ($auth_token) {
        $args['headers']['Authorization'] = 'Bearer ' . $auth_token;
    }

    if ($method === 'POST' && !empty($data)) {
        $args['body'] = json_encode($data);
    }

    $response = wp_remote_request($api_url, $args);

    if (is_wp_error($response)) {
        return [
            'success' => false,
            'error' => $response->get_error_message()
        ];
    }

    $body = wp_remote_retrieve_body($response);
    $response_data = json_decode($body, true);

    return [
        'success' => true,
        'status_code' => wp_remote_retrieve_response_code($response),
        'data' => $response_data
    ];
}

/**
 * Get API health status
 *
 * @return array
 * @since 1.0.0
 */
function askro_get_api_health_status() {
    $status = [
        'api_enabled' => true,
        'cache_enabled' => true,
        'auth_enabled' => true,
        'rate_limiting_enabled' => true,
        'endpoints' => [
            'questions' => true,
            'answers' => true,
            'votes' => true,
            'search' => true,
            'leaderboard' => true,
            'users' => true
        ]
    ];

    // Check if components are loaded
    $api = askro()->get_component('api');
    $api_cache = askro()->get_component('api_cache');
    $api_auth = askro()->get_component('api_auth');

    if (!$api) {
        $status['api_enabled'] = false;
    }

    if (!$api_cache) {
        $status['cache_enabled'] = false;
    }

    if (!$api_auth) {
        $status['auth_enabled'] = false;
    }

    return $status;
} 

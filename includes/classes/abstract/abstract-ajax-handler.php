<?php
/**
 * Abstract AJAX Handler Base Class
 *
 * @package    Askro
 * @subpackage Abstract
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
 * Abstract AJAX Handler Base Class
 *
 * Provides common functionality for all AJAX handlers
 *
 * @since 1.0.0
 */
abstract class Askro_Abstract_Ajax_Handler implements Askro_Ajax_Handler_Interface {

    /**
     * Current user ID
     *
     * @var int
     * @since 1.0.0
     */
    protected $user_id;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->user_id = get_current_user_id();
        $this->init();
    }

    /**
     * Initialize the handler
     *
     * @since 1.0.0
     */
    public function init() {
        $this->register_actions();
    }

    /**
     * Verify nonce for security
     *
     * @param string $action The action name
     * @return bool
     * @since 1.0.0
     */
    public function verify_nonce($action) {
        $nonce = $_POST['nonce'] ?? $_GET['nonce'] ?? '';
        return wp_verify_nonce($nonce, $action);
    }

    /**
     * Send JSON response
     *
     * @param mixed $data Response data
     * @param int $status_code HTTP status code
     * @since 1.0.0
     */
    public function send_response($data, $status_code = 200) {
        wp_send_json_success($data, $status_code);
    }

    /**
     * Send error response
     *
     * @param string $message Error message
     * @param int $status_code HTTP status code
     * @since 1.0.0
     */
    public function send_error($message, $status_code = 400) {
        wp_send_json_error(['message' => $message], $status_code);
    }

    /**
     * Get sanitized POST data
     *
     * @param string $key Data key
     * @param mixed $default Default value
     * @return mixed
     * @since 1.0.0
     */
    protected function get_post_data($key, $default = null) {
        return isset($_POST[$key]) ? sanitize_text_field($_POST[$key]) : $default;
    }

    /**
     * Get sanitized GET data
     *
     * @param string $key Data key
     * @param mixed $default Default value
     * @return mixed
     * @since 1.0.0
     */
    protected function get_get_data($key, $default = null) {
        return isset($_GET[$key]) ? sanitize_text_field($_GET[$key]) : $default;
    }

    /**
     * Check if user is logged in
     *
     * @return bool
     * @since 1.0.0
     */
    protected function is_user_logged_in() {
        return is_user_logged_in();
    }

    /**
     * Check if user has capability
     *
     * @param string $capability Capability to check
     * @return bool
     * @since 1.0.0
     */
    protected function user_can($capability) {
        return current_user_can($capability);
    }

    /**
     * Log AJAX action for debugging
     *
     * @param string $action Action name
     * @param array $data Action data
     * @since 1.0.0
     */
    protected function log_action($action, $data = []) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            // Debug logging removed for production
        }
    }

    /**
     * Validate required fields
     *
     * @param array $required_fields Required field names
     * @param array $data Data to validate
     * @return bool|WP_Error
     * @since 1.0.0
     */
    protected function validate_required_fields($required_fields, $data) {
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', sprintf(__('Field "%s" is required', 'askro'), $field));
            }
        }
        return true;
    }

    /**
     * Get component instance
     *
     * @param string $component Component name
     * @return mixed|null
     * @since 1.0.0
     */
    protected function get_component($component) {
        return askro()->get_component($component);
    }
} 

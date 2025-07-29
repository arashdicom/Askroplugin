<?php
/**
 * AJAX Handler Interface
 *
 * @package    Askro
 * @subpackage Interfaces
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
 * AJAX Handler Interface
 *
 * Defines the contract for all AJAX handlers
 *
 * @since 1.0.0
 */
interface Askro_Ajax_Handler_Interface {

    /**
     * Initialize the AJAX handler
     *
     * @since 1.0.0
     */
    public function init();

    /**
     * Register AJAX actions
     *
     * @since 1.0.0
     */
    public function register_actions();

    /**
     * Validate nonce for security
     *
     * @param string $action The action name
     * @return bool
     * @since 1.0.0
     */
    public function verify_nonce($action);

    /**
     * Send JSON response
     *
     * @param mixed $data Response data
     * @param int $status_code HTTP status code
     * @since 1.0.0
     */
    public function send_response($data, $status_code = 200);

    /**
     * Send error response
     *
     * @param string $message Error message
     * @param int $status_code HTTP status code
     * @since 1.0.0
     */
    public function send_error($message, $status_code = 400);
} 

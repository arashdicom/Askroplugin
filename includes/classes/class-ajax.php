<?php
/**
 * Main AJAX Handler Class
 *
 * @package    Askro
 * @subpackage Classes
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
 * Main AJAX Handler Class
 *
 * Handles core AJAX functionality and coordinates specialized handlers
 *
 * @since 1.0.0
 */
class Askro_Ajax {

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Initialize AJAX hooks
     *
     * @since 1.0.0
     */
    public function init() {
        // Core functionality that hasn't been moved to specialized classes yet
        add_action('wp_ajax_askro_mark_best_answer', [$this, 'handle_mark_best_answer']);
        add_action('wp_ajax_askro_update_question_status', [$this, 'handle_update_question_status']);
        add_action('wp_ajax_askro_report_question', [$this, 'handle_report_question']);
        add_action('wp_ajax_askro_submit_question', [$this, 'handle_submit_question']);
        add_action('wp_ajax_askro_submit_answer', [$this, 'handle_submit_answer']);
        add_action('wp_ajax_askro_create_test_answer', [$this, 'handle_create_test_answer']);
        add_action('wp_ajax_nopriv_askro_create_test_answer', [$this, 'handle_create_test_answer']);
        add_action('wp_ajax_askro_fix_answer_links', [$this, 'handle_fix_answer_links']);
        add_action('wp_ajax_nopriv_askro_fix_answer_links', [$this, 'handle_fix_answer_links']);
        
        // Test handlers
        add_action('wp_ajax_askro_test_endpoint', [$this, 'handle_test_endpoint']);
        add_action('wp_ajax_nopriv_askro_test_endpoint', [$this, 'handle_test_endpoint']);
        add_action('wp_ajax_askro_test_connection', [$this, 'handle_test_connection']);
        add_action('wp_ajax_askro_test_db_write', [$this, 'handle_test_db_write']);
    }

    /**
     * Handle marking best answer
     *
     * @since 1.0.0
     */
    public function handle_mark_best_answer() {
        if (!wp_verify_nonce($_POST['nonce'], 'askro_mark_best_answer')) {
            wp_send_json_error(['message' => __('Security check failed', 'askro')]);
            return;
        }

        $answer_id = intval($_POST['answer_id']);
        $question_id = intval($_POST['question_id']);

        if (!$answer_id || !$question_id) {
            wp_send_json_error(['message' => __('Invalid data', 'askro')]);
            return;
        }

        // Verify user owns the question
        $question = get_post($question_id);
        if (!$question || $question->post_author != get_current_user_id()) {
            wp_send_json_error(['message' => __('You can only mark best answer for your own questions', 'askro')]);
            return;
        }

        // Update question meta
        update_post_meta($question_id, '_askro_best_answer', $answer_id);
        update_post_meta($question_id, '_askro_status', 'solved');

        // Award points to answer author
        $answer = get_post($answer_id);
        if ($answer) {
            $gamification = askro()->get_component('gamification');
            if ($gamification) {
                $gamification->award_points($answer->post_author, 50, 'best_answer');
            }
        }

        wp_send_json_success([
            'message' => __('Best answer marked successfully', 'askro'),
            'answer_id' => $answer_id
        ]);
    }

    /**
     * Handle updating question status
     *
     * @since 1.0.0
     */
    public function handle_update_question_status() {
        if (!wp_verify_nonce($_POST['nonce'], 'askro_update_question_status')) {
            wp_send_json_error(['message' => __('Security check failed', 'askro')]);
            return;
        }

        $question_id = intval($_POST['question_id']);
        $status = sanitize_text_field($_POST['status']);

        if (!$question_id || !$status) {
            wp_send_json_error(['message' => __('Invalid data', 'askro')]);
            return;
        }

        // Verify user owns the question
        $question = get_post($question_id);
        if (!$question || $question->post_author != get_current_user_id()) {
            wp_send_json_error(['message' => __('You can only update your own questions', 'askro')]);
            return;
        }

        update_post_meta($question_id, '_askro_status', $status);

        wp_send_json_success([
            'message' => __('Question status updated successfully', 'askro'),
            'status' => $status
        ]);
    }

    /**
     * Handle reporting a question
     *
     * @since 1.0.0
     */
    public function handle_report_question() {
        if (!wp_verify_nonce($_POST['nonce'], 'askro_report_question')) {
            wp_send_json_error(['message' => __('Security check failed', 'askro')]);
            return;
        }

        $question_id = intval($_POST['question_id']);
        $reason = sanitize_textarea_field($_POST['reason']);

        if (!$question_id || !$reason) {
            wp_send_json_error(['message' => __('Invalid data', 'askro')]);
            return;
        }

        // Create report
        $report_data = [
            'question_id' => $question_id,
            'reporter_id' => get_current_user_id(),
            'reason' => $reason,
            'date_reported' => current_time('mysql')
        ];

        // Store report (you might want to create a reports table)
        $reports = get_option('askro_reports', []);
        $reports[] = $report_data;
        update_option('askro_reports', $reports);

        wp_send_json_success([
            'message' => __('Question reported successfully', 'askro')
        ]);
    }

    /**
     * Handle submitting a question
     *
     * @since 1.0.0
     */
    public function handle_submit_question() {
        if (!wp_verify_nonce($_POST['nonce'], 'askro_question_nonce')) {
            wp_send_json_error(['message' => __('Security check failed', 'askro')]);
            return;
        }

        $title = sanitize_text_field($_POST['question_title']);
        $content = wp_kses_post($_POST['question_content']);
        $category = sanitize_text_field($_POST['question_category']);
        $tags = sanitize_text_field($_POST['question_tags']);

        if (!$title || !$content) {
            wp_send_json_error(['message' => __('Title and content are required', 'askro')]);
            return;
        }

        $post_data = [
            'post_title' => $title,
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'askro_question',
            'post_author' => get_current_user_id()
        ];

        $question_id = wp_insert_post($post_data);

        if (is_wp_error($question_id)) {
            wp_send_json_error(['message' => $question_id->get_error_message()]);
            return;
        }

        // Set category and tags
        if ($category) {
            wp_set_object_terms($question_id, $category, 'askro_question_category');
        }

        if ($tags) {
            $tags_array = array_map('trim', explode(',', $tags));
            wp_set_object_terms($question_id, $tags_array, 'askro_question_tag');
        }

        // Award points for asking question
        $gamification = askro()->get_component('gamification');
        if ($gamification) {
            $gamification->award_points(get_current_user_id(), 10, 'ask_question');
        }

        wp_send_json_success([
            'message' => __('Question submitted successfully', 'askro'),
            'question_id' => $question_id,
            'permalink' => get_permalink($question_id)
        ]);
    }

    /**
     * Handle submitting an answer
     *
     * @since 1.0.0
     */
    public function handle_submit_answer() {
        if (!wp_verify_nonce($_POST['nonce'], 'askro_submit_answer')) {
            wp_send_json_error(['message' => __('Security check failed', 'askro')]);
            return;
        }

        $question_id = intval($_POST['question_id']);
        $content = wp_kses_post($_POST['content']);

        if (!$question_id || !$content) {
            wp_send_json_error(['message' => __('Question ID and content are required', 'askro')]);
            return;
        }

        $post_data = [
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'askro_answer',
            'post_parent' => $question_id,
            'post_author' => get_current_user_id()
        ];

        $answer_id = wp_insert_post($post_data);

        if (is_wp_error($answer_id)) {
            wp_send_json_error(['message' => $answer_id->get_error_message()]);
            return;
        }

        // Award points for answering
        $gamification = askro()->get_component('gamification');
        if ($gamification) {
            $gamification->award_points(get_current_user_id(), 20, 'submit_answer');
        }

        wp_send_json_success([
            'message' => __('Answer submitted successfully', 'askro'),
            'answer_id' => $answer_id
        ]);
    }

    /**
     * Handle creating test answer
     *
     * @since 1.0.0
     */
    public function handle_create_test_answer() {
        $question_id = intval($_POST['question_id']);
        $content = sanitize_textarea_field($_POST['content']);

        if (!$question_id || !$content) {
            wp_send_json_error(['message' => __('Question ID and content are required', 'askro')]);
            return;
        }

        $post_data = [
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'askro_answer',
            'post_parent' => $question_id,
            'post_author' => get_current_user_id()
        ];

        $answer_id = wp_insert_post($post_data);

        if (is_wp_error($answer_id)) {
            wp_send_json_error(['message' => $answer_id->get_error_message()]);
            return;
        }

        wp_send_json_success([
            'message' => __('Test answer created successfully', 'askro'),
            'answer_id' => $answer_id
        ]);
    }

    /**
     * Handle fixing answer links
     *
     * @since 1.0.0
     */
    public function handle_fix_answer_links() {
        global $wpdb;

        // Get all answers
        $answers = get_posts([
            'post_type' => 'askro_answer',
            'numberposts' => -1,
            'post_status' => 'publish'
        ]);

        $fixed_count = 0;

        foreach ($answers as $answer) {
            $content = $answer->post_content;
            $updated_content = $content;

            // Fix any broken links or formatting issues
            // This is a placeholder for actual link fixing logic
            $updated_content = wp_kses_post($content);

            if ($content !== $updated_content) {
                wp_update_post([
                    'ID' => $answer->ID,
                    'post_content' => $updated_content
                ]);
                $fixed_count++;
            }
        }

        wp_send_json_success([
            'message' => sprintf(__('Fixed %d answer links', 'askro'), $fixed_count),
            'fixed_count' => $fixed_count
        ]);
    }

    /**
     * Handle test endpoint
     *
     * @since 1.0.0
     */
    public function handle_test_endpoint() {
        wp_send_json_success([
            'message' => 'Test endpoint working',
            'timestamp' => current_time('mysql')
        ]);
    }

    /**
     * Handle test connection
     *
     * @since 1.0.0
     */
    public function handle_test_connection() {
        wp_send_json_success([
            'message' => 'Connection test successful',
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'plugin_version' => ASKRO_VERSION
        ]);
    }

    /**
     * Handle test database write
     *
     * @since 1.0.0
     */
    public function handle_test_db_write() {
        global $wpdb;

        $test_data = [
            'test_key' => 'test_value',
            'timestamp' => current_time('mysql')
        ];

        $result = update_option('askro_test_data', $test_data);

        if ($result) {
            wp_send_json_success([
                'message' => 'Database write test successful',
                'data' => $test_data
            ]);
        } else {
            wp_send_json_error([
                'message' => 'Database write test failed'
            ]);
        }
    }
}


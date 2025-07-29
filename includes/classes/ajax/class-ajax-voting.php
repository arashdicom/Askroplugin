<?php
/**
 * Voting AJAX Handler Class
 *
 * @package    Askro
 * @subpackage AJAX/Voting
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
 * Voting AJAX Handler Class
 *
 * Handles all voting-related AJAX requests
 *
 * @since 1.0.0
 */
class Askro_Ajax_Voting extends Askro_Abstract_Ajax_Handler {

    /**
     * Register AJAX actions
     *
     * @since 1.0.0
     */
    public function register_actions() {
        // Voting actions
        add_action('wp_ajax_askro_vote', [$this, 'handle_vote']);
        add_action('wp_ajax_nopriv_askro_vote', [$this, 'handle_vote_guest']);
        add_action('wp_ajax_askro_cast_vote', [$this, 'handle_cast_vote']);
        add_action('wp_ajax_nopriv_askro_cast_vote', [$this, 'handle_cast_vote']);
    }

    /**
     * Handle vote for logged-in users
     *
     * @since 1.0.0
     */
    public function handle_vote() {
        if (!$this->verify_nonce('askro_vote')) {
            $this->send_error(__('Security check failed', 'askro'));
            return;
        }

        $post_id = intval($this->get_post_data('post_id'));
        $vote_type = $this->get_post_data('vote_type');
        $vote_value = intval($this->get_post_data('vote_value', 1));

        if (!$post_id || !$vote_type) {
            $this->send_error(__('Invalid vote data', 'askro'));
            return;
        }

        $voting = $this->get_component('voting');
        if (!$voting) {
            $this->send_error(__('Voting component not available', 'askro'));
            return;
        }

        $result = $voting->cast_vote($post_id, $this->user_id, $vote_type, $vote_value);
        
        if (is_wp_error($result)) {
            $this->send_error($result->get_error_message());
            return;
        }

        $this->log_action('vote', [
            'post_id' => $post_id,
            'vote_type' => $vote_type,
            'vote_value' => $vote_value
        ]);

        $this->send_response([
            'success' => true,
            'message' => __('Vote recorded successfully', 'askro'),
            'new_score' => $this->calculate_total_score($post_id)
        ]);
    }

    /**
     * Handle vote for guest users
     *
     * @since 1.0.0
     */
    public function handle_vote_guest() {
        $post_id = intval($this->get_post_data('post_id'));
        $vote_type = $this->get_post_data('vote_type');
        $vote_value = intval($this->get_post_data('vote_value', 1));

        if (!$post_id || !$vote_type) {
            $this->send_error(__('Invalid vote data', 'askro'));
            return;
        }

        // For guests, we might want to limit voting or require registration
        $this->send_error(__('Please log in to vote', 'askro'));
    }

    /**
     * Handle multi-dimensional vote casting
     *
     * @since 1.0.0
     */
    public function handle_cast_vote() {
        if (!$this->verify_nonce('askro_cast_vote')) {
            $this->send_error(__('Security check failed', 'askro'));
            return;
        }

        $post_id = intval($this->get_post_data('post_id'));
        $vote_type = $this->get_post_data('vote_type');
        $vote_value = intval($this->get_post_data('vote_value', 1));

        if (!$post_id || !$vote_type) {
            $this->send_error(__('Invalid vote data', 'askro'));
            return;
        }

        $voting = $this->get_component('voting');
        if (!$voting) {
            $this->send_error(__('Voting component not available', 'askro'));
            return;
        }

        $result = $voting->cast_vote($post_id, $this->user_id, $vote_type, $vote_value);
        
        if (is_wp_error($result)) {
            $this->send_error($result->get_error_message());
            return;
        }

        $this->log_action('cast_vote', [
            'post_id' => $post_id,
            'vote_type' => $vote_type,
            'vote_value' => $vote_value
        ]);

        $this->send_response([
            'success' => true,
            'message' => __('Vote cast successfully', 'askro'),
            'new_score' => $this->calculate_total_score($post_id),
            'vote_type' => $vote_type,
            'vote_value' => $vote_value
        ]);
    }

    /**
     * Get vote value based on vote type
     *
     * @param string $vote_type Vote type
     * @return int
     * @since 1.0.0
     */
    private function get_vote_value($vote_type) {
        $vote_values = [
            'useful' => 3,
            'innovative' => 2,
            'well_researched' => 2,
            'incorrect' => -2,
            'redundant' => -1
        ];

        return $vote_values[$vote_type] ?? 1;
    }

    /**
     * Calculate total score for a post
     *
     * @param int $post_id Post ID
     * @return int
     * @since 1.0.0
     */
    private function calculate_total_score($post_id) {
        $voting = $this->get_component('voting');
        if (!$voting) {
            return 0;
        }

        return $voting->get_post_score($post_id);
    }
} 

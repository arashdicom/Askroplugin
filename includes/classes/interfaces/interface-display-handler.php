<?php
/**
 * Display Handler Interface
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
 * Display Handler Interface
 *
 * Defines the contract for all display-related functionality
 *
 * @since 1.0.0
 */
interface Askro_Display_Handler_Interface {

    /**
     * Initialize the display handler
     *
     * @since 1.0.0
     */
    public function init();

    /**
     * Filter question content
     *
     * @param string $content Post content
     * @return string Modified content
     * @since 1.0.0
     */
    public function filter_question_content($content);

    /**
     * Filter answer content
     *
     * @param string $content Post content
     * @return string Modified content
     * @since 1.0.0
     */
    public function filter_answer_content($content);

    /**
     * Get question data
     *
     * @param int $question_id Question ID
     * @return array Question data
     * @since 1.0.0
     */
    public function get_question_data($question_id);

    /**
     * Build question display
     *
     * @param array $question_data Question data
     * @return string HTML output
     * @since 1.0.0
     */
    public function build_question_display($question_data);

    /**
     * Get answers display
     *
     * @param int $question_id Question ID
     * @return string HTML output
     * @since 1.0.0
     */
    public function get_answers_display($question_id);

    /**
     * Render single answer
     *
     * @param array $answer Answer data
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_answer($answer);

    /**
     * Render voting panel
     *
     * @param int $post_id Post ID
     * @param array $vote_data Vote data
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_voting_panel($post_id, $vote_data);

    /**
     * Render author info
     *
     * @param array $author Author data
     * @param string $date Post date
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_author_info($author, $date);

    /**
     * Render attachment
     *
     * @param int $attachment_id Attachment ID
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_attachment($attachment_id);

    /**
     * Get question answers
     *
     * @param int $question_id Question ID
     * @return array Answers data
     * @since 1.0.0
     */
    public function get_question_answers($question_id);

    /**
     * Get author data
     *
     * @param int $user_id User ID
     * @param bool $is_anonymous Is anonymous
     * @return array Author data
     * @since 1.0.0
     */
    public function get_author_data($user_id, $is_anonymous = false);

    /**
     * Get answer count for question
     *
     * @param int $question_id Question ID
     * @return int Answer count
     * @since 1.0.0
     */
    public function get_answer_count($question_id);

    /**
     * Increment view count
     *
     * @param int $question_id Question ID
     * @since 1.0.0
     */
    public function increment_view_count($question_id);

    /**
     * Render question card
     *
     * @param object $question Question object
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_question_card($question);
} 

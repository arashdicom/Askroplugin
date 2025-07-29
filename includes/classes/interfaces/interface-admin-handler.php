<?php
/**
 * Admin Handler Interface
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
 * Admin Handler Interface
 *
 * Defines the contract for all admin-related functionality
 *
 * @since 1.0.0
 */
interface Askro_Admin_Handler_Interface {

    /**
     * Initialize the admin handler
     *
     * @since 1.0.0
     */
    public function init();

    /**
     * Add admin menu
     *
     * @since 1.0.0
     */
    public function add_admin_menu();

    /**
     * Enqueue admin scripts
     *
     * @param string $hook Current admin page hook
     * @since 1.0.0
     */
    public function enqueue_admin_scripts($hook);

    /**
     * Handle admin AJAX requests
     *
     * @since 1.0.0
     */
    public function handle_admin_ajax();

    /**
     * Register settings
     *
     * @since 1.0.0
     */
    public function register_settings();

    /**
     * Display admin notices
     *
     * @since 1.0.0
     */
    public function admin_notices();

    /**
     * Add custom columns to post types
     *
     * @param array $columns Columns array
     * @return array Modified columns array
     * @since 1.0.0
     */
    public function question_columns($columns);

    /**
     * Display custom column content
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     * @since 1.0.0
     */
    public function question_column_content($column, $post_id);

    /**
     * Add meta boxes
     *
     * @since 1.0.0
     */
    public function add_meta_boxes();

    /**
     * Save meta boxes
     *
     * @param int $post_id Post ID
     * @since 1.0.0
     */
    public function save_meta_boxes($post_id);

    /**
     * Dashboard page
     *
     * @since 1.0.0
     */
    public function dashboard_page();

    /**
     * Questions page
     *
     * @since 1.0.0
     */
    public function questions_page();

    /**
     * Answers page
     *
     * @since 1.0.0
     */
    public function answers_page();

    /**
     * Users page
     *
     * @since 1.0.0
     */
    public function users_page();

    /**
     * Voting page
     *
     * @since 1.0.0
     */
    public function voting_page();

    /**
     * Points page
     *
     * @since 1.0.0
     */
    public function points_page();

    /**
     * Analytics page
     *
     * @since 1.0.0
     */
    public function analytics_page();

    /**
     * Settings page
     *
     * @since 1.0.0
     */
    public function settings_page();

    /**
     * Tools page
     *
     * @since 1.0.0
     */
    public function tools_page();

    /**
     * Get dashboard stats
     *
     * @return array Dashboard statistics
     * @since 1.0.0
     */
    public function get_dashboard_stats();

    /**
     * Get questions data
     *
     * @return array Questions data
     * @since 1.0.0
     */
    public function get_questions_data();

    /**
     * Get answers data
     *
     * @return array Answers data
     * @since 1.0.0
     */
    public function get_answers_data();

    /**
     * Get users data
     *
     * @return array Users data
     * @since 1.0.0
     */
    public function get_users_data();

    /**
     * Get voting data
     *
     * @return array Voting data
     * @since 1.0.0
     */
    public function get_voting_data();

    /**
     * Get points data
     *
     * @return array Points data
     * @since 1.0.0
     */
    public function get_points_data();

    /**
     * Get analytics data
     *
     * @return array Analytics data
     * @since 1.0.0
     */
    public function get_analytics_data();
} 

<?php
/**
 * Search AJAX Handler Class
 *
 * @package    Askro
 * @subpackage AJAX/Search
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
 * Search AJAX Handler Class
 *
 * Handles all search-related AJAX requests
 *
 * @since 1.0.0
 */
class Askro_Ajax_Search extends Askro_Abstract_Ajax_Handler {

    /**
     * Register AJAX actions
     *
     * @since 1.0.0
     */
    public function register_actions() {
        // Search actions
        add_action('wp_ajax_askro_search_questions', [$this, 'handle_search_questions']);
        add_action('wp_ajax_nopriv_askro_search_questions', [$this, 'handle_search_questions']);
        add_action('wp_ajax_askro_advanced_search', [$this, 'handle_advanced_search']);
        add_action('wp_ajax_nopriv_askro_advanced_search', [$this, 'handle_advanced_search']);
        add_action('wp_ajax_askro_get_search_suggestions', [$this, 'handle_get_search_suggestions']);
        add_action('wp_ajax_nopriv_askro_get_search_suggestions', [$this, 'handle_get_search_suggestions']);
        add_action('wp_ajax_askro_apply_advanced_filters', [$this, 'handle_apply_advanced_filters']);
        add_action('wp_ajax_nopriv_askro_apply_advanced_filters', [$this, 'handle_apply_advanced_filters']);
    }

    /**
     * Handle basic search questions
     *
     * @since 1.0.0
     */
    public function handle_search_questions() {
        $search_term = sanitize_text_field($this->get_post_data('search_term'));
        $page = intval($this->get_post_data('page', 1));
        $per_page = intval($this->get_post_data('per_page', 10));

        if (empty($search_term)) {
            $this->send_error(__('Search term is required', 'askro'));
            return;
        }

        $args = [
            'post_type' => 'askro_question',
            'post_status' => 'publish',
            's' => $search_term,
            'posts_per_page' => $per_page,
            'paged' => $page,
            'orderby' => 'relevance'
        ];

        $query = new WP_Query($args);
        $results = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post = get_post();
                
                $results[] = [
                    'id' => $post->ID,
                    'title' => get_the_title(),
                    'excerpt' => wp_trim_words(get_the_excerpt(), 20),
                    'permalink' => get_permalink(),
                    'author' => get_the_author(),
                    'date' => get_the_date(),
                    'votes' => get_post_meta($post->ID, '_askro_votes', true) ?: 0,
                    'answers_count' => get_comments_number($post->ID),
                    'status' => get_post_meta($post->ID, '_askro_status', true) ?: 'open'
                ];
            }
        }

        wp_reset_postdata();

        $this->send_response([
            'success' => true,
            'results' => $results,
            'total_found' => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'current_page' => $page
        ]);
    }

    /**
     * Handle advanced search
     *
     * @since 1.0.0
     */
    public function handle_advanced_search() {
        $search_data = [
            'keyword' => sanitize_text_field($this->get_post_data('keyword')),
            'category' => sanitize_text_field($this->get_post_data('category')),
            'tags' => sanitize_text_field($this->get_post_data('tags')),
            'status' => sanitize_text_field($this->get_post_data('status')),
            'date_from' => sanitize_text_field($this->get_post_data('date_from')),
            'date_to' => sanitize_text_field($this->get_post_data('date_to')),
            'author' => sanitize_text_field($this->get_post_data('author')),
            'sort_by' => sanitize_text_field($this->get_post_data('sort_by', 'date')),
            'sort_order' => sanitize_text_field($this->get_post_data('sort_order', 'DESC')),
            'page' => intval($this->get_post_data('page', 1)),
            'per_page' => intval($this->get_post_data('per_page', 10))
        ];

        $args = [
            'post_type' => 'askro_question',
            'post_status' => 'publish',
            'posts_per_page' => $search_data['per_page'],
            'paged' => $search_data['page'],
            'orderby' => $this->get_orderby_value($search_data['sort_by']),
            'order' => $search_data['sort_order']
        ];

        // Add keyword search
        if (!empty($search_data['keyword'])) {
            $args['s'] = $search_data['keyword'];
        }

        // Add category filter
        if (!empty($search_data['category'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'askro_question_category',
                'field' => 'slug',
                'terms' => $search_data['category']
            ];
        }

        // Add tags filter
        if (!empty($search_data['tags'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'askro_question_tag',
                'field' => 'slug',
                'terms' => explode(',', $search_data['tags'])
            ];
        }

        // Add date range filter
        if (!empty($search_data['date_from']) || !empty($search_data['date_to'])) {
            $date_query = [];
            
            if (!empty($search_data['date_from'])) {
                $date_query['after'] = $search_data['date_from'];
            }
            
            if (!empty($search_data['date_to'])) {
                $date_query['before'] = $search_data['date_to'];
            }
            
            if (!empty($date_query)) {
                $args['date_query'] = $date_query;
            }
        }

        // Add author filter
        if (!empty($search_data['author'])) {
            $user = get_user_by('login', $search_data['author']);
            if ($user) {
                $args['author'] = $user->ID;
            }
        }

        // Add status filter
        if (!empty($search_data['status'])) {
            $args['meta_query'][] = [
                'key' => '_askro_status',
                'value' => $search_data['status'],
                'compare' => '='
            ];
        }

        $query = new WP_Query($args);
        $results = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post = get_post();
                
                $results[] = [
                    'id' => $post->ID,
                    'title' => get_the_title(),
                    'excerpt' => wp_trim_words(get_the_excerpt(), 20),
                    'permalink' => get_permalink(),
                    'author' => get_the_author(),
                    'date' => get_the_date(),
                    'votes' => get_post_meta($post->ID, '_askro_votes', true) ?: 0,
                    'answers_count' => get_comments_number($post->ID),
                    'status' => get_post_meta($post->ID, '_askro_status', true) ?: 'open',
                    'categories' => wp_get_post_terms($post->ID, 'askro_question_category', ['fields' => 'names']),
                    'tags' => wp_get_post_terms($post->ID, 'askro_question_tag', ['fields' => 'names'])
                ];
            }
        }

        wp_reset_postdata();

        $this->send_response([
            'success' => true,
            'results' => $results,
            'total_found' => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'current_page' => $search_data['page'],
            'search_params' => $search_data
        ]);
    }

    /**
     * Handle search suggestions
     *
     * @since 1.0.0
     */
    public function handle_get_search_suggestions() {
        $keyword = sanitize_text_field($this->get_post_data('keyword'));
        $type = sanitize_text_field($this->get_post_data('type', 'questions'));

        if (empty($keyword)) {
            $this->send_response(['suggestions' => []]);
            return;
        }

        $suggestions = [];

        switch ($type) {
            case 'questions':
                $suggestions = $this->get_question_suggestions($keyword);
                break;
            case 'categories':
                $suggestions = $this->get_category_suggestions($keyword);
                break;
            case 'tags':
                $suggestions = $this->get_tag_suggestions($keyword);
                break;
            case 'users':
                $suggestions = $this->get_user_suggestions($keyword);
                break;
        }

        $this->send_response([
            'success' => true,
            'suggestions' => $suggestions
        ]);
    }

    /**
     * Handle advanced filters
     *
     * @since 1.0.0
     */
    public function handle_apply_advanced_filters() {
        $filters = [
            'status' => sanitize_text_field($this->get_post_data('status')),
            'category' => sanitize_text_field($this->get_post_data('category')),
            'tags' => sanitize_text_field($this->get_post_data('tags')),
            'date_range' => sanitize_text_field($this->get_post_data('date_range')),
            'vote_range' => sanitize_text_field($this->get_post_data('vote_range')),
            'answer_range' => sanitize_text_field($this->get_post_data('answer_range')),
            'sort_by' => sanitize_text_field($this->get_post_data('sort_by', 'date')),
            'sort_order' => sanitize_text_field($this->get_post_data('sort_order', 'DESC')),
            'page' => intval($this->get_post_data('page', 1)),
            'per_page' => intval($this->get_post_data('per_page', 10))
        ];

        $args = [
            'post_type' => 'askro_question',
            'post_status' => 'publish',
            'posts_per_page' => $filters['per_page'],
            'paged' => $filters['page'],
            'orderby' => $this->get_orderby_value($filters['sort_by']),
            'order' => $filters['sort_order']
        ];

        // Apply filters
        $this->apply_filters_to_query($args, $filters);

        $query = new WP_Query($args);
        $results = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post = get_post();
                
                $results[] = [
                    'id' => $post->ID,
                    'title' => get_the_title(),
                    'excerpt' => wp_trim_words(get_the_excerpt(), 20),
                    'permalink' => get_permalink(),
                    'author' => get_the_author(),
                    'date' => get_the_date(),
                    'votes' => get_post_meta($post->ID, '_askro_votes', true) ?: 0,
                    'answers_count' => get_comments_number($post->ID),
                    'status' => get_post_meta($post->ID, '_askro_status', true) ?: 'open'
                ];
            }
        }

        wp_reset_postdata();

        $this->send_response([
            'success' => true,
            'results' => $results,
            'total_found' => $query->found_posts,
            'total_pages' => $query->max_num_pages,
            'current_page' => $filters['page'],
            'applied_filters' => $filters
        ]);
    }

    /**
     * Get question suggestions
     *
     * @param string $keyword Search keyword
     * @return array
     * @since 1.0.0
     */
    private function get_question_suggestions($keyword) {
        $args = [
            'post_type' => 'askro_question',
            'post_status' => 'publish',
            's' => $keyword,
            'posts_per_page' => 5,
            'orderby' => 'relevance'
        ];

        $query = new WP_Query($args);
        $suggestions = [];

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $suggestions[] = [
                    'id' => get_the_ID(),
                    'title' => get_the_title(),
                    'permalink' => get_permalink()
                ];
            }
        }

        wp_reset_postdata();
        return $suggestions;
    }

    /**
     * Get category suggestions
     *
     * @param string $keyword Search keyword
     * @return array
     * @since 1.0.0
     */
    private function get_category_suggestions($keyword) {
        $terms = get_terms([
            'taxonomy' => 'askro_question_category',
            'name__like' => $keyword,
            'number' => 5,
            'hide_empty' => true
        ]);

        $suggestions = [];
        foreach ($terms as $term) {
            $suggestions[] = [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'count' => $term->count
            ];
        }

        return $suggestions;
    }

    /**
     * Get tag suggestions
     *
     * @param string $keyword Search keyword
     * @return array
     * @since 1.0.0
     */
    private function get_tag_suggestions($keyword) {
        $terms = get_terms([
            'taxonomy' => 'askro_question_tag',
            'name__like' => $keyword,
            'number' => 5,
            'hide_empty' => true
        ]);

        $suggestions = [];
        foreach ($terms as $term) {
            $suggestions[] = [
                'id' => $term->term_id,
                'name' => $term->name,
                'slug' => $term->slug,
                'count' => $term->count
            ];
        }

        return $suggestions;
    }

    /**
     * Get user suggestions
     *
     * @param string $keyword Search keyword
     * @return array
     * @since 1.0.0
     */
    private function get_user_suggestions($keyword) {
        $users = get_users([
            'search' => "*{$keyword}*",
            'search_columns' => ['user_login', 'user_nicename', 'display_name'],
            'number' => 5
        ]);

        $suggestions = [];
        foreach ($users as $user) {
            $suggestions[] = [
                'id' => $user->ID,
                'name' => $user->display_name,
                'username' => $user->user_login,
                'avatar' => get_avatar_url($user->ID, 32)
            ];
        }

        return $suggestions;
    }

    /**
     * Get orderby value for WP_Query
     *
     * @param string $sort_by Sort by parameter
     * @return string
     * @since 1.0.0
     */
    private function get_orderby_value($sort_by) {
        $orderby_map = [
            'date' => 'date',
            'title' => 'title',
            'votes' => 'meta_value_num',
            'answers' => 'comment_count',
            'views' => 'meta_value_num',
            'relevance' => 'relevance'
        ];

        return $orderby_map[$sort_by] ?? 'date';
    }

    /**
     * Apply filters to query arguments
     *
     * @param array $args Query arguments
     * @param array $filters Filters to apply
     * @since 1.0.0
     */
    private function apply_filters_to_query(&$args, $filters) {
        // Status filter
        if (!empty($filters['status'])) {
            $args['meta_query'][] = [
                'key' => '_askro_status',
                'value' => $filters['status'],
                'compare' => '='
            ];
        }

        // Category filter
        if (!empty($filters['category'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'askro_question_category',
                'field' => 'slug',
                'terms' => $filters['category']
            ];
        }

        // Tags filter
        if (!empty($filters['tags'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'askro_question_tag',
                'field' => 'slug',
                'terms' => explode(',', $filters['tags'])
            ];
        }

        // Vote range filter
        if (!empty($filters['vote_range'])) {
            $vote_range = explode('-', $filters['vote_range']);
            if (count($vote_range) === 2) {
                $args['meta_query'][] = [
                    'key' => '_askro_votes',
                    'value' => [$vote_range[0], $vote_range[1]],
                    'type' => 'NUMERIC',
                    'compare' => 'BETWEEN'
                ];
            }
        }

        // Answer range filter
        if (!empty($filters['answer_range'])) {
            $answer_range = explode('-', $filters['answer_range']);
            if (count($answer_range) === 2) {
                $args['comment_count_query'] = [
                    'value' => [$answer_range[0], $answer_range[1]],
                    'compare' => 'BETWEEN'
                ];
            }
        }
    }
} 

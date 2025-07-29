<?php
/**
 * API Cache Handler Class
 *
 * @package    Askro
 * @subpackage Core/API/Cache
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
 * Askro API Cache Class
 *
 * Handles caching for API responses and database queries
 *
 * @since 1.0.0
 */
class Askro_API_Cache {

    /**
     * Cache group prefix
     *
     * @var string
     * @since 1.0.0
     */
    private $cache_group = 'askro_api';

    /**
     * Default cache expiration
     *
     * @var int
     * @since 1.0.0
     */
    private $default_expiration = 3600; // 1 hour

    /**
     * Cache keys mapping
     *
     * @var array
     * @since 1.0.0
     */
    private $cache_keys = [
        'questions' => 'questions_%s',
        'question' => 'question_%d',
        'answers' => 'answers_%d',
        'user' => 'user_%d',
        'leaderboard' => 'leaderboard_%s',
        'search' => 'search_%s',
        'analytics' => 'analytics_%s'
    ];

    /**
     * Initialize the cache component
     *
     * @since 1.0.0
     */
    public function init() {
        add_action('save_post', [$this, 'clear_post_cache'], 10, 2);
        add_action('deleted_post', [$this, 'clear_post_cache'], 10, 2);
        add_action('askro_points_updated', [$this, 'clear_user_cache'], 10, 1);
        add_action('askro_vote_cast', [$this, 'clear_post_cache'], 10, 2);
    }

    /**
     * Get cached data
     *
     * @param string $key Cache key
     * @param mixed $default Default value if cache miss
     * @return mixed
     * @since 1.0.0
     */
    public function get($key, $default = false) {
        $cached = wp_cache_get($key, $this->cache_group);
        
        if ($cached === false) {
            return $default;
        }

        return $cached;
    }

    /**
     * Set cached data
     *
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $expiration Expiration time in seconds
     * @return bool
     * @since 1.0.0
     */
    public function set($key, $data, $expiration = null) {
        if ($expiration === null) {
            $expiration = $this->default_expiration;
        }

        return wp_cache_set($key, $data, $this->cache_group, $expiration);
    }

    /**
     * Delete cached data
     *
     * @param string $key Cache key
     * @return bool
     * @since 1.0.0
     */
    public function delete($key) {
        return wp_cache_delete($key, $this->cache_group);
    }

    /**
     * Clear all cache
     *
     * @return bool
     * @since 1.0.0
     */
    public function clear_all() {
        return wp_cache_flush_group($this->cache_group);
    }

    /**
     * Get questions cache key
     *
     * @param array $args Query arguments
     * @return string
     * @since 1.0.0
     */
    public function get_questions_cache_key($args) {
        $key_parts = [
            'questions',
            'page_' . ($args['paged'] ?? 1),
            'per_page_' . ($args['posts_per_page'] ?? 15),
            'orderby_' . ($args['orderby'] ?? 'date'),
            'order_' . ($args['order'] ?? 'DESC')
        ];

        if (!empty($args['tax_query'])) {
            $key_parts[] = 'tax_' . md5(serialize($args['tax_query']));
        }

        if (!empty($args['meta_query'])) {
            $key_parts[] = 'meta_' . md5(serialize($args['meta_query']));
        }

        return implode('_', $key_parts);
    }

    /**
     * Get question cache key
     *
     * @param int $question_id
     * @return string
     * @since 1.0.0
     */
    public function get_question_cache_key($question_id) {
        return sprintf($this->cache_keys['question'], $question_id);
    }

    /**
     * Get answers cache key
     *
     * @param int $question_id
     * @param array $args
     * @return string
     * @since 1.0.0
     */
    public function get_answers_cache_key($question_id, $args = []) {
        $key_parts = [
            sprintf($this->cache_keys['answers'], $question_id),
            'page_' . ($args['paged'] ?? 1),
            'per_page_' . ($args['posts_per_page'] ?? 20)
        ];

        return implode('_', $key_parts);
    }

    /**
     * Get user cache key
     *
     * @param int $user_id
     * @return string
     * @since 1.0.0
     */
    public function get_user_cache_key($user_id) {
        return sprintf($this->cache_keys['user'], $user_id);
    }

    /**
     * Get leaderboard cache key
     *
     * @param string $timeframe
     * @param int $limit
     * @return string
     * @since 1.0.0
     */
    public function get_leaderboard_cache_key($timeframe, $limit) {
        return sprintf($this->cache_keys['leaderboard'], $timeframe . '_' . $limit);
    }

    /**
     * Get search cache key
     *
     * @param string $query
     * @param array $args
     * @return string
     * @since 1.0.0
     */
    public function get_search_cache_key($query, $args = []) {
        $key_parts = [
            'search',
            md5($query),
            'type_' . ($args['type'] ?? 'questions'),
            'page_' . ($args['paged'] ?? 1)
        ];

        if (!empty($args['category'])) {
            $key_parts[] = 'cat_' . $args['category'];
        }

        if (!empty($args['tag'])) {
            $key_parts[] = 'tag_' . $args['tag'];
        }

        return implode('_', $key_parts);
    }

    /**
     * Cache questions query
     *
     * @param array $args Query arguments
     * @param array $data Data to cache
     * @param int $expiration Expiration time
     * @return bool
     * @since 1.0.0
     */
    public function cache_questions($args, $data, $expiration = null) {
        $key = $this->get_questions_cache_key($args);
        return $this->set($key, $data, $expiration);
    }

    /**
     * Get cached questions
     *
     * @param array $args Query arguments
     * @return mixed
     * @since 1.0.0
     */
    public function get_cached_questions($args) {
        $key = $this->get_questions_cache_key($args);
        return $this->get($key);
    }

    /**
     * Cache question data
     *
     * @param int $question_id
     * @param array $data
     * @param int $expiration
     * @return bool
     * @since 1.0.0
     */
    public function cache_question($question_id, $data, $expiration = null) {
        $key = $this->get_question_cache_key($question_id);
        return $this->set($key, $data, $expiration);
    }

    /**
     * Get cached question
     *
     * @param int $question_id
     * @return mixed
     * @since 1.0.0
     */
    public function get_cached_question($question_id) {
        $key = $this->get_question_cache_key($question_id);
        return $this->get($key);
    }

    /**
     * Cache answers
     *
     * @param int $question_id
     * @param array $args
     * @param array $data
     * @param int $expiration
     * @return bool
     * @since 1.0.0
     */
    public function cache_answers($question_id, $args, $data, $expiration = null) {
        $key = $this->get_answers_cache_key($question_id, $args);
        return $this->set($key, $data, $expiration);
    }

    /**
     * Get cached answers
     *
     * @param int $question_id
     * @param array $args
     * @return mixed
     * @since 1.0.0
     */
    public function get_cached_answers($question_id, $args) {
        $key = $this->get_answers_cache_key($question_id, $args);
        return $this->get($key);
    }

    /**
     * Cache user data
     *
     * @param int $user_id
     * @param array $data
     * @param int $expiration
     * @return bool
     * @since 1.0.0
     */
    public function cache_user($user_id, $data, $expiration = null) {
        $key = $this->get_user_cache_key($user_id);
        return $this->set($key, $data, $expiration);
    }

    /**
     * Get cached user
     *
     * @param int $user_id
     * @return mixed
     * @since 1.0.0
     */
    public function get_cached_user($user_id) {
        $key = $this->get_user_cache_key($user_id);
        return $this->get($key);
    }

    /**
     * Cache leaderboard
     *
     * @param string $timeframe
     * @param int $limit
     * @param array $data
     * @param int $expiration
     * @return bool
     * @since 1.0.0
     */
    public function cache_leaderboard($timeframe, $limit, $data, $expiration = null) {
        $key = $this->get_leaderboard_cache_key($timeframe, $limit);
        return $this->set($key, $data, $expiration);
    }

    /**
     * Get cached leaderboard
     *
     * @param string $timeframe
     * @param int $limit
     * @return mixed
     * @since 1.0.0
     */
    public function get_cached_leaderboard($timeframe, $limit) {
        $key = $this->get_leaderboard_cache_key($timeframe, $limit);
        return $this->get($key);
    }

    /**
     * Cache search results
     *
     * @param string $query
     * @param array $args
     * @param array $data
     * @param int $expiration
     * @return bool
     * @since 1.0.0
     */
    public function cache_search($query, $args, $data, $expiration = null) {
        $key = $this->get_search_cache_key($query, $args);
        return $this->set($key, $data, $expiration);
    }

    /**
     * Get cached search results
     *
     * @param string $query
     * @param array $args
     * @return mixed
     * @since 1.0.0
     */
    public function get_cached_search($query, $args) {
        $key = $this->get_search_cache_key($query, $args);
        return $this->get($key);
    }

    /**
     * Clear post cache
     *
     * @param int $post_id
     * @param WP_Post $post
     * @since 1.0.0
     */
    public function clear_post_cache($post_id, $post = null) {
        if (!$post) {
            $post = get_post($post_id);
        }

        if (!$post) {
            return;
        }

        // Clear question cache
        if ($post->post_type === 'askro_question') {
            $this->delete($this->get_question_cache_key($post_id));
            
            // Clear questions list cache
            $this->clear_questions_cache();
        }

        // Clear answer cache
        if ($post->post_type === 'askro_answer') {
            $question_id = get_post_meta($post_id, 'askro_question_id', true);
            if ($question_id) {
                $this->clear_answers_cache($question_id);
                $this->delete($this->get_question_cache_key($question_id));
            }
        }

        // Clear user cache
        $this->delete($this->get_user_cache_key($post->post_author));
    }

    /**
     * Clear user cache
     *
     * @param int $user_id
     * @since 1.0.0
     */
    public function clear_user_cache($user_id) {
        $this->delete($this->get_user_cache_key($user_id));
        $this->clear_leaderboard_cache();
    }

    /**
     * Clear questions cache
     *
     * @since 1.0.0
     */
    public function clear_questions_cache() {
        // Clear all questions-related cache
        $this->delete_pattern('questions_*');
    }

    /**
     * Clear answers cache
     *
     * @param int $question_id
     * @since 1.0.0
     */
    public function clear_answers_cache($question_id) {
        $this->delete_pattern('answers_' . $question_id . '_*');
    }

    /**
     * Clear leaderboard cache
     *
     * @since 1.0.0
     */
    public function clear_leaderboard_cache() {
        $this->delete_pattern('leaderboard_*');
    }

    /**
     * Clear search cache
     *
     * @since 1.0.0
     */
    public function clear_search_cache() {
        $this->delete_pattern('search_*');
    }

    /**
     * Delete cache by pattern
     *
     * @param string $pattern
     * @since 1.0.0
     */
    private function delete_pattern($pattern) {
        // This is a simplified implementation
        // In a real implementation, you would need to iterate through cache keys
        // For now, we'll just clear the entire cache group
        $this->clear_all();
    }

    /**
     * Get cache statistics
     *
     * @return array
     * @since 1.0.0
     */
    public function get_cache_stats() {
        global $wp_object_cache;

        if (method_exists($wp_object_cache, 'getStats')) {
            return $wp_object_cache->getStats();
        }

        return [
            'hits' => 0,
            'misses' => 0,
            'memory_usage' => 0
        ];
    }

    /**
     * Warm up cache
     *
     * @since 1.0.0
     */
    public function warm_up_cache() {
        // Cache popular questions
        $popular_questions = get_posts([
            'post_type' => 'askro_question',
            'post_status' => 'publish',
            'posts_per_page' => 20,
            'meta_key' => 'askro_views',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
        ]);

        foreach ($popular_questions as $question) {
            $this->cache_question($question->ID, $this->format_question($question));
        }

        // Cache leaderboard
        $this->cache_leaderboard('all_time', 10, $this->get_leaderboard_data('all_time', 10));
        $this->cache_leaderboard('weekly', 10, $this->get_leaderboard_data('weekly', 10));
    }

    /**
     * Format question for caching
     *
     * @param WP_Post $question
     * @return array
     * @since 1.0.0
     */
    private function format_question($question) {
        // This would be the same as in the API class
        return [
            'id' => $question->ID,
            'title' => $question->post_title,
            'content' => $question->post_content,
            'created_at' => $question->post_date,
            'author_id' => $question->post_author
        ];
    }

    /**
     * Get leaderboard data
     *
     * @param string $timeframe
     * @param int $limit
     * @return array
     * @since 1.0.0
     */
    private function get_leaderboard_data($timeframe, $limit) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'askro_points_log';

        $where_clause = '';
        if ($timeframe === 'weekly') {
            $where_clause = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        } elseif ($timeframe === 'monthly') {
            $where_clause = "WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }

        $query = "
            SELECT user_id, SUM(points) as total_points
            FROM {$table_name}
            {$where_clause}
            GROUP BY user_id
            ORDER BY total_points DESC
            LIMIT {$limit}
        ";

        return $wpdb->get_results($query);
    }
} 

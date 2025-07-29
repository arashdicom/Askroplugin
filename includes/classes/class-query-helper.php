<?php
/**
 * Askro Query Helper Class
 * 
 * Provides unified database queries and caching for the Askro plugin
 * 
 * @package Askro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Askro_Query_Helper Class
 * 
 * Handles all database queries and caching
 * 
 * @since 1.0.0
 */
class Askro_Query_Helper {
    
    /**
     * Cache for query results
     */
    private $query_cache = [];
    
    /**
     * Cache expiration time (30 minutes)
     */
    private $cache_expiration = 1800;
    
    /**
     * Get questions with caching
     * 
     * @param array $args Query arguments
     * @param bool $force_refresh Force refresh cache
     * @return array Questions data
     */
    public function get_questions($args = [], $force_refresh = false) {
        $defaults = [
            'post_type' => 'askro_question',
            'post_status' => 'publish',
            'posts_per_page' => 10,
            'paged' => 1,
            'orderby' => 'date',
            'order' => 'DESC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        $cache_key = 'questions_' . md5(serialize($args));
        
        if (!$force_refresh && isset($this->query_cache[$cache_key])) {
            return $this->query_cache[$cache_key];
        }
        
        $query = new WP_Query($args);
        $questions = $query->posts;
        
        // Format questions data
        $formatted_questions = [];
        foreach ($questions as $question) {
            $formatted_questions[] = $this->format_question_data($question);
        }
        
        $result = [
            'questions' => $formatted_questions,
            'total' => $query->found_posts,
            'max_pages' => $query->max_num_pages
        ];
        
        $this->query_cache[$cache_key] = $result;
        return $result;
    }
    
    /**
     * Get answers with caching
     * 
     * @param int $question_id Question ID
     * @param array $args Query arguments
     * @param bool $force_refresh Force refresh cache
     * @return array Answers data
     */
    public function get_answers($question_id, $args = [], $force_refresh = false) {
        $defaults = [
            'post_type' => 'askro_answer',
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_askro_question_id',
                    'value' => $question_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => -1,
            'orderby' => 'meta_value_num',
            'meta_key' => '_askro_vote_score',
            'order' => 'DESC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        $cache_key = "answers_{$question_id}_" . md5(serialize($args));
        
        if (!$force_refresh && isset($this->query_cache[$cache_key])) {
            return $this->query_cache[$cache_key];
        }
        
        $query = new WP_Query($args);
        $answers = $query->posts;
        
        // Format answers data
        $formatted_answers = [];
        foreach ($answers as $answer) {
            $formatted_answers[] = $this->format_answer_data($answer);
        }
        
        $result = [
            'answers' => $formatted_answers,
            'total' => count($formatted_answers)
        ];
        
        $this->query_cache[$cache_key] = $result;
        return $result;
    }
    
    /**
     * Get user statistics
     * 
     * @param int $user_id User ID
     * @param bool $force_refresh Force refresh cache
     * @return array User statistics
     */
    public function get_user_statistics($user_id, $force_refresh = false) {
        global $wpdb;
        
        $cache_key = "user_stats_{$user_id}";
        
        if (!$force_refresh && isset($this->query_cache[$cache_key])) {
            return $this->query_cache[$cache_key];
        }
        
        $stats = [
            'questions_count' => 0,
            'answers_count' => 0,
            'comments_count' => 0,
            'votes_received' => 0,
            'votes_given' => 0,
            'best_answers' => 0,
            'total_points' => 0
        ];
        
        // Get questions count
        $stats['questions_count'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_author = %d AND post_type = 'askro_question' AND post_status = 'publish'",
            $user_id
        ));
        
        // Get answers count
        $stats['answers_count'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_author = %d AND post_type = 'askro_answer' AND post_status = 'publish'",
            $user_id
        ));
        
        // Get comments count
        $stats['comments_count'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_comments 
             WHERE user_id = %d AND status = 'approved'",
            $user_id
        ));
        
        // Get votes received
        $stats['votes_received'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_votes 
             WHERE post_author = %d",
            $user_id
        ));
        
        // Get votes given
        $stats['votes_given'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_votes 
             WHERE user_id = %d",
            $user_id
        ));
        
        // Get best answers count
        $stats['best_answers'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_author = %d AND p.post_type = 'askro_answer' 
             AND p.post_status = 'publish' AND pm.meta_key = '_askro_is_best_answer'
             AND pm.meta_value = '1'",
            $user_id
        ));
        
        // Get total points
        $stats['total_points'] = askro_get_user_points($user_id);
        
        $this->query_cache[$cache_key] = $stats;
        return $stats;
    }
    
    /**
     * Get leaderboard data
     * 
     * @param string $type Leaderboard type (points, questions, answers)
     * @param int $limit Number of users to return
     * @param bool $force_refresh Force refresh cache
     * @return array Leaderboard data
     */
    public function get_leaderboard($type = 'points', $limit = 10, $force_refresh = false) {
        global $wpdb;
        
        $cache_key = "leaderboard_{$type}_{$limit}";
        
        if (!$force_refresh && isset($this->query_cache[$cache_key])) {
            return $this->query_cache[$cache_key];
        }
        
        $leaderboard = [];
        
        switch ($type) {
            case 'points':
                $leaderboard = $this->get_points_leaderboard($limit);
                break;
            case 'questions':
                $leaderboard = $this->get_questions_leaderboard($limit);
                break;
            case 'answers':
                $leaderboard = $this->get_answers_leaderboard($limit);
                break;
            case 'best_answers':
                $leaderboard = $this->get_best_answers_leaderboard($limit);
                break;
        }
        
        $this->query_cache[$cache_key] = $leaderboard;
        return $leaderboard;
    }
    
    /**
     * Get points leaderboard
     * 
     * @param int $limit Number of users
     * @return array Leaderboard data
     */
    private function get_points_leaderboard($limit) {
        global $wpdb;
        
        $sql = "SELECT u.ID, u.user_login, u.display_name, 
                       COALESCE(p.points, 0) as total_points
                FROM {$wpdb->users} u
                LEFT JOIN {$wpdb->prefix}askro_points p ON u.ID = p.user_id
                WHERE p.points > 0
                ORDER BY total_points DESC
                LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }
    
    /**
     * Get questions leaderboard
     * 
     * @param int $limit Number of users
     * @return array Leaderboard data
     */
    private function get_questions_leaderboard($limit) {
        global $wpdb;
        
        $sql = "SELECT u.ID, u.user_login, u.display_name, COUNT(p.ID) as questions_count
                FROM {$wpdb->users} u
                JOIN {$wpdb->posts} p ON u.ID = p.post_author
                WHERE p.post_type = 'askro_question' AND p.post_status = 'publish'
                GROUP BY u.ID
                ORDER BY questions_count DESC
                LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }
    
    /**
     * Get answers leaderboard
     * 
     * @param int $limit Number of users
     * @return array Leaderboard data
     */
    private function get_answers_leaderboard($limit) {
        global $wpdb;
        
        $sql = "SELECT u.ID, u.user_login, u.display_name, COUNT(p.ID) as answers_count
                FROM {$wpdb->users} u
                JOIN {$wpdb->posts} p ON u.ID = p.post_author
                WHERE p.post_type = 'askro_answer' AND p.post_status = 'publish'
                GROUP BY u.ID
                ORDER BY answers_count DESC
                LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }
    
    /**
     * Get best answers leaderboard
     * 
     * @param int $limit Number of users
     * @return array Leaderboard data
     */
    private function get_best_answers_leaderboard($limit) {
        global $wpdb;
        
        $sql = "SELECT u.ID, u.user_login, u.display_name, COUNT(p.ID) as best_answers_count
                FROM {$wpdb->users} u
                JOIN {$wpdb->posts} p ON u.ID = p.post_author
                JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
                WHERE p.post_type = 'askro_answer' AND p.post_status = 'publish'
                AND pm.meta_key = '_askro_is_best_answer' AND pm.meta_value = '1'
                GROUP BY u.ID
                ORDER BY best_answers_count DESC
                LIMIT %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $limit));
    }
    
    /**
     * Format question data
     * 
     * @param WP_Post $question Question post object
     * @return array Formatted question data
     */
    private function format_question_data($question) {
        global $askro_user_helper;
        
        $author = $askro_user_helper->get_user($question->post_author);
        
        return [
            'id' => $question->ID,
            'title' => $question->post_title,
            'content' => $question->post_content,
            'excerpt' => wp_trim_words($question->post_content, 20),
            'author' => [
                'id' => $question->post_author,
                'name' => $author ? $author->display_name : __('مستخدم غير معروف', 'askro'),
                'avatar' => get_avatar($question->post_author, 48),
                'points' => askro_get_user_points($question->post_author)
            ],
            'date' => $question->post_date,
            'date_gmt' => $question->post_date_gmt,
            'modified' => $question->post_modified,
            'status' => $question->post_status,
            'url' => get_permalink($question->ID),
            'votes' => askro_get_post_votes($question->ID),
            'answers_count' => askro_get_answer_count($question->ID),
            'views' => askro_get_post_views($question->ID),
            'categories' => wp_get_post_terms($question->ID, 'askro_category'),
            'tags' => wp_get_post_terms($question->ID, 'askro_tag')
        ];
    }
    
    /**
     * Format answer data
     * 
     * @param WP_Post $answer Answer post object
     * @return array Formatted answer data
     */
    private function format_answer_data($answer) {
        global $askro_user_helper;
        
        $author = $askro_user_helper->get_user($answer->post_author);
        $is_best_answer = get_post_meta($answer->ID, '_askro_is_best_answer', true);
        
        return [
            'id' => $answer->ID,
            'content' => $answer->post_content,
            'author' => [
                'id' => $answer->post_author,
                'name' => $author ? $author->display_name : __('مستخدم غير معروف', 'askro'),
                'avatar' => get_avatar($answer->post_author, 48),
                'points' => askro_get_user_points($answer->post_author)
            ],
            'date' => $answer->post_date,
            'date_gmt' => $answer->post_date_gmt,
            'modified' => $answer->post_modified,
            'status' => $answer->post_status,
            'votes' => askro_get_post_votes($answer->ID),
            'is_best_answer' => $is_best_answer === '1',
            'question_id' => get_post_meta($answer->ID, '_askro_question_id', true)
        ];
    }
    
    /**
     * Clear query cache
     * 
     * @param string $pattern Cache key pattern (optional)
     */
    public function clear_cache($pattern = '') {
        if ($pattern) {
            foreach ($this->query_cache as $key => $value) {
                if (strpos($key, $pattern) !== false) {
                    unset($this->query_cache[$key]);
                }
            }
        } else {
            $this->query_cache = [];
        }
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Cache statistics
     */
    public function get_cache_stats() {
        return [
            'total_entries' => count($this->query_cache),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        ];
    }
}

// Initialize query helper
$askro_query_helper = new Askro_Query_Helper(); 

<?php
/**
 * Askro User Helper Class
 * 
 * Provides unified user data retrieval and management for the Askro plugin
 * 
 * @package Askro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Askro_User_Helper Class
 * 
 * Handles all user-related data retrieval and caching
 * 
 * @since 1.0.0
 */
class Askro_User_Helper {
    
    /**
     * Cache for user data
     */
    private $user_cache = [];
    
    /**
     * Cache expiration time (1 hour)
     */
    private $cache_expiration = 3600;
    
    /**
     * Get user data by ID
     * 
     * @param int $user_id User ID
     * @param bool $force_refresh Force refresh cache
     * @return WP_User|false User object or false
     */
    public function get_user($user_id, $force_refresh = false) {
        if (!$user_id) {
            return false;
        }
        
        $cache_key = "user_{$user_id}";
        
        // Check cache first
        if (!$force_refresh && isset($this->user_cache[$cache_key])) {
            return $this->user_cache[$cache_key];
        }
        
        // Get user data
        $user = get_userdata($user_id);
        
        if ($user) {
            $this->user_cache[$cache_key] = $user;
        }
        
        return $user;
    }
    
    /**
     * Get user data by username
     * 
     * @param string $username Username
     * @param bool $force_refresh Force refresh cache
     * @return WP_User|false User object or false
     */
    public function get_user_by_username($username, $force_refresh = false) {
        if (!$username) {
            return false;
        }
        
        $cache_key = "user_username_{$username}";
        
        // Check cache first
        if (!$force_refresh && isset($this->user_cache[$cache_key])) {
            return $this->user_cache[$cache_key];
        }
        
        // Get user data
        $user = get_user_by('login', $username);
        
        if ($user) {
            $this->user_cache[$cache_key] = $user;
            // Also cache by ID
            $this->user_cache["user_{$user->ID}"] = $user;
        }
        
        return $user;
    }
    
    /**
     * Get user data by email
     * 
     * @param string $email Email address
     * @param bool $force_refresh Force refresh cache
     * @return WP_User|false User object or false
     */
    public function get_user_by_email($email, $force_refresh = false) {
        if (!$email) {
            return false;
        }
        
        $cache_key = "user_email_{$email}";
        
        // Check cache first
        if (!$force_refresh && isset($this->user_cache[$cache_key])) {
            return $this->user_cache[$cache_key];
        }
        
        // Get user data
        $user = get_user_by('email', $email);
        
        if ($user) {
            $this->user_cache[$cache_key] = $user;
            // Also cache by ID
            $this->user_cache["user_{$user->ID}"] = $user;
        }
        
        return $user;
    }
    
    /**
     * Get current user
     * 
     * @param bool $force_refresh Force refresh cache
     * @return WP_User|false Current user object or false
     */
    public function get_current_user($force_refresh = false) {
        $user_id = get_current_user_id();
        
        if (!$user_id) {
            return false;
        }
        
        return $this->get_user($user_id, $force_refresh);
    }
    
    /**
     * Get user display name
     * 
     * @param int $user_id User ID
     * @return string Display name
     */
    public function get_user_display_name($user_id) {
        $user = $this->get_user($user_id);
        
        if (!$user) {
            return __('مستخدم غير معروف', 'askro');
        }
        
        return $user->display_name ?: $user->user_login;
    }
    
    /**
     * Get user avatar
     * 
     * @param int $user_id User ID
     * @param int $size Avatar size
     * @return string Avatar HTML
     */
    public function get_user_avatar($user_id, $size = 96) {
        $user = $this->get_user($user_id);
        
        if (!$user) {
            return get_avatar(0, $size);
        }
        
        return get_avatar($user_id, $size);
    }
    
    /**
     * Get user profile URL
     * 
     * @param int $user_id User ID
     * @return string Profile URL
     */
    public function get_user_profile_url($user_id) {
        $user = $this->get_user($user_id);
        
        if (!$user) {
            return home_url();
        }
        
        return home_url("/askro-user/{$user->user_login}/");
    }
    
    /**
     * Get user points
     * 
     * @param int $user_id User ID
     * @return int User points
     */
    public function get_user_points($user_id) {
        return askro_get_user_points($user_id);
    }
    
    /**
     * Get user rank
     * 
     * @param int $user_id User ID
     * @return string User rank
     */
    public function get_user_rank($user_id) {
        return askro_get_user_rank($user_id);
    }
    
    /**
     * Get user badges
     * 
     * @param int $user_id User ID
     * @return array User badges
     */
    public function get_user_badges($user_id) {
        return askro_get_user_badges($user_id);
    }
    
    /**
     * Get user achievements
     * 
     * @param int $user_id User ID
     * @return array User achievements
     */
    public function get_user_achievements($user_id) {
        return askro_get_user_achievements($user_id);
    }
    
    /**
     * Get user statistics
     * 
     * @param int $user_id User ID
     * @return array User statistics
     */
    public function get_user_stats($user_id) {
        global $wpdb;
        
        $stats = [
            'questions_count' => 0,
            'answers_count' => 0,
            'comments_count' => 0,
            'votes_received' => 0,
            'votes_given' => 0,
            'best_answers' => 0,
            'total_points' => 0,
            'rank' => '',
            'join_date' => '',
            'last_activity' => ''
        ];
        
        $user = $this->get_user($user_id);
        if (!$user) {
            return $stats;
        }
        
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
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_author = %d AND post_type = 'askro_answer' 
             AND post_status = 'publish' 
             AND meta_value = 'best_answer'",
            $user_id
        ));
        
        // Get total points
        $stats['total_points'] = $this->get_user_points($user_id);
        
        // Get rank
        $stats['rank'] = $this->get_user_rank($user_id);
        
        // Get join date
        $stats['join_date'] = $user->user_registered;
        
        // Get last activity (simplified)
        $stats['last_activity'] = $user->user_registered;
        
        return $stats;
    }
    
    /**
     * Check if user can perform action
     * 
     * @param int $user_id User ID
     * @param string $action Action name
     * @return bool Whether user can perform action
     */
    public function can_user_perform_action($user_id, $action) {
        $user = $this->get_user($user_id);
        
        if (!$user) {
            return false;
        }
        
        $capability_map = [
            'ask_question' => 'askro_ask_question',
            'submit_answer' => 'askro_submit_answer',
            'add_comment' => 'askro_add_comment',
            'vote' => 'askro_vote',
            'edit_own_content' => 'askro_edit_own_content',
            'delete_own_content' => 'askro_delete_own_content',
            'moderate' => 'askro_moderate',
            'manage_settings' => 'manage_options'
        ];
        
        $capability = $capability_map[$action] ?? 'read';
        
        return user_can($user, $capability);
    }
    
    /**
     * Get user permissions
     * 
     * @param int $user_id User ID
     * @return array User permissions
     */
    public function get_user_permissions($user_id) {
        $user = $this->get_user($user_id);
        
        if (!$user) {
            return [];
        }
        
        $permissions = [
            'ask_question' => $this->can_user_perform_action($user_id, 'ask_question'),
            'submit_answer' => $this->can_user_perform_action($user_id, 'submit_answer'),
            'add_comment' => $this->can_user_perform_action($user_id, 'add_comment'),
            'vote' => $this->can_user_perform_action($user_id, 'vote'),
            'edit_own_content' => $this->can_user_perform_action($user_id, 'edit_own_content'),
            'delete_own_content' => $this->can_user_perform_action($user_id, 'delete_own_content'),
            'moderate' => $this->can_user_perform_action($user_id, 'moderate'),
            'manage_settings' => $this->can_user_perform_action($user_id, 'manage_settings')
        ];
        
        return $permissions;
    }
    
    /**
     * Format user data for API response
     * 
     * @param int $user_id User ID
     * @param array $options Formatting options
     * @return array Formatted user data
     */
    public function format_user_data($user_id, $options = []) {
        $defaults = [
            'include_stats' => true,
            'include_permissions' => false,
            'include_badges' => false,
            'include_achievements' => false
        ];
        
        $options = wp_parse_args($options, $defaults);
        
        $user = $this->get_user($user_id);
        
        if (!$user) {
            return null;
        }
        
        $data = [
            'id' => $user->ID,
            'username' => $user->user_login,
            'display_name' => $user->display_name,
            'email' => $user->user_email,
            'avatar' => $this->get_user_avatar($user_id, 96),
            'profile_url' => $this->get_user_profile_url($user_id),
            'join_date' => $user->user_registered,
            'points' => $this->get_user_points($user_id),
            'rank' => $this->get_user_rank($user_id)
        ];
        
        if ($options['include_stats']) {
            $data['stats'] = $this->get_user_stats($user_id);
        }
        
        if ($options['include_permissions']) {
            $data['permissions'] = $this->get_user_permissions($user_id);
        }
        
        if ($options['include_badges']) {
            $data['badges'] = $this->get_user_badges($user_id);
        }
        
        if ($options['include_achievements']) {
            $data['achievements'] = $this->get_user_achievements($user_id);
        }
        
        return $data;
    }
    
    /**
     * Clear user cache
     * 
     * @param int $user_id User ID (optional, clears all if not provided)
     */
    public function clear_cache($user_id = null) {
        if ($user_id) {
            unset($this->user_cache["user_{$user_id}"]);
        } else {
            $this->user_cache = [];
        }
    }
    
    /**
     * Get multiple users
     * 
     * @param array $user_ids Array of user IDs
     * @return array Array of user objects
     */
    public function get_multiple_users($user_ids) {
        $users = [];
        
        foreach ($user_ids as $user_id) {
            $user = $this->get_user($user_id);
            if ($user) {
                $users[$user_id] = $user;
            }
        }
        
        return $users;
    }
    
    /**
     * Search users
     * 
     * @param string $search_term Search term
     * @param int $limit Maximum results
     * @return array Array of user objects
     */
    public function search_users($search_term, $limit = 10) {
        $args = [
            'search' => "*{$search_term}*",
            'search_columns' => ['user_login', 'display_name', 'user_email'],
            'number' => $limit,
            'orderby' => 'display_name',
            'order' => 'ASC'
        ];
        
        $user_query = new WP_User_Query($args);
        $users = $user_query->get_results();
        
        // Cache the results
        foreach ($users as $user) {
            $this->user_cache["user_{$user->ID}"] = $user;
        }
        
        return $users;
    }
}

// Initialize user helper
$askro_user_helper = new Askro_User_Helper(); 

<?php
/**
 * Core Functions
 *
 * @package    Askro
 * @subpackage Core/Functions
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
 * Get Askro option from custom settings table
 *
 * @param string $option_name Option name
 * @param mixed $default Default value
 * @return mixed Option value
 * @since 1.0.0
 */
function askro_get_option($option_name, $default = '') {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_settings';
    
    // Check if settings table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        // Fallback to WordPress options table
        return get_option('askro_' . $option_name, $default);
    }
    
    $result = $wpdb->get_var($wpdb->prepare(
        "SELECT option_value FROM $table_name WHERE option_name = %s",
        $option_name
    ));
    
    if ($result === null) {
        return $default;
    }
    
    // Try to decode JSON, if it fails return as string
    $decoded = json_decode($result, true);
    return (json_last_error() === JSON_ERROR_NONE) ? $decoded : $result;
}

/**
 * Update Askro option in custom settings table
 *
 * @param string $option_name Option name
 * @param mixed $value Option value
 * @return bool
 * @since 1.0.0
 */
function askro_update_option($option_name, $value) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_settings';
    
    // Check if settings table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        // Fallback to WordPress options table
        return update_option('askro_' . $option_name, $value);
    }
    
    // Convert value to JSON if it's an array or object
    $option_value = is_array($value) || is_object($value) ? json_encode($value) : $value;
    
    $result = $wpdb->replace(
        $table_name,
        [
            'option_name' => $option_name,
            'option_value' => $option_value,
            'autoload' => 'yes'
        ],
        ['%s', '%s', '%s']
    );
    
    return $result !== false;
}

/**
 * Delete Askro option from custom settings table
 *
 * @param string $option_name Option name
 * @return bool
 * @since 1.0.0
 */
function askro_delete_option($option_name) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_settings';
    
    // Check if settings table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        // Fallback to WordPress options table
        return delete_option('askro_' . $option_name);
    }
    
    $result = $wpdb->delete(
        $table_name,
        ['option_name' => $option_name],
        ['%s']
    );
    
    return $result !== false;
}

/**
 * Get current user's Askro data
 *
 * @param int $user_id User ID (optional, defaults to current user)
 * @return array User data
 * @since 1.0.0
 */
function askro_get_user_data($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return [];
    }

    $user = get_userdata($user_id);
    if (!$user) {
        return [];
    }

    return [
        'id' => $user_id,
        'display_name' => $user->display_name,
        'email' => $user->user_email,
        'avatar' => get_avatar_url($user_id),
        'points' => askro_get_user_points($user_id),
        'rank' => askro_get_user_rank($user_id),
        'badges' => askro_get_user_badges($user_id),
        'questions_count' => askro_get_user_questions_count($user_id),
        'answers_count' => askro_get_user_answers_count($user_id),
        'registration_date' => $user->user_registered
    ];
}

/**
 * Get user's total points
 *
 * @param int $user_id User ID
 * @return int Total points
 * @since 1.0.0
 */
function askro_get_user_points($user_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_points_log';
    
    $total_points = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(points_change) FROM $table_name WHERE user_id = %d",
        $user_id
    ));
    
    return max(0, intval($total_points));
}

/**
 * Get user's rank based on points
 *
 * @param int $user_id User ID
 * @return array Rank information
 * @since 1.0.0
 */
function askro_get_user_rank($user_id) {
    $points = askro_get_user_points($user_id);
    
    $ranks = [
        ['name' => 'مبتدئ', 'min_points' => 0, 'color' => '#6b7280'],
        ['name' => 'مساهم', 'min_points' => 100, 'color' => '#3b82f6'],
        ['name' => 'نشط', 'min_points' => 500, 'color' => '#22c55e'],
        ['name' => 'خبير', 'min_points' => 1500, 'color' => '#f59e0b'],
        ['name' => 'محترف', 'min_points' => 5000, 'color' => '#8b5cf6'],
        ['name' => 'أسطورة', 'min_points' => 15000, 'color' => '#ec4899']
    ];
    
    $current_rank = $ranks[0];
    $next_rank = null;
    
    foreach ($ranks as $index => $rank) {
        if ($points >= $rank['min_points']) {
            $current_rank = $rank;
            $next_rank = isset($ranks[$index + 1]) ? $ranks[$index + 1] : null;
        }
    }
    
    return [
        'current' => $current_rank,
        'next' => $next_rank,
        'progress' => $next_rank ? (($points - $current_rank['min_points']) / ($next_rank['min_points'] - $current_rank['min_points'])) * 100 : 100
    ];
}

/**
 * Get user's badges
 *
 * @param int $user_id User ID
 * @return array User badges
 * @since 1.0.0
 */
function askro_get_user_badges($user_id) {
    global $wpdb;
    
    $badges_table = $wpdb->prefix . 'askro_badges';
    $user_badges_table = $wpdb->prefix . 'askro_user_badges';
    
    $badges = $wpdb->get_results($wpdb->prepare(
        "SELECT b.*, ub.earned_at 
         FROM $badges_table b 
         INNER JOIN $user_badges_table ub ON b.id = ub.badge_id 
         WHERE ub.user_id = %d 
         ORDER BY ub.earned_at DESC",
        $user_id
    ));
    
    return $badges ?: [];
}

/**
 * Get user's questions count
 *
 * @param int $user_id User ID
 * @return int Questions count
 * @since 1.0.0
 */
function askro_get_user_questions_count($user_id) {
    // Check cache first
    $cache_key = 'askro_user_questions_count_' . $user_id;
    $cached_count = wp_cache_get($cache_key, 'askro');
    
    if ($cached_count !== false) {
        return $cached_count;
    }
    
    $count = get_posts([
        'post_type' => 'askro_question',
        'author' => $user_id,
        'post_status' => 'publish',
        'numberposts' => 1000, // Reasonable limit for performance
        'fields' => 'ids'
    ]);
    
    $count_result = count($count);
    
    // Cache the result for 30 minutes
    wp_cache_set($cache_key, $count_result, 'askro', 30 * MINUTE_IN_SECONDS);
    
    return $count_result;
}

/**
 * Get user's answers count
 *
 * @param int $user_id User ID
 * @return int Answers count
 * @since 1.0.0
 */
function askro_get_user_answers_count($user_id) {
    // Check cache first
    $cache_key = 'askro_user_answers_count_' . $user_id;
    $cached_count = wp_cache_get($cache_key, 'askro');
    
    if ($cached_count !== false) {
        return $cached_count;
    }
    
    $count = get_posts([
        'post_type' => 'askro_answer',
        'author' => $user_id,
        'post_status' => 'publish',
        'numberposts' => 1000, // Reasonable limit for performance
        'fields' => 'ids'
    ]);
    
    $count_result = count($count);
    
    // Cache the result for 30 minutes
    wp_cache_set($cache_key, $count_result, 'askro', 30 * MINUTE_IN_SECONDS);
    
    return $count_result;
}

/**
 * Get answers count for a question or post
 *
 * @param int $post_id Post ID
 * @return int Answers count
 * @since 1.0.0
 */
function askro_get_answers_count($post_id) {
    return askro_get_question_answers_count($post_id);
}

/**
 * Reward points to a user
 *
 * @param int $user_id User ID
 * @param int $points Points to reward
 * @param string $reason Reason for the reward
 * @return bool Success
 * @since 1.0.0
 */
function askro_award_points($user_id, $points, $reason) {
    return askro_add_user_points($user_id, $points, $reason);
}

/**
 * Award a badge to a user
 *
 * @param int $user_id User ID
 * @param string $badge_category Badge category
 * @return bool Success
 * @since 1.0.0
 */
function askro_award_badge($user_id, $badge_category) {
    global $wpdb;
    
    $badges_table = $wpdb->prefix . 'askro_badges';
    $user_badges_table = $wpdb->prefix . 'askro_user_badges';
    $badge = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $badges_table WHERE badge_category = %s",
        $badge_category
    ));

    if (!$badge) {
        return false;
    }

    // Check if badge already awarded
    $already_awarded = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $user_badges_table WHERE user_id = %d AND badge_id = %d",
        $user_id,
        $badge->id
    ));

    if ($already_awarded) {
        return false;
    }

    // Award the badge
    $result = $wpdb->insert(
        $user_badges_table,
        ['user_id' => $user_id, 'badge_id' => $badge->id, 'earned_at' => current_time('mysql')]
    );

    return $result !== false;
}

/**
 * Add points to user
 *
 * @param int $user_id User ID
 * @param int $points Points to add
 * @param string $reason Reason for points
 * @param string $related_type Related object type
 * @param int $related_id Related object ID
 * @param int $source_user_id Source user ID (optional)
 * @return bool Success
 * @since 1.0.0
 */
function askro_add_user_points($user_id, $points, $reason, $related_type = 'system', $related_id = 0, $source_user_id = 0) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_points_log';
    
    $result = $wpdb->insert(
        $table_name,
        [
            'user_id' => $user_id,
            'source_user_id' => $source_user_id ?: null,
            'points_change' => $points,
            'reason_key' => $reason,
            'related_type' => $related_type,
            'related_id' => $related_id ?: null,
            'created_at' => current_time('mysql')
        ],
        ['%d', '%d', '%d', '%s', '%s', '%d', '%s']
    );
    
    if ($result) {
        // Trigger action for other components
        do_action('askro_user_points_added', $user_id, $points, $reason, $related_type, $related_id);
        
        // Check for new achievements
        askro_check_user_achievements($user_id);
    }
    
    return $result !== false;
}

/**
 * Check and award user achievements
 *
 * @param int $user_id User ID
 * @since 1.0.0
 */
function askro_check_user_achievements($user_id) {
    // This will be implemented in the gamification phase
    do_action('askro_check_achievements', $user_id);
}

/**
 * Get question data
 *
 * @param int $question_id Question ID
 * @return array|null Question data
 * @since 1.0.0
 */
function askro_get_question_data($question_id) {
    $question = get_post($question_id);
    
    if (!$question || $question->post_type !== 'askro_question') {
        return null;
    }
    
    return [
        'id' => $question->ID,
        'title' => $question->post_title,
        'content' => $question->post_content,
        'author' => askro_get_user_data($question->post_author),
        'created_at' => $question->post_date,
        'status' => $question->post_status,
        'categories' => wp_get_post_terms($question->ID, 'askro_question_category'),
        'tags' => wp_get_post_terms($question->ID, 'askro_question_tag'),
        'answers_count' => askro_get_question_answers_count($question->ID),
        'votes' => askro_get_post_votes($question->ID),
        'views' => askro_get_post_views($question->ID),
        'is_solved' => get_post_meta($question->ID, '_askro_is_solved', true),
        'best_answer_id' => get_post_meta($question->ID, '_askro_best_answer', true)
    ];
}

/**
 * Get question answers count
 *
 * @param int $question_id Question ID
 * @return int Answers count
 * @since 1.0.0
 */
function askro_get_question_answers_count($question_id) {
    // Check cache first
    $cache_key = 'askro_answers_count_' . $question_id;
    $cached_count = wp_cache_get($cache_key, 'askro');
    
    if ($cached_count !== false) {
        return $cached_count;
    }
    
    // Get all published answers for the given question ID using meta_query
    // Use a reasonable limit for performance instead of -1
    $count = get_posts([
        'post_type' => 'askro_answer',
        'meta_query' => [
            [
                'key' => '_askro_question_id',
                'value' => $question_id,
                'compare' => '='
            ]
        ],
        'post_status' => 'publish',
        'numberposts' => 1000, // Reasonable limit for performance
        'fields' => 'ids'
    ]);
    
    $count_result = count($count);
    
    // Cache the result for 1 hour
    wp_cache_set($cache_key, $count_result, 'askro', HOUR_IN_SECONDS);
    
    return $count_result;
}

/**
 * Get total votes for a post
 *
 * @param int $post_id Post ID
 * @return int Total votes
 * @since 1.0.0
 */
function askro_get_total_votes($post_id) {
    $votes = askro_get_post_votes($post_id);
    return $votes['total'] ?? 0;
}

/**
 * Get post votes data
 *
 * @param int $post_id Post ID
 * @return array Votes data
 * @since 1.0.0
 */
function askro_get_post_votes($post_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_user_votes';
    
    $votes = $wpdb->get_results($wpdb->prepare(
        "SELECT vote_type, COUNT(*) as count, SUM(vote_strength) as total_strength
         FROM $table_name 
         WHERE post_id = %d 
         GROUP BY vote_type",
        $post_id
    ));
    
    $vote_data = [
        'total' => 0,
        'by_type' => []
    ];
    
    foreach ($votes as $vote) {
        $vote_data['by_type'][$vote->vote_type] = [
            'count' => intval($vote->count),
            'strength' => intval($vote->total_strength)
        ];
        $vote_data['total'] += intval($vote->total_strength);
    }
    
    return $vote_data;
}

/**
 * Get post views count
 *
 * @param int $post_id Post ID
 * @return int Views count
 * @since 1.0.0
 */
function askro_get_post_views($post_id) {
    $views = get_post_meta($post_id, '_askro_views', true);
    return intval($views);
}

/**
 * Increment post views
 *
 * @param int $post_id Post ID
 * @since 1.0.0
 */
function askro_increment_post_views($post_id) {
    $views = askro_get_post_views($post_id);
    update_post_meta($post_id, '_askro_views', $views + 1);
    
    // Log analytics
    askro_log_analytics('post_view', get_current_user_id(), 'post', $post_id);
}

/**
 * Log analytics event
 *
 * @param string $event_type Event type
 * @param int $user_id User ID
 * @param string $object_type Object type
 * @param int $object_id Object ID
 * @param array $data Additional data
 * @since 1.0.0
 */
function askro_log_analytics($event_type, $user_id = 0, $object_type = '', $object_id = 0, $data = []) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_analytics';
    
    $wpdb->insert(
        $table_name,
        [
            'event_type' => $event_type,
            'user_id' => $user_id ?: null,
            'object_type' => $object_type ?: null,
            'object_id' => $object_id ?: null,
            'data' => !empty($data) ? json_encode($data) : null,
            'ip_hash' => hash('sha256', $_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent_hash' => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''),
            'created_at' => current_time('mysql')
        ],
        ['%s', '%d', '%s', '%d', '%s', '%s', '%s', '%s']
    );
}

/**
 * Format time ago
 *
 * @param string $datetime DateTime string
 * @return string Formatted time ago
 * @since 1.0.0
 */
function askro_time_ago($datetime) {
    if (empty($datetime)) {
        return 'غير محدد';
    }
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'الآن';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return sprintf(_n('منذ دقيقة', 'منذ %s دقائق', $minutes, 'askro'), $minutes);
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return sprintf(_n('منذ ساعة', 'منذ %s ساعات', $hours, 'askro'), $hours);
    } elseif ($time < 2592000) {
        $days = floor($time / 86400);
        return sprintf(_n('منذ يوم', 'منذ %s أيام', $days, 'askro'), $days);
    } elseif ($time < 31536000) {
        $months = floor($time / 2592000);
        return sprintf(_n('منذ شهر', 'منذ %s أشهر', $months, 'askro'), $months);
    } else {
        $years = floor($time / 31536000);
        return sprintf(_n('منذ سنة', 'منذ %s سنوات', $years, 'askro'), $years);
    }
}

/**
 * Format number with K/M suffixes
 *
 * @param int $number Number to format
 * @return string Formatted number
 * @since 1.0.0
 */
function askro_format_number($number) {
    if ($number >= 1000000) {
        return round($number / 1000000, 1) . 'M';
    } elseif ($number >= 1000) {
        return round($number / 1000, 1) . 'K';
    }
    
    return number_format($number);
}

/**
 * Sanitize and validate vote data
 *
 * @param array $vote_data Vote data
 * @return array|false Sanitized data or false on failure
 * @since 1.0.0
 */
function askro_sanitize_vote_data($vote_data) {
    $allowed_types = ['useful', 'creative', 'emotional', 'toxic', 'offtopic', 'funny', 'deep', 'inaccurate', 'spam', 'duplicate'];
    
    if (!isset($vote_data['post_id']) || !isset($vote_data['vote_type'])) {
        return false;
    }
    
    $sanitized = [
        'post_id' => intval($vote_data['post_id']),
        'vote_type' => sanitize_text_field($vote_data['vote_type']),
        'vote_strength' => isset($vote_data['vote_strength']) ? intval($vote_data['vote_strength']) : 1
    ];
    
    // Validate vote type
    if (!in_array($sanitized['vote_type'], $allowed_types)) {
        return false;
    }
    
    // Validate vote strength
    if ($sanitized['vote_strength'] < -3 || $sanitized['vote_strength'] > 3 || $sanitized['vote_strength'] == 0) {
        return false;
    }
    
    // Validate post exists
    $post = get_post($sanitized['post_id']);
    if (!$post || !in_array($post->post_type, ['askro_question', 'askro_answer'])) {
        return false;
    }
    
    return $sanitized;
}

/**
 * Check if user can vote on post
 *
 * @param int $user_id User ID
 * @param int $post_id Post ID
 * @return bool Can vote
 * @since 1.0.0
 */
function askro_can_user_vote($user_id, $post_id) {
    if (!$user_id) {
        return false;
    }
    
    $post = get_post($post_id);
    if (!$post) {
        return false;
    }
    
    // Users can't vote on their own posts
    if ($post->post_author == $user_id) {
        return false;
    }
    
    // Check user capabilities
    if (!user_can($user_id, 'read')) {
        return false;
    }
    
    return true;
}

/**
 * Get user's vote on post
 *
 * @param int $user_id User ID
 * @param int $post_id Post ID
 * @param string $vote_type Vote type (optional)
 * @return array|null Vote data
 * @since 1.0.0
 */
function askro_get_user_vote($user_id, $post_id, $vote_type = '') {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_user_votes';
    
    $where = $wpdb->prepare("user_id = %d AND post_id = %d", $user_id, $post_id);
    
    if ($vote_type) {
        $where .= $wpdb->prepare(" AND vote_type = %s", $vote_type);
    }
    
    $vote = $wpdb->get_row("SELECT * FROM $table_name WHERE $where");
    
    return $vote;
}

/**
 * Generate secure nonce for AJAX requests
 *
 * @param string $action Action name
 * @return string Nonce
 * @since 1.0.0
 */
function askro_create_nonce($action = 'askro_nonce') {
    return wp_create_nonce($action);
}

/**
 * Verify nonce for AJAX requests
 *
 * @param string $nonce Nonce to verify
 * @param string $action Action name
 * @return bool Valid nonce
 * @since 1.0.0
 */
function askro_verify_nonce($nonce, $action = 'askro_nonce') {
    return wp_verify_nonce($nonce, $action);
}

/**
 * Check if database tables exist
 *
 * @return bool True if all tables exist, false otherwise
 * @since 1.0.0
 */
function askro_check_database_tables() {
    $database = new Askro_Database();
    $table_status = $database->get_table_status();
    
    foreach ($table_status as $table) {
        if (!$table['exists']) {
            return false;
        }
    }
    
    return true;
}

/**
 * Get plugin info for system status
 *
 * @return array Plugin info
 * @since 1.0.0
 */
function askro_get_system_info() {
    global $wpdb;
    
    $database = new Askro_Database();
    
    return [
        'plugin_version' => ASKRO_VERSION,
        'wp_version' => get_bloginfo('version'),
        'php_version' => PHP_VERSION,
        'mysql_version' => $wpdb->db_version(),
        'theme' => wp_get_theme()->get('Name'),
        'active_plugins' => count(get_option('active_plugins', [])),
        'database_tables' => $database->get_table_status(),
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize')
    ];
}

/**
 * Get points dashboard data
 *
 * @return array Dashboard data
 * @since 1.0.0
 */
function askro_get_points_dashboard_data() {
    global $wpdb;
    
    $points_table = $wpdb->prefix . 'askro_points_log';
    
    // Get total points awarded
    $total_points = $wpdb->get_var(
        "SELECT SUM(CASE WHEN points_change > 0 THEN points_change ELSE 0 END) FROM $points_table"
    );
    
    // Get points awarded this month
    $monthly_points = $wpdb->get_var(
        "SELECT SUM(CASE WHEN points_change > 0 THEN points_change ELSE 0 END) 
         FROM $points_table 
         WHERE created_at >= DATE_FORMAT(NOW(), '%Y-%m-01')"
    );
    
    // Get total transactions
    $total_transactions = $wpdb->get_var(
        "SELECT COUNT(*) FROM $points_table"
    );
    
    // Get active users (users who gained points in last 30 days)
    $active_users = $wpdb->get_var(
        "SELECT COUNT(DISTINCT user_id) 
         FROM $points_table 
         WHERE points_change > 0 
         AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    
    // Get top reasons for points
    $top_reasons = $wpdb->get_results(
        "SELECT reason_key, COUNT(*) as count, SUM(points_change) as total_points
         FROM $points_table 
         WHERE points_change > 0
         GROUP BY reason_key 
         ORDER BY total_points DESC 
         LIMIT 5"
    );
    
    // Get recent activity (last 30 days by day)
    $recent_activity = $wpdb->get_results(
        "SELECT DATE(created_at) as date, 
                SUM(CASE WHEN points_change > 0 THEN points_change ELSE 0 END) as points_awarded,
                COUNT(*) as transactions
         FROM $points_table 
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY DATE(created_at) 
         ORDER BY date ASC"
    );
    
    return [
        'stats' => [
            'total_points' => intval($total_points),
            'monthly_points' => intval($monthly_points),
            'total_transactions' => intval($total_transactions),
            'active_users' => intval($active_users)
        ],
        'top_reasons' => $top_reasons ?: [],
        'recent_activity' => $recent_activity ?: []
    ];
}

/**
 * Get paginated points transactions
 *
 * @param array $args Query arguments
 * @return array Transactions data with pagination
 * @since 1.0.0
 */
function askro_get_points_transactions($args = []) {
    global $wpdb;
    
    $defaults = [
        'per_page' => 20,
        'page' => 1,
        'user_id' => 0,
        'reason' => '',
        'date_from' => '',
        'date_to' => '',
        'points_min' => '',
        'points_max' => '',
        'orderby' => 'created_at',
        'order' => 'DESC'
    ];
    
    $args = wp_parse_args($args, $defaults);
    $points_table = $wpdb->prefix . 'askro_points_log';
    
    // Build WHERE clause
    $where_conditions = ['1=1'];
    $prepare_values = [];
    
    if ($args['user_id']) {
        $where_conditions[] = 'user_id = %d';
        $prepare_values[] = $args['user_id'];
    }
    
    if ($args['reason']) {
        $where_conditions[] = 'reason_key LIKE %s';
        $prepare_values[] = '%' . $args['reason'] . '%';
    }
    
    if ($args['date_from']) {
        $where_conditions[] = 'created_at >= %s';
        $prepare_values[] = $args['date_from'] . ' 00:00:00';
    }
    
    if ($args['date_to']) {
        $where_conditions[] = 'created_at <= %s';
        $prepare_values[] = $args['date_to'] . ' 23:59:59';
    }
    
    if ($args['points_min'] !== '') {
        $where_conditions[] = 'points_change >= %d';
        $prepare_values[] = intval($args['points_min']);
    }
    
    if ($args['points_max'] !== '') {
        $where_conditions[] = 'points_change <= %d';
        $prepare_values[] = intval($args['points_max']);
    }
    
    $where_clause = implode(' AND ', $where_conditions);
    
    // Build ORDER BY clause
    $allowed_orderby = ['created_at', 'points_change', 'user_id'];
    $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'created_at';
    $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
    
    // Get total count
    $count_query = "SELECT COUNT(*) FROM $points_table WHERE $where_clause";
    if (!empty($prepare_values)) {
        $total_items = $wpdb->get_var($wpdb->prepare($count_query, $prepare_values));
    } else {
        $total_items = $wpdb->get_var($count_query);
    }
    
    // Calculate pagination
    $offset = ($args['page'] - 1) * $args['per_page'];
    $total_pages = ceil($total_items / $args['per_page']);
    
    // Get transactions
    $query = "SELECT p.*, u.display_name, u.user_email 
              FROM $points_table p 
              LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
              WHERE $where_clause 
              ORDER BY $orderby $order 
              LIMIT %d OFFSET %d";
    
    $prepare_values[] = $args['per_page'];
    $prepare_values[] = $offset;
    
    if (!empty($prepare_values)) {
        $transactions = $wpdb->get_results($wpdb->prepare($query, $prepare_values));
    } else {
        $transactions = $wpdb->get_results($query);
    }
    
    return [
        'transactions' => $transactions ?: [],
        'pagination' => [
            'total_items' => intval($total_items),
            'total_pages' => intval($total_pages),
            'current_page' => intval($args['page']),
            'per_page' => intval($args['per_page']),
            'has_prev' => $args['page'] > 1,
            'has_next' => $args['page'] < $total_pages
        ]
    ];
}

/**
 * Get analytics data for dashboard
 *
 * @return array Analytics data
 * @since 1.0.0
 */
function askro_get_analytics_data() {
    global $wpdb;
    
    $analytics_table = $wpdb->prefix . 'askro_analytics';
    $points_table = $wpdb->prefix . 'askro_points_log';
    $votes_table = $wpdb->prefix . 'askro_user_votes';
    
    // Get total questions
    $total_questions = wp_count_posts('askro_question')->publish;
    
    // Get total answers
    $total_answers = wp_count_posts('askro_answer')->publish;
    
    // Get active users (users who have posted questions or answers in last 30 days)
    $active_users = $wpdb->get_var(
        "SELECT COUNT(DISTINCT post_author) 
         FROM {$wpdb->posts} 
         WHERE post_type IN ('askro_question', 'askro_answer') 
         AND post_status = 'publish' 
         AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    
    // Calculate engagement rate (answers per question)
    $engagement_rate = $total_questions > 0 ? round(($total_answers / $total_questions) * 100, 1) : 0;
    
    // Calculate changes (comparing current period with previous period)
    $current_period_questions = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} 
         WHERE post_type = 'askro_question' 
         AND post_status = 'publish' 
         AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    
    $previous_period_questions = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} 
         WHERE post_type = 'askro_question' 
         AND post_status = 'publish' 
         AND post_date >= DATE_SUB(NOW(), INTERVAL 60 DAY) 
         AND post_date < DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    
    $questions_change = $previous_period_questions > 0 ? 
        round((($current_period_questions - $previous_period_questions) / $previous_period_questions) * 100, 1) : 0;
    
    $current_period_answers = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} 
         WHERE post_type = 'askro_answer' 
         AND post_status = 'publish' 
         AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    
    $previous_period_answers = $wpdb->get_var(
        "SELECT COUNT(*) FROM {$wpdb->posts} 
         WHERE post_type = 'askro_answer' 
         AND post_status = 'publish' 
         AND post_date >= DATE_SUB(NOW(), INTERVAL 60 DAY) 
         AND post_date < DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    
    $answers_change = $previous_period_answers > 0 ? 
        round((($current_period_answers - $previous_period_answers) / $previous_period_answers) * 100, 1) : 0;
    
    $current_period_users = $wpdb->get_var(
        "SELECT COUNT(DISTINCT post_author) 
         FROM {$wpdb->posts} 
         WHERE post_type IN ('askro_question', 'askro_answer') 
         AND post_status = 'publish' 
         AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    
    $previous_period_users = $wpdb->get_var(
        "SELECT COUNT(DISTINCT post_author) 
         FROM {$wpdb->posts} 
         WHERE post_type IN ('askro_question', 'askro_answer') 
         AND post_status = 'publish' 
         AND post_date >= DATE_SUB(NOW(), INTERVAL 60 DAY) 
         AND post_date < DATE_SUB(NOW(), INTERVAL 30 DAY)"
    );
    
    $users_change = $previous_period_users > 0 ? 
        round((($current_period_users - $previous_period_users) / $previous_period_users) * 100, 1) : 0;
    
    // Get questions over time (last 30 days)
    $questions_chart = $wpdb->get_results(
        "SELECT DATE(post_date) as date, COUNT(*) as count
         FROM {$wpdb->posts} 
         WHERE post_type = 'askro_question' 
         AND post_status = 'publish' 
         AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY DATE(post_date) 
         ORDER BY date ASC"
    );
    
    // Get answers over time (last 30 days)
    $answers_chart = $wpdb->get_results(
        "SELECT DATE(post_date) as date, COUNT(*) as count
         FROM {$wpdb->posts} 
         WHERE post_type = 'askro_answer' 
         AND post_status = 'publish' 
         AND post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY DATE(post_date) 
         ORDER BY date ASC"
    );
    
    // Get user registrations over time (last 30 days)
    $users_chart = $wpdb->get_results(
        "SELECT DATE(user_registered) as date, COUNT(*) as count
         FROM {$wpdb->users} 
         WHERE user_registered >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY DATE(user_registered) 
         ORDER BY date ASC"
    );
    
    // Get top questions (by views and votes)
    $top_questions = $wpdb->get_results(
        "SELECT p.ID, p.post_title, p.post_author, p.post_date,
                u.display_name as author_name,
                COALESCE(pm.meta_value, 0) as views,
                COALESCE(vote_totals.total_votes, 0) as votes,
                COALESCE(answer_counts.answer_count, 0) as answers
         FROM {$wpdb->posts} p
         LEFT JOIN {$wpdb->users} u ON p.post_author = u.ID
         LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_askro_views'
         LEFT JOIN (
             SELECT post_id, SUM(vote_strength) as total_votes
             FROM $votes_table
             GROUP BY post_id
         ) vote_totals ON p.ID = vote_totals.post_id
         LEFT JOIN (
             SELECT post_parent, COUNT(*) as answer_count
             FROM {$wpdb->posts}
             WHERE post_type = 'askro_answer' AND post_status = 'publish'
             GROUP BY post_parent
         ) answer_counts ON p.ID = answer_counts.post_parent
         WHERE p.post_type = 'askro_question' 
         AND p.post_status = 'publish'
         ORDER BY (COALESCE(pm.meta_value, 0) + COALESCE(vote_totals.total_votes, 0)) DESC
         LIMIT 10"
    );
    
    // Get top contributors (by points earned)
    $top_contributors = $wpdb->get_results(
        "SELECT u.ID as user_id, u.display_name, u.user_email,
                COALESCE(point_totals.total_points, 0) as points,
                COALESCE(question_counts.question_count, 0) as questions,
                COALESCE(answer_counts.answer_count, 0) as answers
         FROM {$wpdb->users} u
         LEFT JOIN (
             SELECT user_id, SUM(points_change) as total_points
             FROM $points_table
             WHERE points_change > 0
             GROUP BY user_id
         ) point_totals ON u.ID = point_totals.user_id
         LEFT JOIN (
             SELECT post_author, COUNT(*) as question_count
             FROM {$wpdb->posts}
             WHERE post_type = 'askro_question' AND post_status = 'publish'
             GROUP BY post_author
         ) question_counts ON u.ID = question_counts.post_author
         LEFT JOIN (
             SELECT post_author, COUNT(*) as answer_count
             FROM {$wpdb->posts}
             WHERE post_type = 'askro_answer' AND post_status = 'publish'
             GROUP BY post_author
         ) answer_counts ON u.ID = answer_counts.post_author
         HAVING points > 0 OR questions > 0 OR answers > 0
         ORDER BY points DESC
         LIMIT 10"
    );
    
    // Get popular categories
    $popular_categories = $wpdb->get_results(
        "SELECT t.name, t.slug, tt.count, tt.term_taxonomy_id
         FROM {$wpdb->terms} t
         INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
         WHERE tt.taxonomy = 'askro_question_category'
         AND tt.count > 0
         ORDER BY tt.count DESC
         LIMIT 8"
    );
    
    // Get trending tags (tags used in last 30 days)
    $trending_tags = $wpdb->get_results(
        "SELECT t.term_id, t.name, t.slug, COUNT(tr.object_id) as recent_count
         FROM {$wpdb->terms} t
         INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
         INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
         INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
         WHERE tt.taxonomy = 'askro_question_tag'
         AND p.post_type = 'askro_question'
         AND p.post_status = 'publish'
         AND p.post_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
         GROUP BY t.term_id
         ORDER BY recent_count DESC
         LIMIT 10"
    );
    
    // Get content performance data
    $content_performance = $wpdb->get_results(
        "SELECT p.ID, p.post_title, p.post_author, p.post_date,
                u.display_name as author,
                COALESCE(pm.meta_value, 0) as views,
                COALESCE(vote_totals.total_votes, 0) as votes,
                COALESCE(vote_totals.upvotes, 0) as upvotes,
                COALESCE(vote_totals.downvotes, 0) as downvotes,
                COALESCE(answer_counts.answer_count, 0) as answers_count,
                ROUND((COALESCE(vote_totals.total_votes, 0) / GREATEST(COALESCE(pm.meta_value, 1), 1)) * 100, 1) as engagement
         FROM {$wpdb->posts} p
         LEFT JOIN {$wpdb->users} u ON p.post_author = u.ID
         LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_askro_views'
         LEFT JOIN (
             SELECT post_id, 
                    SUM(vote_strength) as total_votes,
                    SUM(CASE WHEN vote_strength > 0 THEN vote_strength ELSE 0 END) as upvotes,
                    SUM(CASE WHEN vote_strength < 0 THEN ABS(vote_strength) ELSE 0 END) as downvotes
             FROM $votes_table
             GROUP BY post_id
         ) vote_totals ON p.ID = vote_totals.post_id
         LEFT JOIN (
             SELECT post_parent, COUNT(*) as answer_count
             FROM {$wpdb->posts}
             WHERE post_type = 'askro_answer' AND post_status = 'publish'
             GROUP BY post_parent
         ) answer_counts ON p.ID = answer_counts.post_parent
         WHERE p.post_type = 'askro_question' 
         AND p.post_status = 'publish'
         ORDER BY COALESCE(pm.meta_value, 0) DESC
         LIMIT 20"
    );
    
    // Fill in missing dates for charts (last 30 days)
    $date_range = [];
    for ($i = 29; $i >= 0; $i--) {
        $date_range[] = date('Y-m-d', strtotime("-$i days"));
    }
    
    // Normalize chart data
    $questions_by_date = [];
    $answers_by_date = [];
    $users_by_date = [];
    
    foreach ($date_range as $date) {
        $questions_by_date[$date] = 0;
        $answers_by_date[$date] = 0;
        $users_by_date[$date] = 0;
    }
    
    foreach ($questions_chart as $item) {
        $questions_by_date[$item->date] = intval($item->count);
    }
    
    foreach ($answers_chart as $item) {
        $answers_by_date[$item->date] = intval($item->count);
    }
    
    foreach ($users_chart as $item) {
        $users_by_date[$item->date] = intval($item->count);
    }
    
    // Prepare chart data for the new analytics page
    $activity_chart_data = [
        'labels' => array_keys($questions_by_date),
        'questions' => array_values($questions_by_date),
        'answers' => array_values($answers_by_date)
    ];
    
    $engagement_chart_data = [
        'labels' => ['الأسئلة', 'الإجابات', 'التعليقات', 'التصويتات', 'المشاهدات'],
        'values' => [
            $total_questions,
            $total_answers,
            $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_type = 'askro_comment'"),
            $wpdb->get_var("SELECT COUNT(*) FROM $votes_table"),
            $wpdb->get_var("SELECT SUM(meta_value) FROM {$wpdb->postmeta} WHERE meta_key = '_askro_views'")
        ]
    ];
    
    $categories_chart_data = [
        'labels' => array_map(function($cat) { return $cat->name; }, $popular_categories ?: []),
        'values' => array_map(function($cat) { return intval($cat->count); }, $popular_categories ?: [])
    ];
    
    $tags_chart_data = [
        'labels' => array_map(function($tag) { return $tag->name; }, $trending_tags ?: []),
        'values' => array_map(function($tag) { return intval($tag->recent_count); }, $trending_tags ?: [])
    ];
    
    $users_chart_data = [
        'labels' => array_keys($users_by_date),
        'values' => array_values($users_by_date)
    ];
    
    return [
        // Basic stats
        'total_questions' => intval($total_questions),
        'total_answers' => intval($total_answers),
        'active_users' => intval($active_users),
        'engagement_rate' => floatval($engagement_rate),
        
        // Changes
        'questions_change' => floatval($questions_change),
        'answers_change' => floatval($answers_change),
        'users_change' => floatval($users_change),
        'engagement_change' => 0, // Placeholder
        
        // Chart data
        'activity_chart_data' => $activity_chart_data,
        'engagement_chart_data' => $engagement_chart_data,
        'categories_chart_data' => $categories_chart_data,
        'tags_chart_data' => $tags_chart_data,
        'users_chart_data' => $users_chart_data,
        
        // Top content
        'top_questions' => array_map(function($question) {
            return [
                'id' => intval($question->ID),
                'title' => $question->post_title,
                'author' => $question->author_name,
                'date' => $question->post_date,
                'views' => intval($question->views),
                'votes' => intval($question->votes),
                'answers' => intval($question->answers)
            ];
        }, $top_questions ?: []),
        
        'top_contributors' => array_map(function($user) {
            return [
                'user_id' => intval($user->user_id),
                'display_name' => $user->display_name,
                'email' => $user->user_email,
                'points' => intval($user->points),
                'questions' => intval($user->questions),
                'answers' => intval($user->answers)
            ];
        }, $top_contributors ?: []),
        
        'popular_categories' => array_map(function($category) {
            return [
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => intval($category->count),
                'percentage' => 0 // Will be calculated
            ];
        }, $popular_categories ?: []),
        
        'trending_tags' => array_map(function($tag) {
            return [
                'id' => intval($tag->term_id),
                'name' => $tag->name,
                'slug' => $tag->slug,
                'count' => intval($tag->recent_count),
                'growth' => 0 // Placeholder
            ];
        }, $trending_tags ?: []),
        
        'content_performance' => array_map(function($content) {
            return [
                'id' => intval($content->ID),
                'title' => $content->post_title,
                'author' => $content->author,
                'created' => $content->post_date,
                'views' => intval($content->views),
                'views_change' => 0, // Placeholder
                'engagement' => floatval($content->engagement),
                'upvotes' => intval($content->upvotes),
                'downvotes' => intval($content->downvotes),
                'answers_count' => intval($content->answers_count)
            ];
        }, $content_performance ?: [])
    ];
}



/**
 * Get URL for specific page type
 *
 * @param string $page_type Page type (archive, ask_question, user_profile)
 * @return string URL
 * @since 1.0.0
 */
function askro_get_url($page_type) {
    $page_id = askro_get_option($page_type . '_page_id', 0);
    
    if ($page_id) {
        return get_permalink($page_id);
    }
    
    // Fallback URLs
    $fallbacks = [
        'archive' => home_url('/questions/'),
        'ask_question' => home_url('/ask/'),
        'user_profile' => home_url('/profile/')
    ];
    
    return isset($fallbacks[$page_type]) ? $fallbacks[$page_type] : home_url();
}



/**
 * Get question URL using custom page structure
 *
 * @param int $question_id Question ID
 * @return string Question URL
 * @since 1.0.0
 */
function askro_get_question_url($question_id) {
    $archive_page_id = askro_get_option('archive_page_id', 0);
    
    if ($archive_page_id) {
        $archive_url = get_permalink($archive_page_id);
        $question_slug = get_post_field('post_name', $question_id);
        
        // Remove trailing slash from archive URL and add question slug
        $archive_url = rtrim($archive_url, '/');
        return $archive_url . '/' . $question_slug . '/';
    }
    
    // Fallback to default permalink
    return get_permalink($question_id);
}

/**
 * Get leaderboard data
 *
 * @param string $timeframe Timeframe (weekly, monthly, yearly, all_time)
 * @param int $limit Number of users to return
 * @return array Leaderboard data
 * @since 1.0.0
 */
function askro_get_leaderboard_data($timeframe = 'all_time', $limit = 10) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_points_log';
    
    // Build date filter
    $date_filter = '';
    switch ($timeframe) {
        case 'weekly':
            $date_filter = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            break;
        case 'monthly':
            $date_filter = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            break;
        case 'yearly':
            $date_filter = "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            break;
        default:
            $date_filter = '';
    }
    
    $query = "
        SELECT 
            u.ID,
            u.display_name,
            u.user_email,
            SUM(pl.points_change) as total_points,
            COUNT(DISTINCT q.ID) as questions_count,
            COUNT(DISTINCT a.ID) as answers_count,
            COUNT(DISTINCT CASE WHEN a_meta.meta_value = '1' THEN a.ID END) as best_answers_count
        FROM {$wpdb->users} u
        LEFT JOIN {$table_name} pl ON u.ID = pl.user_id {$date_filter}
        LEFT JOIN {$wpdb->posts} q ON u.ID = q.post_author AND q.post_type = 'askro_question' AND q.post_status = 'publish'
        LEFT JOIN {$wpdb->posts} a ON u.ID = a.post_author AND a.post_type = 'askro_answer' AND a.post_status = 'publish'
        LEFT JOIN {$wpdb->postmeta} a_meta ON a.ID = a_meta.post_id AND a_meta.meta_key = 'askro_best_answer'
        GROUP BY u.ID
        HAVING total_points > 0
        ORDER BY total_points DESC
        LIMIT %d
    ";
    
    $results = $wpdb->get_results($wpdb->prepare($query, $limit));
    
    if (!$results) {
        return [];
    }
    
    return array_map(function($user) {
        return [
            'id' => intval($user->ID),
            'display_name' => $user->display_name,
            'email' => $user->user_email,
            'avatar' => get_avatar_url($user->ID),
            'points' => intval($user->total_points),
            'questions_count' => intval($user->questions_count),
            'answers_count' => intval($user->answers_count),
            'best_answers_count' => intval($user->best_answers_count),
            'rank' => askro_get_user_rank($user->ID)
        ];
    }, $results);
}

/**
 * Get user best answers count
 *
 * @param int $user_id User ID
 * @return int Best answers count
 * @since 1.0.0
 */
function askro_get_user_best_answers_count($user_id) {
    global $wpdb;
    
    $query = "
        SELECT COUNT(*)
        FROM {$wpdb->posts} p
        JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_author = %d
        AND p.post_type = 'askro_answer'
        AND p.post_status = 'publish'
        AND pm.meta_key = 'askro_best_answer'
        AND pm.meta_value = '1'
    ";
    
    return intval($wpdb->get_var($wpdb->prepare($query, $user_id)));
}

/**
 * Get user achievements
 *
 * @param int $user_id User ID
 * @return array Achievements
 * @since 1.0.0
 */
function askro_get_user_achievements($user_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_achievements';
    
    $query = "
        SELECT 
            a.id,
            a.name as title,
            a.description,
            a.icon,
            ua.completed_at
        FROM {$table_name} a
        JOIN {$wpdb->prefix}askro_user_achievements ua ON a.id = ua.achievement_id
        WHERE ua.user_id = %d
        ORDER BY ua.completed_at DESC
    ";
    
    $results = $wpdb->get_results($wpdb->prepare($query, $user_id));
    
    if (!$results) {
        return [];
    }
    
    return array_map(function($achievement) {
        return [
            'id' => intval($achievement->id),
            'title' => $achievement->title,
            'description' => $achievement->description,
            'icon' => $achievement->icon,
            'earned_date' => $achievement->completed_at
        ];
    }, $results);
}

/**
 * Get community total points
 *
 * @return int Total points
 * @since 1.0.0
 */
function askro_get_community_total_points() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_points_log';
    
    $query = "SELECT SUM(points_change) FROM {$table_name} WHERE points_change > 0";
    
    return intval($wpdb->get_var($query));
}

/**
 * Get popular searches
 *
 * @param int $limit Number of searches to return
 * @return array Popular searches
 * @since 1.0.0
 */
function askro_get_popular_searches($limit = 10) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_analytics';
    
    $searches = $wpdb->get_results($wpdb->prepare(
        "SELECT event_data, COUNT(*) as count 
         FROM {$table_name} 
         WHERE event_type = 'search' 
         GROUP BY event_data 
         ORDER BY count DESC 
         LIMIT %d",
        $limit
    ));
    
    return $searches;
}



/**
 * Get total vote score for a post
 *
 * @param int $post_id Post ID
 * @return int Total score
 * @since 1.0.0
 */
function askro_get_vote_score($post_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_user_votes';
    
    $score = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(vote_value) FROM {$table_name} WHERE post_id = %d",
        $post_id
    ));
    
    return intval($score) ?: 0;
}

/**
 * Get comments for a post
 *
 * @param int $post_id Post ID
 * @param int $page Page number
 * @param int $per_page Comments per page
 * @return array Comments
 * @since 1.0.0
 */
function askro_get_comments($post_id, $page = 1, $per_page = 10) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_comments';
    
    $offset = ($page - 1) * $per_page;
    
    $comments = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$table_name} 
         WHERE post_id = %d 
         ORDER BY created_at ASC 
         LIMIT %d OFFSET %d",
        $post_id, $per_page, $offset
    ));
    
    return $comments;
}

/**
 * Get comment reactions
 *
 * @param int $comment_id Comment ID
 * @return array Reactions
 * @since 1.0.0
 */
function askro_get_comment_reactions($comment_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_comment_reactions';
    
    $reactions = $wpdb->get_results($wpdb->prepare(
        "SELECT reaction_type, COUNT(*) as count 
         FROM {$table_name} 
         WHERE comment_id = %d 
         GROUP BY reaction_type",
        $comment_id
    ));
    
    return $reactions;
}

/**
 * Get user's reaction to a comment
 *
 * @param int $user_id User ID
 * @param int $comment_id Comment ID
 * @return string|null Reaction type
 * @since 1.0.0
 */
function askro_get_user_comment_reaction($user_id, $comment_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_comment_reactions';
    
    $reaction = $wpdb->get_var($wpdb->prepare(
        "SELECT reaction_type FROM {$table_name} 
         WHERE user_id = %d AND comment_id = %d",
        $user_id, $comment_id
    ));
    
    return $reaction;
}

/**
 * Search questions with advanced filters
 *
 * @param string $query Search query
 * @param array $filters Search filters
 * @return array Search results
 * @since 1.0.0
 */
function askro_search_questions($query, $filters = []) {
    $args = [
        'post_type' => 'askro_question',
        'post_status' => 'publish',
        'posts_per_page' => 20,
        's' => $query,
        'orderby' => 'relevance'
    ];

    // Apply filters
    if (!empty($filters['category'])) {
        $args['tax_query'][] = [
            'taxonomy' => 'askro_question_category',
            'field' => 'slug',
            'terms' => $filters['category']
        ];
    }

    if (!empty($filters['status'])) {
        $args['meta_query'][] = [
            'key' => '_askro_status',
            'value' => $filters['status']
        ];
    }

    if (!empty($filters['date_range'])) {
        $date_range = $filters['date_range'];
        if (!empty($date_range['start']) && !empty($date_range['end'])) {
            $args['date_query'] = [
                [
                    'after' => $date_range['start'],
                    'before' => $date_range['end'],
                    'inclusive' => true
                ]
            ];
        }
    }

    if (!empty($filters['author'])) {
        $args['author'] = intval($filters['author']);
    }

    if (!empty($filters['tags'])) {
        $args['tax_query'][] = [
            'taxonomy' => 'askro_question_tag',
            'field' => 'slug',
            'terms' => explode(',', $filters['tags'])
        ];
    }

    return get_posts($args);
}

/**
 * Get filtered questions
 *
 * @param array $filters Filter criteria
 * @return array Filtered questions
 * @since 1.0.0
 */
function askro_get_filtered_questions($filters = []) {
    $args = [
        'post_type' => 'askro_question',
        'post_status' => 'publish',
        'posts_per_page' => 15,
        'paged' => get_query_var('paged') ? get_query_var('paged') : 1
    ];

    // Apply filters
    if (!empty($filters['categories'])) {
        $args['tax_query'][] = [
            'taxonomy' => 'askro_question_category',
            'field' => 'slug',
            'terms' => $filters['categories']
        ];
    }

    if (!empty($filters['tags'])) {
        $args['tax_query'][] = [
            'taxonomy' => 'askro_question_tag',
            'field' => 'slug',
            'terms' => $filters['tags']
        ];
    }

    if (!empty($filters['status'])) {
        $args['meta_query'][] = [
            'key' => '_askro_status',
            'value' => $filters['status']
        ];
    }

    if (!empty($filters['date_range'])) {
        $date_range = $filters['date_range'];
        if (!empty($date_range['start']) && !empty($date_range['end'])) {
            $args['date_query'] = [
                [
                    'after' => $date_range['start'],
                    'before' => $date_range['end'],
                    'inclusive' => true
                ]
            ];
        }
    }

    if (!empty($filters['vote_range'])) {
        $vote_range = $filters['vote_range'];
        if (!empty($vote_range['min']) || !empty($vote_range['max'])) {
            $vote_query = ['key' => '_askro_vote_count'];
            if (!empty($vote_range['min'])) {
                $vote_query['value'] = intval($vote_range['min']);
                $vote_query['compare'] = '>=';
            }
            if (!empty($vote_range['max'])) {
                $vote_query['value'] = intval($vote_range['max']);
                $vote_query['compare'] = '<=';
            }
            $args['meta_query'][] = $vote_query;
        }
    }

    if (!empty($filters['answer_range'])) {
        $answer_range = $filters['answer_range'];
        if (!empty($answer_range['min']) || !empty($answer_range['max'])) {
            $answer_query = ['key' => '_askro_answer_count'];
            if (!empty($answer_range['min'])) {
                $answer_query['value'] = intval($answer_range['min']);
                $answer_query['compare'] = '>=';
            }
            if (!empty($answer_range['max'])) {
                $answer_query['value'] = intval($answer_range['max']);
                $answer_query['compare'] = '<=';
            }
            $args['meta_query'][] = $answer_query;
        }
    }

    if (!empty($filters['authors'])) {
        $args['author__in'] = array_map('intval', $filters['authors']);
    }

    if (!empty($filters['solved_only'])) {
        $args['meta_query'][] = [
            'key' => '_askro_status',
            'value' => 'solved'
        ];
    }

    if (!empty($filters['unanswered_only'])) {
        $args['meta_query'][] = [
            'key' => '_askro_answer_count',
            'value' => '0',
            'compare' => '='
        ];
    }

    if (!empty($filters['has_attachments'])) {
        $args['meta_query'][] = [
            'key' => '_askro_attachments',
            'compare' => 'EXISTS'
        ];
    }

    // Apply sorting
    if (!empty($filters['sort_by'])) {
        switch ($filters['sort_by']) {
            case 'date':
                $args['orderby'] = 'date';
                break;
            case 'votes':
                $args['meta_key'] = '_askro_vote_count';
                $args['orderby'] = 'meta_value_num';
                break;
            case 'answers':
                $args['meta_key'] = '_askro_answer_count';
                $args['orderby'] = 'meta_value_num';
                break;
            case 'views':
                $args['meta_key'] = '_askro_view_count';
                $args['orderby'] = 'meta_value_num';
                break;
            case 'title':
                $args['orderby'] = 'title';
                break;
        }

        $args['order'] = !empty($filters['sort_order']) ? strtoupper($filters['sort_order']) : 'DESC';
    }

    return get_posts($args);
}

/**
 * Get search suggestions
 *
 * @param string $query Search query
 * @return array Suggestions
 * @since 1.0.0
 */
function askro_get_search_suggestions($query) {
    global $wpdb;
    
    $suggestions = [];
    
    // Get popular search terms
    $search_terms = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT post_title 
         FROM {$wpdb->posts} 
         WHERE post_type = 'askro_question' 
         AND post_status = 'publish' 
         AND post_title LIKE %s 
         ORDER BY post_date DESC 
         LIMIT 5",
        '%' . $wpdb->esc_like($query) . '%'
    ));
    
    $suggestions = array_merge($suggestions, $search_terms);
    
    // Get popular tags
    $tags = get_terms([
        'taxonomy' => 'askro_question_tag',
        'name__like' => $query,
        'number' => 3,
        'orderby' => 'count',
        'order' => 'DESC'
    ]);
    
    foreach ($tags as $tag) {
        $suggestions[] = $tag->name;
    }
    
    return array_unique($suggestions);
}

/**
 * Get vote types with their values
 *
 * @return array Vote types
 * @since 1.0.0
 */
function askro_get_vote_types() {
    return [
        'useful' => [
            'label' => 'مفيد',
            'icon' => '✔️',
            'value' => 3,
            'color' => '#10b981'
        ],
        'creative' => [
            'label' => 'مبدع',
            'icon' => '🧠',
            'value' => 2,
            'color' => '#8b5cf6'
        ],
        'emotional' => [
            'label' => 'عاطفي',
            'icon' => '❤️',
            'value' => 2,
            'color' => '#ef4444'
        ],
        'toxic' => [
            'label' => 'سام',
            'icon' => '☠️',
            'value' => -2,
            'color' => '#dc2626'
        ],
        'offtopic' => [
            'label' => 'خارج الموضوع',
            'icon' => '🔄',
            'value' => -1,
            'color' => '#f59e0b'
        ]
    ];
}

/**
 * Get reaction types
 *
 * @return array Reaction types
 * @since 1.0.0
 */
function askro_get_reaction_types() {
    return [
        'like' => [
            'label' => 'إعجاب',
            'emoji' => '👍',
            'color' => '#3b82f6'
        ],
        'love' => [
            'label' => 'حب',
            'emoji' => '❤️',
            'color' => '#ef4444'
        ],
        'fire' => [
            'label' => 'رائع',
            'emoji' => '🔥',
            'color' => '#f59e0b'
        ]
    ];
}

/**
 * Check if user can edit comment
 *
 * @param object $comment Comment object
 * @return bool
 * @since 1.0.0
 */
function askro_can_edit_comment($comment) {
    $user_id = get_current_user_id();
    return $user_id && ($comment->user_id == $user_id || current_user_can('manage_options'));
}

/**
 * Check if user can delete comment
 *
 * @param object $comment Comment object
 * @return bool
 * @since 1.0.0
 */
function askro_can_delete_comment($comment) {
    $user_id = get_current_user_id();
    return $user_id && ($comment->user_id == $user_id || current_user_can('manage_options'));
}

/**
 * Get status label
 *
 * @param string $status Status
 * @return string Label
 * @since 1.0.0
 */
function askro_get_status_label($status) {
    $labels = [
        'open' => 'مفتوح',
        'solved' => 'محلول',
        'closed' => 'مغلق',
        'urgent' => 'عاجل'
    ];

    return isset($labels[$status]) ? $labels[$status] : $status;
}

/**
 * Get filter presets
 *
 * @return array Filter presets
 * @since 1.0.0
 */
function askro_get_filter_presets() {
    return [
        'recent' => [
            'label' => 'الأحدث',
            'filters' => [
                'sort_by' => 'date',
                'sort_order' => 'desc'
            ]
        ],
        'popular' => [
            'label' => 'الأكثر شعبية',
            'filters' => [
                'sort_by' => 'votes',
                'sort_order' => 'desc'
            ]
        ],
        'unanswered' => [
            'label' => 'بدون إجابة',
            'filters' => [
                'unanswered_only' => true
            ]
        ],
        'solved' => [
            'label' => 'محلولة',
            'filters' => [
                'solved_only' => true
            ]
        ]
    ];
}

/**
 * Set default options if they don't exist
 *
 * @since 1.0.0
 */
function askro_set_default_options() {
    $default_options = [
        'min_role_ask_question' => 'subscriber',
        'min_role_submit_answer' => 'subscriber',
        'min_role_submit_comment' => 'subscriber',
        'enable_pre_question_assistant' => true,
        'enable_image_upload' => true,
        'enable_code_editor' => true,
        'max_attachments' => 5,
        'max_file_size' => 5,
        'leaderboard_limit' => 10,
        'leaderboard_timeframe' => 'all_time',
        'show_avatars' => true,
        'show_ranks' => true,
        'search_results_per_page' => 10,
        'enable_advanced_search' => true,
        'search_highlight' => true
    ];
    
    foreach ($default_options as $option => $default_value) {
        if (!askro_get_option($option)) {
            askro_update_option($option, $default_value);
        }
    }
}

/**
 * Migrate settings from wp_options to custom settings table
 *
 * @since 1.0.0
 */
function askro_migrate_settings_to_custom_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_settings';
    
    // Check if settings table exists
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if (!$table_exists) {
        return false;
    }
    
    // Get all askro options from wp_options
    $options = $wpdb->get_results(
        "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'askro_%'"
    );
    
    if (empty($options)) {
        return true;
    }
    
    $migrated_count = 0;
    
    foreach ($options as $option) {
        // Remove 'askro_' prefix
        $option_name = str_replace('askro_', '', $option->option_name);
        
        // Check if option already exists in custom table
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE option_name = %s",
            $option_name
        ));
        
        if (!$exists) {
            // Try to decode JSON, if it fails use as string
            $value = $option->option_value;
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $value = json_encode($decoded);
            }
            
            $wpdb->insert(
                $table_name,
                [
                    'option_name' => $option_name,
                    'option_value' => $value,
                    'autoload' => 'yes'
                ],
                ['%s', '%s', '%s']
            );
            
            $migrated_count++;
        }
    }
    
    return $migrated_count;
}


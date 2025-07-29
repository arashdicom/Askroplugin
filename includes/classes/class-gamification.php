<?php
/**
 * Gamification System Class
 *
 * @package    Askro
 * @subpackage Core/Gamification
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
 * Askro Gamification Class
 *
 * Handles badges, achievements, quests, and specialized XP system
 *
 * @since 1.0.0
 */
class Askro_Gamification {

    /**
     * Initialize the gamification component
     *
     * @since 1.0.0
     */
    public function init() {
        add_action('askro_user_points_added', [$this, 'check_user_achievements'], 10, 5);
        add_action('askro_check_achievements', [$this, 'check_all_achievements']);
        add_action('wp_login', [$this, 'handle_user_login'], 10, 2);
        add_action('askro_daily_cron', [$this, 'process_daily_quests']);
        add_action('askro_weekly_cron', [$this, 'process_weekly_quests']);
        
        // Schedule cron events
        if (!wp_next_scheduled('askro_daily_cron')) {
            wp_schedule_event(time(), 'daily', 'askro_daily_cron');
        }
        
        if (!wp_next_scheduled('askro_weekly_cron')) {
            wp_schedule_event(time(), 'weekly', 'askro_weekly_cron');
        }
    }

    /**
     * Check and award user achievements
     *
     * @param int $user_id User ID
     * @param int $points Points added
     * @param string $reason Reason for points
     * @param string $related_type Related object type
     * @param int $related_id Related object ID
     * @since 1.0.0
     */
    public function check_user_achievements($user_id, $points, $reason, $related_type, $related_id) {
        $this->check_all_achievements($user_id);
    }

    /**
     * Check all achievements for a user
     *
     * @param int $user_id User ID
     * @since 1.0.0
     */
    public function check_all_achievements($user_id) {
        $achievements = $this->get_active_achievements();
        
        foreach ($achievements as $achievement) {
            if (!$this->user_has_achievement($user_id, $achievement->id)) {
                if ($this->check_achievement_criteria($user_id, $achievement)) {
                    $this->award_achievement($user_id, $achievement->id);
                }
            } elseif ($achievement->is_repeatable) {
                // Check if user can earn this achievement again
                if ($this->can_repeat_achievement($user_id, $achievement)) {
                    $this->award_achievement($user_id, $achievement->id);
                }
            }
        }
    }

    /**
     * Get all active achievements
     *
     * @return array Active achievements
     * @since 1.0.0
     */
    private function get_active_achievements() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'askro_achievements';
        
        return $wpdb->get_results(
            "SELECT * FROM $table_name WHERE is_active = 1 ORDER BY id ASC"
        );
    }

    /**
     * Check if user has achievement
     *
     * @param int $user_id User ID
     * @param int $achievement_id Achievement ID
     * @return bool Has achievement
     * @since 1.0.0
     */
    private function user_has_achievement($user_id, $achievement_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'askro_user_achievements';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND achievement_id = %d AND completed_at IS NOT NULL",
            $user_id, $achievement_id
        ));
        
        return $count > 0;
    }

    /**
     * Check achievement criteria
     *
     * @param int $user_id User ID
     * @param object $achievement Achievement object
     * @return bool Criteria met
     * @since 1.0.0
     */
    private function check_achievement_criteria($user_id, $achievement) {
        $criteria = json_decode($achievement->criteria, true);
        
        if (!$criteria) {
            return false;
        }

        foreach ($criteria as $key => $value) {
            if (!$this->check_single_criterion($user_id, $key, $value)) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check single achievement criterion
     *
     * @param int $user_id User ID
     * @param string $criterion Criterion key
     * @param mixed $required_value Required value
     * @return bool Criterion met
     * @since 1.0.0
     */
    private function check_single_criterion($user_id, $criterion, $required_value) {
        switch ($criterion) {
            case 'account_created':
                return true; // If we're checking, account exists
                
            case 'questions_count':
                return askro_get_user_questions_count($user_id) >= $required_value;
                
            case 'answers_count':
                return askro_get_user_answers_count($user_id) >= $required_value;
                
            case 'accepted_answers':
                return $this->get_user_accepted_answers_count($user_id) >= $required_value;
                
            case 'positive_votes':
                return $this->get_user_positive_votes_count($user_id) >= $required_value;
                
            case 'total_points':
                return askro_get_user_points($user_id) >= $required_value;
                
            case 'daily_login_streak':
                return $this->get_user_login_streak($user_id) >= $required_value;
                
            case 'weekly_posts':
                return $this->get_user_weekly_posts($user_id) >= $required_value;
                
            case 'first_post':
                $questions = askro_get_user_questions_count($user_id);
                $answers = askro_get_user_answers_count($user_id);
                return ($questions + $answers) >= 1;
                
            default:
                return apply_filters('askro_check_achievement_criterion', false, $user_id, $criterion, $required_value);
        }
    }

    /**
     * Award achievement to user
     *
     * @param int $user_id User ID
     * @param int $achievement_id Achievement ID
     * @since 1.0.0
     */
    private function award_achievement($user_id, $achievement_id) {
        global $wpdb;
        
        $achievement = $this->get_achievement($achievement_id);
        if (!$achievement) {
            return;
        }

        $user_achievements_table = $wpdb->prefix . 'askro_user_achievements';
        
        // Insert or update user achievement
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $user_achievements_table WHERE user_id = %d AND achievement_id = %d",
            $user_id, $achievement_id
        ));

        if ($existing) {
            // Update completion
            $wpdb->update(
                $user_achievements_table,
                ['completed_at' => current_time('mysql')],
                ['id' => $existing->id],
                ['%s'],
                ['%d']
            );
        } else {
            // Insert new achievement
            $wpdb->insert(
                $user_achievements_table,
                [
                    'user_id' => $user_id,
                    'achievement_id' => $achievement_id,
                    'completed_at' => current_time('mysql'),
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%s']
            );
        }

        // Award points
        if ($achievement->points_reward > 0) {
            askro_add_user_points(
                $user_id,
                $achievement->points_reward,
                'achievement_unlocked',
                'achievement',
                $achievement_id
            );
        }

        // Award badge if specified
        if ($achievement->badge_reward) {
            $this->award_badge($user_id, $achievement->badge_reward);
        }

        // Send notification
        $this->send_achievement_notification($user_id, $achievement);

        // Log analytics
        askro_log_analytics('achievement_unlocked', $user_id, 'achievement', $achievement_id);

        do_action('askro_achievement_awarded', $user_id, $achievement_id, $achievement);
    }

    /**
     * Award badge to user
     *
     * @param int $user_id User ID
     * @param int $badge_id Badge ID
     * @since 1.0.0
     */
    public function award_badge($user_id, $badge_id) {
        global $wpdb;
        
        $badge = $this->get_badge($badge_id);
        if (!$badge) {
            return;
        }

        // Check if user already has this badge
        if ($this->user_has_badge($user_id, $badge_id)) {
            return;
        }

        $user_badges_table = $wpdb->prefix . 'askro_user_badges';
        
        $result = $wpdb->insert(
            $user_badges_table,
            [
                'user_id' => $user_id,
                'badge_id' => $badge_id,
                'earned_at' => current_time('mysql')
            ],
            ['%d', '%d', '%s']
        );

        if ($result) {
            // Award points
            if ($badge->points_reward > 0) {
                askro_add_user_points(
                    $user_id,
                    $badge->points_reward,
                    'badge_earned',
                    'badge',
                    $badge_id
                );
            }

            // Send notification
            $this->send_badge_notification($user_id, $badge);

            // Log analytics
            askro_log_analytics('badge_earned', $user_id, 'badge', $badge_id);

            do_action('askro_badge_awarded', $user_id, $badge_id, $badge);
        }
    }

    /**
     * Check if user has badge
     *
     * @param int $user_id User ID
     * @param int $badge_id Badge ID
     * @return bool Has badge
     * @since 1.0.0
     */
    private function user_has_badge($user_id, $badge_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'askro_user_badges';
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE user_id = %d AND badge_id = %d",
            $user_id, $badge_id
        ));
        
        return $count > 0;
    }

    /**
     * Get achievement by ID
     *
     * @param int $achievement_id Achievement ID
     * @return object|null Achievement object
     * @since 1.0.0
     */
    private function get_achievement($achievement_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'askro_achievements';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $achievement_id
        ));
    }

    /**
     * Get badge by ID
     *
     * @param int $badge_id Badge ID
     * @return object|null Badge object
     * @since 1.0.0
     */
    private function get_badge($badge_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'askro_badges';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $badge_id
        ));
    }

    /**
     * Get user's accepted answers count
     *
     * @param int $user_id User ID
     * @return int Accepted answers count
     * @since 1.0.0
     */
    private function get_user_accepted_answers_count($user_id) {
        global $wpdb;
        
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_author = %d 
             AND p.post_type = 'askro_answer'
             AND p.post_status = 'publish'
             AND pm.meta_key = '_askro_is_best_answer'
             AND pm.meta_value = '1'",
            $user_id
        ));
        
        return intval($count);
    }

    /**
     * Get user's positive votes count
     *
     * @param int $user_id User ID
     * @return int Positive votes count
     * @since 1.0.0
     */
    private function get_user_positive_votes_count($user_id) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'askro_user_votes';
        $positive_types = ['useful', 'creative', 'deep', 'funny', 'emotional'];
        
        $placeholders = implode(',', array_fill(0, count($positive_types), '%s'));
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name 
             WHERE target_user_id = %d 
             AND vote_type IN ($placeholders) 
             AND vote_strength > 0",
            array_merge([$user_id], $positive_types)
        );
        
        return intval($wpdb->get_var($query));
    }

    /**
     * Get user's login streak
     *
     * @param int $user_id User ID
     * @return int Login streak days
     * @since 1.0.0
     */
    private function get_user_login_streak($user_id) {
        $last_login = get_user_meta($user_id, '_askro_last_login', true);
        $login_streak = get_user_meta($user_id, '_askro_login_streak', true);
        
        if (!$last_login) {
            return 0;
        }
        
        $today = date('Y-m-d');
        $last_login_date = date('Y-m-d', strtotime($last_login));
        
        if ($last_login_date === $today) {
            return intval($login_streak);
        }
        
        return 0;
    }

    /**
     * Get user's weekly posts count
     *
     * @param int $user_id User ID
     * @return int Weekly posts count
     * @since 1.0.0
     */
    private function get_user_weekly_posts($user_id) {
        $week_start = date('Y-m-d', strtotime('monday this week'));
        
        $questions = get_posts([
            'post_type' => 'askro_question',
            'author' => $user_id,
            'post_status' => 'publish',
            'date_query' => [
                'after' => $week_start
            ],
            'numberposts' => -1,
            'fields' => 'ids'
        ]);
        
        $answers = get_posts([
            'post_type' => 'askro_answer',
            'author' => $user_id,
            'post_status' => 'publish',
            'date_query' => [
                'after' => $week_start
            ],
            'numberposts' => -1,
            'fields' => 'ids'
        ]);
        
        return count($questions) + count($answers);
    }

    /**
     * Handle user login for streak tracking
     *
     * @param string $user_login User login
     * @param WP_User $user User object
     * @since 1.0.0
     */
    public function handle_user_login($user_login, $user) {
        $user_id = $user->ID;
        $today = date('Y-m-d');
        $last_login = get_user_meta($user_id, '_askro_last_login', true);
        $login_streak = get_user_meta($user_id, '_askro_login_streak', true);
        
        if (!$last_login) {
            // First login
            update_user_meta($user_id, '_askro_last_login', $today);
            update_user_meta($user_id, '_askro_login_streak', 1);
            return;
        }
        
        $last_login_date = date('Y-m-d', strtotime($last_login));
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        
        if ($last_login_date === $today) {
            // Already logged in today
            return;
        } elseif ($last_login_date === $yesterday) {
            // Consecutive day login
            $login_streak = intval($login_streak) + 1;
        } else {
            // Streak broken
            $login_streak = 1;
        }
        
        update_user_meta($user_id, '_askro_last_login', $today);
        update_user_meta($user_id, '_askro_login_streak', $login_streak);
        
        // Award points for daily login
        askro_add_user_points($user_id, 1, 'daily_login', 'system', 0);
        
        // Check for login streak achievements
        $this->check_all_achievements($user_id);
    }

    /**
     * Check if achievement can be repeated
     *
     * @param int $user_id User ID
     * @param object $achievement Achievement object
     * @return bool Can repeat
     * @since 1.0.0
     */
    private function can_repeat_achievement($user_id, $achievement) {
        if (!$achievement->is_repeatable) {
            return false;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'askro_user_achievements';
        
        // Check last completion time
        $last_completion = $wpdb->get_var($wpdb->prepare(
            "SELECT completed_at FROM $table_name 
             WHERE user_id = %d AND achievement_id = %d 
             ORDER BY completed_at DESC LIMIT 1",
            $user_id, $achievement->id
        ));
        
        if (!$last_completion) {
            return true;
        }
        
        // For now, allow repeating after 24 hours
        $time_diff = time() - strtotime($last_completion);
        return $time_diff >= 86400; // 24 hours
    }

    /**
     * Send achievement notification
     *
     * @param int $user_id User ID
     * @param object $achievement Achievement object
     * @since 1.0.0
     */
    private function send_achievement_notification($user_id, $achievement) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'askro_notifications';
        
        $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'type' => 'achievement_unlocked',
                'title' => sprintf(__('إنجاز جديد: %s', 'askro'), $achievement->name),
                'content' => $achievement->description,
                'related_type' => 'achievement',
                'related_id' => $achievement->id,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s', '%d', '%s']
        );
    }

    /**
     * Send badge notification
     *
     * @param int $user_id User ID
     * @param object $badge Badge object
     * @since 1.0.0
     */
    private function send_badge_notification($user_id, $badge) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'askro_notifications';
        
        $wpdb->insert(
            $table_name,
            [
                'user_id' => $user_id,
                'type' => 'badge_earned',
                'title' => sprintf(__('شارة جديدة: %s', 'askro'), $badge->name),
                'content' => $badge->description,
                'related_type' => 'badge',
                'related_id' => $badge->id,
                'created_at' => current_time('mysql')
            ],
            ['%d', '%s', '%s', '%s', '%s', '%d', '%s']
        );
    }

    /**
     * Process daily quests
     *
     * @since 1.0.0
     */
    public function process_daily_quests() {
        // Implementation for daily quests
        // This would reset daily quest progress and create new quests
        do_action('askro_process_daily_quests');
    }

    /**
     * Process weekly quests
     *
     * @since 1.0.0
     */
    public function process_weekly_quests() {
        // Implementation for weekly quests
        // This would reset weekly quest progress and create new quests
        do_action('askro_process_weekly_quests');
    }

    /**
     * Get user's achievements
     *
     * @param int $user_id User ID
     * @return array User achievements
     * @since 1.0.0
     */
    public function get_user_achievements($user_id) {
        global $wpdb;
        
        $achievements_table = $wpdb->prefix . 'askro_achievements';
        $user_achievements_table = $wpdb->prefix . 'askro_user_achievements';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT a.*, ua.completed_at, ua.progress 
             FROM $achievements_table a
             INNER JOIN $user_achievements_table ua ON a.id = ua.achievement_id
             WHERE ua.user_id = %d AND ua.completed_at IS NOT NULL
             ORDER BY ua.completed_at DESC",
            $user_id
        ));
    }

    /**
     * Get user's badges
     *
     * @param int $user_id User ID
     * @return array User badges
     * @since 1.0.0
     */
    public function get_user_badges($user_id) {
        global $wpdb;
        
        $badges_table = $wpdb->prefix . 'askro_badges';
        $user_badges_table = $wpdb->prefix . 'askro_user_badges';
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT b.*, ub.earned_at 
             FROM $badges_table b
             INNER JOIN $user_badges_table ub ON b.id = ub.badge_id
             WHERE ub.user_id = %d
             ORDER BY ub.earned_at DESC",
            $user_id
        ));
    }

    /**
     * Get gamification statistics
     *
     * @return array Statistics
     * @since 1.0.0
     */
    public function get_gamification_stats() {
        global $wpdb;
        
        $badges_table = $wpdb->prefix . 'askro_badges';
        $user_badges_table = $wpdb->prefix . 'askro_user_badges';
        $achievements_table = $wpdb->prefix . 'askro_achievements';
        $user_achievements_table = $wpdb->prefix . 'askro_user_achievements';
        
        return [
            'total_badges' => $wpdb->get_var("SELECT COUNT(*) FROM $badges_table WHERE is_active = 1"),
            'badges_awarded' => $wpdb->get_var("SELECT COUNT(*) FROM $user_badges_table"),
            'total_achievements' => $wpdb->get_var("SELECT COUNT(*) FROM $achievements_table WHERE is_active = 1"),
            'achievements_unlocked' => $wpdb->get_var("SELECT COUNT(*) FROM $user_achievements_table WHERE completed_at IS NOT NULL"),
            'most_earned_badge' => $wpdb->get_row(
                "SELECT b.name, COUNT(*) as count 
                 FROM $user_badges_table ub
                 INNER JOIN $badges_table b ON ub.badge_id = b.id
                 GROUP BY ub.badge_id
                 ORDER BY count DESC
                 LIMIT 1"
            ),
            'most_unlocked_achievement' => $wpdb->get_row(
                "SELECT a.name, COUNT(*) as count 
                 FROM $user_achievements_table ua
                 INNER JOIN $achievements_table a ON ua.achievement_id = a.id
                 WHERE ua.completed_at IS NOT NULL
                 GROUP BY ua.achievement_id
                 ORDER BY count DESC
                 LIMIT 1"
            )
        ];
    }
}


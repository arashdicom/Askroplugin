<?php
/**
 * Achievements Class
 *
 * @package    Askro
 * @subpackage Core/Achievements
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
 * Askro Achievements Class
 *
 * Handles badges, achievements, and gamification system
 *
 * @since 1.0.0
 */
class Askro_Achievements {

    /**
     * Available badges
     *
     * @var array
     * @since 1.0.0
     */
    private $badges = [];

    /**
     * Available achievements
     *
     * @var array
     * @since 1.0.0
     */
    private $achievements = [];

    /**
     * Initialize the achievements component
     *
     * @since 1.0.0
     */
    public function init() {
        $this->setup_badges();
        $this->setup_achievements();
        
        add_action('askro_user_action', [$this, 'check_achievements'], 10, 3);
        add_action('wp_ajax_askro_claim_reward', [$this, 'claim_reward']);
        add_action('wp_ajax_askro_get_leaderboard', [$this, 'get_leaderboard']);
        
        // Hook into various actions to trigger achievement checks
        add_action('askro_question_posted', [$this, 'check_question_achievements']);
        add_action('askro_answer_posted', [$this, 'check_answer_achievements']);
        add_action('askro_vote_cast', [$this, 'check_vote_achievements']);
        add_action('askro_answer_accepted', [$this, 'check_acceptance_achievements']);
    }

    /**
     * Setup default badges
     *
     * @since 1.0.0
     */
    public function setup_badges() {
        $this->badges = [
            // Question Badges
            'first_question' => [
                'name' => __('أول سؤال', 'askro'),
                'description' => __('طرح أول سؤال', 'askro'),
                'icon' => '❓',
                'color' => '#3B82F6',
                'type' => 'bronze',
                'criteria' => ['questions_count' => 1],
                'points' => 10
            ],
            'curious_mind' => [
                'name' => __('عقل فضولي', 'askro'),
                'description' => __('طرح 10 أسئلة', 'askro'),
                'icon' => '🤔',
                'color' => '#10B981',
                'type' => 'silver',
                'criteria' => ['questions_count' => 10],
                'points' => 50
            ],
            'question_master' => [
                'name' => __('خبير الأسئلة', 'askro'),
                'description' => __('طرح 50 سؤال', 'askro'),
                'icon' => '🎯',
                'color' => '#F59E0B',
                'type' => 'gold',
                'criteria' => ['questions_count' => 50],
                'points' => 200
            ],
            'question_legend' => [
                'name' => __('أسطورة الأسئلة', 'askro'),
                'description' => __('طرح 100 سؤال', 'askro'),
                'icon' => '👑',
                'color' => '#8B5CF6',
                'type' => 'platinum',
                'criteria' => ['questions_count' => 100],
                'points' => 500
            ],

            // Answer Badges
            'first_answer' => [
                'name' => __('أول إجابة', 'askro'),
                'description' => __('تقديم أول إجابة', 'askro'),
                'icon' => '💡',
                'color' => '#3B82F6',
                'type' => 'bronze',
                'criteria' => ['answers_count' => 1],
                'points' => 10
            ],
            'helpful_contributor' => [
                'name' => __('مساهم مفيد', 'askro'),
                'description' => __('تقديم 10 إجابات', 'askro'),
                'icon' => '🤝',
                'color' => '#10B981',
                'type' => 'silver',
                'criteria' => ['answers_count' => 10],
                'points' => 50
            ],
            'answer_expert' => [
                'name' => __('خبير الإجابات', 'askro'),
                'description' => __('تقديم 50 إجابة', 'askro'),
                'icon' => '🎓',
                'color' => '#F59E0B',
                'type' => 'gold',
                'criteria' => ['answers_count' => 50],
                'points' => 200
            ],
            'answer_guru' => [
                'name' => __('معلم الإجابات', 'askro'),
                'description' => __('تقديم 100 إجابة', 'askro'),
                'icon' => '🧙‍♂️',
                'color' => '#8B5CF6',
                'type' => 'platinum',
                'criteria' => ['answers_count' => 100],
                'points' => 500
            ],

            // Vote Badges
            'first_vote' => [
                'name' => __('أول تصويت', 'askro'),
                'description' => __('التصويت لأول مرة', 'askro'),
                'icon' => '👍',
                'color' => '#3B82F6',
                'type' => 'bronze',
                'criteria' => ['votes_cast' => 1],
                'points' => 5
            ],
            'active_voter' => [
                'name' => __('مصوت نشط', 'askro'),
                'description' => __('التصويت 50 مرة', 'askro'),
                'icon' => '🗳️',
                'color' => '#10B981',
                'type' => 'silver',
                'criteria' => ['votes_cast' => 50],
                'points' => 25
            ],
            'vote_champion' => [
                'name' => __('بطل التصويت', 'askro'),
                'description' => __('التصويت 200 مرة', 'askro'),
                'icon' => '🏆',
                'color' => '#F59E0B',
                'type' => 'gold',
                'criteria' => ['votes_cast' => 200],
                'points' => 100
            ],

            // Quality Badges
            'well_received' => [
                'name' => __('محبوب', 'askro'),
                'description' => __('الحصول على 10 تصويتات إيجابية', 'askro'),
                'icon' => '❤️',
                'color' => '#EF4444',
                'type' => 'silver',
                'criteria' => ['upvotes_received' => 10],
                'points' => 30
            ],
            'popular_contributor' => [
                'name' => __('مساهم شعبي', 'askro'),
                'description' => __('الحصول على 50 تصويت إيجابي', 'askro'),
                'icon' => '🌟',
                'color' => '#F59E0B',
                'type' => 'gold',
                'criteria' => ['upvotes_received' => 50],
                'points' => 100
            ],
            'community_favorite' => [
                'name' => __('المفضل لدى المجتمع', 'askro'),
                'description' => __('الحصول على 100 تصويت إيجابي', 'askro'),
                'icon' => '💎',
                'color' => '#8B5CF6',
                'type' => 'platinum',
                'criteria' => ['upvotes_received' => 100],
                'points' => 250
            ],

            // Acceptance Badges
            'problem_solver' => [
                'name' => __('حلال المشاكل', 'askro'),
                'description' => __('قبول 5 إجابات', 'askro'),
                'icon' => '🔧',
                'color' => '#10B981',
                'type' => 'silver',
                'criteria' => ['accepted_answers' => 5],
                'points' => 75
            ],
            'solution_master' => [
                'name' => __('خبير الحلول', 'askro'),
                'description' => __('قبول 20 إجابة', 'askro'),
                'icon' => '⚡',
                'color' => '#F59E0B',
                'type' => 'gold',
                'criteria' => ['accepted_answers' => 20],
                'points' => 200
            ],

            // Special Badges
            'early_adopter' => [
                'name' => __('المتبني المبكر', 'askro'),
                'description' => __('من أوائل المستخدمين', 'askro'),
                'icon' => '🚀',
                'color' => '#8B5CF6',
                'type' => 'special',
                'criteria' => ['registration_date' => '2025-01-01'],
                'points' => 100
            ],
            'daily_visitor' => [
                'name' => __('زائر يومي', 'askro'),
                'description' => __('زيارة الموقع 30 يوم متتالي', 'askro'),
                'icon' => '📅',
                'color' => '#10B981',
                'type' => 'silver',
                'criteria' => ['consecutive_days' => 30],
                'points' => 50
            ],
            'social_butterfly' => [
                'name' => __('اجتماعي', 'askro'),
                'description' => __('متابعة 20 مستخدم', 'askro'),
                'icon' => '🦋',
                'color' => '#EC4899',
                'type' => 'silver',
                'criteria' => ['following_count' => 20],
                'points' => 40
            ],
            'influencer' => [
                'name' => __('مؤثر', 'askro'),
                'description' => __('الحصول على 50 متابع', 'askro'),
                'icon' => '📢',
                'color' => '#F59E0B',
                'type' => 'gold',
                'criteria' => ['followers_count' => 50],
                'points' => 150
            ],

            // Engagement Badges
            'commentator' => [
                'name' => __('معلق نشط', 'askro'),
                'description' => __('كتابة 25 تعليق', 'askro'),
                'icon' => '💬',
                'color' => '#3B82F6',
                'type' => 'bronze',
                'criteria' => ['comments_count' => 25],
                'points' => 25
            ],
            'discussion_starter' => [
                'name' => __('محرك النقاش', 'askro'),
                'description' => __('طرح سؤال حصل على 20 تعليق', 'askro'),
                'icon' => '🔥',
                'color' => '#EF4444',
                'type' => 'gold',
                'criteria' => ['question_comments' => 20],
                'points' => 100
            ],

            // Milestone Badges
            'point_collector' => [
                'name' => __('جامع النقاط', 'askro'),
                'description' => __('الحصول على 1000 نقطة', 'askro'),
                'icon' => '💰',
                'color' => '#F59E0B',
                'type' => 'gold',
                'criteria' => ['total_points' => 1000],
                'points' => 0
            ],
            'point_millionaire' => [
                'name' => __('مليونير النقاط', 'askro'),
                'description' => __('الحصول على 10000 نقطة', 'askro'),
                'icon' => '💎',
                'color' => '#8B5CF6',
                'type' => 'platinum',
                'criteria' => ['total_points' => 10000],
                'points' => 0
            ]
        ];

        // Allow filtering of badges
        $this->badges = apply_filters('askro_badges', $this->badges);
    }

    /**
     * Setup achievements
     *
     * @since 1.0.0
     */
    public function setup_achievements() {
        $this->achievements = [
            'first_week' => [
                'name' => __('الأسبوع الأول', 'askro'),
                'description' => __('أكمل أول أسبوع في المجتمع', 'askro'),
                'icon' => '🎉',
                'reward_points' => 50,
                'reward_badge' => 'early_adopter',
                'criteria' => [
                    'questions_count' => 1,
                    'answers_count' => 1,
                    'votes_cast' => 5
                ],
                'type' => 'milestone'
            ],
            'community_helper' => [
                'name' => __('مساعد المجتمع', 'askro'),
                'description' => __('ساعد 10 أشخاص بإجابات مقبولة', 'askro'),
                'icon' => '🤝',
                'reward_points' => 200,
                'reward_badge' => 'problem_solver',
                'criteria' => [
                    'accepted_answers' => 10
                ],
                'type' => 'contribution'
            ],
            'quality_contributor' => [
                'name' => __('مساهم عالي الجودة', 'askro'),
                'description' => __('احصل على نسبة قبول 80% في آخر 20 إجابة', 'askro'),
                'icon' => '⭐',
                'reward_points' => 300,
                'criteria' => [
                    'acceptance_ratio' => 0.8,
                    'recent_answers' => 20
                ],
                'type' => 'quality'
            ],
            'knowledge_seeker' => [
                'name' => __('باحث المعرفة', 'askro'),
                'description' => __('اطرح أسئلة في 5 تصنيفات مختلفة', 'askro'),
                'icon' => '🔍',
                'reward_points' => 100,
                'criteria' => [
                    'categories_used' => 5
                ],
                'type' => 'exploration'
            ],
            'social_connector' => [
                'name' => __('رابط اجتماعي', 'askro'),
                'description' => __('تفاعل مع 50 مستخدم مختلف', 'askro'),
                'icon' => '🌐',
                'reward_points' => 150,
                'criteria' => [
                    'unique_interactions' => 50
                ],
                'type' => 'social'
            ]
        ];

        $this->achievements = apply_filters('askro_achievements', $this->achievements);
    }

    /**
     * Check and award achievements
     *
     * @param int $user_id User ID
     * @param string $action Action performed
     * @param array $data Additional data
     * @since 1.0.0
     */
    public function check_achievements($user_id, $action, $data = []) {
        $user_stats = $this->get_user_stats($user_id);
        
        foreach ($this->badges as $badge_id => $badge) {
            if ($this->user_has_badge($user_id, $badge_id)) {
                continue;
            }
            
            if ($this->check_badge_criteria($user_stats, $badge['criteria'])) {
                $this->award_badge($user_id, $badge_id);
            }
        }
        
        foreach ($this->achievements as $achievement_id => $achievement) {
            if ($this->user_has_achievement($user_id, $achievement_id)) {
                continue;
            }
            
            if ($this->check_achievement_criteria($user_stats, $achievement['criteria'])) {
                $this->award_achievement($user_id, $achievement_id);
            }
        }
    }

    /**
     * Check badge criteria
     *
     * @param array $user_stats User statistics
     * @param array $criteria Badge criteria
     * @return bool
     * @since 1.0.0
     */
    public function check_badge_criteria($user_stats, $criteria) {
        foreach ($criteria as $key => $required_value) {
            $user_value = $user_stats[$key] ?? 0;
            
            if ($key === 'registration_date') {
                if (strtotime($user_stats['registration_date']) > strtotime($required_value)) {
                    return false;
                }
            } elseif ($user_value < $required_value) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Check achievement criteria
     *
     * @param array $user_stats User statistics
     * @param array $criteria Achievement criteria
     * @return bool
     * @since 1.0.0
     */
    public function check_achievement_criteria($user_stats, $criteria) {
        foreach ($criteria as $key => $required_value) {
            $user_value = $user_stats[$key] ?? 0;
            
            switch ($key) {
                case 'acceptance_ratio':
                    if ($user_stats['answers_count'] < $criteria['recent_answers']) {
                        return false;
                    }
                    $ratio = $user_stats['accepted_answers'] / $user_stats['answers_count'];
                    if ($ratio < $required_value) {
                        return false;
                    }
                    break;
                    
                case 'categories_used':
                    $categories = $this->get_user_categories_count($user_stats['user_id']);
                    if ($categories < $required_value) {
                        return false;
                    }
                    break;
                    
                case 'unique_interactions':
                    $interactions = $this->get_user_unique_interactions($user_stats['user_id']);
                    if ($interactions < $required_value) {
                        return false;
                    }
                    break;
                    
                default:
                    if ($user_value < $required_value) {
                        return false;
                    }
                    break;
            }
        }
        
        return true;
    }

    /**
     * Award badge to user
     *
     * @param int $user_id User ID
     * @param string $badge_id Badge ID
     * @return bool
     * @since 1.0.0
     */
    public function award_badge($user_id, $badge_id) {
        global $wpdb;
        
        if (!isset($this->badges[$badge_id])) {
            return false;
        }
        
        $badge = $this->badges[$badge_id];
        
        // Insert badge record
        $result = $wpdb->insert(
            $wpdb->prefix . 'askro_user_badges',
            [
                'user_id' => $user_id,
                'badge_id' => $badge_id,
                'earned_at' => current_time('mysql'),
                'context' => json_encode([
                    'badge_name' => $badge['name'],
                    'badge_icon' => $badge['icon'],
                    'badge_color' => $badge['color'],
                    'badge_category' => $badge['type'],
                    'points_awarded' => $badge['points']
                ])
            ],
            ['%d', '%d', '%s', '%s']
        );
        
        if ($result) {
            // Award points
            if ($badge['points'] > 0) {
                askro_award_points($user_id, $badge['points'], 'badge_earned', $badge_id);
            }
            
            // Trigger notification
            do_action('askro_badge_earned', $user_id, $badge_id, $badge);
            
            return true;
        }
        
        return false;
    }

    /**
     * Award achievement to user
     *
     * @param int $user_id User ID
     * @param string $achievement_id Achievement ID
     * @return bool
     * @since 1.0.0
     */
    public function award_achievement($user_id, $achievement_id) {
        global $wpdb;
        
        if (!isset($this->achievements[$achievement_id])) {
            return false;
        }
        
        $achievement = $this->achievements[$achievement_id];
        
        // Insert achievement record
        $result = $wpdb->insert(
            $wpdb->prefix . 'askro_user_achievements',
            [
                'user_id' => $user_id,
                'achievement_id' => $achievement_id,
                'completed_at' => current_time('mysql'),
                'progress' => json_encode([
                    'achievement_name' => $achievement['name'],
                    'achievement_icon' => $achievement['icon'],
                    'points_awarded' => $achievement['reward_points']
                ])
            ],
            ['%d', '%d', '%s', '%s']
        );
        
        if ($result) {
            // Award points
            if ($achievement['reward_points'] > 0) {
                askro_award_points($user_id, $achievement['reward_points'], 'achievement_earned', $achievement_id);
            }
            
            // Award bonus badge if specified
            if (!empty($achievement['reward_badge'])) {
                $this->award_badge($user_id, $achievement['reward_badge']);
            }
            
            // Trigger notification
            do_action('askro_achievement_earned', $user_id, $achievement_id, $achievement);
            
            return true;
        }
        
        return false;
    }

    /**
     * Check if user has badge
     *
     * @param int $user_id User ID
     * @param string $badge_id Badge ID
     * @return bool
     * @since 1.0.0
     */
    public function user_has_badge($user_id, $badge_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_user_badges 
             WHERE user_id = %d AND badge_id = %s",
            $user_id,
            $badge_id
        )) > 0;
    }

    /**
     * Check if user has achievement
     *
     * @param int $user_id User ID
     * @param string $achievement_id Achievement ID
     * @return bool
     * @since 1.0.0
     */
    public function user_has_achievement($user_id, $achievement_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_user_achievements 
             WHERE user_id = %d AND achievement_id = %s",
            $user_id,
            $achievement_id
        )) > 0;
    }

    /**
     * Get user statistics
     *
     * @param int $user_id User ID
     * @return array User statistics
     * @since 1.0.0
     */
    public function get_user_stats($user_id) {
        $user = get_userdata($user_id);
        if (!$user) {
            return [];
        }
        
        return [
            'user_id' => $user_id,
            'registration_date' => $user->user_registered,
            'questions_count' => count_user_posts($user_id, 'askro_question'),
            'answers_count' => count_user_posts($user_id, 'askro_answer'),
            'votes_cast' => $this->get_user_votes_cast($user_id),
            'upvotes_received' => $this->get_user_upvotes_received($user_id),
            'downvotes_received' => $this->get_user_downvotes_received($user_id),
            'accepted_answers' => $this->get_user_accepted_answers($user_id),
            'comments_count' => $this->get_user_comments_count($user_id),
            'followers_count' => $this->get_user_followers_count($user_id),
            'following_count' => $this->get_user_following_count($user_id),
            'total_points' => askro_get_user_points($user_id),
            'consecutive_days' => $this->get_user_consecutive_days($user_id)
        ];
    }

    /**
     * Get user votes cast count
     *
     * @param int $user_id User ID
     * @return int Votes cast count
     * @since 1.0.0
     */
    public function get_user_votes_cast($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_votes WHERE user_id = %d",
            $user_id
        ));
    }

    /**
     * Get user upvotes received count
     *
     * @param int $user_id User ID
     * @return int Upvotes received count
     * @since 1.0.0
     */
    public function get_user_upvotes_received($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_votes v
             INNER JOIN {$wpdb->posts} p ON v.post_id = p.ID
             WHERE p.post_author = %d AND v.vote_type = 'helpful' AND v.vote_value = 1",
            $user_id
        ));
    }

    /**
     * Get user downvotes received count
     *
     * @param int $user_id User ID
     * @return int Downvotes received count
     * @since 1.0.0
     */
    public function get_user_downvotes_received($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_votes v
             INNER JOIN {$wpdb->posts} p ON v.post_id = p.ID
             WHERE p.post_author = %d AND v.vote_type = 'helpful' AND v.vote_value = -1",
            $user_id
        ));
    }

    /**
     * Get user accepted answers count
     *
     * @param int $user_id User ID
     * @return int Accepted answers count
     * @since 1.0.0
     */
    public function get_user_accepted_answers($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} 
             WHERE post_author = %d AND post_type = 'askro_answer' 
             AND ID IN (
                 SELECT post_id FROM {$wpdb->postmeta} 
                 WHERE meta_key = '_askro_is_accepted' AND meta_value = '1'
             )",
            $user_id
        ));
    }

    /**
     * Get user comments count
     *
     * @param int $user_id User ID
     * @return int Comments count
     * @since 1.0.0
     */
    public function get_user_comments_count($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_comments 
             WHERE user_id = %d AND status = 'approved'",
            $user_id
        ));
    }

    /**
     * Get user followers count
     *
     * @param int $user_id User ID
     * @return int Followers count
     * @since 1.0.0
     */
    public function get_user_followers_count($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_user_follows 
             WHERE followed_user_id = %d AND status = 'active'",
            $user_id
        ));
    }

    /**
     * Get user following count
     *
     * @param int $user_id User ID
     * @return int Following count
     * @since 1.0.0
     */
    public function get_user_following_count($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_user_follows 
             WHERE follower_user_id = %d AND status = 'active'",
            $user_id
        ));
    }

    /**
     * Get user consecutive days
     *
     * @param int $user_id User ID
     * @return int Consecutive days
     * @since 1.0.0
     */
    public function get_user_consecutive_days($user_id) {
        $last_activity = get_user_meta($user_id, 'askro_last_activity', true);
        $consecutive_days = get_user_meta($user_id, 'askro_consecutive_days', true) ?: 0;
        
        if ($last_activity) {
            $last_date = date('Y-m-d', strtotime($last_activity));
            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            
            if ($last_date === $today) {
                // Already counted today
                return $consecutive_days;
            } elseif ($last_date === $yesterday) {
                // Consecutive day
                $consecutive_days++;
                update_user_meta($user_id, 'askro_consecutive_days', $consecutive_days);
            } else {
                // Streak broken
                $consecutive_days = 1;
                update_user_meta($user_id, 'askro_consecutive_days', 1);
            }
        } else {
            $consecutive_days = 1;
            update_user_meta($user_id, 'askro_consecutive_days', 1);
        }
        
        update_user_meta($user_id, 'askro_last_activity', current_time('mysql'));
        
        return $consecutive_days;
    }

    /**
     * Get user categories count
     *
     * @param int $user_id User ID
     * @return int Categories count
     * @since 1.0.0
     */
    public function get_user_categories_count($user_id) {
        global $wpdb;
        
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT tr.term_taxonomy_id)
             FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
             INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
             WHERE p.post_author = %d 
             AND p.post_type = 'askro_question' 
             AND p.post_status = 'publish'
             AND tt.taxonomy = 'askro_question_category'",
            $user_id
        ));
    }

    /**
     * Get user unique interactions count
     *
     * @param int $user_id User ID
     * @return int Unique interactions count
     * @since 1.0.0
     */
    public function get_user_unique_interactions($user_id) {
        global $wpdb;
        
        // Count unique users interacted with through votes, comments, follows
        $interactions = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT target_user_id) FROM (
                SELECT p.post_author as target_user_id 
                FROM {$wpdb->prefix}askro_votes v
                INNER JOIN {$wpdb->posts} p ON v.post_id = p.ID
                WHERE v.user_id = %d AND p.post_author != %d
                
                UNION
                
                SELECT p.post_author as target_user_id
                FROM {$wpdb->prefix}askro_comments c
                INNER JOIN {$wpdb->posts} p ON c.post_id = p.ID
                WHERE c.user_id = %d AND p.post_author != %d
                
                UNION
                
                SELECT followed_user_id as target_user_id
                FROM {$wpdb->prefix}askro_user_follows
                WHERE follower_user_id = %d
            ) as interactions",
            $user_id, $user_id, $user_id, $user_id, $user_id
        ));
        
        return $interactions ?: 0;
    }

    /**
     * Get user badges
     *
     * @param int $user_id User ID
     * @return array User badges
     * @since 1.0.0
     */
    public function get_user_badges($user_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}askro_user_badges 
             WHERE user_id = %d 
             ORDER BY earned_date DESC",
            $user_id
        ), ARRAY_A);
    }

    /**
     * Get user achievements
     *
     * @param int $user_id User ID
     * @return array User achievements
     * @since 1.0.0
     */
    public function get_user_achievements($user_id) {
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}askro_user_achievements 
             WHERE user_id = %d 
             ORDER BY earned_date DESC",
            $user_id
        ), ARRAY_A);
    }

    /**
     * Render badges showcase
     *
     * @param int $user_id User ID
     * @param array $args Arguments
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_badges_showcase($user_id, $args = []) {
        $defaults = [
            'limit' => 12,
            'show_progress' => true,
            'show_locked' => true
        ];
        
        $args = wp_parse_args($args, $defaults);
        $user_badges = $this->get_user_badges($user_id);
        $earned_badge_ids = array_column($user_badges, 'badge_id');
        
        ob_start();
        ?>
        <div class="askro-badges-showcase">
            <div class="askro-badges-grid">
                <?php
                $count = 0;
                foreach ($this->badges as $badge_id => $badge):
                    if ($count >= $args['limit']) break;
                    
                    $is_earned = in_array($badge_id, $earned_badge_ids);
                    if (!$is_earned && !$args['show_locked']) continue;
                    
                    $badge_class = $is_earned ? 'earned' : 'locked';
                    $earned_date = '';
                    
                    if ($is_earned) {
                        $earned_badge = array_filter($user_badges, function($b) use ($badge_id) {
                            return $b['badge_id'] === $badge_id;
                        });
                        $earned_badge = reset($earned_badge);
                        $earned_date = $earned_badge['earned_date'];
                    }
                    
                    $count++;
                ?>
                <div class="askro-badge-card <?php echo $badge_class; ?>" data-badge-id="<?php echo $badge_id; ?>">
                    <div class="askro-badge-icon" style="color: <?php echo $badge['color']; ?>">
                        <?php echo $badge['icon']; ?>
                    </div>
                    <div class="askro-badge-info">
                        <h4 class="askro-badge-name"><?php echo esc_html($badge['name']); ?></h4>
                        <p class="askro-badge-description"><?php echo esc_html($badge['description']); ?></p>
                        <div class="askro-badge-meta">
                            <span class="askro-badge-type askro-badge-<?php echo $badge['type']; ?>">
                                <?php echo ucfirst($badge['type']); ?>
                            </span>
                            <span class="askro-badge-points">+<?php echo $badge['points']; ?> <?php _e('نقطة', 'askro'); ?></span>
                        </div>
                        
                        <?php if ($is_earned): ?>
                        <div class="askro-badge-earned">
                            <span class="askro-earned-label">✓ <?php _e('تم الحصول عليها', 'askro'); ?></span>
                            <span class="askro-earned-date"><?php echo human_time_diff(strtotime($earned_date), current_time('timestamp')); ?> <?php _e('مضت', 'askro'); ?></span>
                        </div>
                        <?php elseif ($args['show_progress']): ?>
                        <div class="askro-badge-progress">
                            <?php echo $this->render_badge_progress($user_id, $badge_id, $badge); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Render badge progress
     *
     * @param int $user_id User ID
     * @param string $badge_id Badge ID
     * @param array $badge Badge data
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_badge_progress($user_id, $badge_id, $badge) {
        $user_stats = $this->get_user_stats($user_id);
        $progress_items = [];
        
        foreach ($badge['criteria'] as $key => $required_value) {
            $current_value = $user_stats[$key] ?? 0;
            $percentage = min(100, ($current_value / $required_value) * 100);
            
            $progress_items[] = [
                'label' => $this->get_criteria_label($key),
                'current' => $current_value,
                'required' => $required_value,
                'percentage' => $percentage
            ];
        }
        
        ob_start();
        ?>
        <div class="askro-progress-container">
            <?php foreach ($progress_items as $item): ?>
            <div class="askro-progress-item">
                <div class="askro-progress-label">
                    <?php echo $item['label']; ?>: <?php echo $item['current']; ?>/<?php echo $item['required']; ?>
                </div>
                <div class="askro-progress-bar">
                    <div class="askro-progress-fill" style="width: <?php echo $item['percentage']; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php
        
        return ob_get_clean();
    }

    /**
     * Get criteria label
     *
     * @param string $key Criteria key
     * @return string Label
     * @since 1.0.0
     */
    public function get_criteria_label($key) {
        $labels = [
            'questions_count' => __('الأسئلة', 'askro'),
            'answers_count' => __('الإجابات', 'askro'),
            'votes_cast' => __('التصويتات', 'askro'),
            'upvotes_received' => __('التصويتات الإيجابية', 'askro'),
            'accepted_answers' => __('الإجابات المقبولة', 'askro'),
            'comments_count' => __('التعليقات', 'askro'),
            'followers_count' => __('المتابعون', 'askro'),
            'following_count' => __('المتابعة', 'askro'),
            'total_points' => __('النقاط', 'askro'),
            'consecutive_days' => __('الأيام المتتالية', 'askro')
        ];
        
        return $labels[$key] ?? $key;
    }

    /**
     * Get leaderboard via AJAX
     *
     * @since 1.0.0
     */
    public function get_leaderboard() {
        $type = sanitize_text_field($_POST['type'] ?? 'points');
        $period = sanitize_text_field($_POST['period'] ?? 'all_time');
        $limit = intval($_POST['limit'] ?? 10);
        
        $leaderboard = $this->generate_leaderboard($type, $period, $limit);
        
        wp_send_json_success(['leaderboard' => $leaderboard]);
    }

    /**
     * Generate leaderboard
     *
     * @param string $type Leaderboard type
     * @param string $period Time period
     * @param int $limit Number of users
     * @return array Leaderboard data
     * @since 1.0.0
     */
    public function generate_leaderboard($type = 'points', $period = 'all_time', $limit = 10) {
        global $wpdb;
        
        $date_condition = '';
        if ($period !== 'all_time') {
            $date_map = [
                'today' => '1 DAY',
                'week' => '1 WEEK',
                'month' => '1 MONTH',
                'year' => '1 YEAR'
            ];
            
            if (isset($date_map[$period])) {
                $date_condition = "AND DATE(p.post_date) >= DATE_SUB(CURDATE(), INTERVAL {$date_map[$period]})";
            }
        }
        
        switch ($type) {
            case 'questions':
                $query = "
                    SELECT u.ID, u.display_name, COUNT(p.ID) as score
                    FROM {$wpdb->users} u
                    LEFT JOIN {$wpdb->posts} p ON u.ID = p.post_author 
                    AND p.post_type = 'askro_question' 
                    AND p.post_status = 'publish' 
                    {$date_condition}
                    GROUP BY u.ID
                    ORDER BY score DESC
                    LIMIT %d
                ";
                break;
                
            case 'answers':
                $query = "
                    SELECT u.ID, u.display_name, COUNT(p.ID) as score
                    FROM {$wpdb->users} u
                    LEFT JOIN {$wpdb->posts} p ON u.ID = p.post_author 
                    AND p.post_type = 'askro_answer' 
                    AND p.post_status = 'publish' 
                    {$date_condition}
                    GROUP BY u.ID
                    ORDER BY score DESC
                    LIMIT %d
                ";
                break;
                
            case 'badges':
                $query = "
                    SELECT u.ID, u.display_name, COUNT(b.id) as score
                    FROM {$wpdb->users} u
                    LEFT JOIN {$wpdb->prefix}askro_user_badges b ON u.ID = b.user_id
                    GROUP BY u.ID
                    ORDER BY score DESC
                    LIMIT %d
                ";
                break;
                
            default: // points
                $query = "
                    SELECT u.ID, u.display_name, COALESCE(SUM(pt.points), 0) as score
                    FROM {$wpdb->users} u
                    LEFT JOIN {$wpdb->prefix}askro_user_points pt ON u.ID = pt.user_id
                    GROUP BY u.ID
                    ORDER BY score DESC
                    LIMIT %d
                ";
                break;
        }
        
        $results = $wpdb->get_results($wpdb->prepare($query, $limit));
        
        $leaderboard = [];
        $rank = 1;
        
        foreach ($results as $result) {
            $user_badges = $this->get_user_badges($result->ID);
            $top_badge = !empty($user_badges) ? $user_badges[0] : null;
            
            $leaderboard[] = [
                'rank' => $rank,
                'user_id' => $result->ID,
                'display_name' => $result->display_name,
                'avatar_url' => get_avatar_url($result->ID, ['size' => 48]),
                'score' => $result->score,
                'top_badge' => $top_badge,
                'profile_url' => home_url("/askro-user/" . get_userdata($result->ID)->user_login . "/")
            ];
            
            $rank++;
        }
        
        return $leaderboard;
    }

    /**
     * Check question achievements
     *
     * @param int $question_id Question ID
     * @since 1.0.0
     */
    public function check_question_achievements($question_id) {
        $question = get_post($question_id);
        if (!$question) return;
        
        $this->check_achievements($question->post_author, 'question_posted', ['question_id' => $question_id]);
    }

    /**
     * Check answer achievements
     *
     * @param int $answer_id Answer ID
     * @since 1.0.0
     */
    public function check_answer_achievements($answer_id) {
        $answer = get_post($answer_id);
        if (!$answer) return;
        
        $this->check_achievements($answer->post_author, 'answer_posted', ['answer_id' => $answer_id]);
    }

    /**
     * Check vote achievements
     *
     * @param int $vote_id Vote ID
     * @since 1.0.0
     */
    public function check_vote_achievements($vote_id) {
        global $wpdb;
        
        $vote = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}askro_votes WHERE id = %d",
            $vote_id
        ));
        
        if (!$vote) return;
        
        $this->check_achievements($vote->user_id, 'vote_cast', ['vote_id' => $vote_id]);
    }

    /**
     * Check acceptance achievements
     *
     * @param int $answer_id Answer ID
     * @since 1.0.0
     */
    public function check_acceptance_achievements($answer_id) {
        $answer = get_post($answer_id);
        if (!$answer) return;
        
        $this->check_achievements($answer->post_author, 'answer_accepted', ['answer_id' => $answer_id]);
    }

    /**
     * Claim reward via AJAX
     *
     * @since 1.0.0
     */
    public function claim_reward() {
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('يجب تسجيل الدخول.', 'askro')]);
        }
        
        $reward_type = sanitize_text_field($_POST['reward_type'] ?? '');
        $reward_id = sanitize_text_field($_POST['reward_id'] ?? '');
        
        if (!$reward_type || !$reward_id) {
            wp_send_json_error(['message' => __('بيانات المكافأة غير صحيحة.', 'askro')]);
        }
        
        $user_id = get_current_user_id();
        
        // Process reward claim based on type
        $result = $this->process_reward_claim($user_id, $reward_type, $reward_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success([
            'message' => __('تم استلام المكافأة بنجاح!', 'askro'),
            'reward' => $result
        ]);
    }

    /**
     * Process reward claim
     *
     * @param int $user_id User ID
     * @param string $reward_type Reward type
     * @param string $reward_id Reward ID
     * @return array|WP_Error Reward data or error
     * @since 1.0.0
     */
    public function process_reward_claim($user_id, $reward_type, $reward_id) {
        // Implementation depends on reward system design
        // This is a placeholder for future reward claiming functionality
        
        return new WP_Error('not_implemented', __('نظام المكافآت قيد التطوير.', 'askro'));
    }
}


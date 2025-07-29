<?php
/**
 * Voting System Class
 *
 * @package    Askro
 * @subpackage Core/Voting
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
 * Askro Voting Class
 *
 * Handles the multi-dimensional voting system with advanced features
 * like karma deflection, weighted voting, and sentiment analysis.
 *
 * @since 1.0.0
 */
class Askro_Voting {

    /**
     * Initialize the voting component
     *
     * @since 1.0.0
     */
    public function init() {
        // No hooks needed here as voting is handled via AJAX
    }

    /**
     * Process a vote
     *
     * @param int $user_id User ID
     * @param array $vote_data Vote data
     * @return array Result
     * @since 1.0.0
     */
    public function process_vote($user_id, $vote_data) {
        global $wpdb;

        $post_id = $vote_data['post_id'];
        $vote_type = $vote_data['vote_type'];
        $vote_strength = $vote_data['vote_strength'];

        // Get post and target user
        $post = get_post($post_id);
        $target_user_id = $post->post_author;

        // Check for existing vote
        $existing_vote = $this->get_user_vote($user_id, $post_id, $vote_type);

        $votes_table = $wpdb->prefix . 'askro_user_votes';

        if ($existing_vote) {
            // Remove existing vote
            $wpdb->delete(
                $votes_table,
                [
                    'user_id' => $user_id,
                    'post_id' => $post_id,
                    'vote_type' => $vote_type
                ],
                ['%d', '%d', '%s']
            );

            // Reverse the points
            $this->reverse_vote_points($user_id, $target_user_id, $existing_vote);

            $message = __('تم إلغاء التصويت.', 'askro');
            $user_vote = null;
        } else {
            // Add new vote
            $vote_sentiment = $this->analyze_vote_sentiment($vote_type, $vote_strength);
            $context_score = $this->calculate_context_score($user_id, $post_id, $vote_type);

            $result = $wpdb->insert(
                $votes_table,
                [
                    'post_id' => $post_id,
                    'user_id' => $user_id,
                    'target_user_id' => $target_user_id,
                    'vote_type' => $vote_type,
                    'vote_strength' => $vote_strength,
                    'vote_sentiment' => json_encode($vote_sentiment),
                    'context_score' => $context_score,
                    'meta' => json_encode($this->get_vote_meta()),
                    'voted_at' => current_time('mysql')
                ],
                ['%d', '%d', '%d', '%s', '%d', '%s', '%f', '%s', '%s']
            );

            if (!$result) {
                return [
                    'success' => false,
                    'message' => __('حدث خطأ أثناء التصويت.', 'askro')
                ];
            }

            // Apply points
            $this->apply_vote_points($user_id, $target_user_id, $vote_type, $vote_strength, $post_id);

            $message = $this->get_vote_message($vote_type);
            $user_vote = [
                'type' => $vote_type,
                'strength' => $vote_strength
            ];
        }

        // Get updated vote counts
        $vote_counts = $this->get_post_vote_counts($post_id);
        $total_score = $this->calculate_post_score($post_id);

        // Update post meta with cached score
        update_post_meta($post_id, '_askro_vote_score', $total_score);

        // Log analytics
        askro_log_analytics('vote_cast', $user_id, 'post', $post_id, [
            'vote_type' => $vote_type,
            'vote_strength' => $vote_strength,
            'action' => $existing_vote ? 'removed' : 'added'
        ]);

        return [
            'success' => true,
            'data' => [
                'message' => $message,
                'user_vote' => $user_vote,
                'vote_counts' => $vote_counts,
                'total_score' => $total_score,
                'post_id' => $post_id
            ]
        ];
    }

    /**
     * Get user's vote on a post
     *
     * @param int $user_id User ID
     * @param int $post_id Post ID
     * @param string $vote_type Vote type
     * @return object|null Vote object
     * @since 1.0.0
     */
    public function get_user_vote($user_id, $post_id, $vote_type) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'askro_user_votes';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND post_id = %d AND vote_type = %s",
            $user_id, $post_id, $vote_type
        ));
    }

    /**
     * Get all user votes on a post
     *
     * @param int $user_id User ID
     * @param int $post_id Post ID
     * @return array Vote objects
     * @since 1.0.0
     */
    public function get_user_votes_on_post($user_id, $post_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'askro_user_votes';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE user_id = %d AND post_id = %d",
            $user_id, $post_id
        ));
    }

    /**
     * Get post vote counts by type
     *
     * @param int $post_id Post ID
     * @return array Vote counts
     * @since 1.0.0
     */
    public function get_post_vote_counts($post_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'askro_user_votes';

        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT vote_type, COUNT(*) as count, SUM(vote_strength) as total_strength
             FROM $table_name 
             WHERE post_id = %d 
             GROUP BY vote_type",
            $post_id
        ));

        $counts = [];
        foreach ($results as $result) {
            $counts[$result->vote_type] = [
                'count' => intval($result->count),
                'strength' => intval($result->total_strength)
            ];
        }

        return $counts;
    }

    /**
     * Calculate post score based on votes
     *
     * @param int $post_id Post ID
     * @return float Post score
     * @since 1.0.0
     */
    public function calculate_post_score($post_id) {
        global $wpdb;

        $votes_table = $wpdb->prefix . 'askro_user_votes';
        $weights_table = $wpdb->prefix . 'askro_vote_weights';

        $score = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(v.vote_strength * v.context_score * w.point_change_for_target) 
             FROM $votes_table v
             LEFT JOIN $weights_table w ON v.vote_type = w.vote_type AND v.vote_strength = w.vote_strength
             WHERE v.post_id = %d",
            $post_id
        ));

        return floatval($score);
    }

    /**
     * Apply vote points to users
     *
     * @param int $voter_id Voter user ID
     * @param int $target_user_id Target user ID
     * @param string $vote_type Vote type
     * @param int $vote_strength Vote strength
     * @param int $post_id Post ID
     * @since 1.0.0
     */
    private function apply_vote_points($voter_id, $target_user_id, $vote_type, $vote_strength, $post_id) {
        $weights = $this->get_vote_weights($vote_type, $vote_strength);

        if (!$weights) {
            return;
        }

        // Apply karma deflector for negative votes
        $deflected_target_points = $this->apply_karma_deflector(
            $voter_id, 
            $target_user_id, 
            $weights['point_change_for_target']
        );

        // Award points to voter
        if ($weights['point_change_for_voter'] != 0) {
            askro_add_user_points(
                $voter_id,
                $weights['point_change_for_voter'],
                'vote_cast_' . $vote_type,
                'vote',
                $post_id
            );
        }

        // Award/deduct points to target user
        if ($deflected_target_points != 0) {
            askro_add_user_points(
                $target_user_id,
                $deflected_target_points,
                'vote_received_' . $vote_type,
                'vote',
                $post_id,
                $voter_id
            );
        }
    }

    /**
     * Reverse vote points when vote is removed
     *
     * @param int $voter_id Voter user ID
     * @param int $target_user_id Target user ID
     * @param object $vote Vote object
     * @since 1.0.0
     */
    private function reverse_vote_points($voter_id, $target_user_id, $vote) {
        $weights = $this->get_vote_weights($vote->vote_type, $vote->vote_strength);

        if (!$weights) {
            return;
        }

        // Reverse voter points
        if ($weights['point_change_for_voter'] != 0) {
            askro_add_user_points(
                $voter_id,
                -$weights['point_change_for_voter'],
                'vote_removed_' . $vote->vote_type,
                'vote',
                $vote->post_id
            );
        }

        // Reverse target points (apply karma deflector in reverse)
        $deflected_target_points = $this->apply_karma_deflector(
            $voter_id, 
            $target_user_id, 
            $weights['point_change_for_target']
        );

        if ($deflected_target_points != 0) {
            askro_add_user_points(
                $target_user_id,
                -$deflected_target_points,
                'vote_removed_' . $vote->vote_type,
                'vote',
                $vote->post_id,
                $voter_id
            );
        }
    }

    /**
     * Get vote weights from database
     *
     * @param string $vote_type Vote type
     * @param int $vote_strength Vote strength
     * @return array|null Vote weights
     * @since 1.0.0
     */
    private function get_vote_weights($vote_type, $vote_strength) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'askro_vote_weights';

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE vote_type = %s AND vote_strength = %d",
            $vote_type, $vote_strength
        ), ARRAY_A);
    }

    /**
     * Apply karma deflector to reduce impact of negative votes from low-reputation users
     *
     * @param int $voter_id Voter user ID
     * @param int $target_user_id Target user ID
     * @param int $original_points Original points
     * @return int Deflected points
     * @since 1.0.0
     */
    private function apply_karma_deflector($voter_id, $target_user_id, $original_points) {
        // Only apply to negative votes
        if ($original_points >= 0) {
            return $original_points;
        }

        $voter_points = askro_get_user_points($voter_id);
        $target_points = askro_get_user_points($target_user_id);

        // Calculate deflection factor based on voter reputation
        $deflection_factor = 1.0;

        if ($voter_points < 100) {
            // New users have reduced negative impact
            $deflection_factor = 0.3;
        } elseif ($voter_points < 500) {
            // Low reputation users have reduced negative impact
            $deflection_factor = 0.6;
        } elseif ($voter_points < 1000) {
            // Medium reputation users have slightly reduced negative impact
            $deflection_factor = 0.8;
        }

        // Additional protection for high-reputation targets
        if ($target_points > 5000 && $voter_points < 1000) {
            $deflection_factor *= 0.5;
        }

        return intval($original_points * $deflection_factor);
    }

    /**
     * Analyze vote sentiment using basic NLP
     *
     * @param string $vote_type Vote type
     * @param int $vote_strength Vote strength
     * @return array Sentiment data
     * @since 1.0.0
     */
    private function analyze_vote_sentiment($vote_type, $vote_strength) {
        $positive_types = ['useful', 'creative', 'deep', 'funny', 'emotional'];
        $negative_types = ['toxic', 'offtopic', 'inaccurate', 'spam', 'duplicate'];

        $sentiment = 'neutral';
        $confidence = 0.5;

        if (in_array($vote_type, $positive_types)) {
            $sentiment = 'positive';
            $confidence = 0.7 + (abs($vote_strength) * 0.1);
        } elseif (in_array($vote_type, $negative_types)) {
            $sentiment = 'negative';
            $confidence = 0.7 + (abs($vote_strength) * 0.1);
        }

        return [
            'sentiment' => $sentiment,
            'confidence' => min(1.0, $confidence),
            'analyzed_at' => current_time('mysql')
        ];
    }

    /**
     * Calculate context score for vote weighting
     *
     * @param int $user_id User ID
     * @param int $post_id Post ID
     * @param string $vote_type Vote type
     * @return float Context score
     * @since 1.0.0
     */
    private function calculate_context_score($user_id, $post_id, $vote_type) {
        $base_score = 1.0;
        $user_points = askro_get_user_points($user_id);

        // Reputation-based weighting
        if ($user_points > 10000) {
            $reputation_multiplier = 1.5;
        } elseif ($user_points > 5000) {
            $reputation_multiplier = 1.3;
        } elseif ($user_points > 1000) {
            $reputation_multiplier = 1.1;
        } else {
            $reputation_multiplier = 1.0;
        }

        // Category expertise weighting (to be implemented)
        $expertise_multiplier = 1.0;

        // Time-based weighting (early votes have slightly more weight)
        $post = get_post($post_id);
        $post_age_hours = (time() - strtotime($post->post_date)) / 3600;
        $time_multiplier = $post_age_hours < 24 ? 1.1 : 1.0;

        return $base_score * $reputation_multiplier * $expertise_multiplier * $time_multiplier;
    }

    /**
     * Get vote metadata
     *
     * @return array Vote metadata
     * @since 1.0.0
     */
    private function get_vote_meta() {
        return [
            'ip_hash' => hash('sha256', $_SERVER['REMOTE_ADDR'] ?? ''),
            'user_agent_hash' => hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? ''),
            'timestamp' => time()
        ];
    }

    /**
     * Get vote message based on type
     *
     * @param string $vote_type Vote type
     * @return string Message
     * @since 1.0.0
     */
    private function get_vote_message($vote_type) {
        $messages = [
            'useful' => __('تم التصويت كمفيد.', 'askro'),
            'creative' => __('تم التصويت كإبداعي.', 'askro'),
            'deep' => __('تم التصويت كعميق.', 'askro'),
            'funny' => __('تم التصويت كمضحك.', 'askro'),
            'emotional' => __('تم التصويت كمؤثر.', 'askro'),
            'inaccurate' => __('تم التصويت كغير دقيق.', 'askro'),
            'offtopic' => __('تم التصويت كخارج الموضوع.', 'askro'),
            'toxic' => __('تم التصويت كسام.', 'askro'),
            'spam' => __('تم التصويت كسبام.', 'askro'),
            'duplicate' => __('تم التصويت كمكرر.', 'askro')
        ];

        return $messages[$vote_type] ?? __('تم التصويت.', 'askro');
    }

    /**
     * Get vote reason presets
     *
     * @return array Vote reason presets
     * @since 1.0.0
     */
    public function get_vote_reason_presets() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'askro_vote_reason_presets';

        return $wpdb->get_results(
            "SELECT * FROM $table_name WHERE is_active = 1 ORDER BY sort_order ASC"
        );
    }

    /**
     * Get user's voting statistics
     *
     * @param int $user_id User ID
     * @return array Voting statistics
     * @since 1.0.0
     */
    public function get_user_voting_stats($user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'askro_user_votes';

        $stats = $wpdb->get_results($wpdb->prepare(
            "SELECT vote_type, COUNT(*) as count, SUM(vote_strength) as total_strength
             FROM $table_name 
             WHERE user_id = %d 
             GROUP BY vote_type",
            $user_id
        ));

        $voting_stats = [
            'total_votes' => 0,
            'by_type' => []
        ];

        foreach ($stats as $stat) {
            $voting_stats['by_type'][$stat->vote_type] = [
                'count' => intval($stat->count),
                'strength' => intval($stat->total_strength)
            ];
            $voting_stats['total_votes'] += intval($stat->count);
        }

        return $voting_stats;
    }

    /**
     * Get votes received by user
     *
     * @param int $user_id User ID
     * @return array Votes received statistics
     * @since 1.0.0
     */
    public function get_user_votes_received($user_id) {
        global $wpdb;

        $table_name = $wpdb->prefix . 'askro_user_votes';

        $stats = $wpdb->get_results($wpdb->prepare(
            "SELECT vote_type, COUNT(*) as count, SUM(vote_strength) as total_strength
             FROM $table_name 
             WHERE target_user_id = %d 
             GROUP BY vote_type",
            $user_id
        ));

        $received_stats = [
            'total_received' => 0,
            'by_type' => []
        ];

        foreach ($stats as $stat) {
            $received_stats['by_type'][$stat->vote_type] = [
                'count' => intval($stat->count),
                'strength' => intval($stat->total_strength)
            ];
            $received_stats['total_received'] += intval($stat->count);
        }

        return $received_stats;
    }
}


<?php
/**
 * Voting Functions
 *
 * @package    Askro
 * @subpackage Functions
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get post vote count (multi-dimensional voting)
 *
 * @param int $post_id Post ID
 * @param string $vote_type Optional vote type filter
 * @return int
 * @since 1.0.0
 */
function askro_get_vote_count($post_id, $vote_type = '') {
    if (!$post_id) {
        return 0;
    }
    
    // Check cache first
    $cache_key = 'askro_vote_count_' . $post_id . '_' . $vote_type;
    $cached_count = wp_cache_get($cache_key, 'askro');
    
    if ($cached_count !== false) {
        return $cached_count;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'askro_user_votes';
    
    if ($vote_type) {
        // Get count for specific vote type
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d AND vote_type = %s",
            $post_id, $vote_type
        ));
    } else {
        // Get total vote count from database
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d",
            $post_id
        ));
    }
    
    $result = intval($count) ?: 0;
    
    // Cache for 10 minutes
    wp_cache_set($cache_key, $result, 'askro', 600);
    
    return $result;
}

/**
 * Get positive vote count (useful, innovative, well_researched)
 *
 * @param int $post_id Post ID
 * @return int
 * @since 1.0.0
 */
function askro_get_positive_vote_count($post_id) {
    if (!$post_id) {
        return 0;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'askro_user_votes';
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name} 
         WHERE post_id = %d AND vote_type IN ('useful', 'innovative', 'well_researched')",
        $post_id
    ));
    
    return intval($count) ?: 0;
}

/**
 * Get negative vote count (incorrect, redundant)
 *
 * @param int $post_id Post ID
 * @return int
 * @since 1.0.0
 */
function askro_get_negative_vote_count($post_id) {
    if (!$post_id) {
        return 0;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'askro_user_votes';
    
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$table_name} 
         WHERE post_id = %d AND vote_type IN ('incorrect', 'redundant')",
        $post_id
    ));
    
    return intval($count) ?: 0;
}

/**
 * Cast a multi-dimensional vote
 *
 * @param int $post_id Post ID
 * @param string $vote_type Vote type (useful, innovative, well_researched, incorrect, redundant)
 * @param int $vote_value Vote value (positive or negative)
 * @param int $user_id User ID
 * @return bool|WP_Error
 * @since 1.0.0
 */
function askro_cast_vote($post_id, $vote_type, $vote_value = 1, $user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id || !$post_id) {
        return new WP_Error('invalid_params', __('Invalid parameters', 'askro'));
    }
    
    // Validate vote type
    $valid_vote_types = ['useful', 'innovative', 'well_researched', 'incorrect', 'redundant'];
    if (!in_array($vote_type, $valid_vote_types)) {
        return new WP_Error('invalid_vote_type', __('Invalid vote type', 'askro'));
    }
    
    // Check if user can vote
    if (!askro_user_can_vote($user_id)) {
        return new WP_Error('cannot_vote', __('You do not have permission to vote', 'askro'));
    }
    
    // Get current vote for this type
    $current_vote = askro_get_user_vote($user_id, $post_id, $vote_type);
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'askro_user_votes';
    
    // If same vote, remove it (toggle)
    if ($current_vote && $current_vote->vote_type === $vote_type) {
        $wpdb->delete($table_name, [
            'post_id' => $post_id,
            'user_id' => $user_id,
            'vote_type' => $vote_type
        ]);
        
        // Update post meta
        askro_update_vote_counts($post_id);
        
        return true;
    }
    
    // Insert or update vote
    if ($current_vote) {
        $wpdb->update(
            $table_name,
            [
                'vote_type' => $vote_type,
                'vote_value' => $vote_value,
                'vote_date' => current_time('mysql')
            ],
            [
                'post_id' => $post_id,
                'user_id' => $user_id,
                'vote_type' => $current_vote->vote_type
            ]
        );
    } else {
        $wpdb->insert($table_name, [
            'post_id' => $post_id,
            'user_id' => $user_id,
            'vote_type' => $vote_type,
            'vote_value' => $vote_value,
            'vote_date' => current_time('mysql')
        ]);
    }
    
    // Update post meta
    askro_update_vote_counts($post_id);
    
    // Update user reputation if voting on someone else's post
    $post = get_post($post_id);
    if ($post && $post->post_author != $user_id) {
        $reputation_change = ($vote_value > 0) ? 5 : -1;
        askro_update_user_reputation($post->post_author, $reputation_change);
    }
    
    return true;
}

/**
 * Update vote counts for a post (multi-dimensional voting)
 *
 * @param int $post_id Post ID
 * @since 1.0.0
 */
function askro_update_vote_counts($post_id) {
    if (!$post_id) {
        return;
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'askro_user_votes';
    
    // Get vote counts by type
    $vote_counts = $wpdb->get_results($wpdb->prepare(
        "SELECT vote_type, COUNT(*) as count, SUM(vote_value) as total_value 
         FROM {$table_name} 
         WHERE post_id = %d 
         GROUP BY vote_type",
        $post_id
    ));
    
    $vote_data = [];
    $total_score = 0;
    
    foreach ($vote_counts as $vote) {
        $vote_data[$vote->vote_type] = [
            'count' => intval($vote->count),
            'total_value' => intval($vote->total_value)
        ];
        $total_score += intval($vote->total_value);
    }
    
    // Update post meta with vote data
    update_post_meta($post_id, 'askro_vote_data', $vote_data);
    update_post_meta($post_id, 'askro_vote_score', $total_score);
    update_post_meta($post_id, 'askro_total_votes', array_sum(array_column($vote_data, 'count')));
}

/**
 * Check if user can vote
 *
 * @param int $user_id User ID
 * @return bool
 * @since 1.0.0
 */
function askro_user_can_vote($user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    if (!$user_id) {
        return false;
    }
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return false;
    }
    
    // Check user capabilities
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Check reputation requirements
    $min_reputation = get_option('askro_min_reputation_vote', 15);
    if ($min_reputation > 0 && askro_get_user_reputation($user_id) < $min_reputation) {
        return false;
    }
    
    return true;
}

/**
 * Get multi-dimensional voting HTML for a post
 *
 * @param int $post_id Post ID
 * @return string
 * @since 1.0.0
 */
function askro_get_voting_html($post_id) {
    if (!$post_id) {
        return '';
    }
    
    $user_id = get_current_user_id();
    $vote_score = askro_get_vote_score($post_id);
    $can_vote = askro_user_can_vote($user_id);
    $vote_types = askro_get_vote_types();
    
    $disabled = $can_vote ? '' : 'disabled';
    
    ob_start();
    ?>
    <div class="askme-vote-section" data-post-id="<?php echo esc_attr($post_id); ?>">
        <?php foreach ($vote_types as $type => $vote_data): ?>
            <?php
            $user_vote = askro_get_user_vote($user_id, $post_id, $type);
            $vote_count = askro_get_vote_count($post_id, $type);
            $voted_class = ($user_vote && $user_vote->vote_type === $type) ? 'voted' : '';
            ?>
            <button class="askme-vote-btn <?php echo esc_attr($voted_class . ' ' . $disabled); ?>" 
                    data-post-id="<?php echo esc_attr($post_id); ?>"
                    data-vote-type="<?php echo esc_attr($type); ?>"
                    data-vote-value="<?php echo esc_attr($vote_data['value']); ?>"
                    <?php echo $disabled; ?>
                    title="<?php echo esc_attr($vote_data['label']); ?>">
                <span class="askme-vote-icon"><?php echo $vote_data['icon']; ?></span>
                <span class="askme-vote-count"><?php echo esc_html($vote_count); ?></span>
            </button>
        <?php endforeach; ?>
        
        <div class="askme-total-score">
            <span class="askme-score-label">المجموع:</span>
            <span class="askme-score-value"><?php echo esc_html($vote_score); ?></span>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

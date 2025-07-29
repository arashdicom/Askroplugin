<?php
/**
 * Leaderboard Class
 *
 * @package    Askro
 * @subpackage Core/Leaderboard
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
 * Askro Leaderboard Class
 *
 * Handles leaderboards and ranking system
 *
 * @since 1.0.0
 */
class Askro_Leaderboard {

    /**
     * Initialize the leaderboard component
     *
     * @since 1.0.0
     */
    public function init() {
        add_action('wp_ajax_askro_get_leaderboard_data', [$this, 'get_leaderboard_data']);
        add_action('wp_ajax_nopriv_askro_get_leaderboard_data', [$this, 'get_leaderboard_data']);
        
        add_shortcode('askro_leaderboard', [$this, 'leaderboard_shortcode']);
    }

    /**
     * Render leaderboard
     *
     * @param array $args Arguments
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_leaderboard($args = []) {
        $defaults = [
            'type' => 'points',
            'period' => 'all_time',
            'limit' => 20,
            'show_filters' => true,
            'show_user_rank' => true,
            'show_avatars' => true,
            'show_badges' => true
        ];

        $args = wp_parse_args($args, $defaults);

        ob_start();
        ?>
        <div class="askro-leaderboard-container" data-type="<?php echo esc_attr($args['type']); ?>" data-period="<?php echo esc_attr($args['period']); ?>">
            <?php if ($args['show_filters']): ?>
            <!-- Leaderboard Filters -->
            <div class="askro-leaderboard-filters">
                <div class="askro-filter-group">
                    <label for="leaderboard-type" class="askro-filter-label"><?php _e('نوع التصنيف:', 'askro'); ?></label>
                    <select id="leaderboard-type" class="askro-select">
                        <option value="points" <?php selected($args['type'], 'points'); ?>><?php _e('النقاط', 'askro'); ?></option>
                        <option value="questions" <?php selected($args['type'], 'questions'); ?>><?php _e('الأسئلة', 'askro'); ?></option>
                        <option value="answers" <?php selected($args['type'], 'answers'); ?>><?php _e('الإجابات', 'askro'); ?></option>
                        <option value="accepted_answers" <?php selected($args['type'], 'accepted_answers'); ?>><?php _e('الإجابات المقبولة', 'askro'); ?></option>
                        <option value="votes_received" <?php selected($args['type'], 'votes_received'); ?>><?php _e('التصويتات المستلمة', 'askro'); ?></option>
                        <option value="badges" <?php selected($args['type'], 'badges'); ?>><?php _e('الشارات', 'askro'); ?></option>
                        <option value="reputation" <?php selected($args['type'], 'reputation'); ?>><?php _e('السمعة', 'askro'); ?></option>
                    </select>
                </div>

                <div class="askro-filter-group">
                    <label for="leaderboard-period" class="askro-filter-label"><?php _e('الفترة الزمنية:', 'askro'); ?></label>
                    <select id="leaderboard-period" class="askro-select">
                        <option value="all_time" <?php selected($args['period'], 'all_time'); ?>><?php _e('كل الأوقات', 'askro'); ?></option>
                        <option value="today" <?php selected($args['period'], 'today'); ?>><?php _e('اليوم', 'askro'); ?></option>
                        <option value="week" <?php selected($args['period'], 'week'); ?>><?php _e('هذا الأسبوع', 'askro'); ?></option>
                        <option value="month" <?php selected($args['period'], 'month'); ?>><?php _e('هذا الشهر', 'askro'); ?></option>
                        <option value="year" <?php selected($args['period'], 'year'); ?>><?php _e('هذا العام', 'askro'); ?></option>
                    </select>
                </div>

                <button type="button" class="askro-btn-primary askro-update-leaderboard">
                    🔄 <?php _e('تحديث', 'askro'); ?>
                </button>
            </div>
            <?php endif; ?>

            <!-- Leaderboard Header -->
            <div class="askro-leaderboard-header">
                <h2 class="askro-leaderboard-title">
                    <?php echo $this->get_leaderboard_title($args['type'], $args['period']); ?>
                </h2>
                <div class="askro-leaderboard-stats">
                    <span class="askro-total-participants" id="total-participants">
                        <?php _e('جاري التحميل...', 'askro'); ?>
                    </span>
                </div>
            </div>

            <!-- Current User Rank (if logged in) -->
            <?php if ($args['show_user_rank'] && is_user_logged_in()): ?>
            <div class="askro-user-rank-card" id="user-rank-card">
                <div class="askro-loading-placeholder">
                    <div class="askro-spinner"></div>
                    <?php _e('جاري تحميل ترتيبك...', 'askro'); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Top 3 Podium -->
            <div class="askro-leaderboard-podium" id="leaderboard-podium">
                <div class="askro-loading-placeholder">
                    <div class="askro-spinner"></div>
                    <?php _e('جاري تحميل المتصدرين...', 'askro'); ?>
                </div>
            </div>

            <!-- Full Leaderboard -->
            <div class="askro-leaderboard-list" id="leaderboard-list">
                <div class="askro-loading-placeholder">
                    <div class="askro-spinner"></div>
                    <?php _e('جاري تحميل القائمة...', 'askro'); ?>
                </div>
            </div>

            <!-- Load More Button -->
            <div class="askro-leaderboard-actions">
                <button type="button" class="askro-btn-outline askro-load-more-leaderboard" style="display: none;">
                    <?php _e('عرض المزيد', 'askro'); ?>
                </button>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const leaderboardContainer = document.querySelector('.askro-leaderboard-container');
            if (!leaderboardContainer) return;

            let currentPage = 1;
            const limit = <?php echo intval($args['limit']); ?>;

            // Load initial leaderboard
            loadLeaderboard();

            // Filter change handlers
            document.getElementById('leaderboard-type')?.addEventListener('change', function() {
                currentPage = 1;
                loadLeaderboard();
            });

            document.getElementById('leaderboard-period')?.addEventListener('change', function() {
                currentPage = 1;
                loadLeaderboard();
            });

            // Update button
            document.querySelector('.askro-update-leaderboard')?.addEventListener('click', function() {
                currentPage = 1;
                loadLeaderboard();
            });

            // Load more button
            document.querySelector('.askro-load-more-leaderboard')?.addEventListener('click', function() {
                currentPage++;
                loadLeaderboard(true);
            });

            function loadLeaderboard(append = false) {
                const type = document.getElementById('leaderboard-type')?.value || '<?php echo esc_js($args['type']); ?>';
                const period = document.getElementById('leaderboard-period')?.value || '<?php echo esc_js($args['period']); ?>';

                const data = {
                    action: 'askro_get_leaderboard_data',
                    type: type,
                    period: period,
                    page: currentPage,
                    limit: limit,
                    nonce: askroData.nonce
                };

                fetch(askroData.ajax_url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(data)
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        updateLeaderboard(result.data, append);
                    } else {
                        console.error('Leaderboard error:', result.data);
                    }
                })
                .catch(error => {
                    console.error('Leaderboard fetch error:', error);
                });
            }

            function updateLeaderboard(data, append) {
                // Update stats
                document.getElementById('total-participants').textContent = 
                    data.total_participants + ' <?php _e('مشارك', 'askro'); ?>';

                // Update user rank
                const userRankCard = document.getElementById('user-rank-card');
                if (userRankCard && data.user_rank) {
                    userRankCard.innerHTML = renderUserRankCard(data.user_rank);
                }

                // Update podium (top 3)
                if (!append && data.leaderboard.length > 0) {
                    const podium = document.getElementById('leaderboard-podium');
                    podium.innerHTML = renderPodium(data.leaderboard.slice(0, 3));
                }

                // Update list
                const list = document.getElementById('leaderboard-list');
                const startIndex = append ? 0 : 3; // Skip top 3 if not appending
                const listData = data.leaderboard.slice(startIndex);

                if (append) {
                    list.insertAdjacentHTML('beforeend', renderLeaderboardList(listData));
                } else {
                    list.innerHTML = renderLeaderboardList(listData);
                }

                // Update load more button
                const loadMoreBtn = document.querySelector('.askro-load-more-leaderboard');
                if (loadMoreBtn) {
                    if (data.has_more) {
                        loadMoreBtn.style.display = 'block';
                    } else {
                        loadMoreBtn.style.display = 'none';
                    }
                }
            }

            function renderUserRankCard(userRank) {
                return `
                    <div class="askro-user-rank-content">
                        <div class="askro-rank-info">
                            <span class="askro-rank-label"><?php _e('ترتيبك:', 'askro'); ?></span>
                            <span class="askro-rank-number">#${userRank.rank}</span>
                        </div>
                        <div class="askro-rank-score">
                            <span class="askro-score-value">${userRank.score}</span>
                            <span class="askro-score-label">${getScoreLabel()}</span>
                        </div>
                        <div class="askro-rank-progress">
                            ${userRank.next_rank ? `
                                <span class="askro-progress-text">
                                    ${userRank.points_to_next} <?php _e('نقطة للترتيب التالي', 'askro'); ?>
                                </span>
                                <div class="askro-progress-bar">
                                    <div class="askro-progress-fill" style="width: ${userRank.progress_percentage}%"></div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            }

            function renderPodium(topThree) {
                if (topThree.length === 0) return '<p class="askro-no-data"><?php _e('لا توجد بيانات', 'askro'); ?></p>';

                let podiumHtml = '<div class="askro-podium-container">';
                
                // Arrange as 2nd, 1st, 3rd
                const arrangement = [topThree[1], topThree[0], topThree[2]].filter(Boolean);
                const positions = ['second', 'first', 'third'];
                
                arrangement.forEach((user, index) => {
                    if (!user) return;
                    
                    const position = positions[index];
                    const medal = index === 1 ? '🥇' : index === 0 ? '🥈' : '🥉';
                    
                    podiumHtml += `
                        <div class="askro-podium-item askro-podium-${position}">
                            <div class="askro-podium-rank">${medal}</div>
                            <div class="askro-podium-avatar">
                                <img src="${user.avatar_url}" alt="${user.display_name}">
                                ${user.top_badge ? `<div class="askro-podium-badge" style="background: ${user.top_badge.badge_color}">${user.top_badge.badge_icon}</div>` : ''}
                            </div>
                            <div class="askro-podium-info">
                                <h4 class="askro-podium-name">${user.display_name}</h4>
                                <div class="askro-podium-score">${user.score} ${getScoreLabel()}</div>
                            </div>
                        </div>
                    `;
                });
                
                podiumHtml += '</div>';
                return podiumHtml;
            }

            function renderLeaderboardList(users) {
                if (users.length === 0) return '<p class="askro-no-data"><?php _e('لا توجد بيانات إضافية', 'askro'); ?></p>';

                let listHtml = '<div class="askro-leaderboard-items">';
                
                users.forEach(user => {
                    listHtml += `
                        <div class="askro-leaderboard-item" data-user-id="${user.user_id}">
                            <div class="askro-rank-number">#${user.rank}</div>
                            <div class="askro-user-avatar">
                                <img src="${user.avatar_url}" alt="${user.display_name}">
                            </div>
                            <div class="askro-user-info">
                                <h4 class="askro-user-name">
                                    <a href="${user.profile_url}">${user.display_name}</a>
                                </h4>
                                <div class="askro-user-meta">
                                    <span class="askro-user-score">${user.score} ${getScoreLabel()}</span>
                                    ${user.top_badge ? `
                                        <span class="askro-user-badge" style="color: ${user.top_badge.badge_color}">
                                            ${user.top_badge.badge_icon} ${user.top_badge.badge_name}
                                        </span>
                                    ` : ''}
                                </div>
                            </div>
                            <div class="askro-user-stats">
                                <div class="askro-stat-item">
                                    <span class="askro-stat-value">${user.questions_count || 0}</span>
                                    <span class="askro-stat-label"><?php _e('سؤال', 'askro'); ?></span>
                                </div>
                                <div class="askro-stat-item">
                                    <span class="askro-stat-value">${user.answers_count || 0}</span>
                                    <span class="askro-stat-label"><?php _e('إجابة', 'askro'); ?></span>
                                </div>
                                <div class="askro-stat-item">
                                    <span class="askro-stat-value">${user.badges_count || 0}</span>
                                    <span class="askro-stat-label"><?php _e('شارة', 'askro'); ?></span>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                listHtml += '</div>';
                return listHtml;
            }

            function getScoreLabel() {
                const type = document.getElementById('leaderboard-type')?.value || '<?php echo esc_js($args['type']); ?>';
                const labels = {
                    'points': '<?php _e('نقطة', 'askro'); ?>',
                    'questions': '<?php _e('سؤال', 'askro'); ?>',
                    'answers': '<?php _e('إجابة', 'askro'); ?>',
                    'accepted_answers': '<?php _e('إجابة مقبولة', 'askro'); ?>',
                    'votes_received': '<?php _e('تصويت', 'askro'); ?>',
                    'badges': '<?php _e('شارة', 'askro'); ?>',
                    'reputation': '<?php _e('نقطة سمعة', 'askro'); ?>'
                };
                return labels[type] || '<?php _e('نقطة', 'askro'); ?>';
            }
        });
        </script>
        <?php

        return ob_get_clean();
    }

    /**
     * Get leaderboard title
     *
     * @param string $type Leaderboard type
     * @param string $period Time period
     * @return string Title
     * @since 1.0.0
     */
    public function get_leaderboard_title($type, $period) {
        $type_labels = [
            'points' => __('متصدرو النقاط', 'askro'),
            'questions' => __('متصدرو الأسئلة', 'askro'),
            'answers' => __('متصدرو الإجابات', 'askro'),
            'accepted_answers' => __('متصدرو الإجابات المقبولة', 'askro'),
            'votes_received' => __('متصدرو التصويتات', 'askro'),
            'badges' => __('متصدرو الشارات', 'askro'),
            'reputation' => __('متصدرو السمعة', 'askro')
        ];

        $period_labels = [
            'all_time' => __('كل الأوقات', 'askro'),
            'today' => __('اليوم', 'askro'),
            'week' => __('هذا الأسبوع', 'askro'),
            'month' => __('هذا الشهر', 'askro'),
            'year' => __('هذا العام', 'askro')
        ];

        $type_label = $type_labels[$type] ?? $type_labels['points'];
        $period_label = $period_labels[$period] ?? '';

        if ($period === 'all_time') {
            return $type_label;
        }

        return $type_label . ' - ' . $period_label;
    }

    /**
     * Get leaderboard data via AJAX
     *
     * @since 1.0.0
     */
    public function get_leaderboard_data() {
        $type = sanitize_text_field($_POST['type'] ?? 'points');
        $period = sanitize_text_field($_POST['period'] ?? 'all_time');
        $page = intval($_POST['page'] ?? 1);
        $limit = intval($_POST['limit'] ?? 20);

        $leaderboard_data = $this->generate_leaderboard_data($type, $period, $page, $limit);

        wp_send_json_success($leaderboard_data);
    }

    /**
     * Generate leaderboard data
     *
     * @param string $type Leaderboard type
     * @param string $period Time period
     * @param int $page Page number
     * @param int $limit Items per page
     * @return array Leaderboard data
     * @since 1.0.0
     */
    public function generate_leaderboard_data($type, $period, $page = 1, $limit = 20) {
        global $wpdb;

        $offset = ($page - 1) * $limit;
        $date_condition = $this->get_date_condition($period);

        // Get total participants
        $total_participants = $this->get_total_participants($type, $period);

        // Get leaderboard
        $leaderboard = $this->get_leaderboard_query($type, $period, $limit, $offset);

        // Get current user rank if logged in
        $user_rank = null;
        if (is_user_logged_in()) {
            $user_rank = $this->get_user_rank(get_current_user_id(), $type, $period);
        }

        return [
            'leaderboard' => $leaderboard,
            'total_participants' => $total_participants,
            'user_rank' => $user_rank,
            'has_more' => count($leaderboard) === $limit,
            'current_page' => $page
        ];
    }

    /**
     * Get date condition for SQL queries
     *
     * @param string $period Time period
     * @return string SQL condition
     * @since 1.0.0
     */
    public function get_date_condition($period) {
        switch ($period) {
            case 'today':
                return "AND DATE(created_date) = CURDATE()";
            case 'week':
                return "AND created_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            case 'month':
                return "AND created_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            case 'year':
                return "AND created_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            default:
                return "";
        }
    }

    /**
     * Get total participants count
     *
     * @param string $type Leaderboard type
     * @param string $period Time period
     * @return int Total participants
     * @since 1.0.0
     */
    public function get_total_participants($type, $period) {
        global $wpdb;

        $date_condition = $this->get_date_condition($period);

        switch ($type) {
            case 'questions':
                return $wpdb->get_var("
                    SELECT COUNT(DISTINCT post_author) 
                    FROM {$wpdb->posts} 
                    WHERE post_type = 'askro_question' 
                    AND post_status = 'publish' 
                    {$date_condition}
                ");

            case 'answers':
                return $wpdb->get_var("
                    SELECT COUNT(DISTINCT post_author) 
                    FROM {$wpdb->posts} 
                    WHERE post_type = 'askro_answer' 
                    AND post_status = 'publish' 
                    {$date_condition}
                ");

            case 'badges':
                return $wpdb->get_var("
                    SELECT COUNT(DISTINCT user_id) 
                    FROM {$wpdb->prefix}askro_user_badges
                    WHERE 1=1 {$date_condition}
                ");

            default: // points
                return $wpdb->get_var("
                    SELECT COUNT(DISTINCT user_id) 
                    FROM {$wpdb->prefix}askro_user_points
                    WHERE points > 0 {$date_condition}
                ");
        }
    }

    /**
     * Get leaderboard query results
     *
     * @param string $type Leaderboard type
     * @param string $period Time period
     * @param int $limit Limit
     * @param int $offset Offset
     * @return array Leaderboard results
     * @since 1.0.0
     */
    public function get_leaderboard_query($type, $period, $limit, $offset) {
        global $wpdb;

        $date_condition = $this->get_date_condition($period);

        switch ($type) {
            case 'questions':
                $query = "
                    SELECT u.ID as user_id, u.display_name, u.user_login, 
                           COUNT(p.ID) as score,
                           ROW_NUMBER() OVER (ORDER BY COUNT(p.ID) DESC) as rank
                    FROM {$wpdb->users} u
                    LEFT JOIN {$wpdb->posts} p ON u.ID = p.post_author 
                    AND p.post_type = 'askro_question' 
                    AND p.post_status = 'publish' 
                    {$date_condition}
                    GROUP BY u.ID
                    HAVING score > 0
                    ORDER BY score DESC
                    LIMIT %d OFFSET %d
                ";
                break;

            case 'answers':
                $query = "
                    SELECT u.ID as user_id, u.display_name, u.user_login,
                           COUNT(p.ID) as score,
                           ROW_NUMBER() OVER (ORDER BY COUNT(p.ID) DESC) as rank
                    FROM {$wpdb->users} u
                    LEFT JOIN {$wpdb->posts} p ON u.ID = p.post_author 
                    AND p.post_type = 'askro_answer' 
                    AND p.post_status = 'publish' 
                    {$date_condition}
                    GROUP BY u.ID
                    HAVING score > 0
                    ORDER BY score DESC
                    LIMIT %d OFFSET %d
                ";
                break;

            case 'accepted_answers':
                $query = "
                    SELECT u.ID as user_id, u.display_name, u.user_login,
                           COUNT(p.ID) as score,
                           ROW_NUMBER() OVER (ORDER BY COUNT(p.ID) DESC) as rank
                    FROM {$wpdb->users} u
                    LEFT JOIN {$wpdb->posts} p ON u.ID = p.post_author 
                    AND p.post_type = 'askro_answer' 
                    AND p.post_status = 'publish'
                    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
                    AND pm.meta_key = '_askro_is_accepted' 
                    AND pm.meta_value = '1'
                    WHERE pm.post_id IS NOT NULL {$date_condition}
                    GROUP BY u.ID
                    ORDER BY score DESC
                    LIMIT %d OFFSET %d
                ";
                break;

            case 'votes_received':
                $query = "
                    SELECT u.ID as user_id, u.display_name, u.user_login,
                           COUNT(v.id) as score,
                           ROW_NUMBER() OVER (ORDER BY COUNT(v.id) DESC) as rank
                    FROM {$wpdb->users} u
                    LEFT JOIN {$wpdb->posts} p ON u.ID = p.post_author
                    LEFT JOIN {$wpdb->prefix}askro_votes v ON p.ID = v.post_id 
                    AND v.vote_value = 1 {$date_condition}
                    GROUP BY u.ID
                    HAVING score > 0
                    ORDER BY score DESC
                    LIMIT %d OFFSET %d
                ";
                break;

            case 'badges':
                $query = "
                    SELECT u.ID as user_id, u.display_name, u.user_login,
                           COUNT(b.id) as score,
                           ROW_NUMBER() OVER (ORDER BY COUNT(b.id) DESC) as rank
                    FROM {$wpdb->users} u
                    LEFT JOIN {$wpdb->prefix}askro_user_badges b ON u.ID = b.user_id
                    {$date_condition}
                    GROUP BY u.ID
                    HAVING score > 0
                    ORDER BY score DESC
                    LIMIT %d OFFSET %d
                ";
                break;

            case 'reputation':
                $query = "
                    SELECT u.ID as user_id, u.display_name, u.user_login,
                           COALESCE(SUM(pt.points), 0) as score,
                           ROW_NUMBER() OVER (ORDER BY COALESCE(SUM(pt.points), 0) DESC) as rank
                    FROM {$wpdb->users} u
                    LEFT JOIN {$wpdb->prefix}askro_user_points pt ON u.ID = pt.user_id
                    {$date_condition}
                    GROUP BY u.ID
                    HAVING score > 0
                    ORDER BY score DESC
                    LIMIT %d OFFSET %d
                ";
                break;

            default: // points
                $query = "
                    SELECT u.ID as user_id, u.display_name, u.user_login,
                           COALESCE(SUM(pt.points), 0) as score,
                           ROW_NUMBER() OVER (ORDER BY COALESCE(SUM(pt.points), 0) DESC) as rank
                    FROM {$wpdb->users} u
                    LEFT JOIN {$wpdb->prefix}askro_user_points pt ON u.ID = pt.user_id
                    {$date_condition}
                    GROUP BY u.ID
                    HAVING score > 0
                    ORDER BY score DESC
                    LIMIT %d OFFSET %d
                ";
                break;
        }

        $results = $wpdb->get_results($wpdb->prepare($query, $limit, $offset), ARRAY_A);

        // Enhance results with additional data
        foreach ($results as &$result) {
            $result = $this->enhance_user_data($result);
        }

        return $results;
    }

    /**
     * Enhance user data with additional information
     *
     * @param array $user_data Basic user data
     * @return array Enhanced user data
     * @since 1.0.0
     */
    public function enhance_user_data($user_data) {
        $user_id = $user_data['user_id'];

        // Get avatar URL
        $user_data['avatar_url'] = get_avatar_url($user_id, ['size' => 48]);

        // Get profile URL
        $user_data['profile_url'] = home_url("/askro-user/{$user_data['user_login']}/");

        // Get top badge
        $user_data['top_badge'] = $this->get_user_top_badge($user_id);

        // Get additional stats
        $user_data['questions_count'] = count_user_posts($user_id, 'askro_question');
        $user_data['answers_count'] = count_user_posts($user_id, 'askro_answer');
        $user_data['badges_count'] = $this->get_user_badges_count($user_id);

        return $user_data;
    }

    /**
     * Get user's top badge
     *
     * @param int $user_id User ID
     * @return array|null Top badge data
     * @since 1.0.0
     */
    public function get_user_top_badge($user_id) {
        global $wpdb;

        $badge = $wpdb->get_row($wpdb->prepare(
            "SELECT ub.*, b.name as badge_name, b.icon as badge_icon, b.color as badge_color
             FROM {$wpdb->prefix}askro_user_badges ub
             LEFT JOIN {$wpdb->prefix}askro_badges b ON ub.badge_id = b.id
             WHERE ub.user_id = %d 
             ORDER BY 
                CASE b.name 
                    WHEN 'platinum' THEN 4
                    WHEN 'gold' THEN 3
                    WHEN 'silver' THEN 2
                    WHEN 'bronze' THEN 1
                    ELSE 0
                END DESC,
                ub.earned_at DESC
             LIMIT 1",
            $user_id
        ), ARRAY_A);

        return $badge;
    }

    /**
     * Get user badges count
     *
     * @param int $user_id User ID
     * @return int Badges count
     * @since 1.0.0
     */
    public function get_user_badges_count($user_id) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_user_badges WHERE user_id = %d",
            $user_id
        ));
    }

    /**
     * Get user rank
     *
     * @param int $user_id User ID
     * @param string $type Leaderboard type
     * @param string $period Time period
     * @return array|null User rank data
     * @since 1.0.0
     */
    public function get_user_rank($user_id, $type, $period) {
        global $wpdb;

        $date_condition = $this->get_date_condition($period);

        // Get user's score and rank
        switch ($type) {
            case 'questions':
                $user_score = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts} 
                     WHERE post_author = %d 
                     AND post_type = 'askro_question' 
                     AND post_status = 'publish' 
                     {$date_condition}",
                    $user_id
                ));
                break;

            case 'answers':
                $user_score = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->posts} 
                     WHERE post_author = %d 
                     AND post_type = 'askro_answer' 
                     AND post_status = 'publish' 
                     {$date_condition}",
                    $user_id
                ));
                break;

            case 'badges':
                $user_score = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$wpdb->prefix}askro_user_badges 
                     WHERE user_id = %d {$date_condition}",
                    $user_id
                ));
                break;

            default: // points
                $user_score = $wpdb->get_var($wpdb->prepare(
                    "SELECT COALESCE(SUM(points_change), 0) FROM {$wpdb->prefix}askro_points_log 
                     WHERE user_id = %d {$date_condition}",
                    $user_id
                ));
                break;
        }

        if ($user_score <= 0) {
            return null;
        }

        // Get user's rank
        $rank = $this->calculate_user_rank($user_id, $type, $period, $user_score);

        // Get next rank info
        $next_rank_info = $this->get_next_rank_info($user_id, $type, $period, $rank);

        return [
            'rank' => $rank,
            'score' => $user_score,
            'next_rank' => $next_rank_info['next_rank'] ?? null,
            'points_to_next' => $next_rank_info['points_to_next'] ?? 0,
            'progress_percentage' => $next_rank_info['progress_percentage'] ?? 0
        ];
    }

    /**
     * Calculate user rank
     *
     * @param int $user_id User ID
     * @param string $type Leaderboard type
     * @param string $period Time period
     * @param int $user_score User's score
     * @return int User rank
     * @since 1.0.0
     */
    public function calculate_user_rank($user_id, $type, $period, $user_score) {
        global $wpdb;

        $date_condition = $this->get_date_condition($period);

        switch ($type) {
            case 'questions':
                $rank = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT post_author) + 1 
                     FROM {$wpdb->posts} 
                     WHERE post_type = 'askro_question' 
                     AND post_status = 'publish' 
                     {$date_condition}
                     GROUP BY post_author
                     HAVING COUNT(*) > %d",
                    $user_score
                ));
                break;

            default: // points and others
                $rank = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(DISTINCT user_id) + 1 
                     FROM {$wpdb->prefix}askro_user_points 
                     WHERE 1=1 {$date_condition}
                     GROUP BY user_id
                     HAVING SUM(points_change) > %d",
                    $user_score
                ));
                break;
        }

        return $rank ?: 1;
    }

    /**
     * Get next rank information
     *
     * @param int $user_id User ID
     * @param string $type Leaderboard type
     * @param string $period Time period
     * @param int $current_rank Current rank
     * @return array Next rank info
     * @since 1.0.0
     */
    public function get_next_rank_info($user_id, $type, $period, $current_rank) {
        if ($current_rank <= 1) {
            return [];
        }

        // This is a simplified implementation
        // In a real scenario, you'd calculate the exact points needed to reach the next rank
        return [
            'next_rank' => $current_rank - 1,
            'points_to_next' => rand(10, 100), // Placeholder
            'progress_percentage' => rand(20, 80) // Placeholder
        ];
    }

    /**
     * Leaderboard shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     * @since 1.0.0
     */
    public function leaderboard_shortcode($atts) {
        $atts = shortcode_atts([
            'type' => 'points',
            'period' => 'all_time',
            'limit' => 20,
            'show_filters' => 'true',
            'show_user_rank' => 'true',
            'show_avatars' => 'true',
            'show_badges' => 'true'
        ], $atts);

        // Convert string booleans
        $atts['show_filters'] = $atts['show_filters'] === 'true';
        $atts['show_user_rank'] = $atts['show_user_rank'] === 'true';
        $atts['show_avatars'] = $atts['show_avatars'] === 'true';
        $atts['show_badges'] = $atts['show_badges'] === 'true';

        return $this->render_leaderboard($atts);
    }
}


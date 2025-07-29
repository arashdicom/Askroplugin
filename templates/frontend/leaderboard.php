<?php
/**
 * Leaderboard Template
 *
 * @package    Askro
 * @subpackage Templates/Frontend
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

// Get leaderboard settings from admin
$leaderboard_limit = askro_get_option('leaderboard_limit', 10);
$leaderboard_timeframe = askro_get_option('leaderboard_timeframe', 'all_time');
$show_avatars = askro_get_option('show_avatars', true);
$show_ranks = askro_get_option('show_ranks', true);

// Get leaderboard data
$leaderboard_data = askro_get_leaderboard_data($leaderboard_timeframe, $leaderboard_limit);

// Get current user data for comparison
$current_user = askro_get_user_data();
$current_user_rank = 0;

// Find current user's rank
if ($current_user) {
    foreach ($leaderboard_data as $index => $user) {
        if ($user['id'] === $current_user['id']) {
            $current_user_rank = $index + 1;
            break;
        }
    }
}

// Get timeframe options
$timeframe_options = [
    'weekly' => __('هذا الأسبوع', 'askro'),
    'monthly' => __('هذا الشهر', 'askro'),
    'yearly' => __('هذا العام', 'askro'),
    'all_time' => __('كل الوقت', 'askro')
];
?>

<div class="askme-container askme-leaderboard">
    <div class="askme-main-content">
        
        <!-- Leaderboard Header -->
        <div class="askme-leaderboard-header">
            <h1 class="askme-leaderboard-title"><?php _e('لوحة المتصدرين', 'askro'); ?></h1>
            <p class="askme-leaderboard-subtitle"><?php _e('أفضل المساهمين في المجتمع', 'askro'); ?></p>
        </div>

        <!-- Timeframe Filter -->
        <div class="askme-leaderboard-filters">
            <div class="askme-filter-group">
                <label for="timeframe-filter" class="askme-filter-label"><?php _e('الفترة الزمنية:', 'askro'); ?></label>
                <select id="timeframe-filter" class="askme-filter-select">
                    <?php foreach ($timeframe_options as $value => $label): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($leaderboard_timeframe, $value); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Current User Rank -->
        <?php if ($current_user && $current_user_rank > 0): ?>
            <div class="askme-current-user-rank">
                <div class="askme-rank-card askme-current-user">
                    <div class="askme-rank-position">#<?php echo $current_user_rank; ?></div>
                    <div class="askme-rank-avatar">
                        <img src="<?php echo esc_url($current_user['avatar']); ?>" alt="<?php echo esc_attr($current_user['display_name']); ?>">
                    </div>
                    <div class="askme-rank-info">
                        <div class="askme-rank-name"><?php echo esc_html($current_user['display_name']); ?></div>
                        <div class="askme-rank-stats">
                            <span class="askme-rank-points"><?php echo number_format($current_user['points']); ?> <?php _e('نقطة', 'askro'); ?></span>
                            <span class="askme-rank-level"><?php echo esc_html($current_user['rank']['current']['name']); ?></span>
                        </div>
                    </div>
                    <div class="askme-rank-badge"><?php _e('أنت', 'askro'); ?></div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Leaderboard List -->
        <div class="askme-leaderboard-list">
            <?php if ($leaderboard_data): ?>
                <?php foreach ($leaderboard_data as $index => $user): ?>
                    <?php
                    $is_current_user = ($current_user && $user['id'] === $current_user['id']);
                    $rank_class = $is_current_user ? 'askme-current-user' : '';
                    
                    // Add special classes for top 3
                    if ($index === 0) {
                        $rank_class .= ' askme-rank-gold';
                    } elseif ($index === 1) {
                        $rank_class .= ' askme-rank-silver';
                    } elseif ($index === 2) {
                        $rank_class .= ' askme-rank-bronze';
                    }
                    ?>
                    
                    <div class="askme-rank-card <?php echo esc_attr($rank_class); ?>">
                        <div class="askme-rank-position">
                            <?php if ($index < 3): ?>
                                <div class="askme-medal">
                                    <?php if ($index === 0): ?>🥇<?php elseif ($index === 1): ?>🥈<?php else: ?>🥉<?php endif; ?>
                                </div>
                            <?php endif; ?>
                            #<?php echo $index + 1; ?>
                        </div>
                        
                        <div class="askme-rank-avatar">
                            <img src="<?php echo esc_url($user['avatar']); ?>" alt="<?php echo esc_attr($user['display_name']); ?>">
                            <?php if ($show_ranks && $user['rank']['current']['icon']): ?>
                                <div class="askme-rank-icon">
                                    <img src="<?php echo esc_url($user['rank']['current']['icon']); ?>" alt="<?php echo esc_attr($user['rank']['current']['name']); ?>">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="askme-rank-info">
                            <div class="askme-rank-name">
                                <a href="<?php echo add_query_arg('user', $user['id'], get_permalink()); ?>">
                                    <?php echo esc_html($user['display_name']); ?>
                                </a>
                            </div>
                            <div class="askme-rank-stats">
                                <span class="askme-rank-points"><?php echo number_format($user['points']); ?> <?php _e('نقطة', 'askro'); ?></span>
                                <span class="askme-rank-level"><?php echo esc_html($user['rank']['current']['name']); ?></span>
                            </div>
                            <div class="askme-rank-details">
                                <span class="askme-rank-questions"><?php echo number_format($user['questions_count']); ?> <?php _e('سؤال', 'askro'); ?></span>
                                <span class="askme-rank-answers"><?php echo number_format($user['answers_count']); ?> <?php _e('إجابة', 'askro'); ?></span>
                                <span class="askme-rank-best"><?php echo number_format($user['best_answers_count']); ?> <?php _e('أفضل إجابة', 'askro'); ?></span>
                            </div>
                        </div>
                        
                        <?php if ($is_current_user): ?>
                            <div class="askme-rank-badge"><?php _e('أنت', 'askro'); ?></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="askme-no-leaderboard">
                    <p><?php _e('لا توجد بيانات متاحة', 'askro'); ?></p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Load More Button -->
        <?php if (count($leaderboard_data) >= $leaderboard_limit): ?>
            <div class="askme-load-more">
                <button class="askme-btn askme-btn-secondary" id="askme-load-more-leaderboard">
                    <?php _e('عرض المزيد', 'askro'); ?>
                </button>
            </div>
        <?php endif; ?>
        
    </div>

    <!-- Sidebar -->
    <div class="askme-sidebar">
        
        <!-- Community Stats -->
        <div class="askme-sidebar-module">
            <h3 class="askme-module-title"><?php _e('إحصائيات المجتمع', 'askro'); ?></h3>
            <div class="askme-module-content">
                <?php
                $community_stats = [
                    'total_users' => count_users()['total_users'],
                    'total_questions' => wp_count_posts('askro_question')->publish,
                    'total_answers' => wp_count_posts('askro_answer')->publish,
                    'total_points' => askro_get_community_total_points()
                ];
                ?>
                <div class="askme-community-stats">
                    <div class="askme-stat-item">
                        <span class="askme-stat-label"><?php _e('إجمالي المستخدمين', 'askro'); ?></span>
                        <span class="askme-stat-value"><?php echo number_format($community_stats['total_users']); ?></span>
                    </div>
                    <div class="askme-stat-item">
                        <span class="askme-stat-label"><?php _e('إجمالي الأسئلة', 'askro'); ?></span>
                        <span class="askme-stat-value"><?php echo number_format($community_stats['total_questions']); ?></span>
                    </div>
                    <div class="askme-stat-item">
                        <span class="askme-stat-label"><?php _e('إجمالي الإجابات', 'askro'); ?></span>
                        <span class="askme-stat-value"><?php echo number_format($community_stats['total_answers']); ?></span>
                    </div>
                    <div class="askme-stat-item">
                        <span class="askme-stat-label"><?php _e('إجمالي النقاط', 'askro'); ?></span>
                        <span class="askme-stat-value"><?php echo number_format($community_stats['total_points']); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Categories -->
        <div class="askme-sidebar-module">
            <h3 class="askme-module-title"><?php _e('أفضل التصنيفات', 'askro'); ?></h3>
            <div class="askme-module-content">
                <?php
                $top_categories = get_terms([
                    'taxonomy' => 'askro_question_category',
                    'hide_empty' => true,
                    'number' => 5,
                    'orderby' => 'count',
                    'order' => 'DESC'
                ]);
                
                if ($top_categories): ?>
                    <div class="askme-top-categories">
                        <?php foreach ($top_categories as $category): ?>
                            <div class="askme-category-item">
                                <a href="<?php echo get_term_link($category); ?>" class="askme-category-link">
                                    <span class="askme-category-name"><?php echo esc_html($category->name); ?></span>
                                    <span class="askme-category-count"><?php echo number_format($category->count); ?></span>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="askme-no-content"><?php _e('لا توجد تصنيفات', 'askro'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="askme-sidebar-module">
            <h3 class="askme-module-title"><?php _e('النشاط الأخير', 'askro'); ?></h3>
            <div class="askme-module-content">
                <?php
                $recent_activity = get_posts([
                    'post_type' => ['askro_question', 'askro_answer'],
                    'post_status' => 'publish',
                    'posts_per_page' => 5,
                    'orderby' => 'date',
                    'order' => 'DESC'
                ]);
                
                if ($recent_activity): ?>
                    <div class="askme-recent-activity">
                        <?php foreach ($recent_activity as $activity): ?>
                            <div class="askme-activity-item">
                                <div class="askme-activity-icon">
                                    <?php if ($activity->post_type === 'askro_question'): ?>
                                        ❓
                                    <?php else: ?>
                                        💬
                                    <?php endif; ?>
                                </div>
                                <div class="askme-activity-content">
                                    <a href="<?php echo get_permalink($activity->ID); ?>" class="askme-activity-link">
                                        <?php echo esc_html($activity->post_title); ?>
                                    </a>
                                    <div class="askme-activity-meta">
                                        <span class="askme-activity-author">
                                            <?php echo esc_html(get_the_author_meta('display_name', $activity->post_author)); ?>
                                        </span>
                                        <span class="askme-activity-date">
                                            <?php echo askro_time_ago($activity->post_date); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="askme-no-content"><?php _e('لا يوجد نشاط حديث', 'askro'); ?></p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Timeframe filter
    $('#timeframe-filter').on('change', function() {
        const timeframe = $(this).val();
        
        // Show loading
        $('.askme-leaderboard-list').addClass('askme-loading');
        
        $.ajax({
            url: askro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'askro_get_leaderboard',
                timeframe: timeframe,
                nonce: askro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.askme-leaderboard-list').html(response.data.html);
                } else {
                    alert(response.data.message || 'حدث خطأ أثناء تحميل البيانات');
                }
            },
            error: function() {
                alert('حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.');
            },
            complete: function() {
                $('.askme-leaderboard-list').removeClass('askme-loading');
            }
        });
    });
    
    // Load more functionality
    $('#askme-load-more-leaderboard').on('click', function() {
        const button = $(this);
        const currentOffset = $('.askme-rank-card').length;
        const timeframe = $('#timeframe-filter').val();
        
        button.prop('disabled', true).text('جاري التحميل...');
        
        $.ajax({
            url: askro_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'askro_load_more_leaderboard',
                timeframe: timeframe,
                offset: currentOffset,
                limit: <?php echo $leaderboard_limit; ?>,
                nonce: askro_ajax.nonce
            },
            success: function(response) {
                if (response.success) {
                    $('.askme-leaderboard-list').append(response.data.html);
                    
                    if (response.data.has_more) {
                        button.prop('disabled', false).text('عرض المزيد');
                    } else {
                        button.hide();
                    }
                } else {
                    alert(response.data.message || 'حدث خطأ أثناء تحميل البيانات');
                    button.prop('disabled', false).text('عرض المزيد');
                }
            },
            error: function() {
                alert('حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.');
                button.prop('disabled', false).text('عرض المزيد');
            }
        });
    });
});
</script> 

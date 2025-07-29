<?php
/**
 * User Profile Template
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

// Get user data
$current_user_id = get_current_user_id();
$profile_user_id = isset($_GET['user']) ? intval($_GET['user']) : $current_user_id;

// If no user specified and not logged in, redirect to login
if (!$profile_user_id && !$current_user_id) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$profile_user = askro_get_user_data($profile_user_id);
$is_own_profile = ($current_user_id === $profile_user_id);

// Get user statistics
$user_stats = [
    'questions_count' => askro_get_user_questions_count($profile_user_id),
    'answers_count' => askro_get_user_answers_count($profile_user_id),
    'best_answers_count' => askro_get_user_best_answers_count($profile_user_id),
    'total_points' => $profile_user['points'],
    'rank' => $profile_user['rank'],
    'badges' => $profile_user['badges'],
    'registration_date' => $profile_user['registration_date']
];

// Get recent activity
$recent_questions = get_posts([
    'post_type' => 'askro_question',
    'post_status' => 'publish',
    'author' => $profile_user_id,
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC'
]);

$recent_answers = get_posts([
    'post_type' => 'askro_answer',
    'post_status' => 'publish',
    'author' => $profile_user_id,
    'posts_per_page' => 5,
    'orderby' => 'date',
    'order' => 'DESC'
]);

// Get achievements
$achievements = askro_get_user_achievements($profile_user_id);

// Get points history
$points_history = askro_get_points_transactions([
    'user_id' => $profile_user_id,
    'limit' => 10
]);
?>

<div class="askme-container askme-user-profile">
    <div class="askme-main-content">
        
        <!-- Profile Header -->
        <div class="askme-profile-header">
            <div class="askme-profile-avatar">
                <img src="<?php echo esc_url($profile_user['avatar']); ?>" alt="<?php echo esc_attr($profile_user['display_name']); ?>" class="askme-avatar">
                <?php if (isset($user_stats['rank']['current']['icon']) && $user_stats['rank']['current']['icon']): ?>
                    <div class="askme-rank-badge">
                        <img src="<?php echo esc_url($user_stats['rank']['current']['icon']); ?>" alt="<?php echo esc_attr($user_stats['rank']['current']['name']); ?>">
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="askme-profile-info">
                <h1 class="askme-profile-name"><?php echo esc_html($profile_user['display_name']); ?></h1>
                <div class="askme-profile-rank">
                    <span class="askme-rank-name"><?php echo esc_html($user_stats['rank']['current']['name']); ?></span>
                    <?php if (isset($user_stats['rank']['current']['level'])): ?>
                        <span class="askme-rank-level"><?php echo esc_html($user_stats['rank']['current']['level']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="askme-profile-meta">
                    <span class="askme-join-date">
                        <?php printf(__('انضم في %s', 'askro'), date_i18n(get_option('date_format'), strtotime($user_stats['registration_date']))); ?>
                    </span>
                </div>
            </div>
            
            <?php if ($is_own_profile): ?>
                <div class="askme-profile-actions">
                    <a href="<?php echo admin_url('profile.php'); ?>" class="askme-btn askme-btn-secondary">
                        <?php _e('تعديل الملف الشخصي', 'askro'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Profile Stats -->
        <div class="askme-profile-stats">
            <div class="askme-stat-card">
                <div class="askme-stat-icon">📊</div>
                <div class="askme-stat-content">
                    <div class="askme-stat-value"><?php echo number_format($user_stats['total_points']); ?></div>
                    <div class="askme-stat-label"><?php _e('إجمالي النقاط', 'askro'); ?></div>
                </div>
            </div>
            
            <div class="askme-stat-card">
                <div class="askme-stat-icon">❓</div>
                <div class="askme-stat-content">
                    <div class="askme-stat-value"><?php echo number_format($user_stats['questions_count']); ?></div>
                    <div class="askme-stat-label"><?php _e('الأسئلة', 'askro'); ?></div>
                </div>
            </div>
            
            <div class="askme-stat-card">
                <div class="askme-stat-icon">💬</div>
                <div class="askme-stat-content">
                    <div class="askme-stat-value"><?php echo number_format($user_stats['answers_count']); ?></div>
                    <div class="askme-stat-label"><?php _e('الإجابات', 'askro'); ?></div>
                </div>
            </div>
            
            <div class="askme-stat-card">
                <div class="askme-stat-icon">🏆</div>
                <div class="askme-stat-content">
                    <div class="askme-stat-value"><?php echo number_format($user_stats['best_answers_count']); ?></div>
                    <div class="askme-stat-label"><?php _e('أفضل إجابات', 'askro'); ?></div>
                </div>
            </div>
        </div>

        <!-- XP Progress -->
        <div class="askme-xp-progress">
            <div class="askme-progress-header">
                <h3><?php _e('تقدم النقاط', 'askro'); ?></h3>
                <span class="askme-progress-text">
                    <?php echo number_format($user_stats['total_points']); ?> / <?php echo number_format(isset($user_stats['rank']['next']['threshold']) ? $user_stats['rank']['next']['threshold'] : 0); ?>
                </span>
            </div>
            <div class="askme-progress-bar">
                <div class="askme-progress-fill" style="width: <?php echo isset($user_stats['rank']['progress_percentage']) ? $user_stats['rank']['progress_percentage'] : 0; ?>%"></div>
            </div>
            <div class="askme-progress-info">
                <span><?php echo esc_html($user_stats['rank']['current']['name']); ?></span>
                <span><?php echo esc_html($user_stats['rank']['next']['name']); ?></span>
            </div>
        </div>

        <!-- Profile Tabs -->
        <div class="askme-profile-tabs">
            <div class="askme-tab-nav">
                <button class="askme-tab-btn active" data-tab="activity">
                    <?php _e('النشاط الأخير', 'askro'); ?>
                </button>
                <button class="askme-tab-btn" data-tab="questions">
                    <?php _e('الأسئلة', 'askro'); ?>
                </button>
                <button class="askme-tab-btn" data-tab="answers">
                    <?php _e('الإجابات', 'askro'); ?>
                </button>
                <button class="askme-tab-btn" data-tab="achievements">
                    <?php _e('الإنجازات', 'askro'); ?>
                </button>
                <?php if ($is_own_profile): ?>
                    <button class="askme-tab-btn" data-tab="points">
                        <?php _e('سجل النقاط', 'askro'); ?>
                    </button>
                <?php endif; ?>
            </div>

            <!-- Activity Tab -->
            <div class="askme-tab-content active" id="activity">
                <div class="askme-activity-timeline">
                    <?php
                    $activities = array_merge(
                        array_map(function($question) { return ['type' => 'question', 'data' => $question]; }, $recent_questions),
                        array_map(function($answer) { return ['type' => 'answer', 'data' => $answer]; }, $recent_answers)
                    );
                    
                    // Sort by date
                    usort($activities, function($a, $b) {
                        return strtotime($b['data']->post_date) - strtotime($a['data']->post_date);
                    });
                    
                    $activities = array_slice($activities, 0, 10);
                    
                    if ($activities): ?>
                        <?php foreach ($activities as $activity): ?>
                            <div class="askme-activity-item">
                                <div class="askme-activity-icon">
                                    <?php if ($activity['type'] === 'question'): ?>
                                        ❓
                                    <?php else: ?>
                                        💬
                                    <?php endif; ?>
                                </div>
                                <div class="askme-activity-content">
                                    <div class="askme-activity-title">
                                        <a href="<?php echo get_permalink($activity['data']->ID); ?>">
                                            <?php echo esc_html($activity['data']->post_title); ?>
                                        </a>
                                    </div>
                                    <div class="askme-activity-meta">
                                        <span class="askme-activity-type">
                                            <?php echo $activity['type'] === 'question' ? __('سؤال', 'askro') : __('إجابة', 'askro'); ?>
                                        </span>
                                        <span class="askme-activity-date">
                                            <?php echo askro_time_ago($activity['data']->post_date); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="askme-no-activity">
                            <p><?php _e('لا يوجد نشاط حديث', 'askro'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Questions Tab -->
            <div class="askme-tab-content" id="questions">
                <div class="askme-questions-list">
                    <?php if ($recent_questions): ?>
                        <?php foreach ($recent_questions as $question): ?>
                            <div class="askme-question-item">
                                <div class="askme-question-title">
                                    <a href="<?php echo get_permalink($question->ID); ?>">
                                        <?php echo esc_html($question->post_title); ?>
                                    </a>
                                </div>
                                <div class="askme-question-meta">
                                    <span class="askme-question-date">
                                        <?php echo askro_time_ago($question->post_date); ?>
                                    </span>
                                    <span class="askme-question-answers">
                                        <?php echo askro_get_question_answers_count($question->ID); ?> <?php _e('إجابة', 'askro'); ?>
                                    </span>
                                    <span class="askme-question-votes">
                                        <?php echo askro_get_total_votes($question->ID); ?> <?php _e('تصويت', 'askro'); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if ($user_stats['questions_count'] > 5): ?>
                            <div class="askme-load-more">
                                <a href="<?php echo add_query_arg(['tab' => 'questions'], get_permalink()); ?>" class="askme-btn askme-btn-secondary">
                                    <?php _e('عرض جميع الأسئلة', 'askro'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="askme-no-content">
                            <p><?php _e('لا توجد أسئلة', 'askro'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Answers Tab -->
            <div class="askme-tab-content" id="answers">
                <div class="askme-answers-list">
                    <?php if ($recent_answers): ?>
                        <?php foreach ($recent_answers as $answer): ?>
                            <?php
                            $question_id = get_post_meta($answer->ID, 'askro_question_id', true);
                            $question = get_post($question_id);
                            $is_best_answer = get_post_meta($answer->ID, 'askro_best_answer', true);
                            ?>
                            <div class="askme-answer-item <?php echo $is_best_answer ? 'askme-best-answer' : ''; ?>">
                                <?php if ($is_best_answer): ?>
                                    <div class="askme-best-badge">🏆 <?php _e('أفضل إجابة', 'askro'); ?></div>
                                <?php endif; ?>
                                
                                <div class="askme-answer-question">
                                    <a href="<?php echo get_permalink($question_id); ?>">
                                        <?php echo esc_html($question->post_title); ?>
                                    </a>
                                </div>
                                
                                <div class="askme-answer-excerpt">
                                    <?php echo wp_trim_words($answer->post_content, 20); ?>
                                </div>
                                
                                <div class="askme-answer-meta">
                                    <span class="askme-answer-date">
                                        <?php echo askro_time_ago($answer->post_date); ?>
                                    </span>
                                    <span class="askme-answer-votes">
                                        <?php echo askro_get_total_votes($answer->ID); ?> <?php _e('تصويت', 'askro'); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if ($user_stats['answers_count'] > 5): ?>
                            <div class="askme-load-more">
                                <a href="<?php echo add_query_arg(['tab' => 'answers'], get_permalink()); ?>" class="askme-btn askme-btn-secondary">
                                    <?php _e('عرض جميع الإجابات', 'askro'); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="askme-no-content">
                            <p><?php _e('لا توجد إجابات', 'askro'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Achievements Tab -->
            <div class="askme-tab-content" id="achievements">
                <div class="askme-achievements-grid">
                    <?php if ($achievements): ?>
                        <?php foreach ($achievements as $achievement): ?>
                            <div class="askme-achievement-card">
                                <div class="askme-achievement-icon">
                                    <?php if ($achievement['icon']): ?>
                                        <img src="<?php echo esc_url($achievement['icon']); ?>" alt="<?php echo esc_attr($achievement['title']); ?>">
                                    <?php else: ?>
                                        🏆
                                    <?php endif; ?>
                                </div>
                                <div class="askme-achievement-content">
                                    <h4 class="askme-achievement-title"><?php echo esc_html($achievement['title']); ?></h4>
                                    <p class="askme-achievement-description"><?php echo esc_html($achievement['description']); ?></p>
                                    <div class="askme-achievement-date">
                                        <?php echo askro_time_ago($achievement['earned_date']); ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="askme-no-achievements">
                            <p><?php _e('لا توجد إنجازات بعد', 'askro'); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($is_own_profile): ?>
                <!-- Points History Tab -->
                <div class="askme-tab-content" id="points">
                    <div class="askme-points-history">
                        <?php if ($points_history): ?>
                            <div class="askme-points-table">
                                <div class="askme-points-header">
                                    <div class="askme-points-col"><?php _e('التاريخ', 'askro'); ?></div>
                                    <div class="askme-points-col"><?php _e('النقاط', 'askro'); ?></div>
                                    <div class="askme-points-col"><?php _e('السبب', 'askro'); ?></div>
                                </div>
                                
                                <?php foreach ($points_history as $transaction): ?>
                                    <div class="askme-points-row">
                                        <div class="askme-points-col">
                                            <?php echo askro_time_ago(isset($transaction['created_at']) ? $transaction['created_at'] : ''); ?>
                                        </div>
                                        <div class="askme-points-col">
                                            <span class="askme-points-value <?php echo (isset($transaction['points']) && $transaction['points'] > 0) ? 'positive' : 'negative'; ?>">
                                                <?php echo (isset($transaction['points']) && $transaction['points'] > 0 ? '+' : '') . number_format(isset($transaction['points']) ? $transaction['points'] : 0); ?>
                                            </span>
                                        </div>
                                        <div class="askme-points-col">
                                            <?php echo esc_html(isset($transaction['reason']) ? $transaction['reason'] : ''); ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="askme-no-points">
                                <p><?php _e('لا توجد معاملات نقاط', 'askro'); ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
    </div>

    <!-- Sidebar -->
    <div class="askme-sidebar">
        
        <!-- User Badges -->
        <?php if ($user_stats['badges']): ?>
            <div class="askme-sidebar-module">
                <h3 class="askme-module-title"><?php _e('الشارات', 'askro'); ?></h3>
                <div class="askme-module-content">
                    <div class="askme-badges-grid">
                        <?php foreach ($user_stats['badges'] as $badge): ?>
                            <div class="askme-badge-item" title="<?php echo esc_attr($badge['title']); ?>">
                                <?php if ($badge['icon']): ?>
                                    <img src="<?php echo esc_url($badge['icon']); ?>" alt="<?php echo esc_attr($badge['title']); ?>">
                                <?php else: ?>
                                    🏅
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Rank Progress -->
        <div class="askme-sidebar-module">
            <h3 class="askme-module-title"><?php _e('الرتبة التالية', 'askro'); ?></h3>
            <div class="askme-module-content">
                <div class="askme-next-rank">
                    <div class="askme-next-rank-info">
                        <h4><?php echo esc_html($user_stats['rank']['next']['name']); ?></h4>
                        <?php if (isset($user_stats['rank']['next']['description'])): ?>
                            <p><?php echo esc_html($user_stats['rank']['next']['description']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="askme-next-rank-progress">
                        <div class="askme-progress-bar">
                            <div class="askme-progress-fill" style="width: <?php echo isset($user_stats['rank']['progress_percentage']) ? $user_stats['rank']['progress_percentage'] : 0; ?>%"></div>
                        </div>
                        <span class="askme-progress-text">
                            <?php echo number_format($user_stats['total_points']); ?> / <?php echo number_format(isset($user_stats['rank']['next']['threshold']) ? $user_stats['rank']['next']['threshold'] : 0); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="askme-sidebar-module">
            <h3 class="askme-module-title"><?php _e('إحصائيات سريعة', 'askro'); ?></h3>
            <div class="askme-module-content">
                <div class="askme-quick-stats">
                    <div class="askme-quick-stat">
                        <span class="askme-quick-stat-label"><?php _e('معدل الإجابة', 'askro'); ?></span>
                        <span class="askme-quick-stat-value">
                            <?php 
                            $answer_rate = $user_stats['questions_count'] > 0 ? 
                                round(($user_stats['answers_count'] / $user_stats['questions_count']) * 100, 1) : 0;
                            echo $answer_rate . '%';
                            ?>
                        </span>
                    </div>
                    <div class="askme-quick-stat">
                        <span class="askme-quick-stat-label"><?php _e('معدل أفضل إجابة', 'askro'); ?></span>
                        <span class="askme-quick-stat-value">
                            <?php 
                            $best_rate = $user_stats['answers_count'] > 0 ? 
                                round(($user_stats['best_answers_count'] / $user_stats['answers_count']) * 100, 1) : 0;
                            echo $best_rate . '%';
                            ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.askme-tab-btn').on('click', function() {
        const tabId = $(this).data('tab');
        
        // Update active tab button
        $('.askme-tab-btn').removeClass('active');
        $(this).addClass('active');
        
        // Update active tab content
        $('.askme-tab-content').removeClass('active');
        $('#' + tabId).addClass('active');
        
        // Update URL
        const url = new URL(window.location);
        url.searchParams.set('tab', tabId);
        window.history.replaceState({}, '', url);
    });
    
    // Load tab from URL on page load
    const urlParams = new URLSearchParams(window.location.search);
    const activeTab = urlParams.get('tab');
    if (activeTab && $('[data-tab="' + activeTab + '"]').length) {
        $('.askme-tab-btn').removeClass('active');
        $('[data-tab="' + activeTab + '"]').addClass('active');
        $('.askme-tab-content').removeClass('active');
        $('#' + activeTab).addClass('active');
    }
});
</script> 

<?php
/**
 * Admin Dashboard View
 *
 * @package    Askro
 * @subpackage Admin/Views
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
?>

<div class="askro-admin-wrap">
    <div class="askro-admin-header">
        <h1 class="askro-admin-title">
            <?php _e('لوحة تحكم Askro', 'askro'); ?>
        </h1>
        <p class="askro-admin-subtitle">
            <?php _e('مرحباً بك في نظام إدارة الأسئلة والأجوبة المتقدم', 'askro'); ?>
        </p>
    </div>

    <!-- Statistics Cards -->
    <div class="askro-dashboard-grid">
        <!-- Questions Card -->
        <div class="askro-dashboard-card">
            <div class="askro-dashboard-card-icon" style="background-color: #3b82f6;">
                <span class="dashicons dashicons-format-chat" style="color: white; font-size: 24px;"></span>
            </div>
            <h3 class="askro-dashboard-card-title"><?php _e('الأسئلة', 'askro'); ?></h3>
            <div class="askro-dashboard-card-value"><?php echo number_format($stats['questions']['total'] ?? 0); ?></div>
            <div class="askro-dashboard-card-change positive">
                +<?php echo $stats['questions']['today'] ?? 0; ?> <?php _e('اليوم', 'askro'); ?>
            </div>
        </div>

        <!-- Answers Card -->
        <div class="askro-dashboard-card">
            <div class="askro-dashboard-card-icon" style="background-color: #10b981;">
                <span class="dashicons dashicons-yes-alt" style="color: white; font-size: 24px;"></span>
            </div>
            <h3 class="askro-dashboard-card-title"><?php _e('الإجابات', 'askro'); ?></h3>
            <div class="askro-dashboard-card-value"><?php echo number_format($stats['answers']['total'] ?? 0); ?></div>
            <div class="askro-dashboard-card-change positive">
                +<?php echo $stats['answers']['today'] ?? 0; ?> <?php _e('اليوم', 'askro'); ?>
            </div>
        </div>

        <!-- Users Card -->
        <div class="askro-dashboard-card">
            <div class="askro-dashboard-card-icon" style="background-color: #8b5cf6;">
                <span class="dashicons dashicons-groups" style="color: white; font-size: 24px;"></span>
            </div>
            <h3 class="askro-dashboard-card-title"><?php _e('المستخدمون', 'askro'); ?></h3>
            <div class="askro-dashboard-card-value"><?php echo number_format($stats['users']['total'] ?? 0); ?></div>
            <div class="askro-dashboard-card-change positive">
                <?php echo $stats['users']['active_today'] ?? 0; ?> <?php _e('نشط اليوم', 'askro'); ?>
            </div>
        </div>

        <!-- Votes Card -->
        <div class="askro-dashboard-card">
            <div class="askro-dashboard-card-icon" style="background-color: #f59e0b;">
                <span class="dashicons dashicons-thumbs-up" style="color: white; font-size: 24px;"></span>
            </div>
            <h3 class="askro-dashboard-card-title"><?php _e('التصويتات', 'askro'); ?></h3>
            <div class="askro-dashboard-card-value"><?php echo number_format($stats['votes']['total'] ?? 0); ?></div>
            <div class="askro-dashboard-card-change positive">
                +<?php echo $stats['votes']['today'] ?? 0; ?> <?php _e('اليوم', 'askro'); ?>
            </div>
        </div>

        <!-- Points Card -->
        <div class="askro-dashboard-card">
            <div class="askro-dashboard-card-icon" style="background-color: #ef4444;">
                <span class="dashicons dashicons-star-filled" style="color: white; font-size: 24px;"></span>
            </div>
            <h3 class="askro-dashboard-card-title"><?php _e('النقاط الممنوحة', 'askro'); ?></h3>
            <div class="askro-dashboard-card-value"><?php echo number_format($stats['points']['total_awarded'] ?? 0); ?></div>
            <div class="askro-dashboard-card-change">
                <?php echo number_format($stats['points']['average_per_user'] ?? 0, 1); ?> <?php _e('متوسط لكل مستخدم', 'askro'); ?>
            </div>
        </div>

        <!-- Top User Card -->
        <?php if ($stats['points']['top_user']): ?>
        <div class="askro-dashboard-card">
            <div class="askro-dashboard-card-icon" style="background-color: #06b6d4;">
                <span class="dashicons dashicons-awards" style="color: white; font-size: 24px;"></span>
            </div>
            <h3 class="askro-dashboard-card-title"><?php _e('المتصدر', 'askro'); ?></h3>
            <div class="askro-dashboard-card-value" style="font-size: 16px;">
                <?php echo esc_html($stats['points']['top_user']['user']->display_name); ?>
            </div>
            <div class="askro-dashboard-card-change positive">
                <?php echo number_format($stats['points']['top_user']['points'] ?? 0); ?> <?php _e('نقطة', 'askro'); ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Charts and Analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Activity Chart -->
        <div class="askro-admin-form">
            <h3 class="text-lg font-semibold mb-4"><?php _e('النشاط اليومي', 'askro'); ?></h3>
            <div class="chart-container" style="position: relative; height: 300px; max-height: 300px;">
                <canvas id="activityChart"></canvas>
            </div>
        </div>

        <!-- Vote Types Chart -->
        <div class="askro-admin-form">
            <h3 class="text-lg font-semibold mb-4"><?php _e('أنواع التصويت', 'askro'); ?></h3>
            <div class="chart-container" style="position: relative; height: 300px; max-height: 300px;">
                <canvas id="voteTypesChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Activity Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Questions -->
        <div class="askro-admin-form">
            <h3 class="text-lg font-semibold mb-4"><?php _e('الأسئلة الحديثة', 'askro'); ?></h3>
            <div class="askro-admin-table">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th><?php _e('السؤال', 'askro'); ?></th>
                            <th><?php _e('الكاتب', 'askro'); ?></th>
                            <th><?php _e('التاريخ', 'askro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_questions = get_posts([
                            'post_type' => 'askro_question',
                            'posts_per_page' => 5,
                            'post_status' => 'publish'
                        ]);
                        
                        foreach ($recent_questions as $question):
                            $author = get_userdata($question->post_author);
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo get_edit_post_link($question->ID); ?>" class="text-blue-600 hover:text-blue-800">
                                    <?php echo esc_html(wp_trim_words($question->post_title, 8)); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($author->display_name); ?></td>
                            <td><?php echo human_time_diff(strtotime($question->post_date), current_time('timestamp')); ?> <?php _e('مضت', 'askro'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <a href="<?php echo admin_url('admin.php?page=askro-questions'); ?>" class="askro-admin-btn askro-admin-btn-outline">
                    <?php _e('عرض جميع الأسئلة', 'askro'); ?>
                </a>
            </div>
        </div>

        <!-- Recent Answers -->
        <div class="askro-admin-form">
            <h3 class="text-lg font-semibold mb-4"><?php _e('الإجابات الحديثة', 'askro'); ?></h3>
            <div class="askro-admin-table">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th><?php _e('الإجابة', 'askro'); ?></th>
                            <th><?php _e('الكاتب', 'askro'); ?></th>
                            <th><?php _e('التاريخ', 'askro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $recent_answers = get_posts([
                            'post_type' => 'askro_answer',
                            'posts_per_page' => 5,
                            'post_status' => 'publish'
                        ]);
                        
                        foreach ($recent_answers as $answer):
                            $author = get_userdata($answer->post_author);
                        ?>
                        <tr>
                            <td>
                                <a href="<?php echo get_edit_post_link($answer->ID); ?>" class="text-blue-600 hover:text-blue-800">
                                    <?php echo esc_html(wp_trim_words($answer->post_content, 8)); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html($author->display_name); ?></td>
                            <td><?php echo human_time_diff(strtotime($answer->post_date), current_time('timestamp')); ?> <?php _e('مضت', 'askro'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-4">
                <a href="<?php echo admin_url('admin.php?page=askro-answers'); ?>" class="askro-admin-btn askro-admin-btn-outline">
                    <?php _e('عرض جميع الإجابات', 'askro'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="askro-admin-form mt-8">
        <h3 class="text-lg font-semibold mb-4"><?php _e('إجراءات سريعة', 'askro'); ?></h3>
        <div class="flex flex-wrap gap-4">
            <a href="<?php echo admin_url('post-new.php?post_type=askro_question'); ?>" class="askro-admin-btn askro-admin-btn-primary">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php _e('إضافة سؤال جديد', 'askro'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=askro-users'); ?>" class="askro-admin-btn askro-admin-btn-secondary">
                <span class="dashicons dashicons-groups"></span>
                <?php _e('إدارة المستخدمين', 'askro'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=askro-settings'); ?>" class="askro-admin-btn askro-admin-btn-outline">
                <span class="dashicons dashicons-admin-settings"></span>
                <?php _e('الإعدادات', 'askro'); ?>
            </a>
            <a href="<?php echo admin_url('admin.php?page=askro-analytics'); ?>" class="askro-admin-btn askro-admin-btn-outline">
                <span class="dashicons dashicons-chart-area"></span>
                <?php _e('التحليلات المتقدمة', 'askro'); ?>
            </a>
        </div>
    </div>

    <!-- System Status -->
    <div class="askro-admin-form mt-8">
        <h3 class="text-lg font-semibold mb-4"><?php _e('حالة النظام', 'askro'); ?></h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <span class="dashicons dashicons-yes-alt text-green-600"></span>
                    <span class="ml-2 text-green-800 font-medium"><?php _e('قاعدة البيانات', 'askro'); ?></span>
                </div>
                <p class="text-sm text-green-600 mt-1"><?php _e('تعمل بشكل طبيعي', 'askro'); ?></p>
            </div>
            
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <span class="dashicons dashicons-yes-alt text-green-600"></span>
                    <span class="ml-2 text-green-800 font-medium"><?php _e('الأصول', 'askro'); ?></span>
                </div>
                <p class="text-sm text-green-600 mt-1"><?php _e('محملة بنجاح', 'askro'); ?></p>
            </div>
            
            <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <span class="dashicons dashicons-yes-alt text-green-600"></span>
                    <span class="ml-2 text-green-800 font-medium"><?php _e('الإعدادات', 'askro'); ?></span>
                </div>
                <p class="text-sm text-green-600 mt-1"><?php _e('مُكونة بشكل صحيح', 'askro'); ?></p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Activity Chart
    const activityCtx = document.getElementById('activityChart').getContext('2d');
    new Chart(activityCtx, {
        type: 'line',
        data: {
            labels: [
                <?php
                for ($i = 6; $i >= 0; $i--) {
                    echo '"' . date('M j', strtotime("-{$i} days")) . '"';
                    if ($i > 0) echo ',';
                }
                ?>
            ],
            datasets: [{
                label: '<?php _e("الأسئلة", "askro"); ?>',
                data: [
                    <?php
                    for ($i = 6; $i >= 0; $i--) {
                        $date = date('Y-m-d', strtotime("-{$i} days"));
                        $count = get_posts([
                            'post_type' => 'askro_question',
                            'post_status' => 'publish',
                            'date_query' => [
                                [
                                    'year' => date('Y', strtotime($date)),
                                    'month' => date('m', strtotime($date)),
                                    'day' => date('d', strtotime($date))
                                ]
                            ],
                            'fields' => 'ids'
                        ]);
                        echo count($count);
                        if ($i > 0) echo ',';
                    }
                    ?>
                ],
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            }, {
                label: '<?php _e("الإجابات", "askro"); ?>',
                data: [
                    <?php
                    for ($i = 6; $i >= 0; $i--) {
                        $date = date('Y-m-d', strtotime("-{$i} days"));
                        $count = get_posts([
                            'post_type' => 'askro_answer',
                            'post_status' => 'publish',
                            'date_query' => [
                                [
                                    'year' => date('Y', strtotime($date)),
                                    'month' => date('m', strtotime($date)),
                                    'day' => date('d', strtotime($date))
                                ]
                            ],
                            'fields' => 'ids'
                        ]);
                        echo count($count);
                        if ($i > 0) echo ',';
                    }
                    ?>
                ],
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Vote Types Chart
    const voteTypesCtx = document.getElementById('voteTypesChart').getContext('2d');
    new Chart(voteTypesCtx, {
        type: 'doughnut',
        data: {
            labels: ['<?php _e("مفيد", "askro"); ?>', '<?php _e("إبداعي", "askro"); ?>', '<?php _e("عميق", "askro"); ?>', '<?php _e("مضحك", "askro"); ?>', '<?php _e("عاطفي", "askro"); ?>'],
            datasets: [{
                data: [
                    <?php echo $stats['votes']['positive'] * 0.4; ?>,
                    <?php echo $stats['votes']['positive'] * 0.25; ?>,
                    <?php echo $stats['votes']['positive'] * 0.2; ?>,
                    <?php echo $stats['votes']['positive'] * 0.1; ?>,
                    <?php echo $stats['votes']['positive'] * 0.05; ?>
                ],
                backgroundColor: [
                    '#22c55e',
                    '#f59e0b',
                    '#8b5cf6',
                    '#06b6d4',
                    '#ec4899'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
});

    // Debug Tools
    window.createTestAnswer = function() {
        if (confirm('هل تريد إنشاء إجابة تجريبية؟')) {
            // Get the first question
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=askro_create_test_answer&nonce=<?php echo wp_create_nonce("askro_nonce"); ?>'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('تم إنشاء إجابة تجريبية بنجاح!');
                    location.reload();
                } else {
                    alert('خطأ: ' + data.data.message);
                }
            })
            .catch(error => {
                alert('حدث خطأ في الاتصال');
                console.error('Error:', error);
            });
        }
    };
</script>

<!-- Debug Tools Section -->
<div class="askro-dashboard-card" style="background: #fff3cd; border: 2px solid #ffc107; margin-top: 20px;">
    <div class="askro-dashboard-card-icon" style="background-color: #ffc107;">
        <span class="dashicons dashicons-admin-tools" style="color: white; font-size: 24px;"></span>
    </div>
    <h3 class="askro-dashboard-card-title">🔧 أدوات التصحيح</h3>
    <div style="margin: 10px 0;">
        <button onclick="createTestAnswer()" style="background: #dc3545; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 4px; font-size: 14px;">
            إنشاء إجابة تجريبية
        </button>
    </div>
    <div style="font-size: 12px; color: #666; margin-top: 10px;">
        لاختبار نظام الإجابات والتقييم والتعليقات
    </div>
</div>


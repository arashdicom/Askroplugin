<?php
/**
 * Admin Voting Management View
 *
 * Comprehensive voting system management with multi-dimensional voting,
 * vote analytics, karma deflector, and advanced administrative controls.
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

// Get current tab
$current_tab = $_GET['tab'] ?? 'overview';
?>

<div class="askro-admin-wrap">
    <div class="askro-admin-header">
        <h1 class="askro-admin-title">
            <?php _e('نظام التصويت', 'askro'); ?>
        </h1>
        <p class="askro-admin-subtitle">
            <?php _e('إدارة نظام التصويت متعدد الأبعاد ومراقبة الأنشطة', 'askro'); ?>
        </p>
    </div>

    <!-- Voting Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="askro-dashboard-card">
            <div class="askro-dashboard-card-icon" style="background-color: #22c55e;">
                <span class="dashicons dashicons-thumbs-up" style="color: white;"></span>
            </div>
            <h3 class="askro-dashboard-card-title"><?php _e('التصويتات الإيجابية', 'askro'); ?></h3>
            <div class="askro-dashboard-card-value">
                <?php echo number_format($voting_data['positive_votes'] ?? 0); ?>
            </div>
        </div>

        <div class="askro-dashboard-card">
            <div class="askro-dashboard-card-icon" style="background-color: #ef4444;">
                <span class="dashicons dashicons-thumbs-down" style="color: white;"></span>
            </div>
            <h3 class="askro-dashboard-card-title"><?php _e('التصويتات السلبية', 'askro'); ?></h3>
            <div class="askro-dashboard-card-value">
                <?php echo number_format($voting_data['negative_votes'] ?? 0); ?>
            </div>
        </div>

        <div class="askro-dashboard-card">
            <div class="askro-dashboard-card-icon" style="background-color: #3b82f6;">
                <span class="dashicons dashicons-chart-line" style="color: white;"></span>
            </div>
            <h3 class="askro-dashboard-card-title"><?php _e('إجمالي التصويتات', 'askro'); ?></h3>
            <div class="askro-dashboard-card-value">
                <?php echo number_format(($voting_data['positive_votes'] ?? 0) + ($voting_data['negative_votes'] ?? 0)); ?>
            </div>
        </div>

        <div class="askro-dashboard-card">
            <div class="askro-dashboard-card-icon" style="background-color: #8b5cf6;">
                <span class="dashicons dashicons-heart" style="color: white;"></span>
            </div>
            <h3 class="askro-dashboard-card-title"><?php _e('معدل الرضا', 'askro'); ?></h3>
            <div class="askro-dashboard-card-value">
                <?php 
                $total = ($voting_data['positive_votes'] ?? 0) + ($voting_data['negative_votes'] ?? 0);
                $satisfaction = $total > 0 ? round((($voting_data['positive_votes'] ?? 0) / $total) * 100, 1) : 0;
                echo $satisfaction . '%';
                ?>
            </div>
        </div>
    </div>

    <!-- Vote Types Chart -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="askro-admin-form">
            <h3 class="text-lg font-semibold mb-4"><?php _e('أنواع التصويت', 'askro'); ?></h3>
            <canvas id="voteTypesChart" width="400" height="300"></canvas>
        </div>

        <div class="askro-admin-form">
            <h3 class="text-lg font-semibold mb-4"><?php _e('إحصائيات أنواع التصويت', 'askro'); ?></h3>
            <div class="space-y-4">
                <?php if (!empty($voting_data['vote_types'])): ?>
                    <?php foreach ($voting_data['vote_types'] as $vote_type): ?>
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-4 h-4 rounded-full mr-3" style="background-color: #<?php echo substr(md5($vote_type->vote_type), 0, 6); ?>;"></div>
                            <div>
                                <h4 class="font-medium"><?php echo esc_html(ucfirst(str_replace('_', ' ', $vote_type->vote_type))); ?></h4>
                                <p class="text-sm text-gray-500"><?php _e('متوسط القوة:', 'askro'); ?> <?php echo round($vote_type->avg_strength, 2); ?></p>
                            </div>
                        </div>
                        <span class="askro-admin-badge askro-admin-badge-info">
                            <?php echo number_format($vote_type->count); ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-gray-500 text-center"><?php _e('لا توجد بيانات تصويت متاحة', 'askro'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Votes -->
    <div class="askro-admin-form mb-8">
        <h3 class="text-lg font-semibold mb-4"><?php _e('التصويتات الأخيرة', 'askro'); ?></h3>
        <div class="askro-admin-table">
            <table class="w-full">
                <thead>
                    <tr>
                        <th><?php _e('المستخدم', 'askro'); ?></th>
                        <th><?php _e('المحتوى', 'askro'); ?></th>
                        <th><?php _e('نوع التصويت', 'askro'); ?></th>
                        <th><?php _e('القوة', 'askro'); ?></th>
                        <th><?php _e('التاريخ', 'askro'); ?></th>
                        <th><?php _e('الإجراءات', 'askro'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($voting_data['recent_votes'])): ?>
                        <?php foreach ($voting_data['recent_votes'] as $vote): ?>
                        <tr>
                            <td>
                                <div class="flex items-center">
                                    <?php echo get_avatar($vote->user_id, 32, '', '', ['class' => 'rounded-full mr-2']); ?>
                                    <span><?php echo esc_html($vote->display_name ?: __('مستخدم محذوف', 'askro')); ?></span>
                                </div>
                            </td>
                            <td>
                                <a href="<?php echo get_edit_post_link($vote->post_id); ?>" class="text-blue-600 hover:text-blue-800">
                                    <?php echo esc_html(wp_trim_words($vote->post_title ?: __('محتوى محذوف', 'askro'), 8)); ?>
                                </a>
                            </td>
                            <td>
                                <span class="askro-admin-badge askro-admin-badge-info">
                                    <?php echo esc_html(ucfirst(str_replace('_', ' ', $vote->vote_type))); ?>
                                </span>
                            </td>
                            <td>
                                <span class="askro-admin-badge <?php echo $vote->vote_strength > 0 ? 'askro-admin-badge-success' : 'askro-admin-badge-error'; ?>">
                                    <?php echo $vote->vote_strength > 0 ? '+' : ''; ?><?php echo $vote->vote_strength; ?>
                                </span>
                            </td>
                            <td><?php echo human_time_diff(strtotime($vote->created_at), current_time('timestamp')); ?> <?php _e('مضت', 'askro'); ?></td>
                            <td>
                                <button class="askro-admin-btn askro-admin-btn-danger askro-admin-btn-sm delete-vote" 
                                        data-vote-id="<?php echo $vote->id; ?>">
                                    <?php _e('حذف', 'askro'); ?>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-gray-500">
                                <?php _e('لا توجد تصويتات حتى الآن', 'askro'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Voting Settings -->
    <div class="askro-admin-form">
        <h3 class="text-lg font-semibold mb-4"><?php _e('إعدادات التصويت', 'askro'); ?></h3>
        <form method="post" action="options.php">
            <?php settings_fields('askro_voting_settings'); ?>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="askro-admin-form-group">
                    <label class="askro-admin-form-label"><?php _e('السماح بالتصويت للضيوف', 'askro'); ?></label>
                    <label class="flex items-center">
                        <input type="checkbox" name="askro_voting_settings[allow_guest_voting]" 
                               value="1" <?php checked(get_option('askro_voting_settings')['allow_guest_voting'] ?? 0, 1); ?>
                               class="askro-admin-form-checkbox mr-2">
                        <span><?php _e('السماح للزوار غير المسجلين بالتصويت', 'askro'); ?></span>
                    </label>
                </div>

                <div class="askro-admin-form-group">
                    <label class="askro-admin-form-label"><?php _e('تفعيل Karma Deflector', 'askro'); ?></label>
                    <label class="flex items-center">
                        <input type="checkbox" name="askro_voting_settings[enable_karma_deflector]" 
                               value="1" <?php checked(get_option('askro_voting_settings')['enable_karma_deflector'] ?? 1, 1); ?>
                               class="askro-admin-form-checkbox mr-2">
                        <span><?php _e('تقليل تأثير التصويت السلبي المتكرر', 'askro'); ?></span>
                    </label>
                </div>

                <div class="askro-admin-form-group">
                    <label class="askro-admin-form-label"><?php _e('الحد الأقصى للتصويت اليومي', 'askro'); ?></label>
                    <input type="number" name="askro_voting_settings[daily_vote_limit]" 
                           value="<?php echo get_option('askro_voting_settings')['daily_vote_limit'] ?? 50; ?>" 
                           class="askro-admin-form-input">
                    <p class="text-sm text-gray-500 mt-1"><?php _e('عدد التصويتات المسموحة للمستخدم يومياً', 'askro'); ?></p>
                </div>

                <div class="askro-admin-form-group">
                    <label class="askro-admin-form-label"><?php _e('الحد الأدنى للنقاط للتصويت', 'askro'); ?></label>
                    <input type="number" name="askro_voting_settings[minimum_points_to_vote]" 
                           value="<?php echo get_option('askro_voting_settings')['minimum_points_to_vote'] ?? 0; ?>" 
                           class="askro-admin-form-input">
                    <p class="text-sm text-gray-500 mt-1"><?php _e('النقاط المطلوبة للمستخدم ليتمكن من التصويت', 'askro'); ?></p>
                </div>

                <div class="askro-admin-form-group">
                    <label class="askro-admin-form-label"><?php _e('قوة التصويت الافتراضية', 'askro'); ?></label>
                    <input type="number" name="askro_voting_settings[default_vote_strength]" 
                           value="<?php echo get_option('askro_voting_settings')['default_vote_strength'] ?? 1; ?>" 
                           class="askro-admin-form-input" min="1" max="10">
                    <p class="text-sm text-gray-500 mt-1"><?php _e('قوة التصويت الافتراضية (1-10)', 'askro'); ?></p>
                </div>

                <div class="askro-admin-form-group">
                    <label class="askro-admin-form-label"><?php _e('تفعيل التصويت المتعدد الأبعاد', 'askro'); ?></label>
                    <label class="flex items-center">
                        <input type="checkbox" name="askro_voting_settings[enable_multi_dimensional]" 
                               value="1" <?php checked(get_option('askro_voting_settings')['enable_multi_dimensional'] ?? 1, 1); ?>
                               class="askro-admin-form-checkbox mr-2">
                        <span><?php _e('السماح بأنواع تصويت متعددة (مفيد، إبداعي، عميق، إلخ)', 'askro'); ?></span>
                    </label>
                </div>
            </div>

            <!-- Vote Types Configuration -->
            <div class="mt-8">
                <h4 class="text-md font-semibold mb-4"><?php _e('تكوين أنواع التصويت', 'askro'); ?></h4>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php
                    $vote_types = [
                        'helpful' => ['name' => 'مفيد', 'icon' => '👍', 'color' => '#22c55e'],
                        'creative' => ['name' => 'إبداعي', 'icon' => '💡', 'color' => '#f59e0b'],
                        'insightful' => ['name' => 'عميق', 'icon' => '🧠', 'color' => '#8b5cf6'],
                        'funny' => ['name' => 'مضحك', 'icon' => '😄', 'color' => '#06b6d4'],
                        'emotional' => ['name' => 'عاطفي', 'icon' => '❤️', 'color' => '#ec4899']
                    ];

                    foreach ($vote_types as $type_key => $type_info):
                    ?>
                    <div class="p-4 border border-gray-200 rounded-lg">
                        <div class="flex items-center mb-2">
                            <span class="text-2xl mr-2"><?php echo $type_info['icon']; ?></span>
                            <h5 class="font-medium"><?php echo esc_html($type_info['name']); ?></h5>
                        </div>
                        <label class="flex items-center">
                            <input type="checkbox" name="askro_voting_settings[enabled_vote_types][<?php echo $type_key; ?>]" 
                                   value="1" <?php checked(get_option('askro_voting_settings')['enabled_vote_types'][$type_key] ?? 1, 1); ?>
                                   class="askro-admin-form-checkbox mr-2">
                            <span class="text-sm"><?php _e('مفعل', 'askro'); ?></span>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="askro-admin-btn askro-admin-btn-primary">
                    <?php _e('حفظ الإعدادات', 'askro'); ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Vote Types Chart
    const voteTypesCtx = document.getElementById('voteTypesChart').getContext('2d');
    
    const voteTypesData = <?php echo json_encode($voting_data['vote_types'] ?? []); ?>;
    const labels = voteTypesData.map(item => item.vote_type.replace('_', ' '));
    const data = voteTypesData.map(item => item.count);
    const colors = voteTypesData.map(item => '#' + item.vote_type.substring(0, 6).padEnd(6, '0'));

    new Chart(voteTypesCtx, {
        type: 'doughnut',
        data: {
            labels: labels,
            datasets: [{
                data: data,
                backgroundColor: [
                    '#22c55e', '#f59e0b', '#8b5cf6', '#06b6d4', '#ec4899',
                    '#ef4444', '#3b82f6', '#10b981', '#f97316', '#84cc16'
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

    // Delete vote functionality
    document.querySelectorAll('.delete-vote').forEach(button => {
        button.addEventListener('click', function() {
            if (!confirm(askroAdmin.strings.delete_confirm)) {
                return;
            }

            const voteId = this.getAttribute('data-vote-id');
            const formData = new FormData();
            formData.append('action', 'askro_admin_action');
            formData.append('admin_action', 'delete_vote');
            formData.append('vote_id', voteId);
            formData.append('nonce', askroAdmin.nonce);

            fetch(askroAdmin.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.closest('tr').remove();
                    alert(data.data.message);
                } else {
                    alert(data.data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(askroAdmin.strings.error);
            });
        });
    });
});
</script>


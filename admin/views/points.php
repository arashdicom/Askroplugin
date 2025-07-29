<?php
/**
 * Admin Points & Badges View
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

global $wpdb;

// Get data for the page
$points_data = askro_get_points_dashboard_data();
$users = get_users(['number' => 100, 'orderby' => 'display_name', 'order' => 'ASC']);

// Pagination parameters
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $per_page;
$sort_by = isset($_GET['sort']) ? sanitize_text_field($_GET['sort']) : 'date';
$sort_order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';

// Get transactions with pagination
$transactions_data = askro_get_points_transactions([
    'per_page' => $per_page,
    'page' => $current_page,
    'orderby' => $sort_by,
    'order' => $sort_order
]);
$total_transactions = $transactions_data['pagination']['total_items'];
$transactions = $transactions_data['transactions'];
$total_pages = $transactions_data['pagination']['total_pages'];
?>

<div class="askro-admin-wrap">
    <div class="askro-admin-header">
        <h1 class="askro-admin-title">
            <?php _e('النقاط والشارات', 'askro'); ?>
        </h1>
        <p class="askro-admin-subtitle">
            <?php _e('إدارة نظام النقاط والشارات والإنجازات', 'askro'); ?>
        </p>
    </div>

    <!-- Tabs Navigation -->
    <div class="askro-admin-tabs">
        <button class="askro-admin-tab active" data-tab="points"><?php _e('النقاط', 'askro'); ?></button>
        <button class="askro-admin-tab" data-tab="badges"><?php _e('الشارات', 'askro'); ?></button>
        <button class="askro-admin-tab" data-tab="leaderboard"><?php _e('لوحة المتصدرين', 'askro'); ?></button>
        <button class="askro-admin-tab" data-tab="settings"><?php _e('إعدادات النقاط', 'askro'); ?></button>
    </div>

    <!-- Points Tab -->
    <div id="points-tab" class="askro-admin-tab-content active">
        <!-- Points Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="askro-dashboard-card">
                <div class="askro-dashboard-card-icon" style="background-color: #f59e0b;">
                    <span class="dashicons dashicons-star-filled" style="color: white;"></span>
                </div>
                <h3 class="askro-dashboard-card-title"><?php _e('إجمالي النقاط الممنوحة', 'askro'); ?></h3>
                <div class="askro-dashboard-card-value">
                    <?php echo number_format($points_data['stats']['total_points'] ?? 0); ?>
                </div>
            </div>

            <div class="askro-dashboard-card">
                <div class="askro-dashboard-card-icon" style="background-color: #ef4444;">
                    <span class="dashicons dashicons-minus" style="color: white;"></span>
                </div>
                <h3 class="askro-dashboard-card-title"><?php _e('إجمالي النقاط المخصومة', 'askro'); ?></h3>
                <div class="askro-dashboard-card-value">
                    <?php 
                    $total_deducted = $wpdb->get_var(
                        "SELECT SUM(CASE WHEN points_change < 0 THEN ABS(points_change) ELSE 0 END) 
                         FROM {$wpdb->prefix}askro_points_log"
                    );
                    echo number_format($total_deducted ?? 0); 
                    ?>
                </div>
            </div>

            <div class="askro-dashboard-card">
                <div class="askro-dashboard-card-icon" style="background-color: #10b981;">
                    <span class="dashicons dashicons-groups" style="color: white;"></span>
                </div>
                <h3 class="askro-dashboard-card-title"><?php _e('المستخدمون النشطون', 'askro'); ?></h3>
                <div class="askro-dashboard-card-value">
                    <?php echo number_format($points_data['stats']['active_users'] ?? 0); ?>
                </div>
            </div>

            <div class="askro-dashboard-card">
                <div class="askro-dashboard-card-icon" style="background-color: #8b5cf6;">
                    <span class="dashicons dashicons-awards" style="color: white;"></span>
                </div>
                <h3 class="askro-dashboard-card-title"><?php _e('الشارات الممنوحة', 'askro'); ?></h3>
                <div class="askro-dashboard-card-value">
                    <?php 
                    $total_badges = $wpdb->get_var(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}askro_user_badges"
                    );
                    echo number_format($total_badges ?? 0);
                    ?>
                </div>
            </div>
        </div>

        <!-- Award Points Form -->
        <div class="askro-admin-form mb-8">
            <h3 class="text-lg font-semibold mb-4"><?php _e('منح نقاط', 'askro'); ?></h3>
            <form id="award-points-form" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="askro-admin-form-group">
                    <label class="askro-admin-form-label"><?php _e('المستخدم', 'askro'); ?></label>
                    <select name="user_id" class="askro-admin-form-select" required>
                        <option value=""><?php _e('اختر مستخدم...', 'askro'); ?></option>
                        <?php
                        $users = get_users(['number' => 100]);
                        foreach ($users as $user) {
                            echo '<option value="' . $user->ID . '">' . esc_html($user->display_name) . ' (' . $user->user_email . ')</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="askro-admin-form-group">
                    <label class="askro-admin-form-label"><?php _e('النقاط', 'askro'); ?></label>
                    <input type="number" name="points" class="askro-admin-form-input" required placeholder="100">
                </div>

                <div class="askro-admin-form-group">
                    <label class="askro-admin-form-label"><?php _e('السبب', 'askro'); ?></label>
                    <input type="text" name="reason" class="askro-admin-form-input" placeholder="<?php _e('سبب منح النقاط', 'askro'); ?>">
                </div>

                <div class="askro-admin-form-group">
                    <label class="askro-admin-form-label">&nbsp;</label>
                    <button type="submit" class="askro-admin-btn askro-admin-btn-primary w-full">
                        <?php _e('منح النقاط', 'askro'); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Recent Transactions -->
        <div class="askro-admin-form">
            <h3 class="text-lg font-semibold mb-4"><?php _e('المعاملات الأخيرة', 'askro'); ?></h3>
            <div class="askro-admin-table">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th><?php _e('المستخدم', 'askro'); ?></th>
                            <th><?php _e('النقاط', 'askro'); ?></th>
                            <th><?php _e('السبب', 'askro'); ?></th>
                            <th><?php _e('التاريخ', 'askro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($points_data['recent_transactions'])): ?>
                            <?php foreach ($points_data['recent_transactions'] as $transaction): ?>
                            <tr>
                                <td>
                                    <div class="flex items-center">
                                        <?php echo get_avatar($transaction->user_id, 32, '', '', ['class' => 'rounded-full mr-2']); ?>
                                        <span><?php echo esc_html($transaction->display_name); ?></span>
                                    </div>
                                </td>
                                <td>
                                    <span class="askro-admin-badge <?php echo $transaction->points > 0 ? 'askro-admin-badge-success' : 'askro-admin-badge-error'; ?>">
                                        <?php echo $transaction->points > 0 ? '+' : ''; ?><?php echo $transaction->points; ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html($transaction->reason ?: __('غير محدد', 'askro')); ?></td>
                                <td><?php echo human_time_diff(strtotime($transaction->created_at), current_time('timestamp')); ?> <?php _e('مضت', 'askro'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-gray-500">
                                    <?php _e('لا توجد معاملات حتى الآن', 'askro'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Badges Tab -->
    <div id="badges-tab" class="askro-admin-tab-content">
        <!-- Badge Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <?php if (!empty($points_data['badges_stats'])): ?>
                <?php foreach ($points_data['badges_stats'] as $badge_stat): ?>
                <div class="askro-dashboard-card">
                    <div class="askro-dashboard-card-icon" style="background-color: #<?php echo substr(md5($badge_stat->badge_name), 0, 6); ?>;">
                        <span class="dashicons dashicons-awards" style="color: white;"></span>
                    </div>
                    <h3 class="askro-dashboard-card-title">
                        <?php echo esc_html(ucfirst(str_replace('_', ' ', $badge_stat->badge_name))); ?>
                    </h3>
                    <div class="askro-dashboard-card-value"><?php echo $badge_stat->count; ?></div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Award Badge Form -->
        <div class="askro-admin-form mb-8">
            <h3 class="text-lg font-semibold mb-4"><?php _e('منح شارة', 'askro'); ?></h3>
            <form id="award-badge-form" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="askro-admin-form-group">
                    <label class="askro-admin-form-label"><?php _e('المستخدم', 'askro'); ?></label>
                    <select name="user_id" class="askro-admin-form-select" required>
                        <option value=""><?php _e('اختر مستخدم...', 'askro'); ?></option>
                        <?php
                        foreach ($users as $user) {
                            echo '<option value="' . $user->ID . '">' . esc_html($user->display_name) . ' (' . $user->user_email . ')</option>';
                        }
                        ?>
                    </select>
                </div>

                <div class="askro-admin-form-group">
                    <label class="askro-admin-form-label"><?php _e('نوع الشارة', 'askro'); ?></label>
                    <select name="badge_type" class="askro-admin-form-select" required>
                        <option value=""><?php _e('اختر نوع الشارة...', 'askro'); ?></option>
                        <option value="first_question"><?php _e('أول سؤال', 'askro'); ?></option>
                        <option value="first_answer"><?php _e('أول إجابة', 'askro'); ?></option>
                        <option value="helpful_member"><?php _e('عضو مفيد', 'askro'); ?></option>
                        <option value="expert"><?php _e('خبير', 'askro'); ?></option>
                        <option value="top_contributor"><?php _e('مساهم متميز', 'askro'); ?></option>
                        <option value="community_leader"><?php _e('قائد المجتمع', 'askro'); ?></option>
                    </select>
                </div>

                <div class="askro-admin-form-group">
                    <label class="askro-admin-form-label">&nbsp;</label>
                    <button type="submit" class="askro-admin-btn askro-admin-btn-primary w-full">
                        <?php _e('منح الشارة', 'askro'); ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Available Badges -->
        <div class="askro-admin-form">
            <h3 class="text-lg font-semibold mb-4"><?php _e('الشارات المتاحة', 'askro'); ?></h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php
                $available_badges = [
                    'first_question' => ['name' => 'أول سؤال', 'description' => 'لطرح أول سؤال', 'color' => 'bg-blue-100 text-blue-800'],
                    'first_answer' => ['name' => 'أول إجابة', 'description' => 'لتقديم أول إجابة', 'color' => 'bg-green-100 text-green-800'],
                    'helpful_member' => ['name' => 'عضو مفيد', 'description' => 'للحصول على 50 تصويت إيجابي', 'color' => 'bg-yellow-100 text-yellow-800'],
                    'expert' => ['name' => 'خبير', 'description' => 'للحصول على 100 نقطة', 'color' => 'bg-purple-100 text-purple-800'],
                    'top_contributor' => ['name' => 'مساهم متميز', 'description' => 'للحصول على 500 نقطة', 'color' => 'bg-red-100 text-red-800'],
                    'community_leader' => ['name' => 'قائد المجتمع', 'description' => 'للحصول على 1000 نقطة', 'color' => 'bg-indigo-100 text-indigo-800']
                ];

                foreach ($available_badges as $badge_key => $badge_info):
                ?>
                <div class="p-4 border border-gray-200 rounded-lg">
                    <div class="flex items-center mb-2">
                        <span class="dashicons dashicons-awards text-gray-600 mr-2"></span>
                        <h4 class="font-medium"><?php echo esc_html($badge_info['name']); ?></h4>
                    </div>
                    <p class="text-sm text-gray-600 mb-3"><?php echo esc_html($badge_info['description']); ?></p>
                    <span class="askro-admin-badge <?php echo $badge_info['color']; ?>">
                        <?php echo esc_html($badge_key); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Leaderboard Tab -->
    <div id="leaderboard-tab" class="askro-admin-tab-content">
        <div class="askro-admin-form">
            <h3 class="text-lg font-semibold mb-4"><?php _e('لوحة المتصدرين', 'askro'); ?></h3>
            <div class="askro-admin-table">
                <table class="w-full">
                    <thead>
                        <tr>
                            <th><?php _e('الترتيب', 'askro'); ?></th>
                            <th><?php _e('المستخدم', 'askro'); ?></th>
                            <th><?php _e('النقاط', 'askro'); ?></th>
                            <th><?php _e('الأسئلة', 'askro'); ?></th>
                            <th><?php _e('الإجابات', 'askro'); ?></th>
                            <th><?php _e('الشارات', 'askro'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($points_data['top_users'])): ?>
                            <?php foreach ($points_data['top_users'] as $index => $user_points): ?>
                            <?php 
                            $user = get_userdata($user_points->user_id);
                            $user_data = askro_get_user_data($user_points->user_id);
                            ?>
                            <tr>
                                <td>
                                    <span class="askro-admin-badge askro-admin-badge-info">
                                        #<?php echo $index + 1; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center">
                                        <?php echo get_avatar($user->ID, 32, '', '', ['class' => 'rounded-full mr-2']); ?>
                                        <div>
                                            <div class="font-medium"><?php echo esc_html($user->display_name); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo esc_html($user->user_email); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="askro-admin-badge askro-admin-badge-success">
                                        <?php echo number_format($user_points->total_points); ?>
                                    </span>
                                </td>
                                <td><?php echo $user_data['questions_count'] ?? 0; ?></td>
                                <td><?php echo $user_data['answers_count'] ?? 0; ?></td>
                                <td><?php echo count($user_data['badges'] ?? []); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-gray-500">
                                    <?php _e('لا توجد بيانات متاحة', 'askro'); ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Settings Tab -->
    <div id="settings-tab" class="askro-admin-tab-content">
        <div class="askro-admin-form">
            <h3 class="text-lg font-semibold mb-4"><?php _e('إعدادات النقاط', 'askro'); ?></h3>
            <form method="post" action="options.php">
                <?php settings_fields('askro_points_settings'); ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="askro-admin-form-group">
                        <label class="askro-admin-form-label"><?php _e('نقاط طرح سؤال', 'askro'); ?></label>
                        <input type="number" name="askro_points_settings[question_points]" 
                               value="<?php echo get_option('askro_points_settings')['question_points'] ?? 5; ?>" 
                               class="askro-admin-form-input">
                        <p class="text-sm text-gray-500 mt-1"><?php _e('النقاط التي يحصل عليها المستخدم عند طرح سؤال', 'askro'); ?></p>
                    </div>

                    <div class="askro-admin-form-group">
                        <label class="askro-admin-form-label"><?php _e('نقاط تقديم إجابة', 'askro'); ?></label>
                        <input type="number" name="askro_points_settings[answer_points]" 
                               value="<?php echo get_option('askro_points_settings')['answer_points'] ?? 10; ?>" 
                               class="askro-admin-form-input">
                        <p class="text-sm text-gray-500 mt-1"><?php _e('النقاط التي يحصل عليها المستخدم عند تقديم إجابة', 'askro'); ?></p>
                    </div>

                    <div class="askro-admin-form-group">
                        <label class="askro-admin-form-label"><?php _e('نقاط الإجابة المقبولة', 'askro'); ?></label>
                        <input type="number" name="askro_points_settings[accepted_answer_points]" 
                               value="<?php echo get_option('askro_points_settings')['accepted_answer_points'] ?? 25; ?>" 
                               class="askro-admin-form-input">
                        <p class="text-sm text-gray-500 mt-1"><?php _e('النقاط الإضافية للإجابة المقبولة', 'askro'); ?></p>
                    </div>

                    <div class="askro-admin-form-group">
                        <label class="askro-admin-form-label"><?php _e('نقاط التصويت الإيجابي', 'askro'); ?></label>
                        <input type="number" name="askro_points_settings[upvote_points]" 
                               value="<?php echo get_option('askro_points_settings')['upvote_points'] ?? 2; ?>" 
                               class="askro-admin-form-input">
                        <p class="text-sm text-gray-500 mt-1"><?php _e('النقاط التي يحصل عليها المستخدم عند تلقي تصويت إيجابي', 'askro'); ?></p>
                    </div>

                    <div class="askro-admin-form-group">
                        <label class="askro-admin-form-label"><?php _e('خصم نقاط التصويت السلبي', 'askro'); ?></label>
                        <input type="number" name="askro_points_settings[downvote_penalty]" 
                               value="<?php echo get_option('askro_points_settings')['downvote_penalty'] ?? 1; ?>" 
                               class="askro-admin-form-input">
                        <p class="text-sm text-gray-500 mt-1"><?php _e('النقاط التي تُخصم عند تلقي تصويت سلبي', 'askro'); ?></p>
                    </div>

                    <div class="askro-admin-form-group">
                        <label class="askro-admin-form-label"><?php _e('الحد الأدنى للنقاط', 'askro'); ?></label>
                        <input type="number" name="askro_points_settings[minimum_points]" 
                               value="<?php echo get_option('askro_points_settings')['minimum_points'] ?? 0; ?>" 
                               class="askro-admin-form-input">
                        <p class="text-sm text-gray-500 mt-1"><?php _e('أقل عدد نقاط يمكن أن يصل إليه المستخدم', 'askro'); ?></p>
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
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching
    const tabs = document.querySelectorAll('.askro-admin-tab');
    const tabContents = document.querySelectorAll('.askro-admin-tab-content');

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const targetTab = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and contents
            tabs.forEach(t => t.classList.remove('active'));
            tabContents.forEach(tc => tc.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding content
            this.classList.add('active');
            document.getElementById(targetTab + '-tab').classList.add('active');
        });
    });

    // Award points form
    document.getElementById('award-points-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'askro_admin_action');
        formData.append('admin_action', 'award_points');
        formData.append('nonce', askroAdmin.nonce);

        fetch(askroAdmin.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.data.message);
                location.reload();
            } else {
                alert(data.data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert(askroAdmin.strings.error);
        });
    });

    // Award badge form
    document.getElementById('award-badge-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'askro_admin_action');
        formData.append('admin_action', 'award_badge');
        formData.append('nonce', askroAdmin.nonce);

        fetch(askroAdmin.ajax_url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.data.message);
                location.reload();
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
</script>


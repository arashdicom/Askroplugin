<?php
/**
 * Archive Template - Questions Listing
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

// Get template data
$questions = isset($args['questions']) ? $args['questions'] : [];
$pagination = isset($args['pagination']) ? $args['pagination'] : [];
$filters = isset($args['filters']) ? $args['filters'] : [];
$sort_options = isset($args['sort_options']) ? $args['sort_options'] : [];
?>

<div class="askme-container">
    <div class="askme-layout">
        <!-- Main Content -->
        <div class="askme-main-content">
            <!-- Header -->
            <div class="askme-archive-header mb-8">
                <h1 class="askme-page-title text-3xl font-bold text-gray-900 mb-4">
                    <?php _e('الأسئلة', 'askro'); ?>
                </h1>
                
                <!-- Toolbar -->
                <div class="askme-toolbar bg-white rounded-xl shadow-md border border-gray-100 p-4">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Sort Options -->
                        <div class="askme-sort-options">
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($sort_options as $sort_key => $sort_label): ?>
                                    <button class="askme-sort-btn px-4 py-2 rounded-lg border border-gray-200 hover:border-indigo-300 hover:bg-indigo-50 transition-all duration-200 text-sm font-medium <?php echo ($filters['sort'] === $sort_key) ? 'bg-indigo-100 border-indigo-300 text-indigo-700' : 'text-gray-700 hover:text-indigo-700'; ?>" 
                                            data-sort="<?php echo esc_attr($sort_key); ?>">
                                        <?php echo esc_html($sort_label); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Search and Filter -->
                        <div class="askme-search-filter flex flex-col sm:flex-row gap-3">
                            <!-- Search -->
                            <div class="askme-search relative">
                                <input type="text" 
                                       id="askro-search-input"
                                       class="askme-search-input w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" 
                                       placeholder="<?php _e('البحث في الأسئلة...', 'askro'); ?>"
                                       value="<?php echo esc_attr($filters['search'] ?? ''); ?>">
                                <div class="askme-search-icon absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                                    🔍
                                </div>
                                <div id="askro-search-suggestions" class="askme-search-suggestions absolute top-full left-0 right-0 bg-white border border-gray-200 rounded-lg shadow-lg z-50 hidden"></div>
                            </div>
                            
                            <!-- Filter Button -->
                            <button class="askme-filter-btn px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors duration-200 text-sm font-medium flex items-center gap-2">
                                <span>🔧</span>
                                <?php _e('تصفية', 'askro'); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Questions List -->
            <div class="askme-questions-container">
                <?php if (!empty($questions)): ?>
                    <div class="askme-questions-list">
                        <?php foreach ($questions as $question): ?>
                            <?php echo $this->render_question_card($question->ID); ?>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if (!empty($pagination)): ?>
                        <div class="askme-pagination mt-8">
                            <nav class="askme-pagination-nav flex items-center justify-center gap-2">
                                <?php if ($pagination['prev_url']): ?>
                                    <a href="<?php echo esc_url($pagination['prev_url']); ?>" 
                                       class="askme-pagination-link px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                        <?php _e('السابق', 'askro'); ?>
                                    </a>
                                <?php endif; ?>
                                
                                <?php foreach ($pagination['pages'] as $page): ?>
                                    <?php if ($page['current']): ?>
                                        <span class="askme-pagination-current px-4 py-2 bg-indigo-600 text-white rounded-lg">
                                            <?php echo esc_html($page['number']); ?>
                                        </span>
                                    <?php else: ?>
                                        <a href="<?php echo esc_url($page['url']); ?>" 
                                           class="askme-pagination-link px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                            <?php echo esc_html($page['number']); ?>
                                        </a>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                
                                <?php if ($pagination['next_url']): ?>
                                    <a href="<?php echo esc_url($pagination['next_url']); ?>" 
                                       class="askme-pagination-link px-4 py-2 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                        <?php _e('التالي', 'askro'); ?>
                                    </a>
                                <?php endif; ?>
                            </nav>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <!-- No Questions -->
                    <div class="askme-no-questions bg-white rounded-xl shadow-md border border-gray-100 p-8 text-center">
                        <div class="askme-no-questions-icon text-6xl mb-4">❓</div>
                        <h3 class="askme-no-questions-title text-xl font-bold text-gray-900 mb-2">
                            <?php _e('لا توجد أسئلة بعد', 'askro'); ?>
                        </h3>
                        <p class="askme-no-questions-description text-gray-600 mb-6">
                            <?php _e('كن أول من يطرح سؤالاً في هذا المجتمع!', 'askro'); ?>
                        </p>
                        <a href="<?php echo esc_url(askro_get_url('ask_question')); ?>" 
                           class="askme-ask-question-btn inline-flex items-center gap-2 px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200 font-medium">
                            <span>➕</span>
                            <?php _e('اطرح سؤالاً', 'askro'); ?>
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="askme-sidebar">
            <!-- Community Stats -->
            <div class="askme-sidebar-module askme-community-stats bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-6">
                <h3 class="askme-module-title text-lg font-bold text-gray-900 mb-4">
                    <?php _e('إحصائيات المجتمع', 'askro'); ?>
                </h3>
                <div class="askme-stats-grid grid grid-cols-2 gap-4">
                    <div class="askme-stat-item text-center">
                        <div class="askme-stat-value text-2xl font-bold text-indigo-600">
                            <?php echo esc_html(askro_get_option('total_questions', 0)); ?>
                        </div>
                        <div class="askme-stat-label text-sm text-gray-600">
                            <?php _e('أسئلة', 'askro'); ?>
                        </div>
                    </div>
                    <div class="askme-stat-item text-center">
                        <div class="askme-stat-value text-2xl font-bold text-green-600">
                            <?php echo esc_html(askro_get_option('total_answers', 0)); ?>
                        </div>
                        <div class="askme-stat-label text-sm text-gray-600">
                            <?php _e('إجابات', 'askro'); ?>
                        </div>
                    </div>
                    <div class="askme-stat-item text-center">
                        <div class="askme-stat-value text-2xl font-bold text-purple-600">
                            <?php echo esc_html(askro_get_option('total_users', 0)); ?>
                        </div>
                        <div class="askme-stat-label text-sm text-gray-600">
                            <?php _e('مستخدمين', 'askro'); ?>
                        </div>
                    </div>
                    <div class="askme-stat-item text-center">
                        <div class="askme-stat-value text-2xl font-bold text-orange-600">
                            <?php echo esc_html(askro_get_option('total_solved', 0)); ?>
                        </div>
                        <div class="askme-stat-label text-sm text-gray-600">
                            <?php _e('محلولة', 'askro'); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Contributors -->
            <div class="askme-sidebar-module askme-top-contributors bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-6">
                <h3 class="askme-module-title text-lg font-bold text-gray-900 mb-4">
                    <?php _e('أفضل المساهمين', 'askro'); ?>
                </h3>
                <div class="askme-leaderboard-list space-y-3">
                    <?php
                    $top_contributors = get_users([
                        'number' => 5,
                        'meta_key' => 'askro_points',
                        'orderby' => 'meta_value_num',
                        'order' => 'DESC'
                    ]);
                    
                    foreach ($top_contributors as $index => $user):
                        $user_data = askro_get_user_data($user->ID);
                    ?>
                        <div class="askme-leaderboard-item">
                            <div class="askme-leaderboard-rank">
                                <?php echo $index + 1; ?>
                            </div>
                            <div class="askme-leaderboard-avatar">
                                <?php echo get_avatar($user->ID, 40); ?>
                            </div>
                            <div class="askme-leaderboard-info">
                                <div class="askme-leaderboard-name">
                                    <?php echo esc_html($user_data['display_name']); ?>
                                </div>
                                <div class="askme-leaderboard-points">
                                    <?php echo esc_html($user_data['points']); ?> نقطة
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Categories -->
            <div class="askme-sidebar-module askme-categories bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-6">
                <h3 class="askme-module-title text-lg font-bold text-gray-900 mb-4">
                    <?php _e('الفئات', 'askro'); ?>
                </h3>
                <div class="askme-categories-list space-y-2">
                    <?php
                    $categories = get_terms([
                        'taxonomy' => 'askro_question_category',
                        'hide_empty' => true,
                        'number' => 10
                    ]);
                    
                    foreach ($categories as $category):
                    ?>
                        <a href="<?php echo esc_url(get_term_link($category)); ?>" 
                           class="askme-category-link flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                            <span class="askme-category-name text-gray-700">
                                <?php echo esc_html($category->name); ?>
                            </span>
                            <span class="askme-category-count text-sm text-gray-500 bg-gray-100 px-2 py-1 rounded-full">
                                <?php echo esc_html($category->count); ?>
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Tags -->
            <div class="askme-sidebar-module askme-tags bg-white rounded-xl shadow-md border border-gray-100 p-6 mb-6">
                <h3 class="askme-module-title text-lg font-bold text-gray-900 mb-4">
                    <?php _e('العلامات الشائعة', 'askro'); ?>
                </h3>
                <div class="askme-tags-list flex flex-wrap gap-2">
                    <?php
                    $tags = get_terms([
                        'taxonomy' => 'askro_question_tag',
                        'hide_empty' => true,
                        'number' => 20
                    ]);
                    
                    foreach ($tags as $tag):
                    ?>
                        <a href="<?php echo esc_url(get_term_link($tag)); ?>" 
                           class="askme-tag-link px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full hover:bg-indigo-200 transition-colors duration-200 text-sm">
                            <?php echo esc_html($tag->name); ?>
                            <span class="askme-tag-count text-xs text-indigo-600">
                                (<?php echo esc_html($tag->count); ?>)
                            </span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Ask Question CTA -->
            <div class="askme-sidebar-module askme-ask-question-cta bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl p-6 text-center text-white">
                <div class="askme-cta-icon text-4xl mb-4">💡</div>
                <h3 class="askme-cta-title text-lg font-bold mb-2">
                    <?php _e('لديك سؤال؟', 'askro'); ?>
                </h3>
                <p class="askme-cta-description text-indigo-100 mb-4">
                    <?php _e('اطرح سؤالك واحصل على إجابات من المجتمع', 'askro'); ?>
                </p>
                <a href="<?php echo esc_url(askro_get_url('ask_question')); ?>" 
                   class="askme-cta-btn inline-block w-full px-4 py-2 bg-white text-indigo-600 rounded-lg hover:bg-gray-100 transition-colors duration-200 font-medium">
                    <?php _e('اطرح سؤالاً', 'askro'); ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filter Modal -->
<div id="askme-filter-modal" class="askme-modal fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="askme-modal-content max-w-md mx-auto mt-20 bg-white rounded-xl shadow-xl">
        <div class="askme-modal-header flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="askme-modal-title text-lg font-bold text-gray-900">
                <?php _e('تصفية الأسئلة', 'askro'); ?>
            </h3>
            <button class="askme-modal-close text-gray-400 hover:text-gray-600 text-2xl">
                &times;
            </button>
        </div>
        <div class="askme-modal-body p-6">
            <form class="askme-filter-form space-y-4">
                <!-- Status Filter -->
                <div class="askme-filter-group">
                    <label class="askme-filter-label block text-sm font-medium text-gray-700 mb-2">
                        <?php _e('حالة السؤال', 'askro'); ?>
                    </label>
                    <select name="status" class="askme-filter-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value=""><?php _e('جميع الحالات', 'askro'); ?></option>
                        <option value="open" <?php selected($filters['status'] ?? '', 'open'); ?>><?php _e('مفتوح', 'askro'); ?></option>
                        <option value="solved" <?php selected($filters['status'] ?? '', 'solved'); ?>><?php _e('محلول', 'askro'); ?></option>
                        <option value="closed" <?php selected($filters['status'] ?? '', 'closed'); ?>><?php _e('مغلق', 'askro'); ?></option>
                    </select>
                </div>
                
                <!-- Category Filter -->
                <div class="askme-filter-group">
                    <label class="askme-filter-label block text-sm font-medium text-gray-700 mb-2">
                        <?php _e('الفئة', 'askro'); ?>
                    </label>
                    <select name="category" class="askme-filter-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value=""><?php _e('جميع الفئات', 'askro'); ?></option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($filters['category'] ?? '', $category->slug); ?>>
                                <?php echo esc_html($category->name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Date Filter -->
                <div class="askme-filter-group">
                    <label class="askme-filter-label block text-sm font-medium text-gray-700 mb-2">
                        <?php _e('الفترة الزمنية', 'askro'); ?>
                    </label>
                    <select name="date" class="askme-filter-select w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value=""><?php _e('جميع الأوقات', 'askro'); ?></option>
                        <option value="today" <?php selected($filters['date'] ?? '', 'today'); ?>><?php _e('اليوم', 'askro'); ?></option>
                        <option value="week" <?php selected($filters['date'] ?? '', 'week'); ?>><?php _e('هذا الأسبوع', 'askro'); ?></option>
                        <option value="month" <?php selected($filters['date'] ?? '', 'month'); ?>><?php _e('هذا الشهر', 'askro'); ?></option>
                        <option value="year" <?php selected($filters['date'] ?? '', 'year'); ?>><?php _e('هذا العام', 'askro'); ?></option>
                    </select>
                </div>
            </form>
        </div>
        <div class="askme-modal-footer flex items-center justify-end gap-3 p-6 border-t border-gray-200">
            <button class="askme-modal-cancel px-4 py-2 text-gray-700 hover:text-gray-900">
                <?php _e('إلغاء', 'askro'); ?>
            </button>
            <button class="askme-modal-apply px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors duration-200">
                <?php _e('تطبيق', 'askro'); ?>
            </button>
        </div>
    </div>
</div> 

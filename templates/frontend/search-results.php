<?php
/**
 * Search Results Template
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

// Get search parameters
$search_query = sanitize_text_field($_GET['s'] ?? '');
$search_type = sanitize_text_field($_GET['type'] ?? 'questions');
$search_category = sanitize_text_field($_GET['category'] ?? '');
$search_tag = sanitize_text_field($_GET['tag'] ?? '');
$search_status = sanitize_text_field($_GET['status'] ?? '');
$search_author = sanitize_text_field($_GET['author'] ?? '');
$search_date = sanitize_text_field($_GET['date'] ?? '');

// Get search settings from admin
$results_per_page = askro_get_option('search_results_per_page', 10);
$enable_advanced_search = askro_get_option('enable_advanced_search', true);
$search_highlight = askro_get_option('search_highlight', true);

// Build search query
$search_args = [
    'post_type' => $search_type === 'answers' ? 'askro_answer' : 'askro_question',
    'post_status' => 'publish',
    'posts_per_page' => $results_per_page,
    'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
    's' => $search_query
];

// Add taxonomy filters
if ($search_category || $search_tag) {
    $search_args['tax_query'] = [];
    
    if ($search_category) {
        $search_args['tax_query'][] = [
            'taxonomy' => 'askro_question_category',
            'field' => 'slug',
            'terms' => $search_category
        ];
    }
    
    if ($search_tag) {
        $search_args['tax_query'][] = [
            'taxonomy' => 'askro_question_tag',
            'field' => 'slug',
            'terms' => $search_tag
        ];
    }
}

// Add meta filters
if ($search_status) {
    $search_args['meta_query'][] = [
        'key' => 'askro_question_status',
        'value' => $search_status,
        'compare' => '='
    ];
}

if ($search_author) {
    $search_args['author'] = $search_author;
}

if ($search_date) {
    $search_args['date_query'] = [
        [
            'after' => $search_date,
            'inclusive' => true
        ]
    ];
}

// Perform search
$search_query_obj = new WP_Query($search_args);
$total_results = $search_query_obj->found_posts;

// Get categories and tags for filters
$categories = get_terms([
    'taxonomy' => 'askro_question_category',
    'hide_empty' => true,
    'orderby' => 'name',
    'order' => 'ASC'
]);

$tags = get_terms([
    'taxonomy' => 'askro_question_tag',
    'hide_empty' => true,
    'orderby' => 'name',
    'order' => 'ASC'
]);

$statuses = [
    'open' => __('مفتوح', 'askro'),
    'solved' => __('محلول', 'askro'),
    'closed' => __('مغلق', 'askro'),
    'urgent' => __('عاجل', 'askro')
];
?>

<div class="askme-container askme-search-results">
    <div class="askme-main-content">
        
        <!-- Search Header -->
        <div class="askme-search-header">
            <h1 class="askme-search-title">
                <?php if ($search_query): ?>
                    <?php printf(__('نتائج البحث عن: "%s"', 'askro'), esc_html($search_query)); ?>
                <?php else: ?>
                    <?php _e('نتائج البحث', 'askro'); ?>
                <?php endif; ?>
            </h1>
            
            <?php if ($total_results > 0): ?>
                <p class="askme-search-subtitle">
                    <?php printf(
                        _n(
                            'تم العثور على %d نتيجة',
                            'تم العثور على %d نتيجة',
                            $total_results,
                            'askro'
                        ),
                        number_format($total_results)
                    ); ?>
                </p>
            <?php else: ?>
                <p class="askme-search-subtitle"><?php _e('لم يتم العثور على نتائج', 'askro'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Search Form -->
        <div class="askme-search-form-container">
            <form method="get" class="askme-search-form" id="askme-search-form">
                <div class="askme-search-input-group">
                    <input 
                        type="text" 
                        name="s" 
                        value="<?php echo esc_attr($search_query); ?>" 
                        placeholder="<?php _e('ابحث في الأسئلة والإجابات...', 'askro'); ?>"
                        class="askme-search-input"
                    >
                    <button type="submit" class="askme-search-btn">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                    </button>
                </div>
                
                <?php if ($enable_advanced_search): ?>
                    <div class="askme-advanced-search" id="askme-advanced-search">
                        <div class="askme-search-filters">
                            <div class="askme-filter-group">
                                <label for="search-type" class="askme-filter-label"><?php _e('نوع البحث:', 'askro'); ?></label>
                                <select name="type" id="search-type" class="askme-filter-select">
                                    <option value="questions" <?php selected($search_type, 'questions'); ?>><?php _e('الأسئلة', 'askro'); ?></option>
                                    <option value="answers" <?php selected($search_type, 'answers'); ?>><?php _e('الإجابات', 'askro'); ?></option>
                                </select>
                            </div>
                            
                            <div class="askme-filter-group">
                                <label for="search-category" class="askme-filter-label"><?php _e('التصنيف:', 'askro'); ?></label>
                                <select name="category" id="search-category" class="askme-filter-select">
                                    <option value=""><?php _e('جميع التصنيفات', 'askro'); ?></option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo esc_attr($category->slug); ?>" <?php selected($search_category, $category->slug); ?>>
                                            <?php echo esc_html($category->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="askme-filter-group">
                                <label for="search-status" class="askme-filter-label"><?php _e('الحالة:', 'askro'); ?></label>
                                <select name="status" id="search-status" class="askme-filter-select">
                                    <option value=""><?php _e('جميع الحالات', 'askro'); ?></option>
                                    <?php foreach ($statuses as $status => $label): ?>
                                        <option value="<?php echo esc_attr($status); ?>" <?php selected($search_status, $status); ?>>
                                            <?php echo esc_html($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="askme-filter-group">
                                <label for="search-date" class="askme-filter-label"><?php _e('من تاريخ:', 'askro'); ?></label>
                                <input 
                                    type="date" 
                                    name="date" 
                                    id="search-date" 
                                    value="<?php echo esc_attr($search_date); ?>"
                                    class="askme-filter-input"
                                >
                            </div>
                        </div>
                        
                        <div class="askme-search-actions">
                            <button type="submit" class="askme-btn askme-btn-primary">
                                <?php _e('بحث', 'askro'); ?>
                            </button>
                            <button type="button" class="askme-btn askme-btn-secondary" id="askme-clear-filters">
                                <?php _e('مسح الفلاتر', 'askro'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <button type="button" class="askme-toggle-advanced" id="askme-toggle-advanced">
                        <span class="askme-toggle-text"><?php _e('البحث المتقدم', 'askro'); ?></span>
                        <svg class="askme-toggle-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="6,9 12,15 18,9"></polyline>
                        </svg>
                    </button>
                <?php endif; ?>
            </form>
        </div>

        <!-- Search Results -->
        <?php if ($search_query_obj->have_posts()): ?>
            <div class="askme-search-results-list">
                <?php while ($search_query_obj->have_posts()): $search_query_obj->the_post(); ?>
                    <?php
                    $post_type = get_post_type();
                    $is_question = ($post_type === 'askro_question');
                    $is_answer = ($post_type === 'askro_answer');
                    
                    if ($is_question) {
                        $question_data = askro_get_question_data(get_the_ID());
                        $author_data = askro_get_user_data(get_the_author_meta('ID'));
                        $answers_count = askro_get_question_answers_count(get_the_ID());
                        $votes_count = askro_get_total_votes(get_the_ID());
                        $views_count = askro_get_post_views(get_the_ID());
                    } elseif ($is_answer) {
                        $question_id = get_post_meta(get_the_ID(), 'askro_question_id', true);
                        $question = get_post($question_id);
                        $author_data = askro_get_user_data(get_the_author_meta('ID'));
                        $votes_count = askro_get_total_votes(get_the_ID());
                        $is_best_answer = get_post_meta(get_the_ID(), 'askro_best_answer', true);
                    }
                    ?>
                    
                    <div class="askme-search-result-item">
                        <div class="askme-result-type">
                            <?php if ($is_question): ?>
                                <span class="askme-type-badge askme-type-question">❓ <?php _e('سؤال', 'askro'); ?></span>
                            <?php else: ?>
                                <span class="askme-type-badge askme-type-answer">💬 <?php _e('إجابة', 'askro'); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="askme-result-content">
                            <div class="askme-result-title">
                                <?php if ($is_question): ?>
                                    <a href="<?php echo esc_url(askro_get_question_url(get_the_ID())); ?>" class="askro-result-link">
                                        <?php 
                                        if ($search_highlight && $search_query) {
                                            echo preg_replace('/(' . preg_quote($search_query, '/') . ')/i', '<mark>$1</mark>', get_the_title());
                                        } else {
                                            the_title();
                                        }
                                        ?>
                                    </a>
                                <?php else: ?>
                                    <a href="<?php echo esc_url(askro_get_question_url($question_id)); ?>" class="askro-result-link">
                                        <?php echo esc_html($question->post_title); ?>
                                    </a>
                                    <?php if ($is_best_answer): ?>
                                        <span class="askme-best-badge">🏆 <?php _e('أفضل إجابة', 'askro'); ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            
                            <div class="askme-result-excerpt">
                                <?php 
                                $excerpt = wp_trim_words(get_the_excerpt(), 30);
                                if ($search_highlight && $search_query) {
                                    echo preg_replace('/(' . preg_quote($search_query, '/') . ')/i', '<mark>$1</mark>', $excerpt);
                                } else {
                                    echo $excerpt;
                                }
                                ?>
                            </div>
                            
                            <div class="askme-result-meta">
                                <div class="askme-result-author">
                                    <img src="<?php echo esc_url($author_data['avatar']); ?>" alt="<?php echo esc_attr($author_data['display_name']); ?>" class="askme-author-avatar">
                                    <span class="askme-author-name"><?php echo esc_html($author_data['display_name']); ?></span>
                                    <span class="askme-author-rank"><?php echo esc_html($author_data['rank']['current']['name']); ?></span>
                                </div>
                                
                                <div class="askme-result-stats">
                                    <?php if ($is_question): ?>
                                        <span class="askme-stat-item">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                                            </svg>
                                            <?php echo number_format($answers_count); ?>
                                        </span>
                                        <span class="askme-stat-item">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                            <?php echo number_format($views_count); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <span class="askme-stat-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <polyline points="6,9 12,15 18,9"></polyline>
                                        </svg>
                                        <?php echo number_format($votes_count); ?>
                                    </span>
                                    
                                    <span class="askme-stat-item">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12,6 12,12 16,14"></polyline>
                                        </svg>
                                        <?php echo askro_time_ago(get_the_date()); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($is_question): ?>
                                <div class="askme-result-categories">
                                    <?php
                                    $question_categories = get_the_terms(get_the_ID(), 'askro_question_category');
                                    $question_tags = get_the_terms(get_the_ID(), 'askro_question_tag');
                                    
                                    if ($question_categories): ?>
                                        <div class="askme-result-cats">
                                            <?php foreach ($question_categories as $category): ?>
                                                <a href="<?php echo get_term_link($category); ?>" class="askme-category-link">
                                                    <?php echo esc_html($category->name); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($question_tags): ?>
                                        <div class="askme-result-tags">
                                            <?php foreach ($question_tags as $tag): ?>
                                                <a href="<?php echo get_term_link($tag); ?>" class="askme-tag-link">
                                                    #<?php echo esc_html($tag->name); ?>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Pagination -->
            <?php if ($search_query_obj->max_num_pages > 1): ?>
                <div class="askme-pagination">
                    <?php
                    echo paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'current' => max(1, get_query_var('paged')),
                        'total' => $search_query_obj->max_num_pages,
                        'prev_text' => __('السابق', 'askro'),
                        'next_text' => __('التالي', 'askro'),
                        'type' => 'list'
                    ]);
                    ?>
                </div>
            <?php endif; ?>
            
        <?php else: ?>
            <!-- No Results -->
            <div class="askme-no-results">
                <div class="askme-no-results-icon">🔍</div>
                <h3><?php _e('لم يتم العثور على نتائج', 'askro'); ?></h3>
                <p><?php _e('جرب تغيير كلمات البحث أو استخدام فلاتر مختلفة', 'askro'); ?></p>
                
                <div class="askme-no-results-suggestions">
                    <h4><?php _e('اقتراحات للبحث:', 'askro'); ?></h4>
                    <ul>
                        <li><?php _e('تأكد من كتابة الكلمات بشكل صحيح', 'askro'); ?></li>
                        <li><?php _e('جرب كلمات بحث مختلفة', 'askro'); ?></li>
                        <li><?php _e('استخدم كلمات أقل', 'askro'); ?></li>
                        <li><?php _e('جرب البحث في الإجابات بدلاً من الأسئلة', 'askro'); ?></li>
                    </ul>
                </div>
                
                <div class="askme-no-results-actions">
                    <a href="<?php echo home_url(); ?>" class="askme-btn askme-btn-primary">
                        <?php _e('العودة للرئيسية', 'askro'); ?>
                    </a>
                    <a href="<?php echo add_query_arg(['s' => ''], get_permalink()); ?>" class="askme-btn askme-btn-secondary">
                        <?php _e('مسح البحث', 'askro'); ?>
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <?php wp_reset_postdata(); ?>
        
    </div>

    <!-- Sidebar -->
    <div class="askme-sidebar">
        
        <!-- Search Tips -->
        <div class="askme-sidebar-module">
            <h3 class="askme-module-title"><?php _e('نصائح البحث', 'askro'); ?></h3>
            <div class="askme-module-content">
                <div class="askme-search-tips">
                    <div class="askme-tip-item">
                        <h4><?php _e('استخدم علامات الاقتباس', 'askro'); ?></h4>
                        <p><?php _e('للبحث عن عبارة محددة، ضعها بين علامتي اقتباس', 'askro'); ?></p>
                    </div>
                    <div class="askme-tip-item">
                        <h4><?php _e('استخدم الفلاتر', 'askro'); ?></h4>
                        <p><?php _e('استخدم التصنيفات والحالات للحصول على نتائج أدق', 'askro'); ?></p>
                    </div>
                    <div class="askme-tip-item">
                        <h4><?php _e('البحث في الإجابات', 'askro'); ?></h4>
                        <p><?php _e('غير نوع البحث للعثور على إجابات محددة', 'askro'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Popular Searches -->
        <div class="askme-sidebar-module">
            <h3 class="askme-module-title"><?php _e('البحث الشائع', 'askro'); ?></h3>
            <div class="askme-module-content">
                <?php
                $popular_searches = askro_get_popular_searches(10);
                
                if ($popular_searches): ?>
                    <div class="askme-popular-searches">
                        <?php foreach ($popular_searches as $search): ?>
                            <a href="<?php echo add_query_arg('s', urlencode($search['term']), get_permalink()); ?>" class="askme-popular-search-link">
                                <?php echo esc_html($search['term']); ?>
                                <span class="askme-search-count"><?php echo number_format($search['count']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="askme-no-content"><?php _e('لا توجد عمليات بحث شائعة', 'askro'); ?></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Questions -->
        <div class="askme-sidebar-module">
            <h3 class="askme-module-title"><?php _e('آخر الأسئلة', 'askro'); ?></h3>
            <div class="askme-module-content">
                <?php
                $recent_questions = get_posts([
                    'post_type' => 'askro_question',
                    'post_status' => 'publish',
                    'posts_per_page' => 5,
                    'orderby' => 'date',
                    'order' => 'DESC'
                ]);
                
                if ($recent_questions): ?>
                    <div class="askme-recent-questions">
                        <?php foreach ($recent_questions as $question): ?>
                            <div class="askme-recent-question">
                                <a href="<?php echo get_permalink($question->ID); ?>" class="askme-question-link">
                                    <?php echo esc_html($question->post_title); ?>
                                </a>
                                <span class="askme-question-meta">
                                    <?php echo askro_time_ago($question->post_date); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="askme-no-content"><?php _e('لا توجد أسئلة حديثة', 'askro'); ?></p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle advanced search
    $('#askme-toggle-advanced').on('click', function() {
        const advancedSearch = $('#askme-advanced-search');
        const toggleIcon = $(this).find('.askme-toggle-icon');
        
        advancedSearch.slideToggle(300);
        toggleIcon.toggleClass('askme-rotated');
    });
    
    // Clear filters
    $('#askme-clear-filters').on('click', function() {
        $('#search-type').val('questions');
        $('#search-category').val('');
        $('#search-status').val('');
        $('#search-date').val('');
        $('#askme-search-form').submit();
    });
    
    // Auto-submit on filter change
    $('.askme-filter-select, #search-date').on('change', function() {
        if ($('#askme-search-form input[name="s"]').val().trim()) {
            $('#askme-search-form').submit();
        }
    });
    
    // Highlight search terms in results
    <?php if ($search_highlight && $search_query): ?>
    const searchTerm = '<?php echo esc_js($search_query); ?>';
    const regex = new RegExp('(' + searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
    
    $('.askme-result-title, .askme-result-excerpt').each(function() {
        const text = $(this).html();
        const highlighted = text.replace(regex, '<mark>$1</mark>');
        $(this).html(highlighted);
    });
    <?php endif; ?>
});
</script> 

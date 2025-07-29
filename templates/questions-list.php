<?php
/**
 * Questions List Template
 *
 * @package    Askro
 * @subpackage Templates
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

// Get template arguments
$args = wp_parse_args($args ?? [], [
    'show_header' => true,
    'show_filters' => true,
    'show_search' => true,
    'show_ask_button' => true,
    'posts_per_page' => 10,
    'category' => '',
    'tag' => '',
    'sort' => 'newest'
]);

// Get questions
$query_args = [
    'post_type' => 'askro_question',
    'post_status' => 'publish',
    'posts_per_page' => $args['posts_per_page'],
    'paged' => get_query_var('paged') ?: 1
];

// Add sorting
switch ($args['sort']) {
    case 'votes':
        $query_args['orderby'] = 'meta_value_num';
        $query_args['meta_key'] = '_askro_vote_score';
        $query_args['order'] = 'DESC';
        break;
    case 'views':
        $query_args['orderby'] = 'meta_value_num';
        $query_args['meta_key'] = '_askro_views';
        $query_args['order'] = 'DESC';
        break;
    case 'answers':
        $query_args['orderby'] = 'meta_value_num';
        $query_args['meta_key'] = '_askro_answer_count';
        $query_args['order'] = 'DESC';
        break;
    case 'oldest':
        $query_args['orderby'] = 'date';
        $query_args['order'] = 'ASC';
        break;
    default: // newest
        $query_args['orderby'] = 'date';
        $query_args['order'] = 'DESC';
        break;
}

// Add taxonomy filters
if ($args['category'] || $args['tag']) {
    $query_args['tax_query'] = ['relation' => 'AND'];
    
    if ($args['category']) {
        $query_args['tax_query'][] = [
            'taxonomy' => 'askro_question_category',
            'field' => 'slug',
            'terms' => $args['category']
        ];
    }
    
    if ($args['tag']) {
        $query_args['tax_query'][] = [
            'taxonomy' => 'askro_question_tag',
            'field' => 'slug',
            'terms' => $args['tag']
        ];
    }
}

$questions_query = new WP_Query($query_args);
$display = new Askro_Display();
?>

<div class="askro-questions-container">
    <?php if ($args['show_header']): ?>
    <!-- Header Section -->
    <div class="askro-questions-header">
        <div class="askro-questions-title">
            <h1 class="askro-heading-1">
                <?php 
                if ($args['category']) {
                    $category = get_term_by('slug', $args['category'], 'askro_question_category');
                    printf(__('أسئلة %s', 'askro'), $category ? $category->name : $args['category']);
                } elseif ($args['tag']) {
                    $tag = get_term_by('slug', $args['tag'], 'askro_question_tag');
                    printf(__('أسئلة بعلامة %s', 'askro'), $tag ? $tag->name : $args['tag']);
                } else {
                    _e('جميع الأسئلة', 'askro');
                }
                ?>
            </h1>
            <p class="askro-questions-subtitle">
                <?php printf(_n('سؤال واحد', '%s سؤال', $questions_query->found_posts, 'askro'), number_format($questions_query->found_posts)); ?>
            </p>
        </div>

        <?php if ($args['show_ask_button']): ?>
        <div class="askro-questions-actions">
            <a href="<?php echo esc_url(add_query_arg('askro_action', 'ask')); ?>" class="askro-btn-primary">
                ➕ <?php _e('طرح سؤال', 'askro'); ?>
            </a>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php if ($args['show_search'] || $args['show_filters']): ?>
    <!-- Search and Filters -->
    <div class="askro-questions-controls">
        <?php if ($args['show_search']): ?>
        <div class="askro-search-container">
            <form class="askro-search-form" method="get">
                <div class="askro-search-input-container">
                    <input type="text" 
                           name="askro_search" 
                           class="askro-search-input" 
                           placeholder="<?php _e('ابحث في الأسئلة...', 'askro'); ?>"
                           value="<?php echo esc_attr(get_query_var('askro_search')); ?>">
                    <button type="submit" class="askro-search-btn">
                        🔍
                    </button>
                </div>
                <div class="askro-search-results" style="display: none;"></div>
            </form>
        </div>
        <?php endif; ?>

        <?php if ($args['show_filters']): ?>
        <div class="askro-filters-container">
            <!-- Sort Filter -->
            <div class="askro-filter-group">
                <label for="askro-sort" class="askro-filter-label"><?php _e('ترتيب:', 'askro'); ?></label>
                <select id="askro-sort" class="askro-select" onchange="askroUpdateFilters()">
                    <option value="newest" <?php selected($args['sort'], 'newest'); ?>><?php _e('الأحدث', 'askro'); ?></option>
                    <option value="votes" <?php selected($args['sort'], 'votes'); ?>><?php _e('الأعلى تصويتاً', 'askro'); ?></option>
                    <option value="views" <?php selected($args['sort'], 'views'); ?>><?php _e('الأكثر مشاهدة', 'askro'); ?></option>
                    <option value="answers" <?php selected($args['sort'], 'answers'); ?>><?php _e('الأكثر إجابة', 'askro'); ?></option>
                    <option value="oldest" <?php selected($args['sort'], 'oldest'); ?>><?php _e('الأقدم', 'askro'); ?></option>
                </select>
            </div>

            <!-- Category Filter -->
            <div class="askro-filter-group">
                <label for="askro-category" class="askro-filter-label"><?php _e('التصنيف:', 'askro'); ?></label>
                <select id="askro-category" class="askro-select" onchange="askroUpdateFilters()">
                    <option value=""><?php _e('جميع التصنيفات', 'askro'); ?></option>
                    <?php
                    $categories = get_terms([
                        'taxonomy' => 'askro_question_category',
                        'hide_empty' => false
                    ]);
                    foreach ($categories as $category):
                    ?>
                    <option value="<?php echo $category->slug; ?>" <?php selected($args['category'], $category->slug); ?>>
                        <?php echo esc_html($category->name); ?> (<?php echo $category->count; ?>)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Status Filter -->
            <div class="askro-filter-group">
                <label for="askro-status" class="askro-filter-label"><?php _e('الحالة:', 'askro'); ?></label>
                <select id="askro-status" class="askro-select" onchange="askroUpdateFilters()">
                    <option value=""><?php _e('جميع الأسئلة', 'askro'); ?></option>
                    <option value="unanswered"><?php _e('بدون إجابة', 'askro'); ?></option>
                    <option value="answered"><?php _e('تم الإجابة عليها', 'askro'); ?></option>
                    <option value="accepted"><?php _e('لها إجابة مقبولة', 'askro'); ?></option>
                    <option value="featured"><?php _e('مميزة', 'askro'); ?></option>
                    <option value="closed"><?php _e('مغلقة', 'askro'); ?></option>
                </select>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Questions List -->
    <div class="askro-questions-list" id="askro-questions-list">
        <?php if ($questions_query->have_posts()): ?>
            <?php while ($questions_query->have_posts()): $questions_query->the_post(); ?>
                <?php echo $display->render_question_card(get_post()); ?>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="askro-no-questions">
                <div class="askro-no-questions-content">
                    <div class="askro-no-questions-icon">❓</div>
                    <h3 class="askro-heading-3"><?php _e('لا توجد أسئلة', 'askro'); ?></h3>
                    <p class="askro-body-text">
                        <?php _e('لم يتم العثور على أسئلة تطابق معايير البحث.', 'askro'); ?>
                    </p>
                    <?php if ($args['show_ask_button']): ?>
                    <a href="<?php echo esc_url(add_query_arg('askro_action', 'ask')); ?>" class="askro-btn-primary">
                        ➕ <?php _e('كن أول من يطرح سؤالاً', 'askro'); ?>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($questions_query->max_num_pages > 1): ?>
    <div class="askro-pagination">
        <?php
        echo paginate_links([
            'total' => $questions_query->max_num_pages,
            'current' => max(1, get_query_var('paged')),
            'format' => '?paged=%#%',
            'show_all' => false,
            'end_size' => 1,
            'mid_size' => 2,
            'prev_next' => true,
            'prev_text' => '← ' . __('السابق', 'askro'),
            'next_text' => __('التالي', 'askro') . ' →',
            'type' => 'list',
            'add_args' => false,
            'add_fragment' => '',
            'before_page_number' => '',
            'after_page_number' => ''
        ]);
        ?>
    </div>
    <?php endif; ?>

    <!-- Load More Button (Alternative to pagination) -->
    <?php if ($questions_query->max_num_pages > 1 && get_query_var('paged') < $questions_query->max_num_pages): ?>
    <div class="askro-load-more-container" style="display: none;">
        <button type="button" class="askro-btn-outline askro-load-more-btn" 
                data-page="<?php echo get_query_var('paged') + 1; ?>"
                data-max-pages="<?php echo $questions_query->max_num_pages; ?>">
            <?php _e('تحميل المزيد', 'askro'); ?>
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Featured Questions Sidebar -->
<div class="askro-sidebar">
    <!-- Stats Widget -->
    <div class="askro-widget">
        <h3 class="askro-widget-title"><?php _e('إحصائيات', 'askro'); ?></h3>
        <div class="askro-stats-widget">
            <?php
            $stats = [
                'questions' => wp_count_posts('askro_question')->publish,
                'answers' => wp_count_posts('askro_answer')->publish,
                'users' => count_users()['total_users']
            ];
            ?>
            <div class="askro-stat-item">
                <span class="askro-stat-number"><?php echo number_format($stats['questions']); ?></span>
                <span class="askro-stat-label"><?php _e('سؤال', 'askro'); ?></span>
            </div>
            <div class="askro-stat-item">
                <span class="askro-stat-number"><?php echo number_format($stats['answers']); ?></span>
                <span class="askro-stat-label"><?php _e('إجابة', 'askro'); ?></span>
            </div>
            <div class="askro-stat-item">
                <span class="askro-stat-number"><?php echo number_format($stats['users']); ?></span>
                <span class="askro-stat-label"><?php _e('مستخدم', 'askro'); ?></span>
            </div>
        </div>
    </div>

    <!-- Top Categories -->
    <div class="askro-widget">
        <h3 class="askro-widget-title"><?php _e('التصنيفات الشائعة', 'askro'); ?></h3>
        <div class="askro-categories-widget">
            <?php
            $top_categories = get_terms([
                'taxonomy' => 'askro_question_category',
                'orderby' => 'count',
                'order' => 'DESC',
                'number' => 5,
                'hide_empty' => true
            ]);
            
            foreach ($top_categories as $category):
            ?>
            <a href="<?php echo get_term_link($category); ?>" class="askro-category-item">
                <span class="askro-category-name"><?php echo esc_html($category->name); ?></span>
                <span class="askro-category-count"><?php echo $category->count; ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Top Tags -->
    <div class="askro-widget">
        <h3 class="askro-widget-title"><?php _e('العلامات الشائعة', 'askro'); ?></h3>
        <div class="askro-tags-widget">
            <?php
            $top_tags = get_terms([
                'taxonomy' => 'askro_question_tag',
                'orderby' => 'count',
                'order' => 'DESC',
                'number' => 10,
                'hide_empty' => true
            ]);
            
            foreach ($top_tags as $tag):
            ?>
            <a href="<?php echo get_term_link($tag); ?>" class="askro-tag-item">
                #<?php echo esc_html($tag->name); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Activity -->
    <div class="askro-widget">
        <h3 class="askro-widget-title"><?php _e('النشاط الأخير', 'askro'); ?></h3>
        <div class="askro-activity-widget">
            <?php
            $recent_answers = get_posts([
                'post_type' => 'askro_answer',
                'posts_per_page' => 5,
                'post_status' => 'publish'
            ]);
            
            foreach ($recent_answers as $answer):
                $question_id = get_post_meta($answer->ID, '_askro_question_id', true);
                $question = get_post($question_id);
                if (!$question) continue;
            ?>
            <div class="askro-activity-item">
                <div class="askro-activity-content">
                    <a href="<?php echo get_permalink($question_id) . '#answer-' . $answer->ID; ?>" class="askro-activity-link">
                        <?php echo wp_trim_words($question->post_title, 8); ?>
                    </a>
                    <div class="askro-activity-meta">
                        <?php echo human_time_diff(strtotime($answer->post_date), current_time('timestamp')); ?> <?php _e('مضت', 'askro'); ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
// Update filters function
function askroUpdateFilters() {
    const sort = document.getElementById('askro-sort').value;
    const category = document.getElementById('askro-category').value;
    const status = document.getElementById('askro-status').value;
    
    const url = new URL(window.location);
    url.searchParams.set('askro_sort', sort);
    url.searchParams.set('askro_category', category);
    url.searchParams.set('askro_status', status);
    url.searchParams.delete('paged'); // Reset pagination
    
    window.location.href = url.toString();
}

// Load more functionality
document.addEventListener('DOMContentLoaded', function() {
    const loadMoreBtn = document.querySelector('.askro-load-more-btn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            const page = parseInt(this.dataset.page);
            const maxPages = parseInt(this.dataset.maxPages);
            
            // Show loading state
            this.textContent = '<?php _e('جاري التحميل...', 'askro'); ?>';
            this.disabled = true;
            
            // AJAX request
            fetch(askroData.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'askro_load_more_questions',
                    page: page,
                    nonce: askroData.nonce,
                    category: '<?php echo esc_js($args['category']); ?>',
                    tag: '<?php echo esc_js($args['tag']); ?>',
                    sort: '<?php echo esc_js($args['sort']); ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Append new questions
                    document.getElementById('askro-questions-list').insertAdjacentHTML('beforeend', data.data.html);
                    
                    // Update button
                    if (data.data.has_more && page < maxPages) {
                        this.dataset.page = page + 1;
                        this.textContent = '<?php _e('تحميل المزيد', 'askro'); ?>';
                        this.disabled = false;
                    } else {
                        this.style.display = 'none';
                    }
                } else {
                    this.textContent = '<?php _e('خطأ في التحميل', 'askro'); ?>';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.textContent = '<?php _e('خطأ في التحميل', 'askro'); ?>';
            });
        });
    }
});
</script>

<?php
// Reset global post data
wp_reset_postdata();
?>


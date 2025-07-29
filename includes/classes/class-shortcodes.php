<?php
/**
 * Shortcodes Class
 *
 * @package    Askro
 * @subpackage Core/Shortcodes
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
 * Askro Shortcodes Class
 *
 * Handles all shortcodes for the plugin
 *
 * @since 1.0.0
 */
class Askro_Shortcodes {

    /**
     * Initialize the shortcodes component
     *
     * @since 1.0.0
     */
    public function init() {
        // Register shortcodes - Core Page Shortcodes
        add_shortcode('askro_archive', [$this, 'questions_archive_shortcode']);
        add_shortcode('askro_single_question', [$this, 'single_question_shortcode']);
        add_shortcode('askro_ask_question_form', [$this, 'submit_question_form_shortcode']);
        add_shortcode('askro_user_profile', [$this, 'user_profile_shortcode']);
        
        // Register shortcodes - Component Shortcodes
        add_shortcode('askro_questions_list', [$this, 'questions_list_shortcode']);
        add_shortcode('askro_leaderboard', [$this, 'leaderboard_shortcode']);
        add_shortcode('askro_user_stat', [$this, 'user_stat_shortcode']);
        add_shortcode('askro_community_stat', [$this, 'community_stat_shortcode']);
        
        // Register additional shortcodes
        add_shortcode('askro_submit_answer_form', [$this, 'submit_answer_form_shortcode']);
        add_shortcode('askro_search_form', [$this, 'search_form_shortcode']);
        add_shortcode('askro_search_results', [$this, 'search_results_shortcode']);
        add_shortcode('askro_recent_questions', [$this, 'recent_questions_shortcode']);
        add_shortcode('askro_featured_questions', [$this, 'featured_questions_shortcode']);
        add_shortcode('askro_user_stats', [$this, 'user_stats_shortcode']);
        add_shortcode('askro_categories', [$this, 'categories_shortcode']);
        add_shortcode('askro_tags_cloud', [$this, 'tags_cloud_shortcode']);
        add_shortcode('askro_analytics', [$this, 'analytics_shortcode']);
    }

    /**
     * Questions archive shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     * @since 1.0.0
     */
    public function questions_archive_shortcode($atts) {
        $atts = shortcode_atts([
            'questions_per_page' => 15,
            'default_sort' => 'latest',
            'show_search' => 'true',
            'show_filters' => 'true',
            'layout' => 'list',
            'category' => '',
            'tag' => ''
        ], $atts, 'askro_archive');

        // إذا كان هناك متغير askro_question_slug في query، اعرض السؤال الفردي مباشرة
        $question_slug = get_query_var('askro_question_slug');
        if ($question_slug) {
            // البحث عن السؤال باستخدام slug
            $question = get_page_by_path($question_slug, OBJECT, 'askro_question');
            if ($question && $question->post_type === 'askro_question') {
                return $this->render_single_question_content($question, $atts);
            } else {
                return '<div class="askme-error">السؤال غير موجود.</div>';
            }
        }

        // Check if we're viewing a single question
        global $askro_current_question;
        if ($askro_current_question) {
            return $this->render_single_question_content($askro_current_question, $atts);
        }

        // Get questions based on sort order
        $args = [
            'post_type' => 'askro_question',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['questions_per_page']),
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1
        ];

        // Apply sorting
        switch ($atts['default_sort']) {
            case 'most_voted':
                $args['meta_key'] = 'askro_vote_count';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'most_answered':
                $args['meta_key'] = 'askro_answer_count';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            case 'most_viewed':
                $args['meta_key'] = 'askro_view_count';
                $args['orderby'] = 'meta_value_num';
                $args['order'] = 'DESC';
                break;
            default: // latest
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
        }

        // Apply category filter
        if (!empty($atts['category'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'askro_question_category',
                'field' => 'slug',
                'terms' => sanitize_text_field($atts['category'])
            ];
        }

        // Apply tag filter
        if (!empty($atts['tag'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'askro_question_tag',
                'field' => 'slug',
                'terms' => sanitize_text_field($atts['tag'])
            ];
        }

        $questions = new WP_Query($args);

        ob_start();
        ?>
        <div class="askme-container">
            <!-- Main Content Area (70%) -->
            <div class="askme-main-content">
                <!-- Interactive Toolbar -->
                <div class="askme-toolbar">
                    <div class="askme-sort-tabs">
                        <button class="askme-tab <?php echo $atts['default_sort'] === 'latest' ? 'active' : ''; ?>" data-sort="latest">
                            <i class="askme-icon">🕒</i>
                            الأحدث
                        </button>
                        <button class="askme-tab <?php echo $atts['default_sort'] === 'most_answered' ? 'active' : ''; ?>" data-sort="most_answered">
                            <i class="askme-icon">💬</i>
                            الأكثر إجابة
                        </button>
                        <button class="askme-tab <?php echo $atts['default_sort'] === 'unanswered' ? 'active' : ''; ?>" data-sort="unanswered">
                            <i class="askme-icon">❓</i>
                            غير المجاب
                        </button>
                        <button class="askme-tab <?php echo $atts['default_sort'] === 'top_voted' ? 'active' : ''; ?>" data-sort="top_voted">
                            <i class="askme-icon">👍</i>
                            الأعلى تصويتاً
                        </button>
                    </div>

                    <div class="askme-toolbar-right">
                        <div class="askme-search">
                            <input type="text" placeholder="ابحث في الأسئلة..." class="askme-search-input">
                            <button class="askme-search-btn">
                                <i class="askme-icon">🔍</i>
                            </button>
                        </div>
                        <button class="askme-filter-btn" id="askme-filter-toggle">
                            <i class="askme-icon">⚙️</i>
                            فلترة متقدمة
                        </button>
                    </div>
                </div>

                <!-- Advanced Filter Modal -->
                <div class="askme-filter-modal" id="askme-filter-modal">
                    <div class="askme-filter-content">
                        <div class="askme-filter-header">
                            <h3>فلترة متقدمة</h3>
                            <button class="askme-modal-close">&times;</button>
                        </div>
                        <div class="askme-filter-body">
                            <div class="askme-filter-group">
                                <label>التصنيف</label>
                                <select class="askme-filter-select" name="category">
                                    <option value="">جميع التصنيفات</option>
                                    <?php
                                    $categories = get_terms([
                                        'taxonomy' => 'askro_question_category',
                                        'hide_empty' => true
                                    ]);
                                    if ($categories && !is_wp_error($categories)) {
                                        foreach ($categories as $category) {
                                            echo '<option value="' . esc_attr($category->slug) . '">' . esc_html($category->name) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="askme-filter-group">
                                <label>الحالة</label>
                                <select class="askme-filter-select" name="status">
                                    <option value="">جميع الحالات</option>
                                    <option value="open">مفتوح</option>
                                    <option value="solved">محلول</option>
                                    <option value="closed">مغلق</option>
                                    <option value="urgent">عاجل</option>
                                </select>
                            </div>
                            <div class="askme-filter-group">
                                <label>الفترة الزمنية</label>
                                <select class="askme-filter-select" name="timeframe">
                                    <option value="">جميع الأوقات</option>
                                    <option value="today">اليوم</option>
                                    <option value="week">هذا الأسبوع</option>
                                    <option value="month">هذا الشهر</option>
                                    <option value="year">هذا العام</option>
                                </select>
                            </div>
                        </div>
                        <div class="askme-filter-footer">
                            <button class="askme-btn askme-btn-secondary" id="askme-filter-reset">إعادة تعيين</button>
                            <button class="askme-btn askme-btn-primary" id="askme-filter-apply">تطبيق الفلترة</button>
                        </div>
                    </div>
                </div>

                <!-- Questions List -->
                <div class="askme-questions-list">
                    <?php if ($questions->have_posts()): ?>
                        <?php while ($questions->have_posts()): $questions->the_post(); ?>
                            <?php echo $this->render_advanced_question_card(get_the_ID()); ?>
                        <?php endwhile; ?>
                        
                        <?php if ($questions->max_num_pages > 1): ?>
                            <div class="askme-pagination">
                                <?php echo $this->render_advanced_pagination($questions); ?>
                            </div>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="askme-no-questions">
                            <div class="askme-empty-state">
                                <i class="askme-icon">❓</i>
                                <h3>لا توجد أسئلة حالياً</h3>
                                <p>كن أول من يطرح سؤالاً في هذا التصنيف</p>
                                <a href="<?php echo esc_url(askro_get_url('ask_question')); ?>" class="askro-btn askro-btn-primary">
                                    <i class="askme-icon">➕</i>
                                    اطرح سؤالاً
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sidebar (30%) -->
            <div class="askme-sidebar">
                <?php echo $this->render_archive_sidebar(); ?>
            </div>
        </div>
        <?php

        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Calculate XP progress percentage
     *
     * @param int $current_points Current user points
     * @return int Progress percentage
     * @since 1.0.0
     */
    private function calculate_xp_progress($current_points) {
        // Define XP thresholds for ranks
        $rank_thresholds = [0, 100, 500, 1000, 2000, 5000, 10000];
        
        for ($i = 0; $i < count($rank_thresholds) - 1; $i++) {
            if ($current_points >= $rank_thresholds[$i] && $current_points < $rank_thresholds[$i + 1]) {
                $current_level_points = $current_points - $rank_thresholds[$i];
                $level_total_points = $rank_thresholds[$i + 1] - $rank_thresholds[$i];
                return min(100, round(($current_level_points / $level_total_points) * 100));
            }
        }
        
        return 100; // Max level
    }

    /**
     * Get status label
     *
     * @param string $status Status value
     * @return string Status label
     * @since 1.0.0
     */
    private function get_status_label($status) {
        $labels = [
            'solved' => 'محلول',
            'urgent' => 'عاجل',
            'closed' => 'مغلق',
            'open' => 'مفتوح'
        ];
        
        return isset($labels[$status]) ? $labels[$status] : $status;
    }

    /**
     * Render single question content
     *
     * @param object $question Question post object
     * @param array $atts Shortcode attributes
     * @return string Rendered HTML
     * @since 1.0.0
     */
    private function render_single_question_content($question, $atts) {
        // Increment views
        askro_increment_post_views($question->ID);

        ob_start();
        ?>
        <div class="askme-container">
            <!-- Main Content Area (70%) -->
            <div class="askme-main-content">
                <div class="askro-single-question" data-question-id="<?php echo $question->ID; ?>">
                    <?php echo $this->render_question_detail($question, $atts); ?>
                    <div class="askro-answers-section">
                        <?php echo $this->render_answers_section($question->ID, $atts); ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar (30%) -->
            <div class="askme-sidebar">
                <?php echo $this->render_single_question_sidebar($question->ID); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Single question shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     * @since 1.0.0
     */
    public function single_question_shortcode($atts) {
        $atts = shortcode_atts([
            'id' => 0,
            'show_answers' => 'true',
            'show_comments' => 'true',
            'show_voting' => 'true'
        ], $atts, 'askro_single_question');

        $question_id = intval($atts['id']);
        if (!$question_id) {
            global $post;
            $question_id = $post ? $post->ID : 0;
        }

        if (!$question_id) {
            return '<p>' . __('معرف السؤال غير صحيح.', 'askro') . '</p>';
        }

        $question = get_post($question_id);
        if (!$question || $question->post_type !== 'askro_question') {
            return '<p>' . __('السؤال غير موجود.', 'askro') . '</p>';
        }
        
        // Debug: Check question details
        // Debug logging removed for production

        // Increment views
        askro_increment_post_views($question_id);

        ob_start();
        ?>
        <div class="askme-container">
            <div class="askme-layout">
                <!-- Main Content Area (70%) -->
                <div class="askme-main-content">
                    <div class="askro-single-question" data-question-id="<?php echo $question_id; ?>">
                        <?php echo $this->render_question_detail($question, $atts); ?>
                        
                        <?php if ($atts['show_answers'] === 'true'): ?>
                            <div class="askro-answers-section">
                                <?php echo $this->render_answers_section($question_id, $atts); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Sidebar (30%) -->
                <div class="askme-sidebar">
                    <?php echo $this->render_single_question_sidebar($question_id); ?>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Submit question form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     * @since 1.0.0
     */
    public function submit_question_form_shortcode($atts) {
        $atts = shortcode_atts([
            'redirect_after_submit' => '',
            'show_categories' => 'true',
            'show_tags' => 'true',
            'show_attachments' => 'true'
        ], $atts, 'askro_ask_question_form');

        // Pass attributes to template
        $template_vars = [
            'show_categories' => $atts['show_categories'] === 'true',
            'show_tags' => $atts['show_tags'] === 'true',
            'show_attachments' => $atts['show_attachments'] === 'true',
            'redirect_after_submit' => $atts['redirect_after_submit']
        ];

        ob_start();
        
        // Include the template
        include ASKRO_PLUGIN_DIR . 'templates/frontend/ask-question-form.php';
        
        return ob_get_clean();
    }

    /**
     * Search results shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     * @since 1.0.0
     */
    public function search_results_shortcode($atts) {
        $atts = shortcode_atts([
            'results_per_page' => 10,
            'enable_advanced_search' => 'true',
            'search_highlight' => 'true'
        ], $atts, 'askro_search_results');

        // Pass attributes to template
        $template_vars = [
            'results_per_page' => intval($atts['results_per_page']),
            'enable_advanced_search' => $atts['enable_advanced_search'] === 'true',
            'search_highlight' => $atts['search_highlight'] === 'true'
        ];

        ob_start();
        
        // Include the template
        include ASKRO_PLUGIN_DIR . 'templates/frontend/search-results.php';
        
        return ob_get_clean();
    }

    /**
     * Submit answer form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     * @since 1.0.0
     */
    public function submit_answer_form_shortcode($atts) {
        if (!is_user_logged_in()) {
            return '<p>' . sprintf(__('يجب <a href="%s">تسجيل الدخول</a> لتقديم إجابة.', 'askro'), wp_login_url()) . '</p>';
        }

        $atts = shortcode_atts([
            'question_id' => 0,
            'show_attachments' => 'true'
        ], $atts, 'askro_submit_answer_form');

        $question_id = intval($atts['question_id']);
        if (!$question_id) {
            global $post;
            $question_id = $post ? $post->ID : 0;
        }

        if (!$question_id) {
            return '<p>' . __('معرف السؤال غير صحيح.', 'askro') . '</p>';
        }

        ob_start();
        ?>
        <div class="askro-submit-answer-form">
            <h3><?php _e('إجابتك', 'askro'); ?></h3>
            
            <form id="askro-answer-form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('askro_submit_answer', 'askro_answer_nonce'); ?>
                <input type="hidden" name="question_id" value="<?php echo $question_id; ?>" />
                
                <div class="askro-form-group">
                    <label for="answer_content"><?php _e('إجابتك', 'askro'); ?> <span class="required">*</span></label>
                    <?php
                    wp_editor('', 'answer_content', [
                        'textarea_name' => 'answer_content',
                        'textarea_rows' => 8,
                        'media_buttons' => true,
                        'teeny' => false,
                        'tinymce' => [
                            'toolbar1' => 'bold,italic,underline,strikethrough,|,bullist,numlist,|,link,unlink,|,undo,redo',
                            'toolbar2' => ''
                        ]
                    ]);
                    ?>
                </div>

                <?php if ($atts['show_attachments'] === 'true'): ?>
                    <div class="askro-form-group">
                        <label for="answer_attachments"><?php _e('المرفقات', 'askro'); ?></label>
                        <input type="file" id="answer_attachments" name="answer_attachments[]" multiple 
                               accept="image/*,.pdf,.doc,.docx,.txt" />
                        <p class="description"><?php _e('يمكنك رفع صور أو ملفات لتوضيح إجابتك.', 'askro'); ?></p>
                    </div>
                <?php endif; ?>

                <div class="askro-form-actions">
                    <button type="submit" class="askro-btn askro-btn-primary">
                        <?php _e('نشر الإجابة', 'askro'); ?>
                    </button>
                </div>
            </form>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * User profile shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     * @since 1.0.0
     */
    public function user_profile_shortcode($atts) {
        $atts = shortcode_atts([
            'user_id' => 0,
            'username' => '',
            'show_stats' => 'true',
            'show_badges' => 'true',
            'show_achievements' => 'true',
            'show_recent_activity' => 'true'
        ], $atts, 'askro_user_profile');

        // Get user ID from attributes or URL
        $user_id = intval($atts['user_id']);
        
        if (!$user_id && !empty($atts['username'])) {
            $user = get_user_by('login', $atts['username']);
            $user_id = $user ? $user->ID : 0;
        }
        
        if (!$user_id) {
            // Try to get from URL parameter
            $username_from_url = get_query_var('username');
            if ($username_from_url) {
                $user = get_user_by('login', $username_from_url);
                $user_id = $user ? $user->ID : 0;
            }
        }
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }

        // Pass attributes to template
        $template_vars = [
            'user_id' => $user_id,
            'show_stats' => $atts['show_stats'] === 'true',
            'show_badges' => $atts['show_badges'] === 'true',
            'show_achievements' => $atts['show_achievements'] === 'true',
            'show_recent_activity' => $atts['show_recent_activity'] === 'true'
        ];

        ob_start();
        
        // Include the template
        include ASKRO_PLUGIN_DIR . 'templates/frontend/user-profile.php';
        
        return ob_get_clean();
    }

    /**
     * Search form shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     * @since 1.0.0
     */
    public function search_form_shortcode($atts) {
        $atts = shortcode_atts([
            'placeholder' => __('ابحث في الأسئلة...', 'askro'),
            'show_filters' => 'true',
            'show_suggestions' => 'true'
        ], $atts, 'askro_search_form');

        ob_start();
        ?>
        <div class="askro-search-form">
            <form id="askro-search" method="get">
                <div class="askro-search-input-group">
                    <input type="text" id="askro-search-input" name="s" 
                           placeholder="<?php echo esc_attr($atts['placeholder']); ?>" 
                           value="<?php echo get_search_query(); ?>" />
                    <button type="submit" class="askro-search-btn">
                        <i class="dashicons dashicons-search"></i>
                    </button>
                </div>
                
                <?php if ($atts['show_filters'] === 'true'): ?>
                    <div class="askro-search-filters">
                        <?php echo $this->render_search_filters(); ?>
                    </div>
                <?php endif; ?>
            </form>
            
            <?php if ($atts['show_suggestions'] === 'true'): ?>
                <div class="askro-search-suggestions" id="askro-search-suggestions"></div>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
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
            'limit' => 10,
            'timeframe' => 'all_time',
            'show_avatars' => 'true',
            'show_ranks' => 'true'
        ], $atts, 'askro_leaderboard');

        // Pass attributes to template
        $template_vars = [
            'limit' => intval($atts['limit']),
            'timeframe' => $atts['timeframe'],
            'show_avatars' => $atts['show_avatars'] === 'true',
            'show_ranks' => $atts['show_ranks'] === 'true'
        ];

        ob_start();
        
        // Include the template
        include ASKRO_PLUGIN_DIR . 'templates/frontend/leaderboard.php';
        
        return ob_get_clean();
    }

    /**
     * Render answer comments
     *
     * @param int $answer_id Answer ID
     * @return string HTML output
     * @since 1.0.0
     */
    private function render_answer_comments($answer_id) {
        global $wpdb;
        
        // Check cache first
        $cache_key = 'askro_comments_' . $answer_id;
        $cached_comments = wp_cache_get($cache_key, 'askro');
        
        if ($cached_comments === false) {
            $comments = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}askro_comments 
                 WHERE answer_id = %d AND parent_id = 0 
                 ORDER BY created_at ASC",
                $answer_id
            ));
            
            // Cache for 15 minutes
            wp_cache_set($cache_key, $comments, 'askro', 900);
        } else {
            $comments = $cached_comments;
        }

        ob_start();
        ?>
        <div class="askme-comments-section" data-answer-id="<?php echo $answer_id; ?>">
            <div class="askme-comments-list">
                <?php if (!empty($comments)): ?>
                    <?php foreach ($comments as $comment): ?>
                        <?php echo $this->render_single_comment($comment); ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <?php if (is_user_logged_in()): ?>
                <div class="askme-add-comment">
                    <form class="askme-comment-form" data-answer-id="<?php echo $answer_id; ?>">
                        <?php wp_nonce_field('askro_nonce', 'nonce'); ?>
                        <textarea name="comment_text" placeholder="<?php _e('أضف تعليقك...', 'askro'); ?>" required></textarea>
                        <button type="submit" class="askme-submit-comment-btn">
                            <?php _e('إرسال', 'askro'); ?>
                        </button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render single comment
     *
     * @param object $comment Comment object
     * @return string HTML output
     * @since 1.0.0
     */
    private function render_single_comment($comment) {
        $user_data = askro_get_user_data($comment->user_id);
        
        ob_start();
        ?>
        <div class="askme-comment" data-comment-id="<?php echo $comment->id; ?>">
            <div class="askme-comment-header">
                <div class="askme-comment-author">
                    <div class="askme-comment-avatar">
                        <?php echo get_avatar($comment->user_id, 32); ?>
                    </div>
                    <div class="askme-comment-author-info">
                        <span class="askme-comment-author-name"><?php echo esc_html($user_data['display_name']); ?></span>
                        <span class="askme-comment-date"><?php echo human_time_diff(strtotime($comment->created_at), current_time('timestamp')); ?> مضت</span>
                    </div>
                </div>
                
                <div class="askme-comment-actions">
                    <button class="askme-comment-reaction" data-reaction="like" data-comment-id="<?php echo $comment->id; ?>">
                        👍 <span class="askme-reaction-count"><?php echo $comment->likes; ?></span>
                    </button>
                    <button class="askme-comment-reaction" data-reaction="love" data-comment-id="<?php echo $comment->id; ?>">
                        ❤️ <span class="askme-reaction-count"><?php echo $comment->loves; ?></span>
                    </button>
                    <button class="askme-comment-reaction" data-reaction="fire" data-comment-id="<?php echo $comment->id; ?>">
                        🔥 <span class="askme-reaction-count"><?php echo $comment->fires; ?></span>
                    </button>
                    
                    <?php if (get_current_user_id() === $comment->user_id): ?>
                        <button class="askme-comment-edit" data-comment-id="<?php echo $comment->id; ?>">
                            ✏️
                        </button>
                        <button class="askme-comment-delete" data-comment-id="<?php echo $comment->id; ?>">
                            🗑️
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="askme-comment-content">
                <?php echo esc_html($comment->content); ?>
            </div>
            
            <?php if (is_user_logged_in()): ?>
                <div class="askme-comment-reply">
                    <button class="askme-reply-btn" data-comment-id="<?php echo $comment->id; ?>">
                        رد
                    </button>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    // Helper methods for rendering components
    private function render_question_card($question_id, $layout = 'list') {
        $question = get_post($question_id);
        if (!$question || $question->post_type !== 'askro_question') {
            return '';
        }

        $author_id = $question->post_author;
        $author_data = askro_get_user_data($author_id);
        $question_status = get_post_meta($question_id, '_askro_status', true);
        $vote_count = askro_get_vote_count($question_id);
        $answers_count = askro_get_answers_count($question_id);
        $views_count = get_post_meta($question_id, '_askro_views', true) ?: 0;
        
        ob_start();
        ?>
        <div class="askme-question-card" data-question-id="<?php echo $question_id; ?>">
            <!-- Card Header -->
            <div class="askme-card-header">
                <div class="askme-question-title">
                    <a href="<?php echo esc_url(askro_get_question_url($question_id)); ?>" class="askme-title-link">
                        <?php echo get_the_title($question_id); ?>
                    </a>
                </div>
                <div class="askme-question-time">
                    <?php echo human_time_diff(get_the_time('U', $question_id), current_time('timestamp')); ?> مضت
                </div>
            </div>

            <!-- Card Body -->
            <div class="askme-card-body">
                <div class="askme-question-excerpt">
                    <?php echo wp_trim_words(get_the_excerpt($question_id), 20, '...'); ?>
                </div>
            </div>

            <!-- Card Footer -->
            <div class="askme-card-footer">
                <!-- Left Side - Author Info -->
                <div class="askme-author-info">
                    <div class="askme-author-avatar">
                        <?php echo get_avatar($author_id, 48); ?>
                    </div>
                    <div class="askme-author-details">
                        <div class="askme-author-name">
                            <?php echo esc_html($author_data['display_name']); ?>
                        </div>
                        <div class="askme-author-stats">
                            <span class="askme-author-points"><?php echo number_format($author_data['points']); ?> نقطة</span>
                            <span class="askme-author-rank"><?php echo esc_html($author_data['rank']['current']['name']); ?></span>
                        </div>
                        <div class="askme-xp-progress">
                            <div class="askme-progress-bar">
                                <div class="askme-progress-fill" style="width: <?php echo $this->calculate_xp_progress($author_data['points']); ?>%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Side - Question Stats -->
                <div class="askme-question-stats">
                    <div class="askme-stat-item">
                        <span class="askme-stat-icon">👍</span>
                        <span class="askme-stat-value"><?php echo $vote_count; ?></span>
                    </div>
                    <div class="askme-stat-item">
                        <span class="askme-stat-icon">💬</span>
                        <span class="askme-stat-value"><?php echo $answers_count; ?></span>
                    </div>
                    <div class="askme-stat-item">
                        <span class="askme-stat-icon">👁️</span>
                        <span class="askme-stat-value"><?php echo $views_count; ?></span>
                    </div>
                    <?php if ($question_status): ?>
                        <div class="askme-status-badge askme-status-<?php echo esc_attr($question_status); ?>">
                            <?php echo $this->get_status_label($question_status); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_search_form() {
        // Implementation for search form rendering
        return '<div class="askro-search-form">Search form content</div>';
    }

    private function render_filters() {
        // Implementation for filters rendering
        return '<div class="askro-filters">Filters content</div>';
    }

    private function render_pagination($query) {
        // Implementation for pagination rendering
        return '<div class="askro-pagination">Pagination content</div>';
    }

    private function render_question_detail($question, $atts) {
        $question_id = $question->ID;
        $author_id = $question->post_author;
        $author_data = askro_get_user_data($author_id);
        $question_status = get_post_meta($question_id, '_askro_status', true);
        $vote_count = askro_get_vote_count($question_id);
        $answers_count = askro_get_answers_count($question_id);
        $views_count = get_post_meta($question_id, '_askro_views', true) ?: 0;
        $categories = get_the_terms($question_id, 'askro_question_category');
        $tags = get_the_terms($question_id, 'askro_question_tag');
        
        ob_start();
        ?>
        <div class="askme-question-detail">
            <!-- Question Header -->
            <div class="askme-question-header">
                <div class="askme-question-title-section">
                    <h1 class="askme-question-title"><?php echo get_the_title($question_id); ?></h1>
                    <?php if ($question_status): ?>
                        <div class="askme-status-badge askme-status-<?php echo esc_attr($question_status); ?>">
                            <?php echo $this->get_status_label($question_status); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if (get_current_user_id() === $author_id): ?>
                    <div class="askme-question-controls">
                        <select class="askme-status-select" data-question-id="<?php echo $question_id; ?>">
                            <option value="open" <?php selected($question_status, 'open'); ?>>مفتوح</option>
                            <option value="urgent" <?php selected($question_status, 'urgent'); ?>>عاجل</option>
                            <option value="closed" <?php selected($question_status, 'closed'); ?>>مغلق</option>
                        </select>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Question Meta -->
            <div class="askme-question-meta">
                <div class="askme-author-info">
                    <div class="askme-author-avatar">
                        <?php echo get_avatar($author_id, 64); ?>
                    </div>
                    <div class="askme-author-details">
                        <div class="askme-author-name">
                            <?php echo esc_html($author_data['display_name']); ?>
                        </div>
                        <div class="askme-author-stats">
                            <span class="askme-author-points"><?php echo number_format($author_data['points']); ?> نقطة</span>
                            <span class="askme-author-rank"><?php echo esc_html($author_data['rank']['current']['name']); ?></span>
                        </div>
                        <div class="askme-question-date">
                            <?php echo get_the_date('j F Y', $question_id); ?>
                        </div>
                    </div>
                </div>
                
                <div class="askme-question-stats">
                    <div class="askme-stat-item">
                        <span class="askme-stat-icon">👁️</span>
                        <span class="askme-stat-value"><?php echo $views_count; ?></span>
                        <span class="askme-stat-label">مشاهدات</span>
                    </div>
                    <div class="askme-stat-item">
                        <span class="askme-stat-icon">💬</span>
                        <span class="askme-stat-value"><?php echo $answers_count; ?></span>
                        <span class="askme-stat-label">إجابة</span>
                    </div>
                    <div class="askme-stat-item">
                        <span class="askme-stat-icon">👍</span>
                        <span class="askme-stat-value"><?php echo $vote_count; ?></span>
                        <span class="askme-stat-label">تصويت</span>
                    </div>
                </div>
            </div>

            <!-- Question Content -->
            <div class="askme-question-content">
                <?php echo apply_filters('the_content', $question->post_content); ?>
            </div>

            <!-- Question Categories and Tags -->
            <?php if ($categories || $tags): ?>
                <div class="askme-question-taxonomies">
                    <?php if ($categories): ?>
                        <div class="askme-categories">
                            <span class="askme-taxonomy-label">التصنيفات:</span>
                            <?php foreach ($categories as $category): ?>
                                <a href="<?php echo get_term_link($category); ?>" class="askme-category-link">
                                    <?php echo esc_html($category->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($tags): ?>
                        <div class="askme-tags">
                            <span class="askme-taxonomy-label">العلامات:</span>
                            <?php foreach ($tags as $tag): ?>
                                <a href="<?php echo get_term_link($tag); ?>" class="askme-tag-link">
                                    <?php echo esc_html($tag->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_answers_section($question_id, $atts) {
        // Check cache first
        $cache_key = 'askro_answers_' . $question_id;
        $cached_answers = wp_cache_get($cache_key, 'askro');
        
        if ($cached_answers === false) {
            $answers = get_posts([
                'post_type' => 'askro_answer',
                'post_status' => 'publish',
                'meta_query' => [
                    [
                        'key' => '_askro_question_id',
                        'value' => $question_id
                    ]
                ],
                'orderby' => 'meta_value_num',
                'meta_key' => '_askro_is_best_answer',
                'order' => 'DESC',
                'numberposts' => 1000 // Changed from -1 for performance
            ]);
            
            // Cache for 30 minutes
            wp_cache_set($cache_key, $answers, 'askro', 1800);
        } else {
            $answers = $cached_answers;
        }

        $best_answer = null;
        $other_answers = [];

        // Separate best answer from others
        foreach ($answers as $answer) {
            if (get_post_meta($answer->ID, '_askro_is_best_answer', true)) {
                $best_answer = $answer;
            } else {
                $other_answers[] = $answer;
            }
        }

        ob_start();
        ?>
        <div class="askme-answers-section">
            <div class="askme-answers-header">
                <h2 class="askme-answers-title">
                    <?php echo count($answers); ?> إجابة
                </h2>
            </div>

            <?php if ($best_answer): ?>
                <div class="askme-best-answer">
                    <div class="askme-best-answer-header">
                        <span class="askme-best-answer-badge">👑 أفضل إجابة</span>
                    </div>
                    <?php echo $this->render_single_answer($best_answer, $question_id, true); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($other_answers)): ?>
                <div class="askme-other-answers">
                    <?php foreach ($other_answers as $answer): ?>
                        <?php echo $this->render_single_answer($answer, $question_id, false); ?>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if (is_user_logged_in()): ?>
                <div class="askme-submit-answer">
                    <h3><?php _e('أضف إجابتك', 'askro'); ?></h3>
                    <div class="askro-submit-answer-form askme-answer-form" id="askro-answer-form-container">
                        <form id="askro-answer-form" class="askme-answer-form" method="post" enctype="multipart/form-data">
                            <?php wp_nonce_field('askro_submit_answer', 'askro_answer_nonce'); ?>
                            <input type="hidden" name="question_id" value="<?php echo esc_attr($question_id); ?>" />
                            <input type="hidden" name="action" value="askro_submit_answer" />
                            
                            <div class="askro-form-group">
                                <label for="answer_content"><?php _e('إجابتك', 'askro'); ?> <span class="required">*</span></label>
                                <?php
                                wp_editor('', 'answer_content', [
                                    'textarea_name' => 'answer_content',
                                    'textarea_rows' => 8,
                                    'media_buttons' => true,
                                    'teeny' => false,
                                    'tinymce' => [
                                        'toolbar1' => 'bold,italic,underline,strikethrough,|,bullist,numlist,|,link,unlink,|,undo,redo',
                                        'toolbar2' => ''
                                    ],
                                    'quicktags' => true,
                                    'drag_drop_upload' => false
                                ]);
                                ?>
                            </div>

                            <div class="askro-form-group">
                                <label for="answer_attachments"><?php _e('المرفقات', 'askro'); ?></label>
                                <input type="file" id="answer_attachments" name="answer_attachments[]" multiple 
                                       accept="image/*,.pdf,.doc,.docx,.txt" />
                                <p class="description"><?php _e('يمكنك رفع صور أو ملفات لتوضيح إجابتك.', 'askro'); ?></p>
                            </div>

                            <div class="askro-form-actions">
                                <button type="submit" class="askro-btn askro-btn-primary askme-submit-btn">
                                    <?php _e('نشر الإجابة', 'askro'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render single answer
     *
     * @param object $answer Answer post object
     * @param int $question_id Question ID
     * @param bool $is_best Whether this is the best answer
     * @return string HTML output
     * @since 1.0.0
     */
    private function render_single_answer($answer, $question_id, $is_best = false) {
        $answer_id = $answer->ID;
        $author_id = $answer->post_author;
        
        // Check cache for author data
        $cache_key = 'askro_user_data_' . $author_id;
        $cached_author_data = wp_cache_get($cache_key, 'askro');
        if ($cached_author_data === false) {
            $author_data = askro_get_user_data($author_id);
            wp_cache_set($cache_key, $author_data, 'askro', 1800); // 30 minutes
        } else {
            $author_data = $cached_author_data;
        }
        
        // Check cache for vote count
        $vote_cache_key = 'askro_vote_count_' . $answer_id;
        $cached_vote_count = wp_cache_get($vote_cache_key, 'askro');
        if ($cached_vote_count === false) {
            $vote_count = askro_get_vote_count($answer_id);
            wp_cache_set($vote_cache_key, $vote_count, 'askro', 600); // 10 minutes
        } else {
            $vote_count = $cached_vote_count;
        }
        
        $current_user_id = get_current_user_id();
        $user_vote = askro_get_user_vote($current_user_id, $answer_id);
        
        ob_start();
        ?>
        <div class="askme-answer <?php echo $is_best ? 'askme-best-answer' : ''; ?>" data-answer-id="<?php echo $answer_id; ?>">
            <div class="askme-answer-layout">
                <!-- Left Column - Voting & Author -->
                <div class="askme-answer-left">
                    <!-- Multi-dimensional Voting -->
                    <div class="askme-voting-section">
                        <div class="askme-vote-buttons">
                            <button class="askme-vote-btn askme-vote-useful" data-vote-type="useful" data-vote-value="3" data-post-id="<?php echo $answer_id; ?>">
                                <span class="askme-vote-icon">✔️</span>
                                <span class="askme-vote-label">مفيد</span>
                            </button>
                            <button class="askme-vote-btn askme-vote-innovative" data-vote-type="innovative" data-vote-value="2" data-post-id="<?php echo $answer_id; ?>">
                                <span class="askme-vote-icon">🧠</span>
                                <span class="askme-vote-label">مبتكر</span>
                            </button>
                            <button class="askme-vote-btn askme-vote-researched" data-vote-type="researched" data-vote-value="2" data-post-id="<?php echo $answer_id; ?>">
                                <span class="askme-vote-icon">📚</span>
                                <span class="askme-vote-label">مدروس</span>
                            </button>
                        </div>
                        
                        <div class="askme-vote-score">
                            <span class="askme-score-value"><?php echo $vote_count; ?></span>
                        </div>
                        
                        <div class="askme-vote-buttons">
                            <button class="askme-vote-btn askme-vote-incorrect" data-vote-type="incorrect" data-vote-value="-2" data-post-id="<?php echo $answer_id; ?>">
                                <span class="askme-vote-icon">❌</span>
                                <span class="askme-vote-label">خاطئ</span>
                            </button>
                            <button class="askme-vote-btn askme-vote-redundant" data-vote-type="redundant" data-vote-value="-1" data-post-id="<?php echo $answer_id; ?>">
                                <span class="askme-vote-icon">🔄</span>
                                <span class="askme-vote-label">مكرر</span>
                            </button>
                        </div>
                    </div>

                    <!-- Author Info -->
                    <div class="askme-answer-author">
                        <div class="askme-author-avatar">
                            <?php echo get_avatar($author_id, 48); ?>
                        </div>
                        <div class="askme-author-details">
                            <div class="askme-author-name">
                                <?php echo esc_html($author_data['display_name']); ?>
                            </div>
                            <div class="askme-author-stats">
                                <span class="askme-author-points"><?php echo number_format($author_data['points']); ?> نقطة</span>
                                <span class="askme-author-rank"><?php echo esc_html($author_data['rank']['current']['name']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - Content & Actions -->
                <div class="askme-answer-right">
                    <!-- Answer Content -->
                    <div class="askme-answer-content">
                        <?php echo apply_filters('the_content', $answer->post_content); ?>
                    </div>

                    <!-- Answer Actions -->
                    <div class="askme-answer-actions">
                        <div class="askme-action-buttons">
                            <button class="askme-action-btn askme-share-btn" data-answer-id="<?php echo $answer_id; ?>">
                                <span class="askme-action-icon">📤</span>
                                <span class="askme-action-label">مشاركة</span>
                            </button>
                            
                            <?php if ($current_user_id !== $author_id): ?>
                                <button class="askme-action-btn askme-report-btn" data-answer-id="<?php echo $answer_id; ?>">
                                    <span class="askme-action-icon">🚨</span>
                                    <span class="askme-action-label">إبلاغ</span>
                                </button>
                            <?php endif; ?>
                            
                            <?php if (get_post_field('post_author', $question_id) === $current_user_id && !$is_best): ?>
                                <button class="askme-action-btn askme-best-answer-btn" data-answer-id="<?php echo $answer_id; ?>">
                                    <span class="askme-action-icon">👑</span>
                                    <span class="askme-action-label">أفضل إجابة</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <div class="askme-answer-comments">
                        <button class="askme-toggle-comments" data-post-id="<?php echo $answer_id; ?>">
                            عرض التعليقات
                        </button>
                        <div class="askme-comments-section" id="comments-<?php echo $answer_id; ?>" style="display: none;">
                            <?php echo $this->render_answer_comments($answer_id); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_user_profile_header($user_data) {
        // Implementation for user profile header rendering
        return '<div class="askro-user-header">User header content</div>';
    }

    private function render_user_stats($user_data) {
        // Implementation for user stats rendering
        return '<div class="askro-stats">User stats content</div>';
    }

    private function render_user_badges($user_id) {
        // Implementation for user badges rendering
        return '<div class="askro-badges">User badges content</div>';
    }

    private function render_user_achievements($user_id) {
        // Implementation for user achievements rendering
        return '<div class="askro-achievements">User achievements content</div>';
    }

    private function render_user_recent_activity($user_id) {
        // Implementation for user recent activity rendering
        return '<div class="askro-activity">User activity content</div>';
    }

    private function render_search_filters() {
        // Implementation for search filters rendering
        return '<div class="askro-search-filters">Search filters content</div>';
    }

    private function render_leaderboard($atts) {
        // Implementation for leaderboard rendering
        return '<div class="askro-leaderboard-content">Leaderboard content</div>';
    }

    // Additional shortcodes implementation
    public function recent_questions_shortcode($atts) {
        // Implementation for recent questions
        return '<div class="askro-recent-questions">Recent questions</div>';
    }

    public function featured_questions_shortcode($atts) {
        // Implementation for featured questions
        return '<div class="askro-featured-questions">Featured questions</div>';
    }

    public function user_stats_shortcode($atts) {
        // Implementation for user stats widget
        return '<div class="askro-user-stats-widget">User stats widget</div>';
    }

    public function categories_shortcode($atts) {
        // Implementation for categories list
        return '<div class="askro-categories">Categories list</div>';
    }

    public function tags_cloud_shortcode($atts) {
        // Implementation for tags cloud
        return '<div class="askro-tags-cloud">Tags cloud</div>';
    }

    public function analytics_shortcode($atts) {
        // Implementation for analytics dashboard
        return '<div class="askro-analytics">Analytics dashboard</div>';
    }

    /**
     * Questions list shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     * @since 1.0.0
     */
    public function questions_list_shortcode($atts) {
        $atts = shortcode_atts([
            'limit' => 5,
            'status' => '',
            'category' => '',
            'tags' => '',
            'orderby' => 'date',
            'author' => 'current'
        ], $atts, 'askro_questions_list');

        // Build query args
        $args = [
            'post_type' => 'askro_question',
            'post_status' => 'publish',
            'posts_per_page' => intval($atts['limit']),
            'orderby' => sanitize_text_field($atts['orderby']),
            'order' => 'DESC'
        ];

        // Add filters
        if (!empty($atts['status'])) {
            $args['meta_query'][] = [
                'key' => '_askro_status',
                'value' => sanitize_text_field($atts['status'])
            ];
        }

        if (!empty($atts['category'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'askro_question_category',
                'field' => 'slug',
                'terms' => sanitize_text_field($atts['category'])
            ];
        }

        if (!empty($atts['tags'])) {
            $tags = array_map('trim', explode(',', $atts['tags']));
            $args['tax_query'][] = [
                'taxonomy' => 'askro_question_tag',
                'field' => 'slug',
                'terms' => $tags
            ];
        }

        if ($atts['author'] === 'current') {
            $args['author'] = get_current_user_id();
        } elseif (is_numeric($atts['author'])) {
            $args['author'] = intval($atts['author']);
        }

        $questions = new WP_Query($args);

        ob_start();
        ?>
        <div class="askme-questions-list">
            <?php if ($questions->have_posts()): ?>
                <ul class="askme-questions-ul">
                    <?php while ($questions->have_posts()): $questions->the_post(); ?>
                        <li class="askme-question-item">
                            <a href="<?php echo esc_url(askro_get_question_url(get_the_ID())); ?>" class="askme-question-link">
                                <?php echo get_the_title(); ?>
                            </a>
                            <span class="askme-question-meta">
                                <?php echo get_the_date(); ?> • 
                                <?php echo askro_get_answers_count(get_the_ID()); ?> إجابة
                            </span>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p><?php _e('لا توجد أسئلة.', 'askro'); ?></p>
            <?php endif; ?>
        </div>
        <?php

        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * User stat shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     * @since 1.0.0
     */
    public function user_stat_shortcode($atts) {
        $atts = shortcode_atts([
            'user' => 'current',
            'stat' => 'xp_total'
        ], $atts, 'askro_user_stat');

        $user_id = ($atts['user'] === 'current') ? get_current_user_id() : intval($atts['user']);
        
        if (!$user_id) {
            return '';
        }

        $user_data = askro_get_user_data($user_id);
        if (empty($user_data)) {
            return '';
        }

        switch ($atts['stat']) {
            case 'xp_total':
                return number_format($user_data['points']);
            case 'rank_name':
                return $user_data['rank']['current']['name'];
            case 'question_count':
                return number_format($user_data['questions_count']);
            case 'best_answer_count':
                return number_format($user_data['answers_count']);
            default:
                return '';
        }
    }

    /**
     * Community stat shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     * @since 1.0.0
     */
    public function community_stat_shortcode($atts) {
        $atts = shortcode_atts([
            'stat' => 'total_questions'
        ], $atts, 'askro_community_stat');

        switch ($atts['stat']) {
            case 'total_questions':
                return number_format(wp_count_posts('askro_question')->publish);
            case 'total_answers':
                return number_format(wp_count_posts('askro_answer')->publish);
            case 'total_users':
                return number_format(count_users()['total_users']);
            case 'total_solved':
                global $wpdb;
                $solved_count = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->postmeta} 
                         WHERE meta_key = %s AND meta_value = %s",
                        '_askro_status',
                        'solved'
                    )
                );
                return number_format($solved_count);
            default:
                return '0';
        }
    }

    /**
     * Render advanced question card
     */
    private function render_advanced_question_card($question_id) {
        $question = get_post($question_id);
        $author_id = $question->post_author;
        $author_data = askro_get_user_data($author_id);
        $status = get_post_meta($question_id, 'askro_status', true);
        $vote_count = askro_get_vote_count($question_id);
        $answer_count = get_post_meta($question_id, 'askro_answer_count', true) ?: 0;
        $view_count = get_post_meta($question_id, 'askro_view_count', true) ?: 0;
        $categories = get_the_terms($question_id, 'askro_question_category');

        ob_start();
        ?>
        <div class="askme-question-card" data-question-id="<?php echo esc_attr($question_id); ?>">
            <div class="askme-question-header">
                <h3 class="askme-question-title">
                    <a href="<?php echo esc_url(askro_get_question_url($question_id)); ?>">
                        <?php echo esc_html($question->post_title); ?>
                    </a>
                </h3>
                <?php if ($status): ?>
                    <span class="askme-status-badge askme-status-<?php echo esc_attr($status); ?>">
                        <?php echo esc_html($this->get_status_label($status)); ?>
                    </span>
                <?php endif; ?>
            </div>

            <div class="askme-question-excerpt">
                <?php echo esc_html(wp_trim_words($question->post_content, 30)); ?>
            </div>

            <div class="askme-question-meta">
                <div class="askme-question-stats">
                    <span class="askme-stat">
                        <i class="askme-icon">👍</i>
                        <?php echo number_format($vote_count); ?>
                    </span>
                    <span class="askme-stat">
                        <i class="askme-icon">💬</i>
                        <?php echo number_format($answer_count); ?>
                    </span>
                    <span class="askme-stat">
                        <i class="askme-icon">👁️</i>
                        <?php echo number_format($view_count); ?>
                    </span>
                </div>

                <div class="askme-question-categories">
                    <?php if ($categories && !is_wp_error($categories)): ?>
                        <?php foreach ($categories as $category): ?>
                            <a href="<?php echo esc_url(get_term_link($category)); ?>" class="askme-category-tag">
                                <?php echo esc_html($category->name); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="askme-question-footer">
                <div class="askme-author-info">
                    <div class="askme-author-avatar">
                        <img src="<?php echo esc_url(get_avatar_url($author_id, ['size' => 48])); ?>" alt="<?php echo esc_attr($author_data['display_name'] ?? 'مستخدم'); ?>">
                    </div>
                    <div class="askme-author-details">
                        <span class="askme-author-name"><?php echo esc_html($author_data['display_name'] ?? 'مستخدم'); ?></span>
                        <span class="askme-author-rank"><?php echo esc_html($author_data['rank']['current']['name']); ?></span>
                        <div class="askme-xp-progress">
                            <div class="askme-progress-bar">
                                <div class="askme-progress-fill" style="width: <?php echo esc_attr($author_data['rank']['progress_percentage'] ?? 0); ?>%"></div>
                            </div>
                            <span class="askme-xp-text"><?php echo number_format($author_data['points']); ?> نقطة</span>
                        </div>
                    </div>
                </div>

                <div class="askme-question-time">
                    <i class="askme-icon">🕒</i>
                    <?php echo askro_time_ago($question->post_date); ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render advanced pagination
     */
    private function render_advanced_pagination($query) {
        $big = 999999999;
        $pagination = paginate_links([
            'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
            'format' => '?paged=%#%',
            'current' => max(1, get_query_var('paged')),
            'total' => $query->max_num_pages,
            'prev_text' => '<i class="askme-icon">←</i> السابق',
            'next_text' => 'التالي <i class="askme-icon">→</i>',
            'type' => 'array'
        ]);

        if (!$pagination) return '';

        ob_start();
        ?>
        <div class="askme-pagination-wrapper">
            <nav class="askme-pagination">
                <?php foreach ($pagination as $link): ?>
                    <?php echo $link; ?>
                <?php endforeach; ?>
            </nav>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render archive sidebar
     */
    private function render_archive_sidebar() {
        ob_start();
        ?>
        <div class="askme-sidebar-content">
            <!-- Community Stats -->
            <div class="askme-sidebar-module">
                <h3 class="askme-module-title">
                    <i class="askme-icon">📊</i>
                    إحصائيات المجتمع
                </h3>
                <div class="askme-stats-grid">
                    <div class="askme-stat-item">
                        <span class="askme-stat-number"><?php echo number_format(wp_count_posts('askro_question')->publish); ?></span>
                        <span class="askme-stat-label">سؤال</span>
                    </div>
                    <div class="askme-stat-item">
                        <span class="askme-stat-number"><?php echo number_format(wp_count_posts('askro_answer')->publish); ?></span>
                        <span class="askme-stat-label">إجابة</span>
                    </div>
                    <div class="askme-stat-item">
                        <span class="askme-stat-number"><?php echo number_format(count_users()['total_users']); ?></span>
                        <span class="askme-stat-label">عضو</span>
                    </div>
                </div>
            </div>

            <!-- Login/Register Module -->
            <?php if (!is_user_logged_in()): ?>
                <div class="askme-sidebar-module">
                    <h3 class="askme-module-title">
                        <i class="askme-icon">👤</i>
                        تسجيل الدخول
                    </h3>
                    <div class="askme-login-form">
                        <input type="text" placeholder="اسم المستخدم" class="askme-input">
                        <input type="password" placeholder="كلمة المرور" class="askme-input">
                        <button class="askme-btn askme-btn-primary">تسجيل الدخول</button>
                        <p class="askme-form-hint">
                            ليس لديك حساب؟ 
                            <a href="<?php echo esc_url(wp_registration_url()); ?>">سجل الآن</a>
                        </p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Top Contributors -->
            <div class="askme-sidebar-module">
                <h3 class="askme-module-title">
                    <i class="askme-icon">🏆</i>
                    أفضل المساهمين
                </h3>
                <div class="askme-leaderboard-list">
                    <?php
                    $top_users = askro_get_leaderboard_data('all_time', 5);
                    if ($top_users):
                        foreach ($top_users as $index => $user):
                    ?>
                        <div class="askme-leaderboard-item">
                            <span class="askme-rank-number"><?php echo $index + 1; ?></span>
                            <div class="askme-user-avatar">
                                <img src="<?php echo esc_url(get_avatar_url($user['id'], ['size' => 32])); ?>" alt="<?php echo esc_attr($user['display_name'] ?? 'مستخدم'); ?>">
                            </div>
                            <div class="askme-user-info">
                                <span class="askme-user-name"><?php echo esc_html($user['display_name'] ?? 'مستخدم'); ?></span>
                                <span class="askme-user-rank"><?php echo esc_html($user['rank']['current']['name']); ?></span>
                            </div>
                            <span class="askme-user-points"><?php echo number_format($user['points']); ?></span>
                        </div>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                </div>
            </div>

            <!-- Categories -->
            <div class="askme-sidebar-module">
                <h3 class="askme-module-title">
                    <i class="askme-icon">📂</i>
                    التصنيفات
                </h3>
                <div class="askme-categories-list">
                    <?php
                    $categories = get_terms([
                        'taxonomy' => 'askro_question_category',
                        'hide_empty' => true,
                        'number' => 10
                    ]);
                    if ($categories && !is_wp_error($categories)):
                        foreach ($categories as $category):
                    ?>
                        <a href="<?php echo esc_url(get_term_link($category)); ?>" class="askme-category-link">
                            <span class="askme-category-name"><?php echo esc_html($category->name); ?></span>
                            <span class="askme-category-count"><?php echo number_format($category->count); ?></span>
                        </a>
                    <?php 
                        endforeach;
                    endif;
                    ?>
                </div>
            </div>

            <!-- Recent Questions -->
            <div class="askme-sidebar-module">
                <h3 class="askme-module-title">
                    <i class="askme-icon">🕒</i>
                    الأسئلة الحديثة
                </h3>
                <div class="askme-recent-questions">
                    <?php
                    $recent_questions = get_posts([
                        'post_type' => 'askro_question',
                        'numberposts' => 5,
                        'post_status' => 'publish'
                    ]);
                    foreach ($recent_questions as $question):
                    ?>
                        <a href="<?php echo esc_url(askro_get_question_url($question->ID)); ?>" class="askme-recent-question">
                            <span class="askme-question-title"><?php echo esc_html($question->post_title); ?></span>
                            <span class="askme-question-time"><?php echo askro_time_ago($question->post_date); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render single question sidebar
     */
    public function render_single_question_sidebar($question_id) {
        $question = get_post($question_id);
        $views_count = get_post_meta($question_id, '_askro_views', true) ?: 0;
        $answers_count = askro_get_answers_count($question_id);
        $vote_count = askro_get_vote_count($question_id);
        
        ob_start();
        ?>
        <div class="askme-sidebar-content">
            <!-- Question Stats -->
            <div class="askme-sidebar-module">
                <h3 class="askme-module-title">
                    <i class="askme-icon">📊</i>
                    إحصائيات السؤال
                </h3>
                <div class="askme-stats-list">
                    <div class="askme-stat-item">
                        <span class="askme-stat-label">المشاهدات</span>
                        <span class="askme-stat-value"><?php echo number_format($views_count); ?></span>
                    </div>
                    <div class="askme-stat-item">
                        <span class="askme-stat-label">الإجابات</span>
                        <span class="askme-stat-value"><?php echo number_format($answers_count); ?></span>
                    </div>
                    <div class="askme-stat-item">
                        <span class="askme-stat-label">التصويتات</span>
                        <span class="askme-stat-value"><?php echo number_format($vote_count); ?></span>
                    </div>
                    <div class="askme-stat-item">
                        <span class="askme-stat-label">تاريخ النشر</span>
                        <span class="askme-stat-value"><?php echo human_time_diff(strtotime($question->post_date), current_time('timestamp')); ?> مضت</span>
                    </div>
                </div>
            </div>

            <!-- Related Questions -->
            <div class="askme-sidebar-module">
                <h3 class="askme-module-title">
                    <i class="askme-icon">🔗</i>
                    أسئلة مشابهة
                </h3>
                <div class="askme-related-list">
                    <?php
                    $related_questions = get_posts([
                        'post_type' => 'askro_question',
                        'post_status' => 'publish',
                        'posts_per_page' => 5,
                        'post__not_in' => [$question_id],
                        'meta_query' => [
                            [
                                'key' => '_askro_status',
                                'value' => 'solved',
                                'compare' => '='
                            ]
                        ]
                    ]);
                    
                    foreach ($related_questions as $related):
                    ?>
                        <a href="<?php echo esc_url(askro_get_question_url($related->ID)); ?>" class="askme-related-item">
                            <span class="askme-related-title"><?php echo esc_html($related->post_title); ?></span>
                            <span class="askme-related-meta"><?php echo esc_html(askro_get_answers_count($related->ID)); ?> إجابة</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Share Question -->
            <div class="askme-sidebar-module">
                <h3 class="askme-module-title">
                    <i class="askme-icon">📤</i>
                    شارك السؤال
                </h3>
                <div class="askme-share-buttons">
                    <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode($question->post_title); ?>&url=<?php echo urlencode(askro_get_question_url($question_id)); ?>" 
                       target="_blank"
                       class="askme-share-btn">
                        🐦 Twitter
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(askro_get_question_url($question_id)); ?>" 
                       target="_blank"
                       class="askme-share-btn">
                        📘 Facebook
                    </a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(askro_get_question_url($question_id)); ?>" 
                       target="_blank"
                       class="askme-share-btn">
                        💼 LinkedIn
                    </a>
                    <button class="askme-share-btn askme-copy-link"
                            data-url="<?php echo esc_url(askro_get_question_url($question_id)); ?>">
                        📋 نسخ الرابط
                    </button>
                </div>
            </div>

            <!-- Report Question -->
            <?php if (is_user_logged_in()): ?>
                <div class="askme-sidebar-module">
                    <h3 class="askme-module-title">
                        <i class="askme-icon">🚨</i>
                        الإبلاغ عن مشكلة
                    </h3>
                    <p class="askme-report-description">
                        إذا وجدت مشكلة في هذا السؤال، يمكنك الإبلاغ عنها
                    </p>
                    <button class="askme-report-btn"
                            data-question-id="<?php echo esc_attr($question_id); ?>">
                        🚨 الإبلاغ عن مشكلة
                    </button>
                </div>
            <?php endif; ?>

            <!-- Community Stats -->
            <div class="askme-sidebar-module">
                <h3 class="askme-module-title">
                    <i class="askme-icon">📊</i>
                    إحصائيات المجتمع
                </h3>
                <div class="askme-stats-grid">
                    <div class="askme-stat-item">
                        <span class="askme-stat-number"><?php echo number_format(wp_count_posts('askro_question')->publish); ?></span>
                        <span class="askme-stat-label">سؤال</span>
                    </div>
                    <div class="askme-stat-item">
                        <span class="askme-stat-number"><?php echo number_format(wp_count_posts('askro_answer')->publish); ?></span>
                        <span class="askme-stat-label">إجابة</span>
                    </div>
                    <div class="askme-stat-item">
                        <span class="askme-stat-number"><?php echo number_format(count_users()['total_users']); ?></span>
                        <span class="askme-stat-label">عضو</span>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}


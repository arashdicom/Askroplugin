<?php
/**
 * Display Class
 *
 * @package    Askro
 * @subpackage Core/Display
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
 * Askro Display Class
 *
 * Handles display of questions and answers
 *
 * @since 1.0.0
 */
class Askro_Display implements Askro_Display_Handler_Interface {

    /**
     * Initialize the display component
     *
     * @since 1.0.0
     */
    public function init() {
        add_filter('the_content', [$this, 'filter_question_content']);
        add_filter('the_content', [$this, 'filter_answer_content']);
        
        add_action('wp_ajax_askro_get_answers', [$this, 'get_answers_ajax']);
        add_action('wp_ajax_nopriv_askro_get_answers', [$this, 'get_answers_ajax']);
        
        add_action('wp_ajax_askro_load_more_questions', [$this, 'load_more_questions']);
        add_action('wp_ajax_nopriv_askro_load_more_questions', [$this, 'load_more_questions']);
    }

    /**
     * Filter question content
     *
     * @param string $content Post content
     * @return string Modified content
     * @since 1.0.0
     */
    public function filter_question_content($content) {
        if (!is_singular('askro_question')) {
            return $content;
        }

        global $post;
        
        // Get question data
        $question_data = $this->get_question_data($post->ID);
        
        // Build question display
        $question_html = $this->build_question_display($question_data);
        
        // Get answers
        $answers_html = $this->get_answers_display($post->ID);
        
        // Build answer form
        $answer_form = '';
        if (!get_post_meta($post->ID, '_askro_is_closed', true)) {
            $forms = new Askro_Forms();
            $answer_form = $forms->generate_answer_form($post->ID);
        }

        return $question_html . $answers_html . $answer_form;
    }

    /**
     * Filter answer content
     *
     * @param string $content Post content
     * @return string Modified content
     * @since 1.0.0
     */
    public function filter_answer_content($content) {
        if (!is_singular('askro_answer')) {
            return $content;
        }

        global $post;
        
        // Redirect to question page
        $question_id = get_post_meta($post->ID, '_askro_question_id', true);
        if ($question_id) {
            wp_redirect(get_permalink($question_id) . '#answer-' . $post->ID);
            exit;
        }

        return $content;
    }

    /**
     * Get question data
     *
     * @param int $question_id Question ID
     * @return array Question data
     * @since 1.0.0
     */
    public function get_question_data($question_id) {
        $post = get_post($question_id);
        if (!$post) {
            return [];
        }

        // Get meta data
        $views = get_post_meta($question_id, '_askro_views', true) ?: 0;
        $is_featured = get_post_meta($question_id, '_askro_is_featured', true);
        $is_closed = get_post_meta($question_id, '_askro_is_closed', true);
        $is_anonymous = get_post_meta($question_id, '_askro_is_anonymous', true);
        $attachments = get_post_meta($question_id, '_askro_attachments', true) ?: [];

        // Get voting data
        $voting = new Askro_Voting();
        $vote_data = $voting->get_post_votes($question_id);

        // Get categories and tags
        $categories = get_the_terms($question_id, 'askro_question_category') ?: [];
        $tags = get_the_terms($question_id, 'askro_question_tag') ?: [];

        // Get answer count
        $answer_count = $this->get_answer_count($question_id);

        // Get author data
        $author_data = $this->get_author_data($post->post_author, $is_anonymous);

        return [
            'id' => $question_id,
            'title' => $post->post_title,
            'content' => $post->post_content,
            'date' => $post->post_date,
            'views' => $views,
            'is_featured' => $is_featured,
            'is_closed' => $is_closed,
            'is_anonymous' => $is_anonymous,
            'attachments' => $attachments,
            'vote_data' => $vote_data,
            'categories' => $categories,
            'tags' => $tags,
            'answer_count' => $answer_count,
            'author' => $author_data
        ];
    }

    /**
     * Build question display
     *
     * @param array $question_data Question data
     * @return string HTML output
     * @since 1.0.0
     */
    public function build_question_display($question_data) {
        if (empty($question_data)) {
            return '';
        }

        // Increment view count
        $this->increment_view_count($question_data['id']);

        ob_start();
        ?>
        <div class="askro-question-container">
            <!-- Question Header -->
            <div class="askro-question-header">
                <div class="askro-question-meta">
                    <?php if ($question_data['is_featured']): ?>
                    <span class="askro-badge askro-badge-featured">
                        ⭐ <?php _e('مميز', 'askro'); ?>
                    </span>
                    <?php endif; ?>
                    
                    <?php if ($question_data['is_closed']): ?>
                    <span class="askro-badge askro-badge-closed">
                        🔒 <?php _e('مغلق', 'askro'); ?>
                    </span>
                    <?php endif; ?>

                    <div class="askro-question-stats">
                        <span class="askro-stat">
                            <span class="askro-stat-number"><?php echo number_format($question_data['answer_count']); ?></span>
                            <span class="askro-stat-label"><?php _e('إجابة', 'askro'); ?></span>
                        </span>
                        <span class="askro-stat">
                            <span class="askro-stat-number"><?php echo number_format($question_data['views']); ?></span>
                            <span class="askro-stat-label"><?php _e('مشاهدة', 'askro'); ?></span>
                        </span>
                    </div>
                </div>

                <h1 class="askro-question-title"><?php echo esc_html($question_data['title']); ?></h1>
            </div>

            <!-- Question Content -->
            <div class="askro-question-content">
                <div class="askro-question-body">
                    <div class="askro-content">
                        <?php echo wpautop($question_data['content']); ?>
                    </div>

                    <?php if (!empty($question_data['attachments'])): ?>
                    <div class="askro-attachments">
                        <h4 class="askro-attachments-title"><?php _e('المرفقات', 'askro'); ?></h4>
                        <div class="askro-attachments-list">
                            <?php foreach ($question_data['attachments'] as $attachment_id): ?>
                                <?php echo $this->render_attachment($attachment_id); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Categories and Tags -->
                    <?php if (!empty($question_data['categories']) || !empty($question_data['tags'])): ?>
                    <div class="askro-taxonomy">
                        <?php if (!empty($question_data['categories'])): ?>
                        <div class="askro-categories">
                            <span class="askro-taxonomy-label"><?php _e('التصنيفات:', 'askro'); ?></span>
                            <?php foreach ($question_data['categories'] as $category): ?>
                                <a href="<?php echo get_term_link($category); ?>" class="askro-category-link">
                                    <?php echo esc_html($category->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($question_data['tags'])): ?>
                        <div class="askro-tags">
                            <span class="askro-taxonomy-label"><?php _e('العلامات:', 'askro'); ?></span>
                            <?php foreach ($question_data['tags'] as $tag): ?>
                                <a href="<?php echo get_term_link($tag); ?>" class="askro-tag-link">
                                    #<?php echo esc_html($tag->name); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Voting Panel -->
                <div class="askro-voting-panel">
                    <?php echo $this->render_voting_panel($question_data['id'], $question_data['vote_data']); ?>
                </div>
            </div>

            <!-- Question Footer -->
            <div class="askro-question-footer">
                <div class="askro-question-actions">
                    <button type="button" class="askro-btn-outline askro-share-btn" data-post-id="<?php echo $question_data['id']; ?>">
                        📤 <?php _e('مشاركة', 'askro'); ?>
                    </button>
                    <button type="button" class="askro-btn-outline askro-bookmark-btn" data-post-id="<?php echo $question_data['id']; ?>">
                        🔖 <?php _e('حفظ', 'askro'); ?>
                    </button>
                    <?php if (current_user_can('edit_post', $question_data['id'])): ?>
                    <a href="<?php echo get_edit_post_link($question_data['id']); ?>" class="askro-btn-outline">
                        ✏️ <?php _e('تعديل', 'askro'); ?>
                    </a>
                    <?php endif; ?>
                </div>

                <div class="askro-question-author">
                    <?php echo $this->render_author_info($question_data['author'], $question_data['date']); ?>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Get answers display
     *
     * @param int $question_id Question ID
     * @return string HTML output
     * @since 1.0.0
     */
    public function get_answers_display($question_id) {
        $answers = $this->get_question_answers($question_id);
        
        if (empty($answers)) {
            return $this->render_no_answers();
        }

        ob_start();
        ?>
        <div class="askro-answers-container" id="answers">
            <div class="askro-answers-header">
                <h2 class="askro-heading-2">
                    <?php printf(_n('إجابة واحدة', '%s إجابة', count($answers), 'askro'), number_format(count($answers))); ?>
                </h2>
                <div class="askro-answers-sort">
                    <select class="askro-select" id="answers-sort">
                        <option value="votes"><?php _e('الأعلى تصويتاً', 'askro'); ?></option>
                        <option value="newest"><?php _e('الأحدث', 'askro'); ?></option>
                        <option value="oldest"><?php _e('الأقدم', 'askro'); ?></option>
                    </select>
                </div>
            </div>

            <div class="askro-answers-list">
                <?php foreach ($answers as $answer): ?>
                    <?php echo $this->render_answer($answer); ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Render single answer
     *
     * @param array $answer Answer data
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_answer($answer) {
        ob_start();
        ?>
        <div class="askro-answer" id="answer-<?php echo $answer['id']; ?>">
            <div class="askro-answer-content">
                <div class="askro-answer-body">
                    <div class="askro-content">
                        <?php echo wpautop($answer['content']); ?>
                    </div>

                    <?php if (!empty($answer['attachments'])): ?>
                    <div class="askro-attachments">
                        <div class="askro-attachments-list">
                            <?php foreach ($answer['attachments'] as $attachment_id): ?>
                                <?php echo $this->render_attachment($attachment_id); ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Voting Panel -->
                <div class="askro-voting-panel">
                    <?php echo $this->render_voting_panel($answer['id'], $answer['vote_data']); ?>
                </div>
            </div>

            <!-- Answer Footer -->
            <div class="askro-answer-footer">
                <div class="askro-answer-actions">
                    <?php if ($answer['is_accepted']): ?>
                    <span class="askro-badge askro-badge-accepted">
                        ✅ <?php _e('إجابة مقبولة', 'askro'); ?>
                    </span>
                    <?php endif; ?>

                    <?php if (current_user_can('edit_post', $answer['question_id']) && !$answer['is_accepted']): ?>
                    <button type="button" class="askro-btn-outline askro-accept-answer-btn" data-answer-id="<?php echo $answer['id']; ?>">
                        ✅ <?php _e('قبول الإجابة', 'askro'); ?>
                    </button>
                    <?php endif; ?>

                    <button type="button" class="askro-btn-outline askro-share-btn" data-post-id="<?php echo $answer['id']; ?>">
                        📤 <?php _e('مشاركة', 'askro'); ?>
                    </button>

                    <?php if (current_user_can('edit_post', $answer['id'])): ?>
                    <a href="<?php echo get_edit_post_link($answer['id']); ?>" class="askro-btn-outline">
                        ✏️ <?php _e('تعديل', 'askro'); ?>
                    </a>
                    <?php endif; ?>
                </div>

                <div class="askro-answer-author">
                    <?php echo $this->render_author_info($answer['author'], $answer['date']); ?>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Render voting panel
     *
     * @param int $post_id Post ID
     * @param array $vote_data Vote data
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_voting_panel($post_id, $vote_data) {
        $user_votes = $vote_data['user_votes'] ?? [];
        $total_votes = $vote_data['total_votes'] ?? 0;
        $vote_breakdown = $vote_data['vote_breakdown'] ?? [];

        ob_start();
        ?>
        <div class="askro-voting-container" data-post-id="<?php echo $post_id; ?>">
            <!-- Main Vote Score -->
            <div class="askro-vote-score">
                <div class="askro-vote-number"><?php echo number_format($total_votes); ?></div>
                <div class="askro-vote-label"><?php _e('نقطة', 'askro'); ?></div>
            </div>

            <!-- Multi-dimensional Voting -->
            <div class="askro-vote-types">
                <?php
                $vote_types = [
                    'helpful' => ['label' => __('مفيد', 'askro'), 'icon' => '👍', 'color' => 'blue'],
                    'creative' => ['label' => __('إبداعي', 'askro'), 'icon' => '💡', 'color' => 'yellow'],
                    'deep' => ['label' => __('عميق', 'askro'), 'icon' => '🧠', 'color' => 'purple'],
                    'funny' => ['label' => __('مضحك', 'askro'), 'icon' => '😄', 'color' => 'green'],
                    'emotional' => ['label' => __('عاطفي', 'askro'), 'icon' => '❤️', 'color' => 'red']
                ];

                foreach ($vote_types as $type => $config):
                    $count = $vote_breakdown[$type] ?? 0;
                    $user_voted = in_array($type, $user_votes);
                    $active_class = $user_voted ? 'active' : '';
                ?>
                <button type="button" 
                        class="askro-vote-btn askro-vote-<?php echo $type; ?> <?php echo $active_class; ?>"
                        data-post-id="<?php echo $post_id; ?>"
                        data-vote-type="<?php echo $type; ?>"
                        data-tooltip="<?php echo esc_attr($config['label']); ?>">
                    <span class="askro-vote-icon"><?php echo $config['icon']; ?></span>
                    <span class="askro-vote-count"><?php echo number_format($count); ?></span>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Vote Breakdown -->
            <?php if ($total_votes > 0): ?>
            <div class="askro-vote-breakdown">
                <button type="button" class="askro-vote-breakdown-toggle">
                    <?php _e('عرض التفاصيل', 'askro'); ?>
                </button>
                <div class="askro-vote-breakdown-content" style="display: none;">
                    <?php foreach ($vote_types as $type => $config): 
                        $count = $vote_breakdown[$type] ?? 0;
                        $percentage = $total_votes > 0 ? ($count / $total_votes) * 100 : 0;
                    ?>
                    <div class="askro-vote-breakdown-item">
                        <span class="askro-vote-breakdown-label">
                            <?php echo $config['icon']; ?> <?php echo $config['label']; ?>
                        </span>
                        <div class="askro-vote-breakdown-bar">
                            <div class="askro-vote-breakdown-fill askro-vote-<?php echo $type; ?>" 
                                 style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <span class="askro-vote-breakdown-count"><?php echo number_format($count); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Render author info
     *
     * @param array $author Author data
     * @param string $date Post date
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_author_info($author, $date) {
        ob_start();
        ?>
        <div class="askro-author-info">
            <div class="askro-author-avatar">
                <?php if ($author['is_anonymous']): ?>
                    <div class="askro-anonymous-avatar">👤</div>
                <?php else: ?>
                    <?php echo get_avatar($author['id'], 48); ?>
                <?php endif; ?>
            </div>
            <div class="askro-author-details">
                <div class="askro-author-name">
                    <?php if ($author['is_anonymous']): ?>
                        <?php _e('مستخدم مجهول', 'askro'); ?>
                    <?php else: ?>
                        <a href="<?php echo get_author_posts_url($author['id']); ?>">
                            <?php echo esc_html($author['display_name']); ?>
                        </a>
                    <?php endif; ?>
                </div>
                <div class="askro-author-meta">
                    <span class="askro-post-date">
                        <?php echo human_time_diff(strtotime($date), current_time('timestamp')); ?> <?php _e('مضت', 'askro'); ?>
                    </span>
                    <?php if (!$author['is_anonymous']): ?>
                    <span class="askro-author-points">
                        <?php echo number_format($author['points']); ?> <?php _e('نقطة', 'askro'); ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Render attachment
     *
     * @param int $attachment_id Attachment ID
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_attachment($attachment_id) {
        $attachment = get_post($attachment_id);
        if (!$attachment) {
            return '';
        }

        $file_url = wp_get_attachment_url($attachment_id);
        $file_type = get_post_mime_type($attachment_id);
        $file_size = size_format(filesize(get_attached_file($attachment_id)));

        ob_start();
        ?>
        <div class="askro-attachment">
            <?php if (strpos($file_type, 'image/') === 0): ?>
                <div class="askro-attachment-image">
                    <a href="<?php echo $file_url; ?>" target="_blank">
                        <?php echo wp_get_attachment_image($attachment_id, 'medium'); ?>
                    </a>
                </div>
            <?php else: ?>
                <div class="askro-attachment-file">
                    <a href="<?php echo $file_url; ?>" target="_blank" class="askro-file-link">
                        <div class="askro-file-icon">📎</div>
                        <div class="askro-file-info">
                            <div class="askro-file-name"><?php echo esc_html($attachment->post_title); ?></div>
                            <div class="askro-file-size"><?php echo $file_size; ?></div>
                        </div>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Render no answers message
     *
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_no_answers() {
        ob_start();
        ?>
        <div class="askro-no-answers">
            <div class="askro-no-answers-content">
                <div class="askro-no-answers-icon">💭</div>
                <h3 class="askro-heading-3"><?php _e('لا توجد إجابات بعد', 'askro'); ?></h3>
                <p class="askro-body-text">
                    <?php _e('كن أول من يجيب على هذا السؤال وساعد المجتمع!', 'askro'); ?>
                </p>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Get question answers
     *
     * @param int $question_id Question ID
     * @return array Answers data
     * @since 1.0.0
     */
    public function get_question_answers($question_id) {
        $answers = get_posts([
            'post_type' => 'askro_answer',
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_askro_question_id',
                    'value' => $question_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => -1,
            'orderby' => 'meta_value_num',
            'meta_key' => '_askro_vote_score',
            'order' => 'DESC'
        ]);

        $answers_data = [];
        $voting = new Askro_Voting();

        foreach ($answers as $answer) {
            $is_anonymous = get_post_meta($answer->ID, '_askro_is_anonymous', true);
            $is_accepted = get_post_meta($answer->ID, '_askro_is_accepted', true);
            $attachments = get_post_meta($answer->ID, '_askro_attachments', true) ?: [];

            $answers_data[] = [
                'id' => $answer->ID,
                'content' => $answer->post_content,
                'date' => $answer->post_date,
                'question_id' => $question_id,
                'is_anonymous' => $is_anonymous,
                'is_accepted' => $is_accepted,
                'attachments' => $attachments,
                'vote_data' => $voting->get_post_votes($answer->ID),
                'author' => $this->get_author_data($answer->post_author, $is_anonymous)
            ];
        }

        return $answers_data;
    }

    /**
     * Get author data
     *
     * @param int $user_id User ID
     * @param bool $is_anonymous Is anonymous
     * @return array Author data
     * @since 1.0.0
     */
    public function get_author_data($user_id, $is_anonymous = false) {
        if ($is_anonymous || !$user_id) {
            return [
                'id' => 0,
                'display_name' => __('مستخدم مجهول', 'askro'),
                'is_anonymous' => true,
                'points' => 0
            ];
        }

        global $askro_user_helper;
        $user = $askro_user_helper->get_user($user_id);
        if (!$user) {
            return [
                'id' => 0,
                'display_name' => __('مستخدم محذوف', 'askro'),
                'is_anonymous' => true,
                'points' => 0
            ];
        }

        return [
            'id' => $user_id,
            'display_name' => $user->display_name,
            'is_anonymous' => false,
            'points' => askro_get_user_points($user_id)
        ];
    }

    /**
     * Get answer count for question
     *
     * @param int $question_id Question ID
     * @return int Answer count
     * @since 1.0.0
     */
    public function get_answer_count($question_id) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'askro_answer'
             AND p.post_status = 'publish'
             AND pm.meta_key = '_askro_question_id'
             AND pm.meta_value = %d",
            $question_id
        ));
    }

    /**
     * Increment view count
     *
     * @param int $question_id Question ID
     * @since 1.0.0
     */
    public function increment_view_count($question_id) {
        $views = get_post_meta($question_id, '_askro_views', true) ?: 0;
        update_post_meta($question_id, '_askro_views', $views + 1);
    }

    /**
     * Get answers via AJAX
     *
     * @since 1.0.0
     */
    public function get_answers_ajax() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'askro_nonce')) {
            wp_send_json_error(['message' => __('خطأ في التحقق من الأمان.', 'askro')]);
        }

        $question_id = intval($_POST['question_id'] ?? 0);
        if (!$question_id) {
            wp_send_json_error(['message' => __('معرف السؤال مطلوب.', 'askro')]);
        }

        $answers_html = $this->get_answers_display($question_id);

        wp_send_json_success(['html' => $answers_html]);
    }

    /**
     * Load more questions via AJAX
     *
     * @since 1.0.0
     */
    public function load_more_questions() {
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'askro_nonce')) {
            wp_send_json_error(['message' => __('خطأ في التحقق من الأمان.', 'askro')]);
        }

        $page = intval($_POST['page'] ?? 1);
        $per_page = intval($_POST['per_page'] ?? 10);
        $category = sanitize_text_field($_POST['category'] ?? '');
        $tag = sanitize_text_field($_POST['tag'] ?? '');
        $sort = sanitize_text_field($_POST['sort'] ?? 'newest');

        $args = [
            'post_type' => 'askro_question',
            'post_status' => 'publish',
            'posts_per_page' => $per_page,
            'paged' => $page
        ];

        // Add sorting
        switch ($sort) {
            case 'votes':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_askro_vote_score';
                $args['order'] = 'DESC';
                break;
            case 'views':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_askro_views';
                $args['order'] = 'DESC';
                break;
            case 'answers':
                $args['orderby'] = 'meta_value_num';
                $args['meta_key'] = '_askro_answer_count';
                $args['order'] = 'DESC';
                break;
            case 'oldest':
                $args['orderby'] = 'date';
                $args['order'] = 'ASC';
                break;
            default: // newest
                $args['orderby'] = 'date';
                $args['order'] = 'DESC';
                break;
        }

        // Add taxonomy filters
        if ($category || $tag) {
            $args['tax_query'] = ['relation' => 'AND'];
            
            if ($category) {
                $args['tax_query'][] = [
                    'taxonomy' => 'askro_question_category',
                    'field' => 'slug',
                    'terms' => $category
                ];
            }
            
            if ($tag) {
                $args['tax_query'][] = [
                    'taxonomy' => 'askro_question_tag',
                    'field' => 'slug',
                    'terms' => $tag
                ];
            }
        }

        $questions = get_posts($args);
        $html = '';

        foreach ($questions as $question) {
            $html .= $this->render_question_card($question);
        }

        wp_send_json_success([
            'html' => $html,
            'has_more' => count($questions) === $per_page
        ]);
    }

    /**
     * Render question card for listings
     *
     * @param WP_Post $question Question post
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_question_card($question) {
        $question_data = $this->get_question_data($question->ID);

        ob_start();
        ?>
        <div class="askro-question-card">
            <div class="askro-question-card-stats">
                <div class="askro-stat">
                    <span class="askro-stat-number"><?php echo number_format($question_data['vote_data']['total_votes']); ?></span>
                    <span class="askro-stat-label"><?php _e('تصويت', 'askro'); ?></span>
                </div>
                <div class="askro-stat">
                    <span class="askro-stat-number"><?php echo number_format($question_data['answer_count']); ?></span>
                    <span class="askro-stat-label"><?php _e('إجابة', 'askro'); ?></span>
                </div>
                <div class="askro-stat">
                    <span class="askro-stat-number"><?php echo number_format($question_data['views']); ?></span>
                    <span class="askro-stat-label"><?php _e('مشاهدة', 'askro'); ?></span>
                </div>
            </div>

            <div class="askro-question-card-content">
                <h3 class="askro-question-card-title">
                    <a href="<?php echo get_permalink($question->ID); ?>">
                        <?php echo esc_html($question->post_title); ?>
                    </a>
                </h3>

                <div class="askro-question-card-excerpt">
                    <?php echo wp_trim_words(strip_tags($question->post_content), 20); ?>
                </div>

                <div class="askro-question-card-meta">
                    <div class="askro-question-card-author">
                        <?php echo $this->render_author_info($question_data['author'], $question->post_date); ?>
                    </div>

                    <?php if (!empty($question_data['tags'])): ?>
                    <div class="askro-question-card-tags">
                        <?php foreach (array_slice($question_data['tags'], 0, 3) as $tag): ?>
                            <a href="<?php echo get_term_link($tag); ?>" class="askro-tag-link">
                                #<?php echo esc_html($tag->name); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }
}


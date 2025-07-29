<?php
/**
 * Comments Class
 *
 * @package    Askro
 * @subpackage Core/Comments
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
 * Askro Comments Class
 *
 * Handles custom comments system for questions and answers
 *
 * @since 1.0.0
 */
class Askro_Comments {

    /**
     * Initialize the comments component
     *
     * @since 1.0.0
     */
    public function init() {
        add_action('wp_ajax_askro_add_comment', [$this, 'add_comment']);
        add_action('wp_ajax_nopriv_askro_add_comment', [$this, 'add_comment']);
        
        add_action('wp_ajax_askro_edit_comment', [$this, 'edit_comment']);
        add_action('wp_ajax_askro_delete_comment', [$this, 'delete_comment']);
        
        add_action('wp_ajax_askro_load_comments', [$this, 'load_comments']);
        add_action('wp_ajax_nopriv_askro_load_comments', [$this, 'load_comments']);
        
        add_action('wp_ajax_askro_vote_comment', [$this, 'vote_comment']);
        add_action('wp_ajax_nopriv_askro_vote_comment', [$this, 'vote_comment']);
    }

    /**
     * Render comments section
     *
     * @param int $post_id Post ID (question or answer)
     * @param array $args Arguments
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_comments_section($post_id, $args = []) {
        $defaults = [
            'show_form' => true,
            'max_depth' => 3,
            'per_page' => 10,
            'order' => 'ASC'
        ];

        $args = wp_parse_args($args, $defaults);
        
        // Debug logging removed for production
        $comments = $this->get_comments($post_id, $args);
        $comment_count = $this->get_comment_count($post_id);

        ob_start();
        ?>
        <div class="askro-comments-section" data-post-id="<?php echo $post_id; ?>">
            <!-- Comments Header -->
            <div class="askro-comments-header">
                <h4 class="askro-comments-title">
                    <?php printf(_n('تعليق واحد', '%s تعليق', $comment_count, 'askro'), number_format($comment_count)); ?>
                </h4>
                
                <?php if ($comment_count > $args['per_page']): ?>
                <button type="button" class="askro-btn-outline askro-load-more-comments" 
                        data-post-id="<?php echo $post_id; ?>"
                        data-page="2">
                    <?php _e('عرض المزيد من التعليقات', 'askro'); ?>
                </button>
                <?php endif; ?>
            </div>

            <!-- Comments List -->
            <div class="askro-comments-list" id="comments-list-<?php echo $post_id; ?>">
                <?php if (!empty($comments)): ?>
                    <?php foreach ($comments as $comment): ?>
                        <?php echo $this->render_comment($comment, $args); ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="askro-no-comments">
                        <p><?php _e('لا توجد تعليقات بعد. كن أول من يعلق!', 'askro'); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Add Comment Form -->
            <?php if ($args['show_form']): ?>
                <?php echo $this->render_comment_form($post_id); ?>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Render single comment
     *
     * @param array $comment Comment data
     * @param array $args Arguments
     * @param int $depth Comment depth
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_comment($comment, $args = [], $depth = 0) {
        $max_depth = $args['max_depth'] ?? 3;
        $can_reply = $depth < $max_depth;
        $can_edit = $this->can_edit_comment($comment['id']);
        $can_delete = $this->can_delete_comment($comment['id']);

        ob_start();
        ?>
        <div class="askro-comment" id="comment-<?php echo $comment['id']; ?>" data-comment-id="<?php echo $comment['id']; ?>">
            <div class="askro-comment-content">
                <!-- Comment Avatar -->
                <div class="askro-comment-avatar">
                    <?php if ($comment['is_anonymous']): ?>
                        <div class="askro-anonymous-avatar">👤</div>
                    <?php else: ?>
                        <?php echo get_avatar($comment['user_id'], 32); ?>
                    <?php endif; ?>
                </div>

                <!-- Comment Body -->
                <div class="askro-comment-body">
                    <!-- Comment Header -->
                    <div class="askro-comment-header">
                        <div class="askro-comment-author">
                            <?php if ($comment['is_anonymous']): ?>
                                <span class="askro-anonymous-name"><?php _e('مستخدم مجهول', 'askro'); ?></span>
                            <?php else: ?>
                                <a href="<?php echo get_author_posts_url($comment['user_id']); ?>" class="askro-author-link">
                                    <?php echo esc_html($comment['author_name']); ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="askro-comment-meta">
                            <span class="askro-comment-date">
                                <?php echo human_time_diff(strtotime($comment['date']), current_time('timestamp')); ?> <?php _e('مضت', 'askro'); ?>
                            </span>
                            
                            <?php if ($comment['is_edited']): ?>
                            <span class="askro-comment-edited" title="<?php _e('تم التعديل', 'askro'); ?>">
                                ✏️
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Comment Text -->
                    <div class="askro-comment-text">
                        <?php echo wpautop(esc_html($comment['content'])); ?>
                    </div>

                    <!-- Comment Actions -->
                    <div class="askro-comment-actions">
                        <!-- Vote Buttons -->
                        <div class="askro-comment-votes">
                            <button type="button" 
                                    class="askro-vote-btn askro-vote-up <?php echo $comment['user_vote'] === 'up' ? 'active' : ''; ?>"
                                    data-comment-id="<?php echo $comment['id']; ?>"
                                    data-vote-type="up">
                                👍 <span class="vote-count"><?php echo $comment['upvotes']; ?></span>
                            </button>
                            
                            <button type="button" 
                                    class="askro-vote-btn askro-vote-down <?php echo $comment['user_vote'] === 'down' ? 'active' : ''; ?>"
                                    data-comment-id="<?php echo $comment['id']; ?>"
                                    data-vote-type="down">
                                👎 <span class="vote-count"><?php echo $comment['downvotes']; ?></span>
                            </button>
                        </div>

                        <!-- Action Buttons -->
                        <div class="askro-comment-buttons">
                            <?php if ($can_reply): ?>
                            <button type="button" class="askro-btn-text askro-reply-btn" 
                                    data-comment-id="<?php echo $comment['id']; ?>">
                                💬 <?php _e('رد', 'askro'); ?>
                            </button>
                            <?php endif; ?>

                            <?php if ($can_edit): ?>
                            <button type="button" class="askro-btn-text askro-edit-comment-btn" 
                                    data-comment-id="<?php echo $comment['id']; ?>">
                                ✏️ <?php _e('تعديل', 'askro'); ?>
                            </button>
                            <?php endif; ?>

                            <?php if ($can_delete): ?>
                            <button type="button" class="askro-btn-text askro-delete-comment-btn" 
                                    data-comment-id="<?php echo $comment['id']; ?>">
                                🗑️ <?php _e('حذف', 'askro'); ?>
                            </button>
                            <?php endif; ?>

                            <button type="button" class="askro-btn-text askro-share-comment-btn" 
                                    data-comment-id="<?php echo $comment['id']; ?>">
                                📤 <?php _e('مشاركة', 'askro'); ?>
                            </button>
                        </div>
                    </div>

                    <!-- Reply Form (Hidden by default) -->
                    <div class="askro-reply-form" id="reply-form-<?php echo $comment['id']; ?>" style="display: none;">
                        <?php echo $this->render_comment_form($comment['post_id'], $comment['id']); ?>
                    </div>

                    <!-- Edit Form (Hidden by default) -->
                    <div class="askro-edit-form" id="edit-form-<?php echo $comment['id']; ?>" style="display: none;">
                        <?php echo $this->render_edit_form($comment); ?>
                    </div>
                </div>
            </div>

            <!-- Child Comments -->
            <?php if (!empty($comment['children'])): ?>
            <div class="askro-comment-children">
                <?php foreach ($comment['children'] as $child_comment): ?>
                    <?php echo $this->render_comment($child_comment, $args, $depth + 1); ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Render comment form
     *
     * @param int $post_id Post ID
     * @param int $parent_id Parent comment ID (for replies)
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_comment_form($post_id, $parent_id = 0) {
        $form_id = $parent_id ? "reply-form-{$parent_id}" : "comment-form-{$post_id}";
        $placeholder = $parent_id ? __('اكتب ردك...', 'askro') : __('اكتب تعليقك...', 'askro');
        $submit_text = $parent_id ? __('إرسال الرد', 'askro') : __('إرسال التعليق', 'askro');

        ob_start();
        ?>
        <div class="askro-comment-form-container">
            <form class="askro-comment-form" data-post-id="<?php echo $post_id; ?>" data-parent-id="<?php echo $parent_id; ?>">
                <?php wp_nonce_field('askro_add_comment', 'askro_comment_nonce'); ?>
                
                <div class="askro-comment-form-header">
                    <div class="askro-comment-form-avatar">
                        <?php if (is_user_logged_in()): ?>
                            <?php echo get_avatar(get_current_user_id(), 32); ?>
                        <?php else: ?>
                            <div class="askro-anonymous-avatar">👤</div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="askro-comment-form-title">
                        <?php echo $parent_id ? __('إضافة رد', 'askro') : __('إضافة تعليق', 'askro'); ?>
                    </div>
                </div>

                <div class="askro-comment-form-body">
                    <textarea name="comment_content" 
                              class="askro-comment-textarea" 
                              placeholder="<?php echo esc_attr($placeholder); ?>"
                              rows="3"
                              maxlength="1000"
                              required></textarea>
                    
                    <div class="askro-comment-form-meta">
                        <div class="askro-char-counter">
                            <span class="current">0</span>/<span class="max">1000</span>
                        </div>
                        
                        <?php if (!is_user_logged_in()): ?>
                        <label class="askro-checkbox-label">
                            <input type="checkbox" name="comment_anonymous" value="1" class="askro-checkbox">
                            <span class="askro-checkbox-text"><?php _e('تعليق مجهول', 'askro'); ?></span>
                        </label>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="askro-comment-form-actions">
                    <?php if ($parent_id): ?>
                    <button type="button" class="askro-btn-outline askro-cancel-reply-btn">
                        <?php _e('إلغاء', 'askro'); ?>
                    </button>
                    <?php endif; ?>
                    
                    <button type="submit" class="askro-btn-primary askro-submit-comment-btn">
                        <span class="askro-btn-text"><?php echo $submit_text; ?></span>
                        <span class="askro-btn-loading" style="display: none;">
                            <div class="askro-spinner"></div>
                            <?php _e('جاري الإرسال...', 'askro'); ?>
                        </span>
                    </button>
                </div>
            </form>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Render edit form
     *
     * @param array $comment Comment data
     * @return string HTML output
     * @since 1.0.0
     */
    public function render_edit_form($comment) {
        ob_start();
        ?>
        <div class="askro-edit-comment-form">
            <form class="askro-comment-edit-form" data-comment-id="<?php echo $comment['id']; ?>">
                <?php wp_nonce_field('askro_edit_comment', 'askro_edit_comment_nonce'); ?>
                
                <textarea name="comment_content" 
                          class="askro-comment-textarea" 
                          rows="3"
                          maxlength="1000"
                          required><?php echo esc_textarea($comment['content']); ?></textarea>
                
                <div class="askro-edit-form-actions">
                    <button type="button" class="askro-btn-outline askro-cancel-edit-btn">
                        <?php _e('إلغاء', 'askro'); ?>
                    </button>
                    
                    <button type="submit" class="askro-btn-primary askro-save-edit-btn">
                        <span class="askro-btn-text"><?php _e('حفظ التعديل', 'askro'); ?></span>
                        <span class="askro-btn-loading" style="display: none;">
                            <div class="askro-spinner"></div>
                            <?php _e('جاري الحفظ...', 'askro'); ?>
                        </span>
                    </button>
                </div>
            </form>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Add comment via AJAX
     *
     * @since 1.0.0
     */
    public function add_comment() {
        global $askro_response_handler, $askro_security_helper;
        
        // Verify nonce
        if (!$askro_security_helper->verify_nonce('askro_comment_nonce', 'askro_add_comment')) {
            return; // Error already handled by security helper
        }

        // Validate input
        $post_id = intval($_POST['post_id'] ?? 0);
        $parent_id = intval($_POST['parent_id'] ?? 0);
        $content = sanitize_textarea_field($_POST['comment_content'] ?? '');
        $is_anonymous = !empty($_POST['comment_anonymous']);

        if (!$post_id || !$content) {
            $askro_response_handler->send_error('missing_data');
        }

        // Check if user can comment
        if (!$askro_security_helper->verify_capability('askro_add_comment')) {
            return; // Error already handled by security helper
        }

        // Validate content length
        if (strlen($content) < 5) {
            $askro_response_handler->send_error('content_too_short');
        }

        if (strlen($content) > 1000) {
            $askro_response_handler->send_error('content_too_long');
        }

        // Check for spam
        if ($this->is_spam_comment($content)) {
            $askro_response_handler->send_error('spam_detected');
        }

        // Create comment
        // Log comment creation attempt
        // Debug logging removed for production
        $comment_id = $this->create_comment([
            'post_id' => $post_id,
            'parent_id' => $parent_id,
            'content' => $content,
            'user_id' => is_user_logged_in() ? get_current_user_id() : 0,
            'is_anonymous' => $is_anonymous,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        // Log comment created
        // Debug logging removed for production

        if (is_wp_error($comment_id)) {
            wp_send_json_error(['message' => $comment_id->get_error_message()]);
        }

        // Award points
        if (is_user_logged_in()) {
            askro_award_points(get_current_user_id(), 2, 'comment_added');
        }

        // Get comment data for response
        $comment_data = $this->get_comment($comment_id);
        // Debug logging removed for production
        $comment_html = $this->render_comment($comment_data);

        // Debug logging removed for production
        wp_send_json_success([
            'message' => __('تم إضافة التعليق بنجاح!', 'askro'),
            'comment_id' => $comment_id,
            'comment_html' => $comment_html
        ]);
    }

    /**
     * Edit comment via AJAX
     *
     * @since 1.0.0
     */
    public function edit_comment() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['askro_edit_comment_nonce'] ?? '', 'askro_edit_comment')) {
            wp_send_json_error(['message' => __('خطأ في التحقق من الأمان.', 'askro')]);
        }

        $comment_id = intval($_POST['comment_id'] ?? 0);
        $content = sanitize_textarea_field($_POST['comment_content'] ?? '');

        if (!$comment_id || !$content) {
            wp_send_json_error(['message' => __('البيانات المطلوبة مفقودة.', 'askro')]);
        }

        // Check permissions
        if (!$this->can_edit_comment($comment_id)) {
            wp_send_json_error(['message' => __('غير مصرح لك بتعديل هذا التعليق.', 'askro')]);
        }

        // Validate content
        if (strlen($content) < 5 || strlen($content) > 1000) {
            wp_send_json_error(['message' => __('طول التعليق غير صحيح.', 'askro')]);
        }

        // Update comment
        $result = $this->update_comment($comment_id, [
            'content' => $content,
            'is_edited' => true,
            'edited_date' => current_time('mysql')
        ]);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('تم تعديل التعليق بنجاح!', 'askro'),
            'content' => wpautop(esc_html($content))
        ]);
    }

    /**
     * Delete comment via AJAX
     *
     * @since 1.0.0
     */
    public function delete_comment() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'askro_nonce')) {
            wp_send_json_error(['message' => __('خطأ في التحقق من الأمان.', 'askro')]);
        }

        $comment_id = intval($_POST['comment_id'] ?? 0);

        if (!$comment_id) {
            wp_send_json_error(['message' => __('معرف التعليق مطلوب.', 'askro')]);
        }

        // Check permissions
        if (!$this->can_delete_comment($comment_id)) {
            wp_send_json_error(['message' => __('غير مصرح لك بحذف هذا التعليق.', 'askro')]);
        }

        // Delete comment
        $result = $this->delete_comment_by_id($comment_id);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success(['message' => __('تم حذف التعليق بنجاح!', 'askro')]);
    }

    /**
     * Vote on comment via AJAX
     *
     * @since 1.0.0
     */
    public function vote_comment() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'askro_nonce')) {
            wp_send_json_error(['message' => __('خطأ في التحقق من الأمان.', 'askro')]);
        }

        $comment_id = intval($_POST['comment_id'] ?? 0);
        $vote_type = sanitize_text_field($_POST['vote_type'] ?? '');

        if (!$comment_id || !in_array($vote_type, ['up', 'down'])) {
            wp_send_json_error(['message' => __('بيانات التصويت غير صحيحة.', 'askro')]);
        }

        // Check if user can vote
        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => __('يجب تسجيل الدخول للتصويت.', 'askro')]);
        }

        // Process vote
        $result = $this->process_comment_vote($comment_id, get_current_user_id(), $vote_type);

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }

        wp_send_json_success([
            'message' => __('تم تسجيل تصويتك!', 'askro'),
            'upvotes' => $result['upvotes'],
            'downvotes' => $result['downvotes'],
            'user_vote' => $result['user_vote']
        ]);
    }

    /**
     * Load comments via AJAX
     *
     * @since 1.0.0
     */
    public function load_comments() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'askro_nonce')) {
            wp_send_json_error(['message' => __('خطأ في التحقق من الأمان.', 'askro')]);
        }

        $post_id = intval($_POST['post_id'] ?? 0);
        $page = intval($_POST['page'] ?? 1);

        if (!$post_id) {
            wp_send_json_error(['message' => __('معرف المنشور مطلوب.', 'askro')]);
        }

        $comments = $this->get_comments($post_id, ['page' => $page]);
        $html = '';

        foreach ($comments as $comment) {
            $html .= $this->render_comment($comment);
        }

        wp_send_json_success([
            'html' => $html,
            'has_more' => count($comments) === 10 // Assuming 10 per page
        ]);
    }

    /**
     * Get comments for a post
     *
     * @param int $post_id Post ID
     * @param array $args Arguments
     * @return array Comments data
     * @since 1.0.0
     */
    public function get_comments($post_id, $args = []) {
        global $wpdb;

        $defaults = [
            'page' => 1,
            'per_page' => 10,
            'order' => 'ASC',
            'parent_id' => 0
        ];

        $args = wp_parse_args($args, $defaults);
        $offset = ($args['page'] - 1) * $args['per_page'];

        $comments = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}askro_comments 
             WHERE post_id = %d AND parent_id = %d AND status = 'approved'
             ORDER BY created_at {$args['order']}
             LIMIT %d OFFSET %d",
            $post_id,
            $args['parent_id'], 
            $args['per_page'],
            $offset
        ), ARRAY_A);

        $comments_data = [];
        foreach ($comments as $comment) {
            $comment_data = $this->format_comment_data($comment);
            $comment_data['children'] = $this->get_comment_children($comment['id']);
            $comments_data[] = $comment_data;
        }

        return $comments_data;
    }

    /**
     * Get comment children (replies)
     *
     * @param int $parent_id Parent comment ID
     * @return array Children comments
     * @since 1.0.0
     */
    public function get_comment_children($parent_id) {
        global $wpdb;

        $children = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}askro_comments 
             WHERE parent_id = %d AND status = 'approved'
             ORDER BY created_at ASC",
            $parent_id
        ), ARRAY_A);

        $children_data = [];
        foreach ($children as $child) {
            $child_data = $this->format_comment_data($child);
            $child_data['children'] = $this->get_comment_children($child['id']); // Recursive
            $children_data[] = $child_data;
        }

        return $children_data;
    }

    /**
     * Format comment data
     *
     * @param array $comment Raw comment data
     * @return array Formatted comment data
     * @since 1.0.0
     */
    public function format_comment_data($comment) {
        $user_vote = '';
        if (is_user_logged_in()) {
            $user_vote = $this->get_user_comment_vote($comment['id'], get_current_user_id());
        }

        $author_name = '';
        if (!$comment['is_anonymous'] && $comment['user_id']) {
            $user = get_userdata($comment['user_id']);
            $author_name = $user ? $user->display_name : __('مستخدم محذوف', 'askro');
        }

        return [
            'id' => $comment['id'],
            'post_id' => $comment['post_id'],
            'parent_id' => $comment['parent_id'],
            'content' => $comment['content'],
            'user_id' => $comment['user_id'],
            'author_name' => $author_name,
            'is_anonymous' => $comment['is_anonymous'] ?? 0,
            'date' => $comment['created_at'],
            'is_edited' => $comment['is_edited'] ?? 0,
            'upvotes' => $comment['upvotes'] ?? 0,
            'downvotes' => $comment['downvotes'] ?? 0,
            'user_vote' => $user_vote,
            'children' => []
        ];
    }

    /**
     * Create new comment
     *
     * @param array $data Comment data
     * @return int|WP_Error Comment ID or error
     * @since 1.0.0
     */
    public function create_comment($data) {
        global $wpdb;

        // Debug logging removed for production
        
        $result = $wpdb->insert(
            $wpdb->prefix . 'askro_comments',
            [
                'post_id' => $data['post_id'],
                'parent_id' => $data['parent_id'],
                'content' => $data['content'],
                'user_id' => $data['user_id'],
                'answer_id' => $data['post_id'], // Add answer_id for compatibility
                'created_at' => current_time('mysql'),
                'status' => 'approved'
            ],
            ['%d', '%d', '%s', '%d', '%d', '%s', '%s']
        );

        if ($result === false) {
            // Debug logging removed for production
            return new WP_Error('db_error', __('فشل في إنشاء التعليق.', 'askro'));
        }

        $comment_id = $wpdb->insert_id;
        // Debug logging removed for production
        
        return $comment_id;
    }

    /**
     * Update comment
     *
     * @param int $comment_id Comment ID
     * @param array $data Update data
     * @return bool|WP_Error True on success, error on failure
     * @since 1.0.0
     */
    public function update_comment($comment_id, $data) {
        global $wpdb;

        $result = $wpdb->update(
            $wpdb->prefix . 'askro_comments',
            $data,
            ['id' => $comment_id],
            null,
            ['%d']
        );

        if ($result === false) {
            return new WP_Error('db_error', __('فشل في تحديث التعليق.', 'askro'));
        }

        return true;
    }

    /**
     * Delete comment by ID
     *
     * @param int $comment_id Comment ID
     * @return bool|WP_Error True on success, error on failure
     * @since 1.0.0
     */
    public function delete_comment_by_id($comment_id) {
        global $wpdb;

        // Mark as deleted instead of actual deletion
        $result = $wpdb->update(
            $wpdb->prefix . 'askro_comments',
            ['status' => 'deleted'],
            ['id' => $comment_id],
            ['%s'],
            ['%d']
        );

        if ($result === false) {
            return new WP_Error('db_error', __('فشل في حذف التعليق.', 'askro'));
        }

        return true;
    }

    /**
     * Get single comment
     *
     * @param int $comment_id Comment ID
     * @return array|null Comment data
     * @since 1.0.0
     */
    public function get_comment($comment_id) {
        global $wpdb;

        $comment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}askro_comments WHERE id = %d",
            $comment_id
        ), ARRAY_A);

        return $comment ? $this->format_comment_data($comment) : null;
    }

    /**
     * Get comment count for post
     *
     * @param int $post_id Post ID
     * @return int Comment count
     * @since 1.0.0
     */
    public function get_comment_count($post_id) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_comments 
             WHERE post_id = %d AND status = 'approved'",
            $post_id
        ));
    }

    /**
     * Process comment vote
     *
     * @param int $comment_id Comment ID
     * @param int $user_id User ID
     * @param string $vote_type Vote type (up/down)
     * @return array|WP_Error Vote result or error
     * @since 1.0.0
     */
    public function process_comment_vote($comment_id, $user_id, $vote_type) {
        global $wpdb;

        // Check existing vote
        $existing_vote = $wpdb->get_var($wpdb->prepare(
            "SELECT vote_type FROM {$wpdb->prefix}askro_comment_votes 
             WHERE comment_id = %d AND user_id = %d",
            $comment_id,
            $user_id
        ));

        if ($existing_vote === $vote_type) {
            // Remove vote
            $wpdb->delete(
                $wpdb->prefix . 'askro_comment_votes',
                ['comment_id' => $comment_id, 'user_id' => $user_id],
                ['%d', '%d']
            );
            $user_vote = '';
        } elseif ($existing_vote) {
            // Update vote
            $wpdb->update(
                $wpdb->prefix . 'askro_comment_votes',
                ['vote_type' => $vote_type],
                ['comment_id' => $comment_id, 'user_id' => $user_id],
                ['%s'],
                ['%d', '%d']
            );
            $user_vote = $vote_type;
        } else {
            // Insert new vote
            $wpdb->insert(
                $wpdb->prefix . 'askro_comment_votes',
                [
                    'comment_id' => $comment_id,
                    'user_id' => $user_id,
                    'vote_type' => $vote_type,
                    'created_at' => current_time('mysql')
                ],
                ['%d', '%d', '%s', '%s']
            );
            $user_vote = $vote_type;
        }

        // Update comment vote counts
        $upvotes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_comment_votes 
             WHERE comment_id = %d AND vote_type = 'up'",
            $comment_id
        ));

        $downvotes = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}askro_comment_votes 
             WHERE comment_id = %d AND vote_type = 'down'",
            $comment_id
        ));

        // Note: The comments table doesn't have upvotes/downvotes columns by default
        // We calculate them dynamically, but we can add these columns if needed

        return [
            'upvotes' => $upvotes,
            'downvotes' => $downvotes,
            'user_vote' => $user_vote
        ];
    }

    /**
     * Get user's vote on comment
     *
     * @param int $comment_id Comment ID
     * @param int $user_id User ID
     * @return string Vote type or empty string
     * @since 1.0.0
     */
    public function get_user_comment_vote($comment_id, $user_id) {
        global $wpdb;

        return $wpdb->get_var($wpdb->prepare(
            "SELECT vote_type FROM {$wpdb->prefix}askro_comment_votes 
             WHERE comment_id = %d AND user_id = %d",
            $comment_id,
            $user_id
        )) ?: '';
    }

    /**
     * Check if user can comment
     *
     * @return bool
     * @since 1.0.0
     */
    public function can_user_comment() {
        // Allow guests if enabled
        if (!is_user_logged_in()) {
            return get_option('askro_general_settings')['allow_guest_comments'] ?? false;
        }

        return current_user_can('read');
    }

    /**
     * Check if user can edit comment
     *
     * @param int $comment_id Comment ID
     * @return bool
     * @since 1.0.0
     */
    public function can_edit_comment($comment_id) {
        if (!is_user_logged_in()) {
            return false;
        }

        $comment = $this->get_comment($comment_id);
        if (!$comment) {
            return false;
        }

        // Admin can edit any comment
        if (current_user_can('manage_options')) {
            return true;
        }

        // User can edit their own comment within time limit
        if ($comment['user_id'] == get_current_user_id()) {
            $time_limit = 15 * 60; // 15 minutes
            $comment_time = strtotime($comment['date']);
            return (current_time('timestamp') - $comment_time) < $time_limit;
        }

        return false;
    }

    /**
     * Check if user can delete comment
     *
     * @param int $comment_id Comment ID
     * @return bool
     * @since 1.0.0
     */
    public function can_delete_comment($comment_id) {
        if (!is_user_logged_in()) {
            return false;
        }

        $comment = $this->get_comment($comment_id);
        if (!$comment) {
            return false;
        }

        // Admin can delete any comment
        if (current_user_can('manage_options')) {
            return true;
        }

        // User can delete their own comment
        return $comment['user_id'] == get_current_user_id();
    }

    /**
     * Check if comment is spam
     *
     * @param string $content Comment content
     * @return bool
     * @since 1.0.0
     */
    public function is_spam_comment($content) {
        // Simple spam detection
        $spam_words = ['spam', 'viagra', 'casino', 'lottery'];
        $content_lower = strtolower($content);

        foreach ($spam_words as $word) {
            if (strpos($content_lower, $word) !== false) {
                return true;
            }
        }

        // Check for excessive links
        $link_count = substr_count($content_lower, 'http');
        if ($link_count > 2) {
            return true;
        }

        return false;
    }
}


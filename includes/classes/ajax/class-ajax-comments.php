<?php
/**
 * Comments AJAX Handler Class
 *
 * @package    Askro
 * @subpackage AJAX/Comments
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
 * Comments AJAX Handler Class
 *
 * Handles all comments-related AJAX requests
 *
 * @since 1.0.0
 */
class Askro_Ajax_Comments extends Askro_Abstract_Ajax_Handler {

    /**
     * Register AJAX actions
     *
     * @since 1.0.0
     */
    public function register_actions() {
        // Basic comment actions
        add_action('wp_ajax_askro_add_comment', [$this, 'handle_add_comment']);
        add_action('wp_ajax_askro_edit_comment', [$this, 'handle_edit_comment']);
        add_action('wp_ajax_askro_delete_comment', [$this, 'handle_delete_comment']);
        add_action('wp_ajax_askro_comment_reaction', [$this, 'handle_comment_reaction']);

        // Advanced comment actions
        add_action('wp_ajax_askro_load_comments', [$this, 'handle_load_comments']);
        add_action('wp_ajax_nopriv_askro_load_comments', [$this, 'handle_load_comments']);
        add_action('wp_ajax_askro_submit_comment', [$this, 'handle_submit_comment']);
        add_action('wp_ajax_askro_edit_comment', [$this, 'handle_edit_comment_advanced']);
        add_action('wp_ajax_askro_delete_comment', [$this, 'handle_delete_comment_advanced']);
        add_action('wp_ajax_askro_add_reaction', [$this, 'handle_add_reaction']);
    }

    /**
     * Handle adding a new comment
     *
     * @since 1.0.0
     */
    public function handle_add_comment() {
        if (!$this->verify_nonce('askro_add_comment')) {
            $this->send_error(__('Security check failed', 'askro'));
            return;
        }

        $post_id = intval($this->get_post_data('post_id'));
        $content = sanitize_textarea_field($this->get_post_data('content'));
        $parent_id = intval($this->get_post_data('parent_id', 0));

        if (!$post_id || !$content) {
            $this->send_error(__('Invalid comment data', 'askro'));
            return;
        }

        $comments = $this->get_component('comments');
        if (!$comments) {
            $this->send_error(__('Comments component not available', 'askro'));
            return;
        }

        $result = $comments->add_comment($post_id, $this->user_id, $content, $parent_id);
        
        if (is_wp_error($result)) {
            $this->send_error($result->get_error_message());
            return;
        }

        $this->log_action('add_comment', [
            'post_id' => $post_id,
            'parent_id' => $parent_id,
            'comment_id' => $result
        ]);

        $this->send_response([
            'success' => true,
            'message' => __('Comment added successfully', 'askro'),
            'comment_id' => $result,
            'html' => $this->render_comment_html($result)
        ]);
    }

    /**
     * Handle editing a comment
     *
     * @since 1.0.0
     */
    public function handle_edit_comment() {
        if (!$this->verify_nonce('askro_edit_comment')) {
            $this->send_error(__('Security check failed', 'askro'));
            return;
        }

        $comment_id = intval($this->get_post_data('comment_id'));
        $content = sanitize_textarea_field($this->get_post_data('content'));

        if (!$comment_id || !$content) {
            $this->send_error(__('Invalid comment data', 'askro'));
            return;
        }

        $comments = $this->get_component('comments');
        if (!$comments) {
            $this->send_error(__('Comments component not available', 'askro'));
            return;
        }

        $result = $comments->edit_comment($comment_id, $this->user_id, $content);
        
        if (is_wp_error($result)) {
            $this->send_error($result->get_error_message());
            return;
        }

        $this->log_action('edit_comment', [
            'comment_id' => $comment_id
        ]);

        $this->send_response([
            'success' => true,
            'message' => __('Comment updated successfully', 'askro'),
            'html' => $this->render_comment_html($comment_id)
        ]);
    }

    /**
     * Handle deleting a comment
     *
     * @since 1.0.0
     */
    public function handle_delete_comment() {
        if (!$this->verify_nonce('askro_delete_comment')) {
            $this->send_error(__('Security check failed', 'askro'));
            return;
        }

        $comment_id = intval($this->get_post_data('comment_id'));

        if (!$comment_id) {
            $this->send_error(__('Invalid comment ID', 'askro'));
            return;
        }

        $comments = $this->get_component('comments');
        if (!$comments) {
            $this->send_error(__('Comments component not available', 'askro'));
            return;
        }

        $result = $comments->delete_comment($comment_id, $this->user_id);
        
        if (is_wp_error($result)) {
            $this->send_error($result->get_error_message());
            return;
        }

        $this->log_action('delete_comment', [
            'comment_id' => $comment_id
        ]);

        $this->send_response([
            'success' => true,
            'message' => __('Comment deleted successfully', 'askro')
        ]);
    }

    /**
     * Handle comment reaction
     *
     * @since 1.0.0
     */
    public function handle_comment_reaction() {
        if (!$this->verify_nonce('askro_comment_reaction')) {
            $this->send_error(__('Security check failed', 'askro'));
            return;
        }

        $comment_id = intval($this->get_post_data('comment_id'));
        $reaction = sanitize_text_field($this->get_post_data('reaction'));

        if (!$comment_id || !$reaction) {
            $this->send_error(__('Invalid reaction data', 'askro'));
            return;
        }

        $comments = $this->get_component('comments');
        if (!$comments) {
            $this->send_error(__('Comments component not available', 'askro'));
            return;
        }

        $result = $comments->add_reaction($comment_id, $this->user_id, $reaction);
        
        if (is_wp_error($result)) {
            $this->send_error($result->get_error_message());
            return;
        }

        $this->log_action('comment_reaction', [
            'comment_id' => $comment_id,
            'reaction' => $reaction
        ]);

        $this->send_response([
            'success' => true,
            'message' => __('Reaction added successfully', 'askro'),
            'reaction_count' => $this->get_comment_reaction_count($comment_id, $reaction)
        ]);
    }

    /**
     * Handle loading comments
     *
     * @since 1.0.0
     */
    public function handle_load_comments() {
        $post_id = intval($this->get_post_data('post_id'));
        $page = intval($this->get_post_data('page', 1));
        $per_page = intval($this->get_post_data('per_page', 10));

        if (!$post_id) {
            $this->send_error(__('Invalid post ID', 'askro'));
            return;
        }

        $comments = $this->get_component('comments');
        if (!$comments) {
            $this->send_error(__('Comments component not available', 'askro'));
            return;
        }

        $result = $comments->get_comments($post_id, $page, $per_page);
        
        if (is_wp_error($result)) {
            $this->send_error($result->get_error_message());
            return;
        }

        $html = '';
        foreach ($result['comments'] as $comment) {
            $html .= $this->render_comment_html($comment);
        }

        $this->send_response([
            'success' => true,
            'comments' => $result['comments'],
            'html' => $html,
            'has_more' => $result['has_more'],
            'total_pages' => $result['total_pages']
        ]);
    }

    /**
     * Render comment HTML
     *
     * @param int|object $comment Comment ID or comment object
     * @return string
     * @since 1.0.0
     */
    private function render_comment_html($comment) {
        if (is_numeric($comment)) {
            $comment = get_comment($comment);
        }

        if (!$comment) {
            return '';
        }

        $user = get_userdata($comment->user_id);
        $user_name = $user ? $user->display_name : __('Unknown User', 'askro');
        $user_avatar = get_avatar($comment->user_id, 32);
        $comment_date = human_time_diff(strtotime($comment->comment_date), current_time('timestamp'));

        $reactions = $this->get_comment_reactions($comment->comment_ID);
        $reactions_html = $this->render_comment_reactions($comment->comment_ID, $reactions);

        $can_edit = $this->can_edit_comment($comment);
        $can_delete = $this->can_delete_comment($comment);

        ob_start();
        ?>
        <div class="askro-comment" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
            <div class="comment-header">
                <div class="comment-author">
                    <?php echo $user_avatar; ?>
                    <span class="author-name"><?php echo esc_html($user_name); ?></span>
                    <span class="comment-date"><?php echo esc_html($comment_date); ?></span>
                </div>
                <?php if ($can_edit || $can_delete): ?>
                <div class="comment-actions">
                    <?php if ($can_edit): ?>
                    <button class="edit-comment-btn" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                        <?php _e('Edit', 'askro'); ?>
                    </button>
                    <?php endif; ?>
                    <?php if ($can_delete): ?>
                    <button class="delete-comment-btn" data-comment-id="<?php echo esc_attr($comment->comment_ID); ?>">
                        <?php _e('Delete', 'askro'); ?>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="comment-content">
                <?php echo wp_kses_post($comment->comment_content); ?>
            </div>
            <div class="comment-reactions">
                <?php echo $reactions_html; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Get comment reactions
     *
     * @param int $comment_id Comment ID
     * @return array
     * @since 1.0.0
     */
    private function get_comment_reactions($comment_id) {
        $comments = $this->get_component('comments');
        if (!$comments) {
            return [];
        }

        return $comments->get_comment_reactions($comment_id);
    }

    /**
     * Render comment reactions
     *
     * @param int $comment_id Comment ID
     * @param array $reactions Reactions array
     * @return string
     * @since 1.0.0
     */
    private function render_comment_reactions($comment_id, $reactions) {
        $reaction_icons = [
            'like' => '👍',
            'love' => '❤️',
            'fire' => '🔥',
            'laugh' => '😂',
            'wow' => '😮'
        ];

        $html = '<div class="reactions-container">';
        foreach ($reaction_icons as $type => $icon) {
            $count = isset($reactions[$type]) ? $reactions[$type] : 0;
            $active_class = $count > 0 ? 'active' : '';
            $html .= sprintf(
                '<button class="reaction-btn %s" data-reaction="%s" data-comment-id="%d">
                    <span class="reaction-icon">%s</span>
                    <span class="reaction-count">%d</span>
                </button>',
                $active_class,
                esc_attr($type),
                $comment_id,
                $icon,
                $count
            );
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Check if user can edit comment
     *
     * @param object $comment Comment object
     * @return bool
     * @since 1.0.0
     */
    private function can_edit_comment($comment) {
        return $comment->user_id == $this->user_id || $this->user_can('moderate_comments');
    }

    /**
     * Check if user can delete comment
     *
     * @param object $comment Comment object
     * @return bool
     * @since 1.0.0
     */
    private function can_delete_comment($comment) {
        return $comment->user_id == $this->user_id || $this->user_can('moderate_comments');
    }

    /**
     * Get comment reaction count
     *
     * @param int $comment_id Comment ID
     * @param string $reaction Reaction type
     * @return int
     * @since 1.0.0
     */
    private function get_comment_reaction_count($comment_id, $reaction) {
        $comments = $this->get_component('comments');
        if (!$comments) {
            return 0;
        }

        $reactions = $comments->get_comment_reactions($comment_id);
        return isset($reactions[$reaction]) ? $reactions[$reaction] : 0;
    }
} 

<?php
/**
 * Forms Class
 *
 * @package    Askro
 * @subpackage Core/Forms
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
 * Askro Forms Class
 *
 * Handles form generation and processing
 *
 * @since 1.0.0
 */
class Askro_Forms {

    /**
     * Initialize the forms component
     *
     * @since 1.0.0
     */
    public function init() {
        add_action('wp_ajax_askro_submit_question', [$this, 'handle_question_submission']);
        add_action('wp_ajax_nopriv_askro_submit_question', [$this, 'handle_question_submission']);
        
        add_action('wp_ajax_askro_submit_answer', [$this, 'handle_answer_submission']);
        add_action('wp_ajax_nopriv_askro_submit_answer', [$this, 'handle_answer_submission']);
        
        add_action('wp_ajax_askro_upload_file', [$this, 'handle_file_upload']);
        add_action('wp_ajax_nopriv_askro_upload_file', [$this, 'handle_file_upload']);
        
        add_action('wp_ajax_askro_validate_field', [$this, 'handle_field_validation']);
        add_action('wp_ajax_nopriv_askro_validate_field', [$this, 'handle_field_validation']);
    }

    /**
     * Generate question submission form
     *
     * @param array $args Form arguments
     * @return string Form HTML
     * @since 1.0.0
     */
    public function generate_question_form($args = []) {
        $defaults = [
            'show_title' => true,
            'show_categories' => true,
            'show_tags' => true,
            'show_attachments' => true,
            'required_login' => false,
            'redirect_after' => '',
            'form_id' => 'askro-question-form',
            'submit_text' => __('نشر السؤال', 'askro')
        ];

        $args = wp_parse_args($args, $defaults);

        // Check if login is required
        if ($args['required_login'] && !is_user_logged_in()) {
            return $this->generate_login_required_message();
        }

        ob_start();
        ?>
        <div class="askro-form-container">
            <?php if ($args['show_title']): ?>
            <div class="askro-form-header">
                <h2 class="askro-heading-2"><?php _e('طرح سؤال جديد', 'askro'); ?></h2>
                <p class="askro-body-text"><?php _e('شارك سؤالك مع المجتمع واحصل على إجابات مفيدة', 'askro'); ?></p>
            </div>
            <?php endif; ?>

            <form id="<?php echo esc_attr($args['form_id']); ?>" class="askro-question-form" enctype="multipart/form-data">
                <?php wp_nonce_field('askro_submit_question', 'askro_question_nonce'); ?>
                
                <!-- Question Title -->
                <div class="askro-form-group">
                    <label for="question_title" class="askro-form-label required">
                        <?php _e('عنوان السؤال', 'askro'); ?>
                        <span class="askro-required">*</span>
                    </label>
                    <input type="text" 
                           id="question_title" 
                           name="question_title" 
                           class="askro-input" 
                           placeholder="<?php _e('اكتب عنوان سؤالك هنا...', 'askro'); ?>"
                           required
                           maxlength="200"
                           data-validate="title">
                    <div class="askro-field-feedback"></div>
                    <div class="askro-char-counter">
                        <span class="current">0</span>/<span class="max">200</span>
                    </div>
                </div>

                <!-- Question Content -->
                <div class="askro-form-group">
                    <label for="question_content" class="askro-form-label required">
                        <?php _e('تفاصيل السؤال', 'askro'); ?>
                        <span class="askro-required">*</span>
                    </label>
                    <div class="askro-editor-toolbar">
                        <button type="button" class="askro-editor-btn" data-action="bold" title="<?php _e('عريض', 'askro'); ?>">
                            <strong>B</strong>
                        </button>
                        <button type="button" class="askro-editor-btn" data-action="italic" title="<?php _e('مائل', 'askro'); ?>">
                            <em>I</em>
                        </button>
                        <button type="button" class="askro-editor-btn" data-action="link" title="<?php _e('رابط', 'askro'); ?>">
                            🔗
                        </button>
                        <button type="button" class="askro-editor-btn" data-action="code" title="<?php _e('كود', 'askro'); ?>">
                            &lt;/&gt;
                        </button>
                        <button type="button" class="askro-editor-btn" data-action="list" title="<?php _e('قائمة', 'askro'); ?>">
                            ≡
                        </button>
                    </div>
                    <textarea id="question_content" 
                              name="question_content" 
                              class="askro-textarea" 
                              rows="8"
                              placeholder="<?php _e('اشرح سؤالك بالتفصيل. كلما كان السؤال أوضح، كانت الإجابات أفضل...', 'askro'); ?>"
                              required
                              minlength="20"
                              data-validate="content"></textarea>
                    <div class="askro-field-feedback"></div>
                    <div class="askro-editor-preview" style="display: none;"></div>
                </div>

                <?php if ($args['show_categories']): ?>
                <!-- Categories -->
                <div class="askro-form-group">
                    <label for="question_categories" class="askro-form-label">
                        <?php _e('التصنيف', 'askro'); ?>
                    </label>
                    <select id="question_categories" 
                            name="question_categories[]" 
                            class="askro-select" 
                            multiple
                            data-placeholder="<?php _e('اختر التصنيفات المناسبة...', 'askro'); ?>">
                        <?php
                        $categories = get_terms([
                            'taxonomy' => 'askro_question_category',
                            'hide_empty' => false
                        ]);
                        
                        foreach ($categories as $category) {
                            echo '<option value="' . $category->term_id . '">' . esc_html($category->name) . '</option>';
                        }
                        ?>
                    </select>
                    <div class="askro-field-feedback"></div>
                </div>
                <?php endif; ?>

                <?php if ($args['show_tags']): ?>
                <!-- Tags -->
                <div class="askro-form-group">
                    <label for="question_tags" class="askro-form-label">
                        <?php _e('العلامات', 'askro'); ?>
                    </label>
                    <input type="text" 
                           id="question_tags" 
                           name="question_tags" 
                           class="askro-input askro-tags-input"
                           placeholder="<?php _e('أضف علامات لسؤالك...', 'askro'); ?>"
                           data-placeholder="<?php _e('اكتب علامة واضغط Enter', 'askro'); ?>">
                    <div class="askro-field-feedback"></div>
                    <p class="askro-field-help">
                        <?php _e('أضف حتى 5 علامات لوصف موضوع سؤالك', 'askro'); ?>
                    </p>
                </div>
                <?php endif; ?>

                <?php if ($args['show_attachments']): ?>
                <!-- File Attachments -->
                <div class="askro-form-group">
                    <label class="askro-form-label">
                        <?php _e('المرفقات', 'askro'); ?>
                    </label>
                    <div class="askro-file-upload-area" id="file-upload-area">
                        <div class="askro-file-upload-content">
                            <div class="askro-file-upload-icon">📎</div>
                            <p class="askro-file-upload-text">
                                <?php _e('اسحب الملفات هنا أو', 'askro'); ?>
                                <button type="button" class="askro-file-upload-btn">
                                    <?php _e('تصفح الملفات', 'askro'); ?>
                                </button>
                            </p>
                            <p class="askro-file-upload-help">
                                <?php _e('الحد الأقصى: 5 ملفات، 10MB لكل ملف', 'askro'); ?>
                            </p>
                        </div>
                        <input type="file" 
                               id="question_attachments" 
                               name="question_attachments[]" 
                               multiple 
                               accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt"
                               style="display: none;">
                    </div>
                    <div class="askro-uploaded-files"></div>
                    <div class="askro-field-feedback"></div>
                </div>
                <?php endif; ?>

                <!-- Anonymous Option -->
                <div class="askro-form-group">
                    <label class="askro-checkbox-label">
                        <input type="checkbox" name="question_anonymous" value="1" class="askro-checkbox">
                        <span class="askro-checkbox-text">
                            <?php _e('نشر السؤال بشكل مجهول', 'askro'); ?>
                        </span>
                    </label>
                    <p class="askro-field-help">
                        <?php _e('لن يظهر اسمك مع السؤال، ولكن ستحصل على النقاط', 'askro'); ?>
                    </p>
                </div>

                <!-- Submit Button -->
                <div class="askro-form-actions">
                    <button type="button" class="askro-btn-outline askro-preview-btn">
                        <?php _e('معاينة', 'askro'); ?>
                    </button>
                    <button type="submit" class="askro-btn-primary askro-submit-btn">
                        <span class="askro-btn-text"><?php echo esc_html($args['submit_text']); ?></span>
                        <span class="askro-btn-loading" style="display: none;">
                            <div class="askro-spinner"></div>
                            <?php _e('جاري النشر...', 'askro'); ?>
                        </span>
                    </button>
                </div>

                <!-- Hidden Fields -->
                <input type="hidden" name="redirect_after" value="<?php echo esc_attr($args['redirect_after']); ?>">
            </form>
        </div>

        <!-- Preview Modal -->
        <div id="askro-preview-modal" class="askro-modal" style="display: none;">
            <div class="askro-modal-content">
                <div class="askro-modal-header">
                    <h3><?php _e('معاينة السؤال', 'askro'); ?></h3>
                    <button type="button" class="askro-modal-close">&times;</button>
                </div>
                <div class="askro-modal-body">
                    <div id="askro-preview-content"></div>
                </div>
                <div class="askro-modal-footer">
                    <button type="button" class="askro-btn-outline askro-modal-close">
                        <?php _e('إغلاق', 'askro'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Generate answer submission form
     *
     * @param int $question_id Question ID
     * @param array $args Form arguments
     * @return string Form HTML
     * @since 1.0.0
     */
    public function generate_answer_form($question_id, $args = []) {
        $defaults = [
            'show_title' => true,
            'show_attachments' => true,
            'required_login' => false,
            'form_id' => 'askro-answer-form',
            'submit_text' => __('نشر الإجابة', 'askro')
        ];

        $args = wp_parse_args($args, $defaults);

        // Check if login is required
        if ($args['required_login'] && !is_user_logged_in()) {
            return $this->generate_login_required_message();
        }

        // Check if question exists and is not closed
        $question = get_post($question_id);
        if (!$question || get_post_meta($question_id, '_askro_is_closed', true)) {
            return '<div class="askro-alert askro-alert-warning">' . 
                   __('هذا السؤال مغلق ولا يمكن إضافة إجابات جديدة.', 'askro') . 
                   '</div>';
        }

        ob_start();
        ?>
        <div class="askro-form-container">
            <?php if ($args['show_title']): ?>
            <div class="askro-form-header">
                <h3 class="askro-heading-3"><?php _e('إجابتك', 'askro'); ?></h3>
                <p class="askro-body-text"><?php _e('شارك معرفتك وساعد في حل هذا السؤال', 'askro'); ?></p>
            </div>
            <?php endif; ?>

            <form id="<?php echo esc_attr($args['form_id']); ?>" class="askro-answer-form" enctype="multipart/form-data">
                <?php wp_nonce_field('askro_submit_answer', 'askro_answer_nonce'); ?>
                <input type="hidden" name="question_id" value="<?php echo intval($question_id); ?>">
                
                <!-- Answer Content -->
                <div class="askro-form-group">
                    <label for="answer_content" class="askro-form-label required">
                        <?php _e('إجابتك', 'askro'); ?>
                        <span class="askro-required">*</span>
                    </label>
                    <div class="askro-editor-toolbar">
                        <button type="button" class="askro-editor-btn" data-action="bold" title="<?php _e('عريض', 'askro'); ?>">
                            <strong>B</strong>
                        </button>
                        <button type="button" class="askro-editor-btn" data-action="italic" title="<?php _e('مائل', 'askro'); ?>">
                            <em>I</em>
                        </button>
                        <button type="button" class="askro-editor-btn" data-action="link" title="<?php _e('رابط', 'askro'); ?>">
                            🔗
                        </button>
                        <button type="button" class="askro-editor-btn" data-action="code" title="<?php _e('كود', 'askro'); ?>">
                            &lt;/&gt;
                        </button>
                        <button type="button" class="askro-editor-btn" data-action="list" title="<?php _e('قائمة', 'askro'); ?>">
                            ≡
                        </button>
                        <button type="button" class="askro-editor-btn" data-action="quote" title="<?php _e('اقتباس', 'askro'); ?>">
                            "
                        </button>
                    </div>
                    <textarea id="answer_content" 
                              name="answer_content" 
                              class="askro-textarea" 
                              rows="6"
                              placeholder="<?php _e('اكتب إجابتك هنا. قدم إجابة مفصلة ومفيدة...', 'askro'); ?>"
                              required
                              minlength="10"
                              data-validate="content"></textarea>
                    <div class="askro-field-feedback"></div>
                    <div class="askro-editor-preview" style="display: none;"></div>
                </div>

                <?php if ($args['show_attachments']): ?>
                <!-- File Attachments -->
                <div class="askro-form-group">
                    <label class="askro-form-label">
                        <?php _e('المرفقات', 'askro'); ?>
                    </label>
                    <div class="askro-file-upload-area" id="answer-file-upload-area">
                        <div class="askro-file-upload-content">
                            <div class="askro-file-upload-icon">📎</div>
                            <p class="askro-file-upload-text">
                                <?php _e('اسحب الملفات هنا أو', 'askro'); ?>
                                <button type="button" class="askro-file-upload-btn">
                                    <?php _e('تصفح الملفات', 'askro'); ?>
                                </button>
                            </p>
                            <p class="askro-file-upload-help">
                                <?php _e('الحد الأقصى: 3 ملفات، 5MB لكل ملف', 'askro'); ?>
                            </p>
                        </div>
                        <input type="file" 
                               id="answer_attachments" 
                               name="answer_attachments[]" 
                               multiple 
                               accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt"
                               style="display: none;">
                    </div>
                    <div class="askro-uploaded-files"></div>
                    <div class="askro-field-feedback"></div>
                </div>
                <?php endif; ?>

                <!-- Anonymous Option -->
                <div class="askro-form-group">
                    <label class="askro-checkbox-label">
                        <input type="checkbox" name="answer_anonymous" value="1" class="askro-checkbox">
                        <span class="askro-checkbox-text">
                            <?php _e('نشر الإجابة بشكل مجهول', 'askro'); ?>
                        </span>
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="askro-form-actions">
                    <button type="button" class="askro-btn-outline askro-preview-btn">
                        <?php _e('معاينة', 'askro'); ?>
                    </button>
                    <button type="submit" class="askro-btn-primary askro-submit-btn">
                        <span class="askro-btn-text"><?php echo esc_html($args['submit_text']); ?></span>
                        <span class="askro-btn-loading" style="display: none;">
                            <div class="askro-spinner"></div>
                            <?php _e('جاري النشر...', 'askro'); ?>
                        </span>
                    </button>
                </div>
            </form>
        </div>
        <?php

        return ob_get_clean();
    }

    /**
     * Generate login required message
     *
     * @return string Message HTML
     * @since 1.0.0
     */
    private function generate_login_required_message() {
        ob_start();
        ?>
        <div class="askro-login-required">
            <div class="askro-card">
                <div class="text-center">
                    <div class="askro-login-icon">🔐</div>
                    <h3 class="askro-heading-3"><?php _e('تسجيل الدخول مطلوب', 'askro'); ?></h3>
                    <p class="askro-body-text">
                        <?php _e('يجب تسجيل الدخول أولاً لتتمكن من طرح الأسئلة أو تقديم الإجابات.', 'askro'); ?>
                    </p>
                    <div class="askro-login-actions">
                        <a href="<?php echo wp_login_url(get_permalink()); ?>" class="askro-btn-primary">
                            <?php _e('تسجيل الدخول', 'askro'); ?>
                        </a>
                        <a href="<?php echo wp_registration_url(); ?>" class="askro-btn-outline">
                            <?php _e('إنشاء حساب جديد', 'askro'); ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle question submission via AJAX
     *
     * @since 1.0.0
     */
    public function handle_question_submission() {
        // Verify nonce
        global $askro_response_handler, $askro_security_helper;
        
        if (!$askro_security_helper->verify_nonce('askro_question_nonce', 'askro_submit_question')) {
            return; // Error already handled by security helper
        }

        // Check if user can submit questions
        if (!$askro_security_helper->verify_capability('askro_ask_question')) {
            return; // Error already handled by security helper
        }

        // Validate and sanitize input using security helper
        $validation_rules = [
            'question_title' => [
                'type' => 'text',
                'required' => true,
                'min_length' => 10,
                'max_length' => 200
            ],
            'question_content' => [
                'type' => 'textarea',
                'required' => true,
                'min_length' => 20,
                'max_length' => 5000
            ],
            'question_category' => [
                'type' => 'int',
                'required' => false
            ]
        ];
        
        $question_data = $askro_security_helper->sanitize_input($_POST, $validation_rules);
        if (is_wp_error($question_data)) {
            $askro_response_handler->send_validation_error($question_data);
        }

        // Check for duplicate questions
        if ($this->is_duplicate_question($question_data['title'], $question_data['content'])) {
            $askro_response_handler->send_duplicate_error('question');
        }

        // Create the question post
        $question_id = $this->create_question_post($question_data);
        if (is_wp_error($question_id)) {
            $askro_response_handler->send_validation_error($question_id);
        }

        // Handle file uploads
        if (!empty($_FILES['question_attachments']['name'][0])) {
            $upload_result = $this->handle_question_attachments($question_id, $_FILES['question_attachments']);
            if (is_wp_error($upload_result)) {
                // Debug logging removed for production
            }
        }

        // Award points for question submission
        if (is_user_logged_in()) {
            askro_award_points(get_current_user_id(), 5, 'question_submitted');
        }

        // Track analytics
        askro_track_event('question_submitted', [
            'question_id' => $question_id,
            'user_id' => get_current_user_id(),
            'anonymous' => $question_data['anonymous']
        ]);

        // Send success response
        $redirect_url = !empty($_POST['redirect_after']) ? 
                       esc_url_raw($_POST['redirect_after']) : 
                       get_permalink($question_id);

        wp_send_json_success([
            'message' => __('تم نشر سؤالك بنجاح!', 'askro'),
            'question_id' => $question_id,
            'redirect_url' => $redirect_url
        ]);
    }

    /**
     * Handle answer submission via AJAX
     *
     * @since 1.0.0
     */
    public function handle_answer_submission() {
        // Verify nonce
        global $askro_response_handler, $askro_security_helper;
        
        if (!$askro_security_helper->verify_nonce('askro_answer_nonce', 'askro_submit_answer')) {
            return; // Error already handled by security helper
        }

        // Check if user can submit answers
        if (!$askro_security_helper->verify_capability('askro_submit_answer')) {
            return; // Error already handled by security helper
        }

        // Validate question ID
        $question_id = intval($_POST['question_id'] ?? 0);
        if (!$question_id || !get_post($question_id)) {
            $askro_response_handler->send_not_found_error('question');
        }

        // Check if question is closed
        if (get_post_meta($question_id, '_askro_is_closed', true)) {
            $askro_response_handler->send_error('question_closed');
        }

        // Validate and sanitize input
        $answer_data = $this->validate_answer_data($_POST);
        if (is_wp_error($answer_data)) {
            $askro_response_handler->send_validation_error($answer_data);
        }

        // Check for duplicate answers
        if ($this->is_duplicate_answer($question_id, $answer_data['content'])) {
            $askro_response_handler->send_duplicate_error('answer');
        }

        // Create the answer post
        $answer_id = $this->create_answer_post($answer_data, $question_id);
        if (is_wp_error($answer_id)) {
            $askro_response_handler->send_validation_error($answer_id);
        }

        // Handle file uploads
        if (!empty($_FILES['answer_attachments']['name'][0])) {
            $upload_result = $this->handle_answer_attachments($answer_id, $_FILES['answer_attachments']);
            if (is_wp_error($upload_result)) {
                // Debug logging removed for production
            }
        }

        // Award points for answer submission
        if (is_user_logged_in()) {
            askro_award_points(get_current_user_id(), 10, 'answer_submitted');
        }

        // Track analytics
        askro_track_event('answer_submitted', [
            'answer_id' => $answer_id,
            'question_id' => $question_id,
            'user_id' => get_current_user_id(),
            'anonymous' => $answer_data['anonymous']
        ]);

        // Send success response
        wp_send_json_success([
            'message' => __('تم نشر إجابتك بنجاح!', 'askro'),
            'answer_id' => $answer_id,
            'question_id' => $question_id
        ]);
    }

    /**
     * Handle file upload via AJAX
     *
     * @since 1.0.0
     */
    public function handle_file_upload() {
        // Verify nonce
        global $askro_response_handler;
        
        if (!wp_verify_nonce($_POST['nonce'] ?? '', 'askro_file_upload')) {
            $askro_response_handler->send_security_error('file_upload');
        }

        // Check if user can upload files
        if (!is_user_logged_in()) {
            $askro_response_handler->send_login_required_error();
        }

        // Validate file
        if (empty($_FILES['file'])) {
            $askro_response_handler->send_error('missing_data');
        }

        $file = $_FILES['file'];
        $validation = $this->validate_uploaded_file($file);
        if (is_wp_error($validation)) {
            $askro_response_handler->send_validation_error($validation);
        }

        // Upload file
        $upload_result = $this->upload_file($file);
        if (is_wp_error($upload_result)) {
            $askro_response_handler->send_file_upload_error($upload_result->get_error_message());
        }

        wp_send_json_success([
            'file_id' => $upload_result['attachment_id'],
            'file_url' => $upload_result['url'],
            'file_name' => $upload_result['filename'],
            'file_size' => size_format($upload_result['filesize'])
        ]);
    }

    /**
     * Handle field validation via AJAX
     *
     * @since 1.0.0
     */
    public function handle_field_validation() {
        $field = sanitize_text_field($_POST['field'] ?? '');
        $value = sanitize_text_field($_POST['value'] ?? '');

        $validation_result = $this->validate_field($field, $value);

        if (is_wp_error($validation_result)) {
            wp_send_json_error(['message' => $validation_result->get_error_message()]);
        }

        wp_send_json_success(['message' => __('صحيح', 'askro')]);
    }

    /**
     * Check if user can submit questions
     *
     * @return bool
     * @since 1.0.0
     */
    private function can_user_submit_question() {
        // Allow guests if enabled in settings
        if (!is_user_logged_in()) {
            return get_option('askro_general_settings')['allow_guest_questions'] ?? false;
        }

        // Check user capabilities
        if (!current_user_can('read')) {
            return false;
        }

        // Check minimum points requirement
        $min_points = get_option('askro_general_settings')['min_points_question'] ?? 0;
        if ($min_points > 0) {
            $user_points = askro_get_user_points(get_current_user_id());
            if ($user_points < $min_points) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user can submit answers
     *
     * @return bool
     * @since 1.0.0
     */
    private function can_user_submit_answer() {
        // Allow guests if enabled in settings
        if (!is_user_logged_in()) {
            return get_option('askro_general_settings')['allow_guest_answers'] ?? false;
        }

        // Check user capabilities
        if (!current_user_can('read')) {
            return false;
        }

        // Check minimum points requirement
        $min_points = get_option('askro_general_settings')['min_points_answer'] ?? 0;
        if ($min_points > 0) {
            $user_points = askro_get_user_points(get_current_user_id());
            if ($user_points < $min_points) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate question data
     *
     * @param array $data Raw POST data
     * @return array|WP_Error Validated data or error
     * @since 1.0.0
     */
    private function validate_question_data($data) {
        $validated = [];

        // Title validation
        $title = sanitize_text_field($data['question_title'] ?? '');
        if (empty($title)) {
            return new WP_Error('missing_title', __('عنوان السؤال مطلوب.', 'askro'));
        }
        if (strlen($title) < 10) {
            return new WP_Error('title_too_short', __('عنوان السؤال قصير جداً (10 أحرف على الأقل).', 'askro'));
        }
        if (strlen($title) > 200) {
            return new WP_Error('title_too_long', __('عنوان السؤال طويل جداً (200 حرف كحد أقصى).', 'askro'));
        }
        $validated['title'] = $title;

        // Content validation
        $content = wp_kses_post($data['question_content'] ?? '');
        if (empty($content)) {
            return new WP_Error('missing_content', __('محتوى السؤال مطلوب.', 'askro'));
        }
        if (strlen(strip_tags($content)) < 20) {
            return new WP_Error('content_too_short', __('محتوى السؤال قصير جداً (20 حرف على الأقل).', 'askro'));
        }
        $validated['content'] = $content;

        // Categories
        $categories = array_map('intval', $data['question_categories'] ?? []);
        $validated['categories'] = array_filter($categories);

        // Tags
        $tags = sanitize_text_field($data['question_tags'] ?? '');
        $validated['tags'] = $tags;

        // Anonymous
        $validated['anonymous'] = !empty($data['question_anonymous']);

        return $validated;
    }

    /**
     * Validate answer data
     *
     * @param array $data Raw POST data
     * @return array|WP_Error Validated data or error
     * @since 1.0.0
     */
    private function validate_answer_data($data) {
        $validated = [];

        // Content validation
        $content = wp_kses_post($data['answer_content'] ?? '');
        if (empty($content)) {
            return new WP_Error('missing_content', __('محتوى الإجابة مطلوب.', 'askro'));
        }
        if (strlen(strip_tags($content)) < 10) {
            return new WP_Error('content_too_short', __('محتوى الإجابة قصير جداً (10 أحرف على الأقل).', 'askro'));
        }
        $validated['content'] = $content;

        // Anonymous
        $validated['anonymous'] = !empty($data['answer_anonymous']);

        return $validated;
    }

    /**
     * Check if question is duplicate
     *
     * @param string $title Question title
     * @param string $content Question content
     * @return bool
     * @since 1.0.0
     */
    private function is_duplicate_question($title, $content) {
        global $wpdb;

        // Check for similar titles
        $similar_posts = $wpdb->get_results($wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} 
             WHERE post_type = 'askro_question' 
             AND post_status = 'publish' 
             AND post_title LIKE %s 
             LIMIT 1",
            '%' . $wpdb->esc_like($title) . '%'
        ));

        if (!empty($similar_posts)) {
            // Check content similarity
            foreach ($similar_posts as $post) {
                $existing_content = get_post_field('post_content', $post->ID);
                $similarity = $this->calculate_text_similarity($content, $existing_content);
                if ($similarity > 0.8) { // 80% similarity threshold
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if answer is duplicate
     *
     * @param int $question_id Question ID
     * @param string $content Answer content
     * @return bool
     * @since 1.0.0
     */
    private function is_duplicate_answer($question_id, $content) {
        if (!is_user_logged_in()) {
            return false;
        }

        $user_id = get_current_user_id();
        
        // Get user's existing answers for this question
        $existing_answers = get_posts([
            'post_type' => 'askro_answer',
            'author' => $user_id,
            'meta_query' => [
                [
                    'key' => '_askro_question_id',
                    'value' => $question_id,
                    'compare' => '='
                ]
            ],
            'posts_per_page' => -1
        ]);

        foreach ($existing_answers as $answer) {
            $similarity = $this->calculate_text_similarity($content, $answer->post_content);
            if ($similarity > 0.7) { // 70% similarity threshold
                return true;
            }
        }

        return false;
    }

    /**
     * Calculate text similarity
     *
     * @param string $text1 First text
     * @param string $text2 Second text
     * @return float Similarity score (0-1)
     * @since 1.0.0
     */
    private function calculate_text_similarity($text1, $text2) {
        $text1 = strtolower(strip_tags($text1));
        $text2 = strtolower(strip_tags($text2));
        
        similar_text($text1, $text2, $percent);
        return $percent / 100;
    }

    /**
     * Create question post
     *
     * @param array $data Validated question data
     * @return int|WP_Error Question ID or error
     * @since 1.0.0
     */
    private function create_question_post($data) {
        $post_data = [
            'post_type' => 'askro_question',
            'post_title' => $data['title'],
            'post_content' => $data['content'],
            'post_status' => 'publish',
            'post_author' => is_user_logged_in() ? get_current_user_id() : 0
        ];

        // Set to pending if moderation is enabled
        if (get_option('askro_general_settings')['moderate_questions'] ?? false) {
            $post_data['post_status'] = 'pending';
        }

        $question_id = wp_insert_post($post_data);
        
        if (is_wp_error($question_id)) {
            return $question_id;
        }

        // Set categories
        if (!empty($data['categories'])) {
            wp_set_post_terms($question_id, $data['categories'], 'askro_question_category');
        }

        // Set tags
        if (!empty($data['tags'])) {
            wp_set_post_terms($question_id, explode(',', $data['tags']), 'askro_question_tag');
        }

        // Set meta data
        update_post_meta($question_id, '_askro_is_anonymous', $data['anonymous']);
        update_post_meta($question_id, '_askro_views', 0);
        update_post_meta($question_id, '_askro_is_featured', 0);
        update_post_meta($question_id, '_askro_is_closed', 0);

        return $question_id;
    }

    /**
     * Create answer post
     *
     * @param array $data Validated answer data
     * @param int $question_id Question ID
     * @return int|WP_Error Answer ID or error
     * @since 1.0.0
     */
    private function create_answer_post($data, $question_id) {
        $post_data = [
            'post_type' => 'askro_answer',
            'post_content' => $data['content'],
            'post_status' => 'publish',
            'post_author' => is_user_logged_in() ? get_current_user_id() : 0,
            'post_parent' => $question_id
        ];

        // Set to pending if moderation is enabled
        if (get_option('askro_general_settings')['moderate_answers'] ?? false) {
            $post_data['post_status'] = 'pending';
        }

        $answer_id = wp_insert_post($post_data);
        
        if (is_wp_error($answer_id)) {
            return $answer_id;
        }

        // Set meta data
        update_post_meta($answer_id, '_askro_question_id', $question_id);
        update_post_meta($answer_id, '_askro_is_anonymous', $data['anonymous']);
        update_post_meta($answer_id, '_askro_is_accepted', 0);

        return $answer_id;
    }

    /**
     * Validate uploaded file
     *
     * @param array $file File data from $_FILES
     * @return bool|WP_Error True if valid, error otherwise
     * @since 1.0.0
     */
    private function validate_uploaded_file($file) {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return new WP_Error('upload_error', __('خطأ في رفع الملف.', 'askro'));
        }

        // Check file size (10MB max)
        $max_size = 10 * 1024 * 1024; // 10MB
        if ($file['size'] > $max_size) {
            return new WP_Error('file_too_large', __('حجم الملف كبير جداً (10MB كحد أقصى).', 'askro'));
        }

        // Check file type
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            return new WP_Error('invalid_file_type', __('نوع الملف غير مدعوم.', 'askro'));
        }

        return true;
    }

    /**
     * Upload file and create attachment
     *
     * @param array $file File data from $_FILES
     * @return array|WP_Error Upload result or error
     * @since 1.0.0
     */
    private function upload_file($file) {
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $upload = wp_handle_upload($file, ['test_form' => false]);
        
        if (isset($upload['error'])) {
            return new WP_Error('upload_failed', $upload['error']);
        }

        // Create attachment
        $attachment_data = [
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name($file['name']),
            'post_content' => '',
            'post_status' => 'inherit'
        ];

        $attachment_id = wp_insert_attachment($attachment_data, $upload['file']);
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }

        // Generate metadata
        $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        wp_update_attachment_metadata($attachment_id, $metadata);

        return [
            'attachment_id' => $attachment_id,
            'url' => $upload['url'],
            'filename' => basename($upload['file']),
            'filesize' => filesize($upload['file'])
        ];
    }

    /**
     * Handle question attachments
     *
     * @param int $question_id Question ID
     * @param array $files Files from $_FILES
     * @return bool|WP_Error True on success, error on failure
     * @since 1.0.0
     */
    private function handle_question_attachments($question_id, $files) {
        $attachment_ids = [];
        
        for ($i = 0; $i < count($files['name']); $i++) {
            if (empty($files['name'][$i])) {
                continue;
            }

            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];

            $validation = $this->validate_uploaded_file($file);
            if (is_wp_error($validation)) {
                continue; // Skip invalid files
            }

            $upload_result = $this->upload_file($file);
            if (!is_wp_error($upload_result)) {
                $attachment_ids[] = $upload_result['attachment_id'];
                wp_update_post([
                    'ID' => $upload_result['attachment_id'],
                    'post_parent' => $question_id
                ]);
            }
        }

        if (!empty($attachment_ids)) {
            update_post_meta($question_id, '_askro_attachments', $attachment_ids);
        }

        return true;
    }

    /**
     * Handle answer attachments
     *
     * @param int $answer_id Answer ID
     * @param array $files Files from $_FILES
     * @return bool|WP_Error True on success, error on failure
     * @since 1.0.0
     */
    private function handle_answer_attachments($answer_id, $files) {
        $attachment_ids = [];
        
        for ($i = 0; $i < count($files['name']); $i++) {
            if (empty($files['name'][$i])) {
                continue;
            }

            $file = [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i]
            ];

            $validation = $this->validate_uploaded_file($file);
            if (is_wp_error($validation)) {
                continue; // Skip invalid files
            }

            $upload_result = $this->upload_file($file);
            if (!is_wp_error($upload_result)) {
                $attachment_ids[] = $upload_result['attachment_id'];
                wp_update_post([
                    'ID' => $upload_result['attachment_id'],
                    'post_parent' => $answer_id
                ]);
            }
        }

        if (!empty($attachment_ids)) {
            update_post_meta($answer_id, '_askro_attachments', $attachment_ids);
        }

        return true;
    }

    /**
     * Validate individual field
     *
     * @param string $field Field name
     * @param string $value Field value
     * @return bool|WP_Error True if valid, error otherwise
     * @since 1.0.0
     */
    private function validate_field($field, $value) {
        switch ($field) {
            case 'title':
                if (strlen($value) < 10) {
                    return new WP_Error('title_too_short', __('العنوان قصير جداً', 'askro'));
                }
                if (strlen($value) > 200) {
                    return new WP_Error('title_too_long', __('العنوان طويل جداً', 'askro'));
                }
                break;

            case 'content':
                if (strlen(strip_tags($value)) < 20) {
                    return new WP_Error('content_too_short', __('المحتوى قصير جداً', 'askro'));
                }
                break;
        }

        return true;
    }
}


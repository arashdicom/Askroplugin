<?php
/**
 * Ask Question Form Template
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

// Get current user data
$current_user = askro_get_user_data();
$min_role = askro_get_option('min_role_ask_question', 'subscriber');

// Check if user is logged in and has the required role
$user_can_ask = false;
if (is_user_logged_in()) {
    $user = wp_get_current_user();
    $user_roles = $user->roles;
    
    // Define role hierarchy
    $role_hierarchy = [
        'subscriber' => 1,
        'contributor' => 2,
        'author' => 3,
        'editor' => 4,
        'administrator' => 5
    ];
    
    $min_role_level = isset($role_hierarchy[$min_role]) ? $role_hierarchy[$min_role] : 1;
    
    foreach ($user_roles as $role) {
        $user_role_level = isset($role_hierarchy[$role]) ? $role_hierarchy[$role] : 0;
        if ($user_role_level >= $min_role_level) {
            $user_can_ask = true;
            break;
        }
    }
}

// Get form settings from admin
$enable_pre_question_assistant = askro_get_option('enable_pre_question_assistant', true);
$enable_image_upload = askro_get_option('enable_image_upload', true);
$enable_code_editor = askro_get_option('enable_code_editor', true);
$max_attachments = askro_get_option('max_attachments', 5);
$max_file_size = askro_get_option('max_file_size', 5); // MB

// Get categories and tags
$categories = get_terms([
    'taxonomy' => 'askro_question_category',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
]);

$tags = get_terms([
    'taxonomy' => 'askro_question_tag',
    'hide_empty' => false,
    'orderby' => 'name',
    'order' => 'ASC'
]);

// Get question statuses
$question_statuses = [
    'open' => __('مفتوح', 'askro'),
    'urgent' => __('عاجل', 'askro'),
    'closed' => __('مغلق', 'askro')
];
?>

<div class="askme-container askme-ask-question-form">
    <div class="askme-main-content">
        
        <?php if (!$user_can_ask): ?>
            <!-- Login Required Message -->
            <div class="askme-alert askme-alert-warning">
                <div class="askme-alert-content">
                    <h3><?php _e('تسجيل الدخول مطلوب', 'askro'); ?></h3>
                    <p><?php _e('يجب عليك تسجيل الدخول لطرح سؤال جديد.', 'askro'); ?></p>
                    <a href="<?php echo wp_login_url(get_permalink()); ?>" class="askme-btn askme-btn-primary">
                        <?php _e('تسجيل الدخول', 'askro'); ?>
                    </a>
                </div>
            </div>
        <?php else: ?>
            
            <!-- Form Header -->
            <div class="askme-form-header">
                <h1 class="askme-form-title"><?php _e('اطرح سؤالاً جديداً', 'askro'); ?></h1>
                <p class="askme-form-subtitle"><?php _e('شارك معرفتك مع المجتمع واحصل على إجابات من الخبراء', 'askro'); ?></p>
            </div>

            <!-- Question Form -->
            <form id="askme-question-form" class="askme-form" method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('askro_submit_question', 'askro_question_nonce'); ?>
                
                <!-- Title Field -->
                <div class="askme-form-group">
                    <label for="question_title" class="askme-form-label">
                        <?php _e('عنوان السؤال *', 'askro'); ?>
                    </label>
                    <input 
                        type="text" 
                        id="question_title" 
                        name="question_title" 
                        class="askme-form-input" 
                        required 
                        maxlength="200"
                        placeholder="<?php _e('اكتب عنواناً واضحاً ومختصراً لسؤالك...', 'askro'); ?>"
                    >
                    <div class="askme-form-help">
                        <?php _e('يجب أن يكون العنوان واضحاً ومختصراً (أقل من 200 حرف)', 'askro'); ?>
                    </div>
                    
                    <?php if ($enable_pre_question_assistant): ?>
                        <!-- Pre-question Assistant -->
                        <div id="askme-pre-question-assistant" class="askme-pre-question-assistant" style="display: none;">
                            <div class="askme-assistant-header">
                                <h4><?php _e('أسئلة مشابهة', 'askro'); ?></h4>
                                <span class="askme-assistant-close">&times;</span>
                            </div>
                            <div id="askme-similar-questions" class="askme-similar-questions"></div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Content Field -->
                <div class="askme-form-group">
                    <label for="question_content" class="askme-form-label">
                        <?php _e('تفاصيل السؤال *', 'askro'); ?>
                    </label>
                    <div class="askme-editor-container">
                        <?php
                        $editor_settings = [
                            'textarea_name' => 'question_content',
                            'textarea_rows' => 10,
                            'media_buttons' => false,
                            'tinymce' => [
                                'toolbar1' => 'bold,italic,underline,strikethrough,|,bullist,numlist,|,link,unlink,|,code,|,undo,redo',
                                'toolbar2' => '',
                                'content_css' => plugin_dir_url(ASKRO_PLUGIN_FILE) . 'assets/css/src/main.css'
                            ],
                            'quicktags' => [
                                'buttons' => 'strong,em,link,ul,ol,li,code,close'
                            ]
                        ];
                        
                        if ($enable_code_editor) {
                            $editor_settings['tinymce']['toolbar1'] .= ',|,askro_code_block,askro_spoiler,askro_notice';
                        }
                        
                        wp_editor('', 'question_content', $editor_settings);
                        ?>
                    </div>
                    <div class="askme-form-help">
                        <?php _e('اشرح مشكلتك بالتفصيل. كلما كانت التفاصيل أكثر وضوحاً، كلما كانت الإجابة أفضل.', 'askro'); ?>
                    </div>
                </div>

                <!-- Category Selection -->
                <div class="askme-form-group">
                    <label for="question_category" class="askme-form-label">
                        <?php _e('التصنيف *', 'askro'); ?>
                    </label>
                    <?php if (!empty($categories)): ?>
                        <select id="question_category" name="question_category" class="askme-form-select" required>
                            <option value=""><?php _e('اختر تصنيفاً...', 'askro'); ?></option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo esc_attr($category->term_id); ?>">
                                    <?php echo esc_html($category->name); ?>
                                    <?php if ($category->count > 0): ?>
                                        (<?php echo $category->count; ?>)
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="askme-form-help">
                            <?php _e('اختر التصنيف المناسب لسؤالك لمساعدة الآخرين في العثور عليه', 'askro'); ?>
                        </div>
                    <?php else: ?>
                        <div class="askme-alert askme-alert-warning">
                            <p><?php _e('لا توجد تصنيفات متاحة حالياً. سيتم إنشاء التصنيفات الافتراضية تلقائياً عند تفعيل البلجن.', 'askro'); ?></p>
                            <p><?php _e('يمكنك إضافة تصنيفات جديدة من لوحة التحكم في قسم إعدادات Askro.', 'askro'); ?></p>
                            <?php if (current_user_can('manage_options')): ?>
                                <p><a href="<?php echo admin_url('admin.php?page=askro-settings#categories-management'); ?>" target="_blank" class="askme-btn askme-btn-primary">
                                    <?php _e('إدارة التصنيفات', 'askro'); ?>
                                </a></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tags Selection -->
                <div class="askme-form-group">
                    <label for="question_tags" class="askme-form-label">
                        <?php _e('العلامات', 'askro'); ?>
                    </label>
                    <input 
                        type="text" 
                        id="question_tags" 
                        name="question_tags" 
                        class="askme-form-input askme-tags-input" 
                        placeholder="<?php _e('اكتب العلامات مفصولة بفواصل...', 'askro'); ?>"
                    >
                    <div class="askme-form-help">
                        <?php _e('أضف علامات لمساعدة الآخرين في العثور على سؤالك', 'askro'); ?>
                    </div>
                </div>

                <!-- Question Status -->
                <div class="askme-form-group">
                    <label for="question_status" class="askme-form-label">
                        <?php _e('حالة السؤال', 'askro'); ?>
                    </label>
                    <select id="question_status" name="question_status" class="askme-form-select">
                        <?php foreach ($question_statuses as $status => $label): ?>
                            <option value="<?php echo esc_attr($status); ?>">
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if ($enable_image_upload): ?>
                    <!-- File Upload -->
                    <div class="askme-form-group">
                        <label class="askme-form-label">
                            <?php _e('المرفقات', 'askro'); ?>
                        </label>
                        <div class="askme-file-upload-container">
                            <div class="askme-file-upload-area" id="askme-file-upload-area">
                                <div class="askme-upload-icon">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                        <polyline points="7,10 12,15 17,10"></polyline>
                                        <line x1="12" y1="15" x2="12" y2="3"></line>
                                    </svg>
                                </div>
                                <p class="askme-upload-text">
                                    <?php _e('اسحب وأفلت الملفات هنا أو', 'askro'); ?>
                                    <button type="button" class="askme-upload-btn" id="askme-select-files">
                                        <?php _e('اختر الملفات', 'askro'); ?>
                                    </button>
                                </p>
                                <p class="askme-upload-info">
                                    <?php printf(__('الحد الأقصى: %d ملف، %d ميجابايت لكل ملف', 'askro'), $max_attachments, $max_file_size); ?>
                                </p>
                            </div>
                            <input type="file" id="askme-file-input" multiple accept="image/*,.pdf,.doc,.docx" style="display: none;">
                            <div id="askme-file-preview" class="askme-file-preview"></div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Form Actions -->
                <div class="askme-form-actions">
                    <button type="submit" class="askme-btn askme-btn-primary askme-btn-lg" id="askme-submit-question">
                        <span class="askme-btn-text"><?php _e('إرسال السؤال', 'askro'); ?></span>
                        <span class="askme-btn-loading" style="display: none;">
                            <svg class="askme-spinner" width="20" height="20" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none" stroke-dasharray="31.416" stroke-dashoffset="31.416">
                                    <animate attributeName="stroke-dasharray" dur="2s" values="0 31.416;15.708 15.708;0 31.416" repeatCount="indefinite"/>
                                    <animate attributeName="stroke-dashoffset" dur="2s" values="0;-15.708;-31.416" repeatCount="indefinite"/>
                                </circle>
                            </svg>
                        </span>
                    </button>
                    <button type="button" class="askme-btn askme-btn-secondary" id="askme-save-draft">
                        <?php _e('حفظ كمسودة', 'askro'); ?>
                    </button>
                </div>

            </form>

        <?php endif; ?>
        
    </div>

    <!-- Sidebar -->
    <div class="askme-sidebar">
        
        <!-- Tips Module -->
        <div class="askme-sidebar-module">
            <h3 class="askme-module-title"><?php _e('نصائح لطرح سؤال جيد', 'askro'); ?></h3>
            <div class="askme-module-content">
                <ul class="askme-tips-list">
                    <li><?php _e('اكتب عنواناً واضحاً ومختصراً', 'askro'); ?></li>
                    <li><?php _e('اشرح المشكلة بالتفصيل', 'askro'); ?></li>
                    <li><?php _e('أضف أمثلة أو شاشات توضيحية', 'askro'); ?></li>
                    <li><?php _e('اختر التصنيف المناسب', 'askro'); ?></li>
                    <li><?php _e('أضف علامات مفيدة', 'askro'); ?></li>
                </ul>
            </div>
        </div>

        <!-- User Stats Module -->
        <?php if ($current_user): ?>
            <div class="askme-sidebar-module">
                <h3 class="askme-module-title"><?php _e('إحصائياتك', 'askro'); ?></h3>
                <div class="askme-module-content">
                    <div class="askme-user-stats">
                        <div class="askme-stat-item">
                            <span class="askme-stat-label"><?php _e('النقاط', 'askro'); ?></span>
                            <span class="askme-stat-value"><?php echo number_format($current_user['points']); ?></span>
                        </div>
                        <div class="askme-stat-item">
                            <span class="askme-stat-label"><?php _e('الرتبة', 'askro'); ?></span>
                            <span class="askme-stat-value"><?php echo esc_html($current_user['rank']['current']['name']); ?></span>
                        </div>
                        <div class="askme-stat-item">
                            <span class="askme-stat-label"><?php _e('الأسئلة', 'askro'); ?></span>
                            <span class="askme-stat-value"><?php echo number_format($current_user['questions_count']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Recent Questions Module -->
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
    // Pre-question Assistant
    <?php if ($enable_pre_question_assistant): ?>
    let searchTimeout;
    $('#question_title').on('keyup', function() {
        const title = $(this).val();
        if (title.length < 3) {
            $('#askme-pre-question-assistant').hide();
            return;
        }
        
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            $.ajax({
                url: askro_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'askro_search_questions',
                    title: title,
                    nonce: askro_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        let html = '';
                        response.data.forEach(function(question) {
                            html += '<div class="askme-similar-question">';
                            html += '<a href="' + question.permalink + '">' + question.title + '</a>';
                            if (question.status === 'solved') {
                                html += '<span class="askme-solved-badge">✓</span>';
                            }
                            html += '</div>';
                        });
                        $('#askme-similar-questions').html(html);
                        $('#askme-pre-question-assistant').show();
                    } else {
                        $('#askme-pre-question-assistant').hide();
                    }
                }
            });
        }, 500);
    });
    
    $('.askme-assistant-close').on('click', function() {
        $('#askme-pre-question-assistant').hide();
    });
    <?php endif; ?>

    // File Upload
    <?php if ($enable_image_upload): ?>
    const fileInput = document.getElementById('askme-file-input');
    const uploadArea = document.getElementById('askme-file-upload-area');
    const filePreview = document.getElementById('askme-file-preview');
    const maxFiles = <?php echo $max_attachments; ?>;
    const maxSize = <?php echo $max_file_size * 1024 * 1024; ?>; // Convert to bytes
    
    // Drag and drop
    uploadArea.addEventListener('dragover', function(e) {
        e.preventDefault();
        uploadArea.classList.add('askme-drag-over');
    });
    
    uploadArea.addEventListener('dragleave', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('askme-drag-over');
    });
    
    uploadArea.addEventListener('drop', function(e) {
        e.preventDefault();
        uploadArea.classList.remove('askme-drag-over');
        const files = e.dataTransfer.files;
        handleFiles(files);
    });
    
    // File selection
    document.getElementById('askme-select-files').addEventListener('click', function() {
        fileInput.click();
    });
    
    fileInput.addEventListener('change', function() {
        handleFiles(this.files);
    });
    
    function handleFiles(files) {
        const currentFiles = filePreview.children.length;
        
        for (let i = 0; i < files.length && currentFiles + i < maxFiles; i++) {
            const file = files[i];
            
            if (file.size > maxSize) {
                alert('الملف ' + file.name + ' كبير جداً. الحد الأقصى: ' + <?php echo $max_file_size; ?> + ' ميجابايت');
                continue;
            }
            
            addFilePreview(file);
        }
    }
    
    function addFilePreview(file) {
        const preview = document.createElement('div');
        preview.className = 'askme-file-item';
        preview.innerHTML = `
            <div class="askme-file-info">
                <span class="askme-file-name">${file.name}</span>
                <span class="askme-file-size">${formatFileSize(file.size)}</span>
            </div>
            <button type="button" class="askme-file-remove" onclick="this.parentElement.remove()">×</button>
        `;
        filePreview.appendChild(preview);
    }
    
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    <?php endif; ?>

    // Form Submission
    $('#askme-question-form').on('submit', function(e) {
        e.preventDefault();
        
        const submitBtn = $('#askme-submit-question');
        const btnText = submitBtn.find('.askme-btn-text');
        const btnLoading = submitBtn.find('.askme-btn-loading');
        
        // Show loading
        btnText.hide();
        btnLoading.show();
        submitBtn.prop('disabled', true);
        
        // Prepare form data
        const formData = new FormData(this);
        formData.append('action', 'askro_submit_question');
        formData.append('nonce', $('#askro_question_nonce').val());
        
        $.ajax({
            url: askro_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Redirect to the new question
                    window.location.href = response.data.permalink;
                } else {
                    alert(response.data.message || 'حدث خطأ أثناء إرسال السؤال');
                }
            },
            error: function() {
                alert('حدث خطأ في الاتصال. يرجى المحاولة مرة أخرى.');
            },
            complete: function() {
                // Hide loading
                btnText.show();
                btnLoading.hide();
                submitBtn.prop('disabled', false);
            }
        });
    });
});
</script> 

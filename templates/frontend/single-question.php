<?php
/**
 * Single Question Template
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

// إجبار تحميل أصول البلجن
if (function_exists('askro')) {
    $assets = askro()->get_component('assets');
    if ($assets) {
        $assets->enqueue_frontend_assets();
    }
}

// إضافة JavaScript data للتفاعلات
wp_localize_script('askme-shortcodes-script', 'askroAjax', [
    'ajax_url' => admin_url('admin-ajax.php'),
    'nonce' => wp_create_nonce('askro_nonce'),
    'current_user' => get_current_user_id(),
    'is_logged_in' => is_user_logged_in(),
    'strings' => [
        'login_required' => __('يجب تسجيل الدخول للتفاعل', 'askro'),
        'error_occurred' => __('حدث خطأ', 'askro'),
        'success' => __('تم بنجاح', 'askro')
    ]
]);

// إضافة CSS مخصص للتخطيط
wp_add_inline_style('askro-main-style', '
    .askme-container {
        max-width: 100%;
        margin: 0;
        padding: 20px;
    }
    .askme-single-question-wrapper {
        width: 100%;
    }
');

// استدعاء الهيدر
get_header();

// التحقق من وجود السؤال وعرضه
global $post;
if ($post && $post->post_type === 'askro_question') {
    // التأكد من أن السؤال منشور أو أن المستخدم يمكنه رؤيته
    if ($post->post_status === 'publish' || current_user_can('edit_post', $post->ID)) {
        // عرض المحتوى باستخدام الشورت كود
        echo '<div class="askme-single-question-wrapper">';
        echo do_shortcode('[askro_single_question id="' . $post->ID . '"]');
        echo '</div>';
        
        // إضافة JavaScript لضمان عمل التفاعلات
        wp_add_inline_script('askro-main-script', '
            jQuery(document).ready(function($) {
                // التأكد من ظهور محتوى السؤال
                $(".askme-single-question-wrapper").show();
                
                // تفعيل التفاعلات
                if (typeof askroInitializeVoting === "function") {
                    askroInitializeVoting();
                }
                if (typeof askroInitializeComments === "function") {
                    askroInitializeComments();
                }
            });
        ');
    } else {
        // السؤال غير منشور أو لا يمكن الوصول إليه
        echo '<div class="askme-container">';
        echo '<div class="askme-not-found">';
        echo '<h2>السؤال غير متاح</h2>';
        echo '<p>هذا السؤال غير منشور أو تم حذفه.</p>';
        echo '<a href="' . esc_url(home_url()) . '" class="askme-btn askme-btn-primary">العودة للرئيسية</a>';
        echo '</div>';
        echo '</div>';
    }
} else {
    // ليس سؤالاً صحيحاً
    echo '<div class="askme-container">';
    echo '<div class="askme-not-found">';
    echo '<h2>الصفحة غير موجودة</h2>';
    echo '<p>المحتوى المطلوب غير موجود.</p>';
    echo '<a href="' . esc_url(home_url()) . '" class="askme-btn askme-btn-primary">العودة للرئيسية</a>';
    echo '</div>';
    echo '</div>';
}

// استدعاء الفوتر
get_footer();

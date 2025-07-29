<?php
/**
 * Assets Management Class
 *
 * @package    Askro
 * @subpackage Core/Assets
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
 * Askro Assets Class
 *
 * Handles loading and management of CSS and JavaScript assets
 * for both frontend and admin areas.
 *
 * @since 1.0.0
 */
class Askro_Assets {

    /**
     * Initialize the assets component
     *
     * @since 1.0.0
     */
    public function init() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_head', [$this, 'add_inline_styles']);
        add_action('wp_footer', [$this, 'add_inline_scripts']);
    }

    /**
     * Enqueue frontend assets
     *
     * @since 1.0.0
     */
    public function enqueue_frontend_assets() {
        // Only load on pages that need Askro
        if (!$this->should_load_assets()) {
            return;
        }

        // Register and enqueue main stylesheet
        wp_register_style(
            'askro-main-style',
            ASKRO_ASSETS_URL . 'css/style.css',
            [],
            $this->get_asset_version('css/style.css'),
            'all'
        );
        wp_enqueue_style('askro-main-style');

        // Register and enqueue AskMe shortcodes stylesheet
        wp_register_style(
            'askme-shortcodes-style',
            ASKRO_ASSETS_URL . 'css/src/askme-shortcodes.css',
            ['askro-main-style'],
            $this->get_asset_version('css/src/askme-shortcodes.css'),
            'all'
        );
        wp_enqueue_style('askme-shortcodes-style');

        // Register vendor styles
        $this->register_vendor_styles();

        // Register and enqueue main JavaScript
        wp_register_script(
            'askro-main-script',
            ASKRO_ASSETS_URL . 'js/main.js',
            ['jquery'],
            $this->get_asset_version('js/main.js'),
            true
        );
        wp_enqueue_script('askro-main-script');

        // Register and enqueue AskMe shortcodes script
        wp_register_script(
            'askme-shortcodes-script',
            ASKRO_ASSETS_URL . 'js/src/askme-shortcodes.js',
            ['jquery', 'askro-main-script'],
            $this->get_asset_version('js/src/askme-shortcodes.js'),
            true
        );
        wp_enqueue_script('askme-shortcodes-script');



        // Register vendor scripts
        $this->register_vendor_scripts();

        // Localize script with data
        $this->localize_frontend_script();

        // Conditionally load additional assets
        $this->conditional_asset_loading();
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     * @since 1.0.0
     */
    public function enqueue_admin_assets($hook) {
        // Only load on Askro admin pages
        if (!$this->is_askro_admin_page($hook)) {
            return;
        }

        // Register and enqueue admin stylesheet
        wp_register_style(
            'askro-admin-style',
            ASKRO_ASSETS_URL . 'css/admin.css',
            [],
            $this->get_asset_version('css/admin.css'),
            'all'
        );
        wp_enqueue_style('askro-admin-style');

        // Register and enqueue admin JavaScript
        wp_register_script(
            'askro-admin-script',
            ASKRO_ASSETS_URL . 'js/admin.js',
            ['jquery', 'wp-color-picker'],
            $this->get_asset_version('js/admin.js'),
            true
        );
        wp_enqueue_script('askro-admin-script');

        // Enqueue WordPress color picker
        wp_enqueue_style('wp-color-picker');

        // Enqueue media uploader if needed
        if ($this->needs_media_uploader($hook)) {
            wp_enqueue_media();
        }

        // Localize admin script
        $this->localize_admin_script();
    }

    /**
     * Register vendor styles
     *
     * @since 1.0.0
     */
    private function register_vendor_styles() {
        // Swiper CSS
        wp_register_style(
            'askro-swiper',
            ASKRO_ASSETS_URL . 'js/vendor/swiper/swiper-bundle.css',
            [],
            '11.0.5'
        );

        // Cropper CSS
        wp_register_style(
            'askro-cropper',
            ASKRO_ASSETS_URL . 'js/vendor/cropper/cropper.css',
            [],
            '1.6.1'
        );

        // Tagify CSS
        wp_register_style(
            'askro-tagify',
            ASKRO_ASSETS_URL . 'js/vendor/tagify/tagify.css',
            [],
            '4.21.1'
        );

        // Toastr CSS
        wp_register_style(
            'askro-toastr',
            ASKRO_ASSETS_URL . 'js/vendor/toastr/toastr.css',
            [],
            '2.1.4'
        );
    }

    /**
     * Register vendor scripts
     *
     * @since 1.0.0
     */
    private function register_vendor_scripts() {
        // Chart.js
        wp_register_script(
            'askro-chart',
            ASKRO_ASSETS_URL . 'js/vendor/chart/chart.js',
            [],
            '4.4.0',
            true
        );

        // Swiper JS
        wp_register_script(
            'askro-swiper',
            ASKRO_ASSETS_URL . 'js/vendor/swiper/swiper-bundle.js',
            [],
            '11.0.5',
            true
        );

        // Cropper JS
        wp_register_script(
            'askro-cropper',
            ASKRO_ASSETS_URL . 'js/vendor/cropper/cropper.js',
            [],
            '1.6.1',
            true
        );

        // Tagify JS
        wp_register_script(
            'askro-tagify',
            ASKRO_ASSETS_URL . 'js/vendor/tagify/tagify.js',
            [],
            '4.21.1',
            true
        );

        // Toastr JS
        wp_register_script(
            'askro-toastr',
            ASKRO_ASSETS_URL . 'js/vendor/toastr/toastr.min.js',
            [],
            '2.1.4',
            true
        );

        // Anime.js
        wp_register_script(
            'askro-anime',
            ASKRO_ASSETS_URL . 'js/vendor/anime/anime.min.js',
            [],
            '3.2.1',
            true
        );
    }

    /**
     * Conditional asset loading based on page content
     *
     * @since 1.0.0
     */
    private function conditional_asset_loading() {
        global $post;

        if (!$post) {
            return;
        }

        $content = $post->post_content;

        // Load Chart.js for user profile and analytics
                if (has_shortcode($content, 'askro_user_profile') ||
            has_shortcode($content, 'askro_leaderboard') ||
            has_shortcode($content, 'askro_user_profile') || 
            has_shortcode($content, 'askro_analytics')) {
            wp_enqueue_script('askro-chart');
        }

        // Load Swiper for image galleries and sliders
        if (has_shortcode($content, 'askro_single_question') ||
            has_shortcode($content, 'askro_archive') ||
            has_shortcode($content, 'askro_single_question') ||
            strpos($content, 'askro-gallery') !== false) {
            wp_enqueue_style('askro-swiper');
            wp_enqueue_script('askro-swiper');
        }

        // Load Tagify for question forms and tag inputs
        if (has_shortcode($content, 'askro_ask_question_form') ||
            has_shortcode($content, 'askro_submit_question_form') ||
            has_shortcode($content, 'askro_submit_answer_form')) {
            wp_enqueue_style('askro-tagify');
            wp_enqueue_script('askro-tagify');
        }

        // Load Cropper for image upload and editing
        if (has_shortcode($content, 'askro_ask_question_form') ||
            has_shortcode($content, 'askro_submit_question_form')) {
            wp_enqueue_style('askro-cropper');
            wp_enqueue_script('askro-cropper');
        }

        // Always load Toastr for notifications
        wp_enqueue_style('askro-toastr');
        wp_enqueue_script('askro-toastr');

        // Load Anime.js for animations
        wp_enqueue_script('askro-anime');
    }

    /**
     * Localize frontend script with data
     *
     * @since 1.0.0
     */
    private function localize_frontend_script() {
        $current_user = wp_get_current_user();
        
        $localize_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('askro_nonce'),
            'askro_comment_nonce' => wp_create_nonce('askro_add_comment'),
            'plugin_url' => ASKRO_PLUGIN_URL,
            'current_user' => $current_user->ID,
            'is_logged_in' => is_user_logged_in(),
            'debug_mode' => defined('WP_DEBUG') && WP_DEBUG,
            'strings' => [
                'vote_error' => __('حدث خطأ أثناء التصويت. يرجى المحاولة مرة أخرى.', 'askro'),
                'login_required' => __('يجب تسجيل الدخول للتصويت.', 'askro'),
                'draft_saved' => __('تم حفظ المسودة تلقائياً.', 'askro'),
                'confirm_delete' => __('هل أنت متأكد من الحذف؟', 'askro'),
                'loading' => __('جاري التحميل...', 'askro'),
                'error' => __('حدث خطأ غير متوقع.', 'askro'),
                'success' => __('تمت العملية بنجاح.', 'askro'),
                'no_results' => __('لا توجد نتائج.', 'askro'),
                'load_more' => __('تحميل المزيد', 'askro'),
                'show_less' => __('إظهار أقل', 'askro')
            ]
        ];

        wp_localize_script('askro-main-script', 'askroAjax', $localize_data);
    }

    /**
     * Localize admin script with data
     *
     * @since 1.0.0
     */
    private function localize_admin_script() {
        $localize_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('askro_admin_nonce'),
            'strings' => [
                'saving' => __('جاري الحفظ...', 'askro'),
                'save' => __('حفظ', 'askro'),
                'save_settings' => __('حفظ الإعدادات', 'askro'),
                'save_error' => __('حدث خطأ أثناء الحفظ.', 'askro'),
                'confirm_bulk_action' => __('هل أنت متأكد من تنفيذ هذا الإجراء؟', 'askro'),
                'confirm_system_tool' => __('هل أنت متأكد من تشغيل هذه الأداة؟', 'askro'),
                'no_items_selected' => __('لم يتم تحديد أي عناصر.', 'askro'),
                'bulk_action_error' => __('حدث خطأ أثناء تنفيذ الإجراء المجمع.', 'askro'),
                'system_tool_error' => __('حدث خطأ أثناء تشغيل الأداة.', 'askro'),
                'field_required' => __('هذا الحقل مطلوب.', 'askro'),
                'invalid_email' => __('عنوان البريد الإلكتروني غير صحيح.', 'askro'),
                'select_image' => __('اختر صورة', 'askro'),
                'use_image' => __('استخدم هذه الصورة', 'askro'),
                'questions' => __('الأسئلة', 'askro'),
                'answers' => __('الإجابات', 'askro'),
                'users' => __('المستخدمون', 'askro'),
                'votes' => __('التصويتات', 'askro')
            ]
        ];

        wp_localize_script('askro-admin-script', 'askroAdmin', $localize_data);
    }

    /**
     * Add inline styles to head
     *
     * @since 1.0.0
     */
    public function add_inline_styles() {
        if (!$this->should_load_assets()) {
            return;
        }

        // Get theme customizations
        $primary_color = get_option('askro_primary_color', '#3b82f6');
        $secondary_color = get_option('askro_secondary_color', '#8b5cf6');
        $accent_color = get_option('askro_accent_color', '#06b6d4');

        echo '<style id="askro-custom-styles">';
        echo ':root {';
        echo '--askro-primary: ' . esc_attr($primary_color) . ';';
        echo '--askro-secondary: ' . esc_attr($secondary_color) . ';';
        echo '--askro-accent: ' . esc_attr($accent_color) . ';';
        echo '}';
        echo '</style>';
    }

    /**
     * Add inline scripts to footer
     *
     * @since 1.0.0
     */
    public function add_inline_scripts() {
        if (!$this->should_load_assets()) {
            return;
        }

        // Add any inline JavaScript if needed
        echo '<script id="askro-inline-scripts">';
        echo '// Askro inline scripts can go here';
        echo '</script>';
    }

    /**
     * Check if assets should be loaded
     *
     * @return bool
     * @since 1.0.0
     */
    private function should_load_assets() {
        global $post;

        // Always load on Askro pages
        if (is_page() && $post) {
            $content = $post->post_content;
            
            // Check for AskMe shortcodes
                    $askro_shortcodes = [
            'askro_archive',
            'askro_single_question',
            'askro_ask_question_form',
            'askro_user_profile',
            'askro_leaderboard',
            'askro_search_results',
            'askro_questions_list',
            'askro_user_stat',
            'askro_community_stat'
        ];

            foreach ($askro_shortcodes as $shortcode) {
                if (has_shortcode($content, $shortcode)) {
                    return true;
                }
            }
        }

        // Load on question and answer post types - PRIORITY
        if (is_singular(['askro_question', 'askro_answer'])) {
            return true;
        }

        // Load on question archive
        if (is_post_type_archive('askro_question')) {
            return true;
        }

        // Load on question taxonomy pages
        if (is_tax(['askro_question_category', 'askro_question_tag'])) {
            return true;
        }

        // Check if current template is our custom template
        if (is_singular('askro_question')) {
            $template = get_page_template_slug();
            if (empty($template)) {
                // Check if we're using our custom template
                $custom_template = ASKRO_PLUGIN_DIR . 'templates/frontend/single-question.php';
                if (file_exists($custom_template)) {
                    return true;
                }
            }
        }

        // Allow themes/plugins to override
        return apply_filters('askro_should_load_assets', false);
    }

    /**
     * Check if current page is an Askro admin page
     *
     * @param string $hook Current admin page hook
     * @return bool
     * @since 1.0.0
     */
    private function is_askro_admin_page($hook) {
        // Askro admin pages
        $askro_pages = [
            'toplevel_page_askro',
            'askro_page_askro-voting-points',
            'askro_page_askro-status-tools',
            'askro_page_askro-analytics',
            'askro_page_askro-settings'
        ];

        if (in_array($hook, $askro_pages)) {
            return true;
        }

        // Askro post type edit pages
        global $post_type;
        if (in_array($post_type, ['askro_question', 'askro_answer'])) {
            return true;
        }

        return false;
    }

    /**
     * Check if media uploader is needed
     *
     * @param string $hook Current admin page hook
     * @return bool
     * @since 1.0.0
     */
    private function needs_media_uploader($hook) {
        $media_pages = [
            'askro_page_askro-settings'
        ];

        return in_array($hook, $media_pages);
    }

    /**
     * Get asset version for cache busting
     *
     * @param string $asset_path Relative path to asset
     * @return string Version string
     * @since 1.0.0
     */
    private function get_asset_version($asset_path) {
        $file_path = ASKRO_PLUGIN_DIR . 'assets/' . $asset_path;
        
        if (file_exists($file_path)) {
            return filemtime($file_path);
        }
        
        return ASKRO_VERSION;
    }

    /**
     * Preload critical assets
     *
     * @since 1.0.0
     */
    public function preload_critical_assets() {
        if (!$this->should_load_assets()) {
            return;
        }

        // Preload critical CSS
        echo '<link rel="preload" href="' . esc_url(ASKRO_ASSETS_URL . 'css/style.css') . '" as="style">';
        
        // Preload critical JavaScript
        echo '<link rel="preload" href="' . esc_url(ASKRO_ASSETS_URL . 'js/main.js') . '" as="script">';
    }

    /**
     * Add resource hints
     *
     * @since 1.0.0
     */
    public function add_resource_hints() {
        if (!$this->should_load_assets()) {
            return;
        }

        // DNS prefetch for external resources
        echo '<link rel="dns-prefetch" href="//fonts.googleapis.com">';
        echo '<link rel="dns-prefetch" href="//fonts.gstatic.com">';
    }

    /**
     * Get all registered Askro assets
     *
     * @return array
     * @since 1.0.0
     */
    public function get_registered_assets() {
        global $wp_scripts, $wp_styles;
        
        $askro_assets = [
            'styles' => [],
            'scripts' => []
        ];

        // Get Askro styles
        foreach ($wp_styles->registered as $handle => $style) {
            if (strpos($handle, 'askro-') === 0) {
                $askro_assets['styles'][$handle] = [
                    'src' => $style->src,
                    'version' => $style->ver,
                    'deps' => $style->deps
                ];
            }
        }

        // Get Askro scripts
        foreach ($wp_scripts->registered as $handle => $script) {
            if (strpos($handle, 'askro-') === 0) {
                $askro_assets['scripts'][$handle] = [
                    'src' => $script->src,
                    'version' => $script->ver,
                    'deps' => $script->deps
                ];
            }
        }

        return $askro_assets;
    }
}


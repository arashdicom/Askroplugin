<?php
/**
 * URL Handler Class
 *
 * @package    Askro
 * @subpackage Core/URLHandler
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
 * Askro URL Handler Class
 *
 * Handles custom URL structure for questions and answers
 *
 * @since 1.0.0
 */
class Askro_URL_Handler {

    /**
     * Initialize the URL handler
     *
     * @since 1.0.0
     */
    public function init() {
        // قواعد rewrite مخصصة لروابط الأسئلة الفردية
        add_action('init', [$this, 'add_custom_rewrite_rule']);
        add_filter('query_vars', [$this, 'add_custom_query_var']);
        add_action('template_redirect', [$this, 'handle_question_requests']);
        add_filter('post_type_link', [$this, 'modify_question_permalink'], 10, 2);
    }

    /**
     * أضف قاعدة rewrite: /archive-slug/question-slug/ → index.php?page_id=archive_id&askro_question_slug=slug
     */
    public function add_custom_rewrite_rule() {
        $archive_page_id = askro_get_option('archive_page_id', 0);
        if ($archive_page_id) {
            $archive_page = get_post($archive_page_id);
            if ($archive_page) {
                $archive_slug = $archive_page->post_name;
                
                // إضافة قاعدة rewrite للأسئلة الفردية
                add_rewrite_rule(
                    '^' . $archive_slug . '/([^/]+)/?$',
                    'index.php?page_id=' . $archive_page_id . '&askro_question_slug=$matches[1]',
                    'top'
                );
                
                // إضافة قاعدة rewrite للصفحات الفرعية (مثل التعليقات)
                add_rewrite_rule(
                    '^' . $archive_slug . '/([^/]+)/([^/]+)/?$',
                    'index.php?page_id=' . $archive_page_id . '&askro_question_slug=$matches[1]&askro_action=$matches[2]',
                    'top'
                );
            }
        }
    }

    /**
     * أضف متغير askro_question_slug إلى قائمة المتغيرات المسموحة
     */
    public function add_custom_query_var($vars) {
        $vars[] = 'askro_question_slug';
        $vars[] = 'askro_action';
        return $vars;
    }

    /**
     * Handle question requests
     *
     * @since 1.0.0
     */
    public function handle_question_requests() {
        $question_slug = get_query_var('askro_question_slug');
        
        if ($question_slug) {
            // Find the question by slug
            $question = get_page_by_path($question_slug, OBJECT, 'askro_question');
            
            if (!$question || $question->post_type !== 'askro_question') {
                // Question not found, redirect to archive page
                $archive_page_id = askro_get_option('archive_page_id', 0);
                if ($archive_page_id) {
                    wp_redirect(get_permalink($archive_page_id), 301);
                    exit;
                }
                // إذا لم توجد صفحة أرشيف، اعرض 404
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                return;
            }
            
            // تحقق من أن السؤال منشور أو يمكن للمستخدم رؤيته
            if ($question->post_status !== 'publish' && !current_user_can('edit_post', $question->ID)) {
                // السؤال غير منشور
                global $wp_query;
                $wp_query->set_404();
                status_header(404);
                return;
            }
            
            // Set up the query to load the archive page with question data
            $archive_page_id = askro_get_option('archive_page_id', 0);
            if ($archive_page_id) {
                global $wp_query, $post;
                
                // Add question data to global scope first
                global $askro_current_question;
                $askro_current_question = $question;
                
                // Set up the query to load the archive page
                $wp_query = new WP_Query([
                    'page_id' => $archive_page_id,
                    'post_type' => 'page'
                ]);
                
                if ($wp_query->have_posts()) {
                    $wp_query->the_post();
                    
                    // Set up post data for the archive page
                    setup_postdata($post);
                    
                    // Let WordPress handle the template loading normally
                    return;
                }
            }
            
            // إذا لم نجد صفحة أرشيف، جرب عرض السؤال مباشرة
            global $wp_query, $post;
            $wp_query = new WP_Query([
                'p' => $question->ID,
                'post_type' => 'askro_question'
            ]);
            
            if ($wp_query->have_posts()) {
                $wp_query->the_post();
                setup_postdata($post);
                return;
            }
        }
    }

    /**
     * Modify question permalink
     *
     * @param string $permalink Default permalink
     * @param object $post Post object
     * @return string Modified permalink
     * @since 1.0.0
     */
    public function modify_question_permalink($permalink, $post) {
        if ($post->post_type === 'askro_question') {
            return askro_get_question_url($post->ID);
        }
        
        return $permalink;
    }
} 

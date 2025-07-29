<?php
/**
 * Custom Post Types Class
 *
 * @package    Askro
 * @subpackage Core/PostTypes
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
 * Askro Post Types Class
 *
 * Handles registration of custom post types for questions and answers
 *
 * @since 1.0.0
 */
class Askro_Post_Types {

    /**
     * Initialize the post types component
     *
     * @since 1.0.0
     */
    public function init() {
        add_action('init', [$this, 'register_post_types']);
        add_action('init', [$this, 'register_taxonomies']);
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_boxes']);
        add_filter('manage_askro_question_posts_columns', [$this, 'question_columns']);
        add_filter('manage_askro_answer_posts_columns', [$this, 'answer_columns']);
        add_action('manage_askro_question_posts_custom_column', [$this, 'question_column_content'], 10, 2);
        add_action('manage_askro_answer_posts_custom_column', [$this, 'answer_column_content'], 10, 2);
        add_filter('post_updated_messages', [$this, 'updated_messages']);
        add_action('pre_get_posts', [$this, 'modify_main_query']);
        
        // إخفاء قوائم الأسئلة والإجابات الافتراضية من لوحة التحكم
        add_action('admin_menu', [$this, 'hide_default_post_type_menus'], 999);
        
        // إنشاء التصنيفات الافتراضية عند التفعيل
        add_action('askro_plugin_activated', [$this, 'create_default_categories']);
    }

    /**
     * Create default categories
     *
     * @since 1.0.0
     */
    public function create_default_categories() {
        $default_categories = [
            'البرمجة' => [
                'description' => 'أسئلة حول البرمجة والتطوير',
                'children' => [
                    'JavaScript' => 'أسئلة حول JavaScript وNode.js',
                    'PHP' => 'أسئلة حول PHP وWordPress',
                    'Python' => 'أسئلة حول Python',
                    'Java' => 'أسئلة حول Java',
                    'C++' => 'أسئلة حول C++',
                    'HTML/CSS' => 'أسئلة حول HTML وCSS',
                    'React' => 'أسئلة حول React.js',
                    'Vue.js' => 'أسئلة حول Vue.js',
                    'Angular' => 'أسئلة حول Angular',
                    'Laravel' => 'أسئلة حول Laravel',
                    'Django' => 'أسئلة حول Django',
                    'Flutter' => 'أسئلة حول Flutter',
                    'React Native' => 'أسئلة حول React Native'
                ]
            ],
            'قاعدة البيانات' => [
                'description' => 'أسئلة حول قواعد البيانات وإدارة البيانات',
                'children' => [
                    'MySQL' => 'أسئلة حول MySQL',
                    'PostgreSQL' => 'أسئلة حول PostgreSQL',
                    'MongoDB' => 'أسئلة حول MongoDB',
                    'SQLite' => 'أسئلة حول SQLite',
                    'Redis' => 'أسئلة حول Redis'
                ]
            ],
            'الذكاء الاصطناعي' => [
                'description' => 'أسئلة حول الذكاء الاصطناعي والتعلم الآلي',
                'children' => [
                    'Machine Learning' => 'أسئلة حول التعلم الآلي',
                    'Deep Learning' => 'أسئلة حول التعلم العميق',
                    'Computer Vision' => 'أسئلة حول رؤية الحاسوب',
                    'NLP' => 'أسئلة حول معالجة اللغة الطبيعية',
                    'TensorFlow' => 'أسئلة حول TensorFlow',
                    'PyTorch' => 'أسئلة حول PyTorch'
                ]
            ],
            'DevOps' => [
                'description' => 'أسئلة حول DevOps وإدارة البنية التحتية',
                'children' => [
                    'Docker' => 'أسئلة حول Docker',
                    'Kubernetes' => 'أسئلة حول Kubernetes',
                    'AWS' => 'أسئلة حول Amazon Web Services',
                    'Azure' => 'أسئلة حول Microsoft Azure',
                    'Google Cloud' => 'أسئلة حول Google Cloud Platform',
                    'CI/CD' => 'أسئلة حول التكامل المستمر والنشر المستمر',
                    'Linux' => 'أسئلة حول Linux وإدارة الخوادم'
                ]
            ],
            'الأمان السيبراني' => [
                'description' => 'أسئلة حول الأمان السيبراني وحماية البيانات',
                'children' => [
                    'Network Security' => 'أسئلة حول أمان الشبكات',
                    'Web Security' => 'أسئلة حول أمان الويب',
                    'Cryptography' => 'أسئلة حول التشفير',
                    'Penetration Testing' => 'أسئلة حول اختبار الاختراق',
                    'Malware Analysis' => 'أسئلة حول تحليل البرمجيات الخبيثة'
                ]
            ],
            'الموبايل' => [
                'description' => 'أسئلة حول تطوير تطبيقات الموبايل',
                'children' => [
                    'iOS Development' => 'أسئلة حول تطوير تطبيقات iOS',
                    'Android Development' => 'أسئلة حول تطوير تطبيقات Android',
                    'Cross-Platform' => 'أسئلة حول التطوير عبر المنصات',
                    'React Native' => 'أسئلة حول React Native',
                    'Flutter' => 'أسئلة حول Flutter',
                    'Xamarin' => 'أسئلة حول Xamarin'
                ]
            ],
            'الويب' => [
                'description' => 'أسئلة حول تطوير الويب والتصميم',
                'children' => [
                    'Frontend Development' => 'أسئلة حول تطوير الواجهة الأمامية',
                    'Backend Development' => 'أسئلة حول تطوير الخلفية',
                    'Full Stack' => 'أسئلة حول التطوير الشامل',
                    'Web Design' => 'أسئلة حول تصميم الويب',
                    'SEO' => 'أسئلة حول تحسين محركات البحث',
                    'Performance' => 'أسئلة حول أداء الويب'
                ]
            ],
            'الأدوات والتقنيات' => [
                'description' => 'أسئلة حول الأدوات والتقنيات المختلفة',
                'children' => [
                    'Git' => 'أسئلة حول Git وإدارة الإصدارات',
                    'VS Code' => 'أسئلة حول Visual Studio Code',
                    'IDE' => 'أسئلة حول بيئات التطوير المتكاملة',
                    'Terminal' => 'أسئلة حول سطر الأوامر',
                    'API' => 'أسئلة حول واجهات برمجة التطبيقات',
                    'Microservices' => 'أسئلة حول الخدمات المصغرة'
                ]
            ],
            'التعليم والموارد' => [
                'description' => 'أسئلة حول التعلم والموارد التعليمية',
                'children' => [
                    'Online Courses' => 'أسئلة حول الدورات التدريبية عبر الإنترنت',
                    'Books' => 'أسئلة حول الكتب والمراجع',
                    'Tutorials' => 'أسئلة حول البرامج التعليمية',
                    'Communities' => 'أسئلة حول المجتمعات التقنية',
                    'Career Advice' => 'نصائح مهنية في مجال التقنية'
                ]
            ],
            'مشاكل وحلول' => [
                'description' => 'أسئلة حول حل المشاكل التقنية',
                'children' => [
                    'Debugging' => 'أسئلة حول تصحيح الأخطاء',
                    'Error Handling' => 'أسئلة حول معالجة الأخطاء',
                    'Performance Issues' => 'أسئلة حول مشاكل الأداء',
                    'Compatibility' => 'أسئلة حول التوافق',
                    'Best Practices' => 'أسئلة حول أفضل الممارسات'
                ]
            ]
        ];

        foreach ($default_categories as $category_name => $category_data) {
            // إنشاء التصنيف الرئيسي
            $parent_term = term_exists($category_name, 'askro_question_category');
            
            if (!$parent_term) {
                $parent_term = wp_insert_term($category_name, 'askro_question_category', [
                    'description' => $category_data['description']
                ]);
            }
            
            if (!is_wp_error($parent_term) && isset($category_data['children'])) {
                $parent_id = is_array($parent_term) ? $parent_term['term_id'] : $parent_term;
                
                // إنشاء التصنيفات الفرعية
                foreach ($category_data['children'] as $child_name => $child_description) {
                    $child_term = term_exists($child_name, 'askro_question_category');
                    
                    if (!$child_term) {
                        wp_insert_term($child_name, 'askro_question_category', [
                            'description' => $child_description,
                            'parent' => $parent_id
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Register custom post types
     *
     * @since 1.0.0
     */
    public function register_post_types() {
        $this->register_question_post_type();
        $this->register_answer_post_type();
    }

    /**
     * Register question post type
     *
     * @since 1.0.0
     */
    private function register_question_post_type() {
        $labels = [
            'name' => __('الأسئلة', 'askro'),
            'singular_name' => __('سؤال', 'askro'),
            'menu_name' => __('الأسئلة', 'askro'),
            'name_admin_bar' => __('سؤال', 'askro'),
            'add_new' => __('إضافة جديد', 'askro'),
            'add_new_item' => __('إضافة سؤال جديد', 'askro'),
            'new_item' => __('سؤال جديد', 'askro'),
            'edit_item' => __('تحرير السؤال', 'askro'),
            'view_item' => __('عرض السؤال', 'askro'),
            'all_items' => __('جميع الأسئلة', 'askro'),
            'search_items' => __('البحث في الأسئلة', 'askro'),
            'parent_item_colon' => __('السؤال الأصل:', 'askro'),
            'not_found' => __('لم يتم العثور على أسئلة.', 'askro'),
            'not_found_in_trash' => __('لم يتم العثور على أسئلة في المهملات.', 'askro'),
            'featured_image' => __('صورة السؤال', 'askro'),
            'set_featured_image' => __('تعيين صورة السؤال', 'askro'),
            'remove_featured_image' => __('إزالة صورة السؤال', 'askro'),
            'use_featured_image' => __('استخدام كصورة السؤال', 'askro'),
            'archives' => __('أرشيف الأسئلة', 'askro'),
            'insert_into_item' => __('إدراج في السؤال', 'askro'),
            'uploaded_to_this_item' => __('تم رفعه لهذا السؤال', 'askro'),
            'filter_items_list' => __('تصفية قائمة الأسئلة', 'askro'),
            'items_list_navigation' => __('تنقل قائمة الأسئلة', 'askro'),
            'items_list' => __('قائمة الأسئلة', 'askro'),
        ];

        $args = [
            'labels' => $labels,
            'description' => __('أسئلة المستخدمين في نظام Askro', 'askro'),
            'public' => true,
            'publicly_queryable' => true, // تمكين الاستعلامات العامة
            'show_ui' => true,
            'show_in_menu' => false, // إخفاء من القوائم الافتراضية
            'show_in_nav_menus' => false,
            'show_in_admin_bar' => true,
            'show_in_rest' => true,
            'query_var' => true, // تمكين query vars
            'rewrite' => ['slug' => 'question', 'with_front' => false], // تمكين rewrite مع slug مخصص
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'edit_posts',
            ],
            'map_meta_cap' => true,
            'has_archive' => false, // Disable archive (we use custom pages)
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => 'dashicons-editor-help',
            'supports' => [
                'title',
                'editor',
                'author',
                'thumbnail',
                'excerpt',
                'comments',
                'revisions',
                'custom-fields',
            ],
            'taxonomies' => ['askro_question_category', 'askro_question_tag'],
        ];

        register_post_type('askro_question', $args);
    }

    /**
     * Register answer post type
     *
     * @since 1.0.0
     */
    private function register_answer_post_type() {
        $labels = [
            'name' => __('الإجابات', 'askro'),
            'singular_name' => __('إجابة', 'askro'),
            'menu_name' => __('الإجابات', 'askro'),
            'name_admin_bar' => __('إجابة', 'askro'),
            'add_new' => __('إضافة جديد', 'askro'),
            'add_new_item' => __('إضافة إجابة جديدة', 'askro'),
            'new_item' => __('إجابة جديدة', 'askro'),
            'edit_item' => __('تحرير الإجابة', 'askro'),
            'view_item' => __('عرض الإجابة', 'askro'),
            'all_items' => __('جميع الإجابات', 'askro'),
            'search_items' => __('البحث في الإجابات', 'askro'),
            'parent_item_colon' => __('السؤال:', 'askro'),
            'not_found' => __('لم يتم العثور على إجابات.', 'askro'),
            'not_found_in_trash' => __('لم يتم العثور على إجابات في المهملات.', 'askro'),
            'featured_image' => __('صورة الإجابة', 'askro'),
            'set_featured_image' => __('تعيين صورة الإجابة', 'askro'),
            'remove_featured_image' => __('إزالة صورة الإجابة', 'askro'),
            'use_featured_image' => __('استخدام كصورة الإجابة', 'askro'),
            'archives' => __('أرشيف الإجابات', 'askro'),
            'insert_into_item' => __('إدراج في الإجابة', 'askro'),
            'uploaded_to_this_item' => __('تم رفعه لهذه الإجابة', 'askro'),
            'filter_items_list' => __('تصفية قائمة الإجابات', 'askro'),
            'items_list_navigation' => __('تنقل قائمة الإجابات', 'askro'),
            'items_list' => __('قائمة الإجابات', 'askro'),
        ];

        $args = [
            'labels' => $labels,
            'description' => __('إجابات المستخدمين على الأسئلة في نظام Askro', 'askro'),
            'public' => true,
            'publicly_queryable' => false, // تعطيل للتوافق مع نظام الروابط
            'show_ui' => true,
            'show_in_menu' => false, // إخفاء من القوائم الافتراضية
            'show_in_nav_menus' => false,
            'show_in_admin_bar' => true,
            'show_in_rest' => true,
            'query_var' => false, // تعطيل لتجنب تعارض مع نظام الروابط
            'rewrite' => false, // تعطيل rewrite rules
            'capability_type' => 'post',
            'capabilities' => [
                'create_posts' => 'edit_posts',
            ],
            'map_meta_cap' => true,
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => null,
            'menu_icon' => 'dashicons-format-chat',
            'supports' => [
                'editor',
                'author',
                'thumbnail',
                'revisions',
                'custom-fields',
            ],
        ];

        register_post_type('askro_answer', $args);
    }

    /**
     * Register taxonomies
     *
     * @since 1.0.0
     */
    public function register_taxonomies() {
        $this->register_question_category_taxonomy();
        $this->register_question_tag_taxonomy();
    }

    /**
     * Register question category taxonomy
     *
     * @since 1.0.0
     */
    private function register_question_category_taxonomy() {
        $labels = [
            'name' => __('تصنيفات الأسئلة', 'askro'),
            'singular_name' => __('تصنيف', 'askro'),
            'menu_name' => __('التصنيفات', 'askro'),
            'all_items' => __('جميع التصنيفات', 'askro'),
            'parent_item' => __('التصنيف الأصل', 'askro'),
            'parent_item_colon' => __('التصنيف الأصل:', 'askro'),
            'new_item_name' => __('اسم التصنيف الجديد', 'askro'),
            'add_new_item' => __('إضافة تصنيف جديد', 'askro'),
            'edit_item' => __('تحرير التصنيف', 'askro'),
            'update_item' => __('تحديث التصنيف', 'askro'),
            'view_item' => __('عرض التصنيف', 'askro'),
            'separate_items_with_commas' => __('فصل التصنيفات بفواصل', 'askro'),
            'add_or_remove_items' => __('إضافة أو إزالة التصنيفات', 'askro'),
            'choose_from_most_used' => __('اختر من الأكثر استخداماً', 'askro'),
            'popular_items' => __('التصنيفات الشائعة', 'askro'),
            'search_items' => __('البحث في التصنيفات', 'askro'),
            'not_found' => __('لم يتم العثور على تصنيفات', 'askro'),
            'no_terms' => __('لا توجد تصنيفات', 'askro'),
            'items_list' => __('قائمة التصنيفات', 'askro'),
            'items_list_navigation' => __('تنقل قائمة التصنيفات', 'askro'),
        ];

        $args = [
            'labels' => $labels,
            'hierarchical' => true,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_rest' => true,
            'rewrite' => [
                'slug' => askro_get_option('category_slug', 'question-category'),
                'with_front' => false,
                'hierarchical' => true,
            ],
            'capabilities' => [
                'manage_terms' => 'manage_categories',
                'edit_terms' => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_posts',
            ],
        ];

        register_taxonomy('askro_question_category', ['askro_question'], $args);
    }

    /**
     * Register question tag taxonomy
     *
     * @since 1.0.0
     */
    private function register_question_tag_taxonomy() {
        $labels = [
            'name' => __('علامات الأسئلة', 'askro'),
            'singular_name' => __('علامة', 'askro'),
            'menu_name' => __('العلامات', 'askro'),
            'all_items' => __('جميع العلامات', 'askro'),
            'parent_item' => null,
            'parent_item_colon' => null,
            'new_item_name' => __('اسم العلامة الجديدة', 'askro'),
            'add_new_item' => __('إضافة علامة جديدة', 'askro'),
            'edit_item' => __('تحرير العلامة', 'askro'),
            'update_item' => __('تحديث العلامة', 'askro'),
            'view_item' => __('عرض العلامة', 'askro'),
            'separate_items_with_commas' => __('فصل العلامات بفواصل', 'askro'),
            'add_or_remove_items' => __('إضافة أو إزالة العلامات', 'askro'),
            'choose_from_most_used' => __('اختر من الأكثر استخداماً', 'askro'),
            'popular_items' => __('العلامات الشائعة', 'askro'),
            'search_items' => __('البحث في العلامات', 'askro'),
            'not_found' => __('لم يتم العثور على علامات', 'askro'),
            'no_terms' => __('لا توجد علامات', 'askro'),
            'items_list' => __('قائمة العلامات', 'askro'),
            'items_list_navigation' => __('تنقل قائمة العلامات', 'askro'),
        ];

        $args = [
            'labels' => $labels,
            'hierarchical' => false,
            'public' => true,
            'show_ui' => true,
            'show_admin_column' => true,
            'show_in_nav_menus' => true,
            'show_tagcloud' => true,
            'show_in_rest' => true,
            'rewrite' => [
                'slug' => askro_get_option('tag_slug', 'question-tag'),
                'with_front' => false,
            ],
            'capabilities' => [
                'manage_terms' => 'manage_categories',
                'edit_terms' => 'manage_categories',
                'delete_terms' => 'manage_categories',
                'assign_terms' => 'edit_posts',
            ],
        ];

        register_taxonomy('askro_question_tag', ['askro_question'], $args);
    }

    /**
     * Add meta boxes
     *
     * @since 1.0.0
     */
    public function add_meta_boxes() {
        // Question meta boxes
        add_meta_box(
            'askro_question_details',
            __('تفاصيل السؤال', 'askro'),
            [$this, 'question_details_meta_box'],
            'askro_question',
            'side',
            'high'
        );

        add_meta_box(
            'askro_question_stats',
            __('إحصائيات السؤال', 'askro'),
            [$this, 'question_stats_meta_box'],
            'askro_question',
            'side',
            'default'
        );

        // Answer meta boxes
        add_meta_box(
            'askro_answer_details',
            __('تفاصيل الإجابة', 'askro'),
            [$this, 'answer_details_meta_box'],
            'askro_answer',
            'side',
            'high'
        );

        add_meta_box(
            'askro_answer_stats',
            __('إحصائيات الإجابة', 'askro'),
            [$this, 'answer_stats_meta_box'],
            'askro_answer',
            'side',
            'default'
        );
    }

    /**
     * Question details meta box
     *
     * @param WP_Post $post Post object
     * @since 1.0.0
     */
    public function question_details_meta_box($post) {
        wp_nonce_field('askro_question_meta', 'askro_question_meta_nonce');

        $is_solved = get_post_meta($post->ID, '_askro_is_solved', true);
        $best_answer = get_post_meta($post->ID, '_askro_best_answer', true);
        $difficulty = get_post_meta($post->ID, '_askro_difficulty', true);
        $priority = get_post_meta($post->ID, '_askro_priority', true);
        $featured = get_post_meta($post->ID, '_askro_featured', true);

        ?>
        <table class="form-table">
            <tr>
                <th><label for="askro_is_solved"><?php _e('تم الحل', 'askro'); ?></label></th>
                <td>
                    <input type="checkbox" id="askro_is_solved" name="askro_is_solved" value="1" <?php checked($is_solved, '1'); ?> />
                    <p class="description"><?php _e('هل تم حل هذا السؤال؟', 'askro'); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="askro_best_answer"><?php _e('أفضل إجابة', 'askro'); ?></label></th>
                <td>
                    <?php
                    $answers = get_posts([
                        'post_type' => 'askro_answer',
                        'post_parent' => $post->ID,
                        'post_status' => 'publish',
                        'numberposts' => -1
                    ]);
                    ?>
                    <select id="askro_best_answer" name="askro_best_answer">
                        <option value=""><?php _e('-- اختر أفضل إجابة --', 'askro'); ?></option>
                        <?php foreach ($answers as $answer): ?>
                            <option value="<?php echo $answer->ID; ?>" <?php selected($best_answer, $answer->ID); ?>>
                                <?php echo sprintf(__('إجابة #%d - %s', 'askro'), $answer->ID, get_the_author_meta('display_name', $answer->post_author)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="askro_difficulty"><?php _e('مستوى الصعوبة', 'askro'); ?></label></th>
                <td>
                    <select id="askro_difficulty" name="askro_difficulty">
                        <option value=""><?php _e('-- اختر المستوى --', 'askro'); ?></option>
                        <option value="beginner" <?php selected($difficulty, 'beginner'); ?>><?php _e('مبتدئ', 'askro'); ?></option>
                        <option value="intermediate" <?php selected($difficulty, 'intermediate'); ?>><?php _e('متوسط', 'askro'); ?></option>
                        <option value="advanced" <?php selected($difficulty, 'advanced'); ?>><?php _e('متقدم', 'askro'); ?></option>
                        <option value="expert" <?php selected($difficulty, 'expert'); ?>><?php _e('خبير', 'askro'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="askro_priority"><?php _e('الأولوية', 'askro'); ?></label></th>
                <td>
                    <select id="askro_priority" name="askro_priority">
                        <option value="normal" <?php selected($priority, 'normal'); ?>><?php _e('عادي', 'askro'); ?></option>
                        <option value="high" <?php selected($priority, 'high'); ?>><?php _e('عالي', 'askro'); ?></option>
                        <option value="urgent" <?php selected($priority, 'urgent'); ?>><?php _e('عاجل', 'askro'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="askro_featured"><?php _e('سؤال مميز', 'askro'); ?></label></th>
                <td>
                    <input type="checkbox" id="askro_featured" name="askro_featured" value="1" <?php checked($featured, '1'); ?> />
                    <p class="description"><?php _e('عرض هذا السؤال في القسم المميز', 'askro'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Question stats meta box
     *
     * @param WP_Post $post Post object
     * @since 1.0.0
     */
    public function question_stats_meta_box($post) {
        $views = askro_get_post_views($post->ID);
        $votes = askro_get_post_votes($post->ID);
        $answers_count = askro_get_question_answers_count($post->ID);

        ?>
        <table class="form-table">
            <tr>
                <th><?php _e('المشاهدات', 'askro'); ?></th>
                <td><?php echo askro_format_number($views); ?></td>
            </tr>
            <tr>
                <th><?php _e('التصويتات', 'askro'); ?></th>
                <td><?php echo askro_format_number($votes['total']); ?></td>
            </tr>
            <tr>
                <th><?php _e('الإجابات', 'askro'); ?></th>
                <td><?php echo askro_format_number($answers_count); ?></td>
            </tr>
            <tr>
                <th><?php _e('تاريخ الإنشاء', 'askro'); ?></th>
                <td><?php echo get_the_date('Y/m/d H:i', $post); ?></td>
            </tr>
            <tr>
                <th><?php _e('آخر تحديث', 'askro'); ?></th>
                <td><?php echo get_the_modified_date('Y/m/d H:i', $post); ?></td>
            </tr>
        </table>
        <?php
    }

    /**
     * Answer details meta box
     *
     * @param WP_Post $post Post object
     * @since 1.0.0
     */
    public function answer_details_meta_box($post) {
        wp_nonce_field('askro_answer_meta', 'askro_answer_meta_nonce');

        $is_best = get_post_meta($post->ID, '_askro_is_best_answer', true);
        $question_id = $post->post_parent;

        ?>
        <table class="form-table">
            <tr>
                <th><?php _e('السؤال', 'askro'); ?></th>
                <td>
                    <?php if ($question_id): ?>
                        <a href="<?php echo get_edit_post_link($question_id); ?>">
                            <?php echo get_the_title($question_id); ?>
                        </a>
                    <?php else: ?>
                        <?php _e('غير محدد', 'askro'); ?>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="askro_is_best_answer"><?php _e('أفضل إجابة', 'askro'); ?></label></th>
                <td>
                    <input type="checkbox" id="askro_is_best_answer" name="askro_is_best_answer" value="1" <?php checked($is_best, '1'); ?> />
                    <p class="description"><?php _e('هل هذه أفضل إجابة للسؤال؟', 'askro'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Answer stats meta box
     *
     * @param WP_Post $post Post object
     * @since 1.0.0
     */
    public function answer_stats_meta_box($post) {
        $votes = askro_get_post_votes($post->ID);

        ?>
        <table class="form-table">
            <tr>
                <th><?php _e('التصويتات', 'askro'); ?></th>
                <td><?php echo askro_format_number($votes['total']); ?></td>
            </tr>
            <tr>
                <th><?php _e('تاريخ الإنشاء', 'askro'); ?></th>
                <td><?php echo get_the_date('Y/m/d H:i', $post); ?></td>
            </tr>
            <tr>
                <th><?php _e('آخر تحديث', 'askro'); ?></th>
                <td><?php echo get_the_modified_date('Y/m/d H:i', $post); ?></td>
            </tr>
        </table>
        <?php
    }

    /**
     * Save meta boxes
     *
     * @param int $post_id Post ID
     * @since 1.0.0
     */
    public function save_meta_boxes($post_id) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $post_type = get_post_type($post_id);

        if ($post_type === 'askro_question') {
            $this->save_question_meta($post_id);
        } elseif ($post_type === 'askro_answer') {
            $this->save_answer_meta($post_id);
        }
    }

    /**
     * Save question meta
     *
     * @param int $post_id Post ID
     * @since 1.0.0
     */
    private function save_question_meta($post_id) {
        // Verify nonce
        if (!isset($_POST['askro_question_meta_nonce']) || 
            !wp_verify_nonce($_POST['askro_question_meta_nonce'], 'askro_question_meta')) {
            return;
        }

        // Save meta fields
        $fields = [
            'askro_is_solved' => 'checkbox',
            'askro_best_answer' => 'text',
            'askro_difficulty' => 'text',
            'askro_priority' => 'text',
            'askro_featured' => 'checkbox'
        ];

        foreach ($fields as $field => $type) {
            $meta_key = '_' . $field;
            
            if ($type === 'checkbox') {
                $value = isset($_POST[$field]) ? '1' : '0';
            } else {
                $value = sanitize_text_field($_POST[$field] ?? '');
            }

            update_post_meta($post_id, $meta_key, $value);
        }

        // Handle best answer selection
        if (!empty($_POST['askro_best_answer'])) {
            $best_answer_id = intval($_POST['askro_best_answer']);
            
            // Remove best answer flag from all other answers
            $answers = get_posts([
                'post_type' => 'askro_answer',
                'post_parent' => $post_id,
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields' => 'ids'
            ]);

            foreach ($answers as $answer_id) {
                update_post_meta($answer_id, '_askro_is_best_answer', '0');
            }

            // Set the selected answer as best
            update_post_meta($best_answer_id, '_askro_is_best_answer', '1');
            
            // Mark question as solved
            update_post_meta($post_id, '_askro_is_solved', '1');
        }
    }

    /**
     * Save answer meta
     *
     * @param int $post_id Post ID
     * @since 1.0.0
     */
    private function save_answer_meta($post_id) {
        // Verify nonce
        if (!isset($_POST['askro_answer_meta_nonce']) || 
            !wp_verify_nonce($_POST['askro_answer_meta_nonce'], 'askro_answer_meta')) {
            return;
        }

        $is_best = isset($_POST['askro_is_best_answer']) ? '1' : '0';
        update_post_meta($post_id, '_askro_is_best_answer', $is_best);

        // If this is marked as best answer, update the question
        if ($is_best === '1') {
            $question_id = wp_get_post_parent_id($post_id);
            if ($question_id) {
                // Remove best answer flag from other answers
                $answers = get_posts([
                    'post_type' => 'askro_answer',
                    'post_parent' => $question_id,
                    'post_status' => 'publish',
                    'numberposts' => -1,
                    'fields' => 'ids'
                ]);

                foreach ($answers as $answer_id) {
                    if ($answer_id != $post_id) {
                        update_post_meta($answer_id, '_askro_is_best_answer', '0');
                    }
                }

                // Update question meta
                update_post_meta($question_id, '_askro_best_answer', $post_id);
                update_post_meta($question_id, '_askro_is_solved', '1');
            }
        }
    }

    /**
     * Customize question columns
     *
     * @param array $columns Columns
     * @return array Modified columns
     * @since 1.0.0
     */
    public function question_columns($columns) {
        $new_columns = [];
        
        foreach ($columns as $key => $title) {
            $new_columns[$key] = $title;
            
            if ($key === 'title') {
                $new_columns['askro_status'] = __('الحالة', 'askro');
                $new_columns['askro_answers'] = __('الإجابات', 'askro');
                $new_columns['askro_votes'] = __('التصويتات', 'askro');
                $new_columns['askro_views'] = __('المشاهدات', 'askro');
            }
        }
        
        return $new_columns;
    }

    /**
     * Customize answer columns
     *
     * @param array $columns Columns
     * @return array Modified columns
     * @since 1.0.0
     */
    public function answer_columns($columns) {
        $new_columns = [];
        
        foreach ($columns as $key => $title) {
            if ($key === 'title') {
                $new_columns['askro_question'] = __('السؤال', 'askro');
                $new_columns['askro_content'] = __('المحتوى', 'askro');
            } else {
                $new_columns[$key] = $title;
            }
            
            if ($key === 'author') {
                $new_columns['askro_best'] = __('أفضل إجابة', 'askro');
                $new_columns['askro_votes'] = __('التصويتات', 'askro');
            }
        }
        
        return $new_columns;
    }

    /**
     * Question column content
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     * @since 1.0.0
     */
    public function question_column_content($column, $post_id) {
        switch ($column) {
            case 'askro_status':
                $is_solved = get_post_meta($post_id, '_askro_is_solved', true);
                if ($is_solved) {
                    echo '<span class="askro-status solved">' . __('تم الحل', 'askro') . '</span>';
                } else {
                    echo '<span class="askro-status unsolved">' . __('لم يتم الحل', 'askro') . '</span>';
                }
                break;
                
            case 'askro_answers':
                $count = askro_get_question_answers_count($post_id);
                echo askro_format_number($count);
                break;
                
            case 'askro_votes':
                $votes = askro_get_post_votes($post_id);
                echo askro_format_number($votes['total']);
                break;
                
            case 'askro_views':
                $views = askro_get_post_views($post_id);
                echo askro_format_number($views);
                break;
        }
    }

    /**
     * Answer column content
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     * @since 1.0.0
     */
    public function answer_column_content($column, $post_id) {
        switch ($column) {
            case 'askro_question':
                $question_id = wp_get_post_parent_id($post_id);
                if ($question_id) {
                    echo '<a href="' . get_edit_post_link($question_id) . '">' . get_the_title($question_id) . '</a>';
                } else {
                    echo __('غير محدد', 'askro');
                }
                break;
                
            case 'askro_content':
                $content = get_post_field('post_content', $post_id);
                echo wp_trim_words($content, 15);
                break;
                
            case 'askro_best':
                $is_best = get_post_meta($post_id, '_askro_is_best_answer', true);
                if ($is_best) {
                    echo '<span class="askro-best-answer">' . __('نعم', 'askro') . '</span>';
                } else {
                    echo __('لا', 'askro');
                }
                break;
                
            case 'askro_votes':
                $votes = askro_get_post_votes($post_id);
                echo askro_format_number($votes['total']);
                break;
        }
    }

    /**
     * Custom post updated messages
     *
     * @param array $messages Messages
     * @return array Modified messages
     * @since 1.0.0
     */
    public function updated_messages($messages) {
        global $post;

        $messages['askro_question'] = [
            0  => '', // Unused. Messages start at index 1.
            1  => sprintf(__('تم تحديث السؤال. <a href="%s">عرض السؤال</a>', 'askro'), esc_url(get_permalink($post->ID))),
            2  => __('تم تحديث الحقل المخصص.', 'askro'),
            3  => __('تم حذف الحقل المخصص.', 'askro'),
            4  => __('تم تحديث السؤال.', 'askro'),
            5  => isset($_GET['revision']) ? sprintf(__('تم استرداد السؤال من المراجعة من %s', 'askro'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
            6  => sprintf(__('تم نشر السؤال. <a href="%s">عرض السؤال</a>', 'askro'), esc_url(get_permalink($post->ID))),
            7  => __('تم حفظ السؤال.', 'askro'),
            8  => sprintf(__('تم إرسال السؤال. <a target="_blank" href="%s">معاينة السؤال</a>', 'askro'), esc_url(add_query_arg('preview', 'true', get_permalink($post->ID)))),
            9  => sprintf(__('تم جدولة السؤال لـ: <strong>%1$s</strong>. <a target="_blank" href="%2$s">معاينة السؤال</a>', 'askro'), date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date)), esc_url(get_permalink($post->ID))),
            10 => sprintf(__('تم تحديث مسودة السؤال. <a target="_blank" href="%s">معاينة السؤال</a>', 'askro'), esc_url(add_query_arg('preview', 'true', get_permalink($post->ID)))),
        ];

        $messages['askro_answer'] = [
            0  => '', // Unused. Messages start at index 1.
            1  => sprintf(__('تم تحديث الإجابة. <a href="%s">عرض الإجابة</a>', 'askro'), esc_url(get_permalink($post->ID))),
            2  => __('تم تحديث الحقل المخصص.', 'askro'),
            3  => __('تم حذف الحقل المخصص.', 'askro'),
            4  => __('تم تحديث الإجابة.', 'askro'),
            5  => isset($_GET['revision']) ? sprintf(__('تم استرداد الإجابة من المراجعة من %s', 'askro'), wp_post_revision_title((int) $_GET['revision'], false)) : false,
            6  => sprintf(__('تم نشر الإجابة. <a href="%s">عرض الإجابة</a>', 'askro'), esc_url(get_permalink($post->ID))),
            7  => __('تم حفظ الإجابة.', 'askro'),
            8  => sprintf(__('تم إرسال الإجابة. <a target="_blank" href="%s">معاينة الإجابة</a>', 'askro'), esc_url(add_query_arg('preview', 'true', get_permalink($post->ID)))),
            9  => sprintf(__('تم جدولة الإجابة لـ: <strong>%1$s</strong>. <a target="_blank" href="%2$s">معاينة الإجابة</a>', 'askro'), date_i18n(__('M j, Y @ G:i'), strtotime($post->post_date)), esc_url(get_permalink($post->ID))),
            10 => sprintf(__('تم تحديث مسودة الإجابة. <a target="_blank" href="%s">معاينة الإجابة</a>', 'askro'), esc_url(add_query_arg('preview', 'true', get_permalink($post->ID)))),
        ];

        return $messages;
    }

    /**
     * Modify main query for custom post types
     *
     * @param WP_Query $query Query object
     * @since 1.0.0
     */
    public function modify_main_query($query) {
        if (!is_admin() && $query->is_main_query()) {
            // Include questions in search results
            if ($query->is_search()) {
                $post_types = $query->get('post_type');
                if (!$post_types) {
                    $post_types = ['post', 'page'];
                }
                if (!is_array($post_types)) {
                    $post_types = [$post_types];
                }
                $post_types[] = 'askro_question';
                $query->set('post_type', $post_types);
            }
        }
    }

    /**
     * Load custom templates for Askro post types
     *
     * @param string $template Template path
     * @return string Modified template path
     * @since 1.0.0
     */
    public function load_custom_templates($template) {
        // Let the theme handle template loading for better compatibility
        // We'll inject content via hooks instead
        return $template;
    }

    /**
     * Load single question template
     *
     * @param string $template Template path
     * @return string Modified template path
     * @since 1.0.0
     */
    public function load_single_question_template($template) {
        // Let the theme handle template loading for better compatibility
        // We'll inject content via hooks instead
        return $template;
    }

    /**
     * Enqueue assets for single question page
     *
     * @since 1.0.0
     */
    public function enqueue_single_question_assets() {
        if (is_singular('askro_question')) {
            wp_enqueue_script('jquery');
            
            // Enqueue plugin assets if available
            if (function_exists('askro')) {
                $assets = askro()->get_component('assets');
                if ($assets) {
                    $assets->enqueue_frontend_assets();
                }
            }
        }
    }

    /**
     * Replace question content with plugin template
     *
     * @param string $content Post content
     * @return string Modified content
     * @since 1.0.0
     */
    public function replace_question_content($content) {
        global $post;
        static $processing = false;
        
        // Prevent infinite recursion
        if ($processing) {
            return $content;
        }
        
        // Only modify content on single question pages
        if (is_singular('askro_question') && in_the_loop() && is_main_query() && $post && $post->post_type === 'askro_question') {
            $processing = true;
            
            // Use the plugin's single question shortcode
            $plugin_content = do_shortcode('[askro_single_question id="' . $post->ID . '"]');
            
            $processing = false;
            
            if (!empty($plugin_content)) {
                return $plugin_content;
            }
        }
        
        return $content;
    }

    /**
     * Add single question content to the page
     *
     * @since 1.0.0
     */
    public function add_single_question_content() {
        global $post;
        if ($post && $post->post_type === 'askro_question') {
            // إضافة محتوى السؤال إلى الصفحة
            echo '<div id="askme-single-question-wrapper">';
            echo do_shortcode('[askro_single_question id="' . $post->ID . '"]');
            echo '</div>';
            
            // إضافة JavaScript لإخفاء المحتوى الافتراضي
            ?>
            <script>
            jQuery(document).ready(function($) {
                // إخفاء المحتوى الافتراضي للثيم
                $('.entry-content, .post-content, .content-area').hide();
                
                // إظهار محتوى السؤال
                $('#askme-single-question-wrapper').show();
            });
            </script>
            <?php
        }
    }

    /**
     * Hide default post type menus from admin menu
     *
     * @since 1.0.0
     */
    public function hide_default_post_type_menus() {
        // إخفاء قوائم الأسئلة والإجابات الافتراضية من لوحة التحكم
        remove_menu_page('edit.php?post_type=askro_question');
        remove_menu_page('edit.php?post_type=askro_answer');
    }
}


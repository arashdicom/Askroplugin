<?php
/**
 * Admin Class
 *
 * @package    Askro
 * @subpackage Admin
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
 * Askro Admin Class
 *
 * Handles all admin functionality
 *
 * @since 1.0.0
 */
class Askro_Admin implements Askro_Admin_Handler_Interface {

    /**
     * Initialize the admin component
     *
     * @since 1.0.0
     */
    public function init() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_ajax_askro_admin_action', [$this, 'handle_admin_ajax']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_notices', [$this, 'admin_notices']);
        
        // Add custom columns to post types
        add_filter('manage_askro_question_posts_columns', [$this, 'question_columns']);
        add_action('manage_askro_question_posts_custom_column', [$this, 'question_column_content'], 10, 2);
        
        // Add meta boxes
        add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_boxes']);
        
        // Add tools AJAX handlers
        add_action('wp_ajax_askro_fix_answer_links', [$this, 'handle_fix_answer_links']);
        add_action('wp_ajax_askro_create_test_data', [$this, 'handle_create_test_data']);
        add_action('wp_ajax_askro_get_db_stats', [$this, 'handle_get_db_stats']);
        add_action('wp_ajax_askro_clear_cache', [$this, 'handle_clear_cache']);
        add_action('wp_ajax_askro_toggle_debug', [$this, 'handle_toggle_debug']);
        add_action('wp_ajax_askro_reset_user_points', [$this, 'handle_reset_user_points']);
        add_action('wp_ajax_askro_bulk_award_points', [$this, 'handle_bulk_award_points']);
        add_action('wp_ajax_askro_bulk_approve_content', [$this, 'handle_bulk_approve_content']);
        add_action('wp_ajax_askro_export_content', [$this, 'handle_export_content']);
    }

    /**
     * Add admin menu pages
     *
     * @since 1.0.0
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Askro', 'askro'),
            __('Askro', 'askro'),
            'manage_options',
            'askro',
            [$this, 'dashboard_page'],
            'dashicons-format-chat',
            30
        );

        // Dashboard submenu
        add_submenu_page(
            'askro',
            __('لوحة التحكم', 'askro'),
            __('لوحة التحكم', 'askro'),
            'manage_options',
            'askro',
            [$this, 'dashboard_page']
        );

        // Questions submenu
        add_submenu_page(
            'askro',
            __('الأسئلة', 'askro'),
            __('الأسئلة', 'askro'),
            'manage_options',
            'askro-questions',
            [$this, 'questions_page']
        );

        // Answers submenu
        add_submenu_page(
            'askro',
            __('الإجابات', 'askro'),
            __('الإجابات', 'askro'),
            'manage_options',
            'askro-answers',
            [$this, 'answers_page']
        );

        // Users submenu
        add_submenu_page(
            'askro',
            __('المستخدمون', 'askro'),
            __('المستخدمون', 'askro'),
            'manage_options',
            'askro-users',
            [$this, 'users_page']
        );

        // Voting submenu
        add_submenu_page(
            'askro',
            __('نظام التصويت', 'askro'),
            __('نظام التصويت', 'askro'),
            'manage_options',
            'askro-voting',
            [$this, 'voting_page']
        );

        // Points submenu
        add_submenu_page(
            'askro',
            __('النقاط والشارات', 'askro'),
            __('النقاط والشارات', 'askro'),
            'manage_options',
            'askro-points',
            [$this, 'points_page']
        );

        // Analytics submenu
        add_submenu_page(
            'askro',
            __('التحليلات', 'askro'),
            __('التحليلات', 'askro'),
            'manage_options',
            'askro-analytics',
            [$this, 'analytics_page']
        );

        // Settings submenu
        add_submenu_page(
            'askro',
            __('الإعدادات', 'askro'),
            __('الإعدادات', 'askro'),
            'manage_options',
            'askro-settings',
            [$this, 'settings_page']
        );

        // Tools submenu
        add_submenu_page(
            'askro',
            __('الأدوات', 'askro'),
            __('الأدوات', 'askro'),
            'manage_options',
            'askro-tools',
            [$this, 'tools_page']
        );

        // Database Check submenu
        add_submenu_page(
            'askro',
            __('فحص قاعدة البيانات', 'askro'),
            __('فحص قاعدة البيانات', 'askro'),
            'manage_options',
            'askro-db-check',
            [$this, 'db_check_page']
        );
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook
     * @since 1.0.0
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on Askro admin pages
        if (strpos($hook, 'askro') === false) {
            return;
        }

        // Enqueue admin CSS
        wp_enqueue_style(
            'askro-admin',
            ASKRO_PLUGIN_URL . 'assets/css/admin.css',
            [],
            ASKRO_VERSION
        );

        // Enqueue admin JS
        wp_enqueue_script(
            'askro-admin',
            ASKRO_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            ASKRO_VERSION,
            true
        );

        // Enqueue Chart.js for analytics
        wp_enqueue_script(
            'askro-chart',
            ASKRO_PLUGIN_URL . 'assets/js/vendor/chart/chart.umd.js',
            [],
            ASKRO_VERSION,
            true
        );

        // Localize script
        wp_localize_script('askro-admin', 'askroAdmin', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('askro_admin_nonce'),
            'strings' => [
                'loading' => __('جاري التحميل...', 'askro'),
                'error' => __('حدث خطأ، يرجى المحاولة مرة أخرى.', 'askro'),
                'success' => __('تم بنجاح!', 'askro'),
                'confirm' => __('هل أنت متأكد؟', 'askro'),
                'delete_confirm' => __('هل أنت متأكد من الحذف؟ لا يمكن التراجع عن هذا الإجراء.', 'askro'),
            ]
        ]);
    }

    /**
     * Dashboard page
     *
     * @since 1.0.0
     */
    public function dashboard_page() {
        $stats = $this->get_dashboard_stats();
        include ASKRO_ADMIN_DIR . 'views/dashboard.php';
    }

    /**
     * Questions page
     *
     * @since 1.0.0
     */
    public function questions_page() {
        $questions = $this->get_questions_data();
        include ASKRO_ADMIN_DIR . 'views/questions.php';
    }

    /**
     * Answers page
     *
     * @since 1.0.0
     */
    public function answers_page() {
        $answers = $this->get_answers_data();
        include ASKRO_ADMIN_DIR . 'views/answers.php';
    }

    /**
     * Users page
     *
     * @since 1.0.0
     */
    public function users_page() {
        $users = $this->get_users_data();
        include ASKRO_ADMIN_DIR . 'views/users.php';
    }

    /**
     * Voting page
     *
     * @since 1.0.0
     */
    public function voting_page() {
        $voting_data = $this->get_voting_data();
        include ASKRO_ADMIN_DIR . 'views/voting.php';
    }

    /**
     * Points page
     *
     * @since 1.0.0
     */
    public function points_page() {
        $points_data = $this->get_points_data();
        include ASKRO_ADMIN_DIR . 'views/points.php';
    }

    /**
     * Analytics page
     *
     * @since 1.0.0
     */
    public function analytics_page() {
        $analytics_data = $this->get_analytics_data();
        include ASKRO_ADMIN_DIR . 'views/analytics.php';
    }

    /**
     * Settings page
     *
     * @since 1.0.0
     */
    public function settings_page() {
        include ASKRO_ADMIN_DIR . 'views/settings.php';
    }

    /**
     * Tools page
     *
     * @since 1.0.0
     */
    public function tools_page() {
        include ASKRO_ADMIN_DIR . 'views/tools.php';
    }

    /**
     * Get dashboard statistics
     *
     * @return array Dashboard stats
     * @since 1.0.0
     */
    public function get_dashboard_stats() {
        global $wpdb;

        $stats = [];

        // Questions count
        $stats['questions'] = [
            'total' => wp_count_posts('askro_question')->publish,
            'today' => $this->get_posts_count_today('askro_question'),
            'this_week' => $this->get_posts_count_this_week('askro_question'),
            'this_month' => $this->get_posts_count_this_month('askro_question')
        ];

        // Answers count
        $stats['answers'] = [
            'total' => wp_count_posts('askro_answer')->publish,
            'today' => $this->get_posts_count_today('askro_answer'),
            'this_week' => $this->get_posts_count_this_week('askro_answer'),
            'this_month' => $this->get_posts_count_this_month('askro_answer')
        ];

        // Users count
        $stats['users'] = [
            'total' => count_users()['total_users'],
            'active_today' => $this->get_active_users_today(),
            'active_this_week' => $this->get_active_users_this_week(),
            'active_this_month' => $this->get_active_users_this_month()
        ];

        // Votes count
        $votes_table = $wpdb->prefix . 'askro_user_votes';
        $stats['votes'] = [
            'total' => $wpdb->get_var("SELECT COUNT(*) FROM {$votes_table}"),
            'today' => $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$votes_table} WHERE DATE(voted_at) = %s",
                current_time('Y-m-d')
            )),
            'positive' => $wpdb->get_var("SELECT COUNT(*) FROM {$votes_table} WHERE vote_strength > 0"),
            'negative' => $wpdb->get_var("SELECT COUNT(*) FROM {$votes_table} WHERE vote_strength < 0")
        ];

        // Points statistics
        $points_table = $wpdb->prefix . 'askro_points_log';
        $stats['points'] = [
            'total_awarded' => $wpdb->get_var("SELECT SUM(points_change) FROM {$points_table} WHERE points_change > 0"),
            'total_deducted' => $wpdb->get_var("SELECT SUM(ABS(points_change)) FROM {$points_table} WHERE points_change < 0"),
            'top_user' => $this->get_top_user_by_points(),
            'average_per_user' => $this->get_average_points_per_user()
        ];

        return $stats;
    }

    /**
     * Get posts count for today
     *
     * @param string $post_type Post type
     * @return int Posts count
     * @since 1.0.0
     */
    private function get_posts_count_today($post_type) {
        $args = [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'date_query' => [
                [
                    'after' => 'today',
                    'inclusive' => true
                ]
            ],
            'fields' => 'ids'
        ];

        $query = new WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Get posts count for this week
     *
     * @param string $post_type Post type
     * @return int Posts count
     * @since 1.0.0
     */
    private function get_posts_count_this_week($post_type) {
        $args = [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'date_query' => [
                [
                    'after' => '1 week ago',
                    'inclusive' => true
                ]
            ],
            'fields' => 'ids'
        ];

        $query = new WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Get posts count for this month
     *
     * @param string $post_type Post type
     * @return int Posts count
     * @since 1.0.0
     */
    private function get_posts_count_this_month($post_type) {
        $args = [
            'post_type' => $post_type,
            'post_status' => 'publish',
            'date_query' => [
                [
                    'after' => '1 month ago',
                    'inclusive' => true
                ]
            ],
            'fields' => 'ids'
        ];

        $query = new WP_Query($args);
        return $query->found_posts;
    }

    /**
     * Get active users today
     *
     * @return int Active users count
     * @since 1.0.0
     */
    private function get_active_users_today() {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'askro_analytics';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$analytics_table} 
             WHERE DATE(created_at) = %s AND user_id > 0",
            current_time('Y-m-d')
        ));
    }

    /**
     * Get active users this week
     *
     * @return int Active users count
     * @since 1.0.0
     */
    private function get_active_users_this_week() {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'askro_analytics';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$analytics_table} 
             WHERE created_at >= %s AND user_id > 0",
            date('Y-m-d H:i:s', strtotime('-1 week'))
        ));
    }

    /**
     * Get active users this month
     *
     * @return int Active users count
     * @since 1.0.0
     */
    private function get_active_users_this_month() {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'askro_analytics';
        return $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT user_id) FROM {$analytics_table} 
             WHERE created_at >= %s AND user_id > 0",
            date('Y-m-d H:i:s', strtotime('-1 month'))
        ));
    }

    /**
     * Get top user by points
     *
     * @return object|null Top user data
     * @since 1.0.0
     */
    private function get_top_user_by_points() {
        global $wpdb;
        
        $points_table = $wpdb->prefix . 'askro_points_log';
        $result = $wpdb->get_row(
            "SELECT user_id, SUM(points_change) as total_points 
             FROM {$points_table} 
             GROUP BY user_id 
             ORDER BY total_points DESC 
             LIMIT 1"
        );

        if ($result) {
            $user = get_userdata($result->user_id);
            return [
                'user' => $user,
                'points' => $result->total_points
            ];
        }

        return null;
    }

    /**
     * Get average points per user
     *
     * @return float Average points
     * @since 1.0.0
     */
    private function get_average_points_per_user() {
        global $wpdb;
        
        $points_table = $wpdb->prefix . 'askro_points_log';
        return $wpdb->get_var(
                         "SELECT AVG(user_total.total_points) 
             FROM (
                 SELECT user_id, SUM(points_change) as total_points 
                 FROM {$points_table} 
                 GROUP BY user_id
             ) as user_total"
        ) ?: 0;
    }

    /**
     * Get questions data for admin page
     *
     * @return array Questions data
     * @since 1.0.0
     */
    public function get_questions_data() {
        $args = [
            'post_type' => 'askro_question',
            'post_status' => ['publish', 'pending', 'draft'],
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC'
        ];

        $questions = get_posts($args);
        $data = [];

        foreach ($questions as $question) {
            // Get meta data
            $is_featured = get_post_meta($question->ID, '_askro_is_featured', true);
            $is_closed = get_post_meta($question->ID, '_askro_is_closed', true);
            $status = get_post_meta($question->ID, '_askro_status', true);
            $is_solved = ($status === 'solved');
            
            $data[] = [
                'id' => $question->ID,
                'title' => $question->post_title,
                'author' => get_userdata($question->post_author),
                'status' => $question->post_status,
                'date' => $question->post_date,
                'answers_count' => askro_get_answers_count($question->ID),
                'votes_count' => askro_get_total_votes($question->ID),
                'views_count' => get_post_meta($question->ID, '_askro_views', true) ?: 0,
                'is_featured' => $is_featured,
                'is_closed' => $is_closed,
                'is_solved' => $is_solved
            ];
        }

        return $data;
    }

    /**
     * Get answers data for admin page
     *
     * @return array Answers data
     * @since 1.0.0
     */
    public function get_answers_data() {
        $args = [
            'post_type' => 'askro_answer',
            'post_status' => ['publish', 'pending', 'draft'],
            'posts_per_page' => 20,
            'orderby' => 'date',
            'order' => 'DESC'
        ];

        $answers = get_posts($args);
        $data = [];

        foreach ($answers as $answer) {
            $question_id = get_post_meta($answer->ID, '_askro_question_id', true);
            $question = get_post($question_id);

            // Get voting data
            $voting = new Askro_Voting();
            $vote_data = $voting->get_post_vote_counts($answer->ID);
            
            // Extract upvotes and downvotes from vote data
            $upvotes = 0;
            $downvotes = 0;
            
            if (isset($vote_data['upvote'])) {
                $upvotes = $vote_data['upvote']['count'];
            }
            if (isset($vote_data['downvote'])) {
                $downvotes = $vote_data['downvote']['count'];
            }
            
            $vote_score = $upvotes - $downvotes;

            // Get comments count
            global $wpdb;
            $comments_count = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}askro_comments WHERE answer_id = %d",
                $answer->ID
            )) ?: 0;

            // Get flagged status
            $is_flagged = get_post_meta($answer->ID, '_askro_is_flagged', true);

            $data[] = [
                'id' => $answer->ID,
                'content' => wp_trim_words($answer->post_content, 20),
                'author' => get_userdata($answer->post_author),
                'question' => $question ? $question->post_title : __('سؤال محذوف', 'askro'),
                'question_id' => $question_id,
                'status' => $answer->post_status,
                'date' => $answer->post_date,
                'votes_count' => askro_get_total_votes($answer->ID),
                'is_accepted' => get_post_meta($answer->ID, '_askro_is_accepted', true),
                'is_flagged' => $is_flagged,
                'upvotes' => $upvotes,
                'downvotes' => $downvotes,
                'vote_score' => $vote_score,
                'comments_count' => $comments_count
            ];
        }

        return $data;
    }

    /**
     * Get users data for admin page
     *
     * @return array Users data
     * @since 1.0.0
     */
    public function get_users_data() {
        $users = get_users([
            'number' => 20,
            'orderby' => 'registered',
            'order' => 'DESC'
        ]);

        $data = [];

        foreach ($users as $user) {
            $user_data = askro_get_user_data($user->ID);
            
            $data[] = [
                'id' => $user->ID,
                'display_name' => $user->display_name,
                'email' => $user->user_email,
                'registered' => $user->user_registered,
                'questions_count' => $user_data['questions_count'] ?? 0,
                'answers_count' => $user_data['answers_count'] ?? 0,
                'total_points' => $user_data['total_points'] ?? 0,
                'reputation' => $user_data['reputation'] ?? 0,
                'badges_count' => count($user_data['badges'] ?? []),
                'last_activity' => $user_data['last_activity'] ?? null
            ];
        }

        return $data;
    }

    /**
     * Get voting data for admin page
     *
     * @return array Voting data
     * @since 1.0.0
     */
    public function get_voting_data() {
        global $wpdb;
        
        $votes_table = $wpdb->prefix . 'askro_votes';
        
        // Get vote types statistics
        $vote_types = $wpdb->get_results(
            "SELECT vote_type, COUNT(*) as count, AVG(vote_strength) as avg_strength
             FROM {$votes_table} 
             GROUP BY vote_type 
             ORDER BY count DESC"
        );

        // Get recent votes
        $recent_votes = $wpdb->get_results(
            "SELECT v.*, p.post_title, u.display_name 
             FROM {$votes_table} v
             LEFT JOIN {$wpdb->posts} p ON v.post_id = p.ID
             LEFT JOIN {$wpdb->users} u ON v.user_id = u.ID
             ORDER BY v.created_at DESC
             LIMIT 20"
        );

        return [
            'vote_types' => $vote_types,
            'recent_votes' => $recent_votes
        ];
    }

    /**
     * Get points data for admin page
     *
     * @return array Points data
     * @since 1.0.0
     */
    public function get_points_data() {
        global $wpdb;
        
        $points_table = $wpdb->prefix . 'askro_points_log';
        $badges_table = $wpdb->prefix . 'askro_user_badges';
        
        // Get top users by points
        $top_users = $wpdb->get_results(
            "SELECT user_id, SUM(points_change) as total_points
             FROM {$points_table}
             GROUP BY user_id
             ORDER BY total_points DESC
             LIMIT 10"
        );

        // Get recent point transactions
        $recent_transactions = $wpdb->get_results(
            "SELECT p.*, u.display_name
             FROM {$points_table} p
             LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID
             ORDER BY p.created_at DESC
             LIMIT 20"
        );

        // Get badges statistics
        $badges_stats = $wpdb->get_results(
            "SELECT b.name as badge_name, COUNT(ub.id) as count
             FROM {$badges_table} b
             LEFT JOIN {$wpdb->prefix}askro_user_badges ub ON b.id = ub.badge_id
             GROUP BY b.id, b.name
             ORDER BY count DESC"
        );

        return [
            'top_users' => $top_users,
            'recent_transactions' => $recent_transactions,
            'badges_stats' => $badges_stats
        ];
    }

    /**
     * Get analytics data for admin page
     *
     * @return array Analytics data
     * @since 1.0.0
     */
    public function get_analytics_data() {
        global $wpdb;
        
        $analytics_table = $wpdb->prefix . 'askro_analytics';
        
        // Get daily activity for the last 30 days
        $daily_activity = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as date, 
                    COUNT(*) as total_events,
                    COUNT(DISTINCT user_id) as unique_users
             FROM {$analytics_table}
             WHERE created_at >= %s
             GROUP BY DATE(created_at)
             ORDER BY date DESC",
            date('Y-m-d H:i:s', strtotime('-30 days'))
        ));

        // Get event types statistics
        $event_types = $wpdb->get_results(
            "SELECT event_type, COUNT(*) as count
             FROM {$analytics_table}
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
             GROUP BY event_type
             ORDER BY count DESC"
        );

        // Get popular content
        $popular_questions = $wpdb->get_results($wpdb->prepare(
            "SELECT object_id as post_id, COUNT(*) as views
             FROM {$analytics_table}
             WHERE event_type = 'question_view' 
             AND created_at >= %s
             GROUP BY object_id
             ORDER BY views DESC
             LIMIT 10",
            date('Y-m-d H:i:s', strtotime('-7 days'))
        ));

        return [
            'daily_activity' => $daily_activity,
            'event_types' => $event_types,
            'popular_questions' => $popular_questions
        ];
    }

    /**
     * Handle admin AJAX requests
     *
     * @since 1.0.0
     */
    public function handle_admin_ajax() {
        check_ajax_referer('askro_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('غير مصرح لك بهذا الإجراء.', 'askro'));
        }

        $action = sanitize_text_field($_POST['admin_action'] ?? '');

        switch ($action) {
            case 'delete_question':
                $this->ajax_delete_question();
                break;
            case 'delete_answer':
                $this->ajax_delete_answer();
                break;
            case 'approve_content':
                $this->ajax_approve_content();
                break;
            case 'reject_content':
                $this->ajax_reject_content();
                break;
            case 'award_points':
                $this->ajax_award_points();
                break;
            case 'award_badge':
                $this->ajax_award_badge();
                break;
            default:
                wp_send_json_error(['message' => __('إجراء غير صحيح.', 'askro')]);
        }
    }

    /**
     * AJAX delete question
     *
     * @since 1.0.0
     */
    private function ajax_delete_question() {
        $question_id = intval($_POST['question_id'] ?? 0);
        
        if (!$question_id) {
            wp_send_json_error(['message' => __('معرف السؤال غير صحيح.', 'askro')]);
        }

        $result = wp_delete_post($question_id, true);
        
        if ($result) {
            wp_send_json_success(['message' => __('تم حذف السؤال بنجاح.', 'askro')]);
        } else {
            wp_send_json_error(['message' => __('فشل في حذف السؤال.', 'askro')]);
        }
    }

    /**
     * AJAX delete answer
     *
     * @since 1.0.0
     */
    private function ajax_delete_answer() {
        $answer_id = intval($_POST['answer_id'] ?? 0);
        
        if (!$answer_id) {
            wp_send_json_error(['message' => __('معرف الإجابة غير صحيح.', 'askro')]);
        }

        $result = wp_delete_post($answer_id, true);
        
        if ($result) {
            wp_send_json_success(['message' => __('تم حذف الإجابة بنجاح.', 'askro')]);
        } else {
            wp_send_json_error(['message' => __('فشل في حذف الإجابة.', 'askro')]);
        }
    }

    /**
     * AJAX approve content
     *
     * @since 1.0.0
     */
    private function ajax_approve_content() {
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('معرف المحتوى غير صحيح.', 'askro')]);
        }

        $result = wp_update_post([
            'ID' => $post_id,
            'post_status' => 'publish'
        ]);
        
        if ($result) {
            wp_send_json_success(['message' => __('تم الموافقة على المحتوى بنجاح.', 'askro')]);
        } else {
            wp_send_json_error(['message' => __('فشل في الموافقة على المحتوى.', 'askro')]);
        }
    }

    /**
     * AJAX reject content
     *
     * @since 1.0.0
     */
    private function ajax_reject_content() {
        $post_id = intval($_POST['post_id'] ?? 0);
        
        if (!$post_id) {
            wp_send_json_error(['message' => __('معرف المحتوى غير صحيح.', 'askro')]);
        }

        $result = wp_update_post([
            'ID' => $post_id,
            'post_status' => 'draft'
        ]);
        
        if ($result) {
            wp_send_json_success(['message' => __('تم رفض المحتوى بنجاح.', 'askro')]);
        } else {
            wp_send_json_error(['message' => __('فشل في رفض المحتوى.', 'askro')]);
        }
    }

    /**
     * AJAX award points
     *
     * @since 1.0.0
     */
    private function ajax_award_points() {
        $user_id = intval($_POST['user_id'] ?? 0);
        $points = intval($_POST['points'] ?? 0);
        $reason = sanitize_text_field($_POST['reason'] ?? '');
        
        if (!$user_id || !$points) {
            wp_send_json_error(['message' => __('بيانات غير صحيحة.', 'askro')]);
        }

        $result = askro_award_points($user_id, $points, $reason);
        
        if ($result) {
            wp_send_json_success(['message' => __('تم منح النقاط بنجاح.', 'askro')]);
        } else {
            wp_send_json_error(['message' => __('فشل في منح النقاط.', 'askro')]);
        }
    }

    /**
     * AJAX award badge
     *
     * @since 1.0.0
     */
    private function ajax_award_badge() {
        $user_id = intval($_POST['user_id'] ?? 0);
        $badge_category = sanitize_text_field($_POST['badge_type'] ?? '');
        
        if (!$user_id || !$badge_category) {
            wp_send_json_error(['message' => __('بيانات غير صحيحة.', 'askro')]);
        }

        $result = askro_award_badge($user_id, $badge_category);
        
        if ($result) {
            wp_send_json_success(['message' => __('تم منح الشارة بنجاح.', 'askro')]);
        } else {
            wp_send_json_error(['message' => __('فشل في منح الشارة.', 'askro')]);
        }
    }

    /**
     * Register settings
     *
     * @since 1.0.0
     */
    public function register_settings() {
        // Register page assignment settings
        register_setting('askro_settings', 'askro_archive_page_id');
        register_setting('askro_settings', 'askro_ask_question_page_id');
        register_setting('askro_settings', 'askro_user_profile_page_id');
        
        // Register access control settings
        register_setting('askro_settings', 'askro_min_role_ask_question');
        register_setting('askro_settings', 'askro_min_role_submit_answer');
        register_setting('askro_settings', 'askro_min_role_submit_comment');
        
        // Register general settings
        register_setting('askro_settings', 'askro_enable_pwa');
        register_setting('askro_settings', 'askro_app_name');
        register_setting('askro_settings', 'askro_app_icon');
    }

    /**
     * Admin notices
     *
     * @since 1.0.0
     */
    public function admin_notices() {
        // Check if database tables exist
        if (!askro_check_database_tables()) {
            echo '<div class="notice notice-error"><p>';
            echo __('جداول قاعدة البيانات الخاصة بـ Askro غير موجودة. يرجى إلغاء تفعيل البلجن وإعادة تفعيله.', 'askro');
            echo '</p></div>';
        }
    }

    /**
     * Add custom columns to questions list
     *
     * @param array $columns Existing columns
     * @return array Modified columns
     * @since 1.0.0
     */
    public function question_columns($columns) {
        $new_columns = [];
        
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            
            if ($key === 'title') {
                $new_columns['askro_answers'] = __('الإجابات', 'askro');
                $new_columns['askro_votes'] = __('التصويت', 'askro');
                $new_columns['askro_views'] = __('المشاهدات', 'askro');
            }
        }
        
        return $new_columns;
    }

    /**
     * Display custom column content for questions
     *
     * @param string $column Column name
     * @param int $post_id Post ID
     * @since 1.0.0
     */
    public function question_column_content($column, $post_id) {
        switch ($column) {
            case 'askro_answers':
                echo askro_get_answers_count($post_id);
                break;
            case 'askro_votes':
                echo askro_get_total_votes($post_id);
                break;
            case 'askro_views':
                echo get_post_meta($post_id, '_askro_views', true) ?: 0;
                break;
        }
    }

    /**
     * Add meta boxes
     *
     * @since 1.0.0
     */
    public function add_meta_boxes() {
        add_meta_box(
            'askro_question_meta',
            __('معلومات السؤال', 'askro'),
            [$this, 'question_meta_box'],
            'askro_question',
            'side',
            'high'
        );

        add_meta_box(
            'askro_answer_meta',
            __('معلومات الإجابة', 'askro'),
            [$this, 'answer_meta_box'],
            'askro_answer',
            'side',
            'high'
        );
    }

    /**
     * Question meta box content
     *
     * @param WP_Post $post Post object
     * @since 1.0.0
     */
    public function question_meta_box($post) {
        wp_nonce_field('askro_question_meta', 'askro_question_meta_nonce');
        
        $views = get_post_meta($post->ID, '_askro_views', true) ?: 0;
        $answers_count = askro_get_answers_count($post->ID);
        $votes_count = askro_get_total_votes($post->ID);
        $is_featured = get_post_meta($post->ID, '_askro_is_featured', true);
        $is_closed = get_post_meta($post->ID, '_askro_is_closed', true);
        
        echo '<p><strong>' . __('المشاهدات:', 'askro') . '</strong> ' . $views . '</p>';
        echo '<p><strong>' . __('الإجابات:', 'askro') . '</strong> ' . $answers_count . '</p>';
        echo '<p><strong>' . __('التصويت:', 'askro') . '</strong> ' . $votes_count . '</p>';
        
        echo '<p><label>';
        echo '<input type="checkbox" name="askro_is_featured" value="1" ' . checked($is_featured, 1, false) . '>';
        echo ' ' . __('سؤال مميز', 'askro');
        echo '</label></p>';
        
        echo '<p><label>';
        echo '<input type="checkbox" name="askro_is_closed" value="1" ' . checked($is_closed, 1, false) . '>';
        echo ' ' . __('سؤال مغلق', 'askro');
        echo '</label></p>';
    }

    /**
     * Answer meta box content
     *
     * @param WP_Post $post Post object
     * @since 1.0.0
     */
    public function answer_meta_box($post) {
        wp_nonce_field('askro_answer_meta', 'askro_answer_meta_nonce');
        
        $question_id = get_post_meta($post->ID, '_askro_question_id', true);
        $is_accepted = get_post_meta($post->ID, '_askro_is_accepted', true);
        $votes_count = askro_get_total_votes($post->ID);
        
        $questions = get_posts([
            'post_type' => 'askro_question',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);
        
        echo '<p><label for="askro_question_id"><strong>' . __('السؤال:', 'askro') . '</strong></label>';
        echo '<select name="askro_question_id" id="askro_question_id" style="width: 100%;">';
        echo '<option value="">' . __('اختر سؤال...', 'askro') . '</option>';
        
        foreach ($questions as $question) {
            echo '<option value="' . $question->ID . '" ' . selected($question_id, $question->ID, false) . '>';
            echo esc_html($question->post_title);
            echo '</option>';
        }
        
        echo '</select></p>';
        
        echo '<p><strong>' . __('التصويت:', 'askro') . '</strong> ' . $votes_count . '</p>';
        
        echo '<p><label>';
        echo '<input type="checkbox" name="askro_is_accepted" value="1" ' . checked($is_accepted, 1, false) . '>';
        echo ' ' . __('إجابة مقبولة', 'askro');
        echo '</label></p>';
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

        // Save question meta
        if (isset($_POST['askro_question_meta_nonce']) && 
            wp_verify_nonce($_POST['askro_question_meta_nonce'], 'askro_question_meta')) {
            
            update_post_meta($post_id, '_askro_is_featured', isset($_POST['askro_is_featured']) ? 1 : 0);
            update_post_meta($post_id, '_askro_is_closed', isset($_POST['askro_is_closed']) ? 1 : 0);
        }

        // Save answer meta
        if (isset($_POST['askro_answer_meta_nonce']) && 
            wp_verify_nonce($_POST['askro_answer_meta_nonce'], 'askro_answer_meta')) {
            
            if (isset($_POST['askro_question_id'])) {
                update_post_meta($post_id, '_askro_question_id', intval($_POST['askro_question_id']));
            }
            
            update_post_meta($post_id, '_askro_is_accepted', isset($_POST['askro_is_accepted']) ? 1 : 0);
        }
    }

    /**
     * Plugin activation hook
     *
     * @since 1.0.0
     */
    public static function activate() {
        // Set default options if they don't exist
        if (!get_option('askro_min_role_ask_question')) {
            update_option('askro_min_role_ask_question', 'subscriber');
        }
        if (!get_option('askro_min_role_submit_answer')) {
            update_option('askro_min_role_submit_answer', 'subscriber');
        }
        if (!get_option('askro_min_role_submit_comment')) {
            update_option('askro_min_role_submit_comment', 'subscriber');
        }
        
        // Create database tables
        askro_check_database_tables();
    }

    // ========================================
    // TOOLS AJAX HANDLERS
    // ========================================

    /**
     * Handle fix answer links
     *
     * @since 1.0.0
     */
    public function handle_fix_answer_links() {
        check_ajax_referer('askro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('غير مصرح لك بهذا الإجراء.', 'askro')]);
        }

        global $wpdb;
        $linked_count = 0;

        // Get all answers without question_id
        $answers = get_posts([
            'post_type' => 'askro_answer',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                [
                    'key' => '_askro_question_id',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);

        foreach ($answers as $answer) {
            // Try to find a question by title similarity
            $question = get_posts([
                'post_type' => 'askro_question',
                'posts_per_page' => 1,
                'post_status' => 'publish',
                's' => $answer->post_title
            ]);

            if (!empty($question)) {
                update_post_meta($answer->ID, '_askro_question_id', $question[0]->ID);
                $linked_count++;
            }
        }

        wp_send_json_success(['linked_count' => $linked_count]);
    }

    /**
     * Handle create test data
     *
     * @since 1.0.0
     */
    public function handle_create_test_data() {
        check_ajax_referer('askro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('غير مصرح لك بهذا الإجراء.', 'askro')]);
        }

        // Create test question
        $question_id = wp_insert_post([
            'post_title' => 'سؤال تجريبي للاختبار',
            'post_content' => 'هذا سؤال تجريبي لاختبار النظام.',
            'post_type' => 'askro_question',
            'post_status' => 'publish',
            'post_author' => get_current_user_id()
        ]);

        if ($question_id) {
            // Create test answer
            $answer_id = wp_insert_post([
                'post_title' => 'إجابة تجريبية للاختبار',
                'post_content' => 'هذه إجابة تجريبية لاختبار النظام.',
                'post_type' => 'askro_answer',
                'post_status' => 'publish',
                'post_author' => get_current_user_id()
            ]);

            if ($answer_id) {
                update_post_meta($answer_id, '_askro_question_id', $question_id);
            }
        }

        wp_send_json_success(['message' => __('تم إنشاء البيانات التجريبية بنجاح!', 'askro')]);
    }

    /**
     * Handle get database stats
     *
     * @since 1.0.0
     */
    public function handle_get_db_stats() {
        check_ajax_referer('askro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('غير مصرح لك بهذا الإجراء.', 'askro')]);
        }

        global $wpdb;

        $stats = [
            'questions' => wp_count_posts('askro_question')->publish,
            'answers' => wp_count_posts('askro_answer')->publish,
            'comments' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}askro_comments"),
            'votes' => $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}askro_user_votes"),
            'active_users' => $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}askro_points_log")
        ];

        wp_send_json_success($stats);
    }

    /**
     * Handle clear cache
     *
     * @since 1.0.0
     */
    public function handle_clear_cache() {
        check_ajax_referer('askro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('غير مصرح لك بهذا الإجراء.', 'askro')]);
        }

        // Clear WordPress cache
        wp_cache_flush();
        
        // Clear transients
        delete_transient('askro_stats_cache');
        delete_transient('askro_leaderboard_cache');

        wp_send_json_success(['message' => __('تم مسح التخزين المؤقت بنجاح!', 'askro')]);
    }

    /**
     * Handle toggle debug mode
     *
     * @since 1.0.0
     */
    public function handle_toggle_debug() {
        check_ajax_referer('askro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('غير مصرح لك بهذا الإجراء.', 'askro')]);
        }

        $debug_mode = isset($_POST['debug_mode']) ? (bool)$_POST['debug_mode'] : false;
        update_option('askro_debug_mode', $debug_mode);

        wp_send_json_success(['message' => __('تم تحديث وضع التصحيح بنجاح!', 'askro')]);
    }

    /**
     * Handle reset user points
     *
     * @since 1.0.0
     */
    public function handle_reset_user_points() {
        check_ajax_referer('askro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('غير مصرح لك بهذا الإجراء.', 'askro')]);
        }

        $user_id = intval($_POST['user_id'] ?? 0);
        
        if (!$user_id) {
            wp_send_json_error(['message' => __('معرف المستخدم غير صحيح.', 'askro')]);
        }

        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'askro_points_log', ['user_id' => $user_id]);

        wp_send_json_success(['message' => __('تم إعادة تعيين نقاط المستخدم بنجاح!', 'askro')]);
    }

    /**
     * Handle bulk award points
     *
     * @since 1.0.0
     */
    public function handle_bulk_award_points() {
        check_ajax_referer('askro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('غير مصرح لك بهذا الإجراء.', 'askro')]);
        }

        $points = intval($_POST['points'] ?? 0);
        
        if ($points <= 0) {
            wp_send_json_error(['message' => __('عدد النقاط غير صحيح.', 'askro')]);
        }

        $users = get_users(['role__not_in' => ['administrator']]);
        $users_count = 0;

        foreach ($users as $user) {
            askro_add_user_points($user->ID, $points, 'bulk_award', 'admin_action');
            $users_count++;
        }

        wp_send_json_success(['users_count' => $users_count]);
    }

    /**
     * Handle bulk approve content
     *
     * @since 1.0.0
     */
    public function handle_bulk_approve_content() {
        check_ajax_referer('askro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('غير مصرح لك بهذا الإجراء.', 'askro')]);
        }

        $pending_questions = get_posts([
            'post_type' => 'askro_question',
            'post_status' => 'pending',
            'posts_per_page' => -1
        ]);

        $pending_answers = get_posts([
            'post_type' => 'askro_answer',
            'post_status' => 'pending',
            'posts_per_page' => -1
        ]);

        $approved_count = 0;

        foreach ($pending_questions as $question) {
            wp_update_post([
                'ID' => $question->ID,
                'post_status' => 'publish'
            ]);
            $approved_count++;
        }

        foreach ($pending_answers as $answer) {
            wp_update_post([
                'ID' => $answer->ID,
                'post_status' => 'publish'
            ]);
            $approved_count++;
        }

        wp_send_json_success(['approved_count' => $approved_count]);
    }

    /**
     * Handle export content
     *
     * @since 1.0.0
     */
    public function handle_export_content() {
        check_ajax_referer('askro_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('غير مصرح لك بهذا الإجراء.', 'askro')]);
        }

        $format = sanitize_text_field($_POST['format'] ?? 'json');
        
        $questions = get_posts([
            'post_type' => 'askro_question',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);

        $answers = get_posts([
            'post_type' => 'askro_answer',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        ]);

        $data = [
            'questions' => [],
            'answers' => []
        ];

        foreach ($questions as $question) {
            $data['questions'][] = [
                'id' => $question->ID,
                'title' => $question->post_title,
                'content' => $question->post_content,
                'author' => get_the_author_meta('display_name', $question->post_author),
                'date' => $question->post_date
            ];
        }

        foreach ($answers as $answer) {
            $data['answers'][] = [
                'id' => $answer->ID,
                'title' => $answer->post_title,
                'content' => $answer->post_content,
                'author' => get_the_author_meta('display_name', $answer->post_author),
                'date' => $answer->post_date,
                'question_id' => get_post_meta($answer->ID, '_askro_question_id', true)
            ];
        }

        switch ($format) {
            case 'json':
                $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                break;
            case 'csv':
                $content = $this->array_to_csv($data);
                break;
            case 'xml':
                $content = $this->array_to_xml($data);
                break;
            default:
                $content = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        wp_send_json_success(['content' => $content]);
    }

    /**
     * Convert array to CSV
     *
     * @param array $data Data to convert
     * @return string CSV content
     * @since 1.0.0
     */
    private function array_to_csv($data) {
        $csv = "Type,ID,Title,Content,Author,Date,Question ID\n";
        
        foreach ($data['questions'] as $question) {
            $csv .= "Question,{$question['id']},\"{$question['title']}\",\"{$question['content']}\",\"{$question['author']}\",{$question['date']},\n";
        }
        
        foreach ($data['answers'] as $answer) {
            $csv .= "Answer,{$answer['id']},\"{$answer['title']}\",\"{$answer['content']}\",\"{$answer['author']}\",{$answer['date']},{$answer['question_id']}\n";
        }
        
        return $csv;
    }

    /**
     * Convert array to XML
     *
     * @param array $data Data to convert
     * @return string XML content
     * @since 1.0.0
     */
    private function array_to_xml($data) {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<askro_data>\n";
        
        $xml .= "  <questions>\n";
        foreach ($data['questions'] as $question) {
            $xml .= "    <question>\n";
            $xml .= "      <id>{$question['id']}</id>\n";
            $xml .= "      <title>" . htmlspecialchars($question['title']) . "</title>\n";
            $xml .= "      <content>" . htmlspecialchars($question['content']) . "</content>\n";
            $xml .= "      <author>" . htmlspecialchars($question['author']) . "</author>\n";
            $xml .= "      <date>{$question['date']}</date>\n";
            $xml .= "    </question>\n";
        }
        $xml .= "  </questions>\n";
        
        $xml .= "  <answers>\n";
        foreach ($data['answers'] as $answer) {
            $xml .= "    <answer>\n";
            $xml .= "      <id>{$answer['id']}</id>\n";
            $xml .= "      <title>" . htmlspecialchars($answer['title']) . "</title>\n";
            $xml .= "      <content>" . htmlspecialchars($answer['content']) . "</content>\n";
            $xml .= "      <author>" . htmlspecialchars($answer['author']) . "</author>\n";
            $xml .= "      <date>{$answer['date']}</date>\n";
            $xml .= "      <question_id>{$answer['question_id']}</question_id>\n";
            $xml .= "    </answer>\n";
        }
        $xml .= "  </answers>\n";
        
        $xml .= "</askro_data>";
        
        return $xml;
    }

    /**
     * Database Check page
     *
     * @since 1.0.0
     */
    public function db_check_page() {
        include_once plugin_dir_path(__FILE__) . '../admin-db-check.php';
    }
}


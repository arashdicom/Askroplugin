<?php
/**
 * Plugin Name: Askro - Advanced Q&A Community Platform
 * Plugin URI: https://arashdi.com/askro
 * Description: A highly advanced, interactive, and gamified Q&A platform that prioritizes user experience, performance, and comprehensive administrative control.
 * Version: 1.0.0
 * Author: Arashdi
 * Author URI: https://arashdi.com
 * License: GPL-3.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: askro
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * Requires PHP: 8.0
 * Network: false
 *
 * @package    Askro
 * @subpackage Core
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

// Define plugin constants
define('ASKRO_VERSION', '1.0.0');
define('ASKRO_PLUGIN_FILE', __FILE__);
define('ASKRO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ASKRO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ASKRO_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('ASKRO_ASSETS_URL', ASKRO_PLUGIN_URL . 'assets/');
define('ASKRO_TEMPLATES_DIR', ASKRO_PLUGIN_DIR . 'templates/');
define('ASKRO_INCLUDES_DIR', ASKRO_PLUGIN_DIR . 'includes/');
define('ASKRO_ADMIN_DIR', ASKRO_PLUGIN_DIR . 'admin/');

// Minimum requirements check
if (version_compare(PHP_VERSION, '8.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo sprintf(
            __('Askro requires PHP version 8.0 or higher. You are running version %s.', 'askro'),
            PHP_VERSION
        );
        echo '</p></div>';
    });
    return;
}

if (version_compare(get_bloginfo('version'), '6.0', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo sprintf(
            __('Askro requires WordPress version 6.0 or higher. You are running version %s.', 'askro'),
            get_bloginfo('version')
        );
        echo '</p></div>';
    });
    return;
}

// Autoloader
spl_autoload_register(function ($class) {
    // Check if the class belongs to our plugin
    if (strpos($class, 'Askro_') !== 0) {
        return;
    }
    
    // Convert class name to file path
    $class_file = str_replace('_', '-', strtolower($class));
    $class_file = str_replace('askro-', '', $class_file);
    $file_path = ASKRO_INCLUDES_DIR . 'classes/class-' . $class_file . '.php';
    
    if (file_exists($file_path)) {
        require_once $file_path;
    }
});

/**
 * Main Askro Plugin Class
 *
 * @since 1.0.0
 */
final class Askro_Main {
    
    /**
     * Single instance of the plugin
     *
     * @var Askro_Main
     * @since 1.0.0
     */
    private static $instance = null;
    
    /**
     * Plugin components
     *
     * @var array
     * @since 1.0.0
     */
    private $components = [];
    
    /**
     * Dependency container
     *
     * @var Askro_Dependency_Container
     * @since 1.0.0
     */
    private $container;
    
    /**
     * Get single instance
     *
     * @return Askro_Main
     * @since 1.0.0
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    private function __construct() {
        $this->includes();
        $this->init_hooks();
    }
    
    /**
     * Include required files
     *
     * @since 1.0.0
     */
    private function includes() {
        // Core functions
        require_once ASKRO_INCLUDES_DIR . 'functions/core-functions.php';
        require_once ASKRO_INCLUDES_DIR . 'functions/template-functions.php';
        require_once ASKRO_INCLUDES_DIR . 'functions/user-functions.php';
        require_once ASKRO_INCLUDES_DIR . 'functions/voting-functions.php';
        require_once ASKRO_INCLUDES_DIR . 'functions/api-functions.php';
        require_once ASKRO_INCLUDES_DIR . 'functions/service-functions.php';
        
        // URL Handler
        require_once ASKRO_INCLUDES_DIR . 'classes/class-url-handler.php';
        
        // API Components
        require_once ASKRO_INCLUDES_DIR . 'classes/class-api.php';
        require_once ASKRO_INCLUDES_DIR . 'classes/class-api-cache.php';
        require_once ASKRO_INCLUDES_DIR . 'classes/class-api-auth.php';
        require_once ASKRO_INCLUDES_DIR . 'classes/class-api-docs.php';
        
        // Error Handling & Monitoring
require_once ASKRO_INCLUDES_DIR . 'classes/class-error-handler.php';
require_once ASKRO_INCLUDES_DIR . 'classes/class-testing.php';
require_once ASKRO_INCLUDES_DIR . 'classes/class-monitoring.php';

// Helper Classes
require_once ASKRO_INCLUDES_DIR . 'classes/class-response-handler.php';
require_once ASKRO_INCLUDES_DIR . 'classes/class-user-helper.php';
require_once ASKRO_INCLUDES_DIR . 'classes/class-query-helper.php';
require_once ASKRO_INCLUDES_DIR . 'classes/class-security-helper.php';
require_once ASKRO_INCLUDES_DIR . 'classes/class-standards-helper.php';
require_once ASKRO_INCLUDES_DIR . 'classes/class-database-optimizer.php';
        
        // Interfaces
        require_once ASKRO_INCLUDES_DIR . 'classes/interfaces/interface-ajax-handler.php';
        require_once ASKRO_INCLUDES_DIR . 'classes/interfaces/interface-display-handler.php';
        require_once ASKRO_INCLUDES_DIR . 'classes/interfaces/interface-admin-handler.php';
        
        // Abstract Classes
        require_once ASKRO_INCLUDES_DIR . 'classes/abstract/abstract-ajax-handler.php';
        
        // AJAX Handler Classes
        require_once ASKRO_INCLUDES_DIR . 'classes/ajax/class-ajax-voting.php';
        require_once ASKRO_INCLUDES_DIR . 'classes/ajax/class-ajax-comments.php';
        require_once ASKRO_INCLUDES_DIR . 'classes/ajax/class-ajax-search.php';
        
        // Dependency Injection
        require_once ASKRO_INCLUDES_DIR . 'classes/class-dependency-container.php';
        
        // Admin includes
        if (is_admin()) {
            require_once ASKRO_ADMIN_DIR . 'class-admin.php';
        }
    }
    
    /**
     * Initialize hooks
     *
     * @since 1.0.0
     */
    private function init_hooks() {
        add_action('plugins_loaded', [$this, 'init'], 10);
        add_action('init', [$this, 'load_textdomain'], 5);
        
        // Activation and deactivation hooks
        // register_activation_hook(ASKRO_PLUGIN_FILE, [$this, 'activate']); // Moved outside
        // register_deactivation_hook(ASKRO_PLUGIN_FILE, [$this, 'deactivate']); // Moved outside
    }
    
    /**
     * Initialize plugin components using dependency injection
     *
     * @since 1.0.0
     */
    public function init() {
        // Initialize dependency container
        $this->container = Askro_Dependency_Container::get_instance();
        $this->container->initialize_all_services();
        
        // Get components from container
        $this->components = [
            'database' => $this->container->get('database'),
            'post_types' => $this->container->get('post_types'),
            'taxonomies' => $this->container->get('taxonomies'),
            'url_handler' => $this->container->get('url_handler'),
            'assets' => $this->container->get('assets'),
            'shortcodes' => $this->container->get('shortcodes'),
            'ajax' => new Askro_Ajax(), // Keep original AJAX class for backward compatibility
            'comments' => $this->container->get('comments'),
            'voting' => $this->container->get('voting'),
            'gamification' => $this->container->get('gamification'),
            'analytics' => $this->container->get('analytics'),
            'api' => $this->container->get('api'),
            'api_cache' => $this->container->get('api_cache'),
            'api_auth' => $this->container->get('api_auth'),
            'api_docs' => $this->container->get('api_docs'),
            'ajax_voting' => $this->container->get('ajax_voting'),
            'ajax_comments' => $this->container->get('ajax_comments'),
            'ajax_search' => $this->container->get('ajax_search'),
        ];
        
        // Add admin component if in admin area
        if (defined('ABSPATH') && function_exists('is_admin') && is_admin()) {
            $this->components['admin'] = $this->container->get('admin');
        }
        
        // Initialize each component
        foreach ($this->components as $component) {
            if (method_exists($component, 'init')) {
                $component->init();
            }
        }
        
        do_action('askro_loaded');
    }
    
    /**
     * Load plugin textdomain
     *
     * @since 1.0.0
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'askro',
            false,
            dirname(ASKRO_PLUGIN_BASENAME) . '/languages'
        );
    }
    
    /**
     * Plugin activation hook
     *
     * @since 1.0.0
     */
    public static function activate() {
        // Create database tables
        $database = new Askro_Database();
        $database->create_tables();
        
        // Create default settings
        $database->create_default_settings();
        
        // Create default categories
        self::create_default_categories();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create default categories
     *
     * @since 1.0.0
     */
    public static function create_default_categories() {
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
                    'UI/UX Design' => 'أسئلة حول تصميم واجهة المستخدم',
                    'SEO' => 'أسئلة حول تحسين محركات البحث',
                    'Performance' => 'أسئلة حول أداء المواقع',
                    'Accessibility' => 'أسئلة حول إمكانية الوصول'
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
     * Plugin deactivation
     *
     * @since 1.0.0
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear scheduled events
        wp_clear_scheduled_hook('askro_daily_cleanup');
        wp_clear_scheduled_hook('askro_weekly_stats');
        
        // Remove activation flag
        delete_option('askro_activated');
    }
    
    /**
     * Get component instance
     *
     * @param string $component Component name
     * @return object|null
     * @since 1.0.0
     */
    public function get_component($component) {
        return isset($this->components[$component]) ? $this->components[$component] : null;
    }
}

/**
 * Get main plugin instance
 *
 * @return Askro_Main
 * @since 1.0.0
 */
function askro() {
    return Askro_Main::get_instance();
}

// Initialize the plugin
askro();

// Register activation and deactivation hooks
register_activation_hook(__FILE__, [Askro_Main::get_instance(), 'activate']);
register_deactivation_hook(__FILE__, [Askro_Main::get_instance(), 'deactivate']);


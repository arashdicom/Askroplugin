<?php
/**
 * Database Management Class
 *
 * @package    Askro
 * @subpackage Core/Database
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
 * Askro Database Class
 *
 * Handles all database operations including table creation,
 * data management, and schema updates.
 *
 * @since 1.0.0
 */
class Askro_Database {

    /**
     * Database version
     *
     * @var string
     * @since 1.0.0
     */
    private $db_version = '1.0.0';

    /**
     * WordPress database object
     *
     * @var wpdb
     * @since 1.0.0
     */
    private $wpdb;

    /**
     * Table names
     *
     * @var array
     * @since 1.0.0
     */
    private $tables = [];

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->set_table_names();
    }

    /**
     * Initialize the database component
     *
     * @since 1.0.0
     */
    public function init() {
        add_action('plugins_loaded', [$this, 'check_database_version']);
    }

    /**
     * Set table names with WordPress prefix
     *
     * @since 1.0.0
     */
    private function set_table_names() {
        $this->tables = [
            'user_votes' => $this->wpdb->prefix . 'askro_user_votes',
            'vote_weights' => $this->wpdb->prefix . 'askro_vote_weights',
            'points_log' => $this->wpdb->prefix . 'askro_points_log',
            'vote_reason_presets' => $this->wpdb->prefix . 'askro_vote_reason_presets',
            'comments' => $this->wpdb->prefix . 'askro_comments',
            'comment_votes' => $this->wpdb->prefix . 'askro_comment_votes',
            'comment_reactions' => $this->wpdb->prefix . 'askro_comment_reactions',
            'user_badges' => $this->wpdb->prefix . 'askro_user_badges',
            'badges' => $this->wpdb->prefix . 'askro_badges',
            'user_achievements' => $this->wpdb->prefix . 'askro_user_achievements',
            'achievements' => $this->wpdb->prefix . 'askro_achievements',
            'user_follows' => $this->wpdb->prefix . 'askro_user_follows',
            'notifications' => $this->wpdb->prefix . 'askro_notifications',
            'user_settings' => $this->wpdb->prefix . 'askro_user_settings',
            'analytics' => $this->wpdb->prefix . 'askro_analytics',
            'settings' => $this->wpdb->prefix . 'askro_settings',
            'security_logs' => $this->wpdb->prefix . 'askro_security_logs'
        ];
    }

    /**
     * Get table name
     *
     * @param string $table Table key
     * @return string Table name with prefix
     * @since 1.0.0
     */
    public function get_table_name($table) {
        return isset($this->tables[$table]) ? $this->tables[$table] : '';
    }

    /**
     * Check database version and update if needed
     *
     * @since 1.0.0
     */
    public function check_database_version() {
        $installed_version = get_option('askro_db_version', '0.0.0');
        
        if (version_compare($installed_version, $this->db_version, '<')) {
            $this->create_tables();
            $this->update_database_version();
        }
    }

    /**
     * Create all custom database tables
     *
     * @since 1.0.0
     */
    public function create_tables() {
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Create user votes table
        $this->create_user_votes_table();
        
        // Create vote weights table
        $this->create_vote_weights_table();
        
        // Create points log table
        $this->create_points_log_table();
        
        // Create vote reason presets table
        $this->create_vote_reason_presets_table();
        
        // Create comments table
        $this->create_comments_table();
        
        // Create comment votes table
        $this->create_comment_votes_table();
        
        // Create comment reactions table
        $this->create_comment_reactions_table();
        
        // Create badges table
        $this->create_badges_table();
        
        // Create user badges table
        $this->create_user_badges_table();
        
        // Create achievements table
        $this->create_achievements_table();
        
        // Create user achievements table
        $this->create_user_achievements_table();
        
        // Create user follows table
        $this->create_user_follows_table();
        
        // Create notifications table
        $this->create_notifications_table();
        
        // Create user settings table
        $this->create_user_settings_table();
        
        // Create analytics table
        $this->create_analytics_table();
        
        // Create settings table
        $this->create_settings_table();
        
        // Create security logs table
        $this->create_security_logs_table();
    }

    /**
     * Create user votes table
     *
     * @since 1.0.0
     */
    private function create_user_votes_table() {
        $table_name = $this->tables['user_votes'];
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            target_user_id bigint(20) unsigned NOT NULL,
            vote_type enum('useful','creative','emotional','toxic','offtopic','funny','deep','inaccurate','spam','duplicate') NOT NULL DEFAULT 'useful',
            vote_strength tinyint(4) NOT NULL DEFAULT 1,
            vote_sentiment json DEFAULT NULL,
            context_score float DEFAULT NULL,
            meta json DEFAULT NULL,
            voted_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY post_user_vote (post_id, user_id, vote_type),
            KEY post_user_idx (post_id, user_id),
            KEY target_user_idx (target_user_id),
            KEY vote_type_idx (vote_type),
            KEY voted_at_idx (voted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($sql);
    }

    /**
     * Create vote weights table
     *
     * @since 1.0.0
     */
    private function create_vote_weights_table() {
        $table_name = $this->tables['vote_weights'];
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            vote_type enum('useful','creative','emotional','toxic','offtopic','funny','deep','inaccurate','spam','duplicate') NOT NULL,
            vote_strength tinyint(4) NOT NULL,
            point_change_for_voter int(11) NOT NULL DEFAULT 0,
            point_change_for_target int(11) NOT NULL DEFAULT 0,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY vote_type_strength (vote_type, vote_strength)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($sql);
    }

    /**
     * Create points log table
     *
     * @since 1.0.0
     */
    private function create_points_log_table() {
        $table_name = $this->tables['points_log'];
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            source_user_id bigint(20) unsigned DEFAULT NULL,
            points_change int(11) NOT NULL,
            reason_key varchar(100) NOT NULL,
            related_type enum('answer','question','vote','comment','achievement','system','badge','quest') NOT NULL,
            related_id bigint(20) unsigned DEFAULT NULL,
            context json DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id_idx (user_id),
            KEY source_user_id_idx (source_user_id),
            KEY reason_key_idx (reason_key),
            KEY related_type_id_idx (related_type, related_id),
            KEY created_at_idx (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($sql);
    }

    /**
     * Create vote reason presets table
     *
     * @since 1.0.0
     */
    private function create_vote_reason_presets_table() {
        $table_name = $this->tables['vote_reason_presets'];
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            vote_type enum('useful','creative','emotional','toxic','offtopic','funny','deep','inaccurate','spam','duplicate') NOT NULL,
            title varchar(100) NOT NULL,
            description text DEFAULT NULL,
            icon varchar(100) DEFAULT NULL,
            color varchar(7) DEFAULT '#3b82f6',
            is_active tinyint(1) NOT NULL DEFAULT 1,
            sort_order int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY vote_type_unique (vote_type),
            KEY is_active_idx (is_active),
            KEY sort_order_idx (sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($sql);
    }

    /**
     * Create comments table
     *
     * @since 1.0.0
     */
    private function create_comments_table() {
        $table_name = $this->tables['comments'];
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            answer_id bigint(20) unsigned DEFAULT NULL,
            user_id bigint(20) unsigned NOT NULL,
            parent_id bigint(20) unsigned DEFAULT 0,
            content longtext NOT NULL,
            status enum('approved','pending','spam','trash') NOT NULL DEFAULT 'approved',
            vote_score int(11) NOT NULL DEFAULT 0,
            meta json DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY post_id_idx (post_id),
            KEY answer_id_idx (answer_id),
            KEY user_id_idx (user_id),
            KEY parent_id_idx (parent_id),
            KEY status_idx (status),
            KEY created_at_idx (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($sql);
    }

    /**
     * Create comment votes table
     *
     * @since 1.0.0
     */
    private function create_comment_votes_table() {
        $table_name = $this->tables['comment_votes'];
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            comment_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            vote_type enum('up','down') NOT NULL DEFAULT 'up',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY comment_user_vote_unique (comment_id, user_id),
            KEY comment_id_idx (comment_id),
            KEY user_id_idx (user_id),
            KEY vote_type_idx (vote_type),
            KEY created_at_idx (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($sql);
        
        // Add foreign key after table creation
        $this->wpdb->query("ALTER TABLE $table_name ADD CONSTRAINT fk_comment_votes_comment_id FOREIGN KEY (comment_id) REFERENCES {$this->tables['comments']}(id) ON DELETE CASCADE");
    }

    /**
     * Create comment reactions table
     *
     * @since 1.0.0
     */
    private function create_comment_reactions_table() {
        $table_name = $this->tables['comment_reactions'];
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            comment_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            reaction_type varchar(20) NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY comment_user_reaction_unique (comment_id, user_id, reaction_type),
            KEY comment_id_idx (comment_id),
            KEY user_id_idx (user_id),
            KEY reaction_type_idx (reaction_type),
            KEY created_at_idx (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($sql);
        
        // Add foreign key after table creation
        $this->wpdb->query("ALTER TABLE $table_name ADD CONSTRAINT fk_comment_reactions_comment_id FOREIGN KEY (comment_id) REFERENCES {$this->tables['comments']}(id) ON DELETE CASCADE");
    }

    /**
     * Create badges table
     *
     * @since 1.0.0
     */
    private function create_badges_table() {
        $table_name = $this->tables['badges'];
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            description text DEFAULT NULL,
            icon varchar(100) DEFAULT NULL,
            color varchar(7) DEFAULT '#3b82f6',
            criteria json NOT NULL,
            category enum('contribution','popularity','expertise','special','secret') NOT NULL DEFAULT 'contribution',
            points_reward int(11) NOT NULL DEFAULT 0,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            is_secret tinyint(1) NOT NULL DEFAULT 0,
            sort_order int(11) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY name_unique (name),
            KEY category_idx (category),
            KEY is_active_idx (is_active),
            KEY is_secret_idx (is_secret)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($sql);
    }

    /**
     * Create user badges table
     *
     * @since 1.0.0
     */
    private function create_user_badges_table() {
        $table_name = $this->tables['user_badges'];
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            badge_id bigint(20) unsigned NOT NULL,
            earned_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            context json DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY user_badge_unique (user_id, badge_id),
            KEY user_id_idx (user_id),
            KEY badge_id_idx (badge_id),
            KEY earned_at_idx (earned_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($sql);
    }

    /**
     * Create achievements table
     *
     * @since 1.0.0
     */
    private function create_achievements_table() {
        $table_name = $this->tables['achievements'];
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(100) NOT NULL,
            description text DEFAULT NULL,
            icon varchar(100) DEFAULT NULL,
            criteria json NOT NULL,
            points_reward int(11) NOT NULL DEFAULT 0,
            badge_reward bigint(20) unsigned DEFAULT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 1,
            is_repeatable tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY name_unique (name),
            KEY is_active_idx (is_active),
            KEY badge_reward_idx (badge_reward)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($sql);
    }

    /**
     * Create user achievements table
     *
     * @since 1.0.0
     */
    private function create_user_achievements_table() {
        $table_name = $this->tables['user_achievements'];
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            achievement_id bigint(20) unsigned NOT NULL,
            progress json DEFAULT NULL,
            completed_at datetime DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id_idx (user_id),
            KEY achievement_id_idx (achievement_id),
            KEY completed_at_idx (completed_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($sql);
    }

    /**
     * Create user follows table
     *
     * @since 1.0.0
     */
    private function create_user_follows_table() {
        $table_name = $this->tables['user_follows'];
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            follower_id bigint(20) unsigned NOT NULL,
            following_id bigint(20) unsigned NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY follower_following_unique (follower_id, following_id),
            KEY follower_id_idx (follower_id),
            KEY following_id_idx (following_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($sql);
    }

    /**
     * Create notifications table
     *
     * @since 1.0.0
     */
    private function create_notifications_table() {
        $table_name = $this->tables['notifications'];
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            type enum('new_answer','new_comment','vote_received','badge_earned','achievement_unlocked','mention','follow','system') NOT NULL,
            title varchar(255) NOT NULL,
            content text DEFAULT NULL,
            related_type enum('answer','question','comment','user','badge','achievement') DEFAULT NULL,
            related_id bigint(20) unsigned DEFAULT NULL,
            is_read tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_id_idx (user_id),
            KEY type_idx (type),
            KEY is_read_idx (is_read),
            KEY created_at_idx (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($sql);
    }

    /**
     * Create user settings table
     *
     * @since 1.0.0
     */
    private function create_user_settings_table() {
        $table_name = $this->tables['user_settings'];
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            setting_key varchar(100) NOT NULL,
            setting_value longtext DEFAULT NULL,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_setting_unique (user_id, setting_key),
            KEY user_id_idx (user_id),
            KEY setting_key_idx (setting_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($sql);
    }

    /**
     * Create analytics table
     *
     * @since 1.0.0
     */
    private function create_analytics_table() {
        $table_name = $this->tables['analytics'];
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            user_id bigint(20) unsigned DEFAULT NULL,
            object_type varchar(50) DEFAULT NULL,
            object_id bigint(20) unsigned DEFAULT NULL,
            data json DEFAULT NULL,
            ip_hash varchar(64) DEFAULT NULL,
            user_agent_hash varchar(64) DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type_idx (event_type),
            KEY user_id_idx (user_id),
            KEY object_type_id_idx (object_type, object_id),
            KEY created_at_idx (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($sql);
    }

    /**
     * Create settings table
     *
     * @since 1.0.0
     */
    private function create_settings_table() {
        $table_name = $this->tables['settings'];
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            option_name varchar(191) NOT NULL,
            option_value longtext NOT NULL,
            autoload enum('yes','no') NOT NULL DEFAULT 'yes',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY option_name_idx (option_name),
            KEY autoload_idx (autoload),
            KEY updated_at_idx (updated_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($sql);
    }

    /**
     * Create security logs table
     *
     * @since 1.0.0
     */
    private function create_security_logs_table() {
        $table_name = $this->tables['security_logs'];
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_type varchar(100) NOT NULL,
            event_data longtext NOT NULL,
            ip_address varchar(45) NOT NULL,
            user_agent text NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            date_created datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type_idx (event_type),
            KEY ip_address_idx (ip_address),
            KEY user_id_idx (user_id),
            KEY date_created_idx (date_created)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        
        dbDelta($sql);
    }

    /**
     * Create default data
     *
     * @since 1.0.0
     */
    public function create_default_data() {
        $this->create_default_vote_weights();
        $this->create_default_vote_reason_presets();
        $this->create_default_badges();
        $this->create_default_achievements();
        $this->create_default_settings();
    }

    /**
     * Create default vote weights
     *
     * @since 1.0.0
     */
    private function create_default_vote_weights() {
        $table_name = $this->tables['vote_weights'];
        
        // Check if data already exists
        $existing = $this->wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($existing > 0) {
            return;
        }

        $default_weights = [
            // Positive votes
            ['useful', 1, 1, 5],
            ['useful', 2, 2, 10],
            ['useful', 3, 3, 15],
            ['creative', 1, 1, 8],
            ['creative', 2, 2, 12],
            ['creative', 3, 3, 18],
            ['deep', 1, 2, 10],
            ['deep', 2, 3, 15],
            ['deep', 3, 5, 25],
            ['funny', 1, 1, 3],
            ['emotional', 1, 1, 5],
            
            // Negative votes
            ['toxic', -1, -1, -10],
            ['toxic', -2, -2, -20],
            ['toxic', -3, -3, -30],
            ['offtopic', -1, -1, -5],
            ['offtopic', -2, -2, -10],
            ['inaccurate', -1, -1, -8],
            ['inaccurate', -2, -2, -15],
            ['spam', -1, -2, -25],
            ['duplicate', -1, -1, -5]
        ];

        foreach ($default_weights as $weight) {
            $this->wpdb->insert(
                $table_name,
                [
                    'vote_type' => $weight[0],
                    'vote_strength' => $weight[1],
                    'point_change_for_voter' => $weight[2],
                    'point_change_for_target' => $weight[3]
                ],
                ['%s', '%d', '%d', '%d']
            );
        }
    }

    /**
     * Create default vote reason presets
     *
     * @since 1.0.0
     */
    private function create_default_vote_reason_presets() {
        $table_name = $this->tables['vote_reason_presets'];
        
        // Check if data already exists
        $existing = $this->wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($existing > 0) {
            return;
        }

        $default_presets = [
            ['useful', 'مفيد', 'إجابة مفيدة وواضحة', 'dashicons-yes-alt', '#22c55e', 1, 1],
            ['creative', 'إبداعي', 'حل إبداعي ومبتكر', 'dashicons-lightbulb', '#f59e0b', 1, 2],
            ['deep', 'عميق', 'تحليل عميق ومفصل', 'dashicons-analytics', '#8b5cf6', 1, 3],
            ['funny', 'مضحك', 'محتوى مسلي ومضحك', 'dashicons-smiley', '#06b6d4', 1, 4],
            ['emotional', 'مؤثر', 'محتوى مؤثر وملهم', 'dashicons-heart', '#ec4899', 1, 5],
            ['inaccurate', 'غير دقيق', 'معلومات غير صحيحة', 'dashicons-warning', '#ef4444', 1, 6],
            ['offtopic', 'خارج الموضوع', 'لا يتعلق بالسؤال', 'dashicons-dismiss', '#6b7280', 1, 7],
            ['toxic', 'سام', 'محتوى سام أو مؤذي', 'dashicons-shield-alt', '#dc2626', 1, 8],
            ['spam', 'سبام', 'محتوى غير مرغوب فيه', 'dashicons-trash', '#991b1b', 1, 9],
            ['duplicate', 'مكرر', 'إجابة مكررة', 'dashicons-admin-page', '#9ca3af', 1, 10]
        ];

        foreach ($default_presets as $preset) {
            $this->wpdb->insert(
                $table_name,
                [
                    'vote_type' => $preset[0],
                    'title' => $preset[1],
                    'description' => $preset[2],
                    'icon' => $preset[3],
                    'color' => $preset[4],
                    'is_active' => $preset[5],
                    'sort_order' => $preset[6]
                ],
                ['%s', '%s', '%s', '%s', '%s', '%d', '%d']
            );
        }
    }

    /**
     * Create default badges
     *
     * @since 1.0.0
     */
    private function create_default_badges() {
        $table_name = $this->tables['badges'];
        
        // Check if data already exists
        $existing = $this->wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($existing > 0) {
            return;
        }

        $default_badges = [
            [
                'name' => 'مرحباً بك',
                'description' => 'أول سؤال أو إجابة',
                'icon' => 'dashicons-welcome-learn-more',
                'color' => '#22c55e',
                'criteria' => json_encode(['first_post' => true]),
                'category' => 'contribution',
                'points_reward' => 10
            ],
            [
                'name' => 'فضولي',
                'description' => 'طرح 10 أسئلة',
                'icon' => 'dashicons-editor-help',
                'color' => '#3b82f6',
                'criteria' => json_encode(['questions_count' => 10]),
                'category' => 'contribution',
                'points_reward' => 50
            ],
            [
                'name' => 'مساعد',
                'description' => 'تقديم 25 إجابة',
                'icon' => 'dashicons-sos',
                'color' => '#8b5cf6',
                'criteria' => json_encode(['answers_count' => 25]),
                'category' => 'contribution',
                'points_reward' => 100
            ],
            [
                'name' => 'خبير',
                'description' => '100 إجابة مقبولة',
                'icon' => 'dashicons-awards',
                'color' => '#f59e0b',
                'criteria' => json_encode(['accepted_answers' => 100]),
                'category' => 'expertise',
                'points_reward' => 500
            ],
            [
                'name' => 'محبوب',
                'description' => '1000 تصويت إيجابي',
                'icon' => 'dashicons-heart',
                'color' => '#ec4899',
                'criteria' => json_encode(['positive_votes' => 1000]),
                'category' => 'popularity',
                'points_reward' => 200
            ]
        ];

        foreach ($default_badges as $badge) {
            $this->wpdb->insert(
                $table_name,
                $badge,
                ['%s', '%s', '%s', '%s', '%s', '%s', '%d']
            );
        }
    }

    /**
     * Create default achievements
     *
     * @since 1.0.0
     */
    private function create_default_achievements() {
        $table_name = $this->tables['achievements'];
        
        // Check if data already exists
        $existing = $this->wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($existing > 0) {
            return;
        }

        $default_achievements = [
            [
                'name' => 'البداية',
                'description' => 'إنشاء حساب وتسجيل الدخول',
                'icon' => 'dashicons-admin-users',
                'criteria' => json_encode(['account_created' => true]),
                'points_reward' => 5,
                'is_repeatable' => 0
            ],
            [
                'name' => 'نشط يومياً',
                'description' => 'تسجيل الدخول يومياً لمدة 7 أيام',
                'icon' => 'dashicons-calendar-alt',
                'criteria' => json_encode(['daily_login_streak' => 7]),
                'points_reward' => 25,
                'is_repeatable' => 1
            ],
            [
                'name' => 'مشارك نشط',
                'description' => 'نشر 5 أسئلة أو إجابات في أسبوع',
                'icon' => 'dashicons-edit',
                'criteria' => json_encode(['weekly_posts' => 5]),
                'points_reward' => 30,
                'is_repeatable' => 1
            ]
        ];

        foreach ($default_achievements as $achievement) {
            $this->wpdb->insert(
                $table_name,
                $achievement,
                ['%s', '%s', '%s', '%s', '%d', '%d']
            );
        }
    }

    /**
     * Create default settings
     *
     * @since 1.0.0
     */
    public function create_default_settings() {
        $table_name = $this->tables['settings'];
        
        // Check if data already exists
        $existing = $this->wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        if ($existing > 0) {
            return;
        }

        $default_settings = [
            // General Settings
            ['archive_page_id', '0', 'yes'],
            ['ask_question_page_id', '0', 'yes'],
            ['user_profile_page_id', '0', 'yes'],
            ['min_role_ask_question', 'subscriber', 'yes'],
            ['min_role_submit_answer', 'subscriber', 'yes'],
            ['min_role_submit_comment', 'subscriber', 'yes'],
            
            // Voting Settings
            ['voting_settings', json_encode([
                'allow_guest_voting' => false,
                'enable_karma_deflector' => true,
                'daily_vote_limit' => 50,
                'minimum_points_to_vote' => 0,
                'default_vote_strength' => 1,
                'enable_multi_dimensional' => true,
                'enabled_vote_types' => [
                    'useful' => true,
                    'creative' => true,
                    'deep' => true,
                    'funny' => true,
                    'emotional' => true,
                    'toxic' => true,
                    'offtopic' => true,
                    'inaccurate' => true,
                    'spam' => true,
                    'duplicate' => true
                ]
            ]), 'yes'],
            
            // Points Settings
            ['points_settings', json_encode([
                'question_points' => 5,
                'answer_points' => 10,
                'accepted_answer_points' => 25,
                'upvote_points' => 2,
                'downvote_penalty' => 1,
                'minimum_points' => 0
            ]), 'yes'],
            
            // General Settings
            ['general_settings', json_encode([
                'allow_guest_questions' => false,
                'allow_guest_answers' => false,
                'allow_guest_comments' => false,
                'moderate_questions' => false,
                'moderate_answers' => false,
                'enable_pre_question_assistant' => true,
                'enable_image_upload' => true,
                'enable_code_editor' => true,
                'max_attachments' => 5,
                'max_file_size' => 5
            ]), 'yes'],
            
            // Display Settings
            ['display_settings', json_encode([
                'show_avatars' => true,
                'show_ranks' => true,
                'leaderboard_limit' => 10,
                'leaderboard_timeframe' => 'all_time',
                'search_results_per_page' => 10,
                'enable_advanced_search' => true,
                'search_highlight' => true
            ]), 'yes'],
            
            // PWA Settings
            ['pwa_settings', json_encode([
                'enable_pwa' => false,
                'app_name' => 'AskMe',
                'app_icon' => ''
            ]), 'yes'],
            
            // Security Settings
            ['security_settings', json_encode([
                'blocked_ips' => [],
                'blocked_domains' => [],
                'enable_rate_limiting' => true,
                'max_requests_per_minute' => 60
            ]), 'yes'],
            
            // Analytics Settings
            ['analytics_settings', json_encode([
                'track_user_behavior' => true,
                'track_page_views' => true,
                'track_votes' => true,
                'track_comments' => true,
                'retention_days' => 90
            ]), 'yes']
        ];

        foreach ($default_settings as $setting) {
            $this->wpdb->insert(
                $table_name,
                [
                    'option_name' => $setting[0],
                    'option_value' => $setting[1],
                    'autoload' => $setting[2]
                ],
                ['%s', '%s', '%s']
            );
        }
    }

    /**
     * Update database version
     *
     * @since 1.0.0
     */
    private function update_database_version() {
        update_option('askro_db_version', $this->db_version);
    }

    /**
     * Drop all tables (for uninstall)
     *
     * @since 1.0.0
     */
    public function drop_tables() {
        foreach ($this->tables as $table) {
            $this->wpdb->query("DROP TABLE IF EXISTS $table");
        }
        
        delete_option('askro_db_version');
    }

    /**
     * Get table status
     *
     * @return array Table status information
     * @since 1.0.0
     */
    public function get_table_status() {
        $status = [];
        
        foreach ($this->tables as $key => $table) {
            $exists = $this->wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
            $count = 0;
            
            if ($exists) {
                $count = $this->wpdb->get_var("SELECT COUNT(*) FROM $table");
            }
            
            $status[$key] = [
                'name' => $table,
                'exists' => $exists,
                'count' => $count
            ];
        }
        
        return $status;
    }

    /**
     * Optimize database tables
     *
     * @since 1.0.0
     */
    public function optimize_tables() {
        foreach ($this->tables as $table) {
            $this->wpdb->query("OPTIMIZE TABLE $table");
        }
    }

    /**
     * Repair database tables
     *
     * @since 1.0.0
     */
    public function repair_tables() {
        foreach ($this->tables as $table) {
            $this->wpdb->query("REPAIR TABLE $table");
        }
    }
}


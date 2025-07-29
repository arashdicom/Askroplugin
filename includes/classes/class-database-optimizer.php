<?php
/**
 * Askro Database Optimizer Class
 * 
 * Provides database query optimization and performance improvements
 * 
 * @package Askro
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Askro_Database_Optimizer Class
 * 
 * Handles database optimization and query improvements
 * 
 * @since 1.0.0
 */
class Askro_Database_Optimizer {
    
    /**
     * Query cache
     * 
     * @var array
     */
    private $query_cache = [];
    
    /**
     * Cache expiration time (in seconds)
     * 
     * @var int
     */
    private $cache_expiration = 1800; // 30 minutes
    
    /**
     * Slow query threshold (in seconds)
     * 
     * @var float
     */
    private $slow_query_threshold = 1.0;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        add_action('init', [$this, 'setup_query_monitoring']);
        add_action('wp_loaded', [$this, 'optimize_tables']);
        add_action('admin_init', [$this, 'schedule_optimization']);
    }
    
    /**
     * Setup query monitoring
     */
    public function setup_query_monitoring() {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            add_filter('query', [$this, 'monitor_slow_queries']);
        }
    }
    
    /**
     * Monitor slow queries
     * 
     * @param string $query SQL query
     * @return string Original query
     */
    public function monitor_slow_queries($query) {
        $start_time = microtime(true);
        
        // Store original query for monitoring
        $original_query = $query;
        
        // Add monitoring callback
        add_filter('query', function($q) use ($start_time, $original_query) {
            $execution_time = microtime(true) - $start_time;
            
            if ($execution_time > $this->slow_query_threshold) {
                $this->log_slow_query($original_query, $execution_time);
            }
            
            return $q;
        }, 999);
        
        return $query;
    }
    
    /**
     * Log slow query
     * 
     * @param string $query SQL query
     * @param float $execution_time Execution time in seconds
     */
    private function log_slow_query($query, $execution_time) {
        global $askro_error_handler;
        
        if ($askro_error_handler) {
            $askro_error_handler->warning('Slow Query Detected', [
                'query' => $query,
                'execution_time' => $execution_time,
                'threshold' => $this->slow_query_threshold
            ]);
        }
    }
    
    /**
     * Optimize database tables
     */
    public function optimize_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'askro_questions',
            $wpdb->prefix . 'askro_answers',
            $wpdb->prefix . 'askro_comments',
            $wpdb->prefix . 'askro_points_log',
            $wpdb->prefix . 'askro_user_achievements',
            $wpdb->prefix . 'askro_achievements',
            $wpdb->prefix . 'askro_badges',
            $wpdb->prefix . 'askro_security_logs'
        ];
        
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
                $wpdb->query("OPTIMIZE TABLE $table");
            }
        }
    }
    
    /**
     * Schedule optimization
     */
    public function schedule_optimization() {
        if (!wp_next_scheduled('askro_optimize_tables')) {
            wp_schedule_event(time(), 'daily', 'askro_optimize_tables');
        }
        
        add_action('askro_optimize_tables', [$this, 'optimize_tables']);
    }
    
    /**
     * Get optimized questions query
     * 
     * @param array $args Query arguments
     * @return string Optimized SQL query
     */
    public function get_questions_query($args = []) {
        global $wpdb;
        
        $defaults = [
            'status' => 'open',
            'limit' => 10,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'user_id' => 0,
            'category' => '',
            'search' => ''
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $table = $wpdb->prefix . 'askro_questions';
        $where_clauses = [];
        $where_values = [];
        
        // Build WHERE clause
        if (!empty($args['status'])) {
            $where_clauses[] = 'q.status = %s';
            $where_values[] = $args['status'];
        }
        
        if (!empty($args['user_id'])) {
            $where_clauses[] = 'q.user_id = %d';
            $where_values[] = $args['user_id'];
        }
        
        if (!empty($args['search'])) {
            $where_clauses[] = '(q.title LIKE %s OR q.content LIKE %s)';
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $where_values[] = $search_term;
            $where_values[] = $search_term;
        }
        
        $where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';
        
        // Build ORDER BY clause
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        // Build LIMIT clause
        $limit = intval($args['limit']);
        $offset = intval($args['offset']);
        $limit_sql = "LIMIT $limit OFFSET $offset";
        
        // Build complete query with JOIN for user data
        $sql = "
            SELECT 
                q.*,
                u.display_name as author_name,
                u.user_email as author_email,
                COUNT(a.id) as answer_count,
                SUM(a.votes) as total_votes
            FROM $table q
            LEFT JOIN {$wpdb->users} u ON q.user_id = u.ID
            LEFT JOIN {$wpdb->prefix}askro_answers a ON q.id = a.question_id
            $where_sql
            GROUP BY q.id
            ORDER BY $orderby
            $limit_sql
        ";
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        return $sql;
    }
    
    /**
     * Get optimized answers query
     * 
     * @param int $question_id Question ID
     * @param array $args Query arguments
     * @return string Optimized SQL query
     */
    public function get_answers_query($question_id, $args = []) {
        global $wpdb;
        
        $defaults = [
            'limit' => 10,
            'offset' => 0,
            'orderby' => 'is_best DESC, votes DESC',
            'order' => 'DESC'
        ];
        
        $args = wp_parse_args($args, $defaults);
        
        $table = $wpdb->prefix . 'askro_answers';
        
        // Build ORDER BY clause
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        // Build LIMIT clause
        $limit = intval($args['limit']);
        $offset = intval($args['offset']);
        $limit_sql = "LIMIT $limit OFFSET $offset";
        
        // Build complete query with JOIN for user data
        $sql = $wpdb->prepare("
            SELECT 
                a.*,
                u.display_name as author_name,
                u.user_email as author_email,
                COUNT(c.id) as comment_count
            FROM $table a
            LEFT JOIN {$wpdb->users} u ON a.user_id = u.ID
            LEFT JOIN {$wpdb->prefix}askro_comments c ON a.id = c.post_id
            WHERE a.question_id = %d
            GROUP BY a.id
            ORDER BY $orderby
            $limit_sql
        ", $question_id);
        
        return $sql;
    }
    
    /**
     * Get optimized user statistics query
     * 
     * @param int $user_id User ID
     * @return string Optimized SQL query
     */
    public function get_user_statistics_query($user_id) {
        global $wpdb;
        
        $sql = $wpdb->prepare("
            SELECT 
                u.ID,
                u.display_name,
                u.user_email,
                COUNT(DISTINCT q.id) as question_count,
                COUNT(DISTINCT a.id) as answer_count,
                COUNT(DISTINCT c.id) as comment_count,
                SUM(CASE WHEN a.is_best = 1 THEN 1 ELSE 0 END) as best_answer_count,
                SUM(q.views) as total_views,
                SUM(a.votes) as total_votes,
                SUM(pl.points) as total_points
            FROM {$wpdb->users} u
            LEFT JOIN {$wpdb->prefix}askro_questions q ON u.ID = q.user_id
            LEFT JOIN {$wpdb->prefix}askro_answers a ON u.ID = a.user_id
            LEFT JOIN {$wpdb->prefix}askro_comments c ON u.ID = c.user_id
            LEFT JOIN {$wpdb->prefix}askro_points_log pl ON u.ID = pl.user_id
            WHERE u.ID = %d
            GROUP BY u.ID
        ", $user_id);
        
        return $sql;
    }
    
    /**
     * Get optimized leaderboard query
     * 
     * @param string $type Leaderboard type
     * @param int $limit Limit
     * @return string Optimized SQL query
     */
    public function get_leaderboard_query($type = 'points', $limit = 10) {
        global $wpdb;
        
        $limit = intval($limit);
        
        switch ($type) {
            case 'questions':
                $sql = "
                    SELECT 
                        u.ID,
                        u.display_name,
                        COUNT(q.id) as count
                    FROM {$wpdb->users} u
                    INNER JOIN {$wpdb->prefix}askro_questions q ON u.ID = q.user_id
                    GROUP BY u.ID
                    ORDER BY count DESC
                    LIMIT $limit
                ";
                break;
                
            case 'answers':
                $sql = "
                    SELECT 
                        u.ID,
                        u.display_name,
                        COUNT(a.id) as count
                    FROM {$wpdb->users} u
                    INNER JOIN {$wpdb->prefix}askro_answers a ON u.ID = a.user_id
                    GROUP BY u.ID
                    ORDER BY count DESC
                    LIMIT $limit
                ";
                break;
                
            case 'best_answers':
                $sql = "
                    SELECT 
                        u.ID,
                        u.display_name,
                        COUNT(a.id) as count
                    FROM {$wpdb->users} u
                    INNER JOIN {$wpdb->prefix}askro_answers a ON u.ID = a.user_id
                    WHERE a.is_best = 1
                    GROUP BY u.ID
                    ORDER BY count DESC
                    LIMIT $limit
                ";
                break;
                
            case 'points':
            default:
                $sql = "
                    SELECT 
                        u.ID,
                        u.display_name,
                        SUM(pl.points) as total_points
                    FROM {$wpdb->users} u
                    INNER JOIN {$wpdb->prefix}askro_points_log pl ON u.ID = pl.user_id
                    GROUP BY u.ID
                    ORDER BY total_points DESC
                    LIMIT $limit
                ";
                break;
        }
        
        return $sql;
    }
    
    /**
     * Add database indexes
     */
    public function add_indexes() {
        global $wpdb;
        
        $indexes = [
            'askro_questions' => [
                'idx_user_id' => 'user_id',
                'idx_status' => 'status',
                'idx_created_at' => 'created_at',
                'idx_views' => 'views'
            ],
            'askro_answers' => [
                'idx_question_id' => 'question_id',
                'idx_user_id' => 'user_id',
                'idx_is_best' => 'is_best',
                'idx_votes' => 'votes'
            ],
            'askro_comments' => [
                'idx_post_id' => 'post_id',
                'idx_user_id' => 'user_id',
                'idx_parent_id' => 'parent_id'
            ],
            'askro_points_log' => [
                'idx_user_id' => 'user_id',
                'idx_action' => 'action',
                'idx_created_at' => 'created_at'
            ],
            'askro_security_logs' => [
                'idx_event_type' => 'event_type',
                'idx_user_id' => 'user_id',
                'idx_created_at' => 'created_at'
            ]
        ];
        
        foreach ($indexes as $table => $table_indexes) {
            $table_name = $wpdb->prefix . $table;
            
            if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                foreach ($table_indexes as $index_name => $column) {
                    $this->add_index_if_not_exists($table_name, $index_name, $column);
                }
            }
        }
    }
    
    /**
     * Add index if it doesn't exist
     * 
     * @param string $table Table name
     * @param string $index_name Index name
     * @param string $column Column name
     */
    private function add_index_if_not_exists($table, $index_name, $column) {
        global $wpdb;
        
        $existing_indexes = $wpdb->get_results("SHOW INDEX FROM $table");
        $index_exists = false;
        
        foreach ($existing_indexes as $index) {
            if ($index->Key_name === $index_name) {
                $index_exists = true;
                break;
            }
        }
        
        if (!$index_exists) {
            $wpdb->query("ALTER TABLE $table ADD INDEX $index_name ($column)");
        }
    }
    
    /**
     * Get query performance statistics
     * 
     * @return array Performance statistics
     */
    public function get_performance_stats() {
        global $wpdb;
        
        $stats = [
            'total_queries' => $wpdb->num_queries,
            'slow_queries' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0
        ];
        
        // Get slow queries count from logs
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $stats['slow_queries'] = $this->get_slow_queries_count();
        }
        
        return $stats;
    }
    
    /**
     * Get slow queries count
     * 
     * @return int Slow queries count
     */
    private function get_slow_queries_count() {
        global $askro_error_handler;
        
        if ($askro_error_handler) {
            // This would be implemented based on your logging system
            return 0;
        }
        
        return 0;
    }
    
    /**
     * Clear query cache
     */
    public function clear_cache() {
        $this->query_cache = [];
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Cache statistics
     */
    public function get_cache_stats() {
        return [
            'cache_size' => count($this->query_cache),
            'cache_hits' => 0, // Would be implemented with proper cache tracking
            'cache_misses' => 0
        ];
    }
}

// Initialize database optimizer
$askro_database_optimizer = new Askro_Database_Optimizer(); 

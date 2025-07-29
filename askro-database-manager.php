<?php
/**
 * Askro Database Management Script
 * 
 * This script provides database management functionality for the Askro plugin:
 * 1. Creates missing database tables by triggering check_database_version()
 * 2. Optimizes and repairs existing tables
 * 3. Shows table sizes and status information
 * 
 * Usage: Run this file directly via web browser or WP-CLI
 * 
 * @package Askro
 * @since 1.0.0
 */

// Prevent direct access if not in WordPress context
if (!defined('ABSPATH')) {
    // Load WordPress if running standalone
    $wp_load_path = dirname(__FILE__) . '/../../../wp-load.php';
    if (file_exists($wp_load_path)) {
        require_once($wp_load_path);
    } else {
        die('WordPress not found. Please run this script from the plugin directory or ensure wp-load.php is accessible.');
    }
}

/**
 * Askro Database Manager Class
 */
class Askro_Database_Manager {
    
    private $wpdb;
    private $askro_database;
    
    /**
     * All Askro tables with their full names
     */
    private $askro_tables = [
        'askro_user_votes',
        'askro_vote_weights', 
        'askro_points_log',
        'askro_vote_reason_presets',
        'askro_comments',
        'askro_comment_reactions',
        'askro_user_badges',
        'askro_badges',
        'askro_user_achievements',
        'askro_achievements',
        'askro_user_follows',
        'askro_notifications',
        'askro_user_settings',
        'askro_analytics'
    ];
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        
        // Try to load the Askro_Database class
        $database_class_path = plugin_dir_path(__FILE__) . 'includes/classes/class-database.php';
        if (file_exists($database_class_path)) {
            require_once($database_class_path);
            $this->askro_database = new Askro_Database();
        }
    }
    
    /**
     * Main execution method
     */
    public function run() {
        echo "<h1>Askro Database Management</h1>\n";
        echo "<pre>\n";
        
        $action = isset($_GET['action']) ? $_GET['action'] : 'status';
        
        switch ($action) {
            case 'create':
                $this->create_missing_tables();
                break;
            case 'optimize':
                $this->optimize_tables();
                break;
            case 'repair':
                $this->repair_tables();
                break;
            case 'status':
            default:
                $this->show_table_status();
                break;
        }
        
        echo "\n</pre>";
        echo "<p><strong>Available Actions:</strong></p>";
        echo "<ul>";
        echo "<li><a href='?action=status'>Show Table Status</a></li>";
        echo "<li><a href='?action=create'>Create Missing Tables</a></li>";
        echo "<li><a href='?action=optimize'>Optimize Tables</a></li>";
        echo "<li><a href='?action=repair'>Repair Tables</a></li>";
        echo "</ul>";
    }
    
    /**
     * Create missing tables using the Askro_Database class
     */
    private function create_missing_tables() {
        echo "=== CREATING MISSING TABLES ===\n\n";
        
        if (!$this->askro_database) {
            echo "ERROR: Could not load Askro_Database class!\n";
            echo "Make sure this script is in the plugin root directory.\n";
            return;
        }
        
        // Get current database version
        $current_version = get_option('askro_db_version', 'Not set');
        echo "Current database version: {$current_version}\n";
        
        // Check which tables are missing before creation
        echo "\nChecking existing tables...\n";
        $missing_tables = [];
        foreach ($this->askro_tables as $table) {
            $full_table_name = $this->wpdb->prefix . $table;
            $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");
            if (!$table_exists) {
                $missing_tables[] = $table;
                echo "  ❌ {$full_table_name} - MISSING\n";
            } else {
                echo "  ✅ {$full_table_name} - EXISTS\n";
            }
        }
        
        if (empty($missing_tables)) {
            echo "\nAll tables already exist! No action needed.\n";
            return;
        }
        
        echo "\nCreating missing tables...\n";
        
        // Trigger database version check which will create tables
        try {
            $this->askro_database->check_database_version();
            echo "✅ Database version check completed successfully!\n";
            
            // Verify tables were created
            echo "\nVerifying table creation...\n";
            $still_missing = [];
            foreach ($missing_tables as $table) {
                $full_table_name = $this->wpdb->prefix . $table;
                $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");
                if (!$table_exists) {
                    $still_missing[] = $table;
                    echo "  ❌ {$full_table_name} - STILL MISSING\n";
                } else {
                    echo "  ✅ {$full_table_name} - CREATED SUCCESSFULLY\n";
                }
            }
            
            if (empty($still_missing)) {
                echo "\n🎉 All missing tables have been created successfully!\n";
            } else {
                echo "\n⚠️  Some tables are still missing. Check error logs for details.\n";
            }
            
        } catch (Exception $e) {
            echo "❌ ERROR during table creation: " . $e->getMessage() . "\n";
        }
        
        // Show updated database version
        $new_version = get_option('askro_db_version', 'Not set');
        echo "\nUpdated database version: {$new_version}\n";
    }
    
    /**
     * Show status of all Askro tables
     */
    private function show_table_status() {
        echo "=== ASKRO DATABASE TABLE STATUS ===\n\n";
        
        $total_size = 0;
        $table_count = 0;
        
        foreach ($this->askro_tables as $table) {
            $full_table_name = $this->wpdb->prefix . $table;
            
            // Check if table exists
            $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");
            
            if ($table_exists) {
                // Get table status information
                $status = $this->wpdb->get_row("
                    SELECT 
                        table_rows as row_count,
                        data_length as data_size,
                        index_length as index_size,
                        (data_length + index_length) as total_size
                    FROM information_schema.tables 
                    WHERE table_schema = DATABASE() 
                    AND table_name = '{$full_table_name}'
                ");
                
                if ($status) {
                    $size_mb = $this->format_bytes($status->total_size);
                    $total_size += $status->total_size;
                    $table_count++;
                    
                    echo sprintf(
                        "✅ %-35s | Rows: %8s | Size: %10s\n",
                        $full_table_name,
                        number_format($status->row_count),
                        $size_mb
                    );
                } else {
                    echo "✅ {$full_table_name} - EXISTS (status unknown)\n";
                    $table_count++;
                }
            } else {
                echo "❌ {$full_table_name} - MISSING\n";
            }
        }
        
        echo "\n" . str_repeat("-", 70) . "\n";
        echo "Total Tables: {$table_count}/" . count($this->askro_tables) . "\n";
        echo "Total Size: " . $this->format_bytes($total_size) . "\n";
        
        // Show database version
        $db_version = get_option('askro_db_version', 'Not set');
        echo "Database Version: {$db_version}\n";
    }
    
    /**
     * Optimize all existing Askro tables
     */
    private function optimize_tables() {
        echo "=== OPTIMIZING ASKRO TABLES ===\n\n";
        
        $optimized = 0;
        $errors = 0;
        
        foreach ($this->askro_tables as $table) {
            $full_table_name = $this->wpdb->prefix . $table;
            
            // Check if table exists
            $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");
            
            if ($table_exists) {
                echo "Optimizing {$full_table_name}... ";
                
                $result = $this->wpdb->query("OPTIMIZE TABLE {$full_table_name}");
                
                if ($result !== false) {
                    echo "✅ SUCCESS\n";
                    $optimized++;
                } else {
                    echo "❌ FAILED - " . $this->wpdb->last_error . "\n";
                    $errors++;
                }
            } else {
                echo "⏭️  Skipping {$full_table_name} (table doesn't exist)\n";
            }
        }
        
        echo "\n" . str_repeat("-", 50) . "\n";
        echo "Optimization Complete!\n";
        echo "Tables optimized: {$optimized}\n";
        echo "Errors: {$errors}\n";
    }
    
    /**
     * Repair all existing Askro tables
     */
    private function repair_tables() {
        echo "=== REPAIRING ASKRO TABLES ===\n\n";
        
        $repaired = 0;
        $errors = 0;
        
        foreach ($this->askro_tables as $table) {
            $full_table_name = $this->wpdb->prefix . $table;
            
            // Check if table exists
            $table_exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$full_table_name}'");
            
            if ($table_exists) {
                echo "Repairing {$full_table_name}... ";
                
                $result = $this->wpdb->query("REPAIR TABLE {$full_table_name}");
                
                if ($result !== false) {
                    echo "✅ SUCCESS\n";
                    $repaired++;
                } else {
                    echo "❌ FAILED - " . $this->wpdb->last_error . "\n";
                    $errors++;
                }
            } else {
                echo "⏭️  Skipping {$full_table_name} (table doesn't exist)\n";
            }
        }
        
        echo "\n" . str_repeat("-", 50) . "\n";
        echo "Repair Complete!\n";
        echo "Tables repaired: {$repaired}\n";
        echo "Errors: {$errors}\n";
    }
    
    /**
     * Format bytes into human readable format
     *
     * @param int $bytes
     * @return string
     */
    private function format_bytes($bytes) {
        if ($bytes == 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($bytes, 1024);
        $index = floor($base);
        
        return round(pow(1024, $base - $index), 2) . ' ' . $units[$index];
    }
}

// Initialize and run the database manager
$manager = new Askro_Database_Manager();
$manager->run();

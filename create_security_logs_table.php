<?php
/**
 * Create Security Logs Table Script
 *
 * This script creates the missing wp_askro_security_logs table
 *
 * @package    Askro
 * @since      1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Load WordPress if not already loaded
    if (!function_exists('wp_install')) {
        require_once dirname(__FILE__) . '/../../../wp-load.php';
    }
}

// Ensure we have access to WordPress functions
if (!function_exists('dbDelta')) {
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
}

/**
 * Create security logs table
 */
function create_security_logs_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'askro_security_logs';
    
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
    
    // Check if table was created successfully
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
    
    if ($table_exists) {
        echo "✅ تم إنشاء جدول سجلات الأمان بنجاح: $table_name\n";
        echo "📊 عدد الأعمدة: " . $wpdb->get_var("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table_name'") . "\n";
    } else {
        echo "❌ فشل في إنشاء جدول سجلات الأمان\n";
        echo "🔍 تفاصيل الخطأ: " . $wpdb->last_error . "\n";
    }
}

// Run the script
echo "🚀 بدء إنشاء جدول سجلات الأمان...\n";
create_security_logs_table();
echo "✅ انتهى تنفيذ السكريبت\n"; 

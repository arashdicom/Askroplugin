<?php
/**
 * Create Askro Database Tables
 * 
 * This script creates all the custom tables needed for the Askro plugin
 * including comments, votes, badges, and other features.
 */

// WordPress path
$wp_path = 'C:/Users/willi/Local Sites/plugin/app/public';
require_once($wp_path . '/wp-config.php');
require_once($wp_path . '/wp-includes/wp-db.php');
require_once($wp_path . '/wp-admin/includes/upgrade.php');

// Include the Askro database class
require_once(__DIR__ . '/includes/classes/class-database.php');

echo "Starting Askro Database Creation Process...\n\n";

try {
    // Create database instance
    $database = new Askro_Database();
    
    // Check current table status
    echo "Checking current table status:\n";
    $status = $database->get_table_status();
    
    foreach ($status as $key => $table_info) {
        $exists_text = $table_info['exists'] ? 'EXISTS' : 'MISSING';
        $count_text = $table_info['exists'] ? " ({$table_info['count']} records)" : '';
        echo "- {$table_info['name']}: {$exists_text}{$count_text}\n";
    }
    
    echo "\nCreating missing tables...\n";
    
    // Create all tables
    $database->create_tables();
    
    // Create default data
    echo "Creating default data...\n";
    $database->create_default_data();
    
    // Check status after creation
    echo "\nChecking table status after creation:\n";
    $status_after = $database->get_table_status();
    
    foreach ($status_after as $key => $table_info) {
        $exists_text = $table_info['exists'] ? 'EXISTS' : 'MISSING';
        $count_text = $table_info['exists'] ? " ({$table_info['count']} records)" : '';
        echo "- {$table_info['name']}: {$exists_text}{$count_text}\n";
    }
    
    // Update the database version option
    update_option('askro_db_version', '1.0.0');
    
    echo "\n✅ Database creation completed successfully!\n";
    echo "All Askro tables have been created and populated with default data.\n";
    
} catch (Exception $e) {
    echo "\n❌ Error occurred during database creation:\n";
    echo $e->getMessage() . "\n";
    
    if ($wpdb->last_error) {
        echo "WordPress Database Error: " . $wpdb->last_error . "\n";
    }
    
    exit(1);
}

echo "\nYou can now test the Askro plugin features:\n";
echo "- Comments system should work\n";
echo "- Voting system should work  \n";
echo "- Badges and achievements should work\n";
echo "- User points system should work\n\n";
?>

<?php
// Include necessary WordPress functions
require_once(ABSPATH . 'wp-load.php');
require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

// Optimize all database tables defined in the Askro_Database class
function optimize_askro_tables() {
    global $wpdb;
    
    // List of tables to optimize
    $tables = array(
        'askro_user_votes',  // Replace with actual table names
        'askro_comments',    // Example table names
        'askro_replies',     
        // add other tables...
    );

    foreach ($tables as $table) {
        $table_name = $wpdb->prefix . $table;
        $wpdb->query("OPTIMIZE TABLE $table_name");
        if ($wpdb->last_error) {
            echo "Error optimizing table $table_name: " . $wpdb->last_error . "\n";
        } else {
            echo "Table $table_name optimized successfully.\n";
        }
    }
}

// Run the optimization
optimize_askro_tables();
?>

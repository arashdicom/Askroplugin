<?php
// Database Analysis Script
// تحليل مشاكل قاعدة البيانات

// Database connection details for Local by Flywheel
$host = 'localhost';
$port = 10005;
$database = 'local';
$username = 'root';
$password = 'root';

try {
    // Create connection
    $mysqli = new mysqli($host, $username, $password, $database, $port);
    
    // Check connection
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }
    
    echo "✓ Database connection successful!\n";
    echo "Database: {$database} on {$host}:{$port}\n\n";
    
    // Get all tables
    echo "=== البحث عن جميع الجداول المرتبطة بـ askro ===\n";
    $result = $mysqli->query("SHOW TABLES");
    
    $askro_tables = [];
    $askrow_tables = [];
    $wp_askro_tables = [];
    $wp_askrow_tables = [];
    
    while ($row = $result->fetch_array()) {
        $table_name = $row[0];
        
        if (strpos($table_name, 'askro') !== false) {
            if (strpos($table_name, 'wp_askro') === 0) {
                $wp_askro_tables[] = $table_name;
            } elseif (strpos($table_name, 'askro') === 0) {
                $askro_tables[] = $table_name;
            } else {
                $askro_tables[] = $table_name;
            }
        }
        
        if (strpos($table_name, 'askrow') !== false) {
            if (strpos($table_name, 'wp_askrow') === 0) {
                $wp_askrow_tables[] = $table_name;
            } elseif (strpos($table_name, 'askrow') === 0) {
                $askrow_tables[] = $table_name;
            } else {
                $askrow_tables[] = $table_name;
            }
        }
    }
    
    echo "\n📊 النتائج:\n";
    echo "wp_askro tables: " . count($wp_askro_tables) . "\n";
    echo "wp_askrow tables: " . count($wp_askrow_tables) . "\n";
    echo "askro tables: " . count($askro_tables) . "\n";
    echo "askrow tables: " . count($askrow_tables) . "\n\n";
    
    // Show wp_askro tables
    if (!empty($wp_askro_tables)) {
        echo "🔵 wp_askro tables:\n";
        foreach ($wp_askro_tables as $table) {
            $count_result = $mysqli->query("SELECT COUNT(*) as count FROM {$table}");
            $count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
            echo "  - {$table} ({$count} rows)\n";
        }
        echo "\n";
    }
    
    // Show wp_askrow tables
    if (!empty($wp_askrow_tables)) {
        echo "🟢 wp_askrow tables:\n";
        foreach ($wp_askrow_tables as $table) {
            $count_result = $mysqli->query("SELECT COUNT(*) as count FROM {$table}");
            $count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
            echo "  - {$table} ({$count} rows)\n";
        }
        echo "\n";
    }
    
    // Show other askro tables
    if (!empty($askro_tables)) {
        echo "🔶 Other askro tables:\n";
        foreach ($askro_tables as $table) {
            $count_result = $mysqli->query("SELECT COUNT(*) as count FROM {$table}");
            $count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
            echo "  - {$table} ({$count} rows)\n";
        }
        echo "\n";
    }
    
    // Show other askrow tables
    if (!empty($askrow_tables)) {
        echo "🟡 Other askrow tables:\n";
        foreach ($askrow_tables as $table) {
            $count_result = $mysqli->query("SELECT COUNT(*) as count FROM {$table}");
            $count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
            echo "  - {$table} ({$count} rows)\n";
        }
        echo "\n";
    }
    
    // Check for duplicates
    echo "=== البحث عن التكرارات ===\n";
    $all_askro_related = array_merge($wp_askro_tables, $wp_askrow_tables, $askro_tables, $askrow_tables);
    
    // Group by base name
    $base_names = [];
    foreach ($all_askro_related as $table) {
        $base = str_replace(['wp_askro_', 'wp_askrow_', 'askro_', 'askrow_'], '', $table);
        if (!isset($base_names[$base])) {
            $base_names[$base] = [];
        }
        $base_names[$base][] = $table;
    }
    
    $duplicates_found = false;
    foreach ($base_names as $base => $tables) {
        if (count($tables) > 1) {
            $duplicates_found = true;
            echo "⚠️  تكرار للجدول '{$base}':\n";
            foreach ($tables as $table) {
                $count_result = $mysqli->query("SELECT COUNT(*) as count FROM {$table}");
                $count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
                echo "  - {$table} ({$count} rows)\n";
            }
            echo "\n";
        }
    }
    
    if (!$duplicates_found) {
        echo "✅ لم يتم العثور على تكرارات\n\n";
    }
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

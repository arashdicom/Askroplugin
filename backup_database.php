<?php
/**
 * Database Backup Script
 * سكريپت عمل نسخة احتياطية قبل التنظيف
 */

// Database connection details
$host = 'localhost';
$port = 10005;
$database = 'local';
$username = 'root';
$password = 'root';

class DatabaseBackup {
    private $mysqli;
    private $backup_dir;
    
    public function __construct($mysqli) {
        $this->mysqli = $mysqli;
        $this->backup_dir = __DIR__ . '/backups';
        
        // Create backup directory if it doesn't exist
        if (!is_dir($this->backup_dir)) {
            mkdir($this->backup_dir, 0755, true);
        }
    }
    
    public function createBackup() {
        $timestamp = date('Y-m-d_H-i-s');
        
        echo "📦 إنشاء نسخة احتياطية...\n";
        echo "الوقت: $timestamp\n";
        echo str_repeat("=", 40) . "\n\n";
        
        // 1. Get all askro-related tables
        $askro_tables = $this->getAskroTables();
        
        // 2. Create individual table backups
        $this->backupIndividualTables($askro_tables, $timestamp);
        
        // 3. Create SQL dump of important data
        $this->createSQLDump($askro_tables, $timestamp);
        
        // 4. Create summary file
        $this->createBackupSummary($askro_tables, $timestamp);
        
        echo "✅ تم إنشاء النسخة الاحتياطية بنجاح!\n";
        echo "مجلد النسخ الاحتياطية: {$this->backup_dir}\n\n";
    }
    
    private function getAskroTables() {
        $tables = [];
        $result = $this->mysqli->query("SHOW TABLES LIKE '%askro%'");
        
        while ($row = $result->fetch_array()) {
            $table_name = $row[0];
            $count_result = $this->mysqli->query("SELECT COUNT(*) as count FROM {$table_name}");
            $count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
            
            $tables[] = [
                'name' => $table_name,
                'count' => $count,
                'has_data' => $count > 0
            ];
        }
        
        return $tables;
    }
    
    private function backupIndividualTables($tables, $timestamp) {
        echo "📋 إنشاء نسخ احتياطية للجداول الفردية:\n";
        echo str_repeat("-", 45) . "\n";
        
        $table_backup_dir = $this->backup_dir . "/tables_$timestamp";
        if (!is_dir($table_backup_dir)) {
            mkdir($table_backup_dir, 0755, true);
        }
        
        foreach ($tables as $table) {
            if ($table['has_data']) {
                echo "نسخ {$table['name']} ({$table['count']} صف)... ";
                
                $filename = $table_backup_dir . "/{$table['name']}.json";
                $this->exportTableToJSON($table['name'], $filename);
                
                echo "✅ تم\n";
            } else {
                echo "تخطي {$table['name']} (فارغ)\n";
            }
        }
        
        echo "\n";
    }
    
    private function exportTableToJSON($table_name, $filename) {
        try {
            // Get table structure
            $structure = [];
            $columns_result = $this->mysqli->query("DESCRIBE `$table_name`");
            while ($column = $columns_result->fetch_assoc()) {
                $structure[] = $column;
            }
            
            // Get table data
            $data = [];
            $data_result = $this->mysqli->query("SELECT * FROM `$table_name`");
            while ($row = $data_result->fetch_assoc()) {
                $data[] = $row;
            }
            
            // Create backup object
            $backup = [
                'table_name' => $table_name,
                'backup_time' => date('Y-m-d H:i:s'),
                'structure' => $structure,
                'data' => $data,
                'row_count' => count($data)
            ];
            
            // Save to file
            file_put_contents($filename, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            
        } catch (Exception $e) {
            echo "خطأ: " . $e->getMessage() . "\n";
        }
    }
    
    private function createSQLDump($tables, $timestamp) {
        echo "🗄️ إنشاء SQL dump:\n";
        echo str_repeat("-", 20) . "\n";
        
        $sql_file = $this->backup_dir . "/askro_dump_$timestamp.sql";
        $sql_content = "-- Askro Plugin Database Backup\n";
        $sql_content .= "-- Created: " . date('Y-m-d H:i:s') . "\n";
        $sql_content .= "-- Database: local\n\n";
        
        $sql_content .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            if ($table['has_data']) {
                echo "معالجة {$table['name']}... ";
                
                try {
                    // Get CREATE TABLE statement
                    $create_result = $this->mysqli->query("SHOW CREATE TABLE `{$table['name']}`");
                    if ($create_result) {
                        $create_row = $create_result->fetch_assoc();
                        $sql_content .= "-- Table: {$table['name']}\n";
                        $sql_content .= "DROP TABLE IF EXISTS `{$table['name']}`;\n";
                        $sql_content .= $create_row['Create Table'] . ";\n\n";
                        
                        // Get INSERT statements
                        if ($table['count'] > 0) {
                            $data_result = $this->mysqli->query("SELECT * FROM `{$table['name']}`");
                            if ($data_result && $data_result->num_rows > 0) {
                                $sql_content .= "-- Data for table {$table['name']}\n";
                                
                                while ($row = $data_result->fetch_assoc()) {
                                    $columns = array_keys($row);
                                    $values = array_map(function($value) {
                                        return $value === null ? 'NULL' : "'" . $this->mysqli->real_escape_string($value) . "'";
                                    }, array_values($row));
                                    
                                    $sql_content .= "INSERT INTO `{$table['name']}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $values) . ");\n";
                                }
                                $sql_content .= "\n";
                            }
                        }
                        
                        echo "✅ تم\n";
                    }
                } catch (Exception $e) {
                    echo "خطأ: " . $e->getMessage() . "\n";
                }
            }
        }
        
        $sql_content .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        file_put_contents($sql_file, $sql_content);
        echo "✅ تم حفظ SQL dump في: " . basename($sql_file) . "\n\n";
    }
    
    private function createBackupSummary($tables, $timestamp) {
        echo "📊 إنشاء ملخص النسخة الاحتياطية:\n";
        echo str_repeat("-", 35) . "\n";
        
        $summary = [
            'backup_info' => [
                'timestamp' => $timestamp,
                'date' => date('Y-m-d H:i:s'),
                'total_tables' => count($tables),
                'tables_with_data' => count(array_filter($tables, function($t) { return $t['has_data']; })),
                'total_rows' => array_sum(array_column($tables, 'count'))
            ],
            'tables' => $tables,
            'restoration_instructions' => [
                'sql_restore' => "mysql -h localhost --port=10005 -u root -proot local < askro_dump_$timestamp.sql",
                'individual_restore' => "استخدم ملفات JSON في مجلد tables_$timestamp لاستعادة جداول محددة"
            ]
        ];
        
        $summary_file = $this->backup_dir . "/backup_summary_$timestamp.json";
        file_put_contents($summary_file, json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        echo "✅ تم حفظ الملخص في: " . basename($summary_file) . "\n";
        
        // Display summary
        echo "\n📈 ملخص النسخة الاحتياطية:\n";
        echo "- إجمالي الجداول: " . $summary['backup_info']['total_tables'] . "\n";
        echo "- الجداول التي تحتوي على بيانات: " . $summary['backup_info']['tables_with_data'] . "\n";
        echo "- إجمالي الصفوف: " . number_format($summary['backup_info']['total_rows']) . "\n\n";
    }
}

try {
    // Create connection
    $mysqli = new mysqli($host, $username, $password, $database, $port);
    
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }
    
    echo "✓ Database connection successful!\n\n";
    
    // Create backup
    $backup = new DatabaseBackup($mysqli);
    $backup->createBackup();
    
    $mysqli->close();
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

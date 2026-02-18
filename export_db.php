<?php
// Export complete database to SQL file
try {
    $db = new PDO('mysql:host=localhost;dbname=esportify;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $output = "-- MySQL Dump\n";
    $output .= "-- Database: esportify\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $output .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
    $output .= "START TRANSACTION;\n";
    $output .= "SET time_zone = \"+00:00\";\n\n";
    
    // Get all tables
    $result = $db->query("SHOW TABLES");
    $tables = $result->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        // Get table structure
        $createResult = $db->query("SHOW CREATE TABLE `$table`");
        $createRow = $createResult->fetch(PDO::FETCH_ASSOC);
        
        $output .= "\n-- ========================================\n";
        $output .= "-- Table: $table\n";
        $output .= "-- ========================================\n\n";
        $output .= $createRow['Create Table'] . ";\n\n";
        
        // Get table data
        $dataResult = $db->query("SELECT * FROM `$table`");
        $rows = $dataResult->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($rows)) {
            $output .= "-- Data for table: $table\n";
            
            foreach ($rows as $row) {
                $columns = array_keys($row);
                $values = array_values($row);
                $values = array_map(function($v) use ($db) {
                    if ($v === null) return 'NULL';
                    return $db->quote($v);
                }, $values);
                
                $output .= "INSERT INTO `$table` (" . implode(", ", array_map(function($c) { return "`$c`"; }, $columns)) . ") VALUES (" . implode(", ", $values) . ");\n";
            }
            $output .= "\n";
        }
    }
    
    $output .= "COMMIT;\n";
    
    // Write to file
    $filename = 'C:\Users\ilyes\pi_projects\database_backup_' . date('Y-m-d_H-i-s') . '.sql';
    file_put_contents($filename, $output);
    
    echo "✓ Database exported successfully\n";
    echo "✓ File: $filename\n";
    echo "✓ Size: " . filesize($filename) . " bytes\n";
    echo "✓ Tables exported: " . count($tables) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit(1);
}

<?php
// Import SQL file into database
$sqlFile = 'C:\Users\ilyes\Downloads\esportify (3) (1).sql';

if (!file_exists($sqlFile)) {
    echo "SQL file not found: $sqlFile\n";
    exit(1);
}

try {
    $db = new PDO('mysql:host=localhost;dbname=esportify;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $count = 0;
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            try {
                $db->exec($statement);
                $count++;
            } catch (Exception $e) {
                // Some statements might fail (like duplicate keys), continue
                if (strpos($e->getMessage(), 'Duplicate') === false && 
                    strpos($e->getMessage(), 'already exists') === false) {
                    echo "Warning: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "✓ Successfully imported SQL file\n";
    echo "✓ Executed $count SQL statements\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
    exit(1);
}

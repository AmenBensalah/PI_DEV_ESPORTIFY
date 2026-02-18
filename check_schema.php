<?php
// Quick script to check if max_members column exists
try {
    $db = new PDO('mysql:host=localhost;dbname=esportify', 'root', '');
    $stmt = $db->query('SHOW COLUMNS FROM equipe');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Equipe table columns:\n";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    // Check specifically for max_members
    $hasMaxMembers = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'max_members') {
            $hasMaxMembers = true;
            break;
        }
    }
    
    echo "\nmax_members column exists: " . ($hasMaxMembers ? "YES" : "NO") . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

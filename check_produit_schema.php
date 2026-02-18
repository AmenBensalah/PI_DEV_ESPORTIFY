<?php
// Check if statut column exists in produit table
try {
    $db = new PDO('mysql:host=localhost;dbname=esportify', 'root', '');
    $stmt = $db->query('SHOW COLUMNS FROM produit');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Produit table columns:\n";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
    
    // Check specifically for statut
    $hasStatut = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'statut') {
            $hasStatut = true;
            break;
        }
    }
    
    echo "\nstatut column exists: " . ($hasStatut ? "YES" : "NO") . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

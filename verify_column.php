<?php
$pdo = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', 'root', '');
$pdo->exec('USE esportify');

$columns = $pdo->query('DESCRIBE user')->fetchAll(PDO::FETCH_ASSOC);
echo "User table columns:\n";
echo "─" . str_repeat("─", 40) . "\n";

$found = false;
foreach ($columns as $col) {
    $field = $col['Field'];
    $type = $col['Type'];
    $null = $col['Null'];
    
    if ($field === 'face_descriptor') {
        echo "✓ $field ($type) - FOUND!\n";
        $found = true;
    } else {
        echo "  $field ($type)\n";
    }
}

echo "─" . str_repeat("─", 40) . "\n";
if ($found) {
    echo "\n✅ face_descriptor column successfully added!\n";
} else {
    echo "\n❌ face_descriptor column NOT found\n";
}
?>

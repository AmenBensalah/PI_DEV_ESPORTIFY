<?php
// load_dump.php
$dsn = 'mysql:host=127.0.0.1;dbname=esportify;charset=utf8mb4';
$username = 'root';
$password = '';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = file_get_contents('dump.sql');
    
    // Execute the SQL dump
    // Note: PDO::exec might not support multiple statements depending on driver settings,
    // but for MySQL it often works if emulated prepares are on (default) or if it's just raw SQL.
    // Ideally we'd split but let's try this first.
    $pdo->exec($sql);
    echo "SQL dump imported successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=esportify', 'root', '');
    $stmt = $pdo->query('SHOW TABLES');
    while ($row = $stmt->fetch(PDO::FETCH_COLUMN)) {
        echo $row . "\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}

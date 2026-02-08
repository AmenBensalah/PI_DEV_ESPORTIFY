<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=esportify', 'root', '');
$pdo->exec('DROP TABLE IF EXISTS messenger_messages');
echo 'Table messenger_messages supprimée' . PHP_EOL;
$pdo->exec('DROP TABLE IF EXISTS doctrine_migration_versions');
echo 'Table doctrine_migration_versions supprimée' . PHP_EOL;
?>

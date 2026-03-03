<?php
$pdo = new PDO('mysql:host=localhost;port=3306;dbname=esportify;charset=utf8mb4','root','', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$rows = $pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_COLLATION <> 'utf8mb4_unicode_ci'")->fetchAll(PDO::FETCH_COLUMN);
foreach ($rows as $table) {
    $sql = sprintf("ALTER TABLE `%s` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", str_replace('`','``',$table));
    $pdo->exec($sql);
    echo "altered: $table\n";
}
echo 'count=' . count($rows) . PHP_EOL;

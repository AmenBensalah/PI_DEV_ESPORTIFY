<?php
// Script pour importer le dump SQL esportify

$host = '127.0.0.1';
$user = 'root';
$password = '';
$database = 'esportify';

$connection = new mysqli($host, $user, $password, $database);

if ($connection->connect_error) {
    die("Erreur de connexion: " . $connection->connect_error);
}

$sql_file = 'c:\\Users\\ilyes\\Downloads\\esportify.sql';

if (!file_exists($sql_file)) {
    die("Fichier SQL non trouvé: $sql_file");
}

$sql_content = file_get_contents($sql_file);

// Diviser le contenu en requêtes individuelles
$queries = array_filter(explode(';', $sql_content), function($query) {
    return trim($query) !== '';
});

$executed = 0;
$skipped = 0;

foreach ($queries as $query) {
    $query = trim($query);
    
    // Sauter les commentaires et les SET
    if (empty($query) || strpos($query, '/*') === 0 || strpos($query, '--') === 0 || strpos($query, 'SET') === 0 || strpos($query, '/*!') === 0) {
        $skipped++;
        continue;
    }
    
    if ($connection->query($query) === TRUE) {
        $executed++;
        echo "✓ Requête exécutée ($executed)\n";
    } else {
        echo "✗ Erreur: " . $connection->error . "\n";
        echo "Requête: " . substr($query, 0, 100) . "...\n";
    }
}

echo "\n=== Import terminé ===\n";
echo "Requêtes exécutées: $executed\n";
echo "Requêtes sautées: $skipped\n";

$connection->close();
?>

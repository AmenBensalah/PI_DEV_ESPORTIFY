<?php
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

$apiKey = (string) ($_ENV['GEMINI_API_KEY'] ?? getenv('GEMINI_API_KEY') ?? '');
if ($apiKey === '' && is_file(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || !str_starts_with($line, 'GEMINI_API_KEY=')) {
            continue;
        }
        $apiKey = trim((string) substr($line, strlen('GEMINI_API_KEY=')));
        break;
    }
}

if ($apiKey === '') {
    throw new RuntimeException('GEMINI_API_KEY introuvable. Ajoutez-la dans .env ou en variable d environnement.');
}

$client = HttpClient::create();

try {
    $response = $client->request('GET', 'https://generativelanguage.googleapis.com/v1beta/models?key=' . $apiKey);
    $data = $response->toArray();

    echo "Available Models:\n";
    foreach ($data['models'] as $model) {
        // Output just the name, e.g., models/gemini-pro
        echo $model['name'] . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    if (method_exists($e, 'getResponse')) {
        echo "Response: " . $e->getResponse()->getContent(false) . "\n";
    }
}

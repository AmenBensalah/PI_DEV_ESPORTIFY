<?php
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

$apiKey = 'AIzaSyDEDqijlfqBlJxEpPFxfA0wvE16T6PBBqg';
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

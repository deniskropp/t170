<?php

require_once __DIR__ . '/vendor/autoload.php';

use MultiPersona\Services\MistralClient;
use Dotenv\Dotenv;

// Load .env if it exists
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$apiKey = getenv('MISTRAL_API_KEY');

if (!$apiKey) {
    echo "⚠️  MISTRAL_API_KEY not found in environment.\n";
    echo "Please set it to run live tests.\n";
    // We can still test instantiation
    $client = new MistralClient('dummy_key');
    echo "✅ MistralClient instantiated successfully.\n";
    exit(0);
}

echo "🔑 Found API Key, attempting connection...\n";

$client = new MistralClient($apiKey);

// Test 1: Simple Generation
echo "\nTest 1: Simple Generation\n";
$response = $client->generateResponse("Say 'Hello, World!'");

if ($response['success']) {
    echo "✅ Success: " . $response['content'] . "\n";
} else {
    echo "❌ Error: " . $response['error'] . "\n";
}

// Test 2: Embedding
echo "\nTest 2: Embedding Generation\n";
$embeddingResponse = $client->generateResponse("Hello", ['type' => 'embedding']);

if ($embeddingResponse['success']) {
    echo "✅ Success: Generated embedding with " . count($embeddingResponse['response']['embedding']) . " dimensions.\n";
} else {
    echo "❌ Error: " . $embeddingResponse['error'] . "\n";
}

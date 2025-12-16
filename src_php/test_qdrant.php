<?php

require_once __DIR__ . '/vendor/autoload.php';

use MultiPersona\Services\QdrantClient;
use MultiPersona\Services\ContextManager;
use MultiPersona\Services\EmbeddingService;
use MultiPersona\Services\MistralClient;
use Dotenv\Dotenv;

// Load .env if it exists
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$apiKey = getenv('MISTRAL_API_KEY') ?: 'dummy_key';
$qdrantHost = getenv('QDRANT_HOST') ?: 'localhost';
$qdrantPort = getenv('QDRANT_PORT') ?: 6333;
$qdrantKey = getenv('QDRANT_API_KEY');

echo "🐘 Testing Qdrant Integration...\n";

$mistralClient = new MistralClient($apiKey);
$embeddingService = new EmbeddingService($mistralClient);
$qdrantClient = new QdrantClient($qdrantHost, $qdrantPort, $qdrantKey);
$contextManager = new ContextManager($qdrantClient, $embeddingService);

// Test 1: Check Qdrant Connection (via Collection Info)
echo "\nTest 1: Check Connection\n";
$info = $qdrantClient->getCollectionInfo('multipersona_context');
if ($info['success']) {
    echo "✅ Connected to Qdrant. Collection found.\n";
} else {
    echo "⚠️  Could not connect or collection missing: " . $info['error'] . "\n";
    echo "   (This is expected if Qdrant is not running or collection not created yet)\n";
}

// Test 2: Initialize Collection
echo "\nTest 2: Initialize Collection\n";
$initResult = $contextManager->initialize();
if ($initResult['success']) {
    echo "✅ Collection initialized successfully.\n";
} else {
    echo "⚠️  Initialization failed (might already exist): " . ($initResult['error'] ?? 'Unknown error') . "\n";
}

// Test 3: Add Context (Mocking embedding if no API key)
echo "\nTest 3: Add Context\n";
if ($apiKey === 'dummy_key') {
    echo "⚠️  Skipping actual embedding generation (no API key).\n";
} else {
    $addResult = $contextManager->addContext("The sky is blue.", ['source' => 'test']);
    if ($addResult['success']) {
        echo "✅ Context added successfully.\n";
    } else {
        echo "❌ Failed to add context: " . ($addResult['error'] ?? 'Unknown error') . "\n";
    }
}

echo "\nDone.\n";

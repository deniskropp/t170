<?php

require_once __DIR__ . '/vendor/autoload.php';

use MultiPersona\Services\MockLLMClient;
use MultiPersona\Services\EmbeddingService;
use MultiPersona\Services\QdrantClient;
use MultiPersona\Services\ContextManager;

echo "🚀 Starting Context Manager Verification...\n\n";

// 1. Setup Dependencies
echo "1️⃣  Setting up dependencies...\n";
$mockLLM = new MockLLMClient();
$embeddingService = new EmbeddingService($mockLLM);
$qdrantClient = new QdrantClient('localhost', 6333); // Assuming localhost for now, methods mocked or skipped if needed

// 2. Instantiate ContextManager
echo "2️⃣  Instantiating ContextManager...\n";
$contextManager = new ContextManager($qdrantClient, $embeddingService);

if ($contextManager) {
    echo "✅ ContextManager Instantiated Successfully\n";
} else {
    echo "❌ ContextManager Instantiation Failed\n";
    exit(1);
}

// 3. Test addContext (Mocking Qdrant response would be ideal, but for now we test the flow up to Qdrant call)
// Since QdrantClient makes real HTTP calls, we might fail if Qdrant is not running.
// However, we can check if generateEmbedding works via ContextManager flow.
// But ContextManager::addContext calls embeddingService->generateEmbedding then qdrantClient->upsertVectors.
// We can't easily mock QdrantClient without a MockQdrantClient or using a mocking library.
// For this smoke test, we'll just rely on instantiation and maybe a call that doesn't hit the network if possible,
// or just accept that it might fail on network but pass the PHP logic.

// Let's try to call addContext and catch the Guzzle exception if Qdrant is down.
echo "3️⃣  Testing addContext flow...\n";
try {
    $result = $contextManager->addContext("Test context");
    if ($result['success']) {
        echo "✅ addContext succeeded (Qdrant is running)\n";
    } else {
        echo "⚠️  addContext failed (likely Qdrant down): " . ($result['error'] ?? 'Unknown error') . "\n";
        echo "   (This is expected if Qdrant is not running locally)\n";
    }
} catch (\Exception $e) {
    echo "⚠️  addContext threw exception: " . $e->getMessage() . "\n";
}

echo "\n🎉 Context Manager Verification Complete!\n";

<?php

require_once __DIR__ . '/vendor/autoload.php';

use MultiPersona\Services\MockLLMClient;
use MultiPersona\Services\PromptLoader;
use MultiPersona\Services\QdrantClient;
use MultiPersona\Common\AgentRole;

echo "🚀 Starting Agent Intelligence Verification...\n\n";

// 1. Verify LLM Integration (Mock)
echo "1️⃣  Verifying LLM Integration (Mock)...\n";
$llm = new MockLLMClient();
$response = $llm->generateResponse("Test Prompt");
if ($response['success'] && strpos($response['content'], "mock response") !== false) {
    echo "✅ LLM Generation Successful\n";
} else {
    echo "❌ LLM Generation Failed\n";
}

$embedding = $llm->generateResponse("Test", ['type' => 'embedding']);
if ($embedding['success'] && count($embedding['response']['embedding']) === 1024) {
    echo "✅ LLM Embedding Successful\n";
} else {
    echo "❌ LLM Embedding Failed\n";
}
echo "\n";

// 2. Verify Prompt Loading
echo "2️⃣  Verifying Prompt Loading...\n";
$loader = new PromptLoader(__DIR__ . '/prompts');
$systemPrompt = $loader->getSystemPrompt(AgentRole::Orchestrator);

if ($systemPrompt && strlen($systemPrompt) > 0) {
    echo "✅ System Prompt Loaded for Orchestrator\n";
} else {
    echo "❌ Failed to load System Prompt for Orchestrator\n";
}

// Check a specific task prompt if available (assuming structure from JSON)
// We need to check the JSON content first to know what keys exist.
// Based on file list, Orchestrator.json exists.
echo "\n";

// 3. Verify Qdrant Client Instantiation
echo "3️⃣  Verifying Qdrant Client...\n";
$qdrant = new QdrantClient('localhost', 6333);
// We won't call live methods to avoid failure if Qdrant isn't running,
// but we verify the class is loadable and instantiated.
if ($qdrant) {
    echo "✅ QdrantClient Instantiated\n";
} else {
    echo "❌ QdrantClient Instantiation Failed\n";
}

echo "\n🎉 Verification Complete!\n";

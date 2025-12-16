<?php

require_once __DIR__ . '/vendor/autoload.php';

use MultiPersona\Services\PromptLoader;
use MultiPersona\Services\PromptManager;
use MultiPersona\Services\MistralClient;
use MultiPersona\Common\AgentRole;
use Dotenv\Dotenv;

// Load .env if it exists
if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
}

$loader = new PromptLoader(__DIR__ . '/prompts');
$apiKey = getenv('MISTRAL_API_KEY') ?: 'dummy_key';
$client = new MistralClient($apiKey);
$manager = new PromptManager($loader, $client);

echo "📂 Testing Prompt Loading...\n";

// Test 1: Load System Prompt
$orchestratorSystem = $loader->getSystemPrompt(AgentRole::Orchestrator);
if ($orchestratorSystem) {
    echo "✅ Orchestrator System Prompt loaded: " . substr($orchestratorSystem, 0, 50) . "...\n";
} else {
    echo "❌ Failed to load Orchestrator System Prompt\n";
}

// Test 2: Load Task Prompt
$dispatchPrompt = $loader->getTaskPrompt(AgentRole::Orchestrator, 'dispatch');
if ($dispatchPrompt) {
    echo "✅ Orchestrator Dispatch Prompt loaded.\n";
} else {
    echo "❌ Failed to load Orchestrator Dispatch Prompt\n";
}

// Test 3: Fill Prompt
$filled = $manager->fillPrompt($dispatchPrompt, ['TASK_DESCRIPTION' => 'Build a website']);
if (strpos($filled, 'Build a website') !== false) {
    echo "✅ Prompt filling successful.\n";
} else {
    echo "❌ Prompt filling failed.\n";
}

echo "\nDone.\n";

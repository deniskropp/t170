<?php

require_once __DIR__ . '/vendor/autoload.php';

use MultiPersona\Infrastructure\NtfyService;
use MultiPersona\Services\MetricCollector;
use MultiPersona\Services\AnomalyDetector;
use MultiPersona\Services\EthicalReviewer;
use MultiPersona\Services\TranslatorEngine;
use MultiPersona\Services\SchemaRegistry;
use MultiPersona\Services\FeedbackAnalyzer;
use MultiPersona\Services\PromptOptimizer;
use MultiPersona\Core\TaskOrchestrator;
use MultiPersona\Core\TaskScheduler;
use MultiPersona\Core\TaskPrioritizer;
use MultiPersona\Core\Dispatcher;
use MultiPersona\Core\TaskManager;
use MultiPersona\Core\AgentRegistry;
use MultiPersona\Common\MetricPoint;
use MultiPersona\Common\AnomalyRule;
use MultiPersona\Common\EthicalReviewRequest;
use MultiPersona\Common\TranslationRequest;
use MultiPersona\Common\FeedbackRecord;
use MultiPersona\Common\TaskRecord;
use MultiPersona\Common\TaskStatus;
use MultiPersona\Common\AgentRole;

echo "Starting Phase 8 Verification...\n";

// 1. NtfyService
echo "Testing NtfyService... ";
$ntfy = new NtfyService();
if ($ntfy instanceof NtfyService) {
    echo "OK\n";
} else {
    echo "FAILED\n";
}

// 2. MetricCollector & AnomalyDetector
echo "Testing MetricCollector & AnomalyDetector... ";
$collector = new MetricCollector();
$detector = new AnomalyDetector();
$collector->collect(new MetricPoint('cpu_usage', 95, [], new \DateTime()));
$detector->addRule(new AnomalyRule('cpu_usage', '>', 90, 60, 'Warning'));
$anomalies = $detector->detect($collector->getMetrics());
if (count($anomalies) === 1 && $anomalies[0]->metricName === 'cpu_usage') {
    echo "OK\n";
} else {
    echo "FAILED\n";
}

// 3. EthicalReviewer
echo "Testing EthicalReviewer... ";
$reviewer = new EthicalReviewer();
$result = $reviewer->review(new EthicalReviewRequest('1', 'This is a safe message.', 'Pre-Execution'));
$resultUnsafe = $reviewer->review(new EthicalReviewRequest('2', 'This contains harm.', 'Pre-Execution'));
if ($result->approved && !$resultUnsafe->approved) {
    echo "OK\n";
} else {
    echo "FAILED\n";
}

// 4. TranslatorEngine
echo "Testing TranslatorEngine... ";
$translator = new TranslatorEngine();
$transResult = $translator->translate(new TranslationRequest('Hello'));
if (isset($transResult->kicklang['task']) && $transResult->kicklang['task'] === 'Hello') {
    echo "OK\n";
} else {
    echo "FAILED\n";
}

// 5. SchemaRegistry
echo "Testing SchemaRegistry... ";
$registry = new SchemaRegistry();
$registry->registerSchema('user', ['name' => 'string', 'age' => 'int']);
if ($registry->validate('user', ['name' => 'John', 'age' => 30]) && !$registry->validate('user', ['name' => 'John'])) {
    echo "OK\n";
} else {
    echo "FAILED\n";
}

// 6. FeedbackAnalyzer
echo "Testing FeedbackAnalyzer... ";
$analyzer = new FeedbackAnalyzer();
$analysis = $analyzer->analyze(new FeedbackRecord('1', 2, 'Bad result', new \DateTime()));
if ($analysis['sentiment'] === 'negative') {
    echo "OK\n";
} else {
    echo "FAILED\n";
}

// 7. PromptOptimizer
echo "Testing PromptOptimizer... ";
$optimizer = new PromptOptimizer();
$suggestion = $optimizer->optimize('Do this', []);
if ($suggestion instanceof \MultiPersona\Common\OptimizationSuggestion) {
    echo "OK\n";
} else {
    echo "FAILED\n";
}

// 8. Task Core (Orchestrator, Scheduler, Prioritizer)
echo "Testing Task Core... ";
// Mock dependencies for Orchestrator
$db = new \MultiPersona\Infrastructure\DatabaseService(sys_get_temp_dir() . '/test_db_' . uniqid());
$eventQueue = new \MultiPersona\Infrastructure\EventifyQueue();
$taskManager = new TaskManager($db, $eventQueue);
$agentRegistry = new AgentRegistry($db, $eventQueue);

$dispatcher = new Dispatcher($taskManager, $agentRegistry, $eventQueue, $db);
$orchestrator = new TaskOrchestrator($dispatcher, $taskManager);
$scheduler = new TaskScheduler();
$prioritizer = new TaskPrioritizer();

$task = new TaskRecord(
    '1',
    'Test Task',
    'desc',
    'type',
    TaskStatus::Pending,
    3,
    [],
    null,
    [],
    new \DateTime(),
    new \DateTime(),
    []
);
$scheduler->schedule($task, time() + 3600);
$due = $scheduler->getDueTasks(time() + 3601);

$tasks = [
    new TaskRecord('1', 'Low Task', 'desc', 'type', TaskStatus::Pending, 1, [], null, [], new \DateTime(), new \DateTime(), []),
    new TaskRecord('2', 'High Task', 'desc', 'type', TaskStatus::Pending, 3, [], null, [], new \DateTime(), new \DateTime(), [])
];
$prioritized = $prioritizer->prioritize($tasks);

if ($orchestrator instanceof TaskOrchestrator && count($due) === 1 && $prioritized[0]->priority === 3) {
    echo "OK\n";
} else {
    echo "FAILED\n";
    echo "Orchestrator check: " . ($orchestrator instanceof TaskOrchestrator ? 'OK' : 'FAIL') . "\n";
    echo "Due count: " . count($due) . " (Expected 1)\n";
    echo "Top priority: " . $prioritized[0]->priority . " (Expected 3)\n";
}

echo "Verification Complete.\n";

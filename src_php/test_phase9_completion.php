<?php

require_once __DIR__ . '/vendor/autoload.php';

use MultiPersona\Agents\OrchestratorAgent;
use MultiPersona\Agents\CodeinAgent;
use MultiPersona\Agents\DimaAgent;
use MultiPersona\Agents\KickLaMettaAgent;
use MultiPersona\Agents\SystemMonitorAgent;
use MultiPersona\Common\AgentProfile;
use MultiPersona\Common\AgentRole;
use MultiPersona\Common\TaskRecord;
use MultiPersona\Common\TaskStatus;
use MultiPersona\Infrastructure\DatabaseService;
use MultiPersona\Infrastructure\EventifyQueue;
use MultiPersona\Services\MistralClient;
use MultiPersona\Services\EthicalReviewer;
use MultiPersona\Services\MockLLMClient;
use MultiPersona\Services\TranslatorEngine;
use MultiPersona\Services\MetricCollector;
use MultiPersona\Services\AnomalyDetector;
use MultiPersona\Api\ApiServer;
use MultiPersona\Api\Http\Request;
use MultiPersona\Console\ConsoleApplication;
use MultiPersona\Core\TaskManager;
use MultiPersona\Core\AgentRegistry;
use MultiPersona\Core\Dispatcher;

echo "Starting Phase 9 Verification...\n";

// Mock dependencies
$db = new DatabaseService(sys_get_temp_dir() . '/test_db_phase9_' . uniqid());
$queue = new EventifyQueue();
$llmClient = new MockLLMClient();
$taskManager = new TaskManager($db, $queue);
$agentRegistry = new AgentRegistry($db, $queue);
$dispatcher = new Dispatcher($taskManager, $agentRegistry, $queue, $db);

// 1. Verify Agents
echo "Testing Agents...\n";

// OrchestratorAgent
$orchProfile = new AgentProfile('orch-1', AgentRole::Orchestrator, [], 'Idle', null, new \DateTime(), false);
$orchAgent = new OrchestratorAgent($orchProfile, $db, $queue, 'sys prompt');
if ($orchAgent instanceof OrchestratorAgent)
    echo "  OrchestratorAgent: OK\n";
else
    echo "  OrchestratorAgent: FAILED\n";

// CodeinAgent
$codeinProfile = new AgentProfile('codein-1', AgentRole::Codein, [], 'Idle', null, new \DateTime(), false);
$codeinAgent = new CodeinAgent($codeinProfile, $db, $queue, 'sys prompt', $llmClient);
if ($codeinAgent instanceof CodeinAgent)
    echo "  CodeinAgent: OK\n";
else
    echo "  CodeinAgent: FAILED\n";

// DimaAgent
$dimaProfile = new AgentProfile('dima-1', AgentRole::Dima, [], 'Idle', null, new \DateTime(), false);
$reviewer = new EthicalReviewer();
$dimaAgent = new DimaAgent($dimaProfile, $db, $queue, 'sys prompt', $reviewer);
if ($dimaAgent instanceof DimaAgent)
    echo "  DimaAgent: OK\n";
else
    echo "  DimaAgent: FAILED\n";

// KickLaMettaAgent
$kickProfile = new AgentProfile('kick-1', AgentRole::KickLaMetta, [], 'Idle', null, new \DateTime(), false);
$translator = new TranslatorEngine();
$kickAgent = new KickLaMettaAgent($kickProfile, $db, $queue, 'sys prompt', $translator);
if ($kickAgent instanceof KickLaMettaAgent)
    echo "  KickLaMettaAgent: OK\n";
else
    echo "  KickLaMettaAgent: FAILED\n";

// SystemMonitorAgent
$monProfile = new AgentProfile('mon-1', AgentRole::SystemMonitor, [], 'Idle', null, new \DateTime(), false);
$collector = new MetricCollector();
$detector = new AnomalyDetector();
$monAgent = new SystemMonitorAgent($monProfile, $db, $queue, 'sys prompt', $collector, $detector);
if ($monAgent instanceof SystemMonitorAgent)
    echo "  SystemMonitorAgent: OK\n";
else
    echo "  SystemMonitorAgent: FAILED\n";

// 2. Verify REST API
echo "Testing REST API...\n";
$apiServer = new ApiServer($taskManager, $agentRegistry, $dispatcher);

// Test GET /tasks
$req = new Request(['REQUEST_METHOD' => 'GET', 'PATH_INFO' => '/tasks'], [], [], '');
$res = $apiServer->handleRequest($req);
if ($res->getStatusCode() === 200)
    echo "  GET /tasks: OK\n";
else
    echo "  GET /tasks: FAILED (" . $res->getStatusCode() . ")\n";

// Test GET /agents
$req = new Request(['REQUEST_METHOD' => 'GET', 'PATH_INFO' => '/agents'], [], [], '');
$res = $apiServer->handleRequest($req);
if ($res->getStatusCode() === 200)
    echo "  GET /agents: OK\n";
else
    echo "  GET /agents: FAILED (" . $res->getStatusCode() . ")\n";

// Test GET /system
$req = new Request(['REQUEST_METHOD' => 'GET', 'PATH_INFO' => '/system'], [], [], '');
$res = $apiServer->handleRequest($req);
if ($res->getStatusCode() === 200)
    echo "  GET /system: OK\n";
else
    echo "  GET /system: FAILED (" . $res->getStatusCode() . ")\n";

// 3. Verify CLI
echo "Testing CLI...\n";
$app = new ConsoleApplication($taskManager, $agentRegistry, $dispatcher);
if ($app instanceof ConsoleApplication)
    echo "  ConsoleApplication: OK\n";
else
    echo "  ConsoleApplication: FAILED\n";

echo "Verification Complete.\n";

<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MultiPersona\Core\TaskManager;
use MultiPersona\Core\Dispatcher;
use MultiPersona\Core\AgentRegistry;
use MultiPersona\Infrastructure\DatabaseService;
use MultiPersona\Infrastructure\EventifyQueue;
use MultiPersona\Agents\WePlanAgent;
use MultiPersona\Common\AgentProfile;
use MultiPersona\Common\AgentRole;
use MultiPersona\Common\TaskStatus;

echo "=== MultiPersona PHP System Demo ===\n\n";

// Initialize services
$storageDir = __DIR__ . '/../data';
echo "Initializing database at: " . $storageDir . "\n";

$dbService = new DatabaseService($storageDir);
$queue = new EventifyQueue();

// Create core components
echo "Creating core components...\n";
$taskManager = new TaskManager($dbService, $queue);
$agentRegistry = new AgentRegistry($dbService, $queue);
$dispatcher = new Dispatcher($taskManager, $agentRegistry, $queue, $dbService);

// Register agents
echo "Registering agents...\n";

// Create WePlan agent
$wePlanProfile = new AgentProfile(
    'agent-weplan-001',
    AgentRole::WePlan,
    ['strategic_planning', 'task_management', 'resource_optimization'],
    'Idle',
    null,
    new \DateTime()
);

$agentRegistry->register($wePlanProfile);

// Create WePlan agent instance
$wePlanAgent = new WePlanAgent($wePlanProfile, $dbService, $queue);
echo "Registered WePlan agent: " . $wePlanProfile->id . "\n";

// Create some test tasks
echo "\nCreating test tasks...\n";

$task1 = $taskManager->createTask([
    'name' => 'Generate Strategic Plan',
    'description' => 'Create a comprehensive strategic plan for the PHP implementation',
    'type' => 'planning',
    'priority' => 8,
    'assignedTo' => AgentRole::WePlan->value
]);

echo "Created task: " . $task1->name . " (ID: " . $task1->id . ")\n";

$task2 = $taskManager->createTask([
    'name' => 'Resource Optimization',
    'description' => 'Optimize resource allocation for the implementation plan',
    'type' => 'planning',
    'priority' => 6,
    'assignedTo' => AgentRole::WePlan->value,
    'dependencies' => [$task1->id]
]);

echo "Created task: " . $task2->name . " (ID: " . $task2->id . ")\n";

// Process tasks manually (in a real system, the dispatcher would run continuously)
echo "\nProcessing tasks...\n";

// Get ready tasks
$readyTasks = $taskManager->getReadyTasks();
echo "Found " . count($readyTasks) . " ready tasks\n";

foreach ($readyTasks as $task) {
    echo "\nProcessing task: " . $task->name . "\n";
    
    // Dispatch the task
    $dispatchResult = $dispatcher->dispatchTask($task);
    
    if ($dispatchResult['success']) {
        echo "✓ Task dispatched to agent: " . $dispatchResult['agentRole'] . "\n";
        
        // Simulate agent execution
        $agent = $agentRegistry->getAgent($dispatchResult['agentId']);
        if ($agent) {
            $executionResult = $wePlanAgent->executeTask($task);
            
            if ($executionResult['success']) {
                echo "✓ Task executed successfully\n";
                echo "  Duration: " . round($executionResult['result']['duration'], 3) . " seconds\n";
                echo "  Generated " . count($executionResult['result']['plan']['steps']) . " plan steps\n";
                
                // Handle task completion
                $dispatcher->handleTaskCompletion($task->id, $executionResult['result']);
                
                // Show updated task status
                $updatedTask = $taskManager->getTask($task->id);
                echo "  Task status: " . $updatedTask->status->value . "\n";
            } else {
                echo "✗ Task execution failed: " . $executionResult['error'] . "\n";
                $dispatcher->handleTaskFailure($task->id, $executionResult['error']);
            }
        }
    } else {
        echo "✗ Task dispatch failed: " . $dispatchResult['reason'] . "\n";
    }
}

// Check if dependent tasks are now ready
echo "\nChecking for unblocked tasks...\n";
$newReadyTasks = $taskManager->getReadyTasks();
echo "Now have " . count($newReadyTasks) . " ready tasks\n";

// Show system status
echo "\n=== System Status ===\n";
$status = $dispatcher->getSystemStatus();
echo "Task counts:\n";
foreach ($status['taskCounts'] as $statusName => $count) {
    echo "  " . $statusName . ": " . $count . "\n";
}

echo "\nAgent status:\n";
echo "  Total: " . $status['agentStatus']['total'] . "\n";
echo "  Busy: " . $status['agentStatus']['busy'] . "\n";
echo "  Idle: " . $status['agentStatus']['idle'] . "\n";

echo "\n=== Demo Complete ===\n";

// Show a sample of the generated plan
if (isset($executionResult) && $executionResult['success']) {
    echo "\n=== Sample Plan Generated ===\n";
    $plan = $executionResult['result']['plan'];
    echo "Plan ID: " . $plan['plan_id'] . "\n";
    echo "Objective: " . $plan['objective'] . "\n";
    echo "Timeline: " . $plan['timeline']['start'] . " to " . $plan['timeline']['estimated_completion'] . "\n";
    echo "Steps:\n";
    
    $stepCount = min(3, count($plan['steps'])); // Show first 3 steps
    for ($i = 0; $i < $stepCount; $i++) {
        $step = $plan['steps'][$i];
        echo "  " . ($i + 1) . ". " . $step['name'] . "\n";
        echo "     - " . $step['description'] . "\n";
        echo "     - Duration: " . $step['estimated_duration'] . "\n";
    }
    
    if (count($plan['steps']) > 3) {
        echo "  ... and " . (count($plan['steps']) - 3) . " more steps\n";
    }
}
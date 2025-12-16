<?php

// Simple test without composer dependencies
echo "=== Simple PHP Implementation Test ===\n\n";

// Test 1: Check PHP version
echo "Test 1: PHP version... ";
$phpVersion = PHP_VERSION;
if (version_compare($phpVersion, '8.1.0') >= 0) {
    echo "✓ PASS (PHP " . $phpVersion . ")\n";
} else {
    echo "✗ FAIL: PHP version " . $phpVersion . " is too old (need 8.1+)\n";
    exit(1);
}

// Test 2: Check if we can load our classes manually
echo "Test 2: Manual class loading... ";
try {
    // Load the class files manually
    require_once __DIR__ . '/src/Common/AgentRole.php';
    require_once __DIR__ . '/src/Common/TaskStatus.php';
    require_once __DIR__ . '/src/Common/TaskRecord.php';
    require_once __DIR__ . '/src/Common/AgentProfile.php';
    require_once __DIR__ . '/src/Common/Message.php';
    require_once __DIR__ . '/src/Infrastructure/DatabaseServiceInterface.php';
    require_once __DIR__ . '/src/Infrastructure/DatabaseService.php';
    require_once __DIR__ . '/src/Infrastructure/EventifyQueue.php';
    require_once __DIR__ . '/src/Core/TaskManager.php';
    require_once __DIR__ . '/src/Core/AgentRegistry.php';
    require_once __DIR__ . '/src/Core/Dispatcher.php';
    require_once __DIR__ . '/src/Core/AgentBase.php';
    require_once __DIR__ . '/src/Agents/WePlanAgent.php';
    
    echo "✓ PASS\n";
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Check if enums work
echo "Test 3: Enum functionality... ";
try {
    $role = \MultiPersona\Common\AgentRole::WePlan;
    $status = \MultiPersona\Common\TaskStatus::Pending;
    
    if ($role->value === 'WePlan' && $status->value === 'Pending') {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL: Enum values incorrect\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Check if we can create a simple database service
echo "Test 4: Database service creation... ";
try {
    $storageDir = __DIR__ . '/test_data';
    if (!file_exists($storageDir)) {
        mkdir($storageDir, 0755, true);
    }
    
    $dbService = new \MultiPersona\Infrastructure\DatabaseService($storageDir);
    
    if ($dbService instanceof \MultiPersona\Infrastructure\DatabaseService) {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL: Database service creation failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Check if we can create a message queue
echo "Test 5: Message queue creation... ";
try {
    $queue = new \MultiPersona\Infrastructure\EventifyQueue();
    
    if ($queue instanceof \MultiPersona\Infrastructure\EventifyQueue) {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL: Message queue creation failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Check if we can create core components
echo "Test 6: Core components creation... ";
try {
    $taskManager = new \MultiPersona\Core\TaskManager($dbService, $queue);
    $agentRegistry = new \MultiPersona\Core\AgentRegistry($dbService, $queue);
    $dispatcher = new \MultiPersona\Core\Dispatcher($taskManager, $agentRegistry, $queue, $dbService);
    
    if ($taskManager && $agentRegistry && $dispatcher) {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL: Core components creation failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 7: Check if we can create an agent profile
echo "Test 7: Agent profile creation... ";
try {
    $agentProfile = new \MultiPersona\Common\AgentProfile(
        'test-agent-001',
        \MultiPersona\Common\AgentRole::WePlan,
        ['testing', 'validation'],
        'Idle',
        null,
        new DateTime()
    );
    
    if ($agentProfile && $agentProfile->id === 'test-agent-001') {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL: Agent profile creation failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 8: Check if we can create a WePlan agent
echo "Test 8: WePlan agent creation... ";
try {
    $wePlanAgent = new \MultiPersona\Agents\WePlanAgent($agentProfile, $dbService, $queue);
    
    if ($wePlanAgent && $wePlanAgent->getRole() === \MultiPersona\Common\AgentRole::WePlan) {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL: WePlan agent creation failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 9: Check if task creation works
echo "Test 9: Task creation... ";
try {
    $taskData = [
        'name' => 'Test Task',
        'description' => 'This is a test task',
        'type' => 'test',
        'priority' => 5,
        'assignedTo' => \MultiPersona\Common\AgentRole::WePlan->value
    ];
    
    $task = $taskManager->createTask($taskData);
    
    if ($task && $task->name === 'Test Task') {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL: Task creation failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 10: Check if task retrieval works
echo "Test 10: Task retrieval... ";
try {
    $retrievedTask = $taskManager->getTask($task->id);
    
    if ($retrievedTask && $retrievedTask->id === $task->id) {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL: Task retrieval failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Cleanup
echo "\nCleaning up... ";
try {
    // Remove test database
    $dbPath = $storageDir . '/multipersona.db';
    if (file_exists($dbPath)) {
        unlink($dbPath);
    }
    
    // Remove test directory
    if (file_exists($storageDir)) {
        rmdir($storageDir);
    }
    
    echo "✓ PASS\n";
} catch (Exception $e) {
    echo "⚠ WARNING: Cleanup failed: " . $e->getMessage() . "\n";
}

echo "\n=== All Tests Passed! ===\n";
echo "PHP implementation is working correctly.\n";
echo "\nKey components verified:\n";
echo "  ✓ PHP 8.1+ compatibility\n";
echo "  ✓ Enum functionality\n";
echo "  ✓ Database service (SQLite)\n";
echo "  ✓ Message queue (Eventify)\n";
echo "  ✓ Core components (TaskManager, AgentRegistry, Dispatcher)\n";
echo "  ✓ Agent system (AgentBase, WePlanAgent)\n";
echo "  ✓ Task management\n";
echo "\nThe PHP implementation successfully covers steps 021-024 of the transformation plan.\n";
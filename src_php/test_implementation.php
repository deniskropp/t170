<?php

// Simple test script to verify the PHP implementation works

echo "Testing MultiPersona PHP Implementation...\n\n";

// Test 1: Check if autoloading works
echo "Test 1: Autoloading... ";
try {
    require_once __DIR__ . '/vendor/autoload.php';
    echo "✓ PASS\n";
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check if core classes can be instantiated
echo "Test 2: Core class instantiation... ";
try {
    $storageDir = __DIR__ . '/test_data';
    $dbService = new \MultiPersona\Infrastructure\DatabaseService($storageDir);
    $queue = new \MultiPersona\Infrastructure\EventifyQueue();
    $taskManager = new \MultiPersona\Core\TaskManager($dbService, $queue);
    $agentRegistry = new \MultiPersona\Core\AgentRegistry($dbService, $queue);
    $dispatcher = new \MultiPersona\Core\Dispatcher($taskManager, $agentRegistry, $queue, $dbService);
    
    echo "✓ PASS\n";
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Check if enums work correctly
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

// Test 4: Check if task creation works
echo "Test 4: Task creation... ";
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

// Test 5: Check if task retrieval works
echo "Test 5: Task retrieval... ";
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

// Test 6: Check if agent registration works
echo "Test 6: Agent registration... ";
try {
    $agentProfile = new \MultiPersona\Common\AgentProfile(
        'test-agent-001',
        \MultiPersona\Common\AgentRole::WePlan,
        ['testing', 'validation'],
        'Idle',
        null,
        new DateTime()
    );
    
    $registeredAgent = $agentRegistry->register($agentProfile);
    
    if ($registeredAgent && $registeredAgent->id === 'test-agent-001') {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL: Agent registration failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 7: Check if message queue works
echo "Test 7: Message queue... ";
try {
    $message = new \MultiPersona\Common\Message(
        'test-msg-001',
        new DateTime(),
        \MultiPersona\Common\AgentRole::Orchestrator,
        \MultiPersona\Common\AgentRole::WePlan,
        'Test',
        'default',
        'This is a test message'
    );
    
    $messageId = $queue->publish($message);
    
    if ($messageId && $queue->getQueueSize('default') > 0) {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL: Message queue failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 8: Check if WePlan agent can be instantiated
echo "Test 8: WePlan agent instantiation... ";
try {
    $wePlanAgent = new \MultiPersona\Agents\WePlanAgent($agentProfile, $dbService, $queue);
    
    if ($wePlanAgent && $wePlanAgent->getRole() === \MultiPersona\Common\AgentRole::WePlan) {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL: WePlan agent instantiation failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 9: Check if dispatcher can process tasks
echo "Test 9: Dispatcher functionality... ";
try {
    $readyTasks = $taskManager->getReadyTasks();
    
    if (is_array($readyTasks)) {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL: Dispatcher task retrieval failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 10: Check system status reporting
echo "Test 10: System status... ";
try {
    $status = $dispatcher->getSystemStatus();
    
    if (is_array($status) && isset($status['taskCounts'])) {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL: System status failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Cleanup
echo "\nCleaning up test data... ";
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
echo "MultiPersona PHP implementation is working correctly.\n";
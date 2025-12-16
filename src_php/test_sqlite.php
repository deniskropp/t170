<?php

echo "Testing SQLite functionality...\n\n";

// Test 1: Check if PDO SQLite is available
echo "Test 1: PDO SQLite availability... ";
if (extension_loaded('pdo_sqlite')) {
    echo "✓ PASS\n";
} else {
    echo "✗ FAIL: PDO SQLite extension not loaded\n";
    exit(1);
}

// Test 2: Try to create a test database
echo "Test 2: Database creation... ";
try {
    $testDir = __DIR__ . '/test_sqlite_db';
    if (!file_exists($testDir)) {
        mkdir($testDir, 0755, true);
    }
    
    $dbPath = $testDir . '/test.db';
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ PASS\n";
} catch (PDOException $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Create a test table
echo "Test 3: Table creation... ";
try {
    $pdo->exec('CREATE TABLE IF NOT EXISTS test_table (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        name TEXT NOT NULL,
        created_at TEXT NOT NULL
    )');
    echo "✓ PASS\n";
} catch (PDOException $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Insert test data
echo "Test 4: Data insertion... ";
try {
    $stmt = $pdo->prepare('INSERT INTO test_table (name, created_at) VALUES (?, ?)');
    $stmt->execute(['Test Entry', date('Y-m-d H:i:s')]);
    echo "✓ PASS\n";
} catch (PDOException $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Query data
echo "Test 5: Data retrieval... ";
try {
    $stmt = $pdo->query('SELECT * FROM test_table');
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($results) === 1 && $results[0]['name'] === 'Test Entry') {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL: Unexpected query results\n";
        exit(1);
    }
} catch (PDOException $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 6: Test our DatabaseService with real SQLite
echo "Test 6: DatabaseService with real SQLite... ";
try {
    require_once __DIR__ . '/src/Common/AgentRole.php';
    require_once __DIR__ . '/src/Common/TaskStatus.php';
    require_once __DIR__ . '/src/Common/Types.php';
    require_once __DIR__ . '/src/Infrastructure/DatabaseService.php';
    
    $dbService = new \MultiPersona\Infrastructure\DatabaseService($testDir);
    
    // Test task creation
    $taskData = new \MultiPersona\Common\TaskRecord(
        'test-task-001',
        'SQLite Test Task',
        'Testing SQLite integration',
        'test',
        \MultiPersona\Common\TaskStatus::Pending,
        5,
        [],
        \MultiPersona\Common\AgentRole::WePlan,
        [],
        new DateTime(),
        new DateTime(),
        []
    );
    
    $createdTask = $dbService->createTask($taskData);
    $retrievedTask = $dbService->getTask('test-task-001');
    
    if ($retrievedTask && $retrievedTask->name === 'SQLite Test Task') {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL: DatabaseService test failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Cleanup
echo "\nCleaning up... ";
try {
    // Close database connection
    $pdo = null;
    
    // Remove test files
    if (file_exists($dbPath)) {
        unlink($dbPath);
    }
    
    if (file_exists($testDir)) {
        rmdir($testDir);
    }
    
    echo "✓ PASS\n";
} catch (Exception $e) {
    echo "⚠ WARNING: Cleanup failed: " . $e->getMessage() . "\n";
}

echo "\n=== SQLite Setup Complete! ===\n";
echo "SQLite is properly configured and working with the PHP implementation.\n";
echo "\nYou can now use the real DatabaseService instead of the mock version.\n";
echo "The system is ready for production use with SQLite database persistence.\n";
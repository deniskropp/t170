<?php

require_once __DIR__ . '/src/Infrastructure/NtfyService.php';

use MultiPersona\Infrastructure\NtfyService;

echo "=== NtfyService Test ===\n\n";

// Test 1: NtfyService instantiation
echo "Test 1: NtfyService instantiation... ";
try {
    $ntfyService = new NtfyService(); // No EventifyQueue needed in constructor

    if ($ntfyService instanceof NtfyService) {
        echo "✓ PASS\n";
    } else {
        echo "✗ FAIL: NtfyService instantiation failed\n";
        exit(1);
    }
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Send a dummy notification (assuming success for now)
echo "Test 2: Sending dummy notification... ";
try {
    $topic = 'test_topic';
    $message = 'Hello from NtfyService test!';
    $priority = 'default';

    // Call the correct method with correct parameters
    $notificationSent = $ntfyService->send($message, $topic, $priority);

    if ($notificationSent) { 
        echo "✓ PASS (Notification call initiated. Actual delivery not verified without mocking/server.)\n";
    } else {
        echo "✗ FAIL: Send notification returned false or an unexpected value.\n";
        exit(1);
    }

} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n";
    exit(1);
}


echo "\n=== NtfyService Tests Complete ===\n";

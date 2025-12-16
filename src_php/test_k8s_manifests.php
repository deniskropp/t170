<?php

$dir = __DIR__ . '/k8s';
$files = [
    'configmap.yaml',
    'secret.yaml',
    'qdrant.yaml',
    'deployment.yaml',
    'service.yaml',
    'hpa.yaml',
    'ingress.yaml'
];

echo "Verifying Kubernetes Manifests...\n";

$allOk = true;

foreach ($files as $file) {
    $path = $dir . '/' . $file;
    if (!file_exists($path)) {
        echo "❌ Missing: $file\n";
        $allOk = false;
        continue;
    }

    $content = file_get_contents($path);
    if (empty($content)) {
        echo "❌ Empty: $file\n";
        $allOk = false;
        continue;
    }

    if (strpos($content, 'apiVersion:') === false || strpos($content, 'kind:') === false) {
        echo "❌ Invalid content (missing apiVersion or kind): $file\n";
        $allOk = false;
        continue;
    }

    echo "✅ OK: $file\n";
}

if ($allOk) {
    echo "\nAll manifests verified successfully.\n";
    exit(0);
} else {
    echo "\nVerification failed.\n";
    exit(1);
}

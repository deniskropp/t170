<?php

return [
    'database' => [
        'storage_dir' => __DIR__ . '/../data',
        'backup_dir' => __DIR__ . '/../data/backups'
    ],
    'logging' => [
        'level' => \Monolog\Logger::DEBUG,
        'file' => __DIR__ . '/../log/app.log'
    ],
    'llm' => [
        'api_key' => getenv('MISTRAL_API_KEY'),
        'endpoint' => 'https://api.mistral.ai/v1'
    ],
    'qdrant' => [
        'host' => 'localhost',
        'port' => 6333,
        'api_key' => null
    ],
    'agents' => [
        'default' => [
            'WePlan',
            'Orchestrator',
            'Codein',
            'Dima'
        ],
        'ephemeral' => [
            'PromptEngineer',
            'KickLaMetta'
        ]
    ]
];

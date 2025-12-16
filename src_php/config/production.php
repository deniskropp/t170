<?php

return [
    'database' => [
        'storage_dir' => '/var/www/multipersona/data',
        'backup_dir' => '/var/www/multipersona/backups'
    ],
    'logging' => [
        'level' => \Monolog\Logger::INFO,
        'file' => '/var/www/multipersona/logs/app.log'
    ],
    'llm' => [
        'api_key' => getenv('MISTRAL_API_KEY'),
        'endpoint' => 'https://api.mistral.ai/v1'
    ],
    'qdrant' => [
        'host' => getenv('QDRANT_HOST') ?: 'localhost',
        'port' => getenv('QDRANT_PORT') ?: 6333,
        'api_key' => getenv('QDRANT_API_KEY')
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

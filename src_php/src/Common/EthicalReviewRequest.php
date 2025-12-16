<?php

namespace MultiPersona\Common;

class EthicalReviewRequest
{
    public function __construct(
        public string $taskId,
        public string $context,
        public string $stage // 'Pre-Execution' | 'Post-Execution'
    ) {}
}
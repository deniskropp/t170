<?php

namespace MultiPersona\Common;

class EthicalReviewResult
{
    public function __construct(
        public bool $approved,
        public int $score,
        public array $concerns,
        public string $feedback
    ) {}
}
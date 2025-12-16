<?php

namespace MultiPersona\Common;

class OptimizationSuggestion
{
    public function __construct(
        public AgentRole $role,
        public string $currentPrompt,
        public string $suggestedPrompt,
        public string $reasoning,
        public float $confidence
    ) {}
}
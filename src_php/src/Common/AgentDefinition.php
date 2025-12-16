<?php

namespace MultiPersona\Common;

class AgentDefinition
{
    public function __construct(
        public AgentRole $role,
        public string $mission,
        public array $responsibilities,
        public array $constraints,
        public string $systemPrompt,
        public ?array $capabilities = null,
        public bool $isEphemeral = false
    ) {}
}
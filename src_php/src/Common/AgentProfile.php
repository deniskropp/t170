<?php

namespace MultiPersona\Common;

class AgentProfile
{
    public function __construct(
        public string $id,
        public AgentRole $role,
        public array $capabilities,
        public string $status, // 'Idle' | 'Busy' | 'Offline'
        public ?string $currentTaskId,
        public \DateTime $lastActive,
        public bool $isEphemeral = false
    ) {}
}
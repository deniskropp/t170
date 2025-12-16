<?php

namespace MultiPersona\Common;

class TaskRecord
{
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public string $type,
        public TaskStatus $status,
        public int $priority,
        public array $dependencies,
        public ?AgentRole $assignedTo,
        public array $artifacts,
        public \DateTime $createdAt,
        public \DateTime $updatedAt,
        public array $metadata
    ) {}
}
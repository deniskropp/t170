<?php

namespace MultiPersona\Common;

class Message
{
    public function __construct(
        public string $id,
        public \DateTime $timestamp,
        public AgentRole $sender,
        public AgentRole|string $receiver, // AgentRole or 'Broadcast'
        public string $type, // 'Command' | 'Query' | 'Info' | 'Alert'
        public string $channel,
        public string $content,
        public ?string $correlationId = null
    ) {}
}
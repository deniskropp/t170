<?php

namespace MultiPersona\Common;

class Document
{
    public function __construct(
        public string $id,
        public string $content,
        public array $metadata,
        public ?array $embedding = null
    ) {}
}
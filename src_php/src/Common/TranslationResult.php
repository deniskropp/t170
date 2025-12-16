<?php

namespace MultiPersona\Common;

class TranslationResult
{
    public function __construct(
        public bool $success,
        public ?array $kicklang = null,
        public float $confidence,
        public ?string $error = null
    ) {}
}
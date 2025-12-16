<?php

namespace MultiPersona\Common;

class TranslationRequest
{
    public function __construct(
        public string $input,
        public ?array $context = null
    ) {}
}
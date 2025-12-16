<?php

namespace MultiPersona\Common;

class SearchResult
{
    public function __construct(
        public Document $document,
        public float $score
    ) {}
}
<?php

namespace MultiPersona\Common;

class MetricPoint
{
    public function __construct(
        public string $name,
        public int|float|string $value,
        public array $tags,
        public \DateTime $timestamp
    ) {}
}
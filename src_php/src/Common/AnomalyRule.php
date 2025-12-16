<?php

namespace MultiPersona\Common;

class AnomalyRule
{
    public function __construct(
        public string $metricName,
        public string $condition, // 'GT' | 'LT' | 'EQ'
        public int|float $threshold,
        public int $windowSeconds,
        public string $severity // 'Warning' | 'Critical'
    ) {}
}
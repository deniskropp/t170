<?php

namespace MultiPersona\Common;

class Anomaly
{
    public function __construct(
        public string $id,
        public string $metricName,
        public int|float|string $value,
        public int|float $threshold,
        public string $severity, // 'Warning' | 'Critical'
        public \DateTime $timestamp,
        public string $message
    ) {}
}
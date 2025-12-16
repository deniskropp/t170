<?php

namespace MultiPersona\Services;

use MultiPersona\Common\MetricPoint;

class MetricCollector
{
    private array $metrics = [];

    public function collect(MetricPoint $metric): void
    {
        $this->metrics[] = $metric;
    }

    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function getMetricsByName(string $name): array
    {
        return array_filter($this->metrics, fn($m) => $m->name === $name);
    }

    public function clear(): void
    {
        $this->metrics = [];
    }
}

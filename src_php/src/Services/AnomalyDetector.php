<?php

namespace MultiPersona\Services;

use MultiPersona\Common\Anomaly;
use MultiPersona\Common\AnomalyRule;
use MultiPersona\Common\MetricPoint;

class AnomalyDetector
{
    private array $rules = [];

    public function addRule(AnomalyRule $rule): void
    {
        $this->rules[] = $rule;
    }

    public function detect(array $metrics): array
    {
        $anomalies = [];
        foreach ($metrics as $metric) {
            foreach ($this->rules as $rule) {
                if ($this->isAnomaly($metric, $rule)) {
                    $anomalies[] = new Anomaly(
                        uniqid('anomaly_'),
                        $metric->name,
                        $metric->value,
                        $rule->threshold,
                        $rule->severity,
                        $metric->timestamp,
                        "Metric {$metric->name} value {$metric->value} violated rule: {$rule->condition} {$rule->threshold}"
                    );
                }
            }
        }
        return $anomalies;
    }

    private function isAnomaly(MetricPoint $metric, AnomalyRule $rule): bool
    {
        if ($metric->name !== $rule->metricName) {
            return false;
        }

        switch ($rule->condition) {
            case '>':
                return $metric->value > $rule->threshold;
            case '<':
                return $metric->value < $rule->threshold;
            case '>=':
                return $metric->value >= $rule->threshold;
            case '<=':
                return $metric->value <= $rule->threshold;
            case '==':
                return $metric->value == $rule->threshold;
            default:
                return false;
        }
    }
}

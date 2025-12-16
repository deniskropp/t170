<?php

namespace MultiPersona\Agents;

use MultiPersona\Core\AgentBase;
use MultiPersona\Common\TaskRecord;
use MultiPersona\Common\AgentProfile;
use MultiPersona\Infrastructure\DatabaseServiceInterface;
use MultiPersona\Infrastructure\EventifyQueue;
use MultiPersona\Services\MetricCollector;
use MultiPersona\Services\AnomalyDetector;

class SystemMonitorAgent extends AgentBase
{
    private MetricCollector $collector;
    private AnomalyDetector $detector;

    public function __construct(
        AgentProfile $profile,
        DatabaseServiceInterface $database,
        EventifyQueue $messageBus,
        string $systemPrompt,
        MetricCollector $collector,
        AnomalyDetector $detector
    ) {
        parent::__construct($profile, $database, $messageBus, $systemPrompt);
        $this->collector = $collector;
        $this->detector = $detector;
    }

    protected function performTaskExecution(TaskRecord $task): array
    {
        $this->logMetric('system_monitor_start', 1, ['taskId' => $task->id]);

        $metrics = $this->collector->getMetrics();
        $anomalies = $this->detector->detect($metrics);

        $output = "System check complete. Found " . count($anomalies) . " anomalies.";

        return [
            'success' => true,
            'output' => $output,
            'artifacts' => [
                'anomalies' => json_encode($anomalies)
            ]
        ];
    }
}

<?php

namespace MultiPersona\Agents;

use MultiPersona\Core\AgentBase;
use MultiPersona\Common\TaskRecord;
use MultiPersona\Common\AgentProfile;
use MultiPersona\Infrastructure\DatabaseServiceInterface;
use MultiPersona\Infrastructure\EventifyQueue;
use MultiPersona\Services\EthicalReviewer;
use MultiPersona\Common\EthicalReviewRequest;

class DimaAgent extends AgentBase
{
    private EthicalReviewer $reviewer;

    public function __construct(
        AgentProfile $profile,
        DatabaseServiceInterface $database,
        EventifyQueue $messageBus,
        string $systemPrompt,
        EthicalReviewer $reviewer
    ) {
        parent::__construct($profile, $database, $messageBus, $systemPrompt);
        $this->reviewer = $reviewer;
    }

    protected function performTaskExecution(TaskRecord $task): array
    {
        $this->logMetric('ethical_review_start', 1, ['taskId' => $task->id]);

        $context = $task->description;
        if (isset($task->metadata['context'])) {
            $context .= "\n" . $task->metadata['context'];
        }

        $request = new EthicalReviewRequest($task->id, $context, 'Pre-Execution');
        $result = $this->reviewer->review($request);

        return [
            'success' => true,
            'output' => $result->approved ? "Approved" : "Rejected",
            'artifacts' => [
                'review_result' => json_encode($result)
            ]
        ];
    }
}

<?php

namespace MultiPersona\Agents;

use MultiPersona\Core\AgentBase;
use MultiPersona\Common\TaskRecord;
use MultiPersona\Common\AgentProfile;
use MultiPersona\Infrastructure\DatabaseServiceInterface;
use MultiPersona\Infrastructure\EventifyQueue;
use MultiPersona\Services\TranslatorEngine;
use MultiPersona\Common\TranslationRequest;

class KickLaMettaAgent extends AgentBase
{
    private TranslatorEngine $translator;

    public function __construct(
        AgentProfile $profile,
        DatabaseServiceInterface $database,
        EventifyQueue $messageBus,
        string $systemPrompt,
        TranslatorEngine $translator
    ) {
        parent::__construct($profile, $database, $messageBus, $systemPrompt);
        $this->translator = $translator;
    }

    protected function performTaskExecution(TaskRecord $task): array
    {
        $this->logMetric('translation_start', 1, ['taskId' => $task->id]);

        $input = $task->description;
        $request = new TranslationRequest($input);
        $result = $this->translator->translate($request);

        return [
            'success' => $result->success,
            'output' => $result->success ? "Translation successful" : "Translation failed",
            'artifacts' => [
                'kicklang_code' => json_encode($result->kicklang)
            ]
        ];
    }
}

<?php

namespace MultiPersona\Agents;

use MultiPersona\Core\AgentBase;
use MultiPersona\Common\TaskRecord;
use MultiPersona\Common\AgentProfile;
use MultiPersona\Infrastructure\DatabaseServiceInterface;
use MultiPersona\Infrastructure\EventifyQueue;
use MultiPersona\Services\LLMInterface;

class CodeinAgent extends AgentBase
{
    private LLMInterface $llmClient;

    public function __construct(
        AgentProfile $profile,
        DatabaseServiceInterface $database,
        EventifyQueue $messageBus,
        string $systemPrompt,
        LLMInterface $llmClient
    ) {
        parent::__construct($profile, $database, $messageBus, $systemPrompt);
        $this->llmClient = $llmClient;
    }

    protected function performTaskExecution(TaskRecord $task): array
    {
        $this->logMetric('code_generation_start', 1, ['taskId' => $task->id]);

        $prompt = "Generate code for the following task:\n" . $task->description;
        if (isset($task->metadata['language'])) {
            $prompt .= "\nLanguage: " . $task->metadata['language'];
        }

        $response = $this->llmClient->generateResponse($prompt);

        if ($response['success']) {
            $code = $response['response']['choices'][0]['message']['content'] ?? '';

            return [
                'success' => true,
                'output' => "Code generated successfully.",
                'artifacts' => [
                    'generated_code' => $code
                ]
            ];
        }

        return [
            'success' => false,
            'error' => $response['error'] ?? 'Unknown error during code generation'
        ];
    }
}

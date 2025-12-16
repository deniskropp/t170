<?php

namespace MultiPersona\Services;

use MultiPersona\Common\AgentRole;

class PromptManager
{
    private $loader;
    private $llmClient;

    public function __construct(PromptLoader $loader, MistralClient $llmClient)
    {
        $this->loader = $loader;
        $this->llmClient = $llmClient;
    }

    public function optimizePrompt(AgentRole $role, string $currentPrompt, array $feedback): string
    {
        $optimizationPrompt = $this->loader->getPrompt(AgentRole::PromptEngineer, 'optimize');

        if (!$optimizationPrompt) {
            return $currentPrompt;
        }

        $fullPrompt = str_replace(
            ['{{ROLE}}', '{{CURRENT_PROMPT}}', '{{FEEDBACK}}'],
            [$role->value, $currentPrompt, json_encode($feedback)],
            $optimizationPrompt
        );

        $result = $this->llmClient->generateResponse($fullPrompt);

        return $result['success'] ? ($result['response']['optimized_prompt'] ?? $result['content']) : $currentPrompt;
    }

    public function fillPrompt(string $template, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', (string) $value, $template);
        }
        return $template;
    }
}

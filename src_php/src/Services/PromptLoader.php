<?php

namespace MultiPersona\Services;

use MultiPersona\Common\AgentRole;

class PromptLoader
{
    private $promptDirectory;
    private $cache = [];

    public function __construct(string $promptDirectory = __DIR__ . '/../../prompts')
    {
        $this->promptDirectory = $promptDirectory;
        $this->loadPrompts();
    }

    public function loadPrompts(): void
    {
        $files = glob($this->promptDirectory . '/*.json');
        foreach ($files as $file) {
            $role = basename($file, '.json');
            $this->cache[$role] = json_decode(file_get_contents($file), true);
        }
    }

    public function getPrompt(AgentRole $role, string $templateName): ?string
    {
        // Handle nested keys using dot notation (e.g., 'tasks.dispatch')
        $keys = explode('.', $templateName);
        $value = $this->cache[$role->value] ?? null;

        foreach ($keys as $key) {
            if (is_array($value) && isset($value[$key])) {
                $value = $value[$key];
            } else {
                return null;
            }
        }

        return is_string($value) ? $value : null;
    }

    public function getSystemPrompt(AgentRole $role): ?string
    {
        return $this->getPrompt($role, 'system');
    }

    public function getTaskPrompt(AgentRole $role, string $taskType): ?string
    {
        return $this->getPrompt($role, 'tasks.' . $taskType);
    }
}

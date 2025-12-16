<?php

namespace MultiPersona\Services;

interface LLMInterface
{
    /**
     * Generate a response from the LLM based on a prompt.
     *
     * @param string $prompt The input prompt
     * @param array $context Additional context or parameters
     * @return array Response structure ['success' => bool, 'response' => array, 'usage' => array|null, 'error' => string|null]
     */
    public function generateResponse(string $prompt, array $context = []): array;

    /**
     * Stream a response from the LLM.
     *
     * @param string $prompt The input prompt
     * @param callable $callback Callback function to handle chunks
     */
    public function streamResponse(string $prompt, callable $callback): void;
}

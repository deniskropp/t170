<?php

namespace MultiPersona\Services;

class MockLLMClient implements LLMInterface
{
    public function generateResponse(string $prompt, array $context = []): array
    {
        // Simulate embedding request
        if (isset($context['type']) && $context['type'] === 'embedding') {
            return [
                'success' => true,
                'response' => [
                    'embedding' => array_fill(0, 1024, 0.1) // Mock 1024-dim embedding
                ],
                'usage' => ['total_tokens' => 10]
            ];
        }

        // Simulate chat completion
        return [
            'success' => true,
            'response' => ['mock_response' => true],
            'content' => "This is a mock response to: " . substr($prompt, 0, 50) . "...",
            'usage' => ['total_tokens' => 20]
        ];
    }

    public function streamResponse(string $prompt, callable $callback): void
    {
        $chunks = ["This ", "is ", "a ", "streamed ", "mock ", "response."];
        foreach ($chunks as $chunk) {
            $callback($chunk);
        }
    }
}

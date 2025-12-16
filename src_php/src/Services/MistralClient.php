<?php

namespace MultiPersona\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class MistralClient implements LLMInterface
{
    private $apiKey;
    private $apiEndpoint;
    private $httpClient;
    private $model;

    public function __construct(string $apiKey, string $endpoint = 'https://api.mistral.ai', string $model = 'mistral-large-latest')
    {
        $this->apiKey = $apiKey;
        $this->apiEndpoint = rtrim($endpoint, '/');
        $this->model = $model;
        $this->httpClient = new Client([
            'timeout' => 60.0,
        ]);
    }

    public function generateResponse(string $prompt, array $context = []): array
    {
        try {
            $payload = [
                'model' => $context['model'] ?? $this->model,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => $context['temperature'] ?? 0.7,
                'max_tokens' => $context['max_tokens'] ?? 2048,
            ];

            // Handle system prompt if provided in context
            if (isset($context['system_prompt'])) {
                array_unshift($payload['messages'], [
                    'role' => 'system',
                    'content' => $context['system_prompt']
                ]);
            }

            // Handle history if provided
            if (isset($context['history']) && is_array($context['history'])) {
                // Prepend history before the current user prompt, but after system prompt
                $messages = [];
                if (isset($payload['messages'][0]) && $payload['messages'][0]['role'] === 'system') {
                    $messages[] = array_shift($payload['messages']);
                }

                foreach ($context['history'] as $msg) {
                    $messages[] = $msg;
                }

                $messages[] = [
                    'role' => 'user',
                    'content' => $prompt
                ];

                $payload['messages'] = $messages;
            }

            // Handle embedding request specifically if type is embedding
            if (isset($context['type']) && $context['type'] === 'embedding') {
                return $this->generateEmbedding($prompt, $context);
            }

            $response = $this->httpClient->post(
                $this->apiEndpoint . '/v1/chat/completions',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'json' => $payload
                ]
            );

            $body = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'response' => $body,
                'content' => $body['choices'][0]['message']['content'] ?? '',
                'usage' => $body['usage'] ?? null
            ];

        } catch (GuzzleException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    private function generateEmbedding(string $input, array $context): array
    {
        try {
            $response = $this->httpClient->post(
                $this->apiEndpoint . '/v1/embeddings',
                [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                    ],
                    'json' => [
                        'model' => $context['model'] ?? 'mistral-embed',
                        'input' => [$input]
                    ]
                ]
            );

            $body = json_decode($response->getBody(), true);

            return [
                'success' => true,
                'response' => [
                    'embedding' => $body['data'][0]['embedding'] ?? []
                ],
                'usage' => $body['usage'] ?? null
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function streamResponse(string $prompt, callable $callback): void
    {
        // TODO: Implement streaming
    }
}

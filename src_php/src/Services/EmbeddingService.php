<?php

namespace MultiPersona\Services;

class EmbeddingService
{
    private $llmClient;

    public function __construct(LLMInterface $llmClient)
    {
        $this->llmClient = $llmClient;
    }

    public function generateEmbedding(string $text): array
    {
        $result = $this->llmClient->generateResponse(
            $text,
            ['type' => 'embedding', 'model' => 'mistral-embed']
        );

        return $result['success'] ? $result['response']['embedding'] : [];
    }
}

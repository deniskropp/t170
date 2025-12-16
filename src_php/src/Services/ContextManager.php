<?php

namespace MultiPersona\Services;

class ContextManager
{
    private $qdrantClient;
    private $embeddingService;
    private $collectionName = 'multipersona_context';
    private $vectorSize = 1024; // Default for mistral-embed

    public function __construct(QdrantClient $qdrantClient, EmbeddingService $embeddingService)
    {
        $this->qdrantClient = $qdrantClient;
        $this->embeddingService = $embeddingService;
    }

    public function initialize(): array
    {
        return $this->qdrantClient->createCollection($this->collectionName, $this->vectorSize);
    }

    public function addContext(string $text, array $metadata = []): array
    {
        $embedding = $this->embeddingService->generateEmbedding($text);

        if (empty($embedding)) {
            return ['success' => false, 'error' => 'Failed to generate embedding'];
        }

        return $this->qdrantClient->upsertVectors($this->collectionName, [
            [
                'id' => $this->generateUuid(),
                'vector' => $embedding,
                'payload' => array_merge($metadata, ['text' => $text])
            ]
        ]);
    }

    public function searchContext(string $query, int $limit = 5): array
    {
        $embedding = $this->embeddingService->generateEmbedding($query);

        if (empty($embedding)) {
            return [];
        }

        $result = $this->qdrantClient->search($this->collectionName, $embedding, $limit);

        return $result['success'] ? ($result['result']['result'] ?? []) : [];
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}

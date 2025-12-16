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
                'id' => uniqid(), // Note: Qdrant prefers UUIDs or integers. uniqid is string.
                // Ideally we should use a UUID generator. For now, let's assume Qdrant accepts string IDs if configured, 
                // but standard Qdrant expects UUID or Int. Let's use a simple hash for now or a proper UUID if we had a library.
                // Let's use md5 hash of uniqid to get a 32-char hex string which is close to UUID but not quite.
                // Actually, let's just use a random integer for simplicity in this demo, or a proper UUID v4 function.
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

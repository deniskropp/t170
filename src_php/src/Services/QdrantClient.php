<?php

namespace MultiPersona\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class QdrantClient
{
    private $host;
    private $port;
    private $apiKey;
    private $httpClient;

    public function __construct(
        string $host = 'localhost',
        int $port = 6333,
        string $apiKey = null
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->apiKey = $apiKey;
        $this->httpClient = new Client([
            'base_uri' => "http://{$this->host}:{$this->port}",
            'headers' => $apiKey ? ['api-key' => $apiKey] : [],
            'timeout' => 5.0
        ]);
    }

    public function createCollection(string $name, int $vectorSize): array
    {
        try {
            $response = $this->httpClient->put("/collections/{$name}", [
                'json' => [
                    'vectors' => ['size' => $vectorSize, 'distance' => 'Cosine']
                ]
            ]);

            return ['success' => true, 'result' => json_decode($response->getBody(), true)];
        } catch (GuzzleException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function upsertVectors(string $collection, array $vectors): array
    {
        try {
            $response = $this->httpClient->put("/collections/{$collection}/points", [
                'json' => ['points' => $vectors]
            ]);

            return ['success' => true, 'result' => json_decode($response->getBody(), true)];
        } catch (GuzzleException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function search(string $collection, array $vector, int $limit = 5): array
    {
        try {
            $response = $this->httpClient->post("/collections/{$collection}/points/search", [
                'json' => [
                    'vector' => $vector,
                    'limit' => $limit
                ]
            ]);

            return ['success' => true, 'result' => json_decode($response->getBody(), true)];
        } catch (GuzzleException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function getCollectionInfo(string $name): array
    {
        try {
            $response = $this->httpClient->get("/collections/{$name}");
            return ['success' => true, 'result' => json_decode($response->getBody(), true)];
        } catch (GuzzleException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}

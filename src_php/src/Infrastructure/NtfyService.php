<?php

namespace MultiPersona\Infrastructure;

class NtfyService
{
    private string $baseUrl;
    private string $defaultTopic;

    public function __construct(string $baseUrl = 'https://ntfy.violass.club', string $defaultTopic = 'my_app_alerts')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->defaultTopic = $defaultTopic;
    }

    public function send(string $message, ?string $topic = null, string $priority = 'default'): bool
    {
        $topic = $topic ?? $this->defaultTopic;
        $url = "{$this->baseUrl}/{$topic}";

        $headers = [];
        if ($priority !== 'default') {
            $headers[] = "Priority: {$priority}";
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }
}

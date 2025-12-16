<?php

namespace MultiPersona\Api\Http;

class Response
{
    private int $statusCode;
    private array $headers;
    private string $content;

    public function __construct(int $statusCode = 200, array $headers = [], string $content = '')
    {
        $this->statusCode = $statusCode;
        $this->headers = $headers;
        $this->content = $content;
    }

    public function send(): void
    {
        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        echo $this->content;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}

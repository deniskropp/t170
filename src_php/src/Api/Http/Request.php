<?php

namespace MultiPersona\Api\Http;

class Request
{
    private array $server;
    private array $get;
    private array $post;
    private string $content;

    public function __construct(array $server, array $get, array $post, string $content)
    {
        $this->server = $server;
        $this->get = $get;
        $this->post = $post;
        $this->content = $content;
    }

    public static function createFromGlobals(): self
    {
        return new self(
            $_SERVER,
            $_GET,
            $_POST,
            file_get_contents('php://input')
        );
    }

    public function getMethod(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    public function getPathInfo(): string
    {
        return $this->server['PATH_INFO'] ?? '/';
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getQuery(string $key, $default = null)
    {
        return $this->get[$key] ?? $default;
    }
}

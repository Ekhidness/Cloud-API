<?php
declare(strict_types=1);

namespace App\Core;

readonly class Request
{
    public string $method;
    public string $uri;
    public array $headers;
    public array $body;
    public array $files;
    public ?string $token;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD']);
        $this->uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
        $this->headers = function_exists('getallheaders') ? getallheaders() : $_SERVER;
        $this->token = $this->extractToken();
        $this->files = $_FILES;
        $this->body = $this->parseBody();
    }

    private function extractToken(): ?string
    {
        $auth = $this->headers['Authorization'] ?? $this->headers['HTTP_AUTHORIZATION'] ?? '';
        if (str_starts_with($auth, 'Bearer ')) {
            return substr($auth, 7);
        }
        return null;
    }

    private function parseBody(): array
    {
        $contentType = $this->headers['Content-Type'] ?? $this->headers['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $input = file_get_contents('php://input');
            return json_decode($input, true) ?? [];
        }
        return $_POST;
    }

    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }
}
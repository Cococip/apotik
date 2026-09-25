<?php

namespace App\Core;

class Request
{
    private array $query;
    private array $body;
    private array $files;
    private array $server;
    private array $params = [];
    private ?array $jsonBody = null;

    public function __construct()
    {
        $this->query = $_GET;
        $this->body = $_POST;
        $this->files = $_FILES;
        $this->server = $_SERVER;
    }

    public function method(): string
    {
        $method = $this->server['REQUEST_METHOD'] ?? 'GET';

        if ($method === 'POST' && isset($this->body['_method'])) {
            $override = strtoupper($this->body['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'], true)) {
                $method = $override;
            }
        }

        return $method;
    }

    public function uri(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';
        return rtrim($path, '/') === '' ? '/' : rtrim($path, '/');
    }

    public function isJson(): bool
    {
        $contentType = $this->server['CONTENT_TYPE'] ?? $this->server['HTTP_CONTENT_TYPE'] ?? '';
        return str_contains($contentType, 'application/json');
    }

    public function jsonBody(): array
    {
        if ($this->jsonBody === null) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw ?: '[]', true);
            $this->jsonBody = is_array($decoded) ? $decoded : [];
        }

        return $this->jsonBody;
    }

    public function all(): array
    {
        if ($this->isJson()) {
            return array_merge($this->query, $this->jsonBody());
        }

        return array_merge($this->query, $this->body);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->all()[$key] ?? $default;
    }

    public function query(string $key, mixed $default = null): mixed
    {
        return $this->query[$key] ?? $default;
    }

    public function file(string $key): ?array
    {
        if (!isset($this->files[$key]) || $this->files[$key]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        return $this->files[$key];
    }

    public function setParams(array $params): void
    {
        $this->params = $params;
    }

    public function param(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function header(string $key): ?string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
        return $this->server[$key] ?? null;
    }

    public function ip(): string
    {
        return $this->server['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return $this->server['HTTP_USER_AGENT'] ?? '';
    }

    public function only(array $keys): array
    {
        return array_merge(array_fill_keys($keys, null), array_intersect_key($this->all(), array_flip($keys)));
    }
}

<?php

namespace App\Core;

class Response
{
    public static function json(array $data, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function success(mixed $data = null, string $message = 'Berhasil', int $statusCode = 200): void
    {
        self::json([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'errors' => [],
        ], $statusCode);
    }

    public static function error(string $message = 'Terjadi kesalahan', string $status = 'error', int $statusCode = 400, array $errors = []): void
    {
        self::json([
            'success' => false,
            'status' => $status,
            'message' => $message,
            'data' => null,
            'errors' => $errors,
        ], $statusCode);
    }

    public static function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    public static function download(string $path, string $filename): void
    {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public static function notFound(): void
    {
        http_response_code(404);
        require dirname(__DIR__) . '/Views/errors/404.php';
        exit;
    }

    public static function forbidden(): void
    {
        http_response_code(403);
        require dirname(__DIR__) . '/Views/errors/403.php';
        exit;
    }
}

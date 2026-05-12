<?php
declare(strict_types=1);

namespace App\Core;

class Response
{
    public static function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        if ($status !== 204) {
            echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        exit;
    }

    public static function file(string $path, string $name): void
    {
        if (!file_exists($path)) {
            self::json(['message' => 'Not found', 'code' => 404], 404);
        }
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($name) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($path));
        readfile($path);
        exit;
    }

    public static function validationError(array $errors, int $code = 422): void
    {
        self::json([
            'success' => false,
            'code' => $code,
            'message' => $errors
        ], $code);
    }

    public static function forbidden(string $message = 'Forbidden for you'): void
    {
        self::json(['message' => $message], 403);
    }

    public static function notFound(): void
    {
        self::json(['message' => 'Not found', 'code' => 404], 404);
    }

    public static function unauthorized(): void
    {
        self::json(['message' => 'Login failed'], 403);
    }
}
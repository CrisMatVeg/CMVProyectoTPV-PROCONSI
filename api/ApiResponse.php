<?php

/**
 * ApiResponse
 * Helpers estáticos para respuestas JSON uniformes: { ok: true/false, ... }
 */
class ApiResponse
{
    public static function ok(array $data = []): void
    {
        echo json_encode(array_merge(['ok' => true], $data));
        exit;
    }

    public static function error(string $msg, int $httpCode = 200): void
    {
        if ($httpCode !== 200) {
            http_response_code($httpCode);
        }
        echo json_encode(['ok' => false, 'error' => $msg]);
        exit;
    }
}

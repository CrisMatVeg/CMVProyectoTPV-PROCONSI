<?php

/**
 * Verificación CSRF universal para la API
 */
require_once __DIR__ . '/../model/Usuario.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Las peticiones GET o HEAD generalmente no mutan estado, por lo que quedan exentas.
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'HEAD' && $_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
    // Intentar leer desde apache_request_headers o getallheaders en caso de Apache
    $headers = function_exists('getallheaders') ? getallheaders() : [];

    // Convertir todas las claves a minúsculas para búsqueda insensible a mayúsculas/minúsculas
    $headersLower = array_change_key_case($headers, CASE_LOWER);
    $csrf_token = $headersLower['x-csrf-token'] ?? '';

    // Fallback: Si el servidor web lo pasa nativamente como HTTP_X_CSRF_TOKEN
    if (empty($csrf_token) && !empty($_SERVER['HTTP_X_CSRF_TOKEN'])) {
        $csrf_token = $_SERVER['HTTP_X_CSRF_TOKEN'];
    }

    // 3. Fallback manual en $_SERVER si fallan los anteriores
    if (empty($csrf_token)) {
        foreach ($_SERVER as $key => $value) {
            if (strcasecmp($key, 'HTTP_X_CSRF_TOKEN') === 0) {
                $csrf_token = $value;
                break;
            }
        }
    }

    // Si el token viene duplicado (separado por coma), tomamos solo el primero
    if (!empty($csrf_token) && strpos($csrf_token, ',') !== false) {
        $parts = explode(',', $csrf_token);
        $csrf_token = trim($parts[0]);
    }

    if (empty($csrf_token) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => false,
            'error' => 'Error de seguridad CSRF: Petición rechazada o expirada. Por favor, recarga la página.'
        ]);
        exit;
    }
}

<?php
if (!defined('JWT_SECRET')) {
    define('JWT_SECRET', getenv('SIGSA_JWT_SECRET') ?: getenv('JWT_SECRET') ?: 'dev_secret_local');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function crear_jwt(array $claims): string {
    if (JWT_SECRET === '') {
        throw new RuntimeException('SIGSA_JWT_SECRET no está configurada');
    }

    $header = ['typ' => 'JWT', 'alg' => 'HS256'];
    $codificar = static function (string $valor): string {
        return rtrim(strtr(base64_encode($valor), '+/', '-_'), '=');
    };

    $encabezado = $codificar(json_encode($header, JSON_UNESCAPED_SLASHES));
    $payload = $codificar(json_encode($claims, JSON_UNESCAPED_SLASHES));
    $firma = $codificar(hash_hmac('sha256', "$encabezado.$payload", JWT_SECRET, true));

    return "$encabezado.$payload.$firma";
}

// 1. Verificar si hay usuario autenticado
function requerir_autenticacion() {
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No has iniciado sesión']);
        exit;
    }
}

function requerir_rol(array $roles_permitidos) {
    requerir_autenticacion();
    
    // Asumiendo que guardaste el rol/puesto en la sesión al hacer Login
    if (!in_array($_SESSION['rol'], $roles_permitidos)) {
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permisos para realizar esta acción']);
        exit;
    }
}

function validar_csrf($input) {
    $token_recibido = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $input['csrf_token'] ?? '';
    
    if (empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token_recibido)) {
        http_response_code(403);
        echo json_encode(['error' => 'Token CSRF inválido o expirado']);
        exit;
    }
}

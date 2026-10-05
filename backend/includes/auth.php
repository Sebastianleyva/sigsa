<?php
require_once dirname(__DIR__) . '/config/config.php';

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

function validar_jwt_requerido() {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        http_response_code(401);
        echo json_encode(["ERROR" => "Token de autenticación no proporcionado"]);
        exit;
    }

    try {
        $datos = verificar_jwt($matches[1]); 
        if (!$datos) {
            throw new Exception("Token no válido");
        }
        return $datos;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["ERROR" => "Sesión inválida o expirada"]);
        exit;
    }
}

function validar_csrf_requerido() {
    $headers = getallheaders();
    $csrfToken = $headers['X-CSRF-Token'] ?? $headers['x-csrf-token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (empty($csrfToken) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        http_response_code(403);
        echo json_encode(["ERROR" => "Petición no autorizada (CSRF inválido)"]);
        exit;
    }
}

function requerir_autenticacion() {
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'No has iniciado sesión']);
        exit;
    }
}

$puestos_permitidos = [1,2,3];
function requerir_rol(array $puestos_permitidos) {
    requerir_autenticacion();
    
    if (!in_array($_SESSION['puesto'], $puestos_permitidos,true)) {
        http_response_code(403);
        echo json_encode(['error' => 'No tienes permisos para realizar esta acción']);
        exit;
    }
}




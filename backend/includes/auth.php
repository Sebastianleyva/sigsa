<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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

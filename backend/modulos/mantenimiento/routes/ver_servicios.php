<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/ver_servicios.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["ERROR" => "Metodo no permitido"]);
    exit;
}

try {
    $usuario = validar_jwt_requerido();
    validar_csrf_requerido();
    requerir_permiso_mantenimiento($pdo, $usuario);

    http_response_code(200);
    echo json_encode([
        "mensaje" => "Servicios cargados correctamente",
        "data" => ver_servicios($pdo)
    ]);
} catch (Throwable $e) {
    error_log("Error al consultar servicios de mantenimiento: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ERROR" => "Error al consultar los servicios"]);
}

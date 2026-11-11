<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/ver_reportes_requisicion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["ERROR" => "Metodo no permitido"]);
    exit;
}

try {
    validar_jwt_requerido();
    validar_csrf_requerido();
    requerir_rol([1, 2, 3]);

    http_response_code(200);
    echo json_encode([
        "mensaje" => "Reportes que requieren requisición cargados correctamente",
        "data" => ver_reportes_requisicion($pdo)
    ]);
} catch (Throwable $e) {
    error_log("Error al consultar reportes para requisición: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ERROR" => "Error al consultar los reportes para requisición"]);
}

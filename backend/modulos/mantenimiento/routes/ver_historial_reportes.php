<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/ver_historial_reportes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["ERROR" => "Metodo no permitido"]);
    exit;
}

try {
    validar_jwt_requerido();
    validar_csrf_requerido();
    requerir_rol([1, 2, 3]);

    $mes = filter_input(INPUT_GET, 'mes', FILTER_VALIDATE_INT);
    $anio = filter_input(INPUT_GET, 'anio', FILTER_VALIDATE_INT);
    $mes_actual = (int) date('n');
    $anio_actual = (int) date('Y');
    if ($mes === false || $mes === null || $mes < 1 || $mes > 12
        || $anio !== $anio_actual || $mes > $mes_actual) {
        http_response_code(400);
        echo json_encode(["ERROR" => "El mes de consulta no es válido"]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        "mensaje" => "Historial de reportes cargado correctamente",
        "data" => ver_historial_reportes($pdo, $mes, $anio)
    ]);
} catch (Throwable $e) {
    error_log("Error al consultar el historial de reportes: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ERROR" => "Error al consultar el historial de reportes"]);
}

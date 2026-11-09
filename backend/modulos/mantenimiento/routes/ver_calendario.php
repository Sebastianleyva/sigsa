<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/ver_calendario.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["ERROR" => "Metodo no permitido"]);
    exit;
}

try {
    validar_jwt_requerido();
    validar_csrf_requerido();

    $anio = $_GET['anio'] ?? null;
    $mes = $_GET['mes'] ?? null;
    $calendario = ver_calendario_mes($pdo, $anio, $mes);

    http_response_code(200);
    echo json_encode([
        "mensaje" => "Calendario cargado correctamente",
        "anio" => (int) $anio,
        "mes" => (int) $mes,
        "data" => $calendario
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(["ERROR" => $e->getMessage()]);
} catch (PDOException $e) {
    error_log("Error al consultar el calendario: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ERROR" => "Error al consultar el calendario"]);
} catch (Throwable $e) {
    error_log("Error inesperado al consultar el calendario: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ERROR" => "Error interno al consultar el calendario"]);
}

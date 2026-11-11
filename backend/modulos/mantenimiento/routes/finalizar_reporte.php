<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/finalizar_reporte.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    http_response_code(405);
    echo json_encode(["ERROR" => "Metodo no permitido"]);
    exit;
}

try {
    validar_jwt_requerido();
    validar_csrf_requerido();
    requerir_rol([1, 2, 3]);

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["ERROR" => "El cuerpo de la solicitud debe ser un objeto JSON válido"]);
        exit;
    }

    $reporte = finalizar_reporte($pdo, $input);
    http_response_code(200);
    echo json_encode([
        "mensaje" => "Servicio concluido correctamente",
        "reporte" => $reporte
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(["ERROR" => $e->getMessage()]);
} catch (OutOfBoundsException $e) {
    http_response_code(404);
    echo json_encode(["ERROR" => $e->getMessage()]);
} catch (DomainException $e) {
    http_response_code(409);
    echo json_encode(["ERROR" => $e->getMessage()]);
} catch (Throwable $e) {
    error_log("Error al concluir el servicio del reporte: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ERROR" => "Error interno al concluir el servicio"]);
}

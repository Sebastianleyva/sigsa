<?php

header('content-type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/generar_reporte.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);
    echo json_encode(["ERROR"=>"Metodo no permitido"]);
    exit;
}

try{
    validar_jwt_requerido();
    validar_csrf_requerido();

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["ERROR" => "El cuerpo de la solicitud debe ser un objeto JSON válido"]);
        exit;
    }

    $reporte = generar_reporte($pdo, $input);

    http_response_code(201);
    echo json_encode([
        "mensaje" => "Reporte generado exitosamente",
        "reporte" => $reporte
    ]);

}catch (PDOException $e) {
    error_log("Error al generar el reporte: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ERROR" => "Error al generar el reporte"]);
} catch (OutOfBoundsException $e) {
    http_response_code(404);
    echo json_encode(["ERROR" => $e->getMessage()]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(["ERROR" => $e->getMessage()]);
} catch (Throwable $e) {
    error_log("Error inesperado al generar el reporte: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ERROR" => "Error interno al generar el reporte"]);
}
<?php

header('content-type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/generar_calendario.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);
    echo json_encode(["ERROR"=>"Metodo no permitido"]);
    exit;
}

try{
    valiar_jwt_requerido();
    validar_csrf_requerido();

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["ERROR" => "El cuerpo de la solicitud debe ser un objeto JSON válido"]);
        exit;
    }

    $calendario = generar_calendario($pdo, $input);

    http_response_code(200);
    echo json_encode([
        "mensaje" => "Calendario generado exitosamente",
        "calendario" => $calendario
    ]);
}catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["ERROR" => "Error al generar el calendario: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["ERROR" => "Error: " . $e->getMessage()]);
}
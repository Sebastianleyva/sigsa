<?php

header('Content-type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/crear_servicio.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);
    echo json_encode(["ERROR"=>"Metodo no permitido"]);
    exit;
}

try{
    validar_jwt_requerido();
    validar_csrf_requerido();
    requerir_rol([1, 2, 3]);

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["ERROR" => "El cuerpo de la solicitud debe ser un objeto JSON válido"]);
        exit;
    }

    $servicio = crear_servicio($pdo, $input);

    http_response_code(201);
    echo json_encode(["mensaje" => "Servicio creado exitosamente", 
        "servicio" => $servicio]);

}catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["ERROR" => "Error al crear el servicio: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["ERROR" => "Error: " . $e->getMessage()]);
}
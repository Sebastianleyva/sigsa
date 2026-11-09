<?php

header('content-type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/eliminar_servicio.php';

if($_SERVER['REQUEST_METHOD'] !== 'DELETE'){
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

    $id_servicio = $input['id_servicio'];

    if (!is_int($id_servicio) || $id_servicio <= 0) {
        http_response_code(400);
        echo json_encode(["ERROR" => "El id_servicio debe ser un número entero positivo"]);
        exit;
    }

    $resultado = eliminar_servicio($pdo, $id_servicio);
    
    http_response_code(200);
    echo json_encode([
        "mensaje" => $resultado["mensaje"],
        "id_servicio" => $resultado["id_servicio"]
    ]);

}catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["ERROR" => "Error al eliminar el servicio: " . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(["ERROR" => "Error: " . $e->getMessage()]);
}
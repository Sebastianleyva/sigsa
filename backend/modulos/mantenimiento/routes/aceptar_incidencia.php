<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/aceptar_incidencia.php';

if($_SERVER['REQUEST_METHOD'] !== 'PATCH'){
    http_response_code(405);
    echo json_encode(["ERROR"=>"Metodo no permitido"]);
    exit;
}

try{
    $usuario = validar_jwt_requerido();
    validar_csrf_requerido();
    requerir_rol([1, 2, 3]);
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["ERROR" => "El cuerpo de la solicitud debe ser un objeto JSON válido"]);
        exit;
    }

    $id_personal = filter_var($usuario['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $incidencia = aceptar_incidencia($pdo, $input, $id_personal);

    http_response_code(200);
    echo json_encode(["mensaje" => "Incidencia aceptada exitosamente",
        "incidencia" => $incidencia]);

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(["ERROR" => $e->getMessage()]);
    exit;
} catch (OutOfBoundsException $e) {
    http_response_code(404);
    echo json_encode(["ERROR" => $e->getMessage()]);
    exit;
} catch (DomainException $e) {
    http_response_code(409);
    echo json_encode(["ERROR" => $e->getMessage()]);
    exit;
} catch (Throwable $e) {
    error_log("Error al aceptar la incidencia: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ERROR" => "Error interno del servidor"]);
    exit;
}

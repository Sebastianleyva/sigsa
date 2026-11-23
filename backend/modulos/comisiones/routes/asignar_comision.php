<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/asignar_comision.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["ERROR" => "Metodo no permitido"]);
    exit;
}

try{
    $usuario = validar_jwt_requerido();
    validar_csrf_requerido();

    $id_personal = filter_var($usuario['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_personal === false) {
        http_response_code(401);
        echo json_encode(["ERROR" => "Los datos de identidad no son válidos"]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if(!is_array($input)){
        http_response_code(400);
        echo json_encode(["ERROR" => "Datos de entrada no válidos"]);
        exit;
    }

    $personal_comisionado = asignar_comision($pdo, $input);
    http_response_code(200);
    echo json_encode([
        'mensaje' => 'Comisión asignada correctamente',
        'personal_comisionado' => $personal_comisionado
    ]);
}catch(Exception $e){
    http_response_code(500);
    echo json_encode(["ERROR" => "Error interno del servidor: " . $e->getMessage()]);
}catch(PDOException $e){
    http_response_code(500);
    echo json_encode(["ERROR" => "Error de base de datos: " . $e->getMessage()]);
}

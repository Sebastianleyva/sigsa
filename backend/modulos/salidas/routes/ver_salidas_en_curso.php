<?php

header('Contenct-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

switch($_SERVER['REQUEST_METHOD']){
    case 'GET':
        $usuario = validar_jwt_requerido();
        validar_csrf_requerido();
        ver_salidas_en_curso($pdo, $usuario);
        break;
    default:
        http_response_code(405);
        echo json_encode(['ERROR' => 'Método no permitido']);
        break;
}

function ver_salidas_en_curso($pdo, $usuario){

    $estado = "EN CURSO";

    $sql = $pdo -> prepare(
        'SELECT * FROM salidas
         WHERE estado = :estado '
    );
    
    $sql -> execute([
        'estado' => $estado
    ]);

    $salidas = $sql -> fetchAll();

    http_response_code(200);
    echo json_encode([
        "mensaje" => "Salidas activas obtenidas correctamente",
        "salidas" => $salidas
    ]);
}
<?php

header('content-type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

switch ($_SERVER['REQUEST_METHOD']){
    case 'PATCH':
        $usuario = validar_jwt_requerido();
        validar_csrf_requerido();
        $input=json_decode(file_get_contents('php://input'),true) ?? [];
        aceptar_solicitud($pdo,$input);
        break;
    default:
        http_response_code(405);
        echo json_encode(["ERROR"=>"Metodo no permitido"]);
        break;
}

function aceptar_solicitud($pdo,$input){
    if(empty($input)){
        http_response_code(400);
        echo json_encode(["ERROR" => "Campos faltantes"]);
        exit;
    }

    $id_salida = $input['id_salida'];
    $id_autoriza = $input['id_autoriza'];

    $validar_estado = $pdo ->prepare(
        'SELECT estado FROM salidas
        WHERE id_salida = :id_salida'
    );
    $validar_estado -> execute([
        'id_salida' => $id_salida
    ]);
    $salida = $validar_estado->fetch();
    if($salida['estado']!="PENDIENTE"){
        http_response_code(409);
        echo json_encode([
            "ERROR" => "La solicitud no puede ser modificada"
        ]);
        exit;
    }

    $nuevo_estado = "EN CURSO";
    date_default_timezone_set('America/Mexico_City');
    $hora_salida = date('H:i:s');
    $hora_estimada = date('H:i:s', strtotime('+2 hour')); 
        
    $query = $pdo -> prepare(
        'UPDATE salidas 
        SET estado = :nuevo_estado,
        hora_salida = :hora_salida,
        hora_estimada = :hora_estimada
        WHERE id_salida = :id_salida'
    );
    $query -> execute([
        'id_salida' => $id_salida,
        'nuevo_estado' => $nuevo_estado,
        'hora_salida' => $hora_salida,
        'hora_estimada' => $hora_estimada
    ]);

    http_response_code(200);
    echo json_encode([
        "message" => "Operación realizada con éxito",
        "salida" => [
            'id_salida' => $id_salida,
            'estado' => $nuevo_estado,
            'hora_salida' => $hora_salida,
            'hora_estimada' => $hora_estimada
        ]
    ]);

}
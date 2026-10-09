<?php

header ('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

switch($_SERVER['REQUEST_METHOD']){
    case 'PATCH':
        $usuario = validar_jwt_requerido();
        validar_csrf_requerido();
        $input =  json_decode(file_get_contents('php://input'), true ) ?? [];
        finalizar_salida($pdo,$input);
        break;
    default:
        http_response_code(405);
        echo json_encode(["ERROR"=>"Metodo no permitido"]);
        break;
}

 function finalizar_salida($pdo,$input){

    if(empty($input)){
        http_response_code(400);
        echo json_encode(["ERROR" => "Campos faltantes"]);
        exit;
    }

    $id_salida = $input['id_salida'];
    $id_autoriza = $input['id_autoriza'];

    $validar_estado = $pdo ->prepare(
        'SELECT estado,hora_salida FROM salidas
        WHERE id_salida = :id_salida'
    );
    $validar_estado -> execute([
        'id_salida' => $id_salida
    ]);
    $salida = $validar_estado->fetch();
    if($salida['estado']!="EN CURSO"){
        http_response_code(409);
        echo json_encode([
            "ERROR" => "La solicitud no puede ser modificada"
        ]);
        exit;
    }

    $nuevo_estado = "FINALIZADO";
    date_default_timezone_set('America/Mexico_City');
    $hora_llegada = date('H:i:s');
    $hora_salida = $salida['hora_salida'];
    $adeudo = strtotime($hora_salida) - strtotime($hora_llegada);
    $fromato = gmdate('H:i:s',$adeudo);
        
    $query = $pdo -> prepare(
        'UPDATE salidas 
        SET estado = :nuevo_estado,
        hora_llegada = :hora_llegada,
        adeudo = :formato
        WHERE id_salida = :id_salida'
    );
    $query -> execute([
        'id_salida' => $id_salida,
        'nuevo_estado' => $nuevo_estado,
        'hora_llegada' => $hora_llegada,
        "adeudo" => $fromato
    ]);

    http_response_code(200);
    echo json_encode([
        "mensaje" => "Salida finalizada correctamente"
    ]);
}
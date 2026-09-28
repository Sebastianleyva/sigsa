<?php

header('content-type: application/json');

include_once '../includes/conexion.php';
include_once '../includes/auth.php';

switch($_SERVER['REQUEST_METHOD']){
    case 'POST':
        $input = json_decode(file_get_contents('php://input',true) ?? []);
        crear_solicitud($pdo,$input);
        brak;
    default:
        http_response_code(400);
        echo json_encode(["ERROR"=>"Metodo no permitido"]);
        exit;
}

function crear_solicitud($pdo,$input){
    if(empty($input)){
        http_response_code(401);
        json_encode(["ERROR"=>"Campos faltantes en su solicitud"]);
        break;
    }

    $tipo_salida = $input['tipo_salida'];
    $motivo = $input['motivo'];
    $destino = $input['destino'];
    $fecha = $input['fecha'];
    $hora_salida = $input['hora_salida'];
    $hora_llegada = $input['hora_llegada'];
    $estado = $input['estado'];
    $id_personal = $input['id_personal'];
    $id_autoriza = $input['id_autoriza'];
    $hora_estimada = $input['hora_estimada'];

    $query = $pdo->prepare(
        'INSERT INTO salidas 
        (tipo_salida, motivo, destino, fecha, hora_salida, hora_llegada, estado, id_personal, id_autoriza, hora_estimada)
        VALUES
        (:tipo_salida, :motivo, :destino, :fecha, :hora_salida, :hora_llegada, :estado, :id_personal, :id_autoriza, :hora_estimada)'
    );
    $query -> execute([
        'tipo_salida' = $tipo_salida, 
        'motivo' = $motivo, 
        'destino' = $destino,  
        'fecha' = $fecha, 
        'hora_salida' = $hora_salida ,
        'hora_llegada' = $hora_llegada, 
        'estado' = $estado ,
        'id_personal' = $id_personal ,
        'id_autoriza' = $id_autoriza ,
        'hora_estimada' = $hora_llegada
    ])

}


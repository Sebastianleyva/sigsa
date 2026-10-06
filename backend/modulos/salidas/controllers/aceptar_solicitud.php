<?php

header('content-type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

switch ($_SERVER['REQUEST_METHOD']){
    case 'PATCH':
        $usuario = validar_jwt_requerido();
        validar_csrf_requerido();
        requerir_rol([1,2,3]);
        $input=json_decode(file_get_contents('php://input'),true) ?? [];
        aceptar_solicitud($pdo,$input);
        break;
    default:
        http_response_code(405);
        echo json_encode(["ERROR"=>"Metodo no permitido"]);
        break;
}

function verificacion_jerarquia($pdo,$input){
    if(empty($input)){
        http_response_code(400);
        echo json_encode(["ERROR" => "Campos faltantes"]);
        exit;
    }
    $id_autorizante = $_SESSION['id_personal'];
    $id_solicitante = $input['id_personal'];
    $verificacion = $pdo -> prepare(
        'SELECT id_jefe FROM personal
        WHERE id_personal = :id_solicitante'
    );
    $verificacion -> execute(['id_solcitante' => $id_solicitante]);
    $resultado = $verificacion -> fetch();

    if($resultado!=$id_autorizante){
        http_response_code(403);
        echo json_encode(["ERROR"=>"No puedes autorizar esta salida"]);
        exit;
    }
}

function aceptar_solicitud($pdo,$input){
    if(empty($input)){
        http_response_code(400);
        echo json_encode(["ERROR" => "Campos faltantes"]);
        exit;
    }
    $id_salida = $input['id_salida'];
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
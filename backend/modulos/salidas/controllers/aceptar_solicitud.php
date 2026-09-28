<?php

header('content-type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

switch ($_SERVER['REQUEST_METHOD']){
    case 'PATCH':
        $input=json_decode(file_get_contents('php://input'),true) ?? [];
        aceptar_solicitud($pdo,$input);
        break;
    default:
        http_response_code(405);
        echo json_encode(["ERROR"=>"Metodo no permitido"]);
        break;
}

function aceptar_solicitud($pdo,$input){
    // -------------------------------------------------------------------------
    // MOCK TEMPORAL DE SESIÓN PARA PRUEBAS (ELIMINAR AL INTEGRAR AUTENTICACIÓN)
    // -------------------------------------------------------------------------
    $_SESSION['id_personal'] = $_SESSION['id_personal'] ?? 2; // ID del Jefe/Autorizador
    // -------------------------------------------------------------------------
    if(empty($input)){
        http_response_code(400);
        echo json_encode(["ERROR" => "Campos faltantes"]);
        exit;
    }

    $id_salida = $input['id_salida'];
    $id_autoriza = $input['id_personal']

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
        
    $query = $pdo -> prepare(
        'UPDATE salidas 
        SET estado = :nuevo_estado
        WHERE id_salida = :id_salida'
    );
    $query -> execute([
        'id_salida' => $id_salida,
        'estado' => $nuevo_estado
    ]);

    http_response_code(200);
    echo json_encode([
        "message" => "Operación realizada con éxito",
        "salida" => [
            'id_salida' => $id_salida
        ]
    ]);

}
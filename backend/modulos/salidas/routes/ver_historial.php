<?php

header('Content-type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

switch($_SERVER['REQUEST_METHOD']){
    case 'GET':
        $usuario = validar_jwt_requerido();
        validar_csrf_requerido();
        ver_historial($pdo,$input);
        break;
    case 'DEFAULT':
        http_response_code(405);
        echo json_encode(["ERROR"=>"Metodo no permitido"]);
        break;
}

function ver_historial($pdo,$input){
    $sql = $pdo - prepare(
        'SELECT * from salidas
        WHERE fecha fecha >= NOW() - INTERVAL 1 YEAR'
    );
    $sql -> execute();
    $resultado = $sql -> fetchAll();

    http_response_code(200);
    echo json_encode(["message" => "Historial cargado correctamente",
        "Número de salidas" => count($resultado),
        "Salidas"=>$resultado
        ]);
}

<?php

header('Content-type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

switch($_SERVER['REQUEST_METHOD']){
    case 'GET':
        ver_salidas($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(["ERROR"=>"Metodo no permitido"]);
        break;
}

function ver_salidas($pdo){

    $pendiente = "PENDIENTE";
    $query = $pdo -> prepare(
        'SELECT * FROM salidas
        WHERE estado = :pendiente'
    );
    $query -> execute([
        'pendiente' => $pendiente
    ]);

    $numero = count($query->fetchAll());

    http_response_code(200);
    echo json_encode([
        "message" => "Operación realizada con éxito",
        "salidas" => $numero
    ]);
}
    
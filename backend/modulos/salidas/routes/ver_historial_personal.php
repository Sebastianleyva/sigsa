<?php

header('content-type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

switch($_SERVER['REQUEST_METHOD']){
    case 'GET':
        $usuario = validar_jwt_requerido();
        validar_csrf_requerido();
        $input = json_decode(file_get_contents('php://'),true) ?? [];
        ver_historial_personal($pdo,$input);
        break; 
    case 'DEFAULT':
        break;
}

function ver_historial_personal($pdo,$input){
    if(empty($input)){
        http_response_code(403);
        echo json_encode(["ERROR"=>"Campos faltantes"]);
        exit;
    }

    $id_personal = $_SESSION['id_personal'];
    $sql = $pdo -> prepare(
        'SELECT * FROM salidas
        WHERE id_personal = :id_personal
    ');
    $sql -> execute(['id_personal'=>$id_personal]);
    $resultado = $sql -> fetch();
    
    http_response_code(200);
    echo json_encode(["message" => "Historial cargado correctamente",
        "Número de salidas" => count($resultado),
        "Salidas"=>[
            'id_salida'=> $resultado['id_salida'],
            'id_autoriza' => $resultado['id_jefe'],
            'motivo' => $resultado['motivo'],
            'destino' => $resultado['destino'],
            'horade salida' => $resultado['hora_salida'],
            'hora de llegada' => $resultado['hora_llegada'],
            'adeudo de tiempo' => $resultado['adeudo_tiempo']
        ]);

}
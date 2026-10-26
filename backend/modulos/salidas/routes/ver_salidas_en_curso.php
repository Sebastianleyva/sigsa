<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

switch($_SERVER['REQUEST_METHOD']){
    case 'GET':
        $usuario = validar_jwt_requerido();
        validar_csrf_requerido();
        requerir_rol([1, 2, 3]);
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
        'SELECT s.id_salida, s.tipo_salida, s.motivo, s.destino, s.fecha,
                s.hora_salida, s.hora_estimada, s.id_personal,
                p.nombre, p.apellido_p, p.apellido_m
         FROM salidas s
         JOIN personal p ON p.id_personal = s.id_personal
         WHERE s.estado = :estado
         ORDER BY s.fecha DESC, s.hora_salida DESC, s.id_salida DESC'
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
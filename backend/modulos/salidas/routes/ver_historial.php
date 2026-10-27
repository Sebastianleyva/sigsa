<?php

header('Content-type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

switch($_SERVER['REQUEST_METHOD']){
    case 'GET':
        validar_jwt_requerido();
        validar_csrf_requerido();
        requerir_rol([1, 2, 3]);
        ver_historial($pdo);
        break;
    case 'DEFAULT':
        http_response_code(405);
        echo json_encode(["ERROR"=>"Metodo no permitido"]);
        break;
}

function ver_historial($pdo){
    $sql = $pdo -> prepare(
        'SELECT s.id_salida, s.destino, s.motivo, s.hora_llegada, s.hora_salida, s.id_personal, s.id_autoriza, s.fecha,
        s.adeudo_tiempo, p.nombre, p.apellido_p, p.apellido_m, p.area,
        ar.nombre AS area_nombre,
        a.nombre AS autorizante_nombre,
        a.apellido_p AS autorizante_apellido_p,
        a.apellido_m AS autorizante_apellido_m
        FROM salidas s
        JOIN personal p ON s.id_personal = p.id_personal
        LEFT JOIN areas ar ON p.area = ar.id_area
        LEFT JOIN personal a ON s.id_autoriza = a.id_personal
        WHERE s.estado = :estado
        AND s.fecha >= NOW() - INTERVAL 1 YEAR
        ORDER BY s.fecha DESC, s.hora_salida DESC, s.id_salida DESC'
    );
    $sql -> execute(['estado' => 'FINALIZADO']);
    $resultado = $sql -> fetchAll();

    http_response_code(200);
    echo json_encode(["message" => "Historial cargado correctamente",
        "Número de salidas" => count($resultado),
        "Salidas"=>$resultado
        ]);
}

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
    $mes = filter_input(INPUT_GET, 'mes', FILTER_VALIDATE_INT);
    $anio = filter_input(INPUT_GET, 'anio', FILTER_VALIDATE_INT);
    $mesActual = (int) date('n');
    $anioActual = (int) date('Y');

    if ($mes === false || $mes === null || $mes < 1 || $mes > 12 ||
        $anio === false || $anio === null || $anio !== $anioActual || $mes > $mesActual) {
        http_response_code(400);
        echo json_encode(["ERROR" => "El mes de consulta no es válido"]);
        return;
    }

    $sql = $pdo -> prepare(
        'SELECT s.id_salida, s.destino, s.motivo, s.hora_llegada, s.fecha_llegada,
        s.hora_salida, s.id_personal, s.id_autoriza, s.fecha,
        s.adeudo_tiempo, s.requiere_justificante, s.justificante_entregado,
        s.fecha_justificante_entregado,
        p.nombre, p.apellido_p, p.apellido_m, p.area,
        ar.nombre AS area_nombre,
        a.nombre AS autorizante_nombre,
        a.apellido_p AS autorizante_apellido_p,
        a.apellido_m AS autorizante_apellido_m
        FROM salidas s
        JOIN personal p ON s.id_personal = p.id_personal
        LEFT JOIN areas ar ON p.area = ar.id_area
        LEFT JOIN personal a ON s.id_autoriza = a.id_personal
        WHERE s.estado = :estado
        AND MONTH(s.fecha) = :mes
        AND YEAR(s.fecha) = :anio
        ORDER BY s.fecha DESC, s.hora_salida DESC, s.id_salida DESC'
    );
    $sql -> execute([
        'estado' => 'FINALIZADO',
        'mes' => $mes,
        'anio' => $anio,
    ]);
    $resultado = $sql -> fetchAll();
    foreach ($resultado as &$salida) {
        $salida['requiere_justificante'] = (int) $salida['requiere_justificante'];
        $salida['justificante_entregado'] = $salida['justificante_entregado'] === null
            ? null
            : (int) $salida['justificante_entregado'];
    }
    unset($salida);

    http_response_code(200);
    echo json_encode(["message" => "Historial cargado correctamente",
        "Número de salidas" => count($resultado),
        "Salidas"=>$resultado
        ]);
}

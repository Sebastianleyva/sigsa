<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $usuario = validar_jwt_requerido();
        validar_csrf_requerido();
        ver_historial_personal($pdo, $usuario);
        break;
    default:
        http_response_code(405);
        echo json_encode(['ERROR' => 'Método no permitido']);
        break;
}

function ver_historial_personal( $pdo,  $usuario){
    $id_personal = filter_var(
        $usuario['sub'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );

    if ($id_personal === false) {
        http_response_code(401);
        echo json_encode(['ERROR' => 'Los datos de identidad de la sesión no son válidos']);
        return;
    }

    $sql = $pdo->prepare(
        'SELECT id_salida, tipo_salida, motivo, destino, hora_salida,
                hora_llegada, estado, hora_estimada, adeudo_tiempo
        FROM salidas
        WHERE id_personal = :id_personal
        AND estado = :estado
        ORDER BY fecha DESC, id_salida DESC'
    );
    $sql->execute([
        'id_personal' => $id_personal,
        'estado' => 'FINALIZADO'
    ]);
    $salidas = $sql->fetchAll();

    $historial = [];
    foreach ($salidas as $indice => $salida) {
        $historial[] = [
            'numero_salidas' => $indice + 1,
            'id_salida' => (int) $salida['id_salida'],
            'tipo_salida' => $salida['tipo_salida'],
            'motivo' => $salida['motivo'],
            'destino' => $salida['destino'],
            'horade_salida' => $salida['hora_salida'] ?? null,
            'horade_regreso' => $salida['hora_llegada'] ?? null,
            'estado' => $salida['estado'],
            'adeudo_tiempo' => $salida['adeudo_tiempo'] ?? null
    ];
    }

    http_response_code(200);
    echo json_encode($historial);
}

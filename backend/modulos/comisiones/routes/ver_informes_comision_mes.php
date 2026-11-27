<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/ver_informes_comision_mes.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ERROR' => 'Método no permitido']);
    exit;
}

try {
    $usuario = validar_jwt_requerido();
    validar_csrf_requerido();

    $id_personal = filter_var(
        $usuario['sub'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]],
    );
    if ($id_personal === false) {
        http_response_code(401);
        echo json_encode(['ERROR' => 'Los datos de identidad no son válidos']);
        exit;
    }

    $consulta_personal = $pdo->prepare(
        "SELECT puesto, area
         FROM personal
         WHERE id_personal = :id_personal AND activo = '1'"
    );
    $consulta_personal->execute(['id_personal' => $id_personal]);
    $usuario_actual = $consulta_personal->fetch(PDO::FETCH_ASSOC);
    if (!$usuario_actual) {
        http_response_code(403);
        echo json_encode(['ERROR' => 'El usuario no está activo']);
        exit;
    }

    $puesto = (int) $usuario_actual['puesto'];
    $area_raw = filter_var(
        $usuario_actual['area'],
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]],
    );
    $area = $area_raw === false ? null : $area_raw;
    if (!in_array($puesto, [1, 2], true) && $area !== 6) {
        http_response_code(403);
        echo json_encode(['ERROR' => 'No tienes permisos para consultar informes de comisión']);
        exit;
    }

    $mes = filter_input(INPUT_GET, 'mes', FILTER_VALIDATE_INT);
    $anio = filter_input(INPUT_GET, 'anio', FILTER_VALIDATE_INT);
    $mes_actual = (int) date('n');
    $anio_actual = (int) date('Y');
    if (
        $mes === false || $mes === null || $mes < 1 || $mes > 12
        || $anio !== $anio_actual || $mes > $mes_actual
    ) {
        http_response_code(400);
        echo json_encode(['ERROR' => 'El mes de consulta no es válido']);
        exit;
    }

    echo json_encode([
        'informes' => ver_informes_comision_mes($pdo, $mes, $anio),
    ]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ERROR' => 'Error interno del servidor']);
    exit;
}

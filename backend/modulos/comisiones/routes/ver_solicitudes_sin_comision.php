<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/ver_solicitudes_sin_comision.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ERROR' => 'Metodo no permitido']);
    exit;
}

try {
    $usuario = validar_jwt_requerido();
    validar_csrf_requerido();

    $id_personal = filter_var($usuario['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_personal === false) {
        http_response_code(401);
        echo json_encode(['ERROR' => 'Los datos de identidad no son válidos']);
        exit;
    }

    $consulta_personal = $pdo->prepare(
        "SELECT puesto, area FROM personal
         WHERE id_personal = :id_personal AND activo = '1'"
    );
    $consulta_personal->execute(['id_personal' => $id_personal]);
    $personal = $consulta_personal->fetch(PDO::FETCH_ASSOC);
    if (!$personal) {
        http_response_code(403);
        echo json_encode(['ERROR' => 'El usuario no está activo']);
        exit;
    }

    $puesto = (int) $personal['puesto'];
    $area_usuario = filter_var($personal['area'], FILTER_VALIDATE_INT);
    if (!in_array($puesto, [1, 2], true) && $area_usuario !== 6) {
        http_response_code(403);
        echo json_encode(['ERROR' => 'No tienes permisos para consultar solicitudes sin comisión']);
        exit;
    }

    $solicitudes = ver_solicitudes_sin_comision($pdo);
    $areas = $pdo->query(
        'SELECT id_area, nombre FROM areas ORDER BY nombre'
    )->fetchAll(PDO::FETCH_ASSOC);

    http_response_code(200);
    echo json_encode([
        'solicitudes' => $solicitudes,
        'areas' => $areas
    ]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ERROR' => 'Error interno del servidor']);
    exit;
}

<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/ver_personal_solicitudes_viaticos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ERROR' => 'Metodo no permitido']);
    exit;
}

try {
    $usuario = validar_jwt_requerido();
    validar_csrf_requerido();

    $id_usuario = filter_var($usuario['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_usuario === false) {
        http_response_code(401);
        echo json_encode(['ERROR' => 'Los datos de identidad no son válidos']);
        exit;
    }

    $consulta_usuario = $pdo->prepare(
        "SELECT puesto, area,
                CONCAT_WS(' ', nombre, apellido_p, apellido_m) AS nombre,
                correo, cisco
         FROM personal
         WHERE id_personal = :id_personal AND activo = '1'"
    );
    $consulta_usuario->execute(['id_personal' => $id_usuario]);
    $usuario_actual = $consulta_usuario->fetch(PDO::FETCH_ASSOC);
    if (!$usuario_actual) {
        http_response_code(403);
        echo json_encode(['ERROR' => 'El usuario no está activo']);
        exit;
    }

    $puesto = (int) $usuario_actual['puesto'];
    $area_raw = filter_var($usuario_actual['area'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $area = $area_raw === false ? null : $area_raw;
    if (!in_array($puesto, [1, 2], true) && $area !== 6) {
        http_response_code(403);
        echo json_encode(['ERROR' => 'No tienes permisos para consultar solicitudes de viáticos']);
        exit;
    }

    $id_comision = filter_input(INPUT_GET, 'id_comision', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $id_personal = filter_input(INPUT_GET, 'id_personal', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_comision === false || $id_comision === null || $id_personal === false || $id_personal === null) {
        http_response_code(400);
        echo json_encode(['ERROR' => 'Los identificadores de comisión y personal no son válidos']);
        exit;
    }

    $comisionado = ver_comisionado_solicitud_viaticos($pdo, $id_comision, $id_personal);
    if (!$comisionado) {
        http_response_code(404);
        echo json_encode(['ERROR' => 'El personal no pertenece a una comisión pendiente de solicitudes de viáticos']);
        exit;
    }
    $opciones = obtener_opciones_solicitud_viaticos($pdo, (int) $comisionado['id_puesto']);

    http_response_code(200);
    echo json_encode([
        'comisionado' => $comisionado,
        'opciones' => $opciones,
        'registrante' => [
            'nombre' => $usuario_actual['nombre'],
            'correo' => $usuario_actual['correo'],
            'cisco' => trim((string) ($usuario_actual['cisco'] ?? '')) ?: 'N/A',
        ],
    ]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ERROR' => 'Error interno del servidor']);
    exit;
}

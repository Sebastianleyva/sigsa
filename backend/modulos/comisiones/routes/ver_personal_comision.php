<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/ver_personal_comision.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ERROR' => 'Metodo no permitido']);
    exit;
}

try {
    $usuario = validar_jwt_requerido();
    validar_csrf_requerido();

    $id_personal = filter_var($usuario['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $id_comision = filter_var($_GET['id_comision'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_personal === false || $id_comision === false) {
        http_response_code(400);
        echo json_encode(['ERROR' => 'El usuario o la comisión indicados no son válidos']);
        exit;
    }

    $consulta_personal = $pdo->prepare(
        "SELECT puesto, area FROM personal
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
    $area = filter_var($usuario_actual['area'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!in_array($puesto, [2, 3], true) || $area === false) {
        http_response_code(403);
        echo json_encode(['ERROR' => 'No tienes permisos para asignar comisionados']);
        exit;
    }

    $resultado = ver_personal_comision($pdo, $id_comision, $puesto, $area);
    http_response_code(200);
    echo json_encode($resultado);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['ERROR' => $e->getMessage()]);
    exit;
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ERROR' => 'Error interno del servidor']);
    exit;
}

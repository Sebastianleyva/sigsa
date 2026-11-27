<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/informe_comision.php';

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

    $id_comision = filter_var($_GET['id_comision'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_comision === false) {
        http_response_code(400);
        echo json_encode(['ERROR' => 'El id_comision debe ser un entero positivo']);
        exit;
    }

    $datos = ver_datos_informe_comision($pdo, $id_comision, $id_personal);
    http_response_code(200);
    echo json_encode(['datos' => $datos]);
} catch (OutOfBoundsException $e) {
    http_response_code(404);
    echo json_encode(['ERROR' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ERROR' => 'Error interno del servidor']);
}

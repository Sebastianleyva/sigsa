<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/crear_solicitud_viaticos.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
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

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['ERROR' => 'Datos de entrada no válidos']);
        exit;
    }

    $solicitud = crear_solicitud_viaticos($pdo, $input, $id_personal);
    http_response_code(201);
    echo json_encode([
        'mensaje' => $solicitud['estado_comision'] === 'EN CURSO'
            ? 'Solicitud registrada. Todas las solicitudes de viáticos están completas y la comisión inició.'
            : 'Solicitud de viáticos registrada correctamente.',
        'solicitud' => $solicitud,
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['ERROR' => $e->getMessage()]);
} catch (OutOfBoundsException $e) {
    http_response_code(404);
    echo json_encode(['ERROR' => $e->getMessage()]);
} catch (DomainException $e) {
    http_response_code(403);
    echo json_encode(['ERROR' => $e->getMessage()]);
} catch (SolicitudViaticosConflictException $e) {
    http_response_code(409);
    echo json_encode(['ERROR' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ERROR' => 'Error interno del servidor']);
}

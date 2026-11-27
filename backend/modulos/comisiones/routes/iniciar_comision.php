<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/comisiones_programadas.php';

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
    $area_raw = filter_var($usuario_actual['area'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $area = $area_raw === false ? null : $area_raw;

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(['ERROR' => 'Datos de entrada no válidos']);
        exit;
    }

    $comision = iniciar_comision($pdo, $input, $puesto, $area, $id_personal);
    http_response_code(200);
    echo json_encode([
        'mensaje' => 'Comisión iniciada correctamente',
        'comision' => $comision,
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['ERROR' => $e->getMessage()]);
    exit;
} catch (DomainException $e) {
    http_response_code(403);
    echo json_encode(['ERROR' => $e->getMessage()]);
    exit;
} catch (OutOfBoundsException $e) {
    http_response_code(404);
    echo json_encode(['ERROR' => $e->getMessage()]);
    exit;
} catch (ComisionProgramadaConflictException $e) {
    http_response_code(409);
    echo json_encode(['ERROR' => $e->getMessage()]);
    exit;
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['ERROR' => 'Error interno del servidor']);
    exit;
}

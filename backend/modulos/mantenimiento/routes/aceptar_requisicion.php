<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/aceptar_requisicion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    http_response_code(405);
    echo json_encode(["ERROR" => "Metodo no permitido"]);
    exit;
}

try {
    $usuario = validar_jwt_requerido();
    validar_csrf_requerido();
    requerir_rol([1, 2]);

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["ERROR" => "El cuerpo de la solicitud debe ser un objeto JSON válido"]);
        exit;
    }

    $id_autoriza = filter_var(
        $usuario['sub'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $requisicion = aceptar_requisicion($pdo, $input, $id_autoriza);
    http_response_code(200);
    echo json_encode([
        "mensaje" => "Requisición aprobada correctamente",
        "requisicion" => $requisicion,
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(["ERROR" => $e->getMessage()]);
} catch (OutOfBoundsException $e) {
    http_response_code(404);
    echo json_encode(["ERROR" => $e->getMessage()]);
} catch (DomainException $e) {
    http_response_code(409);
    echo json_encode(["ERROR" => $e->getMessage()]);
} catch (Throwable $e) {
    error_log("Error al aprobar la requisición: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ERROR" => "Error interno al aprobar la requisición"]);
}

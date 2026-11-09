<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/actualizar_fecha_calendario.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    http_response_code(405);
    echo json_encode(["ERROR" => "Metodo no permitido"]);
    exit;
}

try {
    validar_jwt_requerido();
    validar_csrf_requerido();
    requerir_rol([1, 2, 3]);

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new InvalidArgumentException('El cuerpo debe ser un objeto JSON válido');
    }

    $evento = actualizar_fecha_calendario($pdo, $input);
    http_response_code(200);
    echo json_encode([
        "mensaje" => "Fecha de mantenimiento actualizada",
        "calendario" => $evento
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
} catch (PDOException $e) {
    error_log("Error al cambiar fecha de mantenimiento: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ERROR" => "Error al cambiar la fecha de mantenimiento"]);
} catch (Throwable $e) {
    error_log("Error inesperado al cambiar fecha de mantenimiento: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ERROR" => "Error interno al cambiar la fecha"]);
}

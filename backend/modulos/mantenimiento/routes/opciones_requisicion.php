<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/opciones_requisicion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["ERROR" => "Metodo no permitido"]);
    exit;
}

try {
    $usuario = validar_jwt_requerido();
    validar_csrf_requerido();
    requerir_permiso_mantenimiento($pdo, $usuario);
    $id_elabora = filter_var($usuario['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_elabora === false) throw new InvalidArgumentException('El usuario en sesión no es válido');
    echo json_encode(obtener_opciones_requisicion($pdo, $id_elabora));
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(["ERROR" => $e->getMessage()]);
} catch (Throwable $e) {
    error_log("Error al consultar opciones de requisición: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(["ERROR" => "No fue posible consultar las opciones de requisición"]);
}

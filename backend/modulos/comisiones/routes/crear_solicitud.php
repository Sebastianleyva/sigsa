<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/crear_solicitud.php';

if($_SERVER['REQUEST_METHOD'] !== 'POST'){
    http_response_code(405);
    echo json_encode(["ERROR"=>"Metodo no permitido"]);
    exit;
}

try{
    $usuario = validar_jwt_requerido();
    validar_csrf_requerido();

    $id_personal = filter_var($usuario['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_personal === false) {
        http_response_code(401);
        echo json_encode(["ERROR" => "Los datos de identidad no son válidos"]);
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
        echo json_encode(["ERROR" => "El usuario no está activo"]);
        exit;
    }

    $puesto = (int) $personal['puesto'];
    $area = filter_var($personal['area'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if (!in_array($puesto, [1, 2], true) && (int) $area !== 6) {
        http_response_code(403);
        echo json_encode(["ERROR" => "No tienes permisos para crear solicitudes"]);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        http_response_code(400);
        echo json_encode(["ERROR" => "El cuerpo de la solicitud debe ser un objeto JSON válido"]);
        exit;
    }

    $id_personal = filter_var($usuario['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $solicitud = crear_solicitud($pdo, $input, $id_personal);

    http_response_code(201);
    echo json_encode([
        'message'=>'solicitud creada exitosamente',
        'solicitud'=>$solicitud
        ]);
}catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(["ERROR" => $e->getMessage()]);
    exit;
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(["ERROR" => "Error interno del servidor"]);
    exit;
}
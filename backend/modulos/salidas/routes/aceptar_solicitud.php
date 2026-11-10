<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../../../includes/jerarquia_salidas.php';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'PATCH':
        $usuario = validar_jwt_requerido();
        validar_csrf_requerido();
        requerir_rol([1, 2, 3]);
        $input = json_decode(file_get_contents('php://input'), true);
        aceptar_solicitud($pdo, is_array($input) ? $input : [], $usuario);
        break;
    default:
        http_response_code(405);
        echo json_encode(["ERROR" => "Método no permitido"]);
        break;
}

function aceptar_solicitud(PDO $pdo, array $input, array $usuario): void
{
    $id_salida = filter_var($input['id_salida'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $id_autorizante = filter_var($usuario['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $requiere_justificante = $input['requiere_justificante'] ?? null;
    if ($id_salida === false || $id_autorizante === false || !is_bool($requiere_justificante)) {
        http_response_code(400);
        echo json_encode(["ERROR" => "La solicitud debe indicar si requiere justificante"]);
        return;
    }

    $pdo->beginTransaction();
    $bloquear_salida = $pdo->prepare('SELECT id_salida FROM salidas WHERE id_salida = :id_salida FOR UPDATE');
    $bloquear_salida->execute(['id_salida' => $id_salida]);
    if (!$bloquear_salida->fetch()) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(["ERROR" => "Solicitud inexistente"]);
        return;
    }

    $salida = obtener_salida_para_autorizacion($pdo, $id_salida);
    exigir_autorizacion_salida($pdo, $salida, $id_autorizante);
    if ($salida['estado'] !== 'PENDIENTE') {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(["ERROR" => "La solicitud ya fue atendida o no puede modificarse"]);
        return;
    }

    date_default_timezone_set('America/Mexico_City');
    $hora_salida = date('H:i:s');
    $hora_estimada = date('H:i:s', strtotime('+2 hour'));
    $actualizar = $pdo->prepare(
        "UPDATE salidas
         SET estado = 'EN CURSO', hora_salida = :hora_salida,
             hora_estimada = :hora_estimada, id_autoriza = :id_autoriza,
             requiere_justificante = :requiere_justificante,
             justificante_entregado = NULL
         WHERE id_salida = :id_salida AND estado = 'PENDIENTE'"
    );
    $actualizar->execute([
        'hora_salida' => $hora_salida,
        'hora_estimada' => $hora_estimada,
        'id_autoriza' => $id_autorizante,
        'requiere_justificante' => $requiere_justificante ? 1 : 0,
        'id_salida' => $id_salida
    ]);

    if ($actualizar->rowCount() !== 1) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(["ERROR" => "La solicitud ya fue atendida"]);
        return;
    }

    $pdo->commit();
    http_response_code(200);
    echo json_encode([
        "message" => "Solicitud aceptada con éxito",
        "salida" => [
            'id_salida' => $id_salida,
            'estado' => 'EN CURSO',
            'hora_salida' => $hora_salida,
            'hora_estimada' => $hora_estimada,
            'requiere_justificante' => $requiere_justificante,
            'id_autoriza' => $id_autorizante
        ]
    ]);
}

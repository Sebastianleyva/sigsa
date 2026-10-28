<?php

header ('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../../../includes/jerarquia_salidas.php';

switch($_SERVER['REQUEST_METHOD']){
    case 'PATCH':
        $usuario = validar_jwt_requerido();
        validar_csrf_requerido();
        requerir_rol([1,2,3]);
        $input =  json_decode(file_get_contents('php://input'), true ) ?? [];
        finalizar_salida($pdo,$input,$usuario);
        break;
    default:
        http_response_code(405);
        echo json_encode(["ERROR"=>"Metodo no permitido"]);
        break;
}

function finalizar_salida($pdo,$input,$usuario){
    $id_salida = filter_var($input['id_salida'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $id_autorizante = filter_var($usuario['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_salida === false || $id_autorizante === false) {
        http_response_code(400);
        echo json_encode(["ERROR" => "El identificador de la solicitud no es válido"]);
        return;
    }

    $pdo->beginTransaction();
    $bloquear_salida = $pdo->prepare(
        'SELECT id_salida FROM salidas WHERE id_salida = :id_salida FOR UPDATE'
    );
    $bloquear_salida->execute(['id_salida' => $id_salida]);
    if (!$bloquear_salida->fetch()) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(["ERROR" => "Solicitud inexistente"]);
        return;
    }

    $salida_autorizable = obtener_salida_para_autorizacion($pdo, $id_salida);
    exigir_autorizacion_salida($pdo, $salida_autorizable, $id_autorizante, true);

    $validar_estado = $pdo ->prepare(
        'SELECT estado, fecha, hora_salida, hora_estimada FROM salidas
        WHERE id_salida = :id_salida'
    );
    $validar_estado -> execute([
        'id_salida' => $id_salida
    ]);
    $salida = $validar_estado->fetch();
    if (!$salida || $salida['estado'] !== "EN CURSO") {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode([
            "ERROR" => "La solicitud no puede ser modificada"
        ]);
        exit;
    }

    $nuevo_estado = "FINALIZADO";
    date_default_timezone_set('America/Mexico_City');
    $hora_llegada = date('H:i:s');
    $fecha_estimada = $salida['fecha'];
    if ($salida['hora_estimada'] < $salida['hora_salida']) {
        $fecha_estimada = date('Y-m-d', strtotime($fecha_estimada . ' +1 day'));
    }

    $llegada = new DateTimeImmutable(date('Y-m-d') . ' ' . $hora_llegada);
    $llegada_estimada = new DateTimeImmutable($fecha_estimada . ' ' . $salida['hora_estimada']);
    $adeudo_tiempo = null;

    if ($llegada > $llegada_estimada) {
        $segundos_adeudo = $llegada->getTimestamp() - $llegada_estimada->getTimestamp();
        $horas = intdiv($segundos_adeudo, 3600);
        $minutos = intdiv($segundos_adeudo % 3600, 60);
        $segundos = $segundos_adeudo % 60;
        $adeudo_tiempo = sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos);
    }
        
    $query = $pdo -> prepare(
        'UPDATE salidas 
        SET estado = :nuevo_estado,
        hora_llegada = :hora_llegada,
        adeudo_tiempo = :adeudo_tiempo
        WHERE id_salida = :id_salida'
    );
    $query -> execute([
        'id_salida' => $id_salida,
        'nuevo_estado' => $nuevo_estado,
        'hora_llegada' => $hora_llegada,
        'adeudo_tiempo' => $adeudo_tiempo
    ]);

    if ($query->rowCount() !== 1) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(["ERROR" => "La salida ya fue finalizada"]);
        return;
    }

    $pdo->commit();
    http_response_code(200);
    echo json_encode([
        "message" => "Salida finalizada correctamente",
        "salida" => [
            "id_salida" => $id_salida,
            "estado" => $nuevo_estado,
            "hora_llegada" => $hora_llegada,
            "adeudo_tiempo" => $adeudo_tiempo
        ]
    ]);
}
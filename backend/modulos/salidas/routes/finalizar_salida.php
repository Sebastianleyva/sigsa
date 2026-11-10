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
        $input = json_decode(file_get_contents('php://input'), true);
        $input = is_array($input) ? $input : [];
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

    $validar_estado = $pdo->prepare(
        'SELECT estado, fecha, hora_salida, hora_estimada, hora_llegada,
                fecha_llegada, adeudo_tiempo, requiere_justificante,
                justificante_entregado
         FROM salidas
        WHERE id_salida = :id_salida'
    );
    $validar_estado->execute(['id_salida' => $id_salida]);
    $salida = $validar_estado->fetch();
    if (!$salida || $salida['estado'] !== "EN CURSO") {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode([
            "ERROR" => "La solicitud no puede ser modificada"
        ]);
        exit;
    }

    date_default_timezone_set('America/Mexico_City');
    $requiere_justificante = (int) $salida['requiere_justificante'] === 1;
    $justificante_entregado = $input['justificante_entregado'] ?? null;
    if (($requiere_justificante && !is_bool($justificante_entregado))
        || (!$requiere_justificante && $justificante_entregado !== null)) {
        $pdo->rollBack();
        http_response_code(400);
        echo json_encode(["ERROR" => "La respuesta del justificante no coincide con la solicitud"]);
        return;
    }

    $fecha_llegada = $salida['fecha_llegada'];
    $hora_llegada = $salida['hora_llegada'];
    $adeudo_tiempo = $salida['adeudo_tiempo'];
    $regreso_registrado = $hora_llegada !== null
        && $fecha_llegada !== null
        && $fecha_llegada !== '0000-00-00';

    if (!$regreso_registrado) {
        $fecha_llegada = date('Y-m-d');
        $hora_llegada = date('H:i:s');
        $adeudo_tiempo = null;

        if ($salida['hora_salida'] !== null && $salida['hora_estimada'] !== null) {
            $hora_salida = new DateTimeImmutable($salida['fecha'] . ' ' . $salida['hora_salida']);
            $hora_estimada = new DateTimeImmutable($salida['fecha'] . ' ' . $salida['hora_estimada']);
            if ($hora_estimada <= $hora_salida) {
                $hora_estimada = $hora_estimada->modify('+1 day');
            }
            $llegada = new DateTimeImmutable($fecha_llegada . ' ' . $hora_llegada);

            if ($llegada > $hora_estimada) {
                $segundos_adeudo = $llegada->getTimestamp() - $hora_estimada->getTimestamp();
                $horas = intdiv($segundos_adeudo, 3600);
                $minutos = intdiv($segundos_adeudo % 3600, 60);
                $segundos = $segundos_adeudo % 60;
                $adeudo_tiempo = sprintf('%02d:%02d:%02d', $horas, $minutos, $segundos);
            }
        }
    }

    $pendiente_justificante = $requiere_justificante && !$justificante_entregado;
    $nuevo_estado = $pendiente_justificante ? 'EN CURSO' : 'FINALIZADO';
    $valor_justificante_entregado = $requiere_justificante
        ? ($justificante_entregado ? 1 : 0)
        : null;

    $query = $pdo->prepare(
        'UPDATE salidas
         SET estado = :nuevo_estado, hora_llegada = :hora_llegada,
             fecha_llegada = :fecha_llegada, adeudo_tiempo = :adeudo_tiempo,
             justificante_entregado = :justificante_entregado
         WHERE id_salida = :id_salida'
    );
    $query->execute([
        'id_salida' => $id_salida,
        'nuevo_estado' => $nuevo_estado,
        'hora_llegada' => $hora_llegada,
        'fecha_llegada' => $fecha_llegada,
        'adeudo_tiempo' => $adeudo_tiempo,
        'justificante_entregado' => $valor_justificante_entregado
    ]);

    $pdo->commit();
    http_response_code(200);
    echo json_encode([
        "message" => $pendiente_justificante
            ? "Salida marcada como pendiente de justificante"
            : "Salida finalizada correctamente",
        "salida" => [
            "id_salida" => $id_salida,
            "estado" => $nuevo_estado,
            "hora_llegada" => $hora_llegada,
            "fecha_llegada" => $fecha_llegada,
            "adeudo_tiempo" => $adeudo_tiempo,
            "requiere_justificante" => $requiere_justificante,
            "justificante_entregado" => $valor_justificante_entregado
        ]
    ]);
}
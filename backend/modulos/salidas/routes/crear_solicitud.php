<?php

header('content-type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

switch($_SERVER['REQUEST_METHOD']){
    case 'POST':
        $usuario = validar_jwt_requerido();
        validar_csrf_requerido();
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            http_response_code(400);
            echo json_encode(["ERROR" => "El cuerpo de la solicitud debe ser un objeto JSON válido"]);
            exit;
        }
        crear_solicitud($pdo, $input, $usuario);
        break;
    default:
        http_response_code(405);
        echo json_encode(["ERROR"=>"Metodo no permitido"]);
        break;
}

function crear_solicitud($pdo, $input, $usuario) {
    $tipo_salida = $input['tipo_salida'] ?? null;
    $motivo = $input['motivo'] ?? null;
    $destino = $input['destino'] ?? null;

    if (!is_string($tipo_salida) || !is_string($motivo) || !is_string($destino)) {
        http_response_code(400);
        echo json_encode(["ERROR" => "Campos obligatorios faltantes (tipo_salida, motivo, destino)"]);
        exit;
    }

    $tipo_salida = trim($tipo_salida);
    $motivo = trim($motivo);
    $destino = trim($destino);

    if ($tipo_salida === '' || $motivo === '' || $destino === '') {
        http_response_code(400);
        echo json_encode(["ERROR" => "Tipo de salida, motivo y destino no pueden estar vacíos"]);
        exit;
    }

    if (!in_array($tipo_salida, ['PERSONAL', 'MEDICA', 'CITA'], true)) {
        http_response_code(400);
        echo json_encode(["ERROR" => "El tipo de salida no es válido"]);
        exit;
    }

    $id_personal = filter_var($usuario['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $solicita_respaldo = $input['solicita_respaldo'] ?? false;
    if ($id_personal === false) {
        http_response_code(401);
        echo json_encode(["ERROR" => "Los datos de identidad no son válidos"]);
        exit;
    }
    if (!is_bool($solicita_respaldo)) {
        http_response_code(400);
        echo json_encode(["ERROR" => "La solicitud de respaldo debe ser verdadera o falsa"]);
        exit;
    }

    $consulta_personal = $pdo->prepare(
        "SELECT p.id_personal, p.puesto, p.area, p.id_jefe, p.activo, a.id_subdireccion
         FROM personal p
         LEFT JOIN areas a ON a.id_area = p.area
         WHERE p.id_personal = :id_personal"
    );
    $consulta_personal->execute(['id_personal' => $id_personal]);
    $solicitante = $consulta_personal->fetch();
    if (!$solicitante || $solicitante['activo'] !== '1') {
        http_response_code(403);
        echo json_encode(["ERROR" => "El usuario no está activo"]);
        exit;
    }

    $puesto = (int) $solicitante['puesto'];
    if (!in_array($puesto, [1, 2, 3, 4], true)) {
        http_response_code(403);
        echo json_encode(["ERROR" => "El puesto no puede registrar salidas"]);
        exit;
    }
    if ($solicita_respaldo && $puesto !== 4) {
        http_response_code(400);
        echo json_encode(["ERROR" => "Solo un trabajador puede solicitar revisión del subdirector"]);
        exit;
    }

    if (in_array($puesto, [3, 4], true) && $solicitante['id_jefe'] === null && !$solicita_respaldo) {
        http_response_code(409);
        echo json_encode(["ERROR" => "No tienes un jefe asignado para revisar la solicitud"]);
        exit;
    }

    if ($solicita_respaldo && $solicitante['id_subdireccion'] === null) {
        http_response_code(409);
        echo json_encode(["ERROR" => "El departamento no está vinculado a una subdirección"]);
        exit;
    }

    if ($puesto === 3 && $solicitante['id_subdireccion'] === null) {
        http_response_code(409);
        echo json_encode(["ERROR" => "El departamento no está vinculado a una subdirección"]);
        exit;
    }

    if ($solicita_respaldo) {
        $subdirector_activo = $pdo->prepare(
            "SELECT id_personal FROM personal
             WHERE puesto = 2 AND area = :area AND activo = '1'
             LIMIT 1"
        );
        $subdirector_activo->execute(['area' => $solicitante['id_subdireccion']]);
        if (!$subdirector_activo->fetch()) {
            http_response_code(409);
            echo json_encode(["ERROR" => "No hay un subdirector activo asignado a esta subdirección"]);
            exit;
        }
    }

    if ($puesto === 3 || ($puesto === 4 && !$solicita_respaldo)) {
        $puesto_jefe_esperado = $puesto === 3 ? 2 : 3;
        $area_jefe_esperada = $puesto === 3 ? $solicitante['id_subdireccion'] : $solicitante['area'];
        $jefe_activo = $pdo->prepare(
            "SELECT id_personal FROM personal
             WHERE id_personal = :id_jefe AND puesto = :puesto
               AND area <=> :area AND activo = '1'"
        );
        $jefe_activo->execute([
            'id_jefe' => $solicitante['id_jefe'],
            'puesto' => $puesto_jefe_esperado,
            'area' => $area_jefe_esperada
        ]);
        if (!$jefe_activo->fetch()) {
            http_response_code(409);
            echo json_encode(["ERROR" => "El jefe directo no está activo; solicita revisión del subdirector"]);
            exit;
        }
    }

    date_default_timezone_set('America/Mexico_City');
    $fecha = date('Y-m-d');
    $requiere_autorizacion = in_array($puesto, [3, 4], true);
    $estado = $requiere_autorizacion ? "PENDIENTE" : "EN CURSO";
    $hora_salida = $requiere_autorizacion ? null : date('H:i:s');
    $hora_estimada = $requiere_autorizacion ? null : date('H:i:s', strtotime('+2 hour'));

    try {
        $pdo->beginTransaction();
        $bloquear_personal = $pdo->prepare(
            'SELECT id_personal FROM personal WHERE id_personal = :id_personal FOR UPDATE'
        );
        $bloquear_personal->execute(['id_personal' => $id_personal]);

        $validacion_estado = $pdo->prepare(
            'SELECT id_salida FROM salidas
            WHERE estado IN ("PENDIENTE", "EN CURSO") AND id_personal = :id_personal'
        );
        $validacion_estado->execute([
            'id_personal' => $id_personal
        ]);

        if ($validacion_estado->fetch()) {
            $pdo->rollBack();
            http_response_code(409);
            echo json_encode(['ERROR' => "Ya cuenta con una solicitud de salida activa o pendiente"]);
            exit;
        }

        $query = $pdo->prepare(
            'INSERT INTO salidas
            (tipo_salida, motivo, destino, fecha, estado, id_personal, id_autoriza, hora_salida, hora_estimada, solicita_respaldo)
            VALUES
            (:tipo_salida, :motivo, :destino, :fecha, :estado, :id_personal, NULL, :hora_salida, :hora_estimada, :solicita_respaldo)'
        );

        $query->execute([
            'tipo_salida' => $tipo_salida,
            'motivo' => $motivo,
            'destino' => $destino,
            'fecha' => $fecha,
            'estado' => $estado,
            'id_personal' => $id_personal,
            'hora_salida' => $hora_salida,
            'hora_estimada' => $hora_estimada,
            'solicita_respaldo' => $solicita_respaldo ? 1 : 0
        ]);

        $id_salida = $pdo->lastInsertId();
        $pdo->commit();
    } catch (PDOException $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error al crear solicitud de salida: " . $error->getMessage());
        http_response_code(500);
        echo json_encode(["ERROR" => "No fue posible registrar la solicitud de salida"]);
        return;
    }

    http_response_code(201);
    echo json_encode([
        "message" => "Solicitud de salida creada con éxito",
        "salida"  => [
            "id_salida"     => $id_salida,
            "tipo_salida"   => $tipo_salida, 
            "motivo"        => $motivo, 
            "destino"       => $destino,  
            "fecha"         => $fecha, 
            "estado"        => $estado,
            "id_personal"   => $id_personal,
            "id_autoriza"   => null,
            "hora_salida"   => $hora_salida,
            "hora_estimada" => $hora_estimada,
            "solicita_respaldo" => $solicita_respaldo
        ]
    ]);
}

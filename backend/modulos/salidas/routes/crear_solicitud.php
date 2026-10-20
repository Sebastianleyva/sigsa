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
    $id_jefe_claim = $usuario['id_jefe'] ?? null;
    $id_jefe = $id_jefe_claim === null
        ? null
        : filter_var($id_jefe_claim, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($id_personal === false || ($id_jefe_claim !== null && $id_jefe === false)) {
        http_response_code(401);
        echo json_encode(["ERROR" => "Los datos de identidad de la sesión no son válidos"]);
        exit;
    }

    $fecha         = date('Y-m-d');
    $estado        = "PENDIENTE"; 
    $validacion_estado = $pdo->prepare(
        'SELECT id_salida FROM salidas
        WHERE estado IN ("PENDIENTE", "EN CURSO") AND id_personal = :id_personal'
    );
    $validacion_estado->execute([
        'id_personal' => $id_personal
    ]);

    if ($validacion_estado->fetch()) {
        http_response_code(409);
        echo json_encode(['ERROR' => "Ya cuenta con una solicitud de salida activa o pendiente"]);
        exit;
    }

    $query = $pdo->prepare(
        'INSERT INTO salidas 
        (tipo_salida, motivo, destino, fecha, estado, id_personal, id_autoriza)
        VALUES
        (:tipo_salida, :motivo, :destino, :fecha, :estado, :id_personal, :id_autoriza)'
    );

    $query->execute([
        'tipo_salida'   => $tipo_salida, 
        'motivo'        => $motivo, 
        'destino'       => $destino,  
        'fecha'         => $fecha,  
        'estado'        => $estado, 
        'id_personal'   => $id_personal, 
        'id_autoriza'   => $id_autoriza
    ]);

    $id_salida = $pdo->lastInsertId();    

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
            "id_autoriza"   => $id_autoriza
        ]
    ]);
}

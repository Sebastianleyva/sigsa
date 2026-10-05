<?php

header('content-type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

switch($_SERVER['REQUEST_METHOD']){
    case 'POST':
        $usuario = validar_jwt_requerido();
        validar_csrf_requerido();
        $input=json_decode(file_get_contents('php://input'),true) ?? [];
        crear_solicitud($pdo,$input);
        break;
    default:
        http_response_code(405);
        echo json_encode(["ERROR"=>"Metodo no permitido"]);
        break;
}

function crear_solicitud($pdo, $input) {
    if (empty($input['tipo_salida']) || empty($input['motivo']) || empty($input['fecha']) || empty($input['hora_salida'])) {
        http_response_code(400);
        echo json_encode(["ERROR" => "Campos obligatorios faltantes (tipo_salida, motivo, fecha, hora_salida)"]);
        exit;
    }

    $id_personal   = $_SESSION['id_personal'];
    $tipo_salida   = $input['tipo_salida'];
    $motivo        = $input['motivo'];
    $destino       = $input['destino'] ;
    $fecha         = date('Y-m-d');
    $hora_salida   = $input['hora_salida'];
    $hora_estimada = $input['hora_estimada'] ?? null;
    $hora_llegada  = $input['hora_llegada'] ?? null;
    $estado        = "PENDIENTE"; 
    $validacion_estado = $pdo->prepare(
        'SELECT id_salida FROM salidas WHERE estado = "PENDIENTE" AND id_personal = :id_personal'
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
        (tipo_salida, motivo, destino, fecha, hora_salida, hora_llegada, estado, id_personal, hora_estimada)
        VALUES
        (:tipo_salida, :motivo, :destino, :fecha, :hora_salida, :hora_llegada, :estado, :id_personal, :hora_estimada)'
    );

    $query->execute([
        'tipo_salida'   => $tipo_salida, 
        'motivo'        => $motivo, 
        'destino'       => $destino,  
        'fecha'         => $fecha, 
        'hora_salida'   => $hora_salida, 
        'hora_llegada'  => $hora_llegada, 
        'estado'        => $estado, 
        'id_personal'   => $id_personal, 
        'hora_estimada' => $hora_estimada
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
            "hora_salida"   => $hora_salida, 
            "hora_estimada" => $hora_estimada,
            "estado"        => $estado, 
            "id_personal"   => $id_personal, 
        ]
    ]);
}


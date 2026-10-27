<?php

header ('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

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

function verificacion_jerarquia($pdo,$input,$usuario){
    if(empty($input)){
        http_response_code(400);
        echo json_encode(["ERROR" => "Campos faltantes"]);
        exit;
    }
    $id_autorizante = filter_var(
        $usuario['sub'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $id_salida = $input['id_salida'];

    $sql = $pdo -> prepare(
        'SELECT id_personal FROM salidas 
        WHERE id_salida = :id_salida'
    );
    $sql -> execute (['id_salida'=>$id_salida]);
    $resultado1 = $sql -> fetch();
    if(!$resultado1){
        http_response_code(404);
        echo json_encode(["ERROR"=>"Solicitud inexistente"]);
        exit;
    }
    $id_solicitante = $resultado1['id_personal'];

    $verificacion = $pdo -> prepare(
        'SELECT id_jefe FROM personal
        WHERE id_personal = :id_solicitante'
    );
    $verificacion -> execute(['id_solicitante' => $id_solicitante]);
    $resultado2 = $verificacion -> fetch();

    if($resultado2['id_jefe']!=$id_autorizante){
        http_response_code(403);
        echo json_encode(["ERROR"=>"No puedes autorizar esta salida"]);
        exit;
    }
}

function finalizar_salida($pdo,$input,$usuario){
    verificacion_jerarquia($pdo,$input,$usuario);

    $id_salida = $input['id_salida'];

    $validar_estado = $pdo ->prepare(
        'SELECT estado, fecha, hora_salida, hora_estimada FROM salidas
        WHERE id_salida = :id_salida'
    );
    $validar_estado -> execute([
        'id_salida' => $id_salida
    ]);
    $salida = $validar_estado->fetch();
    if($salida['estado']!="EN CURSO"){
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

    http_response_code(200);
    echo json_encode([
        "message" => "Salida finalizada correctamente",
        "salida" => $query
    ]);
}
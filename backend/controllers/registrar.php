<?php
header('Content-type: application/json');

require_once '../includes/auth.php';
require_once '../includes/conexion.php';

switch($_SERVER['REQUEST_METHOD']){
    case 'POST':
        $input=json_decode(file_get_contents('php://input'),true) ?? [];
        register($pdo,$input);
        break;
    default:
        http_response_code(405);
        echo json_encode(["ERROR"=>"Metodo no permitido"]);
        break;
}

function register($pdo,$input){
    if(empty($input)){
        http_response_code(400);
        echo json_encode(["ERROR"=>"Su solicitud está vacía"]);
        exit;
    }
    
    elseif(empty($input['correo']) || empty($input['contrasena'])){
        http_response_code(400);
        echo json_encode(["ERROR"=>"Campos obligatorios faltantes"]);
        exit;
    }

    $rfc= $input['rfc'];
    $curp= $input['curp'];
    $nombre= $input['nombre'];
    $apellido_p = $input['apellido_p'];
    $apellido_m = $input['apellido_m'];
    $correo = $input['correo']; 
    $telefono = $input['telefono']; 
    $puesto = $input['puesto'];
    $area = $input['area'] ?? null; 
    $id_jefe = $input['id_jefe'] ?? null; 
    $contrasena = password_hash($input['contrasena'],PASSWORD_BCRYPT);

    $consulta_validacion = $pdo->prepare(
        "SELECT id_personal 
        FROM personal 
        WHERE correo = :correo or 
        rfc = :rfc OR curp = :curp OR telefono = :telefono"
    );
    $consulta_validacion->execute([
        'correo' => $correo,
        'rfc' => $rfc,
        'curp' => $curp,
        'telefono' => $telefono]);
    if($consulta_validacion->fetch()){
        http_response_code(409);
        echo json_encode(["CONFLICT"=>"Campos invalidos"]);
        exit;
    }

    $query = $pdo->prepare(
        "INSERT INTO personal
        (rfc, curp, nombre, apellido_p, apellido_m, correo, telefono, puesto, area, id_jefe, contrasena)
        VALUES 
        (:rfc, :curp, :nombre, :apellido_p, :apellido_m, :correo, :telefono, :puesto, :area, :id_jefe, :contrasena)"
    );
    $query -> execute([
        'rfc' => $rfc,
        'curp' => $curp,
        'nombre' => $nombre,
        'apellido_p' => $apellido_p,
        'apellido_m' => $apellido_m,
        'correo' => $correo,
        'telefono' => $telefono,
        'puesto' => $puesto,
        'area' => $area,
        'id_jefe' => $id_jefe,
        'contrasena' => $contrasena
    ]);    
    
    $nuevoid = $pdo -> lastInsertID();

    http_response_code(201);
    echo json_encode([
        "message"=>"Usuario creado con exito",
        "usuario"=>[
            "id" => $nuevoid,
            "nombre" => $nombre,
            "curp" => $curp,
            "correo" => $correo
        ]]);
}

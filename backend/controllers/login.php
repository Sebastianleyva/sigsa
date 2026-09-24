<?php

header('Content-type: application/json');

require_once '../includes/auth.php';
require_once '../includes/conexion.php';

switch ($_SERVER['REQUEST_METHOD']){
    case 'POST':
        $input=json_decode(file_get_contents('php://input'),true) ?? [];
        login($pdo,$input);
        break;
    default:
        http_response_code(405);
        echo json_encode(["ERROR"=>"Metodo no permitido"]) ;
        break;
}


function login($pdo, $input) {
    if (empty($input['correo']) || empty($input['contrasena'])) {
        http_response_code(400);
        echo json_encode(['ERROR' => 'Campos incompletos en su solicitud']);
        exit;
    }

    $correo = $input['correo'];
    $query = $pdo->prepare("SELECT * FROM personal WHERE correo = :correo");
    $query->execute(['correo' => $correo]);
    $usuario = $query->fetch();

//    $hash_fresco = password_hash($input['contrasena'], PASSWORD_BCRYPT);
    
//     echo json_encode([
//         'input_recibido'      => $input['contrasena'],
//         'hash_en_bd'          => $usuario['contrasena'],
//         'evaluacion_bd'       => password_verify($input['contrasena'], $usuario['contrasena']),
//         'hash_nuevo_generado' => $hash_fresco,
//         'evaluacion_nuevo'    => password_verify($input['contrasena'], $hash_fresco)
//     ]);
//     exit;
    if (!$usuario || !password_verify($input['contrasena'], $usuario['contrasena'])) {
        http_response_code(401);
        echo json_encode(["ERROR" => "Credenciales invalidas"]);
        exit;
    }

    session_regenerate_id(true);

    $_SESSION['id_personal'] = $usuario['id_personal'];
    $_SESSION['id_jefe']     = $usuario['id_jefe'];
    $_SESSION['area']        = $usuario['area'];
    $_SESSION['nombre']      = $usuario['nombre'];

    http_response_code(200);
    echo json_encode([
        "message" => "Inicio de sesión exitoso",
        "usuario" => [
            "nombre" => $_SESSION['nombre'],
            "area"   => $_SESSION['area']
        ]
    ]);
}
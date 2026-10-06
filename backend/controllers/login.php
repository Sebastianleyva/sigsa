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

    if (!$usuario || !password_verify($input['contrasena'], $usuario['contrasena'])) {
        http_response_code(401);
        echo json_encode(["ERROR" => "Credenciales invalidas"]);
        exit;
    }

    try {
        $ahora = time();
        $jwt = crear_jwt([
            'sub' => (int) $usuario['id_personal'],
            'id_jefe' => $usuario['id_jefe'],
            'area' => $usuario['area'],
            'iat' => $ahora,
            'exp' => $ahora + 3600,
            'iss' => 'sigsa'
        ]);
    } catch (RuntimeException $e) {
        error_log($e->getMessage());
        http_response_code(500);
        echo json_encode(["ERROR" => "No se pudo configurar la autenticación"]);
        exit;
    }

    session_regenerate_id(true);

    $_SESSION['id_personal'] = $usuario['id_personal'];
    $_SESSION['id_jefe']     = $usuario['id_jefe'];
    $_SESSION['area']        = $usuario['area'];
    $_SESSION['nombre']      = $usuario['nombre'];
    $_SESSION['correo']      = $usuario['correo'];
    $_SESSION['puesto']      = $usuario['puesto'];
    $_SESSION['token']       = $jwt;
    $_SESSION['csrf_token']  = bin2hex(random_bytes(32));

    http_response_code(200);
    echo json_encode([
        "message" => "Inicio de sesión exitoso",
        "token" => $jwt,
        "csrf_token" => $_SESSION['csrf_token'],
        "usuario" => [
            "id_personal" => $_SESSION['id_personal'],
            "id_jefe" => $_SESSION['id_jefe'],
            "nombre" => $_SESSION['nombre'],
            "correo" => $_SESSION['correo'],
            "area"   => $_SESSION['area'],
            "puesto" => $_SESSION['puesto']
        ]
    ]);
}
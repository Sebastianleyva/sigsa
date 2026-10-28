<?php

header('Content-type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

switch($_SERVER['REQUEST_METHOD']){
    case 'PATCH':
        $usuario = validar_jwt_requerido();
        validar_csrf_requerido();
        $input=json_decode(file_get_contents('php://input'),true) ?? [];
        cambiar_contra($pdo,$input,$usuario);
        break;
    default:
        http_response_code(405);
        echo json_encode(["ERROR"=>"Metodo no permitido"]) ;
        break;
}

function cambiar_contra($pdo,$input,$usuario){
    if(empty($input) || empty($input['contrasena'] || empty($input['nueva_contrasena  ']))){
        http_response_code(400);
        echo json_encode(["ERROR" =>"Campos faltantes"]);
        exit;
    }

    $id_personal = filter_var($usuario['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $contrasena = $input['contrasena'];

    $verificacion = $pdo -> prepare(
        'SELECT contrasena
        FROM personal
        WHERE id_personal = :id_personal'
    );
    $verificacion -> execute([
        "id_personal" => $id_personal
    ]);
    $resultado = $verificacion -> fetch();

    if(!password_verify($contrasena,$resultado['contrasena'])){
        http_response_code(409);
        echo json_encode(["ERROR" => "Contraseña incorrecta"]);
        exit;
    }

    $nueva_contrasena = password_hash($input['nueva_contrasena'],PASSWORD_BCRYPT);
    $query = $pdo -> prepare(
        'UPDATE personal
        SET contrasena = :nueva_contrasena
        WHERE id_personal = :id_personal'
    );
    $query -> execute([
        "nueva_contrasena" => $nueva_contrasena,
        "id_personal" => $id_personal
    ]);

    http_response_code(200);
    echo json_encode(["MESSAGE" => "Contraseña actualizada"]);
}
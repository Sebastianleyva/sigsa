<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $usuario = validar_jwt_requerido();
        validar_csrf_requerido();
        requerir_rol([1, 2, 3]);
        ver_salidas($pdo, $usuario);
        break;
    default:
        http_response_code(405);
        echo json_encode(["ERROR" => "Método no permitido"]);
        break;
}


function ver_salidas($pdo, $usuario) {
   $estado = 'PENDIENTE';

   $sql = $pdo -> prepare(
    'SELECT * FROM salidas
    WHERE estado = :estado'
   );

   $sql -> execute(['estado' => $estado]);
    $resultado = $sql -> fetchAll();

    http_response_code(200);
    echo json_encode([
        "message" => "Salidas cargadas correctamente",
        "Número de salidas" => count($resultado),
        "Salidas" => $resultado
    ]);
}
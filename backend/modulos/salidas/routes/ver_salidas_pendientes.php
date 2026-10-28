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
    $id_usuario = filter_var($usuario['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_usuario === false) {
        http_response_code(401);
        echo json_encode(["ERROR" => "Los datos de identidad no son válidos"]);
        return;
    }

    $sql = $pdo->prepare(
        "SELECT s.id_salida, s.tipo_salida, s.motivo, s.destino, s.fecha,
                s.solicita_respaldo, p.nombre, p.apellido_p, p.apellido_m,
                p.id_personal, p.area
         FROM salidas s
         JOIN personal p ON p.id_personal = s.id_personal
         LEFT JOIN areas a ON a.id_area = p.area
         JOIN personal u ON u.id_personal = :id_usuario
         WHERE s.estado = 'PENDIENTE' AND u.activo = '1'
           AND (
               p.id_jefe = u.id_personal
               OR (
                   u.puesto = 2
                   AND s.solicita_respaldo = 1
                   AND p.puesto = 4
                   AND a.id_subdireccion = u.area
               )
           )
         ORDER BY s.fecha DESC, s.id_salida DESC"
    );
    $sql->execute(['id_usuario' => $id_usuario]);
    $resultado = $sql->fetchAll();

    http_response_code(200);
    echo json_encode([
        "message" => "Salidas cargadas correctamente",
        "Salidas" => $resultado
    ]);
}
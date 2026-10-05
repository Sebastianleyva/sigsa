<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        $usuario = validar_jwt_requerido();
        ver_salidas($pdo);
        break;
    default:
        http_response_code(405);
        echo json_encode(["ERROR" => "Método no permitido"]);
        break;
}

function ver_salidas($pdo) {
    
    $estado = "PENDIENTE";

    if ($id_personal) {
        $query = $pdo->prepare(
            'SELECT s.*, p.nombre, p.apellido_p 
             FROM salidas s
             INNER JOIN personal p ON s.id_personal = p.id_personal
             WHERE s.estado = :estado AND s.id_personal = :id_personal
             ORDER BY s.fecha DESC, s.hora_salida DESC'
        );
        $query->execute([
            'estado'      => $estado,
            'id_personal' => $id_personal
        ]);
    } else {
        // Consulta general de todas las pendientes (para Jefes / Administradores)
        $query = $pdo->prepare(
            'SELECT s.*, p.nombre, p.apellido_p 
             FROM salidas s
             INNER JOIN personal p ON s.id_personal = p.id_personal
             WHERE s.estado = :estado
             ORDER BY s.fecha DESC, s.hora_salida DESC'
        );
        $query->execute([
            'estado' => $estado
        ]);
    }

    // PDO::FETCH_ASSOC evita duplicar columnas numéricas en el JSON
    $salidas = $query->fetchAll(PDO::FETCH_ASSOC);

    // Respuesta con el conteo total y el arreglo de registros
    http_response_code(200);
    echo json_encode([
        "message" => "Operación realizada con éxito",
        "total"   => count($salidas),
        "salidas" => $salidas
    ]);
}
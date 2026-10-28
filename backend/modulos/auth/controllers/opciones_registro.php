<?php
header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        validar_jwt_requerido();
        validar_csrf_requerido();
        requerir_rol([1, 2, 3]);

        $areas = $pdo->query(
            'SELECT id_area, nombre, id_subdireccion
             FROM areas
             ORDER BY nombre'
        )->fetchAll();
        $personal = $pdo->query(
            "SELECT id_personal, nombre, apellido_p, apellido_m, puesto, area
             FROM personal
             WHERE activo = '1' AND puesto IN (1, 2, 3)
             ORDER BY puesto, nombre, apellido_p"
        )->fetchAll();

        http_response_code(200);
        echo json_encode([
            'areas' => $areas,
            'personal' => $personal
        ]);
        break;
    default:
        http_response_code(405);
        echo json_encode(["ERROR" => "Método no permitido"]);
        break;
}

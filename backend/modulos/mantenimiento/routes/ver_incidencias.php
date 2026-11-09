<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/rechazar_incidencia.php';

if($_SERVER['REQUEST_METHOD'] !== 'GET'){
    http_response_code(405);
    echo json_encode(["ERROR"=>"Metodo no permitido"]);
    exit;
}

tyr{
    validar_jwt_requerido();
    validar_csrf_requerido();
    requirer_rol([1, 2, 3]);

    $incidencias = ver_incidencias($pdo);

    http_response_code(200);
    echo json_encode(["message"=>"Incidencias cargadas correctamente", 
        "data"=>$incidencias]);
}catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["ERROR"=>"Error al cargar las incidencias: " . $e->getMessage()]);
    exit;
}
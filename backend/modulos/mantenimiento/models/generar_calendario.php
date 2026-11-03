<?php

header('Content-Type: application/json');

function generar_calendario($pdo,$input){

    if(empty($input) ){
        http_response_code(400);
        echo json_encode(["ERROR" => "Campos obligatorios faltantes o inválidos (fecha_inicio, fecha_fin)"]);
        exit;
    }

    try{
        $query = $pdo -> prepare(
            "SELECT periodicidad 
            FROM servicios
            WHERE id_servicio = :id_servicio"
        );
        $query->execute();
        $periodicidad = $query->fetchColumn();

        $stm = $pdo-> prepare(
            "INSERT INTO calendario 
            ()"
        )
    }
}
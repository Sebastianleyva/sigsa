<?php

header('Content-Type: application/json');


function crear_servicio($pdo, $input){
    
    if (!is_string($input['nombre']) || empty($input['periodicidad']) || !is_string($input['descripcion'])) {
        http_response_code(400);
        echo json_encode(["ERROR" => "Campos obligatorios faltantes (nombre, periodicidad, descripcion)"]);
        exit;
    }

    $nombre = $input['nombre'];
    $periodicidad = $input['periodicidad'];
    if(!is_int($periodicidad)){
        throw new InvalidArgumentException('El campo periodicidad debe ser un entero');
    }
    $descripcion = $input['descripcion'];

    try{$stm = $pdo -> prepare(
        "INSERT INTO servicios 
        (nombre, periodicidad, descripcion) 
        VALUES 
        (:nombre, :periodicidad, :descripcion)"
    );
    $stm -> execute([
        ':nombre' => $nombre,
        ':periodicidad' => $periodicidad,
        ':descripcion' => $descripcion
    ]);}
    catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["ERROR" => "Error al crear el servicio: " . $e->getMessage()]);
        exit;
    }
    return [
        'id' => $pdo->lastInsertId(),
        'nombre' => $nombre,
        'periodicidad' => $periodicidad,
        'descripcion' => $descripcion
    ];
}
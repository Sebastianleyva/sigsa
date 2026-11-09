<?php

header('Content-Type: application/json');


function crear_servicio($pdo, $input){
    if (!is_array($input)
        || !isset($input['nombre'], $input['periodicidad'], $input['descripcion'])
        || !is_string($input['nombre'])
        || !is_int($input['periodicidad'])
        || $input['periodicidad'] < 1
        || !is_string($input['descripcion'])) {
        http_response_code(400);
        echo json_encode(["ERROR" => "Campos obligatorios faltantes (nombre, periodicidad, descripcion)"]);
        exit;
    }

    $nombre = $input['nombre'];
    $periodicidad = $input['periodicidad'];
    $descripcion = $input['descripcion'];

    try{$stm = $pdo -> prepare(
        "INSERT INTO servicios 
        (nombre, periodicidad, descripcion, activo)
        VALUES 
        (:nombre, :periodicidad, :descripcion, 1)"
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
        'descripcion' => $descripcion,
        'activo' => 1
    ];
}
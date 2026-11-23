<?php

function crear_solicitud($pdo,$input, $id_personal){

    $fecha = date('Y-m-d');
    $institucion_solicita = $input['institucion_solicita'];
    $ubicacion = $input['ubicacion'];
    $nombre_solicita = $input['nombre_solicita'];
    $id_personal = $id_personal;

    $query = $pdo -> prepare(
        'INSERT INTO solicitudes
        (fecha, institucion_solicita, ubicacion, nombre_solicita, id_personal, comisionada)
        VALUES 
        (:fecha, :institucion_solicita, :ubicacion, :nombre_solicita, :id_personal, 0)'
    );
    $query -> execute([
        'fecha' => $fecha,
        'institucion_solicita' => $institucion_solicita,
        'ubicacion' => $ubicacion,
        'nombre_solicita' => $nombre_solicita,
        'id_personal' => $id_personal
    ]);

    return[
        'id_solicitud' => $pdo->lastInsertId(),
        'fecha' => $fecha,
        'institucion_solicita' => $institucion_solicita,
        'ubicacion' => $ubicacion,
        'nombre_solicita' => $nombre_solicita,
        'id_personal' => $id_personal
    ];
}
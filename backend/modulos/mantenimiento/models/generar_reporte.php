<?php
require_once '../../../includes/auth.php';

function generar_reporte($pdo,$input){
    if(!is_array($input)){
        throw new InvalidArgumentException('La entrada debe ser un JSON valido');
    }

    if(!is_string($input['tipo']) || !is_string($input['fecha_inicio']) ||
        !is_string($input['estado']) || !is_string($input['descripcion'])){
        throw new InvalidArgumentException('Los campos tipo, fecha_inicio, estado y descripcion son obligatorios y deben ser cadenas de texto');
    }

    $tipo = $input['tipo'];
    $id_calendario = $input['id_calendario'] ?? null;
    $id_incidencia = $input['id_incidencia'] ?? null;
    if(!$id_calendario && !$id_incidencia){
        throw new InvalidArgumentException('El reporte debe estar asociado a un calendario o a una incidencia');
    }
    $id_personal = $input['id_personal'] ?? null;
    $fecha_inicio = $input['fecha_inicio'];
    $estado = $input['estado'];
    $descripcion = $input['descripcion'];

    $stm = $pdo->prepare(
        "INSERT INTO reportes
        (tipo, id_calendario, id_incidencia, id_personal, fecha_inicio, estado, descripcion)
        VALUES
        (:tipo, :id_calendario, :id_incidencia, :id_personal, :fecha_inicio, :estado, :descripcion)"
    );
    $stm->execute([
        'tipo' => $tipo,
        'id_calendario' => $id_calendario,
        'id_incidencia' => $id_incidencia,
        'id_personal' => $id_personal ?? null,
        'fecha_inicio' => $fecha_inicio,
        'estado' => $estado,
        'descripcion' => $descripcion
    ]);

    return [
        'id_reporte' => $pdo->lastInsertId(),
        'tipo' => $tipo,
        'id_calendario' => $id_calendario,
        'id_incidencia' => $id_incidencia,
        'id_personal' => $id_personal ?? null,
        'fecha_inicio' => $fecha_inicio,
        'estado' => $estado,
        'descripcion' => $descripcion
    ];
}
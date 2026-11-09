<?php

function generar_requisicion($pdo,$input,$usuario){
    if(empty($input['id_reporte']) || !is_int($input['id_reporte'])){
        throw new InvalidArgumentException('El campo id_reporte es obligatorio y debe ser un entero');
    }
    if(!is_string($input['justificacion']) || !is_string($input['fecha']) ||
    !is_string($input['descripcion_servicio']) || !is_string($input['estado'])){
        throw new InvalidArgumentException('Los campos justificacion, fecha, descripcion_servicio y estado son obligatorios y deben ser cadenas de texto');
    }
    
    $id_solicita = filter_var($usuario['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    $stm = $pdo ->prepare(
        
        "INSERT INTO requisiciones 
        (id_reporte, id_solicita, justificacion, fecha, descripcion_servicio, estado)
        VALUES
        (:id_reporte, :id_solicita, :justificacion, :fecha, :descripcion_servicio, :estado)"
    );
    $stm->execute([
        ':id_reporte' => $input['id_reporte'],
        ':id_solicita' => $id_solicita,
        ':justificacion' => $input['justificacion'],
        ':fecha' => $input['fecha'],
        ':descripcion_servicio' => $input['descripcion_servicio'],
        ':estado' => $input['estado']
    ]);

    return [
        'id_requisicion' => $pdo->lastInsertId(),
        'id_reporte' => $input['id_reporte'],
        'id_solicita' => $id_solicita,
        'justificacion' => $input['justificacion'],
        'fecha' => $input['fecha'],
        'descripcion_servicio' => $input['descripcion_servicio'],
        'estado' => $input['estado']
    ];





}
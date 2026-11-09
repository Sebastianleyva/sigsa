<?php

function crear_incidencia($pdo,$input,$usuario_id){

    if(!is_string($input['descripcion']) || !is_string($input['urgencia']) || !is_string($input['lugar']) ){
        http_response_code(400);
        echo json_encode(["ERROR" => "Campos obligatorios faltantes o inválidos (descripcion, id_personal, fecha, urgencia, lugar, estado)"]);
        exit;
    }

    try{
    $descripcion = $input['descripcion'];
    $id_personal = $usuario_id;
    $fecha = date('Y-m-d');
    $urgencia = $input['urgencia'];
    $lugar = $input['lugar'];
    $estado = 'PENDIENTE';
    $stm = $pdo->prepare(
        "INSERT INTO incidencias 
        (id_personal, descripcion, fecha, urgencia, estado,lugar) 
        VALUES 
        (:id_personal, :descripcion, :fecha, :urgencia, :estado,:lugar)"
    );
    $stm->execute([
        ':id_personal' => $id_personal,
        ':descripcion' => $descripcion,
        ':fecha' => $fecha,
        ':urgencia' => $urgencia,
        ':estado' => $estado,
        ':lugar' => $lugar
        ]);
    
    return [
        'id' => $pdo->lastInsertId(),
        'id_personal' => $id_personal,
        'descripcion' => $descripcion,
        'fecha' => $fecha,
        'urgencia' => $urgencia,
        'estado' => $estado,
        'lugar' => $lugar
    ];
        
    }catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["ERROR" => "Error al crear la incidencia: " . $e->getMessage()]);
        exit;
    }



}
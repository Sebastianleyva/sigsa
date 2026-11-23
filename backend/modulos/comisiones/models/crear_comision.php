<?php

function crear_comision($pdo,$input){
    if (!is_array($input)) {
        throw new InvalidArgumentException('La solicitud debe contener un objeto JSON válido');
    }

    $id_solicitud = filter_var($input['id_solicitud'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $id_area = filter_var($input['id_area'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_solicitud === false) {
        throw new InvalidArgumentException('El id_solicitud debe ser un entero positivo');
    }
    if ($id_area === false) {
        throw new InvalidArgumentException('El id_area debe ser un entero positivo');
    }

    $textos = [];
    foreach (['tipo_comision' => 30, 'tipo_destino' => 50] as $campo => $limite) {
        if (!isset($input[$campo]) || !is_string($input[$campo])) {
            throw new InvalidArgumentException("El campo {$campo} es obligatorio y debe ser texto");
        }

        $valor = trim($input[$campo]);
        $longitud = preg_match_all('/./us', $valor, $coincidencias);
        if ($longitud === false || $longitud === 0 || $longitud > $limite) {
            throw new InvalidArgumentException("El campo {$campo} no puede estar vacío ni exceder {$limite} caracteres");
        }
        $textos[$campo] = $valor;
    }

    $solicitud = $pdo->prepare(
        'SELECT id_solicitud FROM solicitudes WHERE id_solicitud = :id_solicitud'
        );
    $solicitud->execute(['id_solicitud' => $id_solicitud]);
    if (!$solicitud->fetchColumn()) {
        throw new InvalidArgumentException('La solicitud indicada no existe');
    }

    $consulta_area = $pdo->prepare(
        'SELECT id_area FROM areas WHERE id_area = :id_area'
        );
    $consulta_area->execute(['id_area' => $id_area]);
    if (!$consulta_area->fetchColumn()) {
        throw new InvalidArgumentException('El área indicada no existe');
    }

    $fecha = date('Y-m-d');
    $tipo_comision = $textos['tipo_comision'];
    $tipo_destino = $textos['tipo_destino'];
    $estado = 'PENDIENTE';

    $query = $pdo -> prepare(
        'INSERT INTO comisiones
        (id_solicitud, fecha, tipo_comision, tipo_destino, id_area, estado)
        VALUES 
        (:id_solicitud, :fecha, :tipo_comision, :tipo_destino, :id_area, :estado)'
    );
    $query -> execute([
        'id_solicitud' => $id_solicitud,
        'fecha' => $fecha,
        'tipo_comision' => $tipo_comision,
        'tipo_destino' => $tipo_destino,
        'id_area' => $id_area,
        'estado' => $estado
    ]);

    return[
        'id_comision' => $pdo->lastInsertId(),
        'id_solicitud' => $id_solicitud,
        'fecha' => $fecha,
        'tipo_comision' => $tipo_comision,
        'tipo_destino' => $tipo_destino,
        'id_area' => $id_area,
        'estado' => $estado
    ];
}

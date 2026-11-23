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

    $opciones = [
        'tipo_comision' => ['URBANA', 'FORANEA'],
        'tipo_destino' => ['CENTRO ESCOLAR', 'CENTRO ADMINISTRATIVO']
    ];
    $textos = [];
    foreach ($opciones as $campo => $valores_permitidos) {
        if (!isset($input[$campo]) || !is_string($input[$campo])) {
            throw new InvalidArgumentException("El campo {$campo} es obligatorio y debe ser texto");
        }

        $valor = strtoupper(trim($input[$campo]));
        if (!in_array($valor, $valores_permitidos, true)) {
            throw new InvalidArgumentException("El campo {$campo} contiene una opción no válida");
        }
        $textos[$campo] = $valor;
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

    $pdo->beginTransaction();
    try {
        $marcar_solicitud = $pdo->prepare(
            'UPDATE solicitudes
             SET comisionada = 1
             WHERE id_solicitud = :id_solicitud AND comisionada = 0'
        );
        $marcar_solicitud->execute(['id_solicitud' => $id_solicitud]);
        if ($marcar_solicitud->rowCount() !== 1) {
            throw new InvalidArgumentException('La solicitud no existe o ya fue comisionada');
        }

        $query = $pdo->prepare(
            'INSERT INTO comisiones
             (id_solicitud, fecha, tipo_comision, tipo_destino, id_area, estado)
             VALUES
             (:id_solicitud, :fecha, :tipo_comision, :tipo_destino, :id_area, :estado)'
        );
        $query->execute([
            'id_solicitud' => $id_solicitud,
            'fecha' => $fecha,
            'tipo_comision' => $tipo_comision,
            'tipo_destino' => $tipo_destino,
            'id_area' => $id_area,
            'estado' => $estado
        ]);
        $id_comision = $pdo->lastInsertId();
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return[
        'id_comision' => $id_comision,
        'id_solicitud' => $id_solicitud,
        'fecha' => $fecha,
        'tipo_comision' => $tipo_comision,
        'tipo_destino' => $tipo_destino,
        'id_area' => $id_area,
        'estado' => $estado
    ];
}

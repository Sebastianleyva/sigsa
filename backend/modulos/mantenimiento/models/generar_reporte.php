<?php
require_once '../../../includes/auth.php';

function generar_reporte($pdo,$input){
    if(!is_array($input)){
        throw new InvalidArgumentException('La entrada debe ser un JSON valido');
    }

    if(!isset($input['tipo'], $input['fecha_inicio'], $input['estado'], $input['descripcion'])
        || !is_string($input['tipo'])
        || !is_string($input['fecha_inicio'])
        || !is_string($input['estado'])
        || !is_string($input['descripcion'])){
        throw new InvalidArgumentException('Los campos tipo, fecha_inicio, estado y descripcion son obligatorios y deben ser cadenas de texto');
    }

    $tipo = $input['tipo'];
    $id_calendario = $input['id_calendario'] ?? null;
    $id_incidencia = $input['id_incidencia'] ?? null;
    $tiene_calendario = $id_calendario !== null && $id_calendario !== '';
    $tiene_incidencia = $id_incidencia !== null && $id_incidencia !== '';

    if ($tiene_calendario === $tiene_incidencia) {
        throw new InvalidArgumentException('El reporte debe estar asociado exactamente a un calendario o a una incidencia');
    }

    if ($tiene_calendario) {
        $id_calendario = filter_var($id_calendario, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id_calendario === false) {
            throw new InvalidArgumentException('El id_calendario debe ser un entero positivo');
        }

        $query = $pdo->prepare('SELECT id_calendario FROM calendario WHERE id_calendario = :id_calendario');
        $query->execute([':id_calendario' => $id_calendario]);
        if ($query->fetchColumn() === false) {
            throw new OutOfBoundsException('Calendario no encontrado');
        }
    } else {
        $id_incidencia = filter_var($id_incidencia, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id_incidencia === false) {
            throw new InvalidArgumentException('El id_incidencia debe ser un entero positivo');
        }

        $query = $pdo->prepare('SELECT estado FROM incidencias WHERE id_incidencia = :id_incidencia');
        $query->execute([':id_incidencia' => $id_incidencia]);
        $incidencia = $query->fetch(PDO::FETCH_ASSOC);
        if (!$incidencia) {
            throw new OutOfBoundsException('Incidencia no encontrada');
        }
        if (strtoupper($incidencia['estado']) !== 'ACEPTADA') {
            throw new DomainException('Solo se pueden generar reportes para incidencias aceptadas');
        }
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
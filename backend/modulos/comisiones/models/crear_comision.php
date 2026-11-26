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
    $fecha_inicio = null;
    $fecha_final = null;
    if ($tipo_comision === 'FORANEA') {
        foreach (['fecha_inicio', 'fecha_final'] as $campo) {
            $valor = $input[$campo] ?? null;
            if (
                !is_string($valor)
                || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)
                || !checkdate((int) substr($valor, 5, 2), (int) substr($valor, 8, 2), (int) substr($valor, 0, 4))
            ) {
                throw new InvalidArgumentException("El campo {$campo} debe ser una fecha válida");
            }
            if ($campo === 'fecha_inicio') {
                $fecha_inicio = $valor;
            } else {
                $fecha_final = $valor;
            }
        }
        if ($fecha_final < $fecha_inicio) {
            throw new InvalidArgumentException('La fecha final no puede ser anterior a la fecha de inicio');
        }
    }

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
             (id_solicitud, fecha, tipo_comision, tipo_destino, id_area, estado, fecha_inicio, fecha_final)
             VALUES
             (:id_solicitud, :fecha, :tipo_comision, :tipo_destino, :id_area, :estado, :fecha_inicio, :fecha_final)'
        );
        $query->execute([
            'id_solicitud' => $id_solicitud,
            'fecha' => $fecha,
            'tipo_comision' => $tipo_comision,
            'tipo_destino' => $tipo_destino,
            'id_area' => $id_area,
            'estado' => $estado,
            'fecha_inicio' => $fecha_inicio,
            'fecha_final' => $fecha_final,
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
        'estado' => $estado,
        'fecha_inicio' => $fecha_inicio,
        'fecha_final' => $fecha_final,
    ];
}

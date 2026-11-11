<?php

function aceptar_incidencia($pdo, $input, $id_personal){
    $id_incidencia = filter_var(
        $input['id_incidencia'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $id_personal = filter_var($id_personal, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($id_incidencia === false || $id_personal === false) {
        throw new InvalidArgumentException('Los identificadores de incidencia y autorizador deben ser enteros positivos');
    }
    if (!array_key_exists('requiere_requisicion', $input) || !is_bool($input['requiere_requisicion'])) {
        throw new InvalidArgumentException('El campo requiere_requisicion debe ser verdadero o falso');
    }

    $pdo->beginTransaction();
    try {
        $query = $pdo->prepare(
            "SELECT estado, descripcion
            FROM incidencias
            WHERE id_incidencia = :id_incidencia
            FOR UPDATE"
        );
        $query->execute([':id_incidencia' => $id_incidencia]);
        $incidencia = $query->fetch(PDO::FETCH_ASSOC);

        if (!$incidencia) {
            throw new OutOfBoundsException('Incidencia no encontrada');
        }

        if (strtoupper($incidencia['estado']) !== 'PENDIENTE') {
            throw new DomainException('Solo se pueden aceptar incidencias pendientes');
        }

        $query = $pdo->prepare(
            "UPDATE incidencias
            SET estado = :estado, id_autoriza = :id_personal
            WHERE id_incidencia = :id_incidencia AND estado = 'PENDIENTE'"
        );
        $query->execute([
            ':estado' => 'ACEPTADA',
            ':id_incidencia' => $id_incidencia,
            ':id_personal' => $id_personal
        ]);

        if ($query->rowCount() !== 1) {
            throw new DomainException('La incidencia ya fue procesada por otro usuario');
        }

        $estado_reporte = $input['requiere_requisicion']
            ? 'EN ESPERA DE REQUISICIÓN'
            : 'EN CURSO';
        $fecha_inicio = date('Y-m-d');
        $query = $pdo->prepare(
            "INSERT INTO reportes
            (tipo, id_calendario, id_incidencia, id_personal, fecha_inicio, estado, descripcion)
            VALUES
            ('incidencia', NULL, :id_incidencia, NULL, :fecha_inicio, :estado, :descripcion)"
        );
        $query->execute([
            ':id_incidencia' => $id_incidencia,
            ':fecha_inicio' => $fecha_inicio,
            ':estado' => $estado_reporte,
            ':descripcion' => $incidencia['descripcion']
        ]);
        $id_reporte = $pdo->lastInsertId();

        $query = $pdo->prepare(
            "SELECT *
            FROM incidencias
            WHERE id_incidencia = :id_incidencia"
        );
        $query->execute([':id_incidencia' => $id_incidencia]);
        $incidencia_actualizada = $query->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();
        return [
            'incidencia' => $incidencia_actualizada,
            'reporte' => [
                'id_reporte' => $id_reporte,
                'estado' => $estado_reporte
            ]
        ];
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}
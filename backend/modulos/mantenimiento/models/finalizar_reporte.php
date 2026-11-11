<?php

function finalizar_reporte($pdo, $input) {
    $id_reporte = filter_var(
        $input['id_reporte'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    if ($id_reporte === false) {
        throw new InvalidArgumentException('El id_reporte debe ser un entero positivo');
    }

    $fecha_final = $input['fecha_final'] ?? null;
    $descripcion = $input['descripcion'] ?? null;
    if (!is_string($fecha_final) || !is_string($descripcion) || trim($descripcion) === '') {
        throw new InvalidArgumentException('La fecha final y la descripción del servicio realizado son obligatorias');
    }

    $fecha_final = trim($fecha_final);
    $descripcion = trim($descripcion);
    $fecha_validada = DateTime::createFromFormat('!Y-m-d', $fecha_final);
    if (!$fecha_validada || $fecha_validada->format('Y-m-d') !== $fecha_final) {
        throw new InvalidArgumentException('La fecha final debe tener el formato AAAA-MM-DD');
    }

    $pdo->beginTransaction();
    try {
        $query = $pdo->prepare(
            "SELECT estado
            FROM reportes
            WHERE id_reporte = :id_reporte
            FOR UPDATE"
        );
        $query->execute([':id_reporte' => $id_reporte]);
        $reporte = $query->fetch(PDO::FETCH_ASSOC);

        if (!$reporte) {
            throw new OutOfBoundsException('Reporte no encontrado');
        }
        if (strtoupper($reporte['estado']) !== 'EN CURSO') {
            throw new DomainException('Solo se pueden concluir los reportes que están en curso');
        }

        $query = $pdo->prepare(
            "UPDATE reportes
            SET fecha_final = :fecha_final, descripcion = :descripcion, estado = 'FINALIZADO'
            WHERE id_reporte = :id_reporte AND UPPER(estado) = 'EN CURSO'"
        );
        $query->execute([
            ':fecha_final' => $fecha_final,
            ':descripcion' => $descripcion,
            ':id_reporte' => $id_reporte
        ]);
        if ($query->rowCount() !== 1) {
            throw new DomainException('El reporte ya fue concluido por otro usuario');
        }

        $query = $pdo->prepare(
            "SELECT id_reporte, fecha_final, estado, descripcion
            FROM reportes
            WHERE id_reporte = :id_reporte"
        );
        $query->execute([':id_reporte' => $id_reporte]);
        $reporte_actualizado = $query->fetch(PDO::FETCH_ASSOC);

        $pdo->commit();
        return $reporte_actualizado;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

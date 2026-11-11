<?php

function generar_requisicion($pdo, $input, $usuario) {
    $id_reporte = filter_var(
        $input['id_reporte'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    if ($id_reporte === false) {
        throw new InvalidArgumentException('El id_reporte debe ser un entero positivo');
    }

    foreach (['justificacion', 'fecha', 'descripcion_servicio', 'tipo_financiamiento'] as $campo) {
        if (!is_string($input[$campo] ?? null) || trim($input[$campo]) === '') {
            throw new InvalidArgumentException("El campo {$campo} es obligatorio");
        }
    }

    $justificacion = trim($input['justificacion']);
    $fecha = trim($input['fecha']);
    $descripcion_servicio = trim($input['descripcion_servicio']);
    $tipo_financiamiento = trim($input['tipo_financiamiento']);
    $fecha_validada = DateTime::createFromFormat('!Y-m-d', $fecha);
    if (!$fecha_validada || $fecha_validada->format('Y-m-d') !== $fecha) {
        throw new InvalidArgumentException('La fecha debe tener el formato AAAA-MM-DD');
    }

    $id_solicita = filter_var($usuario['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_solicita === false) {
        throw new InvalidArgumentException('El usuario solicitante no es válido');
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
        if (!in_array(strtoupper($reporte['estado']), ['ESPERA REQUISICIÓN', 'EN ESPERA DE REQUISICIÓN'], true)) {
            throw new DomainException('El reporte no está pendiente de requisición');
        }

        $query = $pdo->prepare(
            "SELECT id_requisicion
            FROM requisiciones
            WHERE id_reporte = :id_reporte
            LIMIT 1"
        );
        $query->execute([':id_reporte' => $id_reporte]);
        if ($query->fetchColumn() !== false) {
            throw new DomainException('Este reporte ya tiene una requisición registrada');
        }

        $query = $pdo->prepare(
            "INSERT INTO requisiciones
            (id_reporte, id_solicita, justificacion, fecha, descripcion_servicio, estado, tipo_financiamiento)
            VALUES
            (:id_reporte, :id_solicita, :justificacion, :fecha, :descripcion_servicio, 'EN ESPERA', :tipo_financiamiento)"
        );
        $query->execute([
            ':id_reporte' => $id_reporte,
            ':id_solicita' => $id_solicita,
            ':justificacion' => $justificacion,
            ':fecha' => $fecha,
            ':descripcion_servicio' => $descripcion_servicio,
            ':tipo_financiamiento' => $tipo_financiamiento
        ]);
        $id_requisicion = $pdo->lastInsertId();

        $pdo->commit();
        return [
            'id_requisicion' => $id_requisicion,
            'id_reporte' => $id_reporte,
            'justificacion' => $justificacion,
            'fecha' => $fecha,
            'descripcion_servicio' => $descripcion_servicio,
            'estado' => 'EN ESPERA',
            'tipo_financiamiento' => $tipo_financiamiento
        ];
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

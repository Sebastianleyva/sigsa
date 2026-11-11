<?php

function aceptar_requisicion($pdo, $input, $id_autoriza) {
    $id_requisicion = filter_var(
        $input['id_requisicion'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    if ($id_requisicion === false) {
        throw new InvalidArgumentException('El id_requisicion debe ser un entero positivo');
    }

    $id_autoriza = filter_var($id_autoriza, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_autoriza === false) {
        throw new InvalidArgumentException('El usuario autorizador no es válido');
    }

    $pdo->beginTransaction();
    try {
        $query = $pdo->prepare(
            "SELECT id_reporte, estado
            FROM requisiciones
            WHERE id_requisicion = :id_requisicion
            FOR UPDATE"
        );
        $query->execute([':id_requisicion' => $id_requisicion]);
        $requisicion = $query->fetch(PDO::FETCH_ASSOC);

        if (!$requisicion) {
            throw new OutOfBoundsException('Requisición no encontrada');
        }
        if (!in_array(strtoupper($requisicion['estado']), ['EN ESPERA', 'PENDIENTE'], true)) {
            throw new DomainException('La requisición no está pendiente de autorización');
        }

        $query = $pdo->prepare(
            "SELECT estado
            FROM reportes
            WHERE id_reporte = :id_reporte
            FOR UPDATE"
        );
        $query->execute([':id_reporte' => $requisicion['id_reporte']]);
        $reporte = $query->fetch(PDO::FETCH_ASSOC);
        if (!$reporte) {
            throw new OutOfBoundsException('El reporte asociado no existe');
        }
        if (!in_array(strtoupper($reporte['estado']), ['ESPERA REQUISICIÓN', 'EN ESPERA DE REQUISICIÓN'], true)) {
            throw new DomainException('El reporte asociado ya no está en espera de requisición');
        }

        $query = $pdo->prepare(
            "UPDATE requisiciones
            SET estado = 'APROBADA', id_autoriza = :id_autoriza
            WHERE id_requisicion = :id_requisicion"
        );
        $query->execute([
            ':id_autoriza' => $id_autoriza,
            ':id_requisicion' => $id_requisicion,
        ]);

        $query = $pdo->prepare(
            "UPDATE reportes
            SET estado = 'EN CURSO'
            WHERE id_reporte = :id_reporte"
        );
        $query->execute([':id_reporte' => $requisicion['id_reporte']]);

        $pdo->commit();
        return [
            'id_requisicion' => $id_requisicion,
            'id_reporte' => (int) $requisicion['id_reporte'],
            'estado' => 'APROBADA',
        ];
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $error;
    }
}

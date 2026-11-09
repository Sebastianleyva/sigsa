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

    $query = $pdo->prepare(
        "SELECT estado
        FROM incidencias
        WHERE id_incidencia = :id_incidencia"
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

    $query = $pdo->prepare(
        "SELECT *
        FROM incidencias
        WHERE id_incidencia = :id_incidencia"
    );
    $query->execute([':id_incidencia' => $id_incidencia]);

    return $query->fetch(PDO::FETCH_ASSOC);
}
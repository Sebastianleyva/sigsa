<?php

function actualizar_fecha_calendario($pdo, $input){
    $id_calendario = filter_var(
        $input['id_calendario'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    if ($id_calendario === false) {
        throw new InvalidArgumentException('El id_calendario debe ser un entero positivo');
    }

    $fecha_input = $input['fecha_destinada'] ?? null;
    if (!is_string($fecha_input)) {
        throw new InvalidArgumentException('La fecha destinada es obligatoria y debe tener formato YYYY-MM-DD');
    }

    $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha_input);
    $errores_fecha = DateTimeImmutable::getLastErrors();
    if ($fecha === false
        || ($errores_fecha !== false && ($errores_fecha['warning_count'] > 0 || $errores_fecha['error_count'] > 0))
        || $fecha->format('Y-m-d') !== $fecha_input) {
        throw new InvalidArgumentException('La fecha destinada debe ser válida y tener formato YYYY-MM-DD');
    }

    $hoy = new DateTimeImmutable('today');
    $fin_horizonte = new DateTimeImmutable(((int) date('Y') + 1) . '-12-31');
    if ($fecha < $hoy || $fecha > $fin_horizonte) {
        throw new InvalidArgumentException('La fecha debe estar entre hoy y el final del próximo año');
    }

    $query = $pdo->prepare(
        "SELECT id_servicio, fecha_destinada, estado
        FROM calendario
        WHERE id_calendario = :id_calendario"
    );
    $query->execute([':id_calendario' => $id_calendario]);
    $evento = $query->fetch(PDO::FETCH_ASSOC);
    if (!$evento) {
        throw new OutOfBoundsException('La fecha de mantenimiento no existe');
    }
    if (strtoupper($evento['estado']) !== 'PENDIENTE') {
        throw new DomainException('Solo se pueden reprogramar mantenimientos pendientes');
    }
    if ($evento['fecha_destinada'] === $fecha_input) {
        return [
            'id_calendario' => $id_calendario,
            'fecha_destinada' => $fecha_input,
            'estado' => $evento['estado']
        ];
    }

    $query = $pdo->prepare(
        "SELECT id_calendario
        FROM calendario
        WHERE id_servicio = :id_servicio
            AND fecha_destinada = :fecha_destinada
            AND id_calendario <> :id_calendario"
    );
    $query->execute([
        ':id_servicio' => $evento['id_servicio'],
        ':fecha_destinada' => $fecha_input,
        ':id_calendario' => $id_calendario
    ]);
    if ($query->fetchColumn() !== false) {
        throw new DomainException('El servicio ya tiene otro mantenimiento programado para esa fecha');
    }

    $query = $pdo->prepare(
        "UPDATE calendario
        SET fecha_destinada = :fecha_destinada
        WHERE id_calendario = :id_calendario AND UPPER(estado) = 'PENDIENTE'"
    );
    $query->execute([
        ':fecha_destinada' => $fecha_input,
        ':id_calendario' => $id_calendario
    ]);
    if ($query->rowCount() !== 1) {
        throw new DomainException('El mantenimiento ya fue actualizado o procesado');
    }

    return [
        'id_calendario' => $id_calendario,
        'fecha_destinada' => $fecha_input,
        'estado' => $evento['estado']
    ];
}

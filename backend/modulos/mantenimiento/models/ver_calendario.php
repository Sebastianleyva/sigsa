<?php

function ver_calendario_mes($pdo, $anio_input, $mes_input){
    if ((!is_int($anio_input) && !is_string($anio_input))
        || (!is_int($mes_input) && !is_string($mes_input))) {
        throw new InvalidArgumentException('El año y el mes deben ser valores enteros');
    }

    $anio = filter_var($anio_input, FILTER_VALIDATE_INT);
    $mes = filter_var($mes_input, FILTER_VALIDATE_INT);

    if ($anio === false || $anio < 1000 || $anio > 9999) {
        throw new InvalidArgumentException('El año debe ser un entero entre 1000 y 9999');
    }

    if ($mes === false || $mes < 1 || $mes > 12) {
        throw new InvalidArgumentException('El mes debe ser un entero entre 1 y 12');
    }

    $fecha_inicio = sprintf('%04d-%02d-01', $anio, $mes);
    $fecha_siguiente_mes = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha_inicio);
    if ($fecha_siguiente_mes === false) {
        throw new InvalidArgumentException('El año y mes proporcionados no son válidos');
    }
    $fecha_fin = $fecha_siguiente_mes->modify('+1 month')->format('Y-m-d');

    $query = $pdo->prepare(
        "SELECT
            c.id_calendario,
            c.fecha_destinada,
            c.estado,
            c.id_servicio,
            s.nombre AS servicio,
            s.descripcion AS descripcion_servicio,
            s.periodicidad
        FROM calendario c
        INNER JOIN servicios s ON s.id_servicio = c.id_servicio
        WHERE c.fecha_destinada >= :fecha_inicio
            AND c.fecha_destinada < :fecha_fin
        ORDER BY c.fecha_destinada ASC, c.id_calendario ASC"
    );
    $query->execute([
        ':fecha_inicio' => $fecha_inicio,
        ':fecha_fin' => $fecha_fin
    ]);

    return $query->fetchAll(PDO::FETCH_ASSOC);
}

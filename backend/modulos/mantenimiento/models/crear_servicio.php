<?php

function crear_servicio($pdo, $input){
    if (!is_array($input)
        || !isset($input['nombre'], $input['periodicidad'], $input['descripcion'])
        || !is_string($input['nombre'])
        || !is_int($input['periodicidad'])
        || $input['periodicidad'] < 1
        || !is_string($input['descripcion'])) {
        throw new InvalidArgumentException('Nombre, periodicidad y descripcion son obligatorios; la periodicidad debe ser un entero positivo');
    }

    $nombre = trim($input['nombre']);
    $periodicidad = $input['periodicidad'];
    $descripcion = trim($input['descripcion']);
    if ($nombre === '' || $descripcion === '') {
        throw new InvalidArgumentException('El nombre y la descripcion no pueden estar vacios');
    }

    $fecha_inicio_input = $input['fecha_inicio'] ?? '';
    if (!is_string($fecha_inicio_input)) {
        throw new InvalidArgumentException('La fecha inicial debe tener formato YYYY-MM-DD');
    }
    $fecha_inicio_input = trim($fecha_inicio_input);
    if ($fecha_inicio_input === '') {
        $fecha_inicio_input = date('Y-m-d');
    }

    $fecha_inicio = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha_inicio_input);
    $errores_fecha = DateTimeImmutable::getLastErrors();
    if ($fecha_inicio === false
        || ($errores_fecha !== false && ($errores_fecha['warning_count'] > 0 || $errores_fecha['error_count'] > 0))
        || $fecha_inicio->format('Y-m-d') !== $fecha_inicio_input) {
        throw new InvalidArgumentException('La fecha inicial debe ser una fecha válida con formato YYYY-MM-DD');
    }

    $hoy = new DateTimeImmutable('today');
    $fin_horizonte = new DateTimeImmutable(((int) date('Y') + 1) . '-12-31');
    if ($fecha_inicio < $hoy || $fecha_inicio > $fin_horizonte) {
        throw new InvalidArgumentException('La fecha inicial debe estar entre hoy y el final del próximo año');
    }

    $anio_actual = (int) date('Y');
    $fechas = [];
    $fecha = $fecha_inicio;

    while ((int) $fecha->format('Y') <= $anio_actual) {
        $fechas[] = $fecha;
        if ($periodicidad > 104) {
            break;
        }
        $fecha = $fecha->modify('+' . $periodicidad . ' weeks');
    }

    if ((int) $fecha->format('Y') === $anio_actual + 1) {
        $fechas[] = $fecha;
    }

    $pdo->beginTransaction();
    try {
        $stm = $pdo->prepare(
            "INSERT INTO servicios
            (nombre, periodicidad, descripcion, activo)
            VALUES
            (:nombre, :periodicidad, :descripcion, 1)"
        );
        $stm->execute([
            ':nombre' => $nombre,
            ':periodicidad' => $periodicidad,
            ':descripcion' => $descripcion
        ]);
        $id_servicio = (int) $pdo->lastInsertId();

        $stm = $pdo->prepare(
            "INSERT INTO calendario
            (fecha_destinada, estado, id_servicio)
            VALUES
            (:fecha_destinada, 'PENDIENTE', :id_servicio)"
        );
        $fechas_generadas = [];
        foreach ($fechas as $fecha_destinada) {
            $fecha_formateada = $fecha_destinada->format('Y-m-d');
            $stm->execute([
                ':fecha_destinada' => $fecha_formateada,
                ':id_servicio' => $id_servicio
            ]);
            $fechas_generadas[] = [
                'id_calendario' => (int) $pdo->lastInsertId(),
                'fecha_destinada' => $fecha_formateada,
                'estado' => 'PENDIENTE'
            ];
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return [
        'id_servicio' => $id_servicio,
        'nombre' => $nombre,
        'periodicidad' => $periodicidad,
        'descripcion' => $descripcion,
        'activo' => 1,
        'fechas_generadas' => $fechas_generadas
    ];
}

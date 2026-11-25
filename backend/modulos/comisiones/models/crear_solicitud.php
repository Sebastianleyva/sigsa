<?php

function crear_solicitud($pdo,$input, $id_personal){

    $fecha_registro = date('Y-m-d');
    $institucion_solicita = $input['institucion_solicita'];
    $ubicacion = $input['ubicacion'];
    $nombre_solicita = $input['nombre_solicita'];
    $falla_reportada = trim((string)($input['falla_reportada'] ?? ''));
    $num_contacto_raw = $input['num_contacto'] ?? null;
    if (!is_string($num_contacto_raw)) {
        throw new InvalidArgumentException('El número de contacto debe ser texto');
    }
    $num_contacto = trim($num_contacto_raw);
    $fecha_comision = $input['fecha_comision'] ?? null;
    $id_personal = $id_personal;

    if ($num_contacto === '' || strlen($num_contacto) > 12) {
        throw new InvalidArgumentException('El número de contacto es obligatorio y no puede exceder 12 caracteres');
    }

    if ($fecha_comision === null || $fecha_comision === '') {
        $fecha_comision = null;
    } elseif (
        !is_string($fecha_comision)
        || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha_comision)
        || !checkdate((int) substr($fecha_comision, 5, 2), (int) substr($fecha_comision, 8, 2), (int) substr($fecha_comision, 0, 4))
    ) {
        throw new InvalidArgumentException('La fecha de comisión no es válida');
    }

    if ($falla_reportada === '') {
        throw new InvalidArgumentException('La falla reportada no puede estar vacía');
    }

    if (strlen($falla_reportada) > 2000) {
        throw new InvalidArgumentException('La falla reportada no puede exceder 2000 caracteres');
    }

    $query = $pdo -> prepare(
        'INSERT INTO solicitudes
        (fecha_registro, institucion_solicita, ubicacion, nombre_solicita, falla_reportada, id_personal, comisionada, num_contacto, fecha_comision)
        VALUES 
        (:fecha_registro, :institucion_solicita, :ubicacion, :nombre_solicita, :falla_reportada, :id_personal, 0, :num_contacto, :fecha_comision)'
    );
    $query -> execute([
        'fecha_registro' => $fecha_registro,
        'institucion_solicita' => $institucion_solicita,
        'ubicacion' => $ubicacion,
        'nombre_solicita' => $nombre_solicita,
        'falla_reportada' => $falla_reportada,
        'id_personal' => $id_personal,
        'num_contacto' => $num_contacto,
        'fecha_comision' => $fecha_comision
    ]);

    return[
        'id_solicitud' => $pdo->lastInsertId(),
        'fecha_registro' => $fecha_registro,
        'institucion_solicita' => $institucion_solicita,
        'ubicacion' => $ubicacion,
        'nombre_solicita' => $nombre_solicita,
        'falla_reportada' => $falla_reportada,
        'id_personal' => $id_personal,
        'num_contacto' => $num_contacto,
        'fecha_comision' => $fecha_comision
    ];
}
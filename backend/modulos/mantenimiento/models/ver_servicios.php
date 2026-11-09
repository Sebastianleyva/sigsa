<?php

function ver_servicios($pdo){
    $query = $pdo->query(
        "SELECT
            s.id_servicio,
            s.nombre,
            s.periodicidad,
            s.descripcion,
            s.activo,
            c.id_calendario,
            c.fecha_destinada,
            c.estado AS estado_calendario
        FROM servicios s
        LEFT JOIN calendario c
            ON c.id_servicio = s.id_servicio
            AND c.fecha_destinada >= CURRENT_DATE
            AND UPPER(c.estado) = 'PENDIENTE'
        WHERE s.activo = 1
        ORDER BY s.nombre ASC, s.id_servicio ASC, c.fecha_destinada ASC"
    );

    $servicios = [];
    foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $id_servicio = (int) $fila['id_servicio'];
        if (!isset($servicios[$id_servicio])) {
            $servicios[$id_servicio] = [
                'id_servicio' => $id_servicio,
                'nombre' => $fila['nombre'],
                'periodicidad' => (int) $fila['periodicidad'],
                'descripcion' => $fila['descripcion'],
                'activo' => (int) $fila['activo'],
                'fechas_destinadas' => []
            ];
        }

        if ($fila['id_calendario'] !== null) {
            $servicios[$id_servicio]['fechas_destinadas'][] = [
                'id_calendario' => (int) $fila['id_calendario'],
                'fecha_destinada' => $fila['fecha_destinada'],
                'estado' => $fila['estado_calendario']
            ];
        }
    }

    return array_values($servicios);
}

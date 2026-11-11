<?php

function ver_incidencias($pdo) {
    $query = $pdo->query(
        "SELECT
            i.id_incidencia,
            i.id_personal,
            i.id_autoriza,
            i.descripcion,
            i.fecha,
            i.urgencia,
            i.estado,
            i.lugar,
            p.nombre,
            p.apellido_p,
            p.apellido_m
        FROM incidencias i
        LEFT JOIN personal p ON p.id_personal = i.id_personal
        WHERE UPPER(i.estado) <> 'ACEPTADA'
        ORDER BY i.fecha DESC, i.id_incidencia DESC"
    );
    $incidencias = $query->fetchAll(PDO::FETCH_ASSOC);

    foreach ($incidencias as &$incidencia) {
        $nombre = array_filter([
            $incidencia['nombre'],
            $incidencia['apellido_p'],
            $incidencia['apellido_m'],
        ], function ($parte) {
            return is_string($parte) && trim($parte) !== '';
        });
        $incidencia['reportante'] = implode(' ', $nombre);
        unset($incidencia['nombre'], $incidencia['apellido_p'], $incidencia['apellido_m']);
    }
    unset($incidencia);

    return $incidencias;
}
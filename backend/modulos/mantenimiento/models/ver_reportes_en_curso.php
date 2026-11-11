<?php

function ver_reportes_en_curso($pdo, $id_reporte = null) {
    $sql = "SELECT
            r.id_reporte,
            r.tipo,
            r.id_incidencia,
            r.id_calendario,
            r.fecha_inicio,
            r.fecha_final,
            r.estado,
            r.descripcion,
            i.lugar,
            i.descripcion AS descripcion_incidencia,
            i.urgencia,
            c.fecha_destinada,
            s.nombre AS servicio,
            NULLIF(TRIM(CONCAT_WS(' ', p.nombre, p.apellido_p, p.apellido_m)), '') AS reportante
        FROM reportes r
        LEFT JOIN incidencias i ON i.id_incidencia = r.id_incidencia
        LEFT JOIN personal p ON p.id_personal = i.id_personal
        LEFT JOIN calendario c ON c.id_calendario = r.id_calendario
        LEFT JOIN servicios s ON s.id_servicio = c.id_servicio
        WHERE UPPER(r.estado) IN ('EN CURSO', 'ESPERA REQUISICIÓN', 'EN ESPERA DE REQUISICIÓN')";
    $parametros = [];

    if ($id_reporte !== null) {
        $sql .= ' AND r.id_reporte = :id_reporte';
        $parametros[':id_reporte'] = $id_reporte;
    }

    $sql .= ' ORDER BY r.fecha_inicio DESC, r.id_reporte DESC';
    $query = $pdo->prepare($sql);
    $query->execute($parametros);

    return $query->fetchAll(PDO::FETCH_ASSOC);
}

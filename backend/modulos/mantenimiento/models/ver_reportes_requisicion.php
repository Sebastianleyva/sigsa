<?php

function ver_reportes_requisicion($pdo) {
    $query = $pdo->query(
        "SELECT
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
            NULLIF(TRIM(CONCAT_WS(' ', p.nombre, p.apellido_p, p.apellido_m)), '') AS reportante,
            q.id_requisicion,
            q.estado AS estado_requisicion
        FROM reportes r
        LEFT JOIN incidencias i ON i.id_incidencia = r.id_incidencia
        LEFT JOIN personal p ON p.id_personal = i.id_personal
        LEFT JOIN calendario c ON c.id_calendario = r.id_calendario
        LEFT JOIN servicios s ON s.id_servicio = c.id_servicio
        LEFT JOIN requisiciones q ON q.id_requisicion = (
            SELECT MAX(q2.id_requisicion)
            FROM requisiciones q2
            WHERE q2.id_reporte = r.id_reporte
        )
        WHERE UPPER(r.estado) IN ('ESPERA REQUISICIÓN', 'EN ESPERA DE REQUISICIÓN')
        ORDER BY r.fecha_inicio DESC, r.id_reporte DESC"
    );

    return $query->fetchAll(PDO::FETCH_ASSOC);
}

<?php

function ver_historial_reportes($pdo) {
    $query = $pdo->query(
        "SELECT
            r.id_reporte,
            r.tipo,
            r.id_calendario,
            r.id_incidencia,
            r.id_personal,
            r.fecha_inicio,
            r.fecha_final,
            r.estado,
            r.descripcion,
            c.fecha_destinada,
            c.estado AS estado_calendario,
            s.nombre AS servicio,
            s.descripcion AS descripcion_servicio_programado,
            i.fecha AS fecha_incidencia,
            i.lugar,
            i.urgencia,
            i.estado AS estado_incidencia,
            i.id_personal AS id_reportante,
            i.id_autoriza AS id_autoriza_incidencia,
            i.descripcion AS descripcion_incidencia,
            NULLIF(TRIM(CONCAT_WS(' ', pi.nombre, pi.apellido_p, pi.apellido_m)), '') AS reportante,
            NULLIF(TRIM(CONCAT_WS(' ', pa.nombre, pa.apellido_p, pa.apellido_m)), '') AS autorizador_incidencia,
            NULLIF(TRIM(CONCAT_WS(' ', pr.nombre, pr.apellido_p, pr.apellido_m)), '') AS responsable_reporte
        FROM reportes r
        LEFT JOIN incidencias i ON i.id_incidencia = r.id_incidencia
        LEFT JOIN personal pi ON pi.id_personal = i.id_personal
        LEFT JOIN personal pa ON pa.id_personal = i.id_autoriza
        LEFT JOIN personal pr ON pr.id_personal = r.id_personal
        LEFT JOIN calendario c ON c.id_calendario = r.id_calendario
        LEFT JOIN servicios s ON s.id_servicio = c.id_servicio
        ORDER BY r.fecha_inicio DESC, r.id_reporte DESC"
    );
    $reportes = $query->fetchAll(PDO::FETCH_ASSOC);

    foreach ($reportes as &$reporte) {
        $reporte['requisiciones'] = [];
    }
    unset($reporte);

    if (count($reportes) === 0) {
        return $reportes;
    }

    $query = $pdo->query(
        "SELECT
            q.id_requisicion,
            q.id_reporte,
            q.id_solicita,
            q.id_autoriza,
            q.justificacion,
            q.fecha,
            q.fecha_entrega,
            q.descripcion_servicio,
            q.estado,
            q.tipo_financiamiento,
            NULLIF(TRIM(CONCAT_WS(' ', ps.nombre, ps.apellido_p, ps.apellido_m)), '') AS solicitante,
            NULLIF(TRIM(CONCAT_WS(' ', pa.nombre, pa.apellido_p, pa.apellido_m)), '') AS autorizador
        FROM requisiciones q
        LEFT JOIN personal ps ON ps.id_personal = q.id_solicita
        LEFT JOIN personal pa ON pa.id_personal = q.id_autoriza
        ORDER BY q.id_reporte, q.fecha DESC, q.id_requisicion DESC"
    );
    $requisiciones = $query->fetchAll(PDO::FETCH_ASSOC);
    $reportePorId = [];
    foreach ($reportes as $indice => $reporte) {
        $reportePorId[(string)$reporte['id_reporte']] = $indice;
    }

    foreach ($requisiciones as $requisicion) {
        $id_reporte = (string)$requisicion['id_reporte'];
        if (isset($reportePorId[$id_reporte])) {
            $reportes[$reportePorId[$id_reporte]]['requisiciones'][] = $requisicion;
        }
    }

    return $reportes;
}

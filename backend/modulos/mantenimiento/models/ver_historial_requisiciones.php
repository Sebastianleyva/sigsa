<?php

function ver_historial_requisiciones($pdo, int $mes, int $anio): array {
    $query = $pdo->prepare(
        "SELECT
            q.id_requisicion,
            q.tipo_requisicion,
            q.id_reporte,
            q.fecha,
            q.fecha_entrega,
            q.estado,
            q.tipo_financiamiento,
            q.justificacion,
            q.descripcion_servicio,
            q.tipo_codigo,
            COALESCE(cc.codigo_contable, q.cc_central) AS codigo_contable,
            r.tipo AS tipo_reporte,
            r.estado AS estado_reporte,
            i.lugar,
            s.nombre AS servicio,
            NULLIF(TRIM(CONCAT_WS(' ', ps.nombre, ps.apellido_p, ps.apellido_m)), '') AS solicitante,
            NULLIF(TRIM(CONCAT_WS(' ', pa.nombre, pa.apellido_p, pa.apellido_m)), '') AS autorizador,
            NULLIF(TRIM(CONCAT_WS(' ', pe.nombre, pe.apellido_p, pe.apellido_m)), '') AS elaborador
        FROM requisiciones q
        INNER JOIN reportes r ON r.id_reporte = q.id_reporte
        LEFT JOIN incidencias i ON i.id_incidencia = r.id_incidencia
        LEFT JOIN calendario c ON c.id_calendario = r.id_calendario
        LEFT JOIN servicios s ON s.id_servicio = c.id_servicio
        LEFT JOIN personal ps ON ps.id_personal = q.id_solicita
        LEFT JOIN personal pa ON pa.id_personal = q.id_autoriza
        LEFT JOIN personal pe ON pe.id_personal = q.id_elabora
        LEFT JOIN codigos_contables cc ON cc.id_codigo = q.id_codigo
        WHERE MONTH(q.fecha) = :mes
          AND YEAR(q.fecha) = :anio
        ORDER BY q.fecha DESC, q.id_requisicion DESC"
    );
    $query->execute([':mes' => $mes, ':anio' => $anio]);
    $requisiciones = $query->fetchAll(PDO::FETCH_ASSOC);
    if (count($requisiciones) === 0) {
        return [];
    }

    $placeholders = [];
    $parametros = [];
    foreach ($requisiciones as $indice => $requisicion) {
        $placeholder = ':requisicion_' . $indice;
        $placeholders[] = $placeholder;
        $parametros[$placeholder] = $requisicion['id_requisicion'];
    }

    $query = $pdo->prepare(
        'SELECT id_requisicion, num_lote, cantidad, descripcion
         FROM requisiciones_detalle
         WHERE id_requisicion IN (' . implode(', ', $placeholders) . ')
         ORDER BY id_detalle'
    );
    $query->execute($parametros);
    $detalles_por_requisicion = [];
    foreach ($query->fetchAll(PDO::FETCH_ASSOC) as $detalle) {
        $detalles_por_requisicion[$detalle['id_requisicion']][] = [
            'num_lote' => $detalle['num_lote'],
            'cantidad' => $detalle['cantidad'] === null ? null : (int) $detalle['cantidad'],
            'descripcion' => $detalle['descripcion'],
        ];
    }

    foreach ($requisiciones as &$requisicion) {
        $requisicion['detalles'] = $detalles_por_requisicion[$requisicion['id_requisicion']] ?? [];
    }
    unset($requisicion);

    return $requisiciones;
}

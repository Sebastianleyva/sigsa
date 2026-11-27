<?php

function ver_informes_comision_mes($pdo, int $mes, int $anio): array {
    // informe_comision no guarda fecha de registro: se agrupa por la fecha de la comisión.
    $consulta = $pdo->prepare(
        "SELECT ic.id_informe_comision, ic.id_comision,
                CONCAT_WS(' ', p.nombre, p.apellido_p, p.apellido_m) AS comisionado,
                a.nombre AS area_nombre,
                c.fecha_inicio, c.fecha_final, c.estado AS estado_comision,
                s.fecha_comision, s.ubicacion AS lugar, s.institucion_solicita,
                COALESCE(c.fecha_final, c.fecha_inicio, s.fecha_comision, c.fecha) AS fecha_referencia
         FROM informe_comision ic
         INNER JOIN comisiones c ON c.id_comision = ic.id_comision
         INNER JOIN solicitudes s ON s.id_solicitud = c.id_solicitud
         INNER JOIN personal p ON p.id_personal = ic.id_personal
         LEFT JOIN areas a ON a.id_area = p.area
         WHERE MONTH(COALESCE(c.fecha_final, c.fecha_inicio, s.fecha_comision, c.fecha)) = :mes
           AND YEAR(COALESCE(c.fecha_final, c.fecha_inicio, s.fecha_comision, c.fecha)) = :anio
         ORDER BY fecha_referencia DESC, ic.id_informe_comision DESC"
    );
    $consulta->execute(['mes' => $mes, 'anio' => $anio]);

    return $consulta->fetchAll(PDO::FETCH_ASSOC);
}

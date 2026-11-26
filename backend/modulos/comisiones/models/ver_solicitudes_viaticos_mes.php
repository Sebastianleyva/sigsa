<?php

function ver_solicitudes_viaticos_mes($pdo, int $mes, int $anio): array {
    $consulta = $pdo->prepare(
        "SELECT sv.id_solicitud_viaticos, sv.fecha, sv.id_comision,
                CONCAT_WS(' ', p.nombre, p.apellido_p, p.apellido_m) AS comisionado,
                s.fecha_comision, s.ubicacion AS lugar, c.estado AS estado_comision,
                COALESCE(t.total_importe, 0) AS total_importe
         FROM solicitudes_de_viaticos sv
         INNER JOIN comisiones c ON c.id_comision = sv.id_comision
         INNER JOIN solicitudes s ON s.id_solicitud = c.id_solicitud
         INNER JOIN personal p ON p.id_personal = sv.id_personal_comisionado
         LEFT JOIN (
             SELECT id_solicitud_viaticos, SUM(importe) AS total_importe
             FROM viaticos_comisiones
             GROUP BY id_solicitud_viaticos
         ) t ON t.id_solicitud_viaticos = sv.id_solicitud_viaticos
         WHERE MONTH(sv.fecha) = :mes
           AND YEAR(sv.fecha) = :anio
         ORDER BY sv.fecha DESC, sv.id_solicitud_viaticos DESC"
    );
    $consulta->execute(['mes' => $mes, 'anio' => $anio]);

    return $consulta->fetchAll(PDO::FETCH_ASSOC);
}

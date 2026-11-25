<?php

function ver_personal_solicitudes_viaticos($pdo): array {
    $consulta = $pdo->prepare(
        "SELECT c.id_comision, c.tipo_comision, c.tipo_destino, c.estado,
                s.institucion_solicita, s.ubicacion AS lugar, s.fecha_comision,
                p.id_personal, p.nombre, p.apellido_p, p.apellido_m,
                pu.nombre AS puesto_nombre
         FROM comisiones c
         INNER JOIN solicitudes s ON s.id_solicitud = c.id_solicitud
         INNER JOIN personal_comisionado pc ON pc.id_comision = c.id_comision
         INNER JOIN personal p ON p.id_personal = pc.id_personal
         INNER JOIN puestos pu ON pu.id_puesto = p.puesto
         WHERE c.estado = :estado
         ORDER BY c.id_comision DESC, p.apellido_p, p.apellido_m, p.nombre"
    );
    $consulta->execute(['estado' => 'PENDIENTE DE SOLICITUDES_V']);

    return $consulta->fetchAll(PDO::FETCH_ASSOC);
}

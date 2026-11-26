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

function ver_comisionado_solicitud_viaticos($pdo, int $id_comision, int $id_personal): array|false {
    $consulta = $pdo->prepare(
        "SELECT c.id_comision, p.id_personal, p.puesto AS id_puesto,
                CONCAT_WS(' ', p.nombre, p.apellido_p, p.apellido_m) AS nombre_completo,
                p.curp, p.rfc, p.correo, p.telefono,
                COALESCE(NULLIF(TRIM(p.cisco), ''), 'N/A') AS cisco,
                s.fecha_comision, s.ubicacion AS lugar,
                s.falla_reportada AS motivo
         FROM comisiones c
         INNER JOIN personal_comisionado pc ON pc.id_comision = c.id_comision
         INNER JOIN personal p ON p.id_personal = pc.id_personal
         INNER JOIN solicitudes s ON s.id_solicitud = c.id_solicitud
         WHERE c.id_comision = :id_comision
           AND p.id_personal = :id_personal
           AND c.estado = :estado"
    );
    $consulta->execute([
        'id_comision' => $id_comision,
        'id_personal' => $id_personal,
        'estado' => 'PENDIENTE DE SOLICITUDES_V',
    ]);

    return $consulta->fetch(PDO::FETCH_ASSOC);
}

function obtener_opciones_solicitud_viaticos($pdo, int $id_puesto): array {
    $consulta_codigos = $pdo->query(
        'SELECT id_codigo_contable_comision, codigo_contable, descripcion
         FROM codigos_contables_comisiones
         WHERE activo = 1
         ORDER BY codigo_contable'
    );
    $consulta_zonas = $pdo->query(
        'SELECT id_zona, nombre, permite_constancia
         FROM zonas_viaticos
         ORDER BY nombre'
    );
    $consulta_tabulador = $pdo->prepare(
        'SELECT id_zona, tipo_comprobacion, pernocta, monto_diario
         FROM tabulador_viaticos
         WHERE id_puesto = :id_puesto
         ORDER BY id_zona, tipo_comprobacion, pernocta'
    );
    $consulta_tabulador->execute(['id_puesto' => $id_puesto]);

    return [
        'codigos_contables' => $consulta_codigos->fetchAll(PDO::FETCH_ASSOC),
        'zonas' => $consulta_zonas->fetchAll(PDO::FETCH_ASSOC),
        'tabulador' => $consulta_tabulador->fetchAll(PDO::FETCH_ASSOC),
    ];
}

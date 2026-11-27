<?php

function ver_comisiones_participante($pdo, int $id_personal): array {
    $consulta_comisiones = $pdo->prepare(
        "SELECT c.id_comision, c.id_solicitud, c.fecha, c.fecha_inicio, c.fecha_final,
                c.tipo_comision, c.tipo_destino, c.id_area, c.estado,
                a.nombre AS area_nombre, s.ubicacion AS lugar,
                s.institucion_solicita, s.nombre_solicita, s.falla_reportada,
                s.num_contacto, s.fecha_comision
         FROM comisiones c
         INNER JOIN areas a ON a.id_area = c.id_area
         INNER JOIN solicitudes s ON s.id_solicitud = c.id_solicitud
         WHERE c.estado <> 'FINALIZADA'
           AND EXISTS (
             SELECT 1 FROM personal_comisionado pc
             WHERE pc.id_comision = c.id_comision AND pc.id_personal = :id_personal
         )
         ORDER BY c.fecha_inicio IS NULL, c.fecha_inicio DESC, c.id_comision DESC"
    );
    $consulta_comisiones->execute(['id_personal' => $id_personal]);
    $comisiones = $consulta_comisiones->fetchAll(PDO::FETCH_ASSOC);
    if (count($comisiones) === 0) {
        return [];
    }

    $placeholders = [];
    $parametros_personal = [];
    foreach ($comisiones as $indice => $comision) {
        $placeholder = 'comision_' . $indice;
        $placeholders[] = ':' . $placeholder;
        $parametros_personal[$placeholder] = $comision['id_comision'];
    }

    $consulta_personal = $pdo->prepare(
        'SELECT pc.id_comision, p.id_personal, p.nombre, p.apellido_p, p.apellido_m, p.puesto
         FROM personal_comisionado pc
         INNER JOIN personal p ON p.id_personal = pc.id_personal
         WHERE pc.id_comision IN (' . implode(', ', $placeholders) . ')
         ORDER BY p.apellido_p, p.apellido_m, p.nombre'
    );
    $consulta_personal->execute($parametros_personal);
    $personal_por_comision = [];
    foreach ($consulta_personal->fetchAll(PDO::FETCH_ASSOC) as $persona) {
        $personal_por_comision[$persona['id_comision']][] = [
            'id_personal' => $persona['id_personal'],
            'nombre' => $persona['nombre'],
            'apellido_p' => $persona['apellido_p'],
            'apellido_m' => $persona['apellido_m'],
            'puesto' => $persona['puesto'],
        ];
    }

    foreach ($comisiones as &$comision) {
        $comision['comisionados'] = $personal_por_comision[$comision['id_comision']] ?? [];
    }
    unset($comision);

    return $comisiones;
}

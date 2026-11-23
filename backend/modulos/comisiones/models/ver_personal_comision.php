<?php

function ver_personal_comision($pdo, int $id_comision, int $puesto, ?int $area): array {
    if (!in_array($puesto, [2, 3], true) || $area === null) {
        throw new InvalidArgumentException('No tienes permisos para consultar esta comisión');
    }

    $parametros = ['id_comision' => $id_comision, 'estado' => 'PENDIENTE'];
    if ($puesto === 2) {
        $condicion_area = 'a.id_subdireccion = :area';
        $parametros['area'] = $area;
    } else {
        $condicion_area = 'c.id_area = :area';
        $parametros['area'] = $area;
    }

    $consulta_comision = $pdo->prepare(
        "SELECT c.id_comision, c.id_solicitud, c.fecha, c.tipo_comision,
                c.tipo_destino, c.id_area, c.estado, s.ubicacion AS lugar,
                s.institucion_solicita, s.nombre_solicita, a.nombre AS area_nombre
         FROM comisiones c
         INNER JOIN areas a ON a.id_area = c.id_area
         INNER JOIN solicitudes s ON s.id_solicitud = c.id_solicitud
         WHERE c.id_comision = :id_comision
           AND c.estado = :estado
           AND {$condicion_area}"
    );
    $consulta_comision->execute($parametros);
    $comision = $consulta_comision->fetch(PDO::FETCH_ASSOC);
    if (!$comision) {
        throw new InvalidArgumentException('La comisión no está pendiente o no pertenece a tu área');
    }

    $consulta_personal = $pdo->prepare(
        "SELECT id_personal, nombre, apellido_p, apellido_m, puesto
         FROM personal
         WHERE area = :area AND activo = '1' AND puesto IN (3, 4)
         ORDER BY puesto, apellido_p, apellido_m, nombre"
    );
    $consulta_personal->execute(['area' => (int) $comision['id_area']]);

    return [
        'comision' => $comision,
        'personal' => $consulta_personal->fetchAll(PDO::FETCH_ASSOC),
    ];
}

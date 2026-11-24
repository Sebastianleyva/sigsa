<?php

function ver_comisiones_en_curso($pdo, int $puesto, ?int $area): array {
    $parametros = ['estado' => 'EN CURSO'];
    if ($puesto === 2 && $area !== null) {
        $condicion_area = 'a.id_subdireccion = :area';
        $parametros['area'] = $area;
    } elseif ($puesto === 3 && $area !== null) {
        $condicion_area = 'c.id_area = :area';
        $parametros['area'] = $area;
    } else {
        throw new InvalidArgumentException('Solo subdirectores y jefes de departamento pueden consultar comisiones en curso');
    }

    $consulta_comisiones = $pdo->prepare(
        "SELECT c.id_comision, c.id_solicitud, c.fecha, c.fecha_inicio,
                c.tipo_comision, c.tipo_destino, c.id_area, c.estado,
                a.nombre AS area_nombre, s.ubicacion AS lugar,
                s.institucion_solicita, s.nombre_solicita, s.falla_reportada
         FROM comisiones c
         INNER JOIN areas a ON a.id_area = c.id_area
         INNER JOIN solicitudes s ON s.id_solicitud = c.id_solicitud
         WHERE c.estado = :estado AND {$condicion_area}
         ORDER BY c.fecha_inicio DESC, c.id_comision DESC"
    );
    $consulta_comisiones->execute($parametros);
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

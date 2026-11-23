<?php

function ver_comisiones($pdo, int $puesto, ?int $area) {
    $parametros = ['estado' => 'PENDIENTE'];

    if ($puesto === 2 && $area !== null) {
        $sql = 'SELECT c.id_comision, c.id_solicitud, c.fecha, c.tipo_comision,
                       c.tipo_destino, c.id_area, c.estado, s.ubicacion AS lugar,
                       s.institucion_solicita, s.nombre_solicita
                FROM comisiones c
                INNER JOIN areas a ON a.id_area = c.id_area
                INNER JOIN solicitudes s ON s.id_solicitud = c.id_solicitud
                WHERE c.estado = :estado AND a.id_subdireccion = :area';
        $parametros['area'] = $area;
    } elseif ($puesto === 3 && $area !== null) {
        $sql = 'SELECT c.id_comision, c.id_solicitud, c.fecha, c.tipo_comision,
                       c.tipo_destino, c.id_area, c.estado, s.ubicacion AS lugar,
                       s.institucion_solicita, s.nombre_solicita
                FROM comisiones c
                INNER JOIN solicitudes s ON s.id_solicitud = c.id_solicitud
                WHERE c.estado = :estado AND c.id_area = :area';
        $parametros['area'] = $area;
    } else {
        throw new InvalidArgumentException('Solo subdirectores y jefes de departamento pueden consultar comisiones pendientes');
    }

    $consulta_comisiones = $pdo->prepare($sql);
    $consulta_comisiones->execute($parametros);
    $comisiones = $consulta_comisiones->fetchAll(PDO::FETCH_ASSOC);

    return $comisiones;
}
    
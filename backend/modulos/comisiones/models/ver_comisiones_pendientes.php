<?php

function ver_comisiones($pdo, int $puesto, ?int $area) {
    $parametros = ['estado' => 'PENDIENTE'];

    if ($puesto === 1) {
        $sql = 'SELECT c.* FROM comisiones c WHERE c.estado = :estado';
    } elseif ($puesto === 2 && $area !== null) {
        $sql = 'SELECT c.*
                FROM comisiones c
                INNER JOIN areas a ON a.id_area = c.id_area
                WHERE c.estado = :estado AND a.id_subdireccion = :area';
        $parametros['area'] = $area;
    } elseif ($puesto === 3 && $area !== null) {
        $sql = 'SELECT c.*
                FROM comisiones c
                WHERE c.estado = :estado AND c.id_area = :area';
        $parametros['area'] = $area;
    } else {
        throw new InvalidArgumentException('El puesto o el área no son válidos para consultar comisiones');
    }

    $consulta_comisiones = $pdo->prepare($sql);
    $consulta_comisiones->execute($parametros);
    $comisiones = $consulta_comisiones->fetchAll(PDO::FETCH_ASSOC);

    return $comisiones;
}
    
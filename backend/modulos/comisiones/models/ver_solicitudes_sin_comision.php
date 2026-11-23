<?php

function ver_solicitudes_sin_comision($pdo): array {
    $consulta = $pdo->query(
        'SELECT id_solicitud, fecha, institucion_solicita, ubicacion, nombre_solicita
         FROM solicitudes
         WHERE comisionada = 0
         ORDER BY fecha DESC, id_solicitud DESC'
    );

    return $consulta->fetchAll(PDO::FETCH_ASSOC);
}

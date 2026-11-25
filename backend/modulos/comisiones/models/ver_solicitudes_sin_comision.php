<?php

function ver_solicitudes_sin_comision($pdo): array {
    $consulta = $pdo->query(
        'SELECT id_solicitud, fecha_registro, institucion_solicita, ubicacion, nombre_solicita,
                falla_reportada, num_contacto, fecha_comision
         FROM solicitudes
         WHERE comisionada = 0
         ORDER BY fecha_registro DESC, id_solicitud DESC'
    );

    return $consulta->fetchAll(PDO::FETCH_ASSOC);
}

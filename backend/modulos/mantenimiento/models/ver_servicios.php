<?php

function ver_servicios($pdo){
    $query = $pdo->query(
        "SELECT id_servicio, nombre, periodicidad, descripcion, activo
        FROM servicios
        WHERE activo = 1
        ORDER BY nombre ASC, id_servicio ASC"
    );

    return $query->fetchAll(PDO::FETCH_ASSOC);
}

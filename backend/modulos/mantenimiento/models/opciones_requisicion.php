<?php

function obtener_opciones_requisicion($pdo, $id_elabora) {
    $query = $pdo->query(
        "SELECT id_personal, nombre, apellido_p, apellido_m, correo, telefono, puesto
        FROM personal
        WHERE activo = '1' AND puesto IN (1, 2, 3)
        ORDER BY nombre, apellido_p, apellido_m"
    );
    $personal = $query->fetchAll(PDO::FETCH_ASSOC);

    $elabora = null;
    foreach ($personal as $persona) {
        if ((int) $persona['id_personal'] === (int) $id_elabora) {
            $elabora = $persona;
            break;
        }
    }

    $director = null;
    foreach ($personal as $persona) {
        if ((int) $persona['puesto'] === 1) {
            $director = $persona;
            break;
        }
    }

    return ['personal' => $personal, 'elabora' => $elabora, 'director' => $director];
}

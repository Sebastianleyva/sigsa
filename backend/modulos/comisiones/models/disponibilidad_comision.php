<?php

// Estados en los que una comisión foránea ya tiene personal asignado pero todavía no inicia
const COMISION_ESTADOS_POR_INICIAR = ['PENDIENTE DE SOLICITUDES_V', 'PROGRAMADA'];

/**
 * Devuelve, por id_personal, el motivo por el que no puede participar en $comision.
 *
 * Bloquea a la persona si:
 *  - participa en otra comisión EN CURSO, o
 *  - participa en otra comisión foránea por iniciar cuyas fechas se traslapan
 *    (si esa comisión no tiene fechas registradas, se bloquea por precaución).
 * Una comisión urbana se considera ocupando el día de hoy.
 *
 * $comision requiere: id_comision, tipo_comision, fecha_inicio, fecha_final.
 */
function consultar_conflictos_comision($pdo, array $ids_personal, array $comision): array {
    if (count($ids_personal) === 0) {
        return [];
    }

    $es_foranea = $comision['tipo_comision'] === 'FORANEA';
    $parametros = [
        'id_comision' => (int) $comision['id_comision'],
        'inicio_nueva' => $es_foranea ? $comision['fecha_inicio'] : null,
        'fin_nueva' => $es_foranea ? $comision['fecha_final'] : null,
        'estado_por_iniciar_1' => COMISION_ESTADOS_POR_INICIAR[0],
        'estado_por_iniciar_2' => COMISION_ESTADOS_POR_INICIAR[1],
    ];
    $placeholders = [];
    foreach (array_values($ids_personal) as $indice => $id_personal) {
        $placeholder = 'personal_' . $indice;
        $placeholders[] = ':' . $placeholder;
        $parametros[$placeholder] = (int) $id_personal;
    }

    $consulta = $pdo->prepare(
        "SELECT pc.id_personal, o.id_comision, o.estado, o.fecha_inicio, o.fecha_final
         FROM personal_comisionado pc
         INNER JOIN comisiones o ON o.id_comision = pc.id_comision
         WHERE pc.id_personal IN (" . implode(', ', $placeholders) . ")
           AND o.id_comision <> :id_comision
           AND (
               o.estado = 'EN CURSO'
               OR (
                   o.estado IN (:estado_por_iniciar_1, :estado_por_iniciar_2)
                   AND (
                       o.fecha_inicio IS NULL
                       OR o.fecha_final IS NULL
                       OR (
                           o.fecha_inicio <= COALESCE(:fin_nueva, CURDATE())
                           AND o.fecha_final >= COALESCE(:inicio_nueva, CURDATE())
                       )
                   )
               )
           )
         ORDER BY o.estado = 'EN CURSO' DESC, o.fecha_inicio, o.id_comision"
    );
    $consulta->execute($parametros);

    $formatear = static function ($valor): string {
        $fecha = DateTime::createFromFormat('!Y-m-d', (string) $valor);
        return $fecha ? $fecha->format('d/m/Y') : (string) $valor;
    };

    $conflictos = [];
    foreach ($consulta->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $id_personal = (int) $fila['id_personal'];
        if (isset($conflictos[$id_personal])) {
            continue;
        }
        if ($fila['estado'] === 'EN CURSO') {
            $conflictos[$id_personal] = "En la comisión #{$fila['id_comision']}, que está en curso";
        } elseif ($fila['fecha_inicio'] === null || $fila['fecha_final'] === null) {
            $conflictos[$id_personal] = "Asignado a la comisión #{$fila['id_comision']}, que no tiene fechas registradas";
        } else {
            $conflictos[$id_personal] = 'Comisionado del ' . $formatear($fila['fecha_inicio'])
                . ' al ' . $formatear($fila['fecha_final'])
                . " (comisión #{$fila['id_comision']})";
        }
    }

    return $conflictos;
}

/**
 * Recalcula personal.comisionado para el personal de una comisión.
 * La bandera indica que la persona está en al menos una comisión EN CURSO;
 * el módulo de salidas la usa para bloquear solicitudes de salida.
 * Debe llamarse después de cambiar el estado de la comisión.
 */
function sincronizar_bandera_comisionado($pdo, int $id_comision): void {
    $actualizar = $pdo->prepare(
        "UPDATE personal p
         SET p.comisionado = EXISTS (
             SELECT 1
             FROM personal_comisionado pc_activa
             INNER JOIN comisiones c_activa ON c_activa.id_comision = pc_activa.id_comision
             WHERE pc_activa.id_personal = p.id_personal
               AND c_activa.estado = 'EN CURSO'
         )
         WHERE p.id_personal IN (
             SELECT pc.id_personal
             FROM personal_comisionado pc
             WHERE pc.id_comision = :id_comision
         )"
    );
    $actualizar->execute(['id_comision' => $id_comision]);
}

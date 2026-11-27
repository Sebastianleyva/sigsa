<?php

require_once __DIR__ . '/disponibilidad_comision.php';

class ComisionProgramadaConflictException extends RuntimeException {}

/**
 * Comisiones foráneas con todas sus solicitudes de viáticos completas que esperan su fecha de inicio.
 * Las ve el director (todas), el subdirector (las de su subdirección) y el personal asignado.
 */
function ver_comisiones_programadas($pdo, int $puesto, ?int $area, int $id_personal): array {
    $consulta_comisiones = $pdo->prepare(
        "SELECT c.id_comision, c.id_solicitud, c.fecha, c.fecha_inicio, c.fecha_final,
                c.tipo_comision, c.tipo_destino, c.id_area, c.estado,
                a.nombre AS area_nombre, s.ubicacion AS lugar,
                s.institucion_solicita, s.nombre_solicita, s.falla_reportada,
                s.num_contacto, s.fecha_comision,
                c.fecha_inicio <= CURDATE() AS fecha_alcanzada
         FROM comisiones c
         INNER JOIN areas a ON a.id_area = c.id_area
         INNER JOIN solicitudes s ON s.id_solicitud = c.id_solicitud
         WHERE c.estado = 'PROGRAMADA'
           AND (
               :es_director = 1
               OR (:es_subdirector = 1 AND a.id_subdireccion = :area)
               OR EXISTS (
                   SELECT 1 FROM personal_comisionado pc
                   WHERE pc.id_comision = c.id_comision AND pc.id_personal = :id_personal
               )
           )
         ORDER BY c.fecha_inicio, c.id_comision"
    );
    $consulta_comisiones->execute([
        'es_director' => $puesto === 1 ? 1 : 0,
        'es_subdirector' => $puesto === 2 ? 1 : 0,
        'area' => $area ?? 0,
        'id_personal' => $id_personal,
    ]);
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
        // La fecha se compara en la base de datos para no depender del reloj del navegador
        $comision['puede_iniciar'] = (int) $comision['fecha_alcanzada'] === 1;
        unset($comision['fecha_alcanzada']);
    }
    unset($comision);

    return $comisiones;
}

function iniciar_comision($pdo, $input, int $puesto, ?int $area, int $id_personal): array {
    $id_comision = filter_var($input['id_comision'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_comision === false) {
        throw new InvalidArgumentException('El id_comision debe ser un entero positivo');
    }

    $pdo->beginTransaction();
    try {
        $consulta_comision = $pdo->prepare(
            'SELECT c.estado, c.fecha_inicio, a.id_subdireccion,
                    c.fecha_inicio <= CURDATE() AS fecha_alcanzada
             FROM comisiones c
             INNER JOIN areas a ON a.id_area = c.id_area
             WHERE c.id_comision = :id_comision
             FOR UPDATE'
        );
        $consulta_comision->execute(['id_comision' => $id_comision]);
        $comision = $consulta_comision->fetch(PDO::FETCH_ASSOC);
        if (!$comision) {
            throw new OutOfBoundsException('La comisión no existe');
        }

        $consulta_participante = $pdo->prepare(
            'SELECT 1 FROM personal_comisionado
             WHERE id_comision = :id_comision AND id_personal = :id_personal'
        );
        $consulta_participante->execute(['id_comision' => $id_comision, 'id_personal' => $id_personal]);
        $es_participante = (bool) $consulta_participante->fetchColumn();
        $es_subdirector = $puesto === 2 && $area !== null && (int) $comision['id_subdireccion'] === $area;
        if ($puesto !== 1 && !$es_subdirector && !$es_participante) {
            throw new DomainException('No tienes permisos para iniciar esta comisión');
        }

        if ($comision['estado'] !== 'PROGRAMADA') {
            throw new ComisionProgramadaConflictException('La comisión no está programada');
        }
        if ((int) $comision['fecha_alcanzada'] !== 1) {
            $fecha = DateTime::createFromFormat('!Y-m-d', (string) $comision['fecha_inicio']);
            $fecha_texto = $fecha ? $fecha->format('d/m/Y') : (string) $comision['fecha_inicio'];
            throw new ComisionProgramadaConflictException(
                "La comisión no puede iniciar antes de su fecha de inicio ({$fecha_texto})"
            );
        }

        // Bloquea al equipo y confirma que nadie siga en otra comisión en curso
        $consulta_equipo = $pdo->prepare(
            'SELECT p.id_personal
             FROM personal p
             INNER JOIN personal_comisionado pc ON pc.id_personal = p.id_personal
             WHERE pc.id_comision = :id_comision
             ORDER BY p.id_personal
             FOR UPDATE'
        );
        $consulta_equipo->execute(['id_comision' => $id_comision]);
        $ids_equipo = array_map('intval', $consulta_equipo->fetchAll(PDO::FETCH_COLUMN));

        $consulta_ocupados = $pdo->prepare(
            "SELECT DISTINCT CONCAT_WS(' ', p.nombre, p.apellido_p, p.apellido_m) AS nombre
             FROM personal_comisionado pc
             INNER JOIN comisiones o ON o.id_comision = pc.id_comision
             INNER JOIN personal p ON p.id_personal = pc.id_personal
             WHERE o.estado = 'EN CURSO'
               AND o.id_comision <> :id_comision_actual
               AND pc.id_personal IN (
                   SELECT id_personal FROM personal_comisionado WHERE id_comision = :id_comision_equipo
               )
             ORDER BY nombre"
        );
        $consulta_ocupados->execute([
            'id_comision_actual' => $id_comision,
            'id_comision_equipo' => $id_comision,
        ]);
        $ocupados = $consulta_ocupados->fetchAll(PDO::FETCH_COLUMN);
        if (count($ocupados) > 0) {
            throw new ComisionProgramadaConflictException(
                'No se puede iniciar: ' . implode(', ', $ocupados) . ' sigue en otra comisión en curso'
            );
        }

        $actualizar = $pdo->prepare(
            "UPDATE comisiones
             SET estado = 'EN CURSO'
             WHERE id_comision = :id_comision AND estado = 'PROGRAMADA'"
        );
        $actualizar->execute(['id_comision' => $id_comision]);
        if ($actualizar->rowCount() !== 1) {
            throw new ComisionProgramadaConflictException('La comisión ya fue iniciada');
        }
        sincronizar_bandera_comisionado($pdo, $id_comision);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return [
        'id_comision' => $id_comision,
        'estado' => 'EN CURSO',
        'fecha_inicio' => $comision['fecha_inicio'],
        'ids_personal' => $ids_equipo,
    ];
}

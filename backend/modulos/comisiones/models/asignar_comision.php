<?php

function asignar_comision($pdo, $input, int $puesto, ?int $area): array {
    $id_comision = filter_var($input['id_comision'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_comision === false) {
        throw new InvalidArgumentException('El id_comision debe ser un entero positivo');
    }

    $ids_personal_raw = $input['ids_personal'] ?? null;
    if (!is_array($ids_personal_raw) || count($ids_personal_raw) === 0) {
        throw new InvalidArgumentException('Selecciona al menos una persona para la comisión');
    }

    $ids_personal = [];
    foreach ($ids_personal_raw as $id_personal_raw) {
        $id_personal = filter_var($id_personal_raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id_personal === false) {
            throw new InvalidArgumentException('El personal seleccionado no es válido');
        }
        $ids_personal[] = $id_personal;
    }
    $ids_personal = array_values(array_unique($ids_personal));
    sort($ids_personal, SORT_NUMERIC);

    if (!in_array($puesto, [2, 3], true) || $area === null) {
        throw new InvalidArgumentException('El usuario no puede asignar esta comisión');
    }

    $pdo->beginTransaction();
    try {
        $consulta_comision = $pdo->prepare(
            'SELECT c.id_area, c.estado, a.id_subdireccion
             FROM comisiones c
             INNER JOIN areas a ON a.id_area = c.id_area
             WHERE c.id_comision = :id_comision
             FOR UPDATE'
        );
        $consulta_comision->execute(['id_comision' => $id_comision]);
        $comision = $consulta_comision->fetch(PDO::FETCH_ASSOC);
        $pertenece_al_area = $comision && (
            ($puesto === 3 && (int) $comision['id_area'] === $area)
            || ($puesto === 2 && (int) $comision['id_subdireccion'] === $area)
        );
        if (!$pertenece_al_area || $comision['estado'] !== 'PENDIENTE') {
            throw new InvalidArgumentException('La comisión no está pendiente o no pertenece a tu área');
        }

        $placeholders = [];
        $parametros_personal = ['area' => (int) $comision['id_area']];
        foreach ($ids_personal as $indice => $id_personal) {
            $placeholder = 'personal_' . $indice;
            $placeholders[] = ':' . $placeholder;
            $parametros_personal[$placeholder] = $id_personal;
        }
        $consulta_personal = $pdo->prepare(
            "SELECT id_personal, comisionado
             FROM personal
             WHERE area = :area AND activo = '1' AND puesto IN (3, 4)
               AND id_personal IN (" . implode(', ', $placeholders) . ')
             ORDER BY id_personal
             FOR UPDATE'
        );
        $consulta_personal->execute($parametros_personal);
        $personal_valido = $consulta_personal->fetchAll(PDO::FETCH_ASSOC);
        $ids_validos = array_map('intval', array_column($personal_valido, 'id_personal'));
        if (count($ids_validos) !== count($ids_personal)) {
            throw new InvalidArgumentException('Solo puedes asignar personal activo del área de la comisión');
        }

        $consulta_asignados = $pdo->prepare(
            'SELECT id_personal FROM personal_comisionado WHERE id_comision = :id_comision'
        );
        $consulta_asignados->execute(['id_comision' => $id_comision]);
        $ids_existentes = array_map('intval', $consulta_asignados->fetchAll(PDO::FETCH_COLUMN));
        $ids_existentes_lookup = array_fill_keys($ids_existentes, true);
        foreach ($personal_valido as $persona) {
            $id_personal = (int) $persona['id_personal'];
            if ((int) $persona['comisionado'] === 1 && !isset($ids_existentes_lookup[$id_personal])) {
                throw new InvalidArgumentException('Una o más personas seleccionadas ya participan en otra comisión');
            }
        }

        $ids_nuevos = array_values(array_diff($ids_personal, $ids_existentes));
        $marcar_comisionado = $pdo->prepare(
            'UPDATE personal SET comisionado = 1 WHERE id_personal = :id_personal'
        );
        foreach ($ids_personal as $id_personal) {
            $marcar_comisionado->execute(['id_personal' => $id_personal]);
        }

        $insertar_personal = $pdo->prepare(
            'INSERT INTO personal_comisionado (id_comision, id_personal)
             VALUES (:id_comision, :id_personal)'
        );
        foreach ($ids_nuevos as $id_personal) {
            $insertar_personal->execute([
                'id_comision' => $id_comision,
                'id_personal' => $id_personal,
            ]);
        }

        if (count($ids_existentes) + count($ids_nuevos) === 0) {
            throw new InvalidArgumentException('Selecciona al menos una persona para la comisión');
        }

        $actualizar_comision = $pdo->prepare(
            "UPDATE comisiones
             SET estado = 'EN CURSO', fecha_inicio = CURDATE()
             WHERE id_comision = :id_comision AND estado = 'PENDIENTE'"
        );
        $actualizar_comision->execute(['id_comision' => $id_comision]);
        if ($actualizar_comision->rowCount() !== 1) {
            throw new InvalidArgumentException('La comisión ya fue iniciada');
        }

        $consulta_fecha = $pdo->prepare(
            'SELECT fecha_inicio FROM comisiones WHERE id_comision = :id_comision'
        );
        $consulta_fecha->execute(['id_comision' => $id_comision]);
        $fecha_inicio = $consulta_fecha->fetchColumn();
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
        'fecha_inicio' => $fecha_inicio,
        'ids_personal' => array_values(array_unique(array_merge($ids_existentes, $ids_personal))),
    ];
}
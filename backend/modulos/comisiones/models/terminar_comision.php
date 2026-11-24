<?php

function terminar_comision($pdo, $input, int $id_personal, int $puesto, ?int $area): array {
    $id_comision = filter_var(
        $input['id_comision'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    if ($id_comision === false) {
        throw new InvalidArgumentException('El id_comision debe ser un entero positivo');
    }

    $pdo->beginTransaction();
    try {
        $consulta = $pdo->prepare(
            'SELECT c.id_comision, c.id_area, c.estado, a.id_subdireccion
             FROM comisiones c
             INNER JOIN areas a ON a.id_area = c.id_area
             WHERE c.id_comision = :id_comision
             FOR UPDATE'
        );
        $consulta->execute(['id_comision' => $id_comision]);
        $comision = $consulta->fetch(PDO::FETCH_ASSOC);
        if (!$comision) {
            throw new OutOfBoundsException('Comisión no encontrada');
        }
        if (strtoupper($comision['estado']) !== 'EN CURSO') {
            throw new DomainException('Solo se pueden terminar comisiones en curso');
        }

        $consulta_participante = $pdo->prepare(
            'SELECT 1 FROM personal_comisionado
             WHERE id_comision = :id_comision AND id_personal = :id_personal'
        );
        $consulta_participante->execute([
            'id_comision' => $id_comision,
            'id_personal' => $id_personal,
        ]);
        $es_participante = (bool) $consulta_participante->fetchColumn();
        $es_jefe_departamento = $puesto === 3 && $area !== null && (int) $comision['id_area'] === $area;
        if (!$es_participante && !$es_jefe_departamento) {
            throw new DomainException('No tienes permisos para terminar esta comisión');
        }

        $actualizar = $pdo->prepare(
            "UPDATE comisiones
             SET estado = 'FINALIZADA', fecha_final = CURDATE()
             WHERE id_comision = :id_comision AND UPPER(estado) = 'EN CURSO'"
        );
        $actualizar->execute(['id_comision' => $id_comision]);
        if ($actualizar->rowCount() !== 1) {
            throw new DomainException('La comisión ya fue terminada por otro usuario');
        }

        $consulta_asignados = $pdo->prepare(
            'SELECT id_personal FROM personal_comisionado WHERE id_comision = :id_comision'
        );
        $consulta_asignados->execute(['id_comision' => $id_comision]);
        $ids_asignados = $consulta_asignados->fetchAll(PDO::FETCH_COLUMN);

        if (count($ids_asignados) > 0) {
            $placeholders = [];
            $parametros = [];
            foreach ($ids_asignados as $indice => $id_asignado) {
                $placeholder = 'personal_' . $indice;
                $placeholders[] = ':' . $placeholder;
                $parametros[$placeholder] = (int) $id_asignado;
            }
            $liberar = $pdo->prepare(
                'UPDATE personal SET comisionado = 0
                 WHERE id_personal IN (' . implode(', ', $placeholders) . ')'
            );
            $liberar->execute($parametros);
        }

        $pdo->prepare('DELETE FROM personal_comisionado WHERE id_comision = :id_comision')
            ->execute(['id_comision' => $id_comision]);

        $consulta_resultado = $pdo->prepare(
            'SELECT id_comision, estado, fecha_inicio, fecha_final
             FROM comisiones WHERE id_comision = :id_comision'
        );
        $consulta_resultado->execute(['id_comision' => $id_comision]);
        $resultado = $consulta_resultado->fetch(PDO::FETCH_ASSOC);
        $pdo->commit();
        return $resultado;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

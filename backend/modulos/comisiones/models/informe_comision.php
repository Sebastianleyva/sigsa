<?php

const INFORME_COMISION_CAMPOS = [
    'objetivo_comision',
    'actividad_desarrollada',
    'resultados',
    'contribucion',
    'conclusion',
];

const INFORME_COMISION_ESTADOS_PERMITIDOS = ['EN CURSO', 'FINALIZADA'];

function ver_datos_informe_comision($pdo, int $id_comision, int $id_personal): array {
    $consulta = $pdo->prepare(
        "SELECT c.id_comision, c.estado, c.fecha_inicio, c.fecha_final,
                s.ubicacion AS lugar, s.fecha_comision,
                p.nombre, p.apellido_p, p.apellido_m, p.rfc,
                a.nombre AS area_nombre
         FROM comisiones c
         INNER JOIN solicitudes s ON s.id_solicitud = c.id_solicitud
         INNER JOIN personal_comisionado pc
                 ON pc.id_comision = c.id_comision AND pc.id_personal = :id_personal
         INNER JOIN personal p ON p.id_personal = pc.id_personal
         LEFT JOIN areas a ON a.id_area = p.area
         WHERE c.id_comision = :id_comision
         LIMIT 1"
    );
    $consulta->execute(['id_comision' => $id_comision, 'id_personal' => $id_personal]);
    $datos = $consulta->fetch(PDO::FETCH_ASSOC);
    if (!$datos) {
        throw new OutOfBoundsException('No participas en esta comisión o la comisión no existe');
    }

    $existente = $pdo->prepare(
        'SELECT 1 FROM informe_comision
         WHERE id_comision = :id_comision AND id_personal = :id_personal'
    );
    $existente->execute(['id_comision' => $id_comision, 'id_personal' => $id_personal]);

    return [
        'id_comision' => (int) $datos['id_comision'],
        'estado' => $datos['estado'],
        'lugar' => $datos['lugar'],
        'fecha_inicio' => $datos['fecha_inicio'],
        'fecha_final' => $datos['fecha_final'],
        'fecha_comision' => $datos['fecha_comision'],
        'nombre_completo' => trim($datos['nombre'] . ' ' . $datos['apellido_p'] . ' ' . $datos['apellido_m']),
        'rfc' => $datos['rfc'],
        'area_nombre' => $datos['area_nombre'] ?? 'Sin área',
        'informe_registrado' => (bool) $existente->fetchColumn(),
        'puede_registrar' => in_array(
            strtoupper(trim($datos['estado'])),
            INFORME_COMISION_ESTADOS_PERMITIDOS,
            true
        ),
    ];
}

function crear_informe_comision($pdo, array $input, int $id_personal): array {
    $id_comision = filter_var($input['id_comision'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_comision === false) {
        throw new InvalidArgumentException('El id_comision debe ser un entero positivo');
    }

    $valores = [];
    foreach (INFORME_COMISION_CAMPOS as $campo) {
        if (!isset($input[$campo]) || !is_string($input[$campo]) || trim($input[$campo]) === '') {
            throw new InvalidArgumentException('Todos los apartados del informe son obligatorios');
        }
        $valor = trim($input[$campo]);
        if (mb_strlen($valor) > 5000) {
            throw new InvalidArgumentException('Cada apartado del informe puede tener como máximo 5000 caracteres');
        }
        $valores[$campo] = $valor;
    }

    $pdo->beginTransaction();
    try {
        $consulta = $pdo->prepare(
            'SELECT estado FROM comisiones WHERE id_comision = :id_comision FOR UPDATE'
        );
        $consulta->execute(['id_comision' => $id_comision]);
        $comision = $consulta->fetch(PDO::FETCH_ASSOC);
        if (!$comision) {
            throw new OutOfBoundsException('Comisión no encontrada');
        }
        $estado = strtoupper(trim($comision['estado']));
        if (!in_array($estado, INFORME_COMISION_ESTADOS_PERMITIDOS, true)) {
            throw new DomainException('Solo puedes registrar el informe de una comisión en curso o finalizada');
        }

        $consulta_participante = $pdo->prepare(
            'SELECT 1 FROM personal_comisionado
             WHERE id_comision = :id_comision AND id_personal = :id_personal'
        );
        $consulta_participante->execute(['id_comision' => $id_comision, 'id_personal' => $id_personal]);
        if (!$consulta_participante->fetchColumn()) {
            throw new DomainException('No participas en esta comisión');
        }

        $existente = $pdo->prepare(
            'SELECT 1 FROM informe_comision
             WHERE id_comision = :id_comision AND id_personal = :id_personal'
        );
        $existente->execute(['id_comision' => $id_comision, 'id_personal' => $id_personal]);
        if ($existente->fetchColumn()) {
            throw new DomainException('Ya registraste el informe de esta comisión');
        }

        $insertar = $pdo->prepare(
            'INSERT INTO informe_comision
             (id_comision, id_personal, objetivo_comision, actividad_desarrollada,
              resultados, contribucion, conclusion)
             VALUES
             (:id_comision, :id_personal, :objetivo_comision, :actividad_desarrollada,
              :resultados, :contribucion, :conclusion)'
        );
        $insertar->execute(array_merge($valores, [
            'id_comision' => $id_comision,
            'id_personal' => $id_personal,
        ]));
        $id_informe = (int) $pdo->lastInsertId();

        if ($estado !== 'FINALIZADA') {
            $actualizar_comision = $pdo->prepare(
                "UPDATE comisiones SET estado = 'FINALIZADA' WHERE id_comision = :id_comision"
            );
            $actualizar_comision->execute(['id_comision' => $id_comision]);
            if ($actualizar_comision->rowCount() !== 1) {
                throw new RuntimeException('No fue posible finalizar la comisión');
            }

            $liberar_personal = $pdo->prepare(
                'UPDATE personal
                 SET comisionado = 0
                 WHERE id_personal IN (
                     SELECT id_personal
                     FROM personal_comisionado
                     WHERE id_comision = :id_comision
                 )'
            );
            $liberar_personal->execute(['id_comision' => $id_comision]);
        }

        $pdo->commit();
        return [
            'id_informe_comision' => $id_informe,
            'id_comision' => $id_comision,
            'id_personal' => $id_personal,
            'estado_comision' => 'FINALIZADA',
        ];
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

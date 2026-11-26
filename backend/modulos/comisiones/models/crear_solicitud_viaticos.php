<?php

class SolicitudViaticosConflictException extends RuntimeException {}

function crear_solicitud_viaticos($pdo, array $input, int $id_personal_registra): array {
    $id_comision = filter_var(
        $input['id_comision'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]],
    );
    $id_personal_comisionado = filter_var(
        $input['id_personal_comisionado'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]],
    );
    $id_zona = filter_var(
        $input['id_zona'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]],
    );
    if ($id_comision === false || $id_personal_comisionado === false || $id_zona === false) {
        throw new InvalidArgumentException('Los identificadores de comisión, personal y zona deben ser enteros positivos');
    }

    $campos = ['puesto' => 50, 'programa' => 10, 'clave_cobro' => 15];
    $textos = [];
    foreach ($campos as $campo => $maximo) {
        $valor = $input[$campo] ?? null;
        if (!is_string($valor) || trim($valor) === '' || strlen(trim($valor)) > $maximo) {
            throw new InvalidArgumentException("El campo {$campo} es obligatorio y no debe exceder {$maximo} caracteres");
        }
        $textos[$campo] = trim($valor);
    }

    foreach (['contacto_emergencia_nombre' => 150, 'contacto_emergencia_numero' => 10] as $campo => $maximo) {
        $valor = $input[$campo] ?? '';
        if (!is_string($valor) || strlen(trim($valor)) > $maximo) {
            throw new InvalidArgumentException("El campo {$campo} no debe exceder {$maximo} caracteres");
        }
        $textos[$campo] = trim($valor);
    }

    $observaciones = $input['observaciones'] ?? '';
    if (!is_string($observaciones)) {
        throw new InvalidArgumentException('Las observaciones deben ser texto');
    }

    $tipo_comprobacion = $input['tipo_comprobacion'] ?? null;
    if (!in_array($tipo_comprobacion, ['FACTURA', 'CONSTANCIA'], true)) {
        throw new InvalidArgumentException('El tipo de comprobación no es válido');
    }

    $renglones = $input['renglones'] ?? null;
    if (
        !is_array($renglones)
        || count($renglones) === 0
        || array_keys($renglones) !== range(0, count($renglones) - 1)
    ) {
        throw new InvalidArgumentException('Agrega al menos un renglón de viáticos');
    }

    $renglones_validados = [];
    foreach ($renglones as $indice => $renglon) {
        if (!is_array($renglon)) {
            throw new InvalidArgumentException('Uno o más renglones de viáticos no son válidos');
        }
        $id_codigo = filter_var(
            $renglon['id_codigo_contable_comision'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );
        if ($id_codigo === false) {
            throw new InvalidArgumentException('Selecciona una partida válida en cada renglón');
        }

        $dias_raw = $renglon['num_dias'] ?? null;
        $dias = null;
        if ($dias_raw !== null && $dias_raw !== '') {
            if (!is_numeric($dias_raw)) {
                throw new InvalidArgumentException('El número de días debe ser numérico o quedar vacío');
            }
            $dias = (float) $dias_raw;
            if (
                $dias < 0.5
                || $dias > 99999999.99
                || abs(($dias * 2) - round($dias * 2)) > 0.000001
            ) {
                throw new InvalidArgumentException('El número de días debe avanzar en incrementos de medio día');
            }
        }

        $importe = filter_var(
            $renglon['importe'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 0, 'max_range' => 9999999999]],
        );
        if ($importe === false) {
            throw new InvalidArgumentException('El importe es obligatorio y debe ser un monto entero no negativo');
        }

        $renglones_validados[] = [
            'indice' => $indice + 1,
            'id_codigo' => $id_codigo,
            'num_dias' => $dias,
            'importe' => $importe,
        ];
    }

    $pdo->beginTransaction();
    try {
        $consulta_usuario = $pdo->prepare(
            "SELECT puesto, area
             FROM personal
            WHERE id_personal = :id_personal AND activo = '1'"
        );
        $consulta_usuario->execute(['id_personal' => $id_personal_registra]);
        $usuario = $consulta_usuario->fetch(PDO::FETCH_ASSOC);
        if (!$usuario) {
            throw new DomainException('El usuario no está activo');
        }
        $area = filter_var($usuario['area'], FILTER_VALIDATE_INT);
        if (!in_array((int) $usuario['puesto'], [1, 2], true) && $area !== 6) {
            throw new DomainException('No tienes permisos para registrar solicitudes de viáticos');
        }

        $consulta_comision = $pdo->prepare(
            'SELECT estado, tipo_comision
             FROM comisiones
             WHERE id_comision = :id_comision
             FOR UPDATE'
        );
        $consulta_comision->execute(['id_comision' => $id_comision]);
        $comision = $consulta_comision->fetch(PDO::FETCH_ASSOC);
        if (!$comision) {
            throw new OutOfBoundsException('La comisión no existe');
        }
        if (
            $comision['tipo_comision'] !== 'FORANEA'
            || $comision['estado'] !== 'PENDIENTE DE SOLICITUDES_V'
        ) {
            throw new SolicitudViaticosConflictException(
                'La solicitud de viáticos solo puede registrarse para una comisión foránea pendiente',
            );
        }

        $consulta_participante = $pdo->prepare(
            'SELECT p.puesto
             FROM personal_comisionado pc
             INNER JOIN personal p ON p.id_personal = pc.id_personal
             WHERE pc.id_comision = :id_comision
               AND pc.id_personal = :id_personal'
        );
        $consulta_participante->execute([
            'id_comision' => $id_comision,
            'id_personal' => $id_personal_comisionado,
        ]);
        $id_puesto_comisionado = $consulta_participante->fetchColumn();
        if ($id_puesto_comisionado === false) {
            throw new InvalidArgumentException('El personal seleccionado no participa en esta comisión');
        }

        $consulta_existente = $pdo->prepare(
            'SELECT id_solicitud_viaticos
             FROM solicitudes_de_viaticos
             WHERE id_comision = :id_comision
               AND id_personal_comisionado = :id_personal'
        );
        $consulta_existente->execute([
            'id_comision' => $id_comision,
            'id_personal' => $id_personal_comisionado,
        ]);
        if ($consulta_existente->fetchColumn() !== false) {
            throw new SolicitudViaticosConflictException('Este comisionado ya tiene una solicitud de viáticos registrada');
        }

        $consulta_zona = $pdo->prepare(
            'SELECT permite_constancia FROM zonas_viaticos WHERE id_zona = :id_zona'
        );
        $consulta_zona->execute(['id_zona' => $id_zona]);
        $permite_constancia = $consulta_zona->fetchColumn();
        if ($permite_constancia === false) {
            throw new InvalidArgumentException('La zona seleccionada no existe');
        }
        if ($tipo_comprobacion === 'CONSTANCIA' && (int) $permite_constancia !== 1) {
            throw new InvalidArgumentException('La zona seleccionada no permite comprobación con constancia');
        }

        $consulta_codigo = $pdo->prepare(
            'SELECT 1
             FROM codigos_contables_comisiones
             WHERE id_codigo_contable_comision = :id_codigo AND activo = 1'
        );
        $consulta_tarifa = $pdo->prepare(
            'SELECT monto_diario
             FROM tabulador_viaticos
             WHERE id_puesto = :id_puesto
               AND id_zona = :id_zona
               AND tipo_comprobacion = :tipo_comprobacion
               AND pernocta = :pernocta'
        );
        foreach ($renglones_validados as &$renglon) {
            $consulta_codigo->execute(['id_codigo' => $renglon['id_codigo']]);
            if (!$consulta_codigo->fetchColumn()) {
                throw new InvalidArgumentException('Una partida seleccionada no existe o está inactiva');
            }

            $renglon['cuota_diaria'] = null;
            if ($renglon['num_dias'] !== null) {
                $pernocta = $renglon['num_dias'] >= 1 ? 1 : 0;
                $consulta_tarifa->execute([
                    'id_puesto' => (int) $id_puesto_comisionado,
                    'id_zona' => $id_zona,
                    'tipo_comprobacion' => $tipo_comprobacion,
                    'pernocta' => $pernocta,
                ]);
                $cuota = $consulta_tarifa->fetchColumn();
                if ($cuota === false) {
                    throw new InvalidArgumentException(
                        "No hay tarifa en el tabulador para el renglón {$renglon['indice']}",
                    );
                }
                $renglon['cuota_diaria'] = $cuota;
            }
        }
        unset($renglon);

        $insertar_solicitud = $pdo->prepare(
            'INSERT INTO solicitudes_de_viaticos
             (fecha, clave_cobro, puesto, contacto_emergencia_nombre,
              contacto_emergencia_numero, programa, id_personal_comisionado,
              id_personal_registra, financiamiento, id_comision, observaciones)
             VALUES
             (CURDATE(), :clave_cobro, :puesto, :contacto_emergencia_nombre,
              :contacto_emergencia_numero, :programa, :id_personal_comisionado,
              :id_personal_registra, :financiamiento, :id_comision, :observaciones)'
        );
        $insertar_solicitud->execute([
            'clave_cobro' => $textos['clave_cobro'],
            'puesto' => $textos['puesto'],
            'contacto_emergencia_nombre' => $textos['contacto_emergencia_nombre'] ?: null,
            'contacto_emergencia_numero' => $textos['contacto_emergencia_numero'] ?: null,
            'programa' => $textos['programa'],
            'id_personal_comisionado' => $id_personal_comisionado,
            'id_personal_registra' => $id_personal_registra,
            'financiamiento' => '25021 FONE',
            'id_comision' => $id_comision,
            'observaciones' => trim($observaciones),
        ]);
        $id_solicitud_viaticos = (int) $pdo->lastInsertId();

        $insertar_renglon = $pdo->prepare(
            'INSERT INTO viaticos_comisiones
             (id_solicitud_viaticos, id_codigo_c_comision, num_dias,
              cuota_diaria, importe, tipo_comprobacion)
             VALUES
             (:id_solicitud_viaticos, :id_codigo, :num_dias,
              :cuota_diaria, :importe, :tipo_comprobacion)'
        );
        foreach ($renglones_validados as $renglon) {
            $insertar_renglon->execute([
                'id_solicitud_viaticos' => $id_solicitud_viaticos,
                'id_codigo' => $renglon['id_codigo'],
                'num_dias' => $renglon['num_dias'],
                'cuota_diaria' => $renglon['cuota_diaria'],
                'importe' => $renglon['importe'],
                'tipo_comprobacion' => $tipo_comprobacion,
            ]);
        }

        $consulta_pendientes = $pdo->prepare(
            'SELECT COUNT(*)
             FROM personal_comisionado pc
             WHERE pc.id_comision = :id_comision
               AND NOT EXISTS (
                   SELECT 1
                   FROM solicitudes_de_viaticos sv
                   WHERE sv.id_comision = pc.id_comision
                     AND sv.id_personal_comisionado = pc.id_personal
               )'
        );
        $consulta_pendientes->execute(['id_comision' => $id_comision]);
        $personal_pendiente = (int) $consulta_pendientes->fetchColumn();

        $estado_resultante = 'PENDIENTE DE SOLICITUDES_V';
        if ($personal_pendiente === 0) {
            $actualizar_comision = $pdo->prepare(
                "UPDATE comisiones
                 SET estado = 'EN CURSO', fecha_inicio = CURDATE()
                 WHERE id_comision = :id_comision
                   AND estado = 'PENDIENTE DE SOLICITUDES_V'"
            );
            $actualizar_comision->execute(['id_comision' => $id_comision]);
            if ($actualizar_comision->rowCount() !== 1) {
                throw new SolicitudViaticosConflictException(
                    'No fue posible iniciar la comisión después de completar las solicitudes',
                );
            }
            $estado_resultante = 'EN CURSO';
        }

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    return [
        'id_solicitud_viaticos' => $id_solicitud_viaticos,
        'id_comision' => $id_comision,
        'id_personal_comisionado' => $id_personal_comisionado,
        'estado_comision' => $estado_resultante,
        'personal_pendiente' => $personal_pendiente,
    ];
}

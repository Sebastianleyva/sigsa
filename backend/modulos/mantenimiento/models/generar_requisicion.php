<?php

function generar_requisicion($pdo, $input, $usuario) {
    $id_reporte = validar_entero_positivo($input['id_reporte'] ?? null, 'id_reporte');
    $id_elabora = validar_entero_positivo($usuario['sub'] ?? null, 'id_elabora');
    $justificacion = validar_texto_obligatorio($input['justificacion'] ?? null, 'justificacion');
    $fecha = validar_fecha($input['fecha'] ?? null, 'fecha');
    $fecha_entrega = validar_fecha_opcional($input['fecha_entrega'] ?? null, 'fecha_entrega');
    $detalles = $input['detalles'] ?? null;
    $tipo_requisicion = validar_opcion($input['tipo_requisicion'] ?? null, ['SERVICIOS', 'MATERIALES'], 'tipo_requisicion');
    $tipo_codigo = validar_opcion($input['tipo_codigo'] ?? null, ['CC_DIRECCION', 'CC_CENTRAL'], 'tipo_codigo');
    $id_codigo = null;
    $cc_central = null;

    if ($tipo_codigo === 'CC_DIRECCION') {
        $id_codigo = validar_entero_positivo($input['id_codigo'] ?? null, 'id_codigo');
        $query = $pdo->prepare("SELECT id_codigo FROM codigos_contables WHERE id_codigo = :id_codigo");
        $query->execute([':id_codigo' => $id_codigo]);
        if (!$query->fetchColumn()) {
            throw new InvalidArgumentException('El código contable seleccionado no es válido');
        }
    } else {
        $cc_central = validar_codigo_central($input['cc_central'] ?? null);
    }

    if (!is_array($detalles) || count($detalles) === 0) {
        throw new InvalidArgumentException('Debe existir al menos un renglón de requisición');
    }

    $detalles_validos = [];
    foreach ($detalles as $indice => $detalle) {
        if (!is_array($detalle)) {
            throw new InvalidArgumentException("El renglón " . ($indice + 1) . " no es válido");
        }
        $descripcion = validar_texto_obligatorio($detalle['descripcion'] ?? null, "la descripción del renglón " . ($indice + 1));
        $num_lote = trim((string) ($detalle['num_lote'] ?? ''));
        if (strlen($num_lote) > 50) {
            throw new InvalidArgumentException("El Núm. Lote del renglón " . ($indice + 1) . " es demasiado largo");
        }
        $cantidad = $detalle['cantidad'] ?? null;
        if ($cantidad === '' || $cantidad === null) {
            $cantidad = null;
        } elseif (filter_var($cantidad, FILTER_VALIDATE_INT) === false || (int) $cantidad < 0) {
            throw new InvalidArgumentException("La cantidad del renglón " . ($indice + 1) . " debe ser un entero positivo o estar vacía");
        } else {
            $cantidad = (int) $cantidad;
        }
        $detalles_validos[] = [
            'num_lote' => $num_lote === '' ? null : $num_lote,
            'cantidad' => $cantidad,
            'descripcion' => $descripcion,
        ];
    }

    $pdo->beginTransaction();
    try {
        $query = $pdo->prepare("SELECT estado, id_incidencia, id_calendario, id_personal FROM reportes WHERE id_reporte = :id_reporte FOR UPDATE");
        $query->execute([':id_reporte' => $id_reporte]);
        $reporte = $query->fetch(PDO::FETCH_ASSOC);
        if (!$reporte) throw new OutOfBoundsException('Reporte no encontrado');
        if (!in_array(strtoupper(trim($reporte['estado'])), ['ESPERA REQUISICIÓN', 'EN ESPERA DE REQUISICIÓN'], true)) {
            throw new DomainException('El reporte no está pendiente de requisición');
        }

        if ($reporte['id_incidencia']) {
            $query = $pdo->prepare("SELECT id_autoriza FROM incidencias WHERE id_incidencia = :id_incidencia");
            $query->execute([':id_incidencia' => $reporte['id_incidencia']]);
            $id_solicita = $query->fetchColumn();
        } elseif ($reporte['id_calendario']) {
            $id_solicita = $reporte['id_personal'];
        } else {
            throw new DomainException('El reporte no tiene un origen válido');
        }
        if (!$id_solicita) {
            throw new DomainException('El reporte no tiene una persona solicitante registrada');
        }

        $query = $pdo->query(
            "SELECT id_personal FROM personal WHERE activo = '1' AND puesto = 1 ORDER BY id_personal LIMIT 1"
        );
        $id_autoriza = $query->fetchColumn();
        if (!$id_autoriza) {
            throw new DomainException('No hay un director activo configurado para autorizar la requisición');
        }

        $query = $pdo->prepare("SELECT id_requisicion FROM requisiciones WHERE id_reporte = :id_reporte LIMIT 1");
        $query->execute([':id_reporte' => $id_reporte]);
        if ($query->fetchColumn() !== false) throw new DomainException('Este reporte ya tiene una requisición registrada');

        validar_personal_requisicion($pdo, $id_solicita, 'solicitante');
        validar_personal_requisicion($pdo, $id_autoriza, 'autorizador');
        $descripcion_servicio = implode("\n", array_column($detalles_validos, 'descripcion'));

        $query = $pdo->prepare(
            "INSERT INTO requisiciones
            (tipo_requisicion, id_reporte, id_solicita, id_autoriza, fecha, fecha_entrega, justificacion, descripcion_servicio, estado, tipo_financiamiento, id_elabora, tipo_codigo, id_codigo, cc_central)
            VALUES (:tipo_requisicion, :id_reporte, :id_solicita, :id_autoriza, :fecha, :fecha_entrega, :justificacion, :descripcion_servicio, 'EN ESPERA', 'FONE', :id_elabora, :tipo_codigo, :id_codigo, :cc_central)"
        );
        $query->execute([
            ':tipo_requisicion' => $tipo_requisicion,
            ':id_reporte' => $id_reporte,
            ':id_solicita' => $id_solicita,
            ':id_autoriza' => $id_autoriza,
            ':fecha' => $fecha,
            ':fecha_entrega' => $fecha_entrega,
            ':justificacion' => $justificacion,
            ':descripcion_servicio' => $descripcion_servicio,
            ':id_elabora' => $id_elabora,
            ':tipo_codigo' => $tipo_codigo,
            ':id_codigo' => $id_codigo,
            ':cc_central' => $cc_central,
        ]);
        $id_requisicion = (int) $pdo->lastInsertId();

        $queryDetalle = $pdo->prepare(
            "INSERT INTO requisiciones_detalle (id_requisicion, num_lote, cantidad, descripcion)
            VALUES (:id_requisicion, :num_lote, :cantidad, :descripcion)"
        );
        foreach ($detalles_validos as $detalle) {
            $queryDetalle->bindValue(':id_requisicion', $id_requisicion, PDO::PARAM_INT);
            $queryDetalle->bindValue(':num_lote', $detalle['num_lote'], $detalle['num_lote'] === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $queryDetalle->bindValue(':cantidad', $detalle['cantidad'], $detalle['cantidad'] === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $queryDetalle->bindValue(':descripcion', $detalle['descripcion'], PDO::PARAM_STR);
            $queryDetalle->execute();
        }

        $query = $pdo->prepare("UPDATE reportes SET estado = 'EN ESPERA DE RECURSOS' WHERE id_reporte = :id_reporte");
        $query->execute([':id_reporte' => $id_reporte]);
        $pdo->commit();

        return [
            'id_requisicion' => $id_requisicion,
            'id_reporte' => $id_reporte,
            'estado' => 'EN ESPERA',
            'tipo_financiamiento' => 'FONE',
        ];
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $error;
    }
}

function validar_entero_positivo($valor, $campo) {
    $entero = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($entero === false) throw new InvalidArgumentException("El campo {$campo} debe ser un entero positivo");
    return $entero;
}

function validar_texto_obligatorio($valor, $campo) {
    if (!is_string($valor) || trim($valor) === '') throw new InvalidArgumentException("El campo {$campo} es obligatorio");
    return trim($valor);
}

function validar_fecha($valor, $campo) {
    if (!is_string($valor)) throw new InvalidArgumentException("El campo {$campo} es obligatorio");
    $texto = trim($valor);
    $fecha = DateTime::createFromFormat('!Y-m-d', $texto);
    if (!$fecha || $fecha->format('Y-m-d') !== $texto) throw new InvalidArgumentException("El campo {$campo} debe tener el formato AAAA-MM-DD");
    return $fecha->format('Y-m-d');
}

function validar_fecha_opcional($valor, $campo) {
    return $valor === null || $valor === '' ? null : validar_fecha($valor, $campo);
}

function validar_opcion($valor, $opciones, $campo) {
    if (!is_string($valor) || !in_array($valor, $opciones, true)) {
        throw new InvalidArgumentException("El campo {$campo} no es válido");
    }
    return $valor;
}

function validar_codigo_central($valor) {
    if (!is_string($valor)) {
        throw new InvalidArgumentException('El código contable central es obligatorio');
    }
    $codigo = trim($valor);
    if ($codigo === '') {
        throw new InvalidArgumentException('El código contable central es obligatorio');
    }
    if (strlen($codigo) > 14) {
        throw new InvalidArgumentException('El código contable central es demasiado largo');
    }
    return $codigo;
}

function validar_personal_requisicion($pdo, $id_personal, $rol) {
    $query = $pdo->prepare(
        "SELECT id_personal FROM personal
        WHERE id_personal = :id_personal AND activo = '1' AND puesto IN (1, 2, 3)"
    );
    $query->execute([':id_personal' => $id_personal]);
    if (!$query->fetchColumn()) throw new InvalidArgumentException("El {$rol} seleccionado no es válido");
}

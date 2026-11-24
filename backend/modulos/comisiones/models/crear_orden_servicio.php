<?php

function crear_orden_servicio($pdo, $input, int $id_personal): array {
    $id_comision = filter_var($input['id_comision'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_comision === false) {
        throw new InvalidArgumentException('El id_comision debe ser un entero positivo');
    }

    $campos_texto = [
        'responsable_institucion', 'nombre_institucion', 'clave_institucion',
        'domicilio_institucion', 'localidad_institucion', 'municipio_institucion',
        'tel_institucion', 'falla_reportada', 'actividad_realizada', 'observaciones', 'fecha',
    ];
    foreach ($campos_texto as $campo) {
        if (!isset($input[$campo]) || !is_string($input[$campo]) || trim($input[$campo]) === '') {
            throw new InvalidArgumentException('Todos los datos obligatorios de la orden son requeridos');
        }
        $input[$campo] = trim($input[$campo]);
    }

    $n_reporte = filter_var($input['n_reporte'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($n_reporte === false) {
        throw new InvalidArgumentException('El número de reporte no es válido');
    }

    $numericos_opcionales = [
        'cpu', 'proyectores', 'impresoras', 'alumnos_benefi', 'donacion',
    ];
    foreach ($numericos_opcionales as $campo) {
        $valor = $input[$campo] ?? null;
        if ($valor === null || $valor === '') {
            $input[$campo] = null;
            continue;
        }
        $validado = filter_var($valor, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($validado === false) {
            throw new InvalidArgumentException('Los valores numéricos de la orden no son válidos');
        }
        $input[$campo] = $validado;
    }

    $fecha_validada = DateTime::createFromFormat('!Y-m-d', $input['fecha']);
    if (!$fecha_validada || $fecha_validada->format('Y-m-d') !== $input['fecha']) {
        throw new InvalidArgumentException('La fecha debe tener el formato AAAA-MM-DD');
    }
    if (strlen($input['clave_institucion']) > 10) {
        throw new InvalidArgumentException('La clave de la institución no puede exceder 10 caracteres');
    }
    if (!preg_match('/^[0-9]{10}$/', $input['tel_institucion'])) {
        throw new InvalidArgumentException('El teléfono debe contener exactamente 10 dígitos');
    }

    $pdo->beginTransaction();
    try {
        $consulta = $pdo->prepare(
            'SELECT c.id_solicitud, c.tipo_destino, c.estado, c.id_area
             FROM comisiones c
             WHERE c.id_comision = :id_comision
             FOR UPDATE'
        );
        $consulta->execute(['id_comision' => $id_comision]);
        $comision = $consulta->fetch(PDO::FETCH_ASSOC);
        if (!$comision) {
            throw new OutOfBoundsException('Comisión no encontrada');
        }
        if (strtoupper($comision['estado']) !== 'EN CURSO') {
            throw new DomainException('La comisión no está en curso');
        }
        if (strtoupper(trim($comision['tipo_destino'])) !== 'CENTRO ESCOLAR') {
            throw new DomainException('Esta comisión no corresponde a un centro escolar');
        }

        $consulta_usuario = $pdo->prepare(
            "SELECT puesto, area FROM personal
             WHERE id_personal = :id_personal AND activo = '1'"
        );
        $consulta_usuario->execute(['id_personal' => $id_personal]);
        $usuario = $consulta_usuario->fetch(PDO::FETCH_ASSOC);
        if (!$usuario) {
            throw new DomainException('El usuario no está activo');
        }

        $consulta_participante = $pdo->prepare(
            'SELECT 1 FROM personal_comisionado
             WHERE id_comision = :id_comision AND id_personal = :id_personal'
        );
        $consulta_participante->execute(['id_comision' => $id_comision, 'id_personal' => $id_personal]);
        $es_participante = (bool) $consulta_participante->fetchColumn();
        $es_jefe = (int) $usuario['puesto'] === 3 && (int) $usuario['area'] === (int) $comision['id_area'];
        if (!$es_participante && !$es_jefe) {
            throw new DomainException('No tienes permisos para registrar esta orden de servicio');
        }

        $existente = $pdo->prepare('SELECT 1 FROM orden_servicio WHERE id_comision = :id_comision');
        $existente->execute(['id_comision' => $id_comision]);
        if ($existente->fetchColumn()) {
            throw new DomainException('La comisión ya tiene una orden de servicio registrada');
        }

        $insertar = $pdo->prepare(
            'INSERT INTO orden_servicio
             (fecha, n_reporte, responsable_institucion, nombre_institucion, clave_institucion,
              domicilio_institucion, localidad_institucion, municipio_institucion, tel_institucion,
              falla_reportada, actividad_realizada, observaciones, id_comision, cpu, proyectores,
              impresoras, alumnos_benefi, donacion)
             VALUES
             (:fecha, :n_reporte, :responsable_institucion, :nombre_institucion, :clave_institucion,
              :domicilio_institucion, :localidad_institucion, :municipio_institucion, :tel_institucion,
              :falla_reportada, :actividad_realizada, :observaciones, :id_comision, :cpu, :proyectores,
              :impresoras, :alumnos_benefi, :donacion)'
        );
        $insertar->execute([
            'fecha' => $input['fecha'],
            'n_reporte' => $n_reporte,
            'responsable_institucion' => $input['responsable_institucion'],
            'nombre_institucion' => $input['nombre_institucion'],
            'clave_institucion' => $input['clave_institucion'],
            'domicilio_institucion' => $input['domicilio_institucion'],
            'localidad_institucion' => $input['localidad_institucion'],
            'municipio_institucion' => $input['municipio_institucion'],
            'tel_institucion' => $input['tel_institucion'],
            'falla_reportada' => $input['falla_reportada'],
            'actividad_realizada' => $input['actividad_realizada'],
            'observaciones' => $input['observaciones'],
            'id_comision' => $id_comision,
            'cpu' => $input['cpu'],
            'proyectores' => $input['proyectores'],
            'impresoras' => $input['impresoras'],
            'alumnos_benefi' => $input['alumnos_benefi'],
            'donacion' => $input['donacion'],
        ]);

        $resultado = $pdo->prepare('SELECT * FROM orden_servicio WHERE id_comision = :id_comision');
        $resultado->execute(['id_comision' => $id_comision]);
        $orden = $resultado->fetch(PDO::FETCH_ASSOC);
        $pdo->commit();
        return $orden;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

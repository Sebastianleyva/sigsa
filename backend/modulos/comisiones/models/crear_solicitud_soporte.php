<?php

function crear_solicitud_soporte($pdo, $input, int $id_personal): array {
    $id_comision = filter_var($input['id_comision'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_comision === false) {
        throw new InvalidArgumentException('El id_comision debe ser un entero positivo');
    }

    $campos_texto = ['direccion', 'departamento', 'nombre_reporta', 'cisco', 'fecha', 'hora', 'solicitud_falla'];
    foreach ($campos_texto as $campo) {
        if (!isset($input[$campo]) || !is_string($input[$campo]) || trim($input[$campo]) === '') {
            throw new InvalidArgumentException('Todos los datos de la solicitud de soporte son obligatorios');
        }
        $input[$campo] = trim($input[$campo]);
    }

    $tecnico = filter_var($input['tecnico_asignado'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $equipos = filter_var($input['equipos_atendidos'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    $usuarios = filter_var($input['usuarios_beneficiados'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    if ($tecnico === false || $equipos === false || $usuarios === false) {
        throw new InvalidArgumentException('Técnico, equipos y usuarios beneficiados no son válidos');
    }

    $fecha_validada = DateTime::createFromFormat('!Y-m-d', $input['fecha']);
    if (!$fecha_validada || $fecha_validada->format('Y-m-d') !== $input['fecha']) {
        throw new InvalidArgumentException('La fecha debe tener el formato AAAA-MM-DD');
    }

    $hora_validada = DateTime::createFromFormat('!H:i', $input['hora']);
    if (!$hora_validada || $hora_validada->format('H:i') !== $input['hora']) {
        throw new InvalidArgumentException('La hora debe tener el formato HH:MM');
    }

    $pdo->beginTransaction();
    try {
        $consulta = $pdo->prepare(
            'SELECT c.id_solicitud, c.tipo_destino, c.estado, c.id_area, a.id_subdireccion
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
            throw new DomainException('La comisión no está en curso');
        }
        if (strtoupper(trim($comision['tipo_destino'])) !== 'CENTRO ADMINISTRATIVO') {
            throw new DomainException('Esta comisión no corresponde a un centro administrativo');
        }

        $consulta_usuario = $pdo->prepare(
            "SELECT p.puesto, p.area
             FROM personal p
             WHERE p.id_personal = :id_personal AND p.activo = '1'"
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
            throw new DomainException('No tienes permisos para registrar esta solicitud de soporte');
        }

        $consulta_tecnico = $pdo->prepare(
            'SELECT id_personal FROM personal_comisionado
             WHERE id_comision = :id_comision AND id_personal = :tecnico'
        );
        $consulta_tecnico->execute(['id_comision' => $id_comision, 'tecnico' => $tecnico]);
        if (!$consulta_tecnico->fetchColumn()) {
            throw new InvalidArgumentException('El técnico asignado debe participar en la comisión');
        }

        $consulta_existente = $pdo->prepare(
            'SELECT 1 FROM solicitud_soporte WHERE id_comision = :id_comision'
        );
        $consulta_existente->execute(['id_comision' => $id_comision]);
        if ($consulta_existente->fetchColumn()) {
            throw new DomainException('La comisión ya tiene una solicitud de soporte registrada');
        }

        $insertar = $pdo->prepare(
            'INSERT INTO solicitud_soporte
             (direccion, departamento, nombre_reporta, cisco, fecha, hora,
              tecnico_asignado, solicitud_falla, equipos_atendidos, usuarios_beneficiados, estado, id_comision)
             VALUES
             (:direccion, :departamento, :nombre_reporta, :cisco, :fecha, :hora,
              :tecnico_asignado, :solicitud_falla, :equipos_atendidos, :usuarios_beneficiados, :estado, :id_comision)'
        );
        $insertar->execute([
            'direccion' => $input['direccion'],
            'departamento' => $input['departamento'],
            'nombre_reporta' => $input['nombre_reporta'],
            'cisco' => $input['cisco'],
            'fecha' => $input['fecha'],
            'hora' => $input['hora'],
            'tecnico_asignado' => $tecnico,
            'solicitud_falla' => $input['solicitud_falla'],
            'equipos_atendidos' => $equipos,
            'usuarios_beneficiados' => $usuarios,
            'estado' => 'FINALIZADO',
            'id_comision' => $id_comision,
        ]);

        $actualizar_comision = $pdo->prepare(
            "UPDATE comisiones SET estado = 'FINALIZADA' WHERE id_comision = :id_comision"
        );
        $actualizar_comision->execute(['id_comision' => $id_comision]);
        if ($actualizar_comision->rowCount() !== 1) {
            throw new RuntimeException('No fue posible finalizar la comisión');
        }

        $resultado = $pdo->prepare(
            'SELECT * FROM solicitud_soporte WHERE id_comision = :id_comision'
        );
        $resultado->execute(['id_comision' => $id_comision]);
        $solicitud = $resultado->fetch(PDO::FETCH_ASSOC);
        $pdo->commit();
        return $solicitud;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        throw $e;
    }
}

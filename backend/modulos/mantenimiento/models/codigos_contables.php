<?php

function listar_codigos_contables($pdo) {
    $query = $pdo->query(
        "SELECT id_codigo, codigo_contable, descripcion, activo
        FROM codigos_contables
        ORDER BY activo DESC, codigo_contable ASC"
    );

    return array_map(static function ($codigo) {
        return [
            'id_codigo' => (int) $codigo['id_codigo'],
            'codigo_contable' => $codigo['codigo_contable'],
            'descripcion' => $codigo['descripcion'],
            'activo' => (int) $codigo['activo'],
        ];
    }, $query->fetchAll(PDO::FETCH_ASSOC));
}

function crear_codigo_contable($pdo, $input) {
    [$codigo, $descripcion] = validar_datos_codigo_contable($input);
    $query = $pdo->prepare(
        "INSERT INTO codigos_contables (codigo_contable, descripcion, activo)
        VALUES (:codigo_contable, :descripcion, 1)"
    );
    $query->execute([
        ':codigo_contable' => $codigo,
        ':descripcion' => $descripcion,
    ]);

    return [
        'id_codigo' => (int) $pdo->lastInsertId(),
        'codigo_contable' => $codigo,
        'descripcion' => $descripcion,
        'activo' => 1,
    ];
}

function actualizar_codigo_contable($pdo, $input) {
    $id_codigo = filter_var($input['id_codigo'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_codigo === false) {
        throw new InvalidArgumentException('El id_codigo debe ser un entero positivo');
    }

    [$codigo, $descripcion] = validar_datos_codigo_contable($input);
    $query = $pdo->prepare(
        "UPDATE codigos_contables
        SET codigo_contable = :codigo_contable, descripcion = :descripcion
        WHERE id_codigo = :id_codigo"
    );
    $query->execute([
        ':codigo_contable' => $codigo,
        ':descripcion' => $descripcion,
        ':id_codigo' => $id_codigo,
    ]);
    if ($query->rowCount() === 0) {
        $query = $pdo->prepare("SELECT id_codigo FROM codigos_contables WHERE id_codigo = :id_codigo");
        $query->execute([':id_codigo' => $id_codigo]);
        if (!$query->fetchColumn()) {
            throw new OutOfBoundsException('El código contable no existe');
        }
    }

    return [
        'id_codigo' => $id_codigo,
        'codigo_contable' => $codigo,
        'descripcion' => $descripcion,
    ];
}

function desactivar_codigo_contable($pdo, $id_codigo) {
    $query = $pdo->prepare(
        "UPDATE codigos_contables SET activo = 0
        WHERE id_codigo = :id_codigo AND activo = 1"
    );
    $query->execute([':id_codigo' => $id_codigo]);
    if ($query->rowCount() === 0) {
        $query = $pdo->prepare("SELECT activo FROM codigos_contables WHERE id_codigo = :id_codigo");
        $query->execute([':id_codigo' => $id_codigo]);
        $activo = $query->fetchColumn();
        if ($activo === false) {
            throw new OutOfBoundsException('El código contable no existe');
        }
        throw new DomainException('El código contable ya está desactivado');
    }

    return ['id_codigo' => $id_codigo];
}

function validar_datos_codigo_contable($input) {
    if (!is_array($input) || !is_string($input['codigo_contable'] ?? null)) {
        throw new InvalidArgumentException('El código contable es obligatorio');
    }
    if (!is_string($input['descripcion'] ?? null)) {
        throw new InvalidArgumentException('La descripción es obligatoria');
    }

    $codigo = trim($input['codigo_contable']);
    $descripcion = trim($input['descripcion']);
    if ($codigo === '' || strlen($codigo) > 14) {
        throw new InvalidArgumentException('El código contable debe contener entre 1 y 14 caracteres');
    }
    if ($descripcion === '') {
        throw new InvalidArgumentException('La descripción es obligatoria');
    }

    return [$codigo, $descripcion];
}

<?php

function crear_incidencia($pdo, $input, $usuario_id) {
    if (!is_array($input)) {
        throw new InvalidArgumentException('La solicitud debe contener un objeto JSON válido');
    }

    foreach (['descripcion', 'urgencia', 'lugar'] as $campo) {
        if (!isset($input[$campo]) || !is_string($input[$campo])) {
            throw new InvalidArgumentException("El campo {$campo} es obligatorio y debe ser texto");
        }
    }

    $descripcion = trim($input['descripcion']);
    $urgencia = strtoupper(trim($input['urgencia']));
    $lugar = trim($input['lugar']);
    $id_personal = filter_var($usuario_id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    if ($id_personal === false) {
        throw new InvalidArgumentException('El usuario que reporta la incidencia no es válido');
    }
    if ($descripcion === '') {
        throw new InvalidArgumentException('La descripción de la incidencia no puede estar vacía');
    }
    $longitudDescripcion = preg_match_all('/./us', $descripcion, $coincidencias);
    if ($longitudDescripcion === false || $longitudDescripcion > 5000) {
        throw new InvalidArgumentException('La descripción no puede exceder 5000 caracteres');
    }
    if (!in_array($urgencia, ['BAJA', 'MEDIA', 'ALTA'], true)) {
        throw new InvalidArgumentException('La urgencia debe ser BAJA, MEDIA o ALTA');
    }
    if ($lugar === '') {
        throw new InvalidArgumentException('El lugar de la incidencia no puede estar vacío');
    }
    $longitudLugar = preg_match_all('/./us', $lugar, $coincidencias);
    if ($longitudLugar === false || $longitudLugar > 60) {
        throw new InvalidArgumentException('El lugar no puede exceder 60 caracteres');
    }

    $fecha = date('Y-m-d');
    $estado = 'PENDIENTE';
    $stm = $pdo->prepare(
        "INSERT INTO incidencias
        (id_personal, descripcion, fecha, urgencia, estado, lugar)
        VALUES
        (:id_personal, :descripcion, :fecha, :urgencia, :estado, :lugar)"
    );
    $stm->execute([
        ':id_personal' => $id_personal,
        ':descripcion' => $descripcion,
        ':fecha' => $fecha,
        ':urgencia' => $urgencia,
        ':estado' => $estado,
        ':lugar' => $lugar,
    ]);

    return [
        'id' => $pdo->lastInsertId(),
        'id_personal' => $id_personal,
        'descripcion' => $descripcion,
        'fecha' => $fecha,
        'urgencia' => $urgencia,
        'estado' => $estado,
        'lugar' => $lugar,
    ];
}
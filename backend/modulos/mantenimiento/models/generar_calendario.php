<?php

function generar_calendario($pdo,$input){
    if(!is_array($input)){
        throw new InvalidArgumentException('La entrada debe ser un JSON valido');
    }
    
    $campos = ['fecha_destinada', 'estado', 'id_servicio'];
    foreach ($campos as $campo) {
        if (!array_key_exists($campo, $input)) {
            throw new InvalidArgumentException("Falta el campo obligatorio: $campo");
        }
    }

    $fecha_destinada = $input['fecha_destinada'];
    $estado = $input['estado'];
    $id_servicio = $input['id_servicio'];

    $query = $pdo->prepare(
        "SELECT id_servicio
        FROM servicios
        WHERE id_servicio = :id_servicio
        AND activo = 1"
    );
    $query->execute(['id_servicio' => $id_servicio]);
    if(!$query->fetchColumn()){
        throw new OutOfBoundsException('Servicio no encontrado');
    }

    $stm = $pdo->prepare(
        "INSERT INTO calendario
        (fecha_destinada, estado, id_servicio)
        VALUES
        (:fecha_destinada, :estado, :id_servicio)"
    );
    $stm->execute([
        'fecha_destinada' => $fecha_destinada,
        'estado' => $estado,
        'id_servicio' => $id_servicio
    ]);

    return [
        'id_calendario' => $pdo->lastInsertId(),
        'id_servicio' => $id_servicio,
        'fecha_destinada' => $fecha_destinada,
        'estado' => $estado
    ];
}

// function generar_calendario($pdo, $input){
//     if (!is_array($input)) {
//         throw new InvalidArgumentException('La entrada debe ser un objeto JSON válido');
//     }

//     $campos = ['fecha_destinada', 'estado', 'id_servicio'];
//     foreach ($campos as $campo) {
//         if (!array_key_exists($campo, $input)) {
//             throw new InvalidArgumentException("Falta el campo obligatorio: $campo");
//         }
//     }

//     $fecha_destinada = $input['fecha_destinada'];
//     $estado = $input['estado'];
//     $id_servicio = $input['id_servicio'];

//     if (!is_string($fecha_destinada)) {
//         throw new InvalidArgumentException('La fecha destinada debe tener formato YYYY-MM-DD');
//     }

//     $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $fecha_destinada);
//     $errores_fecha = DateTimeImmutable::getLastErrors();
//     if (
//         $fecha === false
//         || ($errores_fecha !== false && ($errores_fecha['warning_count'] > 0 || $errores_fecha['error_count'] > 0))
//         || $fecha->format('Y-m-d') !== $fecha_destinada
//     ) {
//         throw new InvalidArgumentException('La fecha destinada debe tener formato YYYY-MM-DD y ser válida');
//     }

//     if (!is_string($estado) || preg_match('/\A.{1,20}\z/us', $estado) !== 1 || trim($estado) === '') {
//         throw new InvalidArgumentException('El estado debe contener entre 1 y 20 caracteres');
//     }

//     if (
//         (!is_int($id_servicio) && !is_string($id_servicio))
//         || preg_match('/\A[1-9][0-9]*\z/D', (string) $id_servicio) !== 1
//     ) {
//         throw new InvalidArgumentException('El id_servicio debe ser un entero positivo');
//     }

//     $query = $pdo->prepare(
//         'SELECT id_servicio
//         FROM servicios
//         WHERE id_servicio = :id_servicio'
//     );
//     $query->execute(['id_servicio' => $id_servicio]);

//     if ($query->fetchColumn() === false) {
//         throw new OutOfBoundsException('Servicio no encontrado');
//     }

//     $stm = $pdo->prepare(
//         'INSERT INTO calendario
//         (fecha_destinada, estado, id_servicio)
//         VALUES
//         (:fecha_destinada, :estado, :id_servicio)'
//     );
//     $stm->execute([
//         'fecha_destinada' => $fecha_destinada,
//         'estado' => $estado,
//         'id_servicio' => $id_servicio
//     ]);

//     return [
//         'id_calendario' => $pdo->lastInsertId(),
//         'id_servicio' => $id_servicio,
//         'fecha_destinada' => $fecha_destinada,
//         'estado' => $estado
//     ];
// }
<?php

function asignar_comision($pdo, $input){
    $id_comision = $input['id_comision'];
    $id_personal = $input['id_personal'];

    $query = $pdo -> prepare(
        'INSERT INTO personal_comisionado
        (id_comision, id_personal)
        VALUES (:id_comision, :id_personal)
        ');
    $query -> execute([
        ':id_comision' => $id_comision,
        ':id_personal' => $id_personal
    ]);

    $consulta_npersonal = $pdo -> prepare(
        'SELECT * FROM personal_comisionado 
        WHERE id_comision = :id_comision'
    );
    $consulta_npersonal -> execute([
        ':id_comision' => $id_comision
    ]);

    $personal_comisionado = $consulta_npersonal -> fetchAll(PDO::FETCH_ASSOC);
    return [
        'id_personalcomisionado' => $pdo->lastInsertId(),
        'id_comision' => $id_comision,
        'id_personal' => $id_personal,
        'personal_comisionado' => count($personal_comisionado)
    ];
}
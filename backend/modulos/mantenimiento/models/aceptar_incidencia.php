<?php

function aceptar_incidencia($pdo, $input, $id_personal){

    $estado = 'ACEPTADA';
    $id_incidencia =filter_var($input['id_incidencia'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);


    try{
        
        $query = $pdo->prepare(
            "SELECT * FROM incidencias 
            WHERE id_incidencia = :id_incidencia AND id_personal = :id_personal"
        );
        $query->execute([
            ':id_incidencia' => $id_incidencia,
            ':id_personal' => $id_personal
        ]);
        $incidencia = $query->fetch(PDO::FETCH_ASSOC);

        if (!$incidencia) {
            http_response_code(404);
            echo json_encode(["ERROR" => "Incidencia no encontrada o no pertenece al usuario"]);
            exit;
        }

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["ERROR" => "Error al verificar la incidencia: " . $e->getMessage()]);
        exit;
    }

    try{$query = $pdo ->prepare(
        "UPDATE incidencias 
        SET estado = :estado,
        id_autoriza = :id_personal
        WHERE id_incidencia = :id_incidencia"); 
    $query->execute([
        ':estado' => $estado,
        ':id_incidencia' => $id_incidencia,
        ':id_personal' => $id_personal
    ]);} catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["ERROR" => "Error al aceptar la incidencia: " . $e->getMessage()]);
        exit;
    }


}
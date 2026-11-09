<?php

function ver_incidencias($pdo) {
    $estado = 'PENDIENTE';
    try {
        $query = $pdo->prepare(
            "SELECT * FROM incidencias 
            WHERE estado = :estado"
        );
        $query->execute([
            ':estado' => $estado
        ]);
        $incidencias = $query->fetchAll(PDO::FETCH_ASSOC);

        return $incidencias;

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["ERROR" => "Error al obtener las incidencias: " . $e->getMessage()]);
        exit;
    }
}
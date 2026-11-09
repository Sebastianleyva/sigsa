<?php

function eliminar_servicio($pdo, $id_servicio){

    try {
        $stm = $pdo->prepare(
            "UPDATE servicios 
            SET activo = 0
            WHERE 
            id_servicio = :id_servicio");

        $stm->execute([':id_servicio' => $id_servicio]);

        if ($stm->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(["ERROR" => "Servicio no encontrado"]);
            exit;
        }

        return [
            "mensaje" => "Servicio eliminado exitosamente", 
            "id_servicio" => $id_servicio];
            
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["ERROR" => "Error al eliminar el servicio: " . $e->getMessage()]);
        exit;
    }
}
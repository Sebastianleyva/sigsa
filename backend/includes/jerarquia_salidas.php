<?php

function obtener_salida_para_autorizacion(PDO $pdo, int $id_salida): array
{
    $consulta = $pdo->prepare(
        'SELECT s.id_salida, s.estado, s.id_autoriza, s.solicita_respaldo,
                p.id_personal AS id_solicitante, p.id_jefe, p.puesto AS puesto_solicitante,
                p.area AS area_solicitante, a.id_subdireccion
         FROM salidas s
         JOIN personal p ON p.id_personal = s.id_personal
         LEFT JOIN areas a ON a.id_area = p.area
         WHERE s.id_salida = :id_salida'
    );
    $consulta->execute(['id_salida' => $id_salida]);
    $salida = $consulta->fetch();

    if (!$salida) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(404);
        echo json_encode(["ERROR" => "Solicitud inexistente"]);
        exit;
    }

    return $salida;
}

function exigir_autorizacion_salida(
    PDO $pdo,
    array $salida,
    int $id_autorizante,
    bool $permitir_autofinalizar = false
): void
{
    $consulta = $pdo->prepare(
        "SELECT id_personal, puesto, area, activo
         FROM personal
         WHERE id_personal = :id_personal"
    );
    $consulta->execute(['id_personal' => $id_autorizante]);
    $autorizante = $consulta->fetch();

    if (!$autorizante || $autorizante['activo'] !== '1') {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(403);
        echo json_encode(["ERROR" => "El usuario no está activo para autorizar salidas"]);
        exit;
    }

    $es_jefe_directo = (int) $salida['id_jefe'] === $id_autorizante;
    $es_autofinalizacion = $permitir_autofinalizar
        && (int) $salida['id_solicitante'] === $id_autorizante
        && in_array((int) $salida['puesto_solicitante'], [1, 2], true)
        && $salida['id_autoriza'] === null;
    $es_subdirector_respaldo = (int) $salida['solicita_respaldo'] === 1
        && (int) $salida['puesto_solicitante'] === 4
        && (int) $autorizante['puesto'] === 2
        && $salida['id_subdireccion'] !== null
        && (int) $autorizante['area'] === (int) $salida['id_subdireccion'];

    if (!$es_jefe_directo && !$es_subdirector_respaldo && !$es_autofinalizacion) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(403);
        echo json_encode(["ERROR" => "No tienes autorización jerárquica para esta salida"]);
        exit;
    }
}

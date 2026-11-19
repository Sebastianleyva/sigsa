<?php

header('Content-Type: application/json');

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';
require_once '../models/codigos_contables.php';

try {
    validar_jwt_requerido();
    validar_csrf_requerido();
    requerir_rol([1, 2, 3]);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode(['data' => listar_codigos_contables($pdo)]);
        exit;
    }
    if (!in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PATCH', 'DELETE'], true)) {
        http_response_code(405);
        echo json_encode(['ERROR' => 'Método no permitido']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    if (!is_array($input)) {
        throw new InvalidArgumentException('El cuerpo debe ser un objeto JSON válido');
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $codigo = crear_codigo_contable($pdo, $input);
        http_response_code(201);
        echo json_encode(['codigo_contable' => $codigo]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
        $codigo = actualizar_codigo_contable($pdo, $input);
        echo json_encode(['codigo_contable' => $codigo]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $id_codigo = filter_var($input['id_codigo'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($id_codigo === false) {
            throw new InvalidArgumentException('El id_codigo debe ser un entero positivo');
        }
        echo json_encode(['id_codigo' => desactivar_codigo_contable($pdo, $id_codigo)['id_codigo']]);
        exit;
    }

} catch (InvalidArgumentException $error) {
    http_response_code(400);
    echo json_encode(['ERROR' => $error->getMessage()]);
} catch (OutOfBoundsException $error) {
    http_response_code(404);
    echo json_encode(['ERROR' => $error->getMessage()]);
} catch (DomainException $error) {
    http_response_code(409);
    echo json_encode(['ERROR' => $error->getMessage()]);
} catch (PDOException $error) {
    error_log('Error al administrar códigos contables: ' . $error->getMessage());
    http_response_code($error->getCode() === '23000' ? 409 : 500);
    echo json_encode([
        'ERROR' => $error->getCode() === '23000'
            ? 'El código contable ya está registrado'
            : 'No fue posible administrar los códigos contables',
    ]);
} catch (Throwable $error) {
    error_log('Error inesperado al administrar códigos contables: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['ERROR' => 'Error interno al administrar los códigos contables']);
}

<?php

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Método no permitido');
}

try {
    $usuario = validar_jwt_requerido();
    validar_csrf_requerido();

    $id_personal = filter_var($usuario['sub'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_personal === false) {
        http_response_code(401);
        exit('Los datos de identidad no son válidos');
    }

    $id_comision = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_comision === false || $id_comision === null) {
        http_response_code(400);
        exit('Solicitud de soporte inválida');
    }

    $consulta = $pdo->prepare(
        'SELECT ss.*, c.tipo_destino, c.id_area,
                p.nombre AS tecnico_nombre, p.apellido_p AS tecnico_apellido_p,
                p.apellido_m AS tecnico_apellido_m
         FROM solicitud_soporte ss
         INNER JOIN comisiones c ON c.id_comision = ss.id_comision
         INNER JOIN personal p ON p.id_personal = ss.tecnico_asignado
         WHERE ss.id_comision = :id_comision'
    );
    $consulta->execute(['id_comision' => $id_comision]);
    $solicitud = $consulta->fetch(PDO::FETCH_ASSOC);
    if (!$solicitud || strtoupper(trim($solicitud['tipo_destino'])) !== 'CENTRO ADMINISTRATIVO') {
        http_response_code(404);
        exit('Solicitud de soporte no encontrada');
    }

    $consulta_usuario = $pdo->prepare(
        "SELECT puesto, area FROM personal
         WHERE id_personal = :id_personal AND activo = '1'"
    );
    $consulta_usuario->execute(['id_personal' => $id_personal]);
    $usuario_activo = $consulta_usuario->fetch(PDO::FETCH_ASSOC);
    if (!$usuario_activo) {
        http_response_code(403);
        exit('No tienes permisos para consultar esta solicitud de soporte');
    }

    $consulta_participante = $pdo->prepare(
        'SELECT 1 FROM personal_comisionado
         WHERE id_comision = :id_comision AND id_personal = :id_personal'
    );
    $consulta_participante->execute([
        'id_comision' => $id_comision,
        'id_personal' => $id_personal,
    ]);
    $es_participante = (bool) $consulta_participante->fetchColumn();
    $es_jefe = (int) $usuario_activo['puesto'] === 3
        && (int) $usuario_activo['area'] === (int) $solicitud['id_area'];
    if (!$es_participante && !$es_jefe) {
        http_response_code(403);
        exit('No tienes permisos para consultar esta solicitud de soporte');
    }

    $escape = static fn ($valor): string => htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
    $campoTexto = static function (string $campo) use ($solicitud, $escape): string {
        $valor = trim((string) ($solicitud[$campo] ?? ''));
        return $valor === '' ? '' : nl2br($escape($valor), false);
    };

    $fecha = DateTime::createFromFormat('!Y-m-d', (string) $solicitud['fecha']);
    $fechaTexto = $fecha ? $fecha->format('d/m/Y') : (string) $solicitud['fecha'];
    $hora = DateTime::createFromFormat('!H:i:s', (string) $solicitud['hora']);
    $horaTexto = $hora ? $hora->format('H:i') : (string) $solicitud['hora'];
    $tecnico = trim(
        $solicitud['tecnico_nombre'] . ' '
        . $solicitud['tecnico_apellido_p'] . ' '
        . $solicitud['tecnico_apellido_m']
    );

    $directorio_plantilla = __DIR__ . '/../generador_pdf/';
    $plantilla = file_get_contents($directorio_plantilla . 'solicitud_soporte.html');
    $estilos = file_get_contents($directorio_plantilla . 'solicitud_soporte.css');
    if ($plantilla === false || $estilos === false) {
        throw new RuntimeException('No se pudo cargar la plantilla de la solicitud de soporte');
    }

    // strtr reemplaza en una sola pasada: los valores insertados no se vuelven a interpretar
    $html = strtr($plantilla, [
        '{{estilos}}' => $estilos,
        '{{id_solicitud_soporte}}' => $escape($solicitud['id_solicitud_soporte']),
        '{{fecha}}' => $escape($fechaTexto),
        '{{hora}}' => $escape($horaTexto),
        '{{departamento}}' => $campoTexto('departamento'),
        '{{cisco}}' => $campoTexto('cisco'),
        '{{direccion}}' => $campoTexto('direccion'),
        '{{nombre_reporta}}' => $campoTexto('nombre_reporta'),
        '{{tecnico}}' => $escape($tecnico),
        '{{solicitud_falla}}' => $campoTexto('solicitud_falla'),
        '{{equipos_atendidos}}' => $escape($solicitud['equipos_atendidos']),
        '{{usuarios_beneficiados}}' => $escape($solicitud['usuarios_beneficiados']),
    ]);

    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Error interno del servidor');
}
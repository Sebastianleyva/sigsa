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

    $id_orden = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_orden === false || $id_orden === null) {
        http_response_code(400);
        exit('Orden de servicio inválida');
    }

    $consulta = $pdo->prepare(
        'SELECT os.*, c.tipo_destino, c.id_area
         FROM orden_servicio os
         INNER JOIN comisiones c ON c.id_comision = os.id_comision
         WHERE os.id_orden = :id_orden'
    );
    $consulta->execute(['id_orden' => $id_orden]);
    $orden = $consulta->fetch(PDO::FETCH_ASSOC);
    if (!$orden) {
        http_response_code(404);
        exit('Orden de servicio no encontrada');
    }
    if (strtoupper(trim($orden['tipo_destino'])) !== 'CENTRO ESCOLAR') {
        http_response_code(404);
        exit('Orden de servicio no encontrada');
    }

    $consulta_usuario = $pdo->prepare(
        "SELECT puesto, area FROM personal
         WHERE id_personal = :id_personal AND activo = '1'"
    );
    $consulta_usuario->execute(['id_personal' => $id_personal]);
    $usuario_activo = $consulta_usuario->fetch(PDO::FETCH_ASSOC);
    if (!$usuario_activo) {
        http_response_code(403);
        exit('No tienes permisos para consultar esta orden de servicio');
    }

    $consulta_participante = $pdo->prepare(
        'SELECT 1 FROM personal_comisionado
         WHERE id_comision = :id_comision AND id_personal = :id_personal'
    );
    $consulta_participante->execute([
        'id_comision' => $orden['id_comision'],
        'id_personal' => $id_personal,
    ]);
    $es_participante = (bool) $consulta_participante->fetchColumn();
    $es_jefe = (int) $usuario_activo['puesto'] === 3
        && (int) $usuario_activo['area'] === (int) $orden['id_area'];
    if (!$es_participante && !$es_jefe) {
        http_response_code(403);
        exit('No tienes permisos para consultar esta orden de servicio');
    }

    $escape = static fn ($valor): string => htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
    $campoTexto = static function (string $campo) use ($orden, $escape): string {
        $valor = trim((string) ($orden[$campo] ?? ''));
        return $valor === '' ? '' : nl2br($escape($valor), false);
    };
    $cantidad = static function (string $campo) use ($orden, $escape): string {
        return ($orden[$campo] === null || $orden[$campo] === '') ? '' : $escape($orden[$campo]);
    };

    $fecha = DateTime::createFromFormat('!Y-m-d', (string) $orden['fecha']);
    $fecha_texto = $fecha ? $fecha->format('d/m/Y') : (string) $orden['fecha'];

    $directorio_plantilla = __DIR__ . '/../generador_pdf/';
    $plantilla = file_get_contents($directorio_plantilla . 'orden_servicio.html');
    $estilos = file_get_contents($directorio_plantilla . 'orden_servicio.css');
    if ($plantilla === false || $estilos === false) {
        throw new RuntimeException('No se pudo cargar la plantilla de la orden de servicio');
    }

    // strtr reemplaza en una sola pasada: los valores insertados no se vuelven a interpretar
    $html = strtr($plantilla, [
        '{{estilos}}' => $estilos,
        '{{n_reporte}}' => $escape($orden['n_reporte']),
        '{{fecha}}' => $escape($fecha_texto),
        '{{responsable_institucion}}' => $campoTexto('responsable_institucion'),
        '{{nombre_institucion}}' => $campoTexto('nombre_institucion'),
        '{{clave_institucion}}' => $campoTexto('clave_institucion'),
        '{{domicilio_institucion}}' => $campoTexto('domicilio_institucion'),
        '{{localidad_institucion}}' => $campoTexto('localidad_institucion'),
        '{{municipio_institucion}}' => $campoTexto('municipio_institucion'),
        '{{tel_institucion}}' => $campoTexto('tel_institucion'),
        '{{falla_reportada}}' => $campoTexto('falla_reportada'),
        '{{actividad_realizada}}' => $campoTexto('actividad_realizada'),
        '{{observaciones}}' => $campoTexto('observaciones'),
        '{{cpu}}' => $cantidad('cpu'),
        '{{proyectores}}' => $cantidad('proyectores'),
        '{{impresoras}}' => $cantidad('impresoras'),
        '{{alumnos_benefi}}' => $cantidad('alumnos_benefi'),
        '{{donacion}}' => $cantidad('donacion'),
    ]);

    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No fue posible generar la orden de servicio');
}
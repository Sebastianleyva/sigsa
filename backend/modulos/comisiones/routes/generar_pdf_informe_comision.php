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

    $id_informe = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_informe === false || $id_informe === null) {
        http_response_code(400);
        exit('Informe de comisión inválido');
    }

    $consulta_usuario = $pdo->prepare(
        "SELECT puesto, area
         FROM personal
         WHERE id_personal = :id_personal AND activo = '1'"
    );
    $consulta_usuario->execute(['id_personal' => $id_personal]);
    $usuario_actual = $consulta_usuario->fetch(PDO::FETCH_ASSOC);
    if (!$usuario_actual) {
        http_response_code(403);
        exit('El usuario no está activo');
    }

    $consulta = $pdo->prepare(
        "SELECT ic.id_informe_comision, ic.id_comision, ic.id_personal,
                ic.objetivo_comision, ic.actividad_desarrollada, ic.resultados,
                ic.contribucion, ic.conclusion,
                c.tipo_comision, c.fecha_inicio, c.fecha_final,
                s.ubicacion AS lugar, s.fecha_comision,
                CONCAT_WS(' ', p.nombre, p.apellido_p, p.apellido_m) AS comisionado,
                p.rfc, a.nombre AS area_nombre,
                (SELECT sv.programa
                 FROM solicitudes_de_viaticos sv
                 WHERE sv.id_comision = ic.id_comision
                   AND sv.id_personal_comisionado = ic.id_personal
                 ORDER BY sv.id_solicitud_viaticos DESC
                 LIMIT 1) AS programa
         FROM informe_comision ic
         INNER JOIN comisiones c ON c.id_comision = ic.id_comision
         INNER JOIN solicitudes s ON s.id_solicitud = c.id_solicitud
         INNER JOIN personal p ON p.id_personal = ic.id_personal
         LEFT JOIN areas a ON a.id_area = p.area
         WHERE ic.id_informe_comision = :id_informe"
    );
    $consulta->execute(['id_informe' => $id_informe]);
    $informe = $consulta->fetch(PDO::FETCH_ASSOC);
    if (!$informe) {
        http_response_code(404);
        exit('Informe de comisión no encontrado');
    }

    $area_usuario = filter_var($usuario_actual['area'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $es_autor = (int) $informe['id_personal'] === $id_personal;
    $es_administrativo = in_array((int) $usuario_actual['puesto'], [1, 2], true) || $area_usuario === 6;
    if (!$es_autor && !$es_administrativo) {
        http_response_code(403);
        exit('No tienes permisos para consultar este informe de comisión');
    }

    $escape = static fn ($valor): string => htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8',
    );
    $texto = static fn ($valor): string => nl2br($escape($valor), false);
    $fecha = static function ($valor): string {
        if (!$valor) return '';
        $fecha = DateTime::createFromFormat('!Y-m-d', (string) $valor);
        return $fecha ? $fecha->format('d/m/Y') : (string) $valor;
    };

    if (!empty($informe['fecha_inicio']) && !empty($informe['fecha_final'])
        && $informe['fecha_inicio'] !== $informe['fecha_final']) {
        $periodo = 'del ' . $fecha($informe['fecha_inicio']) . ' al ' . $fecha($informe['fecha_final']);
    } else {
        $periodo = $fecha($informe['fecha_inicio'] ?: ($informe['fecha_final'] ?: $informe['fecha_comision']));
    }
    $lugar_periodo = implode(', ', array_filter([
        trim((string) $informe['lugar']),
        $periodo,
    ], static fn (string $valor): bool => $valor !== ''));

    $ruta_logo = __DIR__ . '/../plantillas_pdf/logo_informe_comision.png';
    $logo = is_readable($ruta_logo)
        ? 'data:image/png;base64,' . base64_encode((string) file_get_contents($ruta_logo))
        : '';

    // El logo es opcional: solo se muestra si el archivo existe
    $logo_html = $logo !== ''
        ? '<img src="' . $logo . '" alt="Secretaría de Educación del Estado de Durango">'
        : '';

    $directorio_plantilla = __DIR__ . '/../generador_pdf/';
    $plantilla = file_get_contents($directorio_plantilla . 'informe_comision.html');
    $estilos = file_get_contents($directorio_plantilla . 'informe_comision.css');
    if ($plantilla === false || $estilos === false) {
        throw new RuntimeException('No se pudo cargar la plantilla de informe de comisión');
    }

    // strtr reemplaza en una sola pasada: los valores insertados no se vuelven a interpretar
    $html = strtr($plantilla, [
        '{{estilos}}' => $estilos,
        '{{logo}}' => $logo_html,
        '{{id_informe_comision}}' => $escape($informe['id_informe_comision']),
        '{{lugar_periodo}}' => $escape($lugar_periodo),
        '{{comisionado}}' => $escape($informe['comisionado']),
        '{{rfc}}' => $escape($informe['rfc']),
        '{{area_nombre}}' => $escape($informe['area_nombre']),
        '{{programa}}' => $escape($informe['programa']),
        '{{objetivo_comision}}' => $texto($informe['objetivo_comision']),
        '{{actividad_desarrollada}}' => $texto($informe['actividad_desarrollada']),
        '{{resultados}}' => $texto($informe['resultados']),
        '{{contribucion}}' => $texto($informe['contribucion']),
        '{{conclusion}}' => $texto($informe['conclusion']),
    ]);

    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Error interno del servidor');
}

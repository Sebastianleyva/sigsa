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
    requerir_permiso_mantenimiento($pdo, $usuario);

    $id_requisicion = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_requisicion === false || $id_requisicion === null) {
        http_response_code(400);
        exit('Requisición inválida');
    }

    $query = $pdo->prepare(
        "SELECT q.*, r.id_reporte,
            CONCAT_WS(' ', ps.nombre, ps.apellido_p, ps.apellido_m) AS solicitante,
            pps.nombre AS puesto_solicitante,
            CONCAT_WS(' ', pa.nombre, pa.apellido_p, pa.apellido_m) AS autorizador,
            ppa.nombre AS puesto_autorizador,
            CONCAT_WS(' ', pe.nombre, pe.apellido_p, pe.apellido_m) AS elaborador,
            pe.correo AS correo_elaborador,
            pe.telefono AS telefono_elaborador,
            cc.codigo_contable AS codigo_direccion
        FROM requisiciones q
        JOIN reportes r ON r.id_reporte = q.id_reporte
        LEFT JOIN personal ps ON ps.id_personal = q.id_solicita
        LEFT JOIN puestos pps ON pps.id_puesto = ps.puesto
        LEFT JOIN personal pa ON pa.id_personal = q.id_autoriza
        LEFT JOIN puestos ppa ON ppa.id_puesto = pa.puesto
        LEFT JOIN personal pe ON pe.id_personal = q.id_elabora
        LEFT JOIN codigos_contables cc ON cc.id_codigo = q.id_codigo
        WHERE q.id_requisicion = :id_requisicion"
    );
    $query->execute([':id_requisicion' => $id_requisicion]);
    $requisicion = $query->fetch(PDO::FETCH_ASSOC);

    if (!$requisicion) {
        http_response_code(404);
        exit('Requisición no encontrada');
    }

    $query = $pdo->prepare(
        'SELECT num_lote, cantidad, descripcion FROM requisiciones_detalle WHERE id_requisicion = :id_requisicion ORDER BY id_detalle'
    );
    $query->execute([':id_requisicion' => $id_requisicion]);
    $detalles = $query->fetchAll(PDO::FETCH_ASSOC);

    $escape = static fn ($valor): string => htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');

    // Separar fecha (YYYY-MM-DD) en DÍA, MES y AÑO
    $dia = '';
    $mes = '';
    $anio = '';
    if (!empty($requisicion['fecha'])) {
        $partesFecha = explode('-', (string) $requisicion['fecha']);
        if (count($partesFecha) === 3) {
            $anio = $partesFecha[0];
            $mes  = (string) ((int) $partesFecha[1]);
            $dia  = (string) ((int) $partesFecha[2]);
        }
    }

    // Formatear fecha de entrega si existe
    $fechaEntregaTexto = '';
    if (!empty($requisicion['fecha_entrega'])) {
        $partesEntrega = explode('-', (string) $requisicion['fecha_entrega']);
        $fechaEntregaTexto = count($partesEntrega) === 3
            ? "{$partesEntrega[2]}/{$partesEntrega[1]}/{$partesEntrega[0]}"
            : (string) $requisicion['fecha_entrega'];
    }

    $esMateriales = strtoupper((string) ($requisicion['tipo_requisicion'] ?? 'SERVICIOS')) === 'MATERIALES';

    $codigoCuenta = ($requisicion['tipo_codigo'] ?? '') === 'CC_CENTRAL'
        ? ($requisicion['cc_central'] ?? '')
        : ($requisicion['codigo_direccion'] ?? '');

    $cuentaDefault = $esMateriales
        ? '1-99999-51294001-29401-25021-1-2110008020010-2501-151012009-5050001-6I015-015'
        : '1-99999-51351001-5101-25021-1-2110008020010-2501-134020506-5050001-61015-015';

    $cuentaCargo = $codigoCuenta
        ? "1-99999-{$codigoCuenta}-25021-1-2110008020010-2501-134020506-5050001-61015-015"
        : $cuentaDefault;

    $partesCuenta = explode('-', $cuentaCargo);
    $partidaPresupuestal = $partesCuenta[2] ?? '';

    $puestoSolicitante = (string) ($requisicion['puesto_solicitante'] ?? '');
    $puestoAutorizador = !empty($requisicion['puesto_autorizador'])
        ? (string) $requisicion['puesto_autorizador']
        : 'DIRECTOR DE SISTEMAS Y TECNOLOGÍAS';

    // Filas de la tabla de partidas; las de encabezado no llevan lote ni cantidad
    $filas_partidas = '';
    foreach ($detalles as $idx => $detalle) {
        $esEncabezado = ($detalle['num_lote'] === null || $detalle['num_lote'] === '')
                     && ($detalle['cantidad'] === null || $detalle['cantidad'] === '');
        $filas_partidas .= '<tr>'
            . '<td class="centro">' . $escape($detalle['num_lote']) . '</td>'
            . '<td class="centro">' . $escape($detalle['cantidad']) . '</td>';
        if ($esMateriales) {
            $filas_partidas .= '<td class="centro">' . ($esEncabezado ? '' : 'pza') . '</td>';
        }
        $filas_partidas .= '<td class="' . ($esEncabezado ? 'negrita' : '') . '">'
            . nl2br($escape($detalle['descripcion'])) . '</td>';
        if ($esMateriales) {
            $filas_partidas .= '<td class="centro">' . ($idx === 0 ? $escape($partidaPresupuestal) : '') . '</td>';
        }
        $filas_partidas .= '</tr>';
    }

    $directorio_plantilla = __DIR__ . '/../generador_pdf/';
    $plantilla = file_get_contents($directorio_plantilla . ($esMateriales ? 'requisicion_materiales.html' : 'requisicion_servicios.html'));
    $estilos = file_get_contents($directorio_plantilla . 'requisicion.css');
    if ($plantilla === false || $estilos === false) {
        throw new RuntimeException('No se pudo cargar la plantilla de la requisición');
    }

    // strtr reemplaza en una sola pasada: los valores insertados no se vuelven a interpretar
    $html = strtr($plantilla, [
        '{{estilos}}' => $estilos,
        '{{id_requisicion}}' => $escape($id_requisicion),
        '{{dia}}' => $escape($dia),
        '{{mes}}' => $escape($mes),
        '{{anio}}' => $escape($anio),
        '{{cuenta_cargo}}' => $escape($cuentaCargo),
        '{{justificacion}}' => nl2br($escape($requisicion['justificacion'])),
        '{{solicitante}}' => $escape(mb_strtoupper((string) $requisicion['solicitante'], 'UTF-8')),
        '{{autorizador}}' => $escape(mb_strtoupper((string) $requisicion['autorizador'], 'UTF-8')),
        '{{puesto_solicitante}}' => $escape(mb_strtoupper($puestoSolicitante, 'UTF-8')),
        '{{puesto_autorizador}}' => $escape(mb_strtoupper($puestoAutorizador, 'UTF-8')),
        '{{elaborador}}' => $escape($requisicion['elaborador']),
        '{{correo_elaborador}}' => $escape($requisicion['correo_elaborador']),
        '{{telefono_elaborador}}' => $escape($requisicion['telefono_elaborador']),
        '{{fecha_entrega}}' => $escape($fechaEntregaTexto),
        '{{filas_partidas}}' => $filas_partidas,
    ]);

    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
} catch (Throwable $e) {
    error_log('Error al generar documento de requisición: ' . $e->getMessage());
    http_response_code(500);
    exit('No fue posible generar el documento');
}
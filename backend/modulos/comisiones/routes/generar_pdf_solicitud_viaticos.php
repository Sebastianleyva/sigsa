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

    $consulta_usuario = $pdo->prepare(
        "SELECT puesto, area
         FROM personal
         WHERE id_personal = :id_personal AND activo = '1'"
    );
    $consulta_usuario->execute(['id_personal' => $id_personal]);
    $usuario_actual = $consulta_usuario->fetch(PDO::FETCH_ASSOC);
    $area = $usuario_actual
        ? filter_var($usuario_actual['area'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        : false;
    if (
        !$usuario_actual
        || (!in_array((int) $usuario_actual['puesto'], [1, 2], true) && $area !== 6)
    ) {
        http_response_code(403);
        exit('No tienes permisos para consultar solicitudes de viáticos');
    }

    $id_solicitud = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_solicitud === false || $id_solicitud === null) {
        http_response_code(400);
        exit('Solicitud de viáticos inválida');
    }

    $consulta = $pdo->prepare(
        "SELECT sv.id_solicitud_viaticos, sv.fecha, sv.clave_cobro, sv.puesto,
                sv.contacto_emergencia_nombre, sv.contacto_emergencia_numero,
                sv.programa, sv.financiamiento, sv.observaciones, sv.id_comision,
        c.id_comision AS folio_comision, c.tipo_comision,
        c.fecha_inicio, c.fecha_final, s.fecha_comision,
                s.ubicacion AS lugar, s.falla_reportada AS motivo,
                CONCAT_WS(' ', pc.nombre, pc.apellido_p, pc.apellido_m) AS comisionado,
                pc.curp, pc.rfc, pc.correo AS correo_comisionado, pc.telefono,
                COALESCE(NULLIF(TRIM(pc.cisco), ''), 'N/A') AS cisco_comisionado,
                CONCAT_WS(' ', pr.nombre, pr.apellido_p, pr.apellido_m) AS registrante,
                pr.correo AS correo_registrante,
                COALESCE(NULLIF(TRIM(pr.cisco), ''), 'N/A') AS cisco_registrante
         FROM solicitudes_de_viaticos sv
         INNER JOIN comisiones c ON c.id_comision = sv.id_comision
         INNER JOIN solicitudes s ON s.id_solicitud = c.id_solicitud
         INNER JOIN personal pc ON pc.id_personal = sv.id_personal_comisionado
         INNER JOIN personal pr ON pr.id_personal = sv.id_personal_registra
         WHERE sv.id_solicitud_viaticos = :id_solicitud"
    );
    $consulta->execute(['id_solicitud' => $id_solicitud]);
    $solicitud = $consulta->fetch(PDO::FETCH_ASSOC);
    if (!$solicitud) {
        http_response_code(404);
        exit('Solicitud de viáticos no encontrada');
    }

    $consulta_renglones = $pdo->prepare(
        'SELECT cc.codigo_contable, cc.descripcion, vc.num_dias,
                vc.cuota_diaria, vc.importe, vc.tipo_comprobacion
         FROM viaticos_comisiones vc
         INNER JOIN codigos_contables_comisiones cc
             ON cc.id_codigo_contable_comision = vc.id_codigo_c_comision
         WHERE vc.id_solicitud_viaticos = :id_solicitud
         ORDER BY vc.id_viaticos'
    );
    $consulta_renglones->execute(['id_solicitud' => $id_solicitud]);
    $renglones = $consulta_renglones->fetchAll(PDO::FETCH_ASSOC);

    $consulta_director = $pdo->query(
        "SELECT CONCAT_WS(' ', nombre, apellido_p, apellido_m) AS nombre_director
         FROM personal
         WHERE puesto = 1 AND activo = '1'
         ORDER BY id_personal DESC
         LIMIT 1"
    );
    $director = trim((string) ($consulta_director->fetchColumn() ?: ''));

    $escape = static fn ($valor): string => htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8',
    );
    $fecha = static function ($valor): string {
        if (!$valor) return '';
        $fecha = DateTime::createFromFormat('!Y-m-d', (string) $valor);
        return $fecha ? $fecha->format('d/m/Y') : (string) $valor;
    };
    $periodo_comision = $solicitud['tipo_comision'] === 'FORANEA'
        && !empty($solicitud['fecha_inicio'])
        && !empty($solicitud['fecha_final'])
        ? 'Del ' . $fecha($solicitud['fecha_inicio']) . ' al ' . $fecha($solicitud['fecha_final'])
        : $fecha($solicitud['fecha_comision']);
    $dinero = static fn ($valor): string => '$' . number_format((float) ($valor ?? 0), 2, '.', ',');
    $total = array_sum(array_map(static fn (array $renglon): float => (float) $renglon['importe'], $renglones));

    // Contacto de emergencia combinado (nombre y teléfono)
    $emergencia_partes = array_filter([
        trim((string) ($solicitud['contacto_emergencia_nombre'] ?? '')),
        trim((string) ($solicitud['contacto_emergencia_numero'] ?? '')),
    ], static fn (string $v): bool => $v !== '');
    $contacto_emergencia = implode(' · Tel: ', $emergencia_partes);

    // Garantizar 8 filas mínimas en la tabla de partidas
    $filas_tabla = $renglones;
    while (count($filas_tabla) < 8) {
        $filas_tabla[] = null;
    }

    // Códigos contables: una línea por renglón registrado
    $codigos_contables = '<div class="val"></div>';
    if (!empty($renglones)) {
        $codigos_contables = '';
        foreach ($renglones as $indice => $renglon) {
            $estilo = $indice > 0 ? ' style="margin-top: 2px;"' : '';
            $codigos_contables .= '<div class="val"' . $estilo . '>1-99999-'
                . $escape($renglon['codigo_contable'])
                . '-25021-1-2110008020010-2501-151012009-5050001-6I015-015</div>';
        }
    }

    // Filas de la tabla de partidas (las vacías completan el mínimo de 8)
    $filas_partidas = '';
    foreach ($filas_tabla as $renglon) {
        $concepto = '';
        $dias = '';
        $cuota = '';
        $importe = '';
        if ($renglon !== null) {
            $concepto = $escape($renglon['codigo_contable']) . ' - ' . $escape($renglon['descripcion']);
            if (!empty($renglon['tipo_comprobacion'])) {
                $concepto .= ' (' . $escape($renglon['tipo_comprobacion']) . ')';
            }
            $dias = $renglon['num_dias'] !== null ? $escape($renglon['num_dias']) : '';
            $cuota = $renglon['cuota_diaria'] !== null ? $escape($dinero($renglon['cuota_diaria'])) : '';
            $importe = $escape($dinero($renglon['importe']));
        }
        $filas_partidas .= '<tr><td>' . $concepto . '</td>'
            . '<td class="centro">' . $dias . '</td>'
            . '<td class="num">' . $cuota . '</td>'
            . '<td class="num">' . $importe . '</td></tr>';
    }

    $directorio_plantilla = __DIR__ . '/../generador_pdf/';
    $plantilla = file_get_contents($directorio_plantilla . 'solicitud_viaticos.html');
    $estilos = file_get_contents($directorio_plantilla . 'solicitud_viaticos.css');
    if ($plantilla === false || $estilos === false) {
        throw new RuntimeException('No se pudo cargar la plantilla de la solicitud de viáticos');
    }

    // strtr reemplaza en una sola pasada: los valores insertados no se vuelven a interpretar
    $html = strtr($plantilla, [
        '{{estilos}}' => $estilos,
        '{{id_solicitud_viaticos}}' => $escape($solicitud['id_solicitud_viaticos']),
        '{{anio}}' => date('y'),
        '{{fecha}}' => $escape($fecha($solicitud['fecha'])),
        '{{comisionado}}' => $escape($solicitud['comisionado']),
        '{{comisionado_mayusculas}}' => $escape(mb_strtoupper((string) $solicitud['comisionado'], 'UTF-8')),
        '{{curp}}' => $escape($solicitud['curp']),
        '{{rfc}}' => $escape($solicitud['rfc']),
        '{{clave_cobro}}' => $escape($solicitud['clave_cobro']),
        '{{puesto}}' => $escape($solicitud['puesto']),
        '{{cisco_comisionado}}' => $escape($solicitud['cisco_comisionado']),
        '{{correo_comisionado}}' => $escape($solicitud['correo_comisionado']),
        '{{telefono}}' => $escape($solicitud['telefono']),
        '{{contacto_emergencia}}' => $escape($contacto_emergencia),
        '{{financiamiento}}' => $escape($solicitud['financiamiento']),
        '{{programa}}' => $escape($solicitud['programa']),
        '{{registrante}}' => $escape($solicitud['registrante']),
        '{{cisco_registrante}}' => $escape($solicitud['cisco_registrante']),
        '{{correo_registrante}}' => $escape($solicitud['correo_registrante']),
        '{{codigos_contables}}' => $codigos_contables,
        '{{periodo_comision}}' => $escape($periodo_comision),
        '{{lugar}}' => $escape($solicitud['lugar']),
        '{{motivo}}' => nl2br($escape($solicitud['motivo']), false),
        '{{filas_partidas}}' => $filas_partidas,
        '{{total}}' => $escape($dinero($total)),
        '{{observaciones}}' => nl2br($escape($solicitud['observaciones']), false),
        '{{director}}' => $escape(mb_strtoupper($director, 'UTF-8')),
    ]);

    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Error interno del servidor');
}
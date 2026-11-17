<?php

require_once '../../../includes/auth.php';
require_once '../../../includes/conexion.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    exit('Método no permitido');
}

try {
    validar_jwt_requerido();
    validar_csrf_requerido();
    requerir_rol([1, 2, 3]);
    $id_requisicion = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_requisicion === false) {
        http_response_code(400);
        exit('Requisición inválida');
    }

    $query = $pdo->prepare(
        "SELECT q.*, r.id_reporte, CONCAT_WS(' ', ps.nombre, ps.apellido_p, ps.apellido_m) AS solicitante,
            CONCAT_WS(' ', pa.nombre, pa.apellido_p, pa.apellido_m) AS autorizador,
            CONCAT_WS(' ', pe.nombre, pe.apellido_p, pe.apellido_m) AS elaborador,
            pe.correo AS correo_elaborador, pe.telefono AS telefono_elaborador
        FROM requisiciones q
        JOIN reportes r ON r.id_reporte = q.id_reporte
        LEFT JOIN personal ps ON ps.id_personal = q.id_solicita
        LEFT JOIN personal pa ON pa.id_personal = q.id_autoriza
        LEFT JOIN personal pe ON pe.id_personal = q.id_elabora
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

    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="es"><head><meta charset="UTF-8"><title>Requisición #' . $escape($id_requisicion) . '</title>';
    echo '<style>body{font-family:Arial,sans-serif;color:#172b27;margin:40px}h1{text-align:center;font-size:22px}.meta,.firma{display:grid;grid-template-columns:1fr 1fr;gap:12px;border:1px solid #bbb;padding:14px;margin-bottom:16px}.meta strong,.firma strong{display:block;margin-top:4px}.wide{grid-column:1/-1}table{width:100%;border-collapse:collapse;margin:16px 0}th,td{border:1px solid #999;padding:8px;text-align:left;vertical-align:top}th{background:#eaf2ed}.print{display:block;margin:0 auto 24px;padding:10px 16px;background:#176c58;color:#fff;border:0;cursor:pointer}@media print{.print{display:none}body{margin:12mm}}</style></head><body>';
    echo '<button class="print" onclick="window.print()">Imprimir / Guardar como PDF</button>';
    echo '<h1>REQUISICIÓN DE SERVICIO</h1><div class="meta">';
    echo '<div><span>Área solicitante</span><strong>DIRECCIÓN DE SISTEMAS Y TECNOLOGÍAS</strong></div>';
    echo '<div><span>Cuenta(s) cargo</span><strong>' . $escape('1-99999-51351001-5101-25021-1-2110008020010-2501-134020506-5050001-61015-015') . '</strong></div>';
    echo '<div><span>Financiamiento</span><strong>FONE</strong></div><div><span>Folio</span><strong>Se asigna en oficinas centrales</strong></div>';
    echo '<div><span>Reporte vinculado</span><strong>#' . $escape($requisicion['id_reporte']) . '</strong></div><div><span>Fecha</span><strong>' . $escape($requisicion['fecha']) . '</strong></div>';
    echo '<div><span>Fecha de entrega</span><strong>' . $escape($requisicion['fecha_entrega'] ?: 'No definida') . '</strong></div><div><span>Solicita</span><strong>' . $escape($requisicion['solicitante']) . '</strong></div></div>';
    echo '<h2>Partidas y conceptos</h2><table><thead><tr><th>Núm. lote</th><th>Cantidad</th><th>Descripción</th></tr></thead><tbody>';
    foreach ($detalles as $detalle) echo '<tr><td>' . $escape($detalle['num_lote']) . '</td><td>' . $escape($detalle['cantidad']) . '</td><td>' . nl2br($escape($detalle['descripcion'])) . '</td></tr>';
    echo '</tbody></table><h2>Justificación</h2><p>' . nl2br($escape($requisicion['justificacion'])) . '</p><div class="firma">';
    echo '<div><span>Autoriza</span><strong>' . $escape($requisicion['autorizador']) . '</strong></div><div><span>Elabora</span><strong>' . $escape($requisicion['elaborador']) . '</strong><small>' . $escape($requisicion['correo_elaborador']) . ' · Extensión Cisco: ' . $escape($requisicion['telefono_elaborador']) . '</small></div></div></body></html>';
} catch (Throwable $e) {
    error_log('Error al generar documento de requisición: ' . $e->getMessage());
    http_response_code(500);
    exit('No fue posible generar el documento');
}

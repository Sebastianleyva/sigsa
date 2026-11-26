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
                c.id_comision AS folio_comision, s.fecha_comision,
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

    $escape = static fn ($valor): string => htmlspecialchars(
        (string) ($valor ?? ''),
        ENT_QUOTES | ENT_SUBSTITUTE,
        'UTF-8',
    );
    $fecha = static function ($valor): string {
        if (!$valor) return 'No especificado';
        $fecha = DateTime::createFromFormat('!Y-m-d', (string) $valor);
        return $fecha ? $fecha->format('d/m/Y') : (string) $valor;
    };
    $dinero = static fn ($valor): string => '$' . number_format((float) ($valor ?? 0), 2, '.', ',');
    $total = array_sum(array_map(static fn (array $renglon): float => (float) $renglon['importe'], $renglones));

    header('Content-Type: text/html; charset=UTF-8');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Solicitud de viáticos #<?= $escape($solicitud['id_solicitud_viaticos']) ?></title>
  <style>
    @page { size: letter portrait; margin: 14mm; }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      background: #e9ecef;
      color: #172b27;
      font: 10pt Arial, Helvetica, sans-serif;
    }
    .toolbar {
      padding: 12px;
      background: #172b27;
      text-align: center;
    }
    .toolbar button {
      padding: 10px 18px;
      border: 0;
      border-radius: 4px;
      background: #176c58;
      color: #fff;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
    }
    .document {
      width: 215.9mm;
      min-height: 279.4mm;
      margin: 16px auto;
      padding: 14mm;
      background: #fff;
      box-shadow: 0 2px 10px rgb(0 0 0 / 15%);
    }
    header {
      display: flex;
      justify-content: space-between;
      gap: 20px;
      padding-bottom: 14px;
      border-bottom: 2px solid #176c58;
    }
    header h1 { margin: 0 0 5px; font-size: 17pt; }
    header p { margin: 0; color: #53645f; }
    .folio { text-align: right; white-space: nowrap; }
    section { margin-top: 16px; }
    h2 {
      margin: 0 0 8px;
      padding: 6px 8px;
      background: #e9f1ec;
      color: #203431;
      font-size: 11pt;
    }
    .grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 8px 18px;
    }
    .field { min-width: 0; }
    .field strong {
      display: block;
      margin-bottom: 3px;
      color: #53645f;
      font-size: 8pt;
      text-transform: uppercase;
    }
    .field span { overflow-wrap: anywhere; }
    .full { grid-column: 1 / -1; }
    table { width: 100%; border-collapse: collapse; }
    th, td {
      padding: 7px 6px;
      border: 1px solid #aebbb4;
      text-align: left;
      vertical-align: top;
    }
    th { background: #e9f1ec; }
    .numeric { text-align: right; white-space: nowrap; }
    tfoot th, tfoot td { font-weight: 700; }
    .observations {
      min-height: 36px;
      white-space: pre-wrap;
      overflow-wrap: anywhere;
    }
    .signatures {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 50px;
      margin-top: 48px;
      text-align: center;
    }
    .signature { padding-top: 8px; border-top: 1px solid #333; }
    @media print {
      body { background: #fff; }
      .toolbar { display: none; }
      .document {
        width: 100%;
        min-height: 0;
        margin: 0;
        padding: 0;
        box-shadow: none;
      }
      section, table, tr { break-inside: avoid; }
    }
  </style>
</head>
<body>
  <div class="toolbar">
    <button type="button" onclick="window.print()">Imprimir / Guardar como PDF</button>
  </div>
  <main class="document">
    <header>
      <div>
        <h1>SOLICITUD DE VIÁTICOS</h1>
        <p>DIRECCIÓN DE SISTEMAS Y TECNOLOGÍAS</p>
      </div>
      <div class="folio">
        <strong>Folio #<?= $escape($solicitud['id_solicitud_viaticos']) ?></strong><br>
        Fecha: <?= $escape($fecha($solicitud['fecha'])) ?>
      </div>
    </header>

    <section>
      <h2>Unidad administrativa</h2>
      <div class="grid">
        <div class="field"><strong>Registrante</strong><span><?= $escape($solicitud['registrante']) ?></span></div>
        <div class="field"><strong>Correo</strong><span><?= $escape($solicitud['correo_registrante']) ?></span></div>
        <div class="field"><strong>Cisco</strong><span><?= $escape($solicitud['cisco_registrante']) ?></span></div>
        <div class="field"><strong>Programa</strong><span><?= $escape($solicitud['programa']) ?></span></div>
        <div class="field"><strong>Código contable</strong><span><?= $escape($solicitud['clave_cobro']) ?></span></div>
        <div class="field"><strong>Financiamiento</strong><span><?= $escape($solicitud['financiamiento']) ?></span></div>
      </div>
    </section>

    <section>
      <h2>Comisionado</h2>
      <div class="grid">
        <div class="field"><strong>Nombre</strong><span><?= $escape($solicitud['comisionado']) ?></span></div>
        <div class="field"><strong>Puesto</strong><span><?= $escape($solicitud['puesto']) ?></span></div>
        <div class="field"><strong>CURP</strong><span><?= $escape($solicitud['curp']) ?></span></div>
        <div class="field"><strong>RFC</strong><span><?= $escape($solicitud['rfc']) ?></span></div>
        <div class="field"><strong>Correo</strong><span><?= $escape($solicitud['correo_comisionado']) ?></span></div>
        <div class="field"><strong>Teléfono</strong><span><?= $escape($solicitud['telefono']) ?></span></div>
        <div class="field"><strong>Cisco</strong><span><?= $escape($solicitud['cisco_comisionado']) ?></span></div>
        <div class="field"><strong>Contacto de emergencia</strong><span><?= $escape($solicitud['contacto_emergencia_nombre']) ?></span></div>
        <div class="field"><strong>Teléfono de emergencia</strong><span><?= $escape($solicitud['contacto_emergencia_numero']) ?></span></div>
      </div>
    </section>

    <section>
      <h2>Datos de la comisión #<?= $escape($solicitud['folio_comision']) ?></h2>
      <div class="grid">
        <div class="field"><strong>Periodo</strong><span><?= $escape($fecha($solicitud['fecha_comision'])) ?></span></div>
        <div class="field"><strong>Lugar</strong><span><?= $escape($solicitud['lugar']) ?></span></div>
        <div class="field full"><strong>Motivo</strong><span><?= nl2br($escape($solicitud['motivo']), false) ?></span></div>
      </div>
    </section>

    <section>
      <h2>Partidas, cuotas e importes</h2>
      <table>
        <thead>
          <tr>
            <th>Partida / COG / concepto</th>
            <th class="numeric">Núm. días</th>
            <th class="numeric">Cuota diaria</th>
            <th>Comprobación</th>
            <th class="numeric">Importe</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($renglones as $renglon): ?>
            <tr>
              <td><?= $escape($renglon['codigo_contable']) ?> · <?= $escape($renglon['descripcion']) ?></td>
              <td class="numeric"><?= $renglon['num_dias'] === null ? '—' : $escape($renglon['num_dias']) ?></td>
              <td class="numeric"><?= $renglon['cuota_diaria'] === null ? '—' : $escape($dinero($renglon['cuota_diaria'])) ?></td>
              <td><?= $escape($renglon['tipo_comprobacion']) ?></td>
              <td class="numeric"><?= $escape($dinero($renglon['importe'])) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <th colspan="4" class="numeric">Total</th>
            <td class="numeric"><?= $escape($dinero($total)) ?></td>
          </tr>
        </tfoot>
      </table>
    </section>

    <section>
      <h2>Observaciones</h2>
      <div class="observations"><?= nl2br($escape($solicitud['observaciones']), false) ?></div>
    </section>

    <div class="signatures">
      <div class="signature"><?= $escape($solicitud['comisionado']) ?><br>Comisionado</div>
      <div class="signature"><?= $escape($solicitud['registrante']) ?><br>Elaboró</div>
    </div>
  </main>
</body>
</html>
    <?php
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Error interno del servidor');
}

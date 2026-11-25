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
    $texto = static function (string $campo) use ($orden, $escape): string {
        $valor = trim((string) ($orden[$campo] ?? ''));
        return $valor === '' ? '—' : nl2br($escape($valor), false);
    };
    $cantidad = static function (string $campo) use ($orden, $escape): string {
        return $orden[$campo] === null ? '—' : $escape($orden[$campo]);
    };

    $fecha = DateTime::createFromFormat('!Y-m-d', (string) $orden['fecha']);
    $fecha_texto = $fecha ? $fecha->format('d/m/Y') : (string) $orden['fecha'];

    header('Content-Type: text/html; charset=UTF-8');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Orden de servicio #<?= $escape($orden['n_reporte']) ?></title>
  <style>
    @page { size: letter portrait; margin: 12mm; }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      color: #17231f;
      background: #e9eeeb;
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
      color: #fff;
      background: #176c58;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
    }
    .sheet {
      width: 215.9mm;
      min-height: 279.4mm;
      margin: 16px auto;
      padding: 12mm;
      background: #fff;
      box-shadow: 0 2px 10px rgb(0 0 0 / 15%);
    }
    .heading {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 20px;
      padding-bottom: 14px;
      border-bottom: 3px solid #176c58;
    }
    .heading p, .heading h1 { margin: 0; }
    .department {
      margin-bottom: 7px !important;
      color: #176c58;
      font-size: 9pt;
      font-weight: 700;
      letter-spacing: 1px;
    }
    h1 { font-size: 19pt; }
    .folio {
      min-width: 105px;
      padding: 10px;
      border: 1px solid #17231f;
      text-align: center;
    }
    .folio span, .folio strong { display: block; }
    .folio span { margin-bottom: 4px; font-size: 8pt; }
    .folio strong { font-size: 15pt; }
    .metadata {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      margin: 14px 0;
      border: 1px solid #17231f;
    }
    .metadata div { padding: 8px; border-right: 1px solid #17231f; }
    .metadata div:last-child { border-right: 0; }
    .label {
      display: block;
      margin-bottom: 4px;
      color: #40544c;
      font-size: 7.5pt;
      font-weight: 700;
      text-transform: uppercase;
    }
    .section { margin-top: 14px; break-inside: avoid; }
    .section h2 {
      margin: 0;
      padding: 6px 8px;
      background: #dce5df;
      font-size: 9pt;
      letter-spacing: .4px;
      text-transform: uppercase;
    }
    .grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      border-left: 1px solid #738078;
      border-top: 1px solid #738078;
    }
    .field {
      min-height: 43px;
      padding: 7px 8px;
      border-right: 1px solid #738078;
      border-bottom: 1px solid #738078;
      overflow-wrap: anywhere;
    }
    .full { grid-column: 1 / -1; }
    .long-text { min-height: 68px; white-space: normal; }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      padding: 7px 8px;
      border: 1px solid #738078;
      text-align: left;
    }
    th {
      background: #dce5df;
      font-size: 8pt;
      text-transform: uppercase;
    }
    td:last-child, th:last-child { width: 30%; text-align: center; }
    .signatures {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 38px;
      margin-top: 54px;
      text-align: center;
    }
    .signature-line { border-top: 1px solid #17231f; padding-top: 7px; }
    .signature-line strong, .signature-line span { display: block; }
    .signature-line span { margin-top: 3px; font-size: 8pt; }
    @media print {
      body { background: #fff; }
      .toolbar { display: none !important; }
      .sheet {
        width: 100%;
        min-height: 0;
        margin: 0;
        padding: 0;
        box-shadow: none;
      }
      .section, .metadata, .heading, .signatures { break-inside: avoid; }
    }
    @media screen and (max-width: 760px) {
      .sheet { width: calc(100% - 24px); min-height: 0; padding: 20px; }
      .heading { align-items: flex-start; }
    }
  </style>
</head>
<body>
  <div class="toolbar">
    <button type="button" onclick="window.print()">Imprimir / Guardar como PDF</button>
  </div>
  <main class="sheet">
    <header class="heading">
      <div>
        <p class="department">SIGSA · DIRECCIÓN DE SISTEMAS Y TECNOLOGÍAS</p>
        <h1>ORDEN DE SERVICIO</h1>
      </div>
      <div class="folio">
        <span>NÚMERO DE REPORTE</span>
        <strong><?= $escape($orden['n_reporte']) ?></strong>
      </div>
    </header>

    <section class="metadata" aria-label="Datos del reporte">
      <div><span class="label">Orden</span>#<?= $escape($orden['id_orden']) ?></div>
      <div><span class="label">Comisión</span>#<?= $escape($orden['id_comision']) ?></div>
      <div><span class="label">Fecha</span><?= $escape($fecha_texto) ?></div>
    </section>

    <section class="section">
      <h2>Datos del centro escolar</h2>
      <div class="grid">
        <div class="field"><span class="label">Institución</span><?= $texto('nombre_institucion') ?></div>
        <div class="field"><span class="label">Clave de la institución</span><?= $texto('clave_institucion') ?></div>
        <div class="field"><span class="label">Responsable</span><?= $texto('responsable_institucion') ?></div>
        <div class="field"><span class="label">Teléfono</span><?= $texto('tel_institucion') ?></div>
        <div class="field full"><span class="label">Domicilio</span><?= $texto('domicilio_institucion') ?></div>
        <div class="field"><span class="label">Localidad</span><?= $texto('localidad_institucion') ?></div>
        <div class="field"><span class="label">Municipio</span><?= $texto('municipio_institucion') ?></div>
      </div>
    </section>

    <section class="section">
      <h2>Atención del servicio</h2>
      <div class="grid">
        <div class="field full long-text"><span class="label">Falla reportada</span><?= $texto('falla_reportada') ?></div>
        <div class="field full long-text"><span class="label">Actividad realizada</span><?= $texto('actividad_realizada') ?></div>
      </div>
    </section>

    <section class="section">
      <h2>Equipo atendido y beneficiarios</h2>
      <table>
        <thead><tr><th>Concepto</th><th>Cantidad</th></tr></thead>
        <tbody>
          <tr><td>CPU</td><td><?= $cantidad('cpu') ?></td></tr>
          <tr><td>Proyectores</td><td><?= $cantidad('proyectores') ?></td></tr>
          <tr><td>Impresoras</td><td><?= $cantidad('impresoras') ?></td></tr>
          <tr><td>Alumnos beneficiados</td><td><?= $cantidad('alumnos_benefi') ?></td></tr>
          <tr><td>Donación</td><td><?= $cantidad('donacion') ?></td></tr>
        </tbody>
      </table>
    </section>

    <section class="section">
      <h2>Observaciones</h2>
      <div class="field long-text"><?= $texto('observaciones') ?></div>
    </section>

    <footer class="signatures">
      <div class="signature-line">
        <strong><?= $texto('responsable_institucion') ?></strong>
        <span>Responsable del centro escolar</span>
      </div>
      <div class="signature-line">
        <strong>Personal comisionado</strong>
        <span>Responsable del servicio</span>
      </div>
    </footer>
  </main>
</body>
</html>
<?php
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('No fue posible generar la orden de servicio');
}

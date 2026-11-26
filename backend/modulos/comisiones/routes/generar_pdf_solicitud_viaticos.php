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

    // Logotipo Gobierno del Estado de Durango extraído directamente de SOLICITUD DE VIÁTICOS.xlsx
    $logoDurangoBase64 = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEcAAABABAMAAAC6m3eUAAAAMFBMVEX////+/v79/f38/Pz7+/v6+vry8vLk5OTX19fFxcWxsbGfn5+BgYFfX19RUVFGRkY1y7A7AAAFOklEQVR42o2Tb0wTZxzHf71KbSmaUhgzcVuud9RlM1vqVdyL7QV/iq9cgsphtldFxxUQjcbQA9toXSLQgqHJIqVlOt4I/Wd0c8G21yxNFplyBX2zJYsF9mJBt9lrsjeKYtlzV9BC0rrvi7vvPfd5nuf3/H6/B4hN0rX7W6mNQzhshvQB7zXiDRCJjQ64sTdAdYPxOHehrii0y/CdIGTc+4pBOgszLCxk+iwHi0AGKz+cSi7apk9RRaA+fjjJ37dNO4pD53huzl4c8iRP8q65rl+KQZr++HtPhtJlEYe2IKS39lzftjq7ss1vbSsI6T7pvbp7xZv62MvuKQjtcvmvOl/Gly8NBC8WhN5q5+130lf+TNrutmkLbgcHAktzC9OPw7is8Omqq+7FhzND0enKYgU2dNrDK2GXmSoGkWDhH06b5cX7yQjHObOcekP7kooRecH2NRIERRJGas+g+kI9QYnfxs2QDip1GF4OcqqtpBXDsTrQ1kH9Bog06hmD/qt9NGPGSlq3mPcdlbOHMStmJPMgXKZKP1T99lF/KnX6xOOqlDpxJTl+af57AO0rSHeE7Xg0PLr0rufZ8Hg4rljkEv+4JlIdzyzswdeQhe14sN3/73bPk3lz6j7z+8LN5cGnDxXL+RChkalW5pnUbPvT887n1289Op/4KZ64MXsnfzsU+Pver6FzQnFZyTq2XhzZ6lAGMGWgyWjckAIcsKpui5WxWmgro2As3W3dOGg35Yk0NVT1s1aWZdtY9gjLsGx3ZTP1CjKtq0GrIwkQhRNGLZq2rlrIDb8WRtOHYLMqvXkKJeclpfhA3qh7IzQWT84iJpnkvBsgAv6HCFMjTdNrIdJ0sxElTXybTPmB1+AIrTlE4OWStIRu3WjQk2ysRykgQTEwYAUZoW+Uph3W5d6mQwSNHA7yehwMxxcy2TQ/Urkzd7A7FQdyJkF1ieeccWzB4e3FVVG/1pRmJZOoUa+Z5jLJPHHUwjvZdCwYz7jp0qyAZi7epNXZtGjGESSsrmYzfwGcEJYAZN9S+tLsC4ZhLKfRSjmztyz7YsA1l82egnPC322IkiCpLHp19rlkqstEo1zIJODzbGY+GXNgCFpB2b1dgSBkfFMVIqRvbLohPABVRgxv5SgKPIPMUzmCRLMkF6ESgjon/AEG26yAoksgSECSINFsgHBQsK5FYakEbYdazkMi6AUyI6S0nanpRuYBdIIO4KTwuKRY4MN3m0B2VXgkQvtp+oiYgueSEVPQ45RScH81xSeFzM8oJjGHC4n1ZN4UkymGuwwgVSXz8nRjobKsoLJ0osOtpqfk1TvTawX+LL/A87xYYCjp8Y6eAYrYxUg6RuzPmaNEC3oezLWKeBJ5c8PeWq2MAEwjN+nKUZmIcvleA4HpyPrDDQZQ9gG+1skGaK+tMIAGqB4HunyAiSvs0KA/YU+wJ+ryBFyRs6qAU926I/qlXzPjiXV0HL/mVUVGwj0eB5z1z9jbQtF7X/wYtnE+u7vUF5rAYx0B7tMow9no4FSMd4M3FrMFgpGZ9gk+GPJx06Xc4KRmxhsbE79Co4FodMgNHRd6uj3HmIFGxswqLKesCs/Wfk3vxX7FiLKfZjzdvS0tZlSjpi1GDFSjipYaA9BgBiO0NCkDtTJKQ8oAzCVN6MZ9wNsik7ouf8g5s7uPd3Kt9kjo8s5g8OxYxP5hxBUZvI1DtcoZmpiCrhHf+K2hyCSndrtswTNqlusci4a98THbtA8QhFbiHF0B3w9TgSjv4sxopW9K+d6YyxeN2VyxsYjmPwoYMDTnAd8MAAAAAElFTkSuQmCC';

    header('Content-Type: text/html; charset=UTF-8');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Solicitud de viáticos #<?= $escape($solicitud['id_solicitud_viaticos']) ?></title>
  <style>
    @page {
      size: letter portrait;
      margin: 8mm 11mm;
    }
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    body {
      background: #e9ecef;
      color: #000;
      font: 8pt Arial, Helvetica, sans-serif;
    }
    .toolbar {
      padding: 10px;
      background: #172b27;
      text-align: center;
    }
    .toolbar button {
      padding: 9px 18px;
      border: 0;
      border-radius: 4px;
      background: #176c58;
      color: #fff;
      font-size: 13px;
      font-weight: bold;
      cursor: pointer;
    }
    .sheet {
      width: 215.9mm;
      min-height: 279.4mm;
      margin: 14px auto;
      padding: 8mm 11mm;
      background: #fff;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
    }

    /* Marco exterior único del formato */
    .marco-formato {
      border: 1.5px solid #000;
      width: 100%;
    }

    /* Encabezado superior */
    .encabezado-tabla {
      width: 100%;
      border-collapse: collapse;
    }
    .encabezado-tabla td {
      vertical-align: top;
    }
    .celda-logo {
      width: 17%;
      border-right: 1px solid #000;
      border-bottom: 1px solid #000;
      padding: 5px 6px;
      text-align: center;
      vertical-align: middle !important;
    }
    .celda-logo img {
      height: 54px;
      object-fit: contain;
    }
    .celda-titulo-centro {
      width: 59%;
      padding: 5px 10px 0 10px;
    }
    .titulo-principal {
      font-size: 14pt;
      font-weight: bold;
      margin-bottom: 14px;
    }
    .franja-azul {
      height: 11px;
      background: #00b0f0 !important;
      box-shadow: inset 0 0 0 1000px #00b0f0 !important;
      margin-right: -10px;
    }
    .celda-folios-der {
      width: 24%;
      padding: 0;
      text-align: right;
    }
    .caja-amarilla {
      background: #ffff00 !important;
      box-shadow: inset 0 0 0 1000px #ffff00 !important;
      font-weight: bold;
      font-size: 8.5pt;
      padding: 3px 6px;
      text-align: center;
      border-left: 1px solid #000;
      border-bottom: 1px solid #000;
    }
    .folio-oficio {
      font-weight: bold;
      font-size: 8.5pt;
      padding: 4px 6px 2px 6px;
      text-align: right;
    }
    .fila-fecha-header {
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 6px;
      padding: 8px 6px 4px 6px;
      font-weight: bold;
      font-size: 8pt;
    }
    .valor-fecha-header {
      border-bottom: 1px solid #000;
      min-width: 78px;
      text-align: center;
      padding: 1px 4px;
      color: #ff0000 !important;
      font-weight: bold;
    }

    /* Barras de sección gris */
    .barra-seccion {
      background: #d9d9d9 !important;
      box-shadow: inset 0 0 0 1000px #d9d9d9 !important;
      border-top: 1px solid #000;
      text-align: center;
      border-bottom: 1px solid #000;
      font-weight: bold;
      font-size: 8.5pt;
      padding: 2.5px 6px;
    }

    /* Secciones de campos: SOLO LÍNEAS INFERIORES */
    .bloque-campos {
      padding: 5px 8px 7px 8px;
    }
    .fila-form {
      display: flex;
      align-items: flex-end;
      gap: 6px;
      margin-top: 5px;
    }
    .fila-form:first-child {
      margin-top: 1px;
    }
    .lbl {
      font-weight: bold;
      white-space: nowrap;
      font-size: 7.8pt;
    }
    .val {
      border-bottom: 1px solid #000;
      padding: 0 5px 1px 5px;
      min-height: 15px;
      font-size: 8pt;
      overflow-wrap: anywhere;
    }
    .val.grow {
      flex: 1;
    }
    .sub-nota {
      font-size: 6.5pt;
      color: #333;
      text-align: center;
      margin-top: 1px;
    }

    /* Sección Comisión con líneas y recuadro SELLO DE COMPROBACIÓN */
    .grid-comision {
      display: flex;
      gap: 10px;
      padding: 5px 8px 7px 8px;
      align-items: stretch;
    }
    .col-comision-izq {
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 5px;
    }
    .linea-motivo {
      border-bottom: 1px solid #000;
      padding: 1px 5px 2px 5px;
      min-height: 36px;
      font-size: 7.8pt;
      line-height: 1.35;
      flex: 1;
      overflow-wrap: anywhere;
    }
    .caja-sello-comprobacion {
      width: 118px;
      border: 1px solid #bfbfbf;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      font-family: 'Arial Black', Arial, sans-serif;
      font-weight: 900;
      font-size: 8.5pt;
      color: #d0d0d0;
      line-height: 1.25;
      padding: 6px;
      user-select: none;
    }

    /* Tabla de Partida, cuota e importe */
    .seccion-partidas {
      border-top: 1px solid #000;
      padding: 3px 8px 4px 8px;
    }
    .titulo-partidas {
      font-weight: bold;
      font-size: 8pt;
      margin-bottom: 3px;
    }
    .tabla-partidas {
      width: 100%;
      border-collapse: collapse;
      font-size: 7.6pt;
    }
    .tabla-partidas th {
      background: #d9d9d9 !important;
      box-shadow: inset 0 0 0 1000px #d9d9d9 !important;
      border: 1px solid #000;
      padding: 2.5px 5px;
      font-weight: bold;
      text-align: center;
    }
    .tabla-partidas th:first-child {
      text-align: left;
    }
    .tabla-partidas td {
      border: 1px solid #000;
      padding: 2px 5px;
      height: 15.5px;
    }
    .tabla-partidas td.num {
      text-align: right;
      white-space: nowrap;
    }
    .tabla-partidas td.centro {
      text-align: center;
      white-space: nowrap;
    }
    .fila-total-partidas td {
      border: none;
      padding-top: 3px;
      font-weight: bold;
    }
    .fila-total-partidas td.total-lbl {
      text-align: right;
      padding-right: 8px;
    }
    .fila-total-partidas td.total-val {
      border: 1px solid #000;
      text-align: right;
      background: #fff;
    }
    .nota-asterisco {
      font-size: 6.8pt;
      margin-top: 2px;
    }

    /* Observaciones y Manifestaciones */
    .caja-observaciones {
      padding: 4px 8px;
      min-height: 28px;
      font-size: 7.8pt;
      line-height: 1.3;
      overflow-wrap: anywhere;
    }
    .caja-manifestaciones {
      padding: 5px 12px;
      font-size: 7pt;
      line-height: 1.35;
      text-align: center;
    }

    /* Bloque final de firmas (Autoriza / Recibe) */
    .bloque-firmas {
      border-top: 1px solid #000;
      padding: 6px 14px 8px 14px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 48px;
      font-size: 7.8pt;
    }
    .col-firma {
      display: flex;
      flex-direction: column;
    }
    .firma-encabezado {
      font-weight: bold;
      text-align: center;
      margin-bottom: 26px;
    }
    .firma-nombre {
      font-weight: bold;
      text-align: center;
      padding-bottom: 2px;
      min-height: 14px;
    }
    .firma-pie {
      border-top: 1px solid #000;
      padding-top: 2px;
      text-align: center;
      font-weight: normal;
      font-size: 7.5pt;
    }

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
    }
  </style>
</head>
<body>

  <div class="toolbar">
    <button type="button" onclick="window.print()">Imprimir / Guardar como PDF</button>
  </div>

  <main class="sheet">
    <div class="marco-formato">

      <!-- ENCABEZADO OFICIAL -->
      <table class="encabezado-tabla">
        <tr>
          <td class="celda-logo">
            <img src="<?= $logoDurangoBase64 ?>" alt="Gobierno del Estado de Durango">
          </td>
          <td class="celda-titulo-centro">
            <div class="titulo-principal">Solicitud de viáticos</div>
            <div class="franja-azul"></div>
          </td>
          <td class="celda-folios-der">
            <div class="caja-amarilla">DAF-SF-OFC</div>
            <div class="folio-oficio">DSYT/<?= $escape($solicitud['id_solicitud_viaticos']) ?>/<?= date('y') ?></div>
            <div class="fila-fecha-header">
              <span>Fecha:</span>
              <span class="valor-fecha-header"><?= $escape($fecha($solicitud['fecha'])) ?></span>
            </div>
          </td>
        </tr>
      </table>

      <!-- SECCIÓN 1: COMISIONADO -->
      <div class="barra-seccion" class="datos-comisionado">Comisionado:</div>
      <div class="bloque-campos">
        <div class="fila-form">
          <div style="width: 63%; display: flex; align-items: flex-end; gap: 6px;">
            <span class="lbl">Nombre:</span>
            <span class="val grow" style="text-align: center; font-weight: bold;"><?= $escape($solicitud['comisionado']) ?></span>
          </div>
          <div style="flex: 1;"></div>
        </div>

        <div class="fila-form">
          <div style="width: 58%; display: flex; align-items: flex-end; gap: 6px;">
            <span class="lbl">CURP:</span>
            <span class="val grow" style="text-align: center; font-weight: bold;"><?= $escape($solicitud['curp']) ?></span>
          </div>
          <div style="flex: 1; display: flex; align-items: flex-end; gap: 6px;">
            <span class="lbl" style="margin-left: 14px;">RFC:</span>
            <span class="val grow" style="text-align: center; font-weight: bold;"><?= $escape($solicitud['rfc']) ?></span>
          </div>
        </div>

        <div class="fila-form" style="align-items: flex-start;">
          <span class="lbl" style="padding-top: 2px;">Clave de cobro:</span>
          <div style="width: 36%;">
            <div class="val" style="text-align: center; font-weight: bold;"><?= $escape($solicitud['clave_cobro']) ?></div>
            <div class="sub-nota">(Primeros dígitos hasta el punto)</div>
          </div>
          <span class="lbl" style="margin-left: 10px; padding-top: 2px;">Adscripción:</span>
          <div class="val grow" style="border-bottom: none; font-weight: bold; padding-top: 2px;">
            SISTEMAS Y TECNOLOGÍAS DE LA INFORMACIÓN
          </div>
        </div>

        <div class="fila-form">
          <span class="lbl">Puesto:</span>
          <span class="val" style="width: 44%; text-align: center; font-weight: bold;"><?= $escape($solicitud['puesto']) ?></span>
          <span class="lbl" style="margin-left: 14px;">Teléfono Cisco:</span>
          <span class="val grow" style="text-align: center;"><?= $escape($solicitud['cisco_comisionado']) ?></span>
        </div>

        <div class="fila-form">
          <span class="lbl">Correo electrónico:</span>
          <span class="val" style="width: 38%;"><?= $escape($solicitud['correo_comisionado']) ?></span>
          <span class="lbl" style="margin-left: 14px;">Teléfono celular:</span>
          <span class="val grow" style="text-align: center;"><?= $escape($solicitud['telefono']) ?></span>
        </div>

        <div class="fila-form">
          <span class="lbl">En caso de emergencia avisar a: (nombre y teléfono)</span>
          <span class="val grow"><?= $escape($contacto_emergencia) ?></span>
        </div>
      </div>

      <!-- SECCIÓN 2: UNIDAD ADMINISTRATIVA -->
      <div class="barra-seccion">Unidad Administrativa:</div>
      <div class="bloque-campos">
        <div class="fila-form">
          <span class="lbl">Área:</span>
          <span class="val grow" style="text-align: center; font-weight: bold;">DIRECCIÓN DE SISTEMAS Y TECNOLOGÍAS</span>
        </div>

        <div class="fila-form" style="align-items: flex-start;">
          <div style="width: 55%; display: flex; flex-direction: column; gap: 4px;">
            <div class="fila-form" style="margin-top: 0;">
              <span class="lbl">Financiamiento:</span>
              <span class="val grow"><?= $escape($solicitud['financiamiento']) ?></span>
            </div>
            <div class="fila-form" style="margin-top: 0; align-items: flex-start;">
              <span class="lbl" style="padding-top: 2px;">Rubro:</span>
              <div class="grow">
                <div class="val" style="min-height: 15px;"></div>
                <div class="sub-nota">(solamente programas federales)</div>
              </div>
            </div>
          </div>

          <div style="flex: 1; margin-left: 12px; display: flex; flex-direction: column; gap: 3px;">
            <div class="fila-form" style="margin-top: 0;">
              <span class="lbl">Programa:</span>
              <span class="val grow" style="text-align: left; text-weight: bold;">OTRO ESPECIFICAR</span>
            </div>
            <div class="val" style="min-height: 16px; text-align: center;"><?= $escape($solicitud['programa']) ?></div>
          </div>
        </div>

        <div class="fila-form">
          <span class="lbl">Responsable del Presupuesto:</span>
          <span class="val" style="width: 30%;"><?= $escape($solicitud['registrante']) ?></span>
          <span class="lbl" style="margin-left: 8px;">Firma:</span>
          <span class="val" style="width: 16%;"></span>
          <span class="lbl" style="margin-left: 8px;">Cisco:</span>
          <span class="val grow" style="text-align: center;"><?= $escape($solicitud['cisco_registrante']) ?></span>
        </div>

        <div class="fila-form">
          <span class="lbl">Correo electrónico:</span>
          <span class="val" style="width: 42%;"><?= $escape($solicitud['correo_registrante']) ?></span>
        </div>

        <div class="fila-form">
          <span class="lbl">Código Contable:</span>
          <div class="grow">
            <?php if (empty($renglones)): ?>
              <div class="val"></div>
            <?php else: ?>
              <?php foreach ($renglones as $idx => $r): ?>
                <div class="val" style="<?= $idx > 0 ? 'margin-top: 2px;' : '' ?>">
                  1-99999-<?= $escape($r['codigo_contable']) ?>-25021-1-2110008020010-2501-151012009-5050001-6I015-015
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- SECCIÓN 3: COMISIÓN -->
      <div class="barra-seccion">Comisión:</div>
      <div class="grid-comision">
        <div class="col-comision-izq">
          <div class="fila-form" style="margin-top: 0;">
            <span class="lbl" style="min-width: 48px;">Periodo:</span>
            <span class="val grow" style="font-weight: bold; text-align: center;"><?= $escape($periodo_comision) ?></span>
          </div>
          <div class="fila-form" style="margin-top: 0;">
            <span class="lbl" style="min-width: 48px;">Lugar:</span>
            <span class="val grow" style="font-weight: bold; text-align: center;"><?= $escape($solicitud['lugar']) ?></span>
          </div>
          <div class="fila-form" style="margin-top: 0; align-items: flex-start; flex: 1;">
            <span class="lbl" style="min-width: 48px; padding-top: 2px;">Motivo:</span>
            <div class="linea-motivo" style="font-weight: bold; text-align: center;"><?= nl2br($escape($solicitud['motivo']), false) ?></div>
          </div>
        </div>

        <div class="caja-sello-comprobacion">
          SELLO<br>DE<br>COMPROBACIÓN
        </div>
      </div>

      <!-- SECCIÓN 4: PARTIDA, CUOTA E IMPORTE -->
      <div class="seccion-partidas">
        <div class="titulo-partidas">Partida, cuota e importe:</div>
        <table class="tabla-partidas">
          <thead>
            <tr>
              <th style="width: 55%;">Partida/ COG / Concepto *</th>
              <th style="width: 13%;">Núm Días</th>
              <th style="width: 16%;">Cuota Diaria</th>
              <th style="width: 16%;">Importe</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($filas_tabla as $renglon): ?>
              <tr>
                <td>
                  <?php if ($renglon !== null): ?>
                    <?= $escape($renglon['codigo_contable']) ?> - <?= $escape($renglon['descripcion']) ?>
                    <?php if (!empty($renglon['tipo_comprobacion'])): ?>
                      (<?= $escape($renglon['tipo_comprobacion']) ?>)
                    <?php endif; ?>
                  <?php endif; ?>
                </td>
                <td class="centro">
                  <?= ($renglon !== null && $renglon['num_dias'] !== null) ? $escape($renglon['num_dias']) : '' ?>
                </td>
                <td class="num">
                  <?= ($renglon !== null && $renglon['cuota_diaria'] !== null) ? $escape($dinero($renglon['cuota_diaria'])) : '' ?>
                </td>
                <td class="num">
                  <?= $renglon !== null ? $escape($dinero($renglon['importe'])) : '' ?>
                </td>
              </tr>
            <?php endforeach; ?>
            <tr class="fila-total-partidas">
              <td colspan="3" class="total-lbl">TOTAL TRANSFERENCIA / CHEQUE:</td>
              <td class="total-val"><?= $escape($dinero($total)) ?></td>
            </tr>
          </tbody>
        </table>
        <div class="nota-asterisco">* Elegir opción del menú de conceptos que se despliega en el formato</div>
      </div>

      <!-- SECCIÓN 5: OBSERVACIONES -->
      <div class="barra-seccion">Observaciones:</div>
      <div class="caja-observaciones" style="text-align: center; font-weight: bold;"><?= nl2br($escape($solicitud['observaciones']), false) ?></div>

      <!-- SECCIÓN 6: MANIFESTACIONES -->
      <div class="barra-seccion">Manifestaciones</div>
      <div class="caja-manifestaciones">
        Bajo protesta de decir verdad, el Suscrito me comprometo en un plazo no mayor a cinco días hábiles, a partir de la conclusión del periodo de la comisión, a entregar la totalidad de la documentación comprobatoria de conformidad a los "Lineamientos Generales para la Asignación y Control de Viáticos" y "Ley de Austeridad Durango", y desde este momento autorizo expresamente a que, en caso de incumplimiento parcial o total, el monto asignado me sea descontado de manera directa vía nómina, ya que de no comprobar o reintegrar el recurso ministrado corresponde un daño al Erario Público.
      </div>

      <!-- SECCIÓN 7: FIRMAS -->
      <div class="bloque-firmas">
        <div class="col-firma">
          <div class="firma-encabezado">AUTORIZA:</div>
          <div class="firma-nombre"><?= $escape(mb_strtoupper($director, 'UTF-8')) ?></div>
          <div class="firma-pie">DIRECTOR DE SISTEMAS Y TECNOLOGÍAS</div>
        </div>
        <div class="col-firma">
          <div class="firma-encabezado">RECIBE:</div>
          <div class="firma-nombre"><?= $escape(mb_strtoupper($solicitud['comisionado'], 'UTF-8')) ?></div>
          <div class="firma-pie">Nombre y Firma del Comisionado:</div>
        </div>
      </div>

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
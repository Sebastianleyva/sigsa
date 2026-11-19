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

    // Logotipo oficial Durango + SEED extraído de REQUISICION.xlsx
    $logoOficialBase64 = 'data:image/png;base64,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';

    header('Content-Type: text/html; charset=UTF-8');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= $escape($esMateriales ? 'Requisición de Materiales' : 'Requisición de Servicios') ?> #<?= $escape($id_requisicion) ?></title>
  <style>
    @page {
      size: letter portrait;
      margin: 11mm 13mm;
    }
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    html, body, table, tr, th, td, .gris, .gris-izq {
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 8.5pt;
      color: #000;
      background: #e9ecef;
    }
    .toolbar-print {
      text-align: center;
      padding: 12px;
      background: #172b27;
    }
    .btn-print {
      padding: 9px 18px;
      background: #176c58;
      color: #fff;
      border: 0;
      border-radius: 4px;
      font-size: 13px;
      font-weight: bold;
      cursor: pointer;
    }
    .btn-print:hover {
      background: #125646;
    }
    .hoja-carta {
      width: 215.9mm;
      min-height: 279.4mm;
      margin: 16px auto;
      padding: 11mm 13mm;
      background: #fff;
      box-shadow: 0 2px 10px rgba(0,0,0,0.15);
      display: flex;
      flex-direction: column;
    }
    .marco-documento {
      border: 1.5px solid #000;
      flex: 1;
      display: flex;
      flex-direction: column;
    }
    @media print {
      body {
        background: #fff;
      }
      .toolbar-print {
        display: none !important;
      }
      .hoja-carta {
        width: 100%;
        min-height: 256mm;
        margin: 0;
        padding: 0;
        box-shadow: none;
      }
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }
    td, th {
      border: 1px solid #000;
      padding: 2.5px 5px;
      vertical-align: middle;
    }

    /* Quitar borde exterior duplicado contra .marco-documento y evitar líneas dobles entre tablas apiladas */
    .marco-documento > table:first-child tr:first-child td,
    .marco-documento > table:first-child tr:first-child th { border-top: none; }
    .marco-documento > table tr td:first-child,
    .marco-documento > table tr th:first-child { border-left: none; }
    .marco-documento > table tr td:last-child,
    .marco-documento > table tr th:last-child { border-right: none; }
    .sin-borde-inferior td { border-bottom: none !important; }

    /* Color gris neutro exacto del Excel (garantizado al imprimir PDF) */
    .gris, .gris-izq, .tabla-partidas th {
      background: #bfbfbf !important;
      background-color: #bfbfbf !important;
      box-shadow: inset 0 0 0 1000px #bfbfbf !important;
      color: #000000 !important;
      font-weight: bold;
    }
    .gris { text-align: center; }
    .gris-izq { text-align: left; }
    .centro { text-align: center; }
    .negrita { font-weight: bold; }
    .sin-borde td { border: none !important; padding: 1px 2px; }

    /* Encabezado institucional con división logos / título */
    .celda-logo {
      padding: 4px 8px;
      text-align: center;
      vertical-align: middle;
    }
    .logo-oficial {
      max-height: 44px;
      max-width: 100%;
      object-fit: contain;
      display: inline-block;
    }
    .titulo-requisicion {
      font-size: 14.5pt;
      font-weight: bold;
      letter-spacing: 0.4px;
      text-align: center;
    }

    /* Tabla de partidas: las líneas verticales siempre bajan hasta JUSTIFICACIÓN */
    .contenedor-partidas {
      flex: 1 1 auto;
      display: flex;
      flex-direction: column;
    }
    .tabla-partidas {
      width: 100%;
      flex: 1 1 auto;
      height: 100%;
      border-collapse: collapse;
    }
    .tabla-partidas th {
      font-size: 7.5pt;
      text-align: center;
      border-top: 1px solid #000;
      border-bottom: 1px solid #000;
      padding: 3px 4px;
    }
    .tabla-partidas th:first-child { border-left: none; }
    .tabla-partidas th:last-child { border-right: none; }
    .tabla-partidas td {
      border-top: none;
      border-bottom: none;
      border-left: 1px solid #000;
      border-right: 1px solid #000;
      vertical-align: top;
      padding: 3px 6px;
      font-size: 8pt;
    }
    .tabla-partidas td:first-child { border-left: none; }
    .tabla-partidas td:last-child { border-right: none; }
    .tabla-partidas tbody tr:not(.fila-relleno) td {
      height: 1%;
    }
    .tabla-partidas tr.fila-relleno td {
      height: 99%;
      min-height: 20px;
    }

    /* Justificación */
    .caja-justificacion {
      min-height: 68px;
      padding: 8px 14px;
      text-align: center;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 8.5pt;
    }

    /* Sellos */
    .caja-sello {
      height: 70px;
      text-align: center;
      font-size: 7.5pt;
      color: #444;
    }
    .caja-responsabilidad {
      height: 70px;
      text-align: center;
      font-weight: bold;
      font-size: 7.8pt;
      line-height: 1.3;
    }

    /* Firmas: espacio para firma + celda de nombre + celda de cargo */
    .espacio-firma {
      height: 52px;
      border-bottom: 1px solid #000 !important;
    }
    .celda-nombre-firma {
      height: 18px;
      border-top: 1px solid #000 !important;
      border-bottom: 1px solid #000 !important;
      text-align: center;
      font-weight: bold;
      font-size: 7.5pt;
      padding: 2.5px 4px;
    }
    .celda-cargo-firma {
      height: 17px;
      border-top: 1px solid #000 !important;
      border-bottom: 1px solid #000 !important;
      text-align: center;
      font-size: 7pt;
      padding: 2px 4px;
    }
  </style>
</head>
<body>

  <div class="toolbar-print">
    <button class="btn-print" onclick="window.print()">Imprimir / Guardar como PDF</button>
  </div>

  <div class="hoja-carta">
    <div class="marco-documento">

      <?php if (!$esMateriales): ?>
        <!-- ========================================================================= -->
        <!-- FORMATO 1: REQUISICIÓN DE SERVICIOS (HOJA "SERVICIO" DE EXCEL)            -->
        <!-- ========================================================================= -->
        <table class="sin-borde-inferior">
          <tr>
            <td class="celda-logo" style="width: 34%;">
              <img class="logo-oficial" src="<?= $logoOficialBase64 ?>" alt="Durango · SEED">
            </td>
            <td class="titulo-requisicion" style="width: 66%;">
              REQUISICIÓN DE SERVICIOS
            </td>
          </tr>
        </table>

        <table class="sin-borde-inferior">
          <tr>
            <td class="gris" style="width: 68%;">ÁREA SOLICITANTE</td>
            <td class="gris-izq" style="width: 10%;">FOLIO:</td>
            <td style="width: 22%;"></td>
          </tr>
          <tr>
            <td class="centro negrita">DIRECCIÓN DE SISTEMAS Y TECNOLOGÍAS</td>
            <td colspan="2" style="padding: 0;">
              <table>
                <tr>
                  <td class="gris" style="border-top: none; border-left: none; width: 33%;">DIA</td>
                  <td class="gris" style="border-top: none; width: 33%;">MES</td>
                  <td class="gris" style="border-top: none; border-right: none; width: 34%;">AÑO</td>
                </tr>
                <tr>
                  <td class="centro" style="border-bottom: none; border-left: none;"><?= $escape($dia) ?></td>
                  <td class="centro" style="border-bottom: none;"><?= $escape($mes) ?></td>
                  <td class="centro" style="border-bottom: none; border-right: none;"><?= $escape($anio) ?></td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td class="gris">CUENTA (S) CARGO:</td>
            <td colspan="2" class="gris">FINANCIAMIENTO:</td>
          </tr>
          <tr>
            <td class="centro" style="font-size: 8pt;"><?= $escape($cuentaCargo) ?></td>
            <td colspan="2" style="padding: 2px 4px; font-size: 7.5pt;">
              <table class="sin-borde">
                <tr>
                  <td class="centro"><strong>[ X ]</strong><br>FONE</td>
                  <td class="centro">[ &nbsp; ]<br>EST</td>
                  <td class="centro">[ &nbsp; ]<br>FED</td>
                  <td class="centro">[ &nbsp; ]<br>ING</td>
                </tr>
              </table>
            </td>
          </tr>
        </table>

        <table class="sin-borde-inferior">
          <tr><td colspan="2" class="gris">SOLO PARA PROGRAMAS FEDERALES</td></tr>
          <tr><td class="gris-izq" style="width: 24%;">1.- RUBRO:</td><td class="centro">N/A</td></tr>
          <tr><td class="gris-izq">2.- ACCIÓN:</td><td class="centro">N/A</td></tr>
          <tr><td class="gris-izq">3.- OBJETIVO:</td><td class="centro">N/A</td></tr>
          <tr><td class="gris-izq">4.- META:</td><td class="centro">N/A</td></tr>
          <tr><td class="gris-izq">5.- PROYECTO:</td><td class="centro">N/A</td></tr>
        </table>

        <div class="contenedor-partidas">
          <table class="tabla-partidas">
            <thead>
              <tr>
                <th style="width: 10%;">NUM.<br>LOTE</th>
                <th style="width: 12%;">CANTIDAD</th>
                <th style="width: 78%;">DESCRIPCION DEL SERVICIO Y/O ESPECIFICACIONES</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($detalles as $detalle): ?>
                <?php
                  $esEncabezado = ($detalle['num_lote'] === null || $detalle['num_lote'] === '')
                               && ($detalle['cantidad'] === null || $detalle['cantidad'] === '');
                ?>
                <tr>
                  <td class="centro"><?= $escape($detalle['num_lote']) ?></td>
                  <td class="centro"><?= $escape($detalle['cantidad']) ?></td>
                  <td class="<?= $esEncabezado ? 'negrita' : '' ?>"><?= nl2br($escape($detalle['descripcion'])) ?></td>
                </tr>
              <?php endforeach; ?>
              <tr class="fila-relleno"><td></td><td></td><td></td></tr>
            </tbody>
          </table>
        </div>

        <table>
          <tr><td class="gris">JUSTIFICACIÓN DEL SERVICIO:</td></tr>
        </table>
        <div class="caja-justificacion">
          <div><?= nl2br($escape($requisicion['justificacion'])) ?></div>
        </div>

        <table class="sin-borde-inferior">
          <tr>
            <td rowspan="2" class="gris" style="width: 72%; font-size: 7.5pt; line-height: 1.25;">
              SERÁ OBLIGATORIO PRESENTAR EVIDENCIAS FOTOGRÁFICA, LISTAS DE ASISTENCIA Y BITÁCORAS DE ÓRDEN DEL DÍA DE EVENTO, ETC.
            </td>
            <td class="gris" style="width: 28%;">FECHA DE ENTREGA:</td>
          </tr>
          <tr>
            <td class="centro" style="height: 20px;"><?= $escape($fechaEntregaTexto) ?></td>
          </tr>
        </table>

        <table class="sin-borde-inferior">
          <tr>
            <td class="gris caja-sello" style="width: 33%;">(SELLO DE MODULO DE REQUISICIONES)</td>
            <td class="caja-responsabilidad" style="width: 34%;">
              LA SOLICITUD DE LOS SERVICIOS ES RESPONSABILIDAD DEL ÁREA SOLICITANTE
              <br><br>
              SUBDIRECCIÓN DE ADMINISTRACIÓN
            </td>
            <td class="gris caja-sello" style="width: 33%;">(SELLO DE SERVICIOS)</td>
          </tr>
        </table>

        <table class="sin-borde-inferior">
          <tr>
            <td class="gris" style="width: 50%;">SOLICITA</td>
            <td class="gris" style="width: 50%;">AUTORIZA</td>
          </tr>
          <tr>
            <td class="espacio-firma"></td>
            <td class="espacio-firma"></td>
          </tr>
          <tr>
            <td class="celda-nombre-firma"><?= $escape(mb_strtoupper((string) $requisicion['solicitante'], 'UTF-8')) ?></td>
            <td class="celda-nombre-firma"><?= $escape(mb_strtoupper((string) $requisicion['autorizador'], 'UTF-8')) ?></td>
          </tr>
          <tr>
            <td class="celda-cargo-firma"><?= $escape(mb_strtoupper($puestoSolicitante, 'UTF-8')) ?></td>
            <td class="celda-cargo-firma"><?= $escape(mb_strtoupper($puestoAutorizador, 'UTF-8')) ?></td>
          </tr>
        </table>

        <table>
          <tr>
            <td class="gris" style="width: 38%;">RESPONSABLE DE ELABORAR EL TRAMITE:</td>
            <td class="gris" style="width: 47%;">E-MAIL</td>
            <td style="width: 15%;"></td>
          </tr>
          <tr>
            <td class="centro"><?= $escape($requisicion['elaborador']) ?></td>
            <td class="centro"><?= $escape($requisicion['correo_elaborador']) ?></td>
            <td class="centro"><?= $escape($requisicion['telefono_elaborador']) ?></td>
          </tr>
          <tr>
            <td class="gris" style="border-bottom: none;">NOMBRE</td>
            <td class="gris" style="border-bottom: none;">FIRMA</td>
            <td class="gris" style="border-bottom: none;">CISCO</td>
          </tr>
        </table>

      <?php else: ?>
        <!-- ========================================================================= -->
        <!-- FORMATO 2: REQUISICIÓN DE MATERIALES (HOJA "REQUISICIÓN" DE EXCEL)        -->
        <!-- ========================================================================= -->
        <table class="sin-borde-inferior">
          <tr>
            <td rowspan="2" class="celda-logo" style="width: 34%;">
              <img class="logo-oficial" src="<?= $logoOficialBase64 ?>" alt="Durango · SEED">
            </td>
            <td class="gris" style="width: 42%; font-size: 13pt;">REQUISICIÓN</td>
            <td class="gris-izq" style="width: 10%;">FOLIO:</td>
            <td style="width: 14%;"></td>
          </tr>
          <tr>
            <td colspan="3" style="padding: 0;">
              <table>
                <tr>
                  <td class="gris" style="border: none; width: 42%; font-size: 7.5pt;">MATERIALES Y SUMINISTROS</td>
                  <td class="centro negrita" style="border-top: none; border-bottom: none; width: 8%;">X</td>
                  <td class="gris" style="border-top: none; border-bottom: none; width: 42%; font-size: 7.5pt;">ACTIVO</td>
                  <td class="centro" style="border: none; width: 8%;"></td>
                </tr>
              </table>
            </td>
          </tr>
        </table>

        <table class="sin-borde-inferior">
          <tr>
            <td class="gris" style="width: 72%;">ÁREA SOLICITANTE</td>
            <td class="gris-izq" style="width: 8%;">HOJA:</td>
            <td class="centro" style="width: 6%;">1</td>
            <td class="gris" style="width: 8%;">DE :</td>
            <td class="centro" style="width: 6%;">1</td>
          </tr>
          <tr>
            <td class="centro negrita">DIRECCIÓN DE SISTEMAS Y TECNOLOGÍAS DE LA INFORMACIÓN</td>
            <td colspan="4" class="gris">FECHA</td>
          </tr>
          <tr>
            <td class="gris">CUENTA (S) CARGO:</td>
            <td colspan="4" style="padding: 0;">
              <table>
                <tr>
                  <td class="gris" style="border-top: none; border-left: none; width: 33%;">DÍA</td>
                  <td class="gris" style="border-top: none; width: 33%;">MES</td>
                  <td class="gris" style="border-top: none; border-right: none; width: 34%;">AÑO</td>
                </tr>
                <tr>
                  <td class="centro" style="border-bottom: none; border-left: none;"><?= $escape($dia) ?></td>
                  <td class="centro" style="border-bottom: none;"><?= $escape($mes) ?></td>
                  <td class="centro" style="border-bottom: none; border-right: none;"><?= $escape($anio) ?></td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td class="centro" style="font-size: 8pt;"><?= $escape($cuentaCargo) ?></td>
            <td colspan="4" style="padding: 2px 4px; font-size: 7.5pt;">
              <table class="sin-borde">
                <tr>
                  <td class="centro"><strong>[ X ]</strong><br>FONE</td>
                  <td class="centro">[ &nbsp; ]<br>EST</td>
                  <td class="centro">[ &nbsp; ]<br>FED</td>
                  <td class="centro">[ &nbsp; ]<br>ING</td>
                </tr>
              </table>
            </td>
          </tr>
        </table>

        <table class="sin-borde-inferior">
          <tr><td colspan="2" class="gris">*SOLO PARA PROGRAMAS FEDERALES*</td></tr>
          <tr><td class="gris-izq" style="width: 20%;">RUBRO:</td><td class="centro">n/a</td></tr>
          <tr><td class="gris-izq">ACCIÓN:</td><td class="centro">n/a</td></tr>
          <tr><td class="gris-izq">OBJETIVO:</td><td class="centro">n/a</td></tr>
          <tr><td class="gris-izq">META:</td><td class="centro">n/a</td></tr>
          <tr><td class="gris-izq">PROYECTO:</td><td class="centro">n/a</td></tr>
        </table>

        <div class="contenedor-partidas">
          <table class="tabla-partidas">
            <thead>
              <tr>
                <th style="width: 8%;">NUM.<br>LOTE</th>
                <th style="width: 8%;">CANT.</th>
                <th style="width: 8%;">UDM</th>
                <th style="width: 63%;">DESCRIPCIÓN COMPLETA DEL ARTÍCULO<br><span style="font-weight: normal; font-size: 6.5pt;">(INCLUYENDO DATOS TÉCNICOS CUANDO SE REQUIERAN)</span></th>
                <th style="width: 13%;">PARTIDA</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($detalles as $idx => $detalle): ?>
                <?php
                  $esEncabezado = ($detalle['num_lote'] === null || $detalle['num_lote'] === '')
                               && ($detalle['cantidad'] === null || $detalle['cantidad'] === '');
                ?>
                <tr>
                  <td class="centro"><?= $escape($detalle['num_lote']) ?></td>
                  <td class="centro"><?= $escape($detalle['cantidad']) ?></td>
                  <td class="centro"><?= $esEncabezado ? '' : 'pza' ?></td>
                  <td class="<?= $esEncabezado ? 'negrita' : '' ?>"><?= nl2br($escape($detalle['descripcion'])) ?></td>
                  <td class="centro"><?= $idx === 0 ? $escape($partidaPresupuestal) : '' ?></td>
                </tr>
              <?php endforeach; ?>
              <tr class="fila-relleno"><td></td><td></td><td></td><td></td><td></td></tr>
            </tbody>
          </table>
        </div>

        <table>
          <tr><td class="gris-izq">JUSTIFICACIÓN DE LA COMPRA (ESPECIFICAR) :</td></tr>
        </table>
        <div class="caja-justificacion">
          <div><?= nl2br($escape($requisicion['justificacion'])) ?></div>
        </div>

        <table class="sin-borde-inferior">
          <tr>
            <td class="caja-sello" style="width: 33%;">(SELLO DE MÓDULO DE REQUISICIONES)</td>
            <td class="caja-responsabilidad" style="width: 34%;">
              LA SOLICITUD PARA LA ADQUISICIÓN Y USO DE LOS MATERIALES Y SUMINISTROS ES RESPONSABILIDAD DEL ÁREA SOLICITANTE
              <br><br>
              SUBDIRECCIÓN DE ADMINISTRACIÓN
            </td>
            <td class="caja-sello" style="width: 33%;">(SELLO DE ADQUISICIONES)</td>
          </tr>
        </table>

        <table class="sin-borde-inferior">
          <tr>
            <td class="gris" style="width: 28%;">DOCUMENTACIÓN ADJUNTA</td>
            <td class="gris" style="width: 36%;">SOLICITA:</td>
            <td class="gris" style="width: 36%;">AUTORIZA:</td>
          </tr>
          <tr>
            <td rowspan="3" style="padding: 3px 6px; font-size: 7pt; line-height: 1.5; vertical-align: middle;">
              [ &nbsp; ] APOYO<br>
              [ &nbsp; ] RELACIÓN DE BENEFICIARIOS<br>
              [ &nbsp; ] CONVOCATORIAS<br>
              [ &nbsp; ] MUESTRAS<br>
              [ &nbsp; ] ORDEN DEL DÍA
            </td>
            <td class="espacio-firma"></td>
            <td class="espacio-firma"></td>
          </tr>
          <tr>
            <td class="celda-nombre-firma"><?= $escape(mb_strtoupper((string) $requisicion['solicitante'], 'UTF-8')) ?></td>
            <td class="celda-nombre-firma"><?= $escape(mb_strtoupper((string) $requisicion['autorizador'], 'UTF-8')) ?></td>
          </tr>
          <tr>
            <td class="celda-cargo-firma"><?= $escape(mb_strtoupper($puestoSolicitante, 'UTF-8')) ?></td>
            <td class="celda-cargo-firma"><?= $escape(mb_strtoupper($puestoAutorizador, 'UTF-8')) ?></td>
          </tr>
        </table>

        <table>
          <tr>
            <td class="gris" style="width: 36%;">RESPONSABLE DEL TRÁMITE:</td>
            <td class="gris" style="width: 12%;">E-MAIL</td>
            <td class="centro" style="width: 52%;"><?= $escape($requisicion['correo_elaborador']) ?></td>
          </tr>
          <tr>
            <td class="centro"><?= $escape($requisicion['elaborador']) ?></td>
            <td colspan="2" style="padding: 0;">
              <table>
                <tr>
                  <td class="centro" style="border: none; width: 72%; height: 18px;"></td>
                  <td class="centro" style="border-top: none; border-bottom: none; border-right: none; width: 28%;"><?= $escape($requisicion['telefono_elaborador']) ?></td>
                </tr>
              </table>
            </td>
          </tr>
          <tr>
            <td class="gris" style="border-bottom: none;">NOMBRE</td>
            <td colspan="2" style="padding: 0;">
              <table>
                <tr>
                  <td class="gris" style="border: none; width: 72%;">FIRMA</td>
                  <td class="gris" style="border-top: none; border-bottom: none; border-right: none; width: 28%;">CISCO</td>
                </tr>
              </table>
            </td>
          </tr>
        </table>
      <?php endif; ?>

    </div>
  </div>

</body>
</html>
    <?php
} catch (Throwable $e) {
    error_log('Error al generar documento de requisición: ' . $e->getMessage());
    http_response_code(500);
    exit('No fue posible generar el documento');
}
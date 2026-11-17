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
            CONCAT_WS(' ', pa.nombre, pa.apellido_p, pa.apellido_m) AS autorizador,
            CONCAT_WS(' ', pe.nombre, pe.apellido_p, pe.apellido_m) AS elaborador,
            pe.correo AS correo_elaborador,
            pe.telefono AS telefono_elaborador
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

    // Separar fecha (YYYY-MM-DD) en DÍA, MES y AÑO como en el formato oficial
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

    // Formatear fecha de entrega si existe (si es null se deja en blanco)
    $fechaEntregaTexto = '';
    if (!empty($requisicion['fecha_entrega'])) {
        $partesEntrega = explode('-', (string) $requisicion['fecha_entrega']);
        $fechaEntregaTexto = count($partesEntrega) === 3
            ? "{$partesEntrega[2]}/{$partesEntrega[1]}/{$partesEntrega[0]}"
            : (string) $requisicion['fecha_entrega'];
    }

    // Opcional: Pega aquí tus logos en Base64 (ej. 'data:image/png;base64,...') para que carguen dentro del Blob
    $logoDurangoBase64 = '';
    $logoSeedBase64    = '';

    header('Content-Type: text/html; charset=UTF-8');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Requisición de Servicios #<?= $escape($id_requisicion) ?></title>
  <style>
    @page {
      size: letter portrait;
      margin: 0;
    }
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
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
      padding: 10mm 12mm;
      background: #fff;
      box-shadow: 0 2px 10px rgba(0,0,0,0.15);
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
        min-height: 279.4mm;
        margin: 0;
        padding: 10mm 12mm;
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
    .gris {
      background-color: #d9d9d9;
      font-weight: bold;
      text-align: center;
    }
    .gris-izq {
      background-color: #d9d9d9;
      font-weight: bold;
      text-align: left;
    }
    .centro { text-align: center; }
    .negrita { font-weight: bold; }
    .sin-borde td { border: none; padding: 1px 2px; }

    /* Encabezado institucional */
    .header-doc {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 6px;
    }
    .logos-institucionales {
      display: flex;
      align-items: center;
      gap: 14px;
    }
    .logos-institucionales img {
      height: 44px;
      object-fit: contain;
    }
    .logo-texto-fallback {
      font-weight: bold;
      font-size: 11pt;
      line-height: 1.1;
      border-left: 3px solid #000;
      padding-left: 8px;
    }
    .logo-texto-fallback span {
      display: block;
      font-size: 7pt;
      font-weight: normal;
    }
    .titulo-requisicion {
      font-size: 14.5pt;
      font-weight: bold;
      letter-spacing: 0.4px;
    }

    /* Tabla de partidas (sin líneas horizontales entre filas) */
    .contenedor-partidas {
      flex: 1;
      display: flex;
      flex-direction: column;
      margin-top: 4px;
    }
    .tabla-partidas {
      height: 100%;
      border: 1px solid #000;
    }
    .tabla-partidas th {
      background-color: #d9d9d9;
      font-size: 8pt;
      text-align: center;
      border-bottom: 1px solid #000;
      padding: 3px 4px;
    }
    .tabla-partidas td {
      border-top: none;
      border-bottom: none;
      border-left: 1px solid #000;
      border-right: 1px solid #000;
      vertical-align: top;
      padding: 3px 6px;
      font-size: 8pt;
    }
    .fila-relleno td {
      height: 100%;
      min-height: 150px;
    }

    /* Justificación */
    .caja-justificacion {
      border: 1px solid #000;
      border-top: none;
      min-height: 76px;
      padding: 8px 14px;
      text-align: center;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 8.5pt;
    }

    /* Sellos */
    .caja-sello {
      height: 74px;
      text-align: center;
      font-size: 7.5pt;
      color: #666;
    }
    .caja-responsabilidad {
      height: 74px;
      text-align: center;
      font-weight: bold;
      font-size: 8pt;
      line-height: 1.3;
    }

    /* Firmas */
    .caja-firma {
      height: 68px;
      vertical-align: bottom;
      text-align: center;
      padding-bottom: 4px;
    }
    .linea-firma {
      border-top: 1px solid #000;
      width: 82%;
      margin: 0 auto 3px auto;
    }
  </style>
</head>
<body>

  <div class="toolbar-print">
    <button class="btn-print" onclick="window.print()">Imprimir / Guardar como PDF</button>
  </div>

  <div class="hoja-carta">
    <!-- ENCABEZADO -->
    <div class="header-doc">
      <div class="logos-institucionales">
        <?php if ($logoDurangoBase64 !== ''): ?>
          <img src="<?= $escape($logoDurangoBase64) ?>" alt="Gobierno del Estado de Durango">
        <?php endif; ?>
        <?php if ($logoSeedBase64 !== ''): ?>
          <img src="<?= $escape($logoSeedBase64) ?>" alt="SEED">
        <?php else: ?>
          <div class="logo-texto-fallback">
            SEED
            <span>SECRETARÍA DE EDUCACIÓN DEL ESTADO DE DURANGO</span>
          </div>
        <?php endif; ?>
      </div>
      <div class="titulo-requisicion">REQUISICIÓN DE SERVICIOS</div>
    </div>

    <!-- DATOS GENERALES -->
    <table>
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
        <td class="centro" style="font-size: 8pt;">
          1-99999-51351001-5101-25021-1-2110008020010-2501-134020506-5050001-61015-015
        </td>
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

    <!-- SOLO PARA PROGRAMAS FEDERALES -->
    <table style="margin-top: 4px;">
      <tr>
        <td colspan="2" class="gris">SOLO PARA PROGRAMAS FEDERALES</td>
      </tr>
      <tr>
        <td class="gris-izq" style="width: 24%;">1.- RUBRO:</td>
        <td class="centro">N/A</td>
      </tr>
      <tr>
        <td class="gris-izq">2.- ACCIÓN:</td>
        <td class="centro">N/A</td>
      </tr>
      <tr>
        <td class="gris-izq">3.- OBJETIVO:</td>
        <td class="centro">N/A</td>
      </tr>
      <tr>
        <td class="gris-izq">4.- META:</td>
        <td class="centro">N/A</td>
      </tr>
      <tr>
        <td class="gris-izq">5.- PROYECTO:</td>
        <td class="centro">N/A</td>
      </tr>
    </table>

    <!-- PARTIDAS Y CONCEPTOS -->
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
          <tr class="fila-relleno">
            <td></td>
            <td></td>
            <td></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- JUSTIFICACIÓN DEL SERVICIO -->
    <table style="margin-top: 4px;">
      <tr>
        <td class="gris">JUSTIFICACIÓN DEL SERVICIO:</td>
      </tr>
    </table>
    <div class="caja-justificacion">
      <div><?= nl2br($escape($requisicion['justificacion'])) ?></div>
    </div>

    <!-- EVIDENCIAS Y FECHA DE ENTREGA -->
    <table style="margin-top: 4px;">
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

    <!-- SELLOS -->
    <table style="margin-top: 4px;">
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

    <!-- FIRMAS SOLICITA Y AUTORIZA -->
    <table>
      <tr>
        <td class="gris" style="width: 50%; border-top: none;">SOLICITA</td>
        <td class="gris" style="width: 50%; border-top: none;">AUTORIZA</td>
      </tr>
      <tr>
        <td class="caja-firma">
          <div class="linea-firma"></div>
          <div class="negrita"><?= $escape(mb_strtoupper((string) $requisicion['solicitante'], 'UTF-8')) ?></div>
        </td>
        <td class="caja-firma">
          <div class="linea-firma"></div>
          <div class="negrita"><?= $escape(mb_strtoupper((string) $requisicion['autorizador'], 'UTF-8')) ?></div>
          <div style="font-size: 7.5pt;">DIRECTOR DE SISTEMAS Y TECNOLOGÍAS</div>
        </td>
      </tr>
    </table>

    <!-- RESPONSABLE DE ELABORAR EL TRÁMITE -->
    <table>
      <tr>
        <td class="gris" style="width: 38%; border-top: none;">RESPONSABLE DE ELABORAR EL TRAMITE:</td>
        <td class="gris" style="width: 47%; border-top: none;">E-MAIL</td>
        <td style="width: 15%; border-top: none;"></td>
      </tr>
      <tr>
        <td class="centro"><?= $escape($requisicion['elaborador']) ?></td>
        <td class="centro"><?= $escape($requisicion['correo_elaborador']) ?></td>
        <td class="centro"><?= $escape($requisicion['telefono_elaborador']) ?></td>
      </tr>
      <tr>
        <td class="gris">NOMBRE</td>
        <td class="gris">FIRMA</td>
        <td class="gris">CISCO</td>
      </tr>
    </table>
  </div>

</body>
</html>
    <?php
} catch (Throwable $e) {
    error_log('Error al generar documento de requisición: ' . $e->getMessage());
    http_response_code(500);
    exit('No fue posible generar el documento');
}
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

    // Logotipos oficiales extraídos de orden de servicio.xlsx (PNG verificados)
    $logoDurangoBase64 = 'data:image/png;base64,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';
    $logoSeedBase64 = 'data:image/png;base64,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';

    header('Content-Type: text/html; charset=UTF-8');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Orden de servicio #<?= $escape($orden['n_reporte']) ?></title>
  <style>
    @page {
      size: letter portrait;
      margin: 10mm 13mm;
    }
    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    html, body, table, tr, th, td, .barra-gris {
      -webkit-print-color-adjust: exact !important;
      print-color-adjust: exact !important;
    }
    body {
      font-family: Arial, Helvetica, sans-serif;
      font-size: 9pt;
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
      padding: 10mm 13mm;
      background: #fff;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
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
        min-height: 258mm;
        margin: 0;
        padding: 0;
        box-shadow: none;
      }
    }

    /* Encabezado superior con logos laterales y títulos institucionales */
    .encabezado-institucional {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 10px;
      margin-bottom: 6px;
    }
    .logo-izq,
    .logo-der {
      height: 54px;
      object-fit: contain;
    }
    .titulos-centro {
      flex: 1;
      text-align: center;
      line-height: 1.3;
    }
    .titulos-centro .sec {
      font-size: 10pt;
      font-weight: bold;
      letter-spacing: 0.3px;
    }
    .titulos-centro .dir {
      font-size: 9.5pt;
      font-weight: bold;
    }
    .titulos-centro .doc-titulo {
      margin-top: 8px;
      font-size: 13pt;
      font-weight: bold;
      letter-spacing: 0.6px;
    }

    /* Fila Fecha y No. de Reporte con rectángulos completos */
    .fila-meta {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 4px 10px 6px 10px;
      font-size: 9pt;
    }
    .grupo-meta {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .caja-meta {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 125px;
      height: 22px;
      border: 1px solid #000;
      padding: 2px 8px;
      font-weight: bold;
      background: #fff;
    }
    .caja-meta.reporte {
      min-width: 95px;
    }

    /* Marco exterior único del formato */
    .marco-orden {
      border: 1px solid #000;
      flex: 1;
      display: flex;
      flex-direction: column;
    }

    /* Barras de sección gris neutro exacto del Excel */
    .barra-gris {
      background: #bfbfbf !important;
      background-color: #bfbfbf !important;
      box-shadow: inset 0 0 0 1000px #bfbfbf !important;
      color: #000 !important;
      font-weight: bold;
      font-size: 9pt;
      padding: 3px 10px;
      border-top: 1px solid #000;
      border-bottom: 1px solid #000;
      text-align: center;
    }
    .barra-gris.primera {
      border-top: none;
    }
    .barra-gris.centrada {
      text-align: center;
    }

    /* Bloque 1: Datos de la Institución con cajas rectangulares cerradas */
    .bloque-institucion {
      padding: 8px 14px 10px 14px;
      display: flex;
      flex-direction: column;
      gap: 6px;
      font-size: 8.8pt;
    }
    .fila-dato {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .fila-dato .etiqueta {
      font-weight: bold;
      white-space: nowrap;
      min-width: 82px;
    }
    .fila-dato .caja-valor {
      flex: 1;
      border: 1px solid #000;
      padding: 2px 7px;
      min-height: 21px;
      display: flex;
      align-items: center;
      background: #fff;
      overflow-wrap: anywhere;
    }
    .fila-doble {
      display: flex;
      gap: 14px;
    }
    .fila-doble .col-izq {
      flex: 0 0 36%;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .fila-doble .col-izq .etiqueta {
      min-width: 82px;
    }
    .fila-doble .col-der {
      flex: 1;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .fila-doble .col-der .etiqueta {
      min-width: auto;
    }
    .fila-mitad {
      display: flex;
      gap: 14px;
    }
    .fila-mitad > div {
      flex: 1;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .fila-mitad > div:first-child .etiqueta {
      min-width: 82px;
    }
    .fila-mitad > div:last-child .etiqueta {
      min-width: auto;
    }

    /* Bloques de texto libre (Falla reportada, Actividad realizada, Observaciones) */
    .caja-texto-libre {
      padding: 7px 14px;
      font-size: 8.8pt;
      line-height: 1.35;
      overflow-wrap: anywhere;
    }
    .caja-falla {
      min-height: 58px;
    }
    .caja-actividad {
      flex: 1;
      min-height: 120px;
    }
    .caja-observaciones {
      min-height: 60px;
      border-bottom: 1px solid #000;
    }

    /* Tabla de equipos atendidos */
    .contenedor-equipos {
      padding: 6px 10px;
    }
    .tabla-equipos {
      width: 100%;
      border-collapse: collapse;
      font-size: 8pt;
    }
    .tabla-equipos td {
      border: 1px solid #000;
      padding: 4px 6px;
      font-weight: bold;
    }
    .tabla-equipos .valor-num {
      font-weight: normal;
      margin-left: 6px;
    }

    /* Pie de firmas y sello dentro del marco */
    .bloque-firmas {
      padding: 12px 16px 8px 16px;
      display: flex;
      flex-direction: column;
    }
    .fila-firmas-sello {
      display: flex;
      align-items: flex-end;
      justify-content: space-between;
      gap: 16px;
    }
    .grid-firmas {
      flex: 1;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 28px;
      text-align: center;
      padding-bottom: 6px;
    }
    .firma-item {
      display: flex;
      flex-direction: column;
      align-items: center;
    }
    .espacio-firma {
      height: 54px;
      width: 100%;
    }
    .linea-firma {
      width: 92%;
      border-top: 1px solid #000;
      margin-bottom: 3px;
    }
    .firma-titulo {
      font-weight: bold;
      font-size: 8.5pt;
    }
    .firma-subtitulo {
      font-size: 8pt;
      margin-top: 1px;
    }
    .caja-sello-institucion {
      width: 126px;
      height: 92px;
      border: 1px solid #8c8c8c;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      font-size: 8.5pt;
      color: #8c8c8c;
      padding: 6px;
      line-height: 1.25;
      flex-shrink: 0;
    }
    .notas-pie {
      margin-top: 8px;
      font-size: 7.5pt;
      line-height: 1.35;
    }

    /* Pie institucional fuera del recuadro */
    .pie-direccion {
      display: flex;
      justify-content: space-between;
      padding: 4px 10px 0 10px;
      font-size: 8pt;
      font-weight: bold;
    }
  </style>
</head>
<body>

  <div class="toolbar-print">
    <button type="button" class="btn-print" onclick="window.print()">Imprimir / Guardar como PDF</button>
  </div>

  <main class="hoja-carta">

    <!-- ENCABEZADO CON LOGOS OFICIALES -->
    <header class="encabezado-institucional">
      <img class="logo-izq" src="<?= $logoDurangoBase64 ?>" alt="Durango · Gobierno del Estado">
      <div class="titulos-centro">
        <div class="sec">SECRETARIA DE EDUCACION DEL ESTADO DE DURANGO</div>
        <div class="dir">DIRECCIÓN DE SISTEMAS Y TECNOLOGÍAS<br>DE LA INFORMACIÓN</div>
        <div class="doc-titulo">ORDEN DE SERVICIO</div>
      </div>
      <img class="logo-der" src="<?= $logoSeedBase64 ?>" alt="SEED">
    </header>

    <!-- FECHA Y NÚMERO DE REPORTE EN RECTÁNGULOS -->
    <div class="fila-meta">
      <div class="grupo-meta">
        <strong>Fecha:</strong>
        <span class="caja-meta"><?= $escape($fecha_texto) ?></span>
      </div>
      <div class="grupo-meta">
        <strong>No. de Reporte:</strong>
        <span class="caja-meta reporte"><?= $escape($orden['n_reporte']) ?></span>
      </div>
    </div>

    <!-- RECUADRO EXTERIOR PRINCIPAL -->
    <div class="marco-orden">

      <!-- DATOS DE LA INSTITUCIÓN -->
      <div class="barra-gris primera centrada">DATOS DE LA INSTITUCION</div>
      <div class="bloque-institucion">
        <div class="fila-dato">
          <span class="etiqueta">Responsable:</span>
          <span class="caja-valor"><?= $campoTexto('responsable_institucion') ?></span>
        </div>
        <div class="fila-dato">
          <span class="etiqueta">Institución:</span>
          <span class="caja-valor"><?= $campoTexto('nombre_institucion') ?></span>
        </div>
        <div class="fila-dato fila-doble">
          <div class="col-izq">
            <span class="etiqueta">Clave:</span>
            <span class="caja-valor"><?= $campoTexto('clave_institucion') ?></span>
          </div>
          <div class="col-der">
            <span class="etiqueta">Domicilio:</span>
            <span class="caja-valor"><?= $campoTexto('domicilio_institucion') ?></span>
          </div>
        </div>
        <div class="fila-dato fila-mitad">
          <div>
            <span class="etiqueta">Localidad:</span>
            <span class="caja-valor"><?= $campoTexto('localidad_institucion') ?></span>
          </div>
          <div>
            <span class="etiqueta">Municipio:</span>
            <span class="caja-valor"><?= $campoTexto('municipio_institucion') ?></span>
          </div>
        </div>
        <div class="fila-dato fila-mitad">
          <div>
            <span class="etiqueta">Tel:</span>
            <span class="caja-valor"><?= $campoTexto('tel_institucion') ?></span>
          </div>
          <div></div>
        </div>
      </div>

      <!-- FALLA REPORTADA -->
      <div class="barra-gris">FALLA REPORTADA</div>
      <div class="caja-texto-libre caja-falla"><?= $campoTexto('falla_reportada') ?></div>

      <!-- ACTIVIDAD REALIZADA -->
      <div class="barra-gris">ACTIVIDAD REALIZADA</div>
      <div class="caja-texto-libre caja-actividad"><?= $campoTexto('actividad_realizada') ?></div>

      <!-- EQUIPO ATENDIDO -->
      <div class="contenedor-equipos">
        <table class="tabla-equipos">
          <tr>
            <td style="width: 34%; text-align: center;">No. DE EQUIPOS ATENDIDOS</td>
            <td style="width: 18%;">CPU: <span class="valor-num"><?= $cantidad('cpu') ?></span></td>
            <td style="width: 24%;">PROYECTORES: <span class="valor-num"><?= $cantidad('proyectores') ?></span></td>
            <td style="width: 24%;">IMPRESORAS: <span class="valor-num"><?= $cantidad('impresoras') ?></span></td>
          </tr>
          <tr>
            <td colspan="2">No. DE ALUMNOS BENEFICIADOS: <span class="valor-num"><?= $cantidad('alumnos_benefi') ?></span></td>
            <td colspan="2">DONACION: <span class="valor-num"><?= $cantidad('donacion') ?></span></td>
          </tr>
        </table>
      </div>

      <!-- OBSERVACIONES -->
      <div class="barra-gris">OBSERVACIONES</div>
      <div class="caja-texto-libre caja-observaciones"><?= $campoTexto('observaciones') ?></div>

      <!-- FIRMAS, SELLO DE LA INSTITUCIÓN Y NOTAS -->
      <div class="bloque-firmas">
        <div class="fila-firmas-sello">
          <div class="grid-firmas">
            <div class="firma-item">
              <div class="espacio-firma"></div>
              <div class="linea-firma"></div>
              <div class="firma-titulo">D.S. y T.I.</div>
            </div>
            <div class="firma-item">
              <div class="espacio-firma"></div>
              <div class="linea-firma"></div>
              <div class="firma-titulo">Responsable de la Institución</div>
              <div class="firma-subtitulo">Nombre y Puesto</div>
            </div>
          </div>

          <div class="caja-sello-institucion">
            Sello de<br>la Institución
          </div>
        </div>

        <div class="notas-pie">
          <div>* El equipo técnico de deslinda del manejo o pérdida de Información de los equipos revisados</div>
          <div>** Al firmar y sellar acepta de conformidad la totalidad del trabajo realizado</div>
        </div>
      </div>

    </div>

    <!-- PIE DE PÁGINA INSTITUCIONAL -->
    <footer class="pie-direccion">
      <span>D.S. y T.I. &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Xicotencatl 204, Los Fuentes. Durango, Dgo.</span>
      <span>Tel: 618 812 9898</span>
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
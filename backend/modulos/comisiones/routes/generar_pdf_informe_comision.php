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

    header('Content-Type: text/html; charset=UTF-8');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Informe de comisión #<?= $escape($informe['id_informe_comision']) ?></title>
  <style>
    @page {
      size: letter portrait;
      margin: 10mm 14mm;
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
      font: 9pt Arial, Helvetica, sans-serif;
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
      padding: 10mm 14mm;
      background: #fff;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
    }

    /* Encabezado: logo a la izquierda y título centrado (fila 1 del formato) */
    .encabezado {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 24mm;
    }
    .encabezado img {
      position: absolute;
      left: 8mm;
      top: 50%;
      height: 21mm;
      transform: translateY(-50%);
    }
    .encabezado h1 {
      font-size: 11pt;
      font-weight: bold;
    }

    .franja {
      background: #dadde1 !important;
      box-shadow: inset 0 0 0 1000px #dadde1 !important;
      font-weight: bold;
      font-size: 9pt;
      padding: 2px 6px;
    }
    .franja.centro {
      text-align: center;
    }
    .franja.linea-inferior {
      border-bottom: 1px solid #000;
    }

    /* Datos del personal comisionado: etiqueta sombreada + línea de captura */
    .datos {
      display: grid;
      grid-template-columns: 41.5% 1fr;
      row-gap: 2.6mm;
      margin-top: 2.6mm;
    }
    .datos .franja {
      display: flex;
      align-items: center;
    }
    .linea {
      display: flex;
      align-items: flex-end;
      min-height: 5mm;
      padding: 0 6px 1px 6px;
      border-bottom: 1px solid #000;
      font-size: 9pt;
      overflow-wrap: anywhere;
    }
    .fila-rfc {
      display: grid;
      grid-template-columns: 1fr 22% 34%;
      align-items: stretch;
    }
    .fila-rfc .franja {
      justify-content: center;
    }
    .objetivo .franja {
      min-height: 15mm;
    }
    .caja-objetivo {
      display: flex;
      align-items: center;
      min-height: 15mm;
      padding: 3px 6px;
      border: 1px solid #000;
      font-size: 9pt;
      line-height: 1.35;
      overflow-wrap: anywhere;
    }

    /* Apartados del informe */
    .apartado {
      margin-top: 3.5mm;
      break-inside: avoid;
    }
    .caja {
      display: flex;
      align-items: center;
      padding: 4px 8px;
      border: 1px solid #000;
      font-size: 9pt;
      line-height: 1.4;
      overflow-wrap: anywhere;
    }
    .caja.centro {
      justify-content: center;
      text-align: center;
    }
    .caja.alta {
      min-height: 26mm;
    }
    .caja.media {
      min-height: 19mm;
    }

    /* Firmas */
    .firmas {
      display: grid;
      grid-template-columns: 41.5% 1fr 49%;
      margin-top: 7mm;
      break-inside: avoid;
    }
    .firma-comisionado {
      grid-column: 1;
      display: flex;
      flex-direction: column;
    }
    .recuadro-firma {
      height: 17mm;
      border: 1px solid #000;
    }
    .finanzas {
      grid-column: 3;
      display: flex;
      flex-direction: column;
      border: 1px solid #000;
    }
    .finanzas .franja {
      text-align: center;
    }
    .finanzas .franja:first-child {
      border-bottom: 1px solid #000;
    }
    .finanzas .franja:last-child {
      border-top: 1px solid #000;
    }
    .finanzas .espacio {
      flex: 1;
      min-height: 24mm;
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
    <header class="encabezado">
      <?php if ($logo !== ''): ?>
        <img src="<?= $logo ?>" alt="Secretaría de Educación del Estado de Durango">
      <?php endif; ?>
      <h1>Formato de Informe de Comisión</h1>
    </header>

    <div class="franja centro">Datos del Personal Comisionado</div>

    <section class="datos">
      <div class="franja">Lugar y Periodo de la Comisión</div>
      <div class="linea"><?= $escape($lugar_periodo) ?></div>

      <div class="franja">Nombre del Comisionado</div>
      <div class="fila-rfc">
        <div class="linea"><?= $escape($informe['comisionado']) ?></div>
        <div class="franja">R.F.C.</div>
        <div class="linea" style="justify-content: center;"><?= $escape($informe['rfc']) ?></div>
      </div>

      <div class="franja">Departamento o Dirección de Adscripción</div>
      <div class="linea"><?= $escape($informe['area_nombre']) ?></div>

      <div class="franja">Programa o Recurso</div>
      <div class="linea"><?= $escape($informe['programa']) ?></div>
    </section>

    <section class="datos objetivo">
      <div class="franja">Objetivo de la Comisión</div>
      <div class="caja-objetivo"><?= $texto($informe['objetivo_comision']) ?></div>
    </section>

    <section class="apartado">
      <div class="franja centro">Reporte de las Actividades Desarrolladas durante la Comisión</div>
      <div class="caja centro alta"><?= $texto($informe['actividad_desarrollada']) ?></div>
    </section>

    <section class="apartado">
      <div class="franja centro linea-inferior">Resultados Obtenidos a través de la Comisión</div>
      <div class="caja media" style="border-top: 0;"><?= $texto($informe['resultados']) ?></div>
    </section>

    <section class="apartado">
      <div class="franja centro linea-inferior">Contribución de haber realizado la Comisón</div>
      <div class="caja centro media" style="border-top: 0;"><?= $texto($informe['contribucion']) ?></div>
    </section>

    <section class="apartado">
      <div class="franja centro linea-inferior">Conclusiones Después de haber realizado la Comisión</div>
      <div class="caja media" style="border-top: 0;"><?= $texto($informe['conclusion']) ?></div>
    </section>

    <section class="firmas">
      <div class="firma-comisionado">
        <div class="franja">Firma del Comisionado</div>
        <div class="recuadro-firma"></div>
        <div class="franja">Nombre:</div>
        <div class="linea"><?= $escape($informe['comisionado']) ?></div>
      </div>

      <div class="finanzas">
        <div class="franja">Exclusivo de Subdirección de Finanzas</div>
        <div class="espacio"></div>
        <div class="franja">Sello y Firma de Recibido</div>
      </div>
    </section>
  </main>

</body>
</html>
    <?php
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    exit('Error interno del servidor');
}

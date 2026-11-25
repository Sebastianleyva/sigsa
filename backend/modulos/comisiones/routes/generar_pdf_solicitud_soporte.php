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

    $id_comision = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id_comision === false || $id_comision === null) {
        http_response_code(400);
        exit('Solicitud de soporte inválida');
    }

    $consulta = $pdo->prepare(
        'SELECT ss.*, c.tipo_destino, c.id_area,
                p.nombre AS tecnico_nombre, p.apellido_p AS tecnico_apellido_p,
                p.apellido_m AS tecnico_apellido_m
         FROM solicitud_soporte ss
         INNER JOIN comisiones c ON c.id_comision = ss.id_comision
         INNER JOIN personal p ON p.id_personal = ss.tecnico_asignado
         WHERE ss.id_comision = :id_comision'
    );
    $consulta->execute(['id_comision' => $id_comision]);
    $solicitud = $consulta->fetch(PDO::FETCH_ASSOC);
    if (!$solicitud || strtoupper(trim($solicitud['tipo_destino'])) !== 'CENTRO ADMINISTRATIVO') {
        http_response_code(404);
        exit('Solicitud de soporte no encontrada');
    }

    $consulta_usuario = $pdo->prepare(
        "SELECT puesto, area FROM personal
         WHERE id_personal = :id_personal AND activo = '1'"
    );
    $consulta_usuario->execute(['id_personal' => $id_personal]);
    $usuario_activo = $consulta_usuario->fetch(PDO::FETCH_ASSOC);
    if (!$usuario_activo) {
        http_response_code(403);
        exit('No tienes permisos para consultar esta solicitud de soporte');
    }

    $consulta_participante = $pdo->prepare(
        'SELECT 1 FROM personal_comisionado
         WHERE id_comision = :id_comision AND id_personal = :id_personal'
    );
    $consulta_participante->execute([
        'id_comision' => $id_comision,
        'id_personal' => $id_personal,
    ]);
    $es_participante = (bool) $consulta_participante->fetchColumn();
    $es_jefe = (int) $usuario_activo['puesto'] === 3
        && (int) $usuario_activo['area'] === (int) $solicitud['id_area'];
    if (!$es_participante && !$es_jefe) {
        http_response_code(403);
        exit('No tienes permisos para consultar esta solicitud de soporte');
    }

    $escape = static fn ($valor): string => htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
    $campoTexto = static function (string $campo) use ($solicitud, $escape): string {
        $valor = trim((string) ($solicitud[$campo] ?? ''));
        return $valor === '' ? '' : nl2br($escape($valor), false);
    };

    $fecha = DateTime::createFromFormat('!Y-m-d', (string) $solicitud['fecha']);
    $fechaTexto = $fecha ? $fecha->format('d/m/Y') : (string) $solicitud['fecha'];
    $hora = DateTime::createFromFormat('!H:i:s', (string) $solicitud['hora']);
    $horaTexto = $hora ? $hora->format('H:i') : (string) $solicitud['hora'];
    $tecnico = trim(
        $solicitud['tecnico_nombre'] . ' '
        . $solicitud['tecnico_apellido_p'] . ' '
        . $solicitud['tecnico_apellido_m']
    );

    // Logotipos oficiales en Base64 para compatibilidad con ventana blob:
    $logoDurangoBase64 = 'data:image/png;base64,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';
    $logoSeedBase64 = 'data:image/png;base64,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';

    header('Content-Type: text/html; charset=UTF-8');
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Solicitud de soporte #<?= $escape($solicitud['id_solicitud_soporte']) ?></title>
  <style>
    @page { size: letter portrait; margin: 10mm 13mm; }
    * { box-sizing: border-box; margin: 0; padding: 0; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    body { background: #e9ecef; color: #000; font: 9pt Arial, Helvetica, sans-serif; }
    .toolbar { padding: 12px; background: #172b27; text-align: center; }
    .toolbar button { padding: 9px 18px; border: 0; border-radius: 4px; background: #176c58; color: #fff; font-size: 13px; font-weight: bold; cursor: pointer; }
    .sheet { display: flex; flex-direction: column; width: 215.9mm; min-height: 279.4mm; margin: 16px auto; padding: 10mm 13mm; background: #fff; box-shadow: 0 2px 10px #0002; }

    /* Encabezado con logos laterales */
    .heading { display: flex; align-items: center; justify-content: space-between; gap: 10px; min-height: 76px; margin-bottom: 8px; }
    .logo-izq, .logo-der { height: 54px; object-fit: contain; }
    .heading-center { flex: 1; text-align: center; line-height: 1.35; }
    .heading .agency { font-size: 10pt; font-weight: bold; }
    .heading .directorate { font-size: 9.5pt; font-weight: bold; }
    .heading .title { margin-top: 8px; font-size: 13pt; font-weight: bold; letter-spacing: .5px; }

    .meta { display: flex; justify-content: space-between; padding: 4px 10px 6px; }
    .meta-item { display: flex; align-items: center; gap: 8px; }
    .box { display: inline-flex; align-items: center; justify-content: center; min-width: 115px; min-height: 22px; padding: 2px 8px; border: 1px solid #000; font-weight: bold; overflow-wrap: anywhere; }

    /* Recuadro principal con flex para empujar firmas al fondo */
    .frame { flex: 1; display: flex; flex-direction: column; border: 1px solid #000; }
    .bar {
      padding: 4px 10px;
      border-top: 1px solid #000;
      border-bottom: 1px solid #000;
      background: #bfbfbf !important;
      box-shadow: inset 0 0 0 1000px #bfbfbf !important;
      font-weight: bold;
      text-align: center;
    }
    .bar.first { border-top: none; }

    .fields { display: grid; grid-template-columns: 1fr 1fr; gap: 7px 14px; padding: 10px 14px; }
    .field { display: flex; align-items: flex-start; gap: 8px; min-width: 0; }
    .field.full { grid-column: 1 / -1; }
    .label { flex: 0 0 auto; font-weight: bold; padding-top: 3px; }
    .value { flex: 1; min-height: 22px; padding: 3px 7px; border: 1px solid #000; overflow-wrap: anywhere; }
    .free-text { min-height: 95px; padding: 8px 14px; line-height: 1.4; overflow-wrap: anywhere; }
    .counts { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 10px 14px; }

    /* Firmas al fondo del recuadro sin nombres prellenados */
    .signatures {
      margin-top: auto;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 44px;
      padding: 70px 28px 18px;
      text-align: center;
    }
    .signature { padding-top: 5px; border-top: 1px solid #000; font-weight: bold; font-size: 8.5pt; }

    /* Pie institucional inferior */
    .footer-address {
      display: flex;
      justify-content: space-between;
      padding: 5px 10px 0 10px;
      font-size: 8pt;
      font-weight: bold;
    }

    @media print {
      body { background: #fff; }
      .toolbar { display: none; }
      .sheet { width: 100%; min-height: 258mm; margin: 0; padding: 0; box-shadow: none; }
    }
  </style>
</head>
<body>
  <div class="toolbar">
    <button type="button" onclick="window.print()">Imprimir / Guardar como PDF</button>
  </div>
  <main class="sheet">
    <header class="heading">
      <img class="logo-izq" src="<?= $logoDurangoBase64 ?>" alt="Durango · Gobierno del Estado">
      <div class="heading-center">
        <div class="agency">SECRETARÍA DE EDUCACIÓN DEL ESTADO DE DURANGO</div>
        <div class="directorate">DIRECCIÓN DE SISTEMAS Y TECNOLOGÍAS DE LA INFORMACIÓN</div>
        <div class="title">SOLICITUD DE SOPORTE Y MANTENIMIENTO CORRECTIVO</div>
      </div>
      <img class="logo-der" src="<?= $logoSeedBase64 ?>" alt="SEED">
    </header>

    <div class="meta">
      <div class="meta-item"><strong>Fecha:</strong><span class="box"><?= $escape($fechaTexto) ?></span></div>
      <div class="meta-item"><strong>Hora:</strong><span class="box"><?= $escape($horaTexto) ?></span></div>
    </div>

    <section class="frame">
      <div class="bar first">DATOS DE LA SOLICITUD</div>
      <div class="fields">
        <div class="field"><span class="label">Departamento:</span><span class="value"><?= $campoTexto('departamento') ?></span></div>
        <div class="field"><span class="label">CISCO:</span><span class="value"><?= $campoTexto('cisco') ?></span></div>
        <div class="field full"><span class="label">Dirección / domicilio:</span><span class="value"><?= $campoTexto('direccion') ?></span></div>
        <div class="field"><span class="label">Reporta:</span><span class="value"><?= $campoTexto('nombre_reporta') ?></span></div>
        <div class="field"><span class="label">Técnico asignado:</span><span class="value"><?= $escape($tecnico) ?></span></div>
      </div>

      <div class="bar">DESCRIPCIÓN DE LA FALLA</div>
      <div class="free-text"><?= $campoTexto('solicitud_falla') ?></div>

      <div class="bar">RESULTADO DE LA ATENCIÓN</div>
      <div class="counts">
        <div class="field"><span class="label">Equipos atendidos:</span><span class="value"><?= $escape($solicitud['equipos_atendidos']) ?></span></div>
        <div class="field"><span class="label">Usuarios beneficiados:</span><span class="value"><?= $escape($solicitud['usuarios_beneficiados']) ?></span></div>
      </div>

      <div class="signatures">
        <div class="signature">Nombre y firma de quien reporta</div>
        <div class="signature">Nombre y firma del técnico</div>
      </div>
    </section>

    <footer class="footer-address">
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
    exit('Error interno del servidor');
}
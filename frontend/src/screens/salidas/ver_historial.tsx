import { useEffect, useState } from 'react';
import * as XLSX from 'xlsx';
import { authService } from '../../services/auth_services.js';
import type { VerHistorialSalida } from '../../services/auth_services.js';
import './styles/ver_historial.css';

function formatearFecha(fecha: string) {
  return new Date(`${fecha}T00:00:00`).toLocaleDateString('es-MX', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
}

function formatearHora(hora: string | null) {
  return hora ? hora.slice(0, 5) : 'No registrada';
}

function estadoJustificante(
  requiere: number | boolean,
  entregado: number | null,
  fechaLlegada: string | null,
  horaLlegada: string | null,
  fechaEntrega: string | null,
) {
  if (Number(requiere) === 0) return 'No requerido';
  if (entregado === null) return 'No registrado';
  if (Number(entregado) !== 1) return 'Pendiente';
  if (
    fechaLlegada
    && horaLlegada
    && fechaEntrega
    && fechaEntrega > `${fechaLlegada} ${horaLlegada}`
  ) {
    return 'Entregado a destiempo';
  }
  return 'Entregado';
}

function descargarExcel(salidas: VerHistorialSalida[], mesConsulta: Date) {
  const filas = salidas.map((salida) => ({
    'No.': `#${String(salida.id_salida).padStart(3, '0')}`,
    Personal: `${salida.nombre} ${salida.apellido_p} ${salida.apellido_m}`.trim(),
    'Área': salida.area_nombre ?? 'No asignada',
    Destino: salida.destino,
    Motivo: salida.motivo,
    Autorizó: salida.autorizante_nombre
      ? `${salida.autorizante_nombre} ${salida.autorizante_apellido_p ?? ''} ${salida.autorizante_apellido_m ?? ''}`.trim()
      : 'No registrado',
    Fecha: formatearFecha(salida.fecha),
    'Hora de salida': formatearHora(salida.hora_salida),
    'Hora de regreso': formatearHora(salida.hora_llegada),
    'Adeudo de tiempo': salida.adeudo_tiempo ?? 'No registrado',
    'Requiere justificante': Number(salida.requiere_justificante) !== 0 ? 'Sí' : 'No',
    'Estado del justificante': estadoJustificante(
      salida.requiere_justificante,
      salida.justificante_entregado,
      salida.fecha_llegada,
      salida.hora_llegada,
      salida.fecha_justificante_entregado,
    ),
  }));

  const hoja = XLSX.utils.json_to_sheet(filas);
  hoja['!cols'] = [
    { wch: 8 },
    { wch: 28 },
    { wch: 22 },
    { wch: 28 },
    { wch: 36 },
    { wch: 28 },
    { wch: 20 },
    { wch: 18 },
    { wch: 18 },
    { wch: 20 },
    { wch: 20 },
    { wch: 24 },
  ];
  hoja['!freeze'] = { xSplit: 0, ySplit: 1 };

  const libro = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(libro, hoja, 'Historial de salidas');
  const mes = String(mesConsulta.getMonth() + 1).padStart(2, '0');
  XLSX.writeFile(libro, `historial_salidas_${mesConsulta.getFullYear()}-${mes}.xlsx`);
}

export default function VerHistorial() {
  const hoy = new Date();
  const inicioDelAno = new Date(hoy.getFullYear(), 0, 1);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [salidas, setSalidas] = useState<VerHistorialSalida[]>([]);
  const [mesConsulta, setMesConsulta] = useState(new Date(hoy.getFullYear(), hoy.getMonth(), 1));

  const cambiarMes = (cantidad: number) => {
    setMesConsulta((mesActual) => {
      const nuevoMes = new Date(mesActual.getFullYear(), mesActual.getMonth() + cantidad, 1);
      const inicioDelMesActual = new Date(hoy.getFullYear(), hoy.getMonth(), 1);

      if (nuevoMes < inicioDelAno) return inicioDelAno;
      if (nuevoMes > inicioDelMesActual) return inicioDelMesActual;
      return nuevoMes;
    });
  };

  const nombreMesConsulta = mesConsulta.toLocaleDateString('es-MX', {
    month: 'long',
    year: 'numeric',
  });

  useEffect(() => {
    let mounted = true;

    async function fetchSalidas() {
      try {
        const resultados = await authService.VerHistorial(
          mesConsulta.getMonth() + 1,
          mesConsulta.getFullYear(),
        );
        if (mounted) setSalidas(resultados.Salidas);
      } catch (fetchError) {
        console.error('Error al obtener el historial de salidas:', fetchError);
        if (mounted) {
          setError(
            fetchError instanceof Error
              ? fetchError.message
              : 'No fue posible obtener el historial de salidas.',
          );
        }
      } finally {
        if (mounted) setLoading(false);
      }
    }

    fetchSalidas();
    return () => {
      mounted = false;
    };
  }, [mesConsulta]);

  return (
    <main className="historial-page">
      <header className="historial-topbar">
        <a className="historial-brand" href="#inicio" onClick={(event) => event.preventDefault()}>
          <span className="historial-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </a>
        <div className="historial-topbar-meta">
          <span className="historial-topbar-dot" aria-hidden="true" />
          <span>Sistema de gestión de servicios administrativos</span>
        </div>
      </header>

      <section className="historial-content" id="inicio">
        <div className="historial-heading">
          <span className="historial-eyebrow">CONSULTA · SIGSA</span>
          <h1>Historial de salidas</h1>
          <p>Consulta las salidas registradas durante el último año y revisa sus detalles.</p>
        </div>

        <section className="historial-section" aria-labelledby="historial-title">
          <div className="historial-section-heading">
            <div>
              <span className="historial-section-index">01 / REGISTRO</span>
              <h2 id="historial-title">Salidas registradas</h2>
            </div>
            {!loading && !error && (
              <div className="historial-section-actions">
                <div className="historial-month-navigation" aria-label="Navegación por mes">
                  <button
                    className="historial-month-button"
                    type="button"
                    onClick={() => cambiarMes(-1)}
                    disabled={mesConsulta <= inicioDelAno}
                    aria-label="Consultar el mes anterior"
                  >
                    ←
                  </button>
                  <span className="historial-month-label">{nombreMesConsulta}</span>
                  <button
                    className="historial-month-button"
                    type="button"
                    onClick={() => cambiarMes(1)}
                    disabled={mesConsulta >= new Date(hoy.getFullYear(), hoy.getMonth(), 1)}
                    aria-label="Consultar el mes siguiente"
                  >
                    →
                  </button>
                </div>
                <span className="historial-count">
                  {salidas.length} {salidas.length === 1 ? 'SALIDA' : 'SALIDAS'}
                </span>
                <button
                  className="historial-export-button"
                  type="button"
                  onClick={() => descargarExcel(salidas, mesConsulta)}
                  disabled={salidas.length === 0}
                >
                  Descargar Excel
                </button>
              </div>
            )}
          </div>

          {loading ? (
            <div className="historial-status historial-status-loading" role="status">
              Cargando historial...
            </div>
          ) : error ? (
            <div className="historial-status historial-status-error" role="alert">
              {error}
            </div>
          ) : salidas.length === 0 ? (
            <div className="historial-empty">
              <span className="historial-empty-icon" aria-hidden="true">✓</span>
              <h2>No hay salidas registradas</h2>
              <p>Cuando existan salidas en el periodo consultado, aparecerán aquí.</p>
            </div>
          ) : (
            <div className="historial-list">
              {salidas.map((salida) => (
                <article className="historial-card" key={salida.id_salida}>
                  <div className="historial-card-header">
                    <div>
                      <span className="historial-card-kicker">PERSONAL</span>
                      <h3>{salida.nombre} {salida.apellido_p} {salida.apellido_m}</h3>
                    </div>
                    <span className="historial-card-number">
                      #{String(salida.id_salida).padStart(3, '0')}
                    </span>
                  </div>

                  <div className="historial-card-body">
                    <div className="historial-field">
                      <span className="historial-label">Área</span>
                      <p>{salida.area_nombre ?? 'No asignada'}</p>
                    </div>
                    <div className="historial-field">
                      <span className="historial-label">Destino</span>
                      <p>{salida.destino}</p>
                    </div>
                    <div className="historial-field">
                      <span className="historial-label">Motivo</span>
                      <p>{salida.motivo}</p>
                    </div>
                    <div className="historial-field">
                      <span className="historial-label">Autorizó</span>
                      <p>
                        {salida.autorizante_nombre
                          ? `${salida.autorizante_nombre} ${salida.autorizante_apellido_p ?? ''} ${salida.autorizante_apellido_m ?? ''}`.trim()
                          : 'No registrado'}
                      </p>
                    </div>
                    <div className="historial-meta-grid">
                      <div className="historial-field">
                        <span className="historial-label">Fecha</span>
                        <p>{formatearFecha(salida.fecha)}</p>
                      </div>
                      <div className="historial-field">
                        <span className="historial-label">Hora de salida</span>
                        <p>{formatearHora(salida.hora_salida)}</p>
                      </div>
                      <div className="historial-field">
                        <span className="historial-label">Hora de regreso</span>
                        <p>{formatearHora(salida.hora_llegada)}</p>
                      </div>
                    </div>
                    <div className="historial-field historial-field-adeudo">
                      <span className="historial-label">Adeudo de tiempo</span>
                      <p>{salida.adeudo_tiempo ?? 'No registrado'}</p>
                    </div>
                    <div className="historial-field">
                      <span className="historial-label">Requiere justificante</span>
                      <p>{Number(salida.requiere_justificante) !== 0 ? 'Sí' : 'No'}</p>
                    </div>
                    <div className="historial-field">
                      <span className="historial-label">Estado del justificante</span>
                      <p>
                        {estadoJustificante(
                          salida.requiere_justificante,
                          salida.justificante_entregado,
                          salida.fecha_llegada,
                          salida.hora_llegada,
                          salida.fecha_justificante_entregado,
                        )}
                      </p>
                    </div>
                  </div>
                </article>
              ))}
            </div>
          )}
        </section>

        <footer className="historial-footer">
          <span>© SIGSA</span>
          <span>Sistema de gestión de servicios administrativos</span>
        </footer>
      </section>
    </main>
  );
}

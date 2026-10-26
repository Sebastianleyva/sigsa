import { useEffect, useState } from 'react';
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

export default function VerHistorial() {
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [salidas, setSalidas] = useState<VerHistorialSalida[]>([]);

  useEffect(() => {
    let mounted = true;

    async function fetchSalidas() {
      try {
        const resultados = await authService.VerHistorial();
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
  }, []);

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
              <span className="historial-count">
                {salidas.length} {salidas.length === 1 ? 'SALIDA' : 'SALIDAS'}
              </span>
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
                      <span className="historial-label">Destino</span>
                      <p>{salida.destino}</p>
                    </div>
                    <div className="historial-field">
                      <span className="historial-label">Motivo</span>
                      <p>{salida.motivo}</p>
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

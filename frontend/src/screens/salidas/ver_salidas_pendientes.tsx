import { useEffect, useState } from 'react';
import { authService } from '../../services/auth_services.js';
import './styles/ver_salidas_pendientes.css';

type SalidaPendiente = {
  id_salida: number;
  tipo_salida: string;
  motivo: string;
  destino: string;
  nombre: string;
  apellido_p: string;
  apellido_m: string;
  id_personal: number;
};

export default function Ver_Salidas_Pendientes() {
  const [salidasPendientes, setSalidasPendientes] = useState<SalidaPendiente[]>([]);
  const [loading, setLoading] = useState(true);
  const [selectedId, setSelectedId] = useState<number | null>(null);
  const [procesando, setProcesando] = useState<number | null>(null);

  const toggle = (id_salida: number) => {
    // Si se está procesando esta salida, evitamos que se cierre/abra el menú
    if (procesando === id_salida) return;

    if (id_salida === selectedId) {
      setSelectedId(null);
    } else {
      setSelectedId(id_salida);
    }
  };

  async function handleAceptar(id_salida: number) {
    setProcesando(id_salida);
    try {
      await authService.AceptarSalida({ id_salida });
      setSalidasPendientes((prev) => prev.filter((s) => s.id_salida !== id_salida));
      setSelectedId(null);
    } catch (error) {
      console.error('Error al aceptar salida:', error);
    } finally {
      setProcesando(null);
    }
  }

  async function handleRechazar(id_salida: number) {
    setProcesando(id_salida);
    try {
      await authService.RechazarSalida({ id_salida });
      setSalidasPendientes((prev) => prev.filter((s) => s.id_salida !== id_salida));
      setSelectedId(null);
    } catch (error) {
      console.error('Error al rechazar salida:', error);
    } finally {
      setProcesando(null);
    }
  }

  useEffect(() => {
    async function fetchSalidasPendientes() {
      try {
        const datos = await authService.VerSalidasPendientes();
        setSalidasPendientes(datos);
      } catch (error) {
        console.error('Error fetching salidas pendientes:', error);
      } finally {
        setLoading(false);
      }
    }

    fetchSalidasPendientes();
  }, []);

  return (
    <main className="salidas-pendientes-page">
      <header className="salidas-pendientes-topbar">
        <a className="salidas-pendientes-brand" href="#inicio" onClick={(event) => event.preventDefault()}>
          <span className="salidas-pendientes-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </a>
        <div className="salidas-pendientes-topbar-meta">
          <span className="salidas-pendientes-topbar-dot" aria-hidden="true" />
          <span>Sistema de gestión de servicios administrativos</span>
        </div>
      </header>

      <section className="salidas-pendientes-content" id="inicio">
        <div className="salidas-pendientes-heading">
          <span className="salidas-pendientes-eyebrow">OPERACIÓN · SIGSA</span>
          <h1>Salidas pendientes</h1>
          <p>Revisa las solicitudes de salida que están pendientes de atención.</p>
        </div>

        <section className="salidas-pendientes-section" aria-labelledby="salidas-pendientes-title">
          <div className="salidas-pendientes-section-heading">
            <div>
              <span className="salidas-pendientes-section-index">01 / SOLICITUDES</span>
              <h2 id="salidas-pendientes-title">Solicitudes por revisar</h2>
            </div>
            {!loading && (
              <span className="salidas-pendientes-count">
                {salidasPendientes.length} {salidasPendientes.length === 1 ? 'PENDIENTE' : 'PENDIENTES'}
              </span>
            )}
          </div>

          {loading ? (
            <div className="salidas-pendientes-status salidas-pendientes-status-loading" role="status">
              Cargando solicitudes...
            </div>
          ) : salidasPendientes.length === 0 ? (
            <div className="salidas-pendientes-empty">
              <span className="salidas-pendientes-empty-icon" aria-hidden="true">✓</span>
              <h2>No hay salidas pendientes</h2>
              <p>Las nuevas solicitudes aparecerán aquí cuando estén disponibles.</p>
            </div>
          ) : (
            <div className="salidas-pendientes-list">
              {salidasPendientes.map((salida) => (
                <article className="salidas-pendientes-card" key={salida.id_salida} onClick={() => toggle(salida.id_salida)}>
                  {selectedId === salida.id_salida && (
                    <div className="salidas-pendientes-actions" onClick={(e) => e.stopPropagation()}>
                      <button className="salidas-pendientes-button salidas-pendientes-button-accept" onClick={() => handleAceptar(salida.id_salida)}>Aceptar</button>
                      <button className="salidas-pendientes-button salidas-pendientes-button-reject" onClick={() => handleRechazar(salida.id_salida)}>Rechazar</button>
                    </div>
                  )}
                  <div className="salidas-pendientes-card-header">
                    <div>
                      <span className="salidas-pendientes-card-kicker">SOLICITUD DE SALIDA</span>
                      <h3>{salida.nombre} {salida.apellido_p} {salida.apellido_m}</h3>
                    </div>
                    <span className="salidas-pendientes-card-number">
                      #{String(salida.id_salida).padStart(3, '0')}
                    </span>
                  </div>

                  <div className="salidas-pendientes-card-body">
                    <div className="salidas-pendientes-field">
                      <span className="salidas-pendientes-label">Tipo de salida</span>
                      <p>{salida.tipo_salida}</p>
                    </div>
                    <div className="salidas-pendientes-meta-grid">
                      <div className="salidas-pendientes-field">
                        <span className="salidas-pendientes-label">Destino</span>
                        <p>{salida.destino}</p>
                      </div>
                      <div className="salidas-pendientes-field">
                        <span className="salidas-pendientes-label">Motivo</span>
                        <p>{salida.motivo}</p>
                      </div>
                    </div>
                  </div>
                </article>
              ))}
            </div>
          )}
        </section>

        <footer className="salidas-pendientes-footer">
          <span>© SIGSA</span>
          <span>Sistema de gestión de servicios administrativos</span>
        </footer>
      </section>
    </main>
  );
}
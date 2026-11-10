import { useEffect, useState } from 'react';
import { authService } from '../../services/auth_services.js';
import './styles/ver_salidas_en_curso.css';

type SalidaEnCurso = {
  id_salida: number;
  tipo_salida: string;
  motivo: string;
  destino: string;
  fecha: string;
  hora_salida: string | null;
  hora_estimada: string | null;
  hora_llegada: string | null;
  fecha_llegada: string | null;
  adeudo_tiempo: string | null;
  requiere_justificante: number | boolean;
  justificante_entregado: number | null;
  nombre: string;
  apellido_p: string;
  apellido_m: string;
  id_personal: number;
  puesto: number;
  autorizante_nombre: string | null;
  autorizante_apellido_p: string | null;
  autorizante_apellido_m: string | null;
};


export default function Ver_Salidas_En_Curso() {
  const [salidasEnCurso, setSalidasEnCurso] = useState<SalidaEnCurso[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);
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

  async function handleFinalizar(id_salida: number, justificanteEntregado: boolean | null) {
    setActionError(null);
    setProcesando(id_salida);
    try {
      const resultado = await authService.FinalizarSalida({
        id_salida,
        justificante_entregado: justificanteEntregado,
      });
      if (resultado.salida.estado === 'FINALIZADO') {
        setSalidasEnCurso((prev) => prev.filter((s) => s.id_salida !== id_salida));
      } else {
        setSalidasEnCurso((prev) => prev.map((salida) => (
          salida.id_salida === id_salida
            ? {
                ...salida,
                hora_llegada: resultado.salida.hora_llegada,
                fecha_llegada: resultado.salida.fecha_llegada,
                adeudo_tiempo: resultado.salida.adeudo_tiempo,
                justificante_entregado: resultado.salida.justificante_entregado,
              }
            : salida
        )));
      }
      setSelectedId(null);
    } catch (error) {
      console.error('Error al finalizar salida:', error);
      setActionError(
        error instanceof Error ? error.message : 'No fue posible finalizar la salida.',
      );
    } finally {
      setProcesando(null);
    }
  }

  useEffect(() => {
    let mounted = true;

    async function fetchSalidasEnCurso() {
      try {
        const datos = await authService.VerSalidasEnCurso();
        if (mounted) setSalidasEnCurso(datos);
      } catch (fetchError) {
        console.error('Error al obtener salidas en curso:', fetchError);
        if (mounted) {
          setError(
            fetchError instanceof Error
              ? fetchError.message
              : 'No fue posible obtener las salidas en curso.',
          );
        }
      } finally {
        if (mounted) setLoading(false);
      }
    }

    fetchSalidasEnCurso();
    return () => {
      mounted = false;
    };
  }, []);

  return (
    <main className="salidas-en-curso-page">
      <header className="salidas-en-curso-topbar">
        <a className="salidas-en-curso-brand" href="#inicio" onClick={(event) => event.preventDefault()}>
          <span className="salidas-en-curso-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </a>
        <div className="salidas-en-curso-topbar-meta">
          <span className="salidas-en-curso-topbar-dot" aria-hidden="true" />
          <span>Sistema de gestión de servicios administrativos</span>
        </div>
      </header>

      <section className="salidas-en-curso-content" id="inicio">
        <div className="salidas-en-curso-heading">
          <span className="salidas-en-curso-eyebrow">OPERACIÓN · SIGSA</span>
          <h1>Salidas en curso</h1>
          <p>Consulta las salidas autorizadas que se encuentran activas en este momento.</p>
        </div>

        <section className="salidas-en-curso-section" aria-labelledby="salidas-en-curso-title">
          <div className="salidas-en-curso-section-heading">
            <div>
              <span className="salidas-en-curso-section-index">01 / SEGUIMIENTO</span>
              <h2 id="salidas-en-curso-title">Personal fuera de las instalaciones</h2>
            </div>
            {!loading && !error && (
              <span className="salidas-en-curso-count">
                {salidasEnCurso.length} {salidasEnCurso.length === 1 ? 'SALIDA ACTIVA' : 'SALIDAS ACTIVAS'}
              </span>
            )}
          </div>

          {loading ? (
            <div className="salidas-en-curso-status salidas-en-curso-status-loading" role="status">
              Cargando salidas...
            </div>
          ) : error ? (
            <div className="salidas-en-curso-status salidas-en-curso-status-error" role="alert">
              {error}
            </div>
          ) : salidasEnCurso.length === 0 ? (
            <div className="salidas-en-curso-empty">
              <span className="salidas-en-curso-empty-icon" aria-hidden="true">✓</span>
              <h2>No hay salidas en curso</h2>
              <p>Cuando haya salidas activas, aparecerán aquí para su seguimiento.</p>
            </div>
          ) : (
            <div className="salidas-en-curso-list">
              {salidasEnCurso.map((salida) => {
                const requiereJustificante = Boolean(Number(salida.requiere_justificante));
                const justificantePendiente = requiereJustificante
                  && salida.justificante_entregado !== null
                  && Number(salida.justificante_entregado) === 0;

                return (
                <article
                  className={`salidas-en-curso-card${justificantePendiente ? ' salidas-en-curso-card-pendiente-justificante' : ''}`}
                  key={salida.id_salida}
                  onClick={() => toggle(salida.id_salida)}
                >
                  {selectedId === salida.id_salida && (
                    <div className="salidas-en-curso-actions" onClick={(event) => event.stopPropagation()}>
                      {justificantePendiente ? (
                        <>
                          <span className="salidas-en-curso-justificante-pendiente">
                            Falta entregar el justificante
                          </span>
                          <button
                            className="salidas-en-curso-button"
                            type="button"
                            disabled={procesando === salida.id_salida}
                            onClick={() => handleFinalizar(salida.id_salida, true)}
                          >
                            {procesando === salida.id_salida ? 'Guardando...' : 'Marcar justificante entregado'}
                          </button>
                        </>
                      ) : requiereJustificante ? (
                        <>
                          <span className="salidas-en-curso-justificante-pregunta">
                            ¿Se presentó el justificante?
                          </span>
                          <button
                            className="salidas-en-curso-button"
                            type="button"
                            disabled={procesando === salida.id_salida}
                            onClick={() => handleFinalizar(salida.id_salida, true)}
                          >
                            Sí, finalizar salida
                          </button>
                          <button
                            className="salidas-en-curso-button salidas-en-curso-button-pendiente"
                            type="button"
                            disabled={procesando === salida.id_salida}
                            onClick={() => handleFinalizar(salida.id_salida, false)}
                          >
                            No, dejar pendiente
                          </button>
                        </>
                      ) : (
                        <button
                          className="salidas-en-curso-button"
                          type="button"
                          disabled={procesando === salida.id_salida}
                          onClick={() => handleFinalizar(salida.id_salida, null)}
                        >
                          {procesando === salida.id_salida ? 'Finalizando...' : 'Finalizar salida'}
                        </button>
                      )}
                    </div>
                  )}
                  <div className="salidas-en-curso-card-header">
                    <div>
                      <span className="salidas-en-curso-card-kicker">SALIDA ACTIVA · {salida.tipo_salida}</span>
                      <h3>{salida.nombre} {salida.apellido_p} {salida.apellido_m}</h3>
                      {justificantePendiente && (
                        <span className="salidas-en-curso-justificante-pendiente">
                          Debe justificante
                        </span>
                      )}
                      {requiereJustificante && !justificantePendiente && (
                        <span className="salidas-en-curso-justificante-requerido">
                          Requiere justificante
                        </span>
                      )}
                    </div>
                    <span className="salidas-en-curso-card-number">
                      #{String(salida.id_salida).padStart(3, '0')}
                    </span>
                  </div>

                  <div className="salidas-en-curso-card-body">
                    <div className="salidas-en-curso-field">
                      <span className="salidas-en-curso-label">Destino</span>
                      <p>{salida.destino}</p>
                    </div>
                    <div className="salidas-en-curso-field">
                      <span className="salidas-en-curso-label">Motivo</span>
                      <p>{salida.motivo}</p>
                    </div>
                    <div className="salidas-en-curso-field">
                      <span className="salidas-en-curso-label">Autorizó</span>
                      <p>
                        {salida.autorizante_nombre
                          ? `${salida.autorizante_nombre} ${salida.autorizante_apellido_p ?? ''} ${salida.autorizante_apellido_m ?? ''}`.trim()
                          : [1, 2].includes(Number(salida.puesto))
                            ? 'No requiere autorización'
                            : 'No registrado'}
                      </p>
                    </div>
                    <div className="salidas-en-curso-meta-grid">
                      <div className="salidas-en-curso-field">
                        <span className="salidas-en-curso-label">Fecha</span>
                        <p>{new Date(`${salida.fecha}T00:00:00`).toLocaleDateString('es-MX')}</p>
                      </div>
                      {justificantePendiente && (
                        <div className="salidas-en-curso-field">
                          <span className="salidas-en-curso-label">Regreso reportado</span>
                          <p>
                            {salida.fecha_llegada
                              ? new Date(`${salida.fecha_llegada}T00:00:00`).toLocaleDateString('es-MX')
                              : ''}
                            {salida.hora_llegada ? ` ${salida.hora_llegada.slice(0, 5)}` : ''}
                          </p>
                        </div>
                      )}
                      <div className="salidas-en-curso-field">
                        <span className="salidas-en-curso-label">Hora de salida</span>
                        <p>{salida.hora_salida?.slice(0, 5) ?? 'No registrada'}</p>
                      </div>
                      <div className="salidas-en-curso-field">
                        <span className="salidas-en-curso-label">Regreso estimado</span>
                        <p>{salida.hora_estimada?.slice(0, 5) ?? 'No registrada'}</p>
                      </div>
                    </div>
                  </div>
                </article>
                );
              })}
            </div>
          )}
          {actionError && (
            <div className="salidas-en-curso-status salidas-en-curso-status-error" role="alert">
              {actionError}
            </div>
          )}
        </section>

        <footer className="salidas-en-curso-footer">
          <span>© SIGSA</span>
          <span>Sistema integral de gestión</span>
        </footer>
      </section>
    </main>
  );
}
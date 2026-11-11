import { useEffect, useState } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { authService } from '../../services/auth_services.js'
import { consultarHistorialReportes, type ReporteHistorial } from '../../services/mantenimiento_services'
import './styles/historial_reportes.css'

function mostrarFecha(fecha: string | null) {
  if (!fecha) return 'No registrada'
  const fechaLocal = new Date(`${fecha}T00:00:00`)
  return Number.isNaN(fechaLocal.getTime())
    ? fecha
    : new Intl.DateTimeFormat('es-MX', { dateStyle: 'long' }).format(fechaLocal)
}

function mostrarEstado(estado: string) {
  return estado.toLocaleLowerCase('es-MX').replace(/^./, (letra) => letra.toLocaleUpperCase('es-MX'))
}

function mostrarOrigen(reporte: ReporteHistorial) {
  if (reporte.id_incidencia !== null) {
    return `Incidencia #${reporte.id_incidencia}${reporte.lugar ? ` · ${reporte.lugar}` : ''}`
  }
  if (reporte.servicio) return reporte.servicio
  if (reporte.id_calendario !== null) return `Calendario #${reporte.id_calendario}`
  return 'Mantenimiento sin origen asociado'
}

function mostrarDescripcionReporte(reporte: ReporteHistorial) {
  const descripcion = reporte.descripcion.trim()
  const descripcionIncidencia = reporte.descripcion_incidencia?.trim() ?? ''
  return descripcion && descripcion !== descripcionIncidencia
    ? descripcion
    : 'Aún no se ha registrado la descripción del servicio realizado.'
}

function Dato({ etiqueta, valor }: { etiqueta: string; valor: string | number | null }) {
  return (
    <div>
      <dt>{etiqueta}</dt>
      <dd>{valor === null || valor === '' ? 'No registrado' : valor}</dd>
    </div>
  )
}

export default function HistorialReportesMantenimiento() {
  const navigate = useNavigate()
  const usuario = authService.getUsuarioActual()
  const autorizado = [1, 2, 3].includes(Number(usuario?.puesto))
  const [reportes, setReportes] = useState<ReporteHistorial[]>([])
  const [cargando, setCargando] = useState(true)
  const [error, setError] = useState('')
  const [intentoCarga, setIntentoCarga] = useState(0)

  useEffect(() => {
    if (!autorizado) return

    let vigente = true
    consultarHistorialReportes()
      .then((resultado) => {
        if (vigente) setReportes(resultado)
      })
      .catch((errorCarga: unknown) => {
        if (vigente) {
          setError(errorCarga instanceof Error
            ? errorCarga.message
            : 'No fue posible consultar el historial de reportes.')
        }
      })
      .finally(() => {
        if (vigente) setCargando(false)
      })

    return () => {
      vigente = false
    }
  }, [autorizado, intentoCarga])

  if (!autorizado) return <Navigate to="/mantenimiento" replace />

  return (
    <main className="historial-reportes-page">
      <header className="historial-reportes-topbar">
        <button className="historial-reportes-brand" type="button" onClick={() => navigate('/mantenimiento')}>
          <span className="historial-reportes-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </button>
        <span>HISTORIAL DE REPORTES</span>
      </header>

      <section className="historial-reportes-content">
        <button className="historial-reportes-back" type="button" onClick={() => navigate('/mantenimiento')}>
          ← Volver al módulo
        </button>

        <div className="historial-reportes-heading">
          <div>
            <span className="historial-reportes-eyebrow">CONSULTA · SIGSA</span>
            <h1>Historial de reportes</h1>
            <p>Consulta los reportes de mantenimiento, sus datos de origen y las requisiciones asociadas.</p>
          </div>
          <span className="historial-reportes-count">
            <strong>{reportes.length}</strong>
            {reportes.length === 1 ? 'reporte' : 'reportes'}
          </span>
        </div>

        {error && (
          <div className="historial-reportes-error" role="alert">
            <span>{error}</span>
            {!cargando && (
              <button
                type="button"
                onClick={() => {
                  setError('')
                  setCargando(true)
                  setIntentoCarga((actual) => actual + 1)
                }}
              >
                Reintentar
              </button>
            )}
          </div>
        )}
        {cargando && <p className="historial-reportes-message" role="status">Cargando historial…</p>}
        {!cargando && !error && reportes.length === 0 && (
          <div className="historial-reportes-empty">
            <span aria-hidden="true">✓</span>
            <h2>Aún no hay reportes registrados</h2>
            <p>Los reportes de incidencias y servicios aparecerán aquí cuando se generen.</p>
          </div>
        )}

        {!cargando && !error && reportes.length > 0 && (
          <section className="historial-reportes-list" aria-label="Reportes de mantenimiento">
            {reportes.map((reporte) => (
              <article className="historial-reportes-card" key={reporte.id_reporte}>
                <header className="historial-reportes-card-header">
                  <div>
                    <span className="historial-reportes-card-kicker">REPORTE #{reporte.id_reporte}</span>
                    <h2>{mostrarOrigen(reporte)}</h2>
                  </div>
                  <span className={`historial-reportes-status historial-reportes-status-${reporte.estado.toLocaleLowerCase('es-MX').replace(/\s+/g, '-')}`}>
                    {mostrarEstado(reporte.estado)}
                  </span>
                </header>

                <section className="historial-reportes-section">
                  <h3>Datos del reporte</h3>
                  <dl className="historial-reportes-details">
                    <Dato etiqueta="Tipo" valor={reporte.tipo} />
                    <Dato etiqueta="Estado" valor={mostrarEstado(reporte.estado)} />
                    <Dato etiqueta="Fecha de inicio" valor={mostrarFecha(reporte.fecha_inicio)} />
                    <Dato etiqueta="Fecha de conclusión" valor={mostrarFecha(reporte.fecha_final)} />
                    <Dato etiqueta="Responsable del reporte" valor={reporte.responsable_reporte ?? (reporte.id_personal ? `Personal #${reporte.id_personal}` : null)} />
                    <div className="historial-reportes-description">
                      <dt>Descripción del servicio realizado</dt>
                      <dd>{mostrarDescripcionReporte(reporte)}</dd>
                    </div>
                  </dl>
                </section>

                {reporte.id_incidencia !== null && (
                  <section className="historial-reportes-section">
                    <h3>Incidencia de origen</h3>
                    <dl className="historial-reportes-details">
                      <Dato etiqueta="Número de incidencia" valor={reporte.id_incidencia} />
                      <Dato etiqueta="Lugar" valor={reporte.lugar} />
                      <Dato etiqueta="Urgencia" valor={reporte.urgencia} />
                      <Dato etiqueta="Estado de incidencia" valor={reporte.estado_incidencia ? mostrarEstado(reporte.estado_incidencia) : null} />
                      <Dato etiqueta="Fecha reportada" valor={mostrarFecha(reporte.fecha_incidencia)} />
                      <Dato etiqueta="Reportó" valor={reporte.reportante ?? (reporte.id_reportante ? `Personal #${reporte.id_reportante}` : null)} />
                      <Dato etiqueta="Autorizó" valor={reporte.autorizador_incidencia ?? (reporte.id_autoriza_incidencia ? `Personal #${reporte.id_autoriza_incidencia}` : null)} />
                      <div className="historial-reportes-description">
                        <dt>Descripción de la incidencia</dt>
                        <dd>{reporte.descripcion_incidencia || 'No se registró una descripción de la incidencia.'}</dd>
                      </div>
                    </dl>
                  </section>
                )}

                {reporte.id_calendario !== null && (
                  <section className="historial-reportes-section">
                    <h3>Servicio programado de origen</h3>
                    <dl className="historial-reportes-details">
                      <Dato etiqueta="Número de calendario" valor={reporte.id_calendario} />
                      <Dato etiqueta="Servicio" valor={reporte.servicio} />
                      <Dato etiqueta="Estado del calendario" valor={reporte.estado_calendario ? mostrarEstado(reporte.estado_calendario) : null} />
                      <Dato etiqueta="Fecha programada" valor={mostrarFecha(reporte.fecha_destinada)} />
                      <div className="historial-reportes-description">
                        <dt>Descripción del servicio programado</dt>
                        <dd>{reporte.descripcion_servicio_programado || 'No se registró una descripción del servicio programado.'}</dd>
                      </div>
                    </dl>
                  </section>
                )}

                <section className="historial-reportes-section">
                  <h3>Requisiciones ({reporte.requisiciones.length})</h3>
                  {reporte.requisiciones.length === 0 ? (
                    <p className="historial-reportes-muted">Este reporte no tiene requisiciones asociadas.</p>
                  ) : (
                    <div className="historial-reportes-requisitions">
                      {reporte.requisiciones.map((requisicion) => (
                        <article className="historial-reportes-requisition" key={requisicion.id_requisicion}>
                          <div className="historial-reportes-requisition-heading">
                            <strong>Requisición #{requisicion.id_requisicion}</strong>
                            <span>{mostrarEstado(requisicion.estado)}</span>
                          </div>
                          <dl className="historial-reportes-details">
                            <Dato etiqueta="Fecha" valor={mostrarFecha(requisicion.fecha)} />
                            <Dato etiqueta="Fecha de entrega" valor={mostrarFecha(requisicion.fecha_entrega)} />
                            <Dato etiqueta="Solicitó" valor={requisicion.solicitante ?? `Personal #${requisicion.id_solicita}`} />
                            <Dato etiqueta="Autorizó" valor={requisicion.autorizador ?? (requisicion.id_autoriza ? `Personal #${requisicion.id_autoriza}` : null)} />
                            <Dato etiqueta="Tipo de financiamiento" valor={requisicion.tipo_financiamiento} />
                            <div className="historial-reportes-description">
                              <dt>Justificación</dt>
                              <dd>{requisicion.justificacion}</dd>
                            </div>
                            <div className="historial-reportes-description">
                              <dt>Descripción del servicio solicitado</dt>
                              <dd>{requisicion.descripcion_servicio}</dd>
                            </div>
                          </dl>
                        </article>
                      ))}
                    </div>
                  )}
                </section>
              </article>
            ))}
          </section>
        )}
      </section>
    </main>
  )
}

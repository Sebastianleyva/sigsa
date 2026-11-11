import { useEffect, useState } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { authService } from '../../services/auth_services.js'
import { consultarReportesEnCurso, type ReporteMantenimiento } from '../../services/mantenimiento_services'
import './styles/mantenimientos_en_curso.css'

function mostrarFecha(fecha: string) {
  const fechaLocal = new Date(`${fecha}T00:00:00`)
  return Number.isNaN(fechaLocal.getTime())
    ? fecha
    : new Intl.DateTimeFormat('es-MX', { dateStyle: 'long' }).format(fechaLocal)
}

function mostrarEstado(estado: string) {
  const normalizado = estado.toLocaleUpperCase('es-MX')
  if (normalizado === 'ESPERA REQUISICIÓN' || normalizado === 'EN ESPERA DE REQUISICIÓN') {
    return 'En espera de requisición'
  }
  return estado.toLocaleLowerCase('es-MX').replace(/^./, (letra) => letra.toLocaleUpperCase('es-MX'))
}

function mostrarOrigen(reporte: ReporteMantenimiento) {
  if (reporte.id_incidencia !== null) {
    return `Incidencia #${reporte.id_incidencia}${reporte.lugar ? ` · ${reporte.lugar}` : ''}`
  }
  if (reporte.servicio) return reporte.servicio
  if (reporte.id_calendario !== null) return `Calendario #${reporte.id_calendario}`
  return 'Mantenimiento'
}

export default function MantenimientosEnCurso() {
  const navigate = useNavigate()
  const usuario = authService.getUsuarioActual()
  const autorizado = [1, 2, 3].includes(Number(usuario?.puesto))
  const [reportes, setReportes] = useState<ReporteMantenimiento[]>([])
  const [cargando, setCargando] = useState(true)
  const [error, setError] = useState('')
  const [intentoCarga, setIntentoCarga] = useState(0)
  const [seleccionadoId, setSeleccionadoId] = useState<number | null>(null)

  useEffect(() => {
    if (!autorizado) return

    let vigente = true
    consultarReportesEnCurso()
      .then((resultado) => {
        if (vigente) setReportes(resultado)
      })
      .catch((errorCarga: unknown) => {
        if (vigente) {
          setError(errorCarga instanceof Error
            ? errorCarga.message
            : 'No fue posible consultar los mantenimientos en curso.')
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
    <main className="mantenimientos-curso-page">
      <header className="mantenimientos-curso-topbar">
        <button className="mantenimientos-curso-brand" type="button" onClick={() => navigate('/mantenimiento')}>
          <span className="mantenimientos-curso-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </button>
        <span>SEGUIMIENTO DE MANTENIMIENTOS</span>
      </header>

      <section className="mantenimientos-curso-content">
        <button className="mantenimientos-curso-back" type="button" onClick={() => navigate('/mantenimiento')}>
          ← Volver al módulo
        </button>

        <div className="mantenimientos-curso-heading">
          <div>
            <span className="mantenimientos-curso-eyebrow">SEGUIMIENTO · SIGSA</span>
            <h1>Mantenimientos en curso</h1>
            <p>Consulta los reportes activos y los datos de las incidencias o servicios que les dieron origen.</p>
          </div>
          <div className="mantenimientos-curso-count" aria-label="Reportes activos">
            <strong>{reportes.length}</strong>
            <span>{reportes.length === 1 ? 'reporte activo' : 'reportes activos'}</span>
          </div>
        </div>

        {error && (
          <div className="mantenimientos-curso-error" role="alert">
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
        {cargando && <p className="mantenimientos-curso-message" role="status">Cargando reportes…</p>}
        {!cargando && !error && reportes.length === 0 && (
          <div className="mantenimientos-curso-empty">
            <span aria-hidden="true">✓</span>
            <h2>No hay mantenimientos activos</h2>
            <p>Los reportes de incidencias y servicios en curso aparecerán aquí.</p>
          </div>
        )}

        {!cargando && reportes.length > 0 && (
          <section className="mantenimientos-curso-list" aria-label="Reportes de mantenimiento activos">
            {reportes.map((reporte) => {
              const enCurso = reporte.estado.toLocaleUpperCase('es-MX') === 'EN CURSO'
              const seleccionado = seleccionadoId === reporte.id_reporte
              const descripcionReporte = reporte.descripcion.trim()
              const descripcionIncidencia = reporte.descripcion_incidencia?.trim() ?? ''

              return (
                <article
                  className={`mantenimientos-curso-card${seleccionado ? ' mantenimientos-curso-card-selected' : ''}`}
                  key={reporte.id_reporte}
                >
                  <header className="mantenimientos-curso-card-header">
                    <div>
                      <span className="mantenimientos-curso-card-kicker">REPORTE #{reporte.id_reporte}</span>
                      <h2>{mostrarOrigen(reporte)}</h2>
                    </div>
                    <div className="mantenimientos-curso-card-actions">
                      <span className={`mantenimientos-curso-status${reporte.estado.toLocaleUpperCase('es-MX').includes('ESPERA') ? ' mantenimientos-curso-status-espera' : ''}`}>
                        {mostrarEstado(reporte.estado)}
                      </span>
                      {enCurso && (
                        <button
                          className="mantenimientos-curso-select"
                          type="button"
                          aria-pressed={seleccionado}
                          onClick={() => setSeleccionadoId(seleccionado ? null : reporte.id_reporte)}
                        >
                          {seleccionado ? 'Seleccionado' : 'Seleccionar'}
                        </button>
                      )}
                    </div>
                  </header>
                  <dl className="mantenimientos-curso-details">
                    <div>
                      <dt>Origen</dt>
                      <dd>{reporte.tipo.toLocaleLowerCase('es-MX') === 'incidencia' ? 'Incidencia reportada' : reporte.tipo}</dd>
                    </div>
                    <div>
                      <dt>Inicio</dt>
                      <dd>{mostrarFecha(reporte.fecha_inicio)}</dd>
                    </div>
                    {reporte.urgencia && (
                      <div>
                        <dt>Urgencia</dt>
                        <dd>{reporte.urgencia}</dd>
                      </div>
                    )}
                    {reporte.reportante && (
                      <div>
                        <dt>Reportó</dt>
                        <dd>{reporte.reportante}</dd>
                      </div>
                    )}
                    {reporte.fecha_destinada && (
                      <div>
                        <dt>Fecha programada</dt>
                        <dd>{mostrarFecha(reporte.fecha_destinada)}</dd>
                      </div>
                    )}
                    {descripcionIncidencia && (
                      <div className="mantenimientos-curso-description">
                        <dt>Descripción de la incidencia</dt>
                        <dd>{descripcionIncidencia}</dd>
                      </div>
                    )}
                    {descripcionReporte && descripcionReporte !== descripcionIncidencia && (
                      <div className="mantenimientos-curso-description">
                        <dt>Descripción del servicio realizado</dt>
                        <dd>{descripcionReporte}</dd>
                      </div>
                    )}
                  </dl>
                  {seleccionado && enCurso && (
                    <div className="mantenimientos-curso-actions">
                      <span>El servicio está listo para registrar su conclusión.</span>
                      <button
                        type="button"
                        onClick={() => navigate(`/mantenimiento/reportes/${reporte.id_reporte}/concluir`)}
                      >
                        Concluir servicio
                        <span aria-hidden="true">→</span>
                      </button>
                    </div>
                  )}
                </article>
              )
            })}
          </section>
        )}
      </section>
    </main>
  )
}

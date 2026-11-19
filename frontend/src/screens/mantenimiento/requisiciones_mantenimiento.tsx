import { useEffect, useState } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { authService } from '../../services/auth_services.js'
import {
  aceptarRequisicion,
  consultarReportesRequisicion,
  puedeGestionarMantenimiento,
  type ReporteRequisicion,
} from '../../services/mantenimiento_services'
import './styles/requisiciones_mantenimiento.css'

function mostrarFecha(fecha: string) {
  const fechaLocal = new Date(`${fecha}T00:00:00`)
  return Number.isNaN(fechaLocal.getTime())
    ? fecha
    : new Intl.DateTimeFormat('es-MX', { dateStyle: 'long' }).format(fechaLocal)
}

function mostrarOrigen(reporte: ReporteRequisicion) {
  if (reporte.id_incidencia !== null) {
    return `Incidencia #${reporte.id_incidencia}${reporte.lugar ? ` · ${reporte.lugar}` : ''}`
  }
  if (reporte.servicio) return reporte.servicio
  if (reporte.id_calendario !== null) return `Calendario #${reporte.id_calendario}`
  return 'Mantenimiento'
}

export default function RequisicionesMantenimiento() {
  const navigate = useNavigate()
  const usuario = authService.getUsuarioActual()
  const autorizado = puedeGestionarMantenimiento(usuario)
  const puedeAutorizar = [1, 2].includes(Number(usuario?.puesto))
  const [reportes, setReportes] = useState<ReporteRequisicion[]>([])
  const [cargando, setCargando] = useState(true)
  const [procesandoId, setProcesandoId] = useState<number | null>(null)
  const [error, setError] = useState('')
  const [mensaje, setMensaje] = useState('')
  const [intentoCarga, setIntentoCarga] = useState(0)

  useEffect(() => {
    if (!autorizado) return

    let vigente = true
    consultarReportesRequisicion()
      .then((resultado) => {
        if (vigente) setReportes(resultado)
      })
      .catch((errorCarga: unknown) => {
        if (vigente) {
          setError(errorCarga instanceof Error
            ? errorCarga.message
            : 'No fue posible consultar los reportes que requieren requisición.')
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

  const disponibles = reportes.filter((reporte) => reporte.id_requisicion === null).length
  const requisicionesEnEspera = reportes.length - disponibles

  function seleccionarReporte(reporte: ReporteRequisicion) {
    if (reporte.id_requisicion !== null) return
    navigate(`/mantenimiento/requisiciones/${reporte.id_reporte}`)
  }

  async function autorizarRequisicion(reporte: ReporteRequisicion) {
    if (!puedeAutorizar || !reporte.id_requisicion || procesandoId !== null) return
    setProcesandoId(reporte.id_reporte)
    setError('')
    setMensaje('')
    try {
      await aceptarRequisicion(reporte.id_requisicion)
      setReportes((actuales) => actuales.filter((item) => item.id_reporte !== reporte.id_reporte))
      setMensaje(`La requisición #${reporte.id_requisicion} fue aprobada y el mantenimiento quedó en curso.`)
    } catch (errorAutorizacion: unknown) {
      setError(errorAutorizacion instanceof Error
        ? errorAutorizacion.message
        : 'No fue posible aprobar la requisición.')
    } finally {
      setProcesandoId(null)
    }
  }

  return (
    <main className="requisiciones-mantenimiento-page">
      <header className="requisiciones-mantenimiento-topbar">
        <button className="requisiciones-mantenimiento-brand" type="button" onClick={() => navigate('/mantenimiento')}>
          <span className="requisiciones-mantenimiento-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </button>
        <span>GESTIÓN DE REQUISICIONES</span>
      </header>

      <section className="requisiciones-mantenimiento-content">
        <button className="requisiciones-mantenimiento-back" type="button" onClick={() => navigate('/mantenimiento')}>
          ← Volver al módulo
        </button>

        <div className="requisiciones-mantenimiento-heading">
          <div>
            <span className="requisiciones-mantenimiento-eyebrow">ADMINISTRACIÓN · SIGSA</span>
            <h1>Requisiciones</h1>
            <p>Selecciona un mantenimiento que requiera requisición para completar su formato.</p>
          </div>
          <div className="requisiciones-mantenimiento-summary" aria-label="Resumen de reportes">
            <span><strong>{disponibles}</strong> por requisicionar</span>
            <span><strong>{requisicionesEnEspera}</strong> en espera</span>
          </div>
        </div>

        {mensaje && <p className="requisiciones-mantenimiento-success" role="status">{mensaje}</p>}
        {error && (
          <div className="requisiciones-mantenimiento-error" role="alert">
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

        {cargando && <p className="requisiciones-mantenimiento-message" role="status">Cargando servicios e incidencias…</p>}
        {!cargando && !error && reportes.length === 0 && (
          <div className="requisiciones-mantenimiento-empty">
            <span aria-hidden="true">✓</span>
            <h2>No hay mantenimientos por requisicionar</h2>
            <p>Los servicios e incidencias que requieran una requisición aparecerán aquí.</p>
          </div>
        )}

        {!cargando && reportes.length > 0 && (
          <section className="requisiciones-mantenimiento-list" aria-label="Mantenimientos que requieren requisición">
            {reportes.map((reporte) => {
              const tieneRequisicion = reporte.id_requisicion !== null

              return (
                <article
                  className="requisiciones-mantenimiento-card"
                  key={reporte.id_reporte}
                >
                  <div className="requisiciones-mantenimiento-card-main">
                    <div>
                      <span className="requisiciones-mantenimiento-card-kicker">REPORTE #{reporte.id_reporte}</span>
                      <h2>{mostrarOrigen(reporte)}</h2>
                      <p>{reporte.descripcion}</p>
                      <span className="requisiciones-mantenimiento-card-date">
                        Inicio: {mostrarFecha(reporte.fecha_inicio)}
                      </span>
                    </div>
                    {tieneRequisicion ? (
                      <div className="requisiciones-mantenimiento-request-actions">
                        <span className="requisiciones-mantenimiento-status requisiciones-mantenimiento-status-waiting">
                          Requisición {reporte.estado_requisicion?.toLocaleLowerCase('es-MX') ?? 'registrada'}
                        </span>
                        {puedeAutorizar && ['EN ESPERA', 'PENDIENTE'].includes(
                          reporte.estado_requisicion?.toLocaleUpperCase('es-MX') ?? '',
                        ) && (
                          <button
                            className="requisiciones-mantenimiento-approve"
                            type="button"
                            disabled={procesandoId !== null}
                            onClick={() => void autorizarRequisicion(reporte)}
                          >
                            {procesandoId === reporte.id_reporte ? 'Aprobando…' : 'Aceptar requisición'}
                          </button>
                        )}
                      </div>
                    ) : (
                      <button
                        className="requisiciones-mantenimiento-select"
                        type="button"
                        onClick={() => seleccionarReporte(reporte)}
                      >
                        Crear requisición
                      </button>
                    )}
                  </div>
                </article>
              )
            })}
          </section>
        )}

      </section>
    </main>
  )
}

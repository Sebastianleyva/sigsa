import { type FormEvent, useEffect, useState } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { authService } from '../../services/auth_services.js'
import {
  consultarReportesRequisicion,
  crearRequisicion,
  type ReporteRequisicion,
} from '../../services/mantenimiento_services'
import './styles/requisiciones_mantenimiento.css'

type FormularioRequisicion = {
  justificacion: string
  fecha: string
  descripcion_servicio: string
  tipo_financiamiento: string
}

const formularioVacio: FormularioRequisicion = {
  justificacion: '',
  fecha: '',
  descripcion_servicio: '',
  tipo_financiamiento: '',
}

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
  const autorizado = [1, 2, 3].includes(Number(usuario?.puesto))
  const [reportes, setReportes] = useState<ReporteRequisicion[]>([])
  const [reporteSeleccionado, setReporteSeleccionado] = useState<number | null>(null)
  const [formulario, setFormulario] = useState<FormularioRequisicion>(formularioVacio)
  const [cargando, setCargando] = useState(true)
  const [guardando, setGuardando] = useState(false)
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

  const seleccion = reportes.find((reporte) => reporte.id_reporte === reporteSeleccionado) ?? null
  const disponibles = reportes.filter((reporte) => reporte.id_requisicion === null).length
  const requisicionesEnEspera = reportes.length - disponibles

  function seleccionarReporte(reporte: ReporteRequisicion) {
    if (reporte.id_requisicion !== null) return
    setReporteSeleccionado(reporte.id_reporte)
    setFormulario({ ...formularioVacio, descripcion_servicio: reporte.descripcion })
    setError('')
    setMensaje('')
  }

  function actualizarCampo(campo: keyof FormularioRequisicion, valor: string) {
    setFormulario((actual) => ({ ...actual, [campo]: valor }))
  }

  async function enviarFormulario(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!seleccion || guardando) return

    setError('')
    setMensaje('')
    setGuardando(true)
    try {
      const requisicion = await crearRequisicion({
        id_reporte: seleccion.id_reporte,
        ...formulario,
      })
      setReportes((actuales) => actuales.map((reporte) => (
        reporte.id_reporte === seleccion.id_reporte
          ? {
              ...reporte,
              id_requisicion: Number(requisicion.id_requisicion),
              estado_requisicion: requisicion.estado,
            }
          : reporte
      )))
      setReporteSeleccionado(null)
      setFormulario(formularioVacio)
      setMensaje(`La requisición #${requisicion.id_requisicion} quedó registrada y está en espera.`)
    } catch (errorCreacion: unknown) {
      setError(errorCreacion instanceof Error ? errorCreacion.message : 'No fue posible crear la requisición.')
    } finally {
      setGuardando(false)
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
              const seleccionado = reporteSeleccionado === reporte.id_reporte

              return (
                <article
                  className={`requisiciones-mantenimiento-card${seleccionado ? ' requisiciones-mantenimiento-card-selected' : ''}`}
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
                      <span className="requisiciones-mantenimiento-status requisiciones-mantenimiento-status-waiting">
                        Requisición en espera
                      </span>
                    ) : (
                      <button
                        className="requisiciones-mantenimiento-select"
                        type="button"
                        aria-pressed={seleccionado}
                        onClick={() => seleccionarReporte(reporte)}
                      >
                        {seleccionado ? 'Seleccionado' : 'Crear requisición'}
                      </button>
                    )}
                  </div>
                </article>
              )
            })}
          </section>
        )}

        {seleccion && (
          <section className="requisiciones-mantenimiento-form-panel" aria-labelledby="requisicion-form-title">
            <div className="requisiciones-mantenimiento-form-heading">
              <div>
                <span className="requisiciones-mantenimiento-section-index">NUEVA REQUISICIÓN · REPORTE #{seleccion.id_reporte}</span>
                <h2 id="requisicion-form-title">Completa los datos de la solicitud</h2>
                <p>La descripción del mantenimiento se tomó del reporte. La requisición se guardará en espera.</p>
              </div>
              <button
                type="button"
                className="requisiciones-mantenimiento-cancel"
                onClick={() => {
                  setReporteSeleccionado(null)
                  setFormulario(formularioVacio)
                  setError('')
                }}
              >
                Cancelar
              </button>
            </div>

            <form className="requisiciones-mantenimiento-form" onSubmit={(event) => void enviarFormulario(event)}>
              <label htmlFor="requisicion-justificacion">Justificación <span>*</span></label>
              <textarea
                id="requisicion-justificacion"
                value={formulario.justificacion}
                onChange={(event) => actualizarCampo('justificacion', event.target.value)}
                rows={3}
                required
              />

              <label htmlFor="requisicion-fecha">Fecha de solicitud <span>*</span></label>
              <input
                id="requisicion-fecha"
                type="date"
                value={formulario.fecha}
                onChange={(event) => actualizarCampo('fecha', event.target.value)}
                required
              />

              <label htmlFor="requisicion-descripcion">Descripción del servicio <span>*</span></label>
              <textarea
                id="requisicion-descripcion"
                value={formulario.descripcion_servicio}
                onChange={(event) => actualizarCampo('descripcion_servicio', event.target.value)}
                rows={4}
                required
              />

              <label htmlFor="requisicion-financiamiento">Tipo de financiamiento <span>*</span></label>
              <input
                id="requisicion-financiamiento"
                value={formulario.tipo_financiamiento}
                onChange={(event) => actualizarCampo('tipo_financiamiento', event.target.value)}
                required
              />

              <div className="requisiciones-mantenimiento-form-footer">
                <span>Estado al guardar: <strong>EN ESPERA</strong></span>
                <button type="submit" disabled={guardando}>
                  {guardando ? 'Guardando…' : 'Enviar requisición'}
                </button>
              </div>
            </form>
          </section>
        )}
      </section>
    </main>
  )
}

import { type FormEvent, useEffect, useState } from 'react'
import { Navigate, useNavigate, useParams } from 'react-router-dom'
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

export default function CrearRequisicionMantenimiento() {
  const navigate = useNavigate()
  const { id_reporte: idReporteParam } = useParams()
  const usuario = authService.getUsuarioActual()
  const autorizado = [1, 2, 3].includes(Number(usuario?.puesto))
  const idReporte = Number(idReporteParam)
  const parametroValido = Number.isInteger(idReporte) && idReporte > 0
  const [reporte, setReporte] = useState<ReporteRequisicion | null>(null)
  const [formulario, setFormulario] = useState<FormularioRequisicion>(formularioVacio)
  const [cargando, setCargando] = useState(true)
  const [guardando, setGuardando] = useState(false)
  const [error, setError] = useState('')
  const [mensaje, setMensaje] = useState('')

  useEffect(() => {
    if (!autorizado || !parametroValido) return

    let vigente = true
    consultarReportesRequisicion()
      .then((reportes) => {
        if (!vigente) return
        const resultado = reportes.find((item) => item.id_reporte === idReporte) ?? null
        setReporte(resultado)
        if (resultado) {
          setFormulario((actual) => ({
            ...actual,
            descripcion_servicio: resultado.descripcion,
          }))
        }
        if (!resultado) setError('El reporte no está disponible para requisición.')
      })
      .catch((errorCarga: unknown) => {
        if (vigente) {
          setError(errorCarga instanceof Error
            ? errorCarga.message
            : 'No fue posible consultar el reporte.')
        }
      })
      .finally(() => {
        if (vigente) setCargando(false)
      })

    return () => {
      vigente = false
    }
  }, [autorizado, idReporte, parametroValido])

  if (!autorizado) return <Navigate to="/mantenimiento" replace />

  function actualizarCampo(campo: keyof FormularioRequisicion, valor: string) {
    setFormulario((actual) => ({ ...actual, [campo]: valor }))
  }

  async function enviarFormulario(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!reporte || guardando || reporte.id_requisicion !== null) return

    setError('')
    setMensaje('')
    setGuardando(true)
    try {
      const requisicion = await crearRequisicion({
        id_reporte: reporte.id_reporte,
        ...formulario,
      })
      setMensaje(`La requisición #${requisicion.id_requisicion} quedó registrada y está en espera.`)
      setReporte((actual) => actual
        ? { ...actual, id_requisicion: Number(requisicion.id_requisicion), estado_requisicion: requisicion.estado }
        : actual)
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
        <button className="requisiciones-mantenimiento-back" type="button" onClick={() => navigate('/mantenimiento/requisiciones')}>
          ← Volver a requisiciones
        </button>

        <div className="requisiciones-mantenimiento-heading">
          <div>
            <span className="requisiciones-mantenimiento-eyebrow">ADMINISTRACIÓN · SIGSA</span>
            <h1>Nueva requisición</h1>
            <p>Completa los datos de la requisición para el mantenimiento seleccionado.</p>
          </div>
        </div>

        {parametroValido && cargando && <p className="requisiciones-mantenimiento-message" role="status">Cargando reporte…</p>}
        {!parametroValido && <p className="requisiciones-mantenimiento-error" role="alert">El número de reporte no es válido.</p>}
        {!cargando && error && <p className="requisiciones-mantenimiento-error" role="alert">{error}</p>}
        {!cargando && !error && reporte && reporte.id_requisicion !== null && (
          <p className="requisiciones-mantenimiento-error" role="alert">Este reporte ya tiene una requisición registrada.</p>
        )}
        {!cargando && !error && reporte && reporte.id_requisicion === null && (
          <section className="requisiciones-mantenimiento-form-panel" aria-labelledby="requisicion-form-title">
            <div className="requisiciones-mantenimiento-form-heading">
              <div>
                <span className="requisiciones-mantenimiento-section-index">
                  REPORTE #{reporte.id_reporte} · {mostrarOrigen(reporte)}
                </span>
                <h2 id="requisicion-form-title">Completa los datos de la solicitud</h2>
                <p>
                  Inicio del mantenimiento: {mostrarFecha(reporte.fecha_inicio)}. La requisición se guardará en espera.
                </p>
              </div>
            </div>

            {mensaje && <p className="requisiciones-mantenimiento-success" role="status">{mensaje}</p>}
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
                <button type="button" className="requisiciones-mantenimiento-cancel" onClick={() => navigate('/mantenimiento/requisiciones')}>
                  Cancelar
                </button>
                <button type="submit" disabled={guardando}>
                  {guardando ? 'Guardando…' : 'Enviar requisición'}
                </button>
              </div>
            </form>
          </section>
        )}
        {mensaje && reporte?.id_requisicion !== null && (
          <button type="button" className="requisiciones-mantenimiento-select" onClick={() => navigate('/mantenimiento/requisiciones')}>
            Volver a requisiciones
          </button>
        )}
      </section>
    </main>
  )
}

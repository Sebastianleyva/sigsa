import { type FormEvent, useEffect, useState } from 'react'
import { Navigate, useNavigate, useParams } from 'react-router-dom'
import { authService } from '../../services/auth_services.js'
import { consultarReportesEnCurso, finalizarReporte, type ReporteMantenimiento } from '../../services/mantenimiento_services'
import './styles/concluir_mantenimiento.css'

function fechaLocal(fecha: Date) {
  const anio = fecha.getFullYear()
  const mes = String(fecha.getMonth() + 1).padStart(2, '0')
  const dia = String(fecha.getDate()).padStart(2, '0')
  return `${anio}-${mes}-${dia}`
}

function mostrarFecha(fecha: string) {
  const fechaLocalizada = new Date(`${fecha}T00:00:00`)
  return Number.isNaN(fechaLocalizada.getTime())
    ? fecha
    : new Intl.DateTimeFormat('es-MX', { dateStyle: 'long' }).format(fechaLocalizada)
}

function mostrarOrigen(reporte: ReporteMantenimiento) {
  if (reporte.id_incidencia !== null) {
    return `Incidencia #${reporte.id_incidencia}${reporte.lugar ? ` · ${reporte.lugar}` : ''}`
  }
  if (reporte.servicio) return reporte.servicio
  if (reporte.id_calendario !== null) return `Calendario #${reporte.id_calendario}`
  return `Reporte #${reporte.id_reporte}`
}

export default function ConcluirMantenimiento() {
  const navigate = useNavigate()
  const { id_reporte: idReporteParam } = useParams()
  const idReporte = Number(idReporteParam)
  const idValido = Number.isSafeInteger(idReporte) && idReporte > 0
  const usuario = authService.getUsuarioActual()
  const autorizado = [1, 2, 3].includes(Number(usuario?.puesto))
  const [reporte, setReporte] = useState<ReporteMantenimiento | null>(null)
  const [fechaFinal, setFechaFinal] = useState(() => fechaLocal(new Date()))
  const [descripcion, setDescripcion] = useState('')
  const [cargando, setCargando] = useState(idValido)
  const [guardando, setGuardando] = useState(false)
  const [completado, setCompletado] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    if (!autorizado || !idValido) return

    let vigente = true
    consultarReportesEnCurso(idReporte)
      .then((reportes) => {
        const encontrado = reportes.find(
          (reporteActual) => reporteActual.estado.toLocaleUpperCase('es-MX') === 'EN CURSO',
        )
        if (vigente && encontrado) {
          setReporte(encontrado)
        } else if (vigente) {
          setError('El servicio no está en curso o ya fue concluido.')
        }
      })
      .catch((errorCarga: unknown) => {
        if (vigente) {
          setError(errorCarga instanceof Error
            ? errorCarga.message
            : 'No fue posible consultar el reporte del servicio.')
        }
      })
      .finally(() => {
        if (vigente) setCargando(false)
      })

    return () => {
      vigente = false
    }
  }, [autorizado, idReporte, idValido])

  if (!autorizado) return <Navigate to="/mantenimiento" replace />

  async function enviarReporte(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!reporte || guardando || completado) return

    setError('')
    setGuardando(true)
    try {
      const resultado = await finalizarReporte(reporte.id_reporte, fechaFinal, descripcion)
      setReporte((actual) => actual
        ? { ...actual, ...resultado }
        : actual)
      setCompletado(true)
    } catch (errorGuardado: unknown) {
      setError(errorGuardado instanceof Error
        ? errorGuardado.message
        : 'No fue posible guardar el reporte del servicio.')
    } finally {
      setGuardando(false)
    }
  }

  return (
    <main className="concluir-mantenimiento-page">
      <header className="concluir-mantenimiento-topbar">
        <button className="concluir-mantenimiento-brand" type="button" onClick={() => navigate('/mantenimiento')}>
          <span className="concluir-mantenimiento-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </button>
        <span>CIERRE DE MANTENIMIENTO</span>
      </header>

      <section className="concluir-mantenimiento-content">
        <button
          className="concluir-mantenimiento-back"
          type="button"
          onClick={() => navigate('/mantenimiento/reportes/en-curso')}
        >
          ← Volver a mantenimientos en curso
        </button>

        <div className="concluir-mantenimiento-heading">
          <span className="concluir-mantenimiento-eyebrow">SEGUIMIENTO · SIGSA</span>
          <h1>Reporte del servicio realizado</h1>
          <p>Registra la fecha de conclusión y describe el trabajo que se llevó a cabo.</p>
        </div>

        {cargando && <p className="concluir-mantenimiento-message" role="status">Cargando reporte…</p>}
        {!cargando && error && !reporte && <p className="concluir-mantenimiento-error" role="alert">{error}</p>}
        {!cargando && !idValido && (
          <p className="concluir-mantenimiento-error" role="alert">El número de reporte no es válido.</p>
        )}

        {!cargando && reporte && (
          <>
            <section className="concluir-mantenimiento-context" aria-label="Datos del mantenimiento">
              <div>
                <span>REPORTE #{reporte.id_reporte}</span>
                <h2>{mostrarOrigen(reporte)}</h2>
                <p>Inicio: {mostrarFecha(reporte.fecha_inicio)}</p>
              </div>
              {reporte.descripcion_incidencia && (
                <div className="concluir-mantenimiento-incidencia">
                  <strong>Descripción original de la incidencia</strong>
                  <p>{reporte.descripcion_incidencia}</p>
                </div>
              )}
            </section>

            {completado ? (
              <div className="concluir-mantenimiento-success" role="status">
                <span aria-hidden="true">✓</span>
                <div>
                  <h2>Servicio concluido</h2>
                  <p>El reporte quedó finalizado y ya no aparece entre los mantenimientos en curso.</p>
                  <button type="button" onClick={() => navigate('/mantenimiento/reportes/en-curso')}>
                    Volver a mantenimientos en curso
                  </button>
                </div>
              </div>
            ) : (
              <form className="concluir-mantenimiento-form" onSubmit={(event) => void enviarReporte(event)}>
                <span className="concluir-mantenimiento-section-index">CIERRE DEL SERVICIO</span>
                <label htmlFor="reporte-fecha-final">Fecha de conclusión <span>*</span></label>
                <input
                  id="reporte-fecha-final"
                  type="date"
                  value={fechaFinal}
                  onChange={(event) => setFechaFinal(event.target.value)}
                  required
                />

                <label htmlFor="reporte-descripcion">Descripción del servicio realizado <span>*</span></label>
                <textarea
                  id="reporte-descripcion"
                  value={descripcion}
                  onChange={(event) => setDescripcion(event.target.value)}
                  placeholder="Describe los trabajos realizados y su resultado"
                  rows={6}
                  required
                />

                {error && <p className="concluir-mantenimiento-error" role="alert">{error}</p>}

                <div className="concluir-mantenimiento-form-footer">
                  <span>Al guardar, el estado del reporte cambiará a <strong>FINALIZADO</strong>.</span>
                  <button type="submit" disabled={guardando}>
                    {guardando ? 'Guardando…' : 'Concluir servicio'}
                  </button>
                </div>
              </form>
            )}
          </>
        )}
      </section>
    </main>
  )
}

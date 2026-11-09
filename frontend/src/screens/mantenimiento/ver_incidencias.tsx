import { useEffect, useState } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { authService } from '../../services/auth_services.js'
import {
  aceptarIncidencia,
  consultarIncidencias,
  rechazarIncidencia,
  type IncidenciaMantenimiento,
} from '../../services/mantenimiento_services'
import './styles/ver_incidencias.css'

type Aviso = {
  tipo: 'exito' | 'error'
  mensaje: string
}

function mostrarFecha(fecha: string) {
  const fechaLocal = new Date(`${fecha}T00:00:00`)
  return Number.isNaN(fechaLocal.getTime())
    ? fecha
    : new Intl.DateTimeFormat('es-MX', { dateStyle: 'long' }).format(fechaLocal)
}

function normalizarEstado(estado: string) {
  return estado.toLocaleUpperCase('es-MX')
}

export default function VerIncidenciasMantenimiento() {
  const navigate = useNavigate()
  const usuario = authService.getUsuarioActual()
  const autorizado = [1, 2, 3].includes(Number(usuario?.puesto))
  const [incidencias, setIncidencias] = useState<IncidenciaMantenimiento[]>([])
  const [seleccionada, setSeleccionada] = useState<number | null>(null)
  const [aviso, setAviso] = useState<Aviso | null>(null)
  const [cargando, setCargando] = useState(true)
  const [procesandoId, setProcesandoId] = useState<number | null>(null)
  const [intentoCarga, setIntentoCarga] = useState(0)

  useEffect(() => {
    if (!autorizado) return

    let vigente = true
    consultarIncidencias()
      .then((resultado) => {
        if (vigente) setIncidencias(resultado)
      })
      .catch((error: unknown) => {
        if (vigente) {
          setAviso({
            tipo: 'error',
            mensaje: error instanceof Error ? error.message : 'No fue posible consultar las incidencias.',
          })
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

  const incidenciasPendientes = incidencias.filter(
    (incidencia) => normalizarEstado(incidencia.estado) === 'PENDIENTE',
  )

  async function procesarSeleccion(decision: 'aceptar' | 'rechazar') {
    if (seleccionada === null || procesandoId !== null) return
    const incidencia = incidencias.find((item) => item.id_incidencia === seleccionada)
    if (!incidencia || normalizarEstado(incidencia.estado) !== 'PENDIENTE') return

    const accion = decision === 'aceptar' ? 'aceptar' : 'rechazar'
    if (!window.confirm(`¿Confirmas que deseas ${accion} la incidencia #${incidencia.id_incidencia}?`)) return

    setProcesandoId(seleccionada)
    setAviso(null)
    try {
      const resultado = decision === 'aceptar'
        ? await aceptarIncidencia(seleccionada)
        : await rechazarIncidencia(seleccionada)
      setIncidencias((actuales) => actuales.map((item) => (
        item.id_incidencia === seleccionada
          ? {
              ...item,
              estado: resultado.estado,
              id_autoriza: resultado.id_autoriza === null ? item.id_autoriza : Number(resultado.id_autoriza),
            }
          : item
      )))
      setSeleccionada(null)
      setAviso({
        tipo: 'exito',
        mensaje: `La incidencia #${incidencia.id_incidencia} fue ${decision === 'aceptar' ? 'aceptada' : 'rechazada'}.`,
      })
    } catch (error) {
      setAviso({
        tipo: 'error',
        mensaje: error instanceof Error ? error.message : `No fue posible ${accion} la incidencia.`,
      })
    } finally {
      setProcesandoId(null)
    }
  }

  return (
    <main className="ver-incidencias-page">
      <header className="ver-incidencias-topbar">
        <button className="ver-incidencias-brand" type="button" onClick={() => navigate('/mantenimiento')}>
          <span className="ver-incidencias-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </button>
        <span>GESTIÓN DE INCIDENCIAS</span>
      </header>

      <section className="ver-incidencias-content">
        <button className="ver-incidencias-back" type="button" onClick={() => navigate('/mantenimiento')}>
          ← Volver al módulo
        </button>

        <div className="ver-incidencias-heading">
          <div>
            <span className="ver-incidencias-eyebrow">ADMINISTRACIÓN · SIGSA</span>
            <h1>Incidencias</h1>
            <p>Consulta los reportes recibidos y resuelve las incidencias pendientes.</p>
          </div>
          <div className="ver-incidencias-summary" aria-label="Resumen de incidencias">
            <span><strong>{incidencias.length}</strong> registradas</span>
            <span><strong>{incidenciasPendientes.length}</strong> pendientes</span>
          </div>
        </div>

        {aviso && (
          <div
            className={`ver-incidencias-notice ver-incidencias-notice-${aviso.tipo}`}
            role={aviso.tipo === 'error' ? 'alert' : 'status'}
            aria-live="polite"
          >
            <span aria-hidden="true">{aviso.tipo === 'exito' ? '✓' : '!'}</span>
            <span>{aviso.mensaje}</span>
            {aviso.tipo === 'error' && !cargando && incidencias.length === 0 && (
              <button
                type="button"
                onClick={() => {
                  setAviso(null)
                  setCargando(true)
                  setIntentoCarga((actual) => actual + 1)
                }}
              >
                Reintentar
              </button>
            )}
          </div>
        )}

        {cargando && <p className="ver-incidencias-message" role="status">Cargando incidencias…</p>}
        {!cargando && incidencias.length === 0 && !aviso && (
          <div className="ver-incidencias-empty">
            <span aria-hidden="true">✓</span>
            <h2>No hay incidencias registradas</h2>
            <p>Cuando se envíen nuevos reportes, aparecerán aquí.</p>
          </div>
        )}

        {!cargando && incidencias.length > 0 && (
          <section className="ver-incidencias-list" aria-label="Incidencias registradas">
            {incidencias.map((incidencia) => {
              const estado = normalizarEstado(incidencia.estado)
              const pendiente = estado === 'PENDIENTE'
              const estaSeleccionada = seleccionada === incidencia.id_incidencia
              const reportante = incidencia.reportante.trim() || `Personal #${incidencia.id_personal}`

              return (
                <article
                  className={`ver-incidencia-card${estaSeleccionada ? ' ver-incidencia-card-seleccionada' : ''}`}
                  key={incidencia.id_incidencia}
                >
                  <div className="ver-incidencia-card-header">
                    <div className="ver-incidencia-card-title">
                      <span className="ver-incidencia-id">INCIDENCIA #{incidencia.id_incidencia}</span>
                      <h2>{incidencia.lugar}</h2>
                    </div>
                    <div className="ver-incidencia-card-meta">
                      <span className={`ver-incidencia-status ver-incidencia-status-${estado.toLowerCase()}`}>
                        {estado}
                      </span>
                      {pendiente && (
                        <button
                          className="ver-incidencia-select"
                          type="button"
                          aria-pressed={estaSeleccionada}
                          onClick={() => {
                            setSeleccionada(estaSeleccionada ? null : incidencia.id_incidencia)
                            setAviso(null)
                          }}
                        >
                          {estaSeleccionada ? 'Seleccionada' : 'Seleccionar'}
                        </button>
                      )}
                    </div>
                  </div>

                  <dl className="ver-incidencia-details">
                    <div>
                      <dt>Reportó</dt>
                      <dd>{reportante}</dd>
                    </div>
                    <div>
                      <dt>Fecha</dt>
                      <dd>{mostrarFecha(incidencia.fecha)}</dd>
                    </div>
                    <div>
                      <dt>Urgencia</dt>
                      <dd><span className={`ver-incidencia-urgency ver-incidencia-urgency-${incidencia.urgencia.toLowerCase()}`}>{incidencia.urgencia}</span></dd>
                    </div>
                    <div className="ver-incidencia-description">
                      <dt>Descripción</dt>
                      <dd>{incidencia.descripcion}</dd>
                    </div>
                  </dl>

                  {estaSeleccionada && pendiente && (
                    <div className="ver-incidencia-actions">
                      <span>Selecciona una decisión para esta incidencia.</span>
                      <div>
                        <button
                          type="button"
                          className="ver-incidencia-reject"
                          disabled={procesandoId !== null}
                          onClick={() => void procesarSeleccion('rechazar')}
                        >
                          {procesandoId === incidencia.id_incidencia ? 'Procesando…' : 'Rechazar'}
                        </button>
                        <button
                          type="button"
                          className="ver-incidencia-accept"
                          disabled={procesandoId !== null}
                          onClick={() => void procesarSeleccion('aceptar')}
                        >
                          {procesandoId === incidencia.id_incidencia ? 'Procesando…' : 'Aceptar'}
                        </button>
                      </div>
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

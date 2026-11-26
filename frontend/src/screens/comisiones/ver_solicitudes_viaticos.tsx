import { useEffect, useState } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { authService } from '../../services/auth_services.js'
import {
  consultarSolicitudesViaticosMes,
  obtenerPdfSolicitudViaticos,
} from '../../services/comisiones_services'
import type { SolicitudViaticosResumen } from '../../services/comisiones_services'
import './styles/comisiones_operacion.css'

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

export default function VerSolicitudesViaticos() {
  const navigate = useNavigate()
  const usuario = authService.getUsuarioActual()
  const autorizado = [1, 2].includes(Number(usuario?.puesto))
    || Number(usuario?.area) === 6
  const hoy = new Date()
  const inicioDelAno = new Date(hoy.getFullYear(), 0, 1)
  const mesActual = new Date(hoy.getFullYear(), hoy.getMonth(), 1)
  const [mesConsulta, setMesConsulta] = useState(mesActual)
  const [solicitudes, setSolicitudes] = useState<SolicitudViaticosResumen[]>([])
  const [cargando, setCargando] = useState(true)
  const [error, setError] = useState('')
  const [errorPdf, setErrorPdf] = useState('')
  const [intentoCarga, setIntentoCarga] = useState(0)
  const [idPdfSolicitado, setIdPdfSolicitado] = useState<number | null>(null)

  function cambiarMes(cantidad: number) {
    const nuevoMes = new Date(mesConsulta.getFullYear(), mesConsulta.getMonth() + cantidad, 1)
    if (nuevoMes < inicioDelAno || nuevoMes > mesActual) return
    setCargando(true)
    setError('')
    setErrorPdf('')
    setMesConsulta(nuevoMes)
  }

  useEffect(() => {
    if (!autorizado) return

    let vigente = true
    consultarSolicitudesViaticosMes(mesConsulta.getMonth() + 1, mesConsulta.getFullYear())
      .then((resultado) => {
        if (vigente) setSolicitudes(resultado)
      })
      .catch((errorCarga: unknown) => {
        if (vigente) {
          setError(errorCarga instanceof Error
            ? errorCarga.message
            : 'No fue posible consultar las solicitudes de viáticos.')
        }
      })
      .finally(() => {
        if (vigente) setCargando(false)
      })

    return () => {
      vigente = false
    }
  }, [autorizado, intentoCarga, mesConsulta])

  async function descargarPdf(solicitud: SolicitudViaticosResumen) {
    const ventana = window.open('', '_blank')
    if (!ventana) {
      setErrorPdf('Permite las ventanas emergentes para abrir el documento imprimible.')
      return
    }

    setErrorPdf('')
    setIdPdfSolicitado(solicitud.id_solicitud_viaticos)
    try {
      const url = await obtenerPdfSolicitudViaticos(solicitud.id_solicitud_viaticos)
      ventana.location.href = url
    } catch (errorDescarga) {
      ventana.close()
      setErrorPdf(errorDescarga instanceof Error
        ? errorDescarga.message
        : 'No fue posible generar el PDF de la solicitud.')
    } finally {
      setIdPdfSolicitado(null)
    }
  }

  const nombreMes = mesConsulta.toLocaleDateString('es-MX', {
    month: 'long',
    year: 'numeric',
  })

  if (!autorizado) return <Navigate to="/comisiones" replace />

  return (
    <main className="comisiones-operacion-page">
      <header className="comisiones-operacion-topbar">
        <button className="comisiones-operacion-brand" type="button" onClick={() => navigate('/home')}>
          <span className="comisiones-operacion-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </button>
        <span className="comisiones-operacion-topbar-label">MÓDULO DE COMISIONES</span>
      </header>

      <section className="comisiones-operacion-content">
        <button className="comisiones-operacion-back" type="button" onClick={() => navigate('/comisiones')}>
          ← Volver a comisiones
        </button>

        <div className="viaticos-historial-heading">
          <div className="comisiones-operacion-heading">
            <span className="comisiones-operacion-eyebrow">CONSULTA · SIGSA</span>
            <h1>Ver solicitudes de viáticos</h1>
            <p>Consulta las solicitudes registradas y descarga el documento de cada una.</p>
          </div>
          <div className="viaticos-historial-controls">
            <div className="viaticos-historial-month" aria-label="Navegación por mes">
              <button
                type="button"
                onClick={() => cambiarMes(-1)}
                disabled={mesConsulta <= inicioDelAno}
                aria-label="Consultar el mes anterior"
              >
                ←
              </button>
              <span>{nombreMes}</span>
              <button
                type="button"
                onClick={() => cambiarMes(1)}
                disabled={mesConsulta >= mesActual}
                aria-label="Consultar el mes siguiente"
              >
                →
              </button>
            </div>
            {!cargando && !error && (
              <span className="viaticos-historial-count">
                <strong>{solicitudes.length}</strong>
                {solicitudes.length === 1 ? ' solicitud' : ' solicitudes'}
              </span>
            )}
          </div>
        </div>

        {error && (
          <div className="comisiones-operacion-state error viaticos-historial-error" role="alert">
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
        {errorPdf && <p className="comisiones-operacion-state error" role="alert">{errorPdf}</p>}
        {cargando && <p className="comisiones-operacion-state" role="status">Cargando solicitudes...</p>}
        {!cargando && !error && solicitudes.length === 0 && (
          <div className="comisiones-operacion-state viaticos-historial-empty">
            <h2>No hay solicitudes de viáticos en este mes</h2>
            <p>Las solicitudes registradas durante el mes aparecerán aquí.</p>
          </div>
        )}

        {!cargando && !error && solicitudes.length > 0 && (
          <section className="comisiones-operacion-list" aria-label="Solicitudes de viáticos">
            {solicitudes.map((solicitud) => (
              <article className="comisiones-operacion-card" key={solicitud.id_solicitud_viaticos}>
                <div className="comisiones-operacion-card-header">
                  <span>
                    SOLICITUD #{solicitud.id_solicitud_viaticos}
                    {' · '}COMISIÓN #{solicitud.id_comision}
                  </span>
                  <span>{mostrarEstado(solicitud.estado_comision)}</span>
                </div>
                <h2>{solicitud.comisionado}</h2>
                <dl>
                  <div><dt>Fecha de registro</dt><dd>{mostrarFecha(solicitud.fecha)}</dd></div>
                  <div><dt>Periodo de comisión</dt><dd>{mostrarFecha(solicitud.fecha_comision)}</dd></div>
                  <div><dt>Lugar</dt><dd>{solicitud.lugar}</dd></div>
                  <div>
                    <dt>Importe total</dt>
                    <dd>{solicitud.total_importe.toLocaleString('es-MX', {
                      style: 'currency',
                      currency: 'MXN',
                    })}</dd>
                  </div>
                </dl>
                <button
                  className="comisiones-operacion-card-action"
                  type="button"
                  onClick={() => void descargarPdf(solicitud)}
                  disabled={idPdfSolicitado !== null}
                >
                  {idPdfSolicitado === solicitud.id_solicitud_viaticos ? 'Preparando PDF...' : 'Descargar PDF'}
                </button>
              </article>
            ))}
          </section>
        )}
      </section>
    </main>
  )
}

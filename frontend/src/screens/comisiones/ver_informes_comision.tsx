import { useEffect, useState } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import { authService } from '../../services/auth_services.js'
import {
  consultarInformesComisionMes,
  obtenerPdfInformeComision,
} from '../../services/comisiones_services'
import type { InformeComisionResumen } from '../../services/comisiones_services'
import './styles/comisiones_operacion.css'

function mostrarFecha(fecha: string | null) {
  if (!fecha) return 'No registrada'
  const fechaLocal = new Date(`${fecha}T00:00:00`)
  return Number.isNaN(fechaLocal.getTime())
    ? fecha
    : new Intl.DateTimeFormat('es-MX', { dateStyle: 'long' }).format(fechaLocal)
}

function mostrarPeriodo(informe: InformeComisionResumen) {
  const { fecha_inicio: inicio, fecha_final: final } = informe
  if (inicio && final && inicio !== final) return `${mostrarFecha(inicio)} al ${mostrarFecha(final)}`
  return mostrarFecha(inicio ?? final ?? informe.fecha_comision)
}

function mostrarEstado(estado: string) {
  return estado.toLocaleLowerCase('es-MX').replace(/^./, (letra) => letra.toLocaleUpperCase('es-MX'))
}

export default function VerInformesComision() {
  const navigate = useNavigate()
  const usuario = authService.getUsuarioActual()
  const autorizado = [1, 2].includes(Number(usuario?.puesto))
    || Number(usuario?.area) === 6
  const hoy = new Date()
  const inicioDelAno = new Date(hoy.getFullYear(), 0, 1)
  const mesActual = new Date(hoy.getFullYear(), hoy.getMonth(), 1)
  const [mesConsulta, setMesConsulta] = useState(mesActual)
  const [informes, setInformes] = useState<InformeComisionResumen[]>([])
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
    consultarInformesComisionMes(mesConsulta.getMonth() + 1, mesConsulta.getFullYear())
      .then((resultado) => {
        if (vigente) setInformes(resultado)
      })
      .catch((errorCarga: unknown) => {
        if (vigente) {
          setError(errorCarga instanceof Error
            ? errorCarga.message
            : 'No fue posible consultar los informes de comisión.')
        }
      })
      .finally(() => {
        if (vigente) setCargando(false)
      })

    return () => {
      vigente = false
    }
  }, [autorizado, intentoCarga, mesConsulta])

  async function descargarPdf(informe: InformeComisionResumen) {
    const ventana = window.open('', '_blank')
    if (!ventana) {
      setErrorPdf('Permite las ventanas emergentes para abrir el documento imprimible.')
      return
    }

    setErrorPdf('')
    setIdPdfSolicitado(informe.id_informe_comision)
    try {
      ventana.location.href = await obtenerPdfInformeComision(informe.id_informe_comision)
    } catch (errorDescarga) {
      ventana.close()
      setErrorPdf(errorDescarga instanceof Error
        ? errorDescarga.message
        : 'No fue posible generar el PDF del informe.')
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
            <h1>Ver informes de comisión</h1>
            <p>Consulta los informes registrados por mes de la comisión y descarga el documento de cada uno.</p>
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
                <strong>{informes.length}</strong>
                {informes.length === 1 ? ' informe' : ' informes'}
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
        {cargando && <p className="comisiones-operacion-state" role="status">Cargando informes...</p>}
        {!cargando && !error && informes.length === 0 && (
          <div className="comisiones-operacion-state viaticos-historial-empty">
            <h2>No hay informes de comisión en este mes</h2>
            <p>Los informes de las comisiones realizadas durante el mes aparecerán aquí.</p>
          </div>
        )}

        {!cargando && !error && informes.length > 0 && (
          <section className="comisiones-operacion-list" aria-label="Informes de comisión">
            {informes.map((informe) => (
              <article className="comisiones-operacion-card" key={informe.id_informe_comision}>
                <div className="comisiones-operacion-card-header">
                  <span>
                    INFORME #{informe.id_informe_comision}
                    {' · '}COMISIÓN #{informe.id_comision}
                  </span>
                  <span>{mostrarEstado(informe.estado_comision)}</span>
                </div>
                <h2>{informe.comisionado}</h2>
                <dl>
                  <div><dt>Periodo de comisión</dt><dd>{mostrarPeriodo(informe)}</dd></div>
                  <div><dt>Lugar</dt><dd>{informe.lugar}</dd></div>
                  <div><dt>Institución</dt><dd>{informe.institucion_solicita}</dd></div>
                  <div><dt>Área</dt><dd>{informe.area_nombre ?? 'Sin área'}</dd></div>
                </dl>
                <button
                  className="comisiones-operacion-card-action"
                  type="button"
                  onClick={() => void descargarPdf(informe)}
                  disabled={idPdfSolicitado !== null}
                >
                  {idPdfSolicitado === informe.id_informe_comision ? 'Preparando PDF...' : 'Descargar PDF'}
                </button>
              </article>
            ))}
          </section>
        )}
      </section>
    </main>
  )
}

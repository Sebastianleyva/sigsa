import { useEffect, useState } from 'react'
import { Navigate, useNavigate } from 'react-router-dom'
import * as XLSX from 'xlsx'
import { authService } from '../../services/auth_services.js'
import {
  consultarHistorialRequisiciones,
  obtenerPdfRequisicion,
  puedeVerHistorialRequisiciones,
  type RequisicionHistorial,
} from '../../services/mantenimiento_services'
import './styles/historial_reportes.css'
import './styles/historial_requisiciones.css'

function mostrarFecha(fecha: string | null | undefined) {
  if (!fecha) return 'No registrada'
  const fechaLocal = new Date(`${fecha}T00:00:00`)
  return Number.isNaN(fechaLocal.getTime())
    ? fecha
    : new Intl.DateTimeFormat('es-MX', { dateStyle: 'long' }).format(fechaLocal)
}

function mostrarEstado(estado: string) {
  return estado.toLocaleLowerCase('es-MX').replace(/^./, (letra) => letra.toLocaleUpperCase('es-MX'))
}

function mostrarOrigen(requisicion: RequisicionHistorial) {
  if (requisicion.servicio) return requisicion.servicio
  if (requisicion.lugar) return `Incidencia · ${requisicion.lugar}`
  return `Reporte #${requisicion.id_reporte}`
}

function mostrarCodigo(requisicion: RequisicionHistorial) {
  if (!requisicion.codigo_contable) return null
  const origen = requisicion.tipo_codigo === 'CC_CENTRAL' ? 'Central' : 'Dirección'
  return `${requisicion.codigo_contable} (${origen})`
}

function descargarExcel(requisiciones: RequisicionHistorial[], mesConsulta: Date) {
  const filas = requisiciones.map((requisicion) => ({
    'No. de requisición': requisicion.id_requisicion,
    Tipo: requisicion.tipo_requisicion ?? '',
    Estado: requisicion.estado,
    Fecha: mostrarFecha(requisicion.fecha),
    'Fecha de entrega': mostrarFecha(requisicion.fecha_entrega),
    'No. de reporte': requisicion.id_reporte,
    'Tipo de reporte': requisicion.tipo_reporte,
    Origen: mostrarOrigen(requisicion),
    Solicitó: requisicion.solicitante ?? '',
    Autorizó: requisicion.autorizador ?? '',
    Elaboró: requisicion.elaborador ?? '',
    'Tipo de financiamiento': requisicion.tipo_financiamiento,
    'Código contable': mostrarCodigo(requisicion) ?? '',
    Justificación: requisicion.justificacion ?? '',
    'Descripción del servicio solicitado': requisicion.descripcion_servicio,
    Partidas: requisicion.detalles
      .map((detalle) => `${detalle.cantidad ?? '-'} × ${detalle.descripcion}${detalle.num_lote ? ` (lote ${detalle.num_lote})` : ''}`)
      .join('; '),
  }))

  const hoja = XLSX.utils.json_to_sheet(filas)
  hoja['!cols'] = [
    { wch: 16 }, { wch: 14 }, { wch: 14 }, { wch: 20 }, { wch: 20 }, { wch: 14 },
    { wch: 20 }, { wch: 28 }, { wch: 28 }, { wch: 28 }, { wch: 28 }, { wch: 22 },
    { wch: 26 }, { wch: 42 }, { wch: 42 }, { wch: 60 },
  ]
  hoja['!freeze'] = { xSplit: 0, ySplit: 1 }

  const libro = XLSX.utils.book_new()
  XLSX.utils.book_append_sheet(libro, hoja, 'Historial de requisiciones')
  const mes = String(mesConsulta.getMonth() + 1).padStart(2, '0')
  XLSX.writeFile(libro, `historial_requisiciones_${mesConsulta.getFullYear()}-${mes}.xlsx`)
}

function Dato({ etiqueta, valor }: { etiqueta: string; valor: string | number | null }) {
  return (
    <div>
      <dt>{etiqueta}</dt>
      <dd>{valor === null || valor === '' ? 'No registrado' : valor}</dd>
    </div>
  )
}

export default function HistorialRequisicionesMantenimiento() {
  const navigate = useNavigate()
  const usuario = authService.getUsuarioActual()
  const autorizado = puedeVerHistorialRequisiciones(usuario)
  const hoy = new Date()
  const inicioDelAno = new Date(hoy.getFullYear(), 0, 1)
  const mesActual = new Date(hoy.getFullYear(), hoy.getMonth(), 1)
  const [requisiciones, setRequisiciones] = useState<RequisicionHistorial[]>([])
  const [cargando, setCargando] = useState(true)
  const [error, setError] = useState('')
  const [errorPdf, setErrorPdf] = useState('')
  const [intentoCarga, setIntentoCarga] = useState(0)
  const [idPdfSolicitado, setIdPdfSolicitado] = useState<number | null>(null)
  const [mesConsulta, setMesConsulta] = useState(mesActual)

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
    consultarHistorialRequisiciones(mesConsulta.getMonth() + 1, mesConsulta.getFullYear())
      .then((resultado) => {
        if (vigente) setRequisiciones(resultado)
      })
      .catch((errorCarga: unknown) => {
        if (vigente) {
          setError(errorCarga instanceof Error
            ? errorCarga.message
            : 'No fue posible consultar el historial de requisiciones.')
        }
      })
      .finally(() => {
        if (vigente) setCargando(false)
      })

    return () => {
      vigente = false
    }
  }, [autorizado, intentoCarga, mesConsulta])

  async function descargarPdf(requisicion: RequisicionHistorial) {
    const ventana = window.open('', '_blank')
    if (!ventana) {
      setErrorPdf('Permite las ventanas emergentes para abrir el documento imprimible.')
      return
    }

    setErrorPdf('')
    setIdPdfSolicitado(requisicion.id_requisicion)
    try {
      ventana.location.href = await obtenerPdfRequisicion(requisicion.id_requisicion)
    } catch {
      ventana.close()
      setErrorPdf('No fue posible generar el PDF de la requisición.')
    } finally {
      setIdPdfSolicitado(null)
    }
  }

  const nombreMesConsulta = mesConsulta.toLocaleDateString('es-MX', {
    month: 'long',
    year: 'numeric',
  })

  if (!autorizado) return <Navigate to="/mantenimiento" replace />

  return (
    <main className="historial-reportes-page">
      <header className="historial-reportes-topbar">
        <button className="historial-reportes-brand" type="button" onClick={() => navigate('/mantenimiento')}>
          <span className="historial-reportes-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </button>
        <span>HISTORIAL DE REQUISICIONES</span>
      </header>

      <section className="historial-reportes-content">
        <button className="historial-reportes-back" type="button" onClick={() => navigate('/mantenimiento')}>
          ← Volver al módulo
        </button>

        <div className="historial-reportes-heading">
          <div>
            <span className="historial-reportes-eyebrow">CONSULTA · SIGSA</span>
            <h1>Historial de requisiciones</h1>
            <p>Consulta las requisiciones registradas por mes y descarga el documento de cada una.</p>
          </div>
          {!cargando && !error && (
            <div className="historial-reportes-actions">
              <div className="historial-reportes-month-navigation" aria-label="Navegación por mes">
                <button
                  type="button"
                  onClick={() => cambiarMes(-1)}
                  disabled={mesConsulta <= inicioDelAno}
                  aria-label="Consultar el mes anterior"
                >
                  ←
                </button>
                <span>{nombreMesConsulta}</span>
                <button
                  type="button"
                  onClick={() => cambiarMes(1)}
                  disabled={mesConsulta >= mesActual}
                  aria-label="Consultar el mes siguiente"
                >
                  →
                </button>
              </div>
              <span className="historial-reportes-count">
                <strong>{requisiciones.length}</strong>
                {requisiciones.length === 1 ? 'requisición' : 'requisiciones'}
              </span>
              <button
                className="historial-reportes-export"
                type="button"
                onClick={() => descargarExcel(requisiciones, mesConsulta)}
                disabled={requisiciones.length === 0}
              >
                Descargar Excel
              </button>
            </div>
          )}
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
        {errorPdf && (
          <div className="historial-reportes-error historial-requisiciones-pdf-error" role="alert">
            <span>{errorPdf}</span>
          </div>
        )}
        {cargando && <p className="historial-reportes-message" role="status">Cargando historial…</p>}
        {!cargando && !error && requisiciones.length === 0 && (
          <div className="historial-reportes-empty">
            <span aria-hidden="true">✓</span>
            <h2>No hay requisiciones en este mes</h2>
            <p>Las requisiciones registradas durante el mes aparecerán aquí.</p>
          </div>
        )}

        {!cargando && !error && requisiciones.length > 0 && (
          <section className="historial-reportes-list" aria-label="Requisiciones de mantenimiento">
            {requisiciones.map((requisicion) => (
              <article className="historial-reportes-card" key={requisicion.id_requisicion}>
                <header className="historial-reportes-card-header">
                  <div>
                    <span className="historial-reportes-card-kicker">
                      REQUISICIÓN #{requisicion.id_requisicion}
                      {' · '}REPORTE #{requisicion.id_reporte}
                    </span>
                    <h2>{mostrarOrigen(requisicion)}</h2>
                  </div>
                  <span className={`historial-reportes-status historial-reportes-status-${requisicion.estado.toLocaleLowerCase('es-MX').replace(/\s+/g, '-')}`}>
                    {mostrarEstado(requisicion.estado)}
                  </span>
                </header>

                <section className="historial-reportes-section">
                  <h3>Datos de la requisición</h3>
                  <dl className="historial-reportes-details">
                    <Dato etiqueta="Tipo de requisición" valor={requisicion.tipo_requisicion ? mostrarEstado(requisicion.tipo_requisicion) : null} />
                    <Dato etiqueta="Solicitó" valor={requisicion.solicitante} />
                    <Dato etiqueta="Realizó el trámite" valor={requisicion.elaborador} />
                    <Dato etiqueta="Fecha" valor={mostrarFecha(requisicion.fecha)} />
                  </dl>
                </section>

                <div className="historial-requisiciones-card-actions">
                  <button
                    className="historial-reportes-export"
                    type="button"
                    onClick={() => void descargarPdf(requisicion)}
                    disabled={idPdfSolicitado !== null}
                  >
                    {idPdfSolicitado === requisicion.id_requisicion ? 'Preparando PDF...' : 'Descargar PDF'}
                  </button>
                </div>
              </article>
            ))}
          </section>
        )}
      </section>
    </main>
  )
}

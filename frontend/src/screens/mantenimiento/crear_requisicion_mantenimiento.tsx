import { type FormEvent, useEffect, useState } from 'react'
import { Navigate, useNavigate, useParams } from 'react-router-dom'
import { authService } from '../../services/auth_services.js'
import {
  consultarOpcionesRequisicion,
  consultarReportesRequisicion,
  crearRequisicion,
  obtenerPdfRequisicion,
  puedeGestionarMantenimiento,
  type DetalleRequisicion,
  type OpcionesRequisicion,
  type ReporteRequisicion,
} from '../../services/mantenimiento_services'
import './styles/requisiciones_mantenimiento.css'

const AREA_SOLICITANTE = 'DIRECCIÓN DE SISTEMAS Y TECNOLOGÍAS'
const CUENTA_BASE = '1-99999'
const CUENTA_RESTO = '-25021-1-2110008020010-2501-134020506-5050001-61015-015'
const CUENTA_POR_DEFECTO = `${CUENTA_BASE}-51351001-5101${CUENTA_RESTO}`

function fechaActual() {
  return new Date().toISOString().slice(0, 10)
}

function nombreCompleto(persona: { nombre: string; apellido_p: string; apellido_m: string }) {
  return `${persona.nombre} ${persona.apellido_p} ${persona.apellido_m}`.trim()
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

function detalleVacio(): DetalleRequisicion {
  return { num_lote: '', cantidad: null, descripcion: '' }
}

export default function CrearRequisicionMantenimiento() {
  const navigate = useNavigate()
  const { id_reporte: idReporteParam } = useParams()
  const usuario = authService.getUsuarioActual()
  const autorizado = puedeGestionarMantenimiento(usuario)
  const idReporte = Number(idReporteParam)
  const parametroValido = Number.isInteger(idReporte) && idReporte > 0
  const [reporte, setReporte] = useState<ReporteRequisicion | null>(null)
  const [opciones, setOpciones] = useState<OpcionesRequisicion | null>(null)
  const [fecha, setFecha] = useState(fechaActual)
  const [fechaEntrega, setFechaEntrega] = useState('')
  const [justificacion, setJustificacion] = useState('')
  const [detalles, setDetalles] = useState<DetalleRequisicion[]>([detalleVacio()])
  const [tipoRequisicion, setTipoRequisicion] = useState<'SERVICIOS' | 'MATERIALES'>('SERVICIOS')
  const [tipoCodigo, setTipoCodigo] = useState<'CC_DIRECCION' | 'CC_CENTRAL'>('CC_DIRECCION')
  const [idCodigo, setIdCodigo] = useState<number | null>(null)
  const [ccCentral, setCcCentral] = useState('')
  const [cargando, setCargando] = useState(true)
  const [guardando, setGuardando] = useState(false)
  const [errorCarga, setErrorCarga] = useState('')
  const [error, setError] = useState('')
  const [idRequisicion, setIdRequisicion] = useState<number | null>(null)

  useEffect(() => {
    if (!autorizado || !parametroValido) return
    let vigente = true
    Promise.all([consultarReportesRequisicion(), consultarOpcionesRequisicion()])
      .then(([reportes, opcionesCargadas]) => {
        if (!vigente) return
        const resultado = reportes.find((item) => item.id_reporte === idReporte) ?? null
        if (!resultado) {
          setErrorCarga('El reporte no está disponible para requisición.')
        } else if (resultado.id_requisicion !== null) {
          setErrorCarga('Este reporte ya tiene una requisición registrada.')
        } else {
          setReporte(resultado)
          setOpciones(opcionesCargadas)
        }
      })
      .catch((errorCarga: unknown) => {
        if (vigente) setErrorCarga(errorCarga instanceof Error ? errorCarga.message : 'No fue posible cargar el formulario.')
      })
      .finally(() => {
        if (vigente) setCargando(false)
      })
    return () => {
      vigente = false
    }
  }, [autorizado, idReporte, parametroValido])

  if (!autorizado) return <Navigate to="/mantenimiento" replace />

  function actualizarDetalle(indice: number, campo: keyof DetalleRequisicion, valor: string) {
    setDetalles((actuales) => actuales.map((detalle, actual) => {
      if (actual !== indice) return detalle
      if (campo === 'cantidad') return { ...detalle, cantidad: valor === '' ? null : Number(valor) }
      return { ...detalle, [campo]: valor }
    }))
  }

  function agregarDetalle() {
    setDetalles((actuales) => [...actuales, detalleVacio()])
  }

  function eliminarDetalle(indice: number) {
    setDetalles((actuales) => actuales.length === 1 ? actuales : actuales.filter((_, actual) => actual !== indice))
  }

  async function enviarFormulario(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!reporte || !opciones || guardando) return
    if (tipoCodigo === 'CC_DIRECCION' && idCodigo === null) {
      setError('Selecciona un código contable de dirección.')
      return
    }
    if (tipoCodigo === 'CC_CENTRAL' && ccCentral.trim() === '') {
      setError('Captura el código contable central.')
      return
    }
    if (!detalles.some((detalle) => detalle.descripcion.trim() !== '')) {
      setError('Agrega al menos un renglón con descripción.')
      return
    }
    setError('')
    setGuardando(true)
    try {
      const resultado = await crearRequisicion({
        id_reporte: reporte.id_reporte,
        fecha,
        fecha_entrega: fechaEntrega || null,
        justificacion: justificacion.trim(),
        detalles: detalles.filter((detalle) => detalle.descripcion.trim() !== ''),
        tipo_requisicion: tipoRequisicion,
        tipo_codigo: tipoCodigo,
        id_codigo: tipoCodigo === 'CC_DIRECCION' ? idCodigo : null,
        cc_central: tipoCodigo === 'CC_CENTRAL' ? ccCentral.trim() : null,
      })
      setIdRequisicion(Number(resultado.id_requisicion))
    } catch (errorCreacion: unknown) {
      setError(errorCreacion instanceof Error ? errorCreacion.message : 'No fue posible crear la requisición.')
    } finally {
      setGuardando(false)
    }
  }

  const elabora = opciones?.elabora
  const codigoDireccion = opciones?.codigos_contables.find((codigo) => codigo.id_codigo === idCodigo)?.codigo_contable
  const codigoCuenta = tipoCodigo === 'CC_DIRECCION'
    ? codigoDireccion
    : tipoCodigo === 'CC_CENTRAL'
      ? ccCentral.trim()
      : null
  const cuentaCargo = codigoCuenta
    ? `${CUENTA_BASE}-${codigoCuenta}${CUENTA_RESTO}`
    : CUENTA_POR_DEFECTO

  return (
    <main className="requisiciones-mantenimiento-page">
      <header className="requisiciones-mantenimiento-topbar">
        <button className="requisiciones-mantenimiento-brand" type="button" onClick={() => navigate('/mantenimiento')}>
          <span className="requisiciones-mantenimiento-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </button>
        <span>GESTIÓN DE REQUISICIONES</span>
      </header>

      <section className="requisiciones-mantenimiento-content requisiciones-mantenimiento-form-page">
        <button className="requisiciones-mantenimiento-back" type="button" onClick={() => navigate('/mantenimiento/requisiciones')}>
          ← Volver a requisiciones
        </button>
        <div className="requisiciones-mantenimiento-heading">
          <div>
            <span className="requisiciones-mantenimiento-eyebrow">ADMINISTRACIÓN · SIGSA</span>
            <h1>Nueva requisición</h1>
            <p>Completa los datos oficiales de la solicitud vinculada al reporte #{idReporteParam}.</p>
          </div>
        </div>

        {!parametroValido && <p className="requisiciones-mantenimiento-error" role="alert">El número de reporte no es válido.</p>}
        {parametroValido && cargando && <p className="requisiciones-mantenimiento-message" role="status">Cargando datos del reporte…</p>}
        {!cargando && errorCarga && <p className="requisiciones-mantenimiento-error" role="alert">{errorCarga}</p>}
        {!cargando && !errorCarga && reporte && opciones && (
          <form className="requisiciones-mantenimiento-official-form" onSubmit={(event) => void enviarFormulario(event)}>
            <section className="requisiciones-mantenimiento-form-panel" aria-label="Tipo de requisición">
              <div className="requisiciones-mantenimiento-form-grid">
                <label htmlFor="requisicion-tipo">Tipo de requisición <span>*</span></label>
                <select
                  id="requisicion-tipo"
                  value={tipoRequisicion}
                  onChange={(event) => setTipoRequisicion(event.target.value as 'SERVICIOS' | 'MATERIALES')}
                  required
                >
                  <option value="SERVICIOS">Requisición de servicios</option>
                  <option value="MATERIALES">Requisición de materiales</option>
                </select>
              </div>
            </section>

            <section className="requisiciones-mantenimiento-official-header" aria-label="Datos institucionales">
              <div><span>Área solicitante</span><strong>{AREA_SOLICITANTE}</strong></div>
              <div className="requisiciones-mantenimiento-account-charge">
                <span>Cuenta(s) cargo</span>
                <div className="requisiciones-mantenimiento-form-grid">
                  <label htmlFor="requisicion-tipo-codigo">Tipo de código contable <span>*</span></label>
                  <select
                    id="requisicion-tipo-codigo"
                    value={tipoCodigo}
                    onChange={(event) => {
                      setTipoCodigo(event.target.value as 'CC_DIRECCION' | 'CC_CENTRAL')
                      setError('')
                    }}
                    required
                  >
                    <option value="CC_DIRECCION">Código contable dirección</option>
                    <option value="CC_CENTRAL">Código contable central</option>
                  </select>
                  {tipoCodigo === 'CC_DIRECCION' ? (
                    <>
                      <label htmlFor="requisicion-codigo-direccion">Código y descripción <span>*</span></label>
                      <select
                        id="requisicion-codigo-direccion"
                        value={idCodigo ?? ''}
                        onChange={(event) => setIdCodigo(event.target.value === '' ? null : Number(event.target.value))}
                        required
                      >
                        <option value="">Selecciona un código contable</option>
                        {opciones.codigos_contables.map((codigo) => (
                          <option key={codigo.id_codigo} value={codigo.id_codigo}>
                            {codigo.codigo_contable} — {codigo.descripcion ?? 'Sin descripción'}
                          </option>
                        ))}
                      </select>
                    </>
                  ) : (
                    <>
                      <label htmlFor="requisicion-codigo-central">Código contable central <span>*</span></label>
                      <input
                        id="requisicion-codigo-central"
                        value={ccCentral}
                        onChange={(event) => setCcCentral(event.target.value)}
                        maxLength={14}
                        placeholder="Ej. 51221001-22101"
                        required
                      />
                    </>
                  )}
                </div>
                <strong>{cuentaCargo}</strong>
              </div>
              <div><span>Financiamiento</span><strong>FONE</strong></div>
              <div><span>Programas federales</span><strong>N/A</strong></div>
              <div><span>Folio</span><strong>Se asigna en oficinas centrales</strong></div>
            </section>

            <section className="requisiciones-mantenimiento-form-panel" aria-labelledby="datos-tramite-title">
              <div className="requisiciones-mantenimiento-form-heading">
                <div>
                  <span className="requisiciones-mantenimiento-section-index">DATOS GENERALES · REPORTE #{reporte.id_reporte}</span>
                  <h2 id="datos-tramite-title">Datos generales del trámite</h2>
                  <p>{mostrarOrigen(reporte)} · Inicio: {mostrarFecha(reporte.fecha_inicio)}</p>
                </div>
              </div>
              <div className="requisiciones-mantenimiento-form-grid">
                <label htmlFor="requisicion-fecha">Fecha <span>*</span></label>
                <input id="requisicion-fecha" type="date" value={fecha} onChange={(event) => setFecha(event.target.value)} required />
                <label htmlFor="requisicion-fecha-entrega">Fecha de entrega</label>
                <input id="requisicion-fecha-entrega" type="date" value={fechaEntrega} onChange={(event) => setFechaEntrega(event.target.value)} />
              </div>
            </section>

            <section className="requisiciones-mantenimiento-form-panel" aria-labelledby="partidas-title">
              <div className="requisiciones-mantenimiento-form-heading">
                <div><span className="requisiciones-mantenimiento-section-index">REQUISICIÓN · PARTIDAS</span><h2 id="partidas-title">Partidas y conceptos</h2></div>
                <button className="requisiciones-mantenimiento-add-row" type="button" onClick={agregarDetalle}>+ Agregar renglón</button>
              </div>
              <div className="requisiciones-mantenimiento-details-table">
                <div className="requisiciones-mantenimiento-details-header"><span>Núm. lote</span><span>Cantidad</span><span>Descripción del servicio y/o especificaciones</span><span aria-hidden="true" /></div>
                {detalles.map((detalle, indice) => (
                  <div className="requisiciones-mantenimiento-detail-row" key={indice}>
                    <input aria-label={`Núm. lote del renglón ${indice + 1}`} value={detalle.num_lote} onChange={(event) => actualizarDetalle(indice, 'num_lote', event.target.value)} />
                    <input aria-label={`Cantidad del renglón ${indice + 1}`} type="number" min="0" step="1" value={detalle.cantidad ?? ''} onChange={(event) => actualizarDetalle(indice, 'cantidad', event.target.value)} />
                    <textarea aria-label={`Descripción del renglón ${indice + 1}`} value={detalle.descripcion} onChange={(event) => actualizarDetalle(indice, 'descripcion', event.target.value)} required rows={2} />
                    <button className="requisiciones-mantenimiento-remove-row" type="button" onClick={() => eliminarDetalle(indice)} disabled={detalles.length === 1} aria-label={`Eliminar renglón ${indice + 1}`}>×</button>
                  </div>
                ))}
              </div>
              <small className="requisiciones-mantenimiento-hint">Núm. lote y cantidad son opcionales; pueden quedar vacíos para subtítulos o encabezados.</small>
            </section>

            <section className="requisiciones-mantenimiento-form-panel" aria-labelledby="firmas-title">
              <div className="requisiciones-mantenimiento-form-heading"><div><span className="requisiciones-mantenimiento-section-index">JUSTIFICACIÓN Y FIRMAS</span><h2 id="firmas-title">Responsables del trámite</h2></div></div>
              <label htmlFor="requisicion-justificacion">Justificación técnica <span>*</span></label>
              <textarea id="requisicion-justificacion" value={justificacion} onChange={(event) => setJustificacion(event.target.value)} rows={4} required />
              <div className="requisiciones-mantenimiento-form-grid">
                <label htmlFor="requisicion-solicita">Solicita <span>*</span></label>
                <div id="requisicion-solicita" className="requisiciones-mantenimiento-readonly-field">
                  {reporte.solicitante ?? 'No hay autorizador de incidencia registrado'}
                </div>
                <label htmlFor="requisicion-autoriza">Autoriza <span>*</span></label>
                <div id="requisicion-autoriza" className="requisiciones-mantenimiento-readonly-field">
                  {opciones.director ? nombreCompleto(opciones.director) : 'No hay director activo configurado'}
                </div>
              </div>
              <div className="requisiciones-mantenimiento-elabora">
                <span>Elabora</span>
                <strong>{elabora ? nombreCompleto(elabora) : 'No disponible'}</strong>
                <small>{elabora?.correo} · Extensión Cisco: {elabora?.telefono ?? 'No registrada'}</small>
              </div>
              {error && <p className="requisiciones-mantenimiento-error" role="alert">{error}</p>}
              {idRequisicion ? (
                <div className="requisiciones-mantenimiento-success" role="status">
                  <p>La requisición #{idRequisicion} fue registrada correctamente.</p>
                  <button className="requisiciones-mantenimiento-pdf-button" type="button" onClick={() => {
                    const ventana = window.open('', '_blank'); void obtenerPdfRequisicion(idRequisicion).then((url) => { if (ventana) ventana.location.href = url }).catch(() => { ventana?.close(); setError('No fue posible generar el documento oficial.') })
                  }}>Imprimir / Descargar PDF Oficial</button>
                </div>
              ) : (
                <div className="requisiciones-mantenimiento-form-footer">
                  <button type="button" className="requisiciones-mantenimiento-cancel" onClick={() =>
                    navigate('/mantenimiento/requisiciones')}>Cancelar</button>
                  <button type="submit" disabled={guardando}>{guardando ? 'Guardando…' : 'Enviar requisición'}</button>
                </div>
              )}
            </section>
          </form>
        )}
      </section>
    </main>
  )
}

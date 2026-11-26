import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { Navigate, useNavigate, useParams } from 'react-router-dom'
import { authService } from '../../services/auth_services.js'
import {
  crearSolicitudViaticos,
  consultarComisionadoSolicitudViaticos,
  obtenerPdfSolicitudViaticos,
} from '../../services/comisiones_services'
import type {
  ComisionadoSolicitudViaticos,
  ResultadoSolicitudViaticos,
} from '../../services/comisiones_services'
import './styles/comisiones_operacion.css'

type DatosEmergencia = {
  puesto: string
  contacto_emergencia_nombre: string
  contacto_emergencia_numero: string
}

type DatosUnidadAdministrativa = {
  correo: string
  cisco: string
  programa: string
  rubro: string
  codigo_contable: string
}

type RenglonViaticos = {
  id: number
  id_codigo_contable_comision: string
  num_dias: string
  importe: string
  importeManual: boolean
}

const datosEmergenciaIniciales: DatosEmergencia = {
  puesto: '',
  contacto_emergencia_nombre: '',
  contacto_emergencia_numero: '',
}

const datosUnidadIniciales: DatosUnidadAdministrativa = {
  correo: '',
  cisco: '',
  programa: '',
  rubro: '',
  codigo_contable: '',
}

function formatearDias(valor: string): string {
  const dias = Number(valor)
  if (!Number.isFinite(dias) || dias < 0.5 || !Number.isInteger(dias * 2)) return ''

  const enteros = Math.floor(dias)
  const fraccion = Math.round((dias - enteros) * 2) / 2
  const parteFraccion = fraccion === 0.5 ? '1/2' : ''
  if (enteros === 0) return parteFraccion
  return parteFraccion ? `${enteros} ${parteFraccion}` : String(enteros)
}

export default function CrearSolicitudViaticos() {
  const navigate = useNavigate()
  const { id_comision: idComisionParam, id_personal: idPersonalParam } = useParams()
  const usuario = authService.getUsuarioActual()
  const puedeConsultar = [1, 2].includes(Number(usuario?.puesto))
    || Number(usuario?.area) === 6
  const idComision = Number(idComisionParam)
  const idPersonal = Number(idPersonalParam)
  const idsValidos = Number.isInteger(idComision) && idComision > 0
    && Number.isInteger(idPersonal) && idPersonal > 0
  const [comisionado, setComisionado] = useState<ComisionadoSolicitudViaticos | null>(null)
  const [datosEmergencia, setDatosEmergencia] = useState(datosEmergenciaIniciales)
  const [datosUnidad, setDatosUnidad] = useState(datosUnidadIniciales)
  const [observaciones, setObservaciones] = useState('')
  const [zona, setZona] = useState('')
  const [tipoComprobacion, setTipoComprobacion] = useState<'FACTURA' | 'CONSTANCIA' | ''>('')
  const [renglonesViaticos, setRenglonesViaticos] = useState<RenglonViaticos[]>([
    { id: 1, id_codigo_contable_comision: '', num_dias: '', importe: '', importeManual: false },
  ])
  const [siguienteRenglonId, setSiguienteRenglonId] = useState(2)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [guardarError, setGuardarError] = useState<string | null>(null)
  const [guardando, setGuardando] = useState(false)
  const [resultado, setResultado] = useState<ResultadoSolicitudViaticos | null>(null)
  const [generandoPdf, setGenerandoPdf] = useState(false)
  const [errorPdf, setErrorPdf] = useState<string | null>(null)

  useEffect(() => {
    if (!puedeConsultar || !idsValidos) return

    let active = true
    consultarComisionadoSolicitudViaticos(idComision, idPersonal)
      .then((resultado) => {
        if (active) {
          setComisionado(resultado)
          setDatosUnidad({
            correo: resultado.registrante.correo,
            cisco: resultado.registrante.cisco,
            programa: '',
            rubro: '',
            codigo_contable: '',
          })
        }
      })
      .catch((loadError: unknown) => {
        if (active) {
          setError(loadError instanceof Error
            ? loadError.message
            : 'No fue posible cargar los datos del comisionado.')
        }
      })
      .finally(() => {
        if (active) setLoading(false)
      })

    return () => {
      active = false
    }
  }, [idComision, idPersonal, idsValidos, puedeConsultar])

  function actualizarDato(campo: keyof DatosEmergencia, valor: string) {
    setDatosEmergencia((actuales) => ({ ...actuales, [campo]: valor }))
  }

  function actualizarDatoUnidad(campo: keyof DatosUnidadAdministrativa, valor: string) {
    setDatosUnidad((actuales) => ({ ...actuales, [campo]: valor }))
  }

  function actualizarRenglon(id: number, campo: keyof Omit<RenglonViaticos, 'id'>, valor: string) {
    setRenglonesViaticos((actuales) => actuales.map((renglon) => (
      renglon.id === id
        ? {
          ...renglon,
          [campo]: valor,
          ...(campo === 'importe'
            ? { importeManual: true }
            : campo === 'num_dias'
              ? { importeManual: false }
              : {}),
        }
        : renglon
    )))
  }

  function agregarRenglon() {
    setRenglonesViaticos((actuales) => [
      ...actuales,
      {
        id: siguienteRenglonId,
        id_codigo_contable_comision: '',
        num_dias: '',
        importe: '',
        importeManual: false,
      },
    ])
    setSiguienteRenglonId((id) => id + 1)
  }

  function eliminarRenglon(id: number) {
    setRenglonesViaticos((actuales) => (
      actuales.length > 1 ? actuales.filter((renglon) => renglon.id !== id) : actuales
    ))
  }

  function obtenerCuotaDiaria(numDias: string): number | null {
    const dias = Number(numDias)
    if (!Number.isFinite(dias) || dias < 0.5 || !Number.isInteger(dias * 2)) return null
    const pernocta = dias >= 1 ? 1 : 0
    return obtenerTarifaDiaria(pernocta)
  }

  function obtenerTarifaDiaria(pernocta: 0 | 1): number | null {
    if (
      !comisionado
      || !zona
      || !tipoComprobacion
    ) return null
    const tarifa = comisionado.opciones.tabulador.find((opcion) => (
      opcion.id_zona === Number(zona)
      && opcion.tipo_comprobacion === tipoComprobacion
      && Number(opcion.pernocta) === pernocta
    ))
    return tarifa ? Number(tarifa.monto_diario) : null
  }

  function calcularImporte(numDias: string): string {
    const dias = Number(numDias)
    if (!Number.isFinite(dias) || dias < 0.5 || !Number.isInteger(dias * 2)) return ''

    const diasConPernocta = Math.floor(dias)
    const diasSinPernocta = dias % 1 === 0.5 ? 1 : 0
    const cuotaConPernocta = diasConPernocta > 0
      ? obtenerTarifaDiaria(1)
      : 0
    const cuotaSinPernocta = diasSinPernocta > 0
      ? obtenerTarifaDiaria(0)
      : 0

    if (
      cuotaConPernocta === null
      || cuotaSinPernocta === null
    ) return ''

    const importe = diasConPernocta * cuotaConPernocta
      + diasSinPernocta * cuotaSinPernocta
    return String(Math.round(importe))
  }

  function obtenerTotalImportes(): string {
    const totalEnCentavos = renglonesViaticos.reduce((total, renglon) => {
      const importe = renglon.importeManual
        ? renglon.importe
        : calcularImporte(renglon.num_dias)
      const valor = importe === '' ? 0 : Number(importe)
      return total + (Number.isFinite(valor) ? Math.round(valor * 100) : 0)
    }, 0)
    return (totalEnCentavos / 100).toLocaleString('es-MX', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    })
  }

  function hayTarifaConstancia(idZona: number): boolean {
    return Boolean(
      comisionado?.opciones.zonas.find((opcion) => opcion.id_zona === idZona)?.permite_constancia
      && comisionado.opciones.tabulador.some((tarifa) => (
        tarifa.id_zona === idZona && tarifa.tipo_comprobacion === 'CONSTANCIA'
      )),
    )
  }

  function reiniciarImportesManuales() {
    setRenglonesViaticos((actuales) => actuales.map((renglon) => ({
      ...renglon,
      importeManual: false,
    })))
  }

  async function guardarSolicitud(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!comisionado || !zona || !tipoComprobacion) return

    setGuardarError(null)
    setGuardando(true)
    try {
      const resultadoGuardado = await crearSolicitudViaticos({
        id_comision: idComision,
        id_personal_comisionado: idPersonal,
        id_zona: Number(zona),
        tipo_comprobacion: tipoComprobacion,
        puesto: datosEmergencia.puesto.trim(),
        contacto_emergencia_nombre: datosEmergencia.contacto_emergencia_nombre.trim(),
        contacto_emergencia_numero: datosEmergencia.contacto_emergencia_numero.trim(),
        programa: datosUnidad.programa.trim(),
        clave_cobro: datosUnidad.codigo_contable.trim(),
        observaciones: observaciones.trim(),
        renglones: renglonesViaticos.map((renglon) => ({
          id_codigo_contable_comision: Number(renglon.id_codigo_contable_comision),
          num_dias: renglon.num_dias === '' ? null : Number(renglon.num_dias),
          importe: Number(renglon.importeManual
            ? renglon.importe
            : calcularImporte(renglon.num_dias)),
        })),
      })
      setResultado(resultadoGuardado)
    } catch (saveError) {
      setGuardarError(saveError instanceof Error
        ? saveError.message
        : 'No fue posible guardar la solicitud de viáticos.')
    } finally {
      setGuardando(false)
    }
  }

  async function guardarPdf() {
    if (!resultado || generandoPdf) return

    const ventana = window.open('', '_blank')
    if (!ventana) {
      setErrorPdf('Permite las ventanas emergentes para abrir el documento imprimible.')
      return
    }

    setErrorPdf(null)
    setGenerandoPdf(true)
    try {
      const url = await obtenerPdfSolicitudViaticos(resultado.id_solicitud_viaticos)
      ventana.location.href = url
    } catch (pdfError) {
      ventana.close()
      setErrorPdf(pdfError instanceof Error
        ? pdfError.message
        : 'No fue posible generar el PDF de la solicitud de viáticos.')
    } finally {
      setGenerandoPdf(false)
    }
  }

  if (!puedeConsultar) {
    return <Navigate to="/comisiones" replace />
  }

  if (!idsValidos) {
    return <Navigate to="/comisiones/solicitudes-viaticos" replace />
  }

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
        <button
          className="comisiones-operacion-back"
          type="button"
          onClick={() => navigate('/comisiones/solicitudes-viaticos')}
        >
          ← Volver a solicitudes de viáticos
        </button>
        <div className="comisiones-operacion-heading">
          <span className="comisiones-operacion-eyebrow">COMISIONES · SIGSA</span>
          <h1>Solicitud de viáticos</h1>
          <p>Completa los datos para la solicitud correspondiente a la comisión #{idComision}.</p>
        </div>

        {loading && <p className="comisiones-operacion-state" role="status">Cargando datos del comisionado...</p>}
        {!loading && error && <p className="comisiones-operacion-state error" role="alert">{error}</p>}
        {resultado && (
          <>
            <div className="comisiones-operacion-state success" role="status">
              <span>
                {resultado.mensaje}
                {resultado.personal_pendiente > 0
                  && ` Faltan solicitudes de ${resultado.personal_pendiente} persona(s).`}
              </span>
              <div className="viaticos-solicitud-success-actions">
                <button type="button" onClick={() => void guardarPdf()} disabled={generandoPdf}>
                  {generandoPdf ? 'Generando...' : 'Guardar PDF'}
                </button>
                <button type="button" onClick={() => navigate('/comisiones/solicitudes-viaticos')}>
                  Volver al listado
                </button>
              </div>
            </div>
            {errorPdf && <p className="comisiones-operacion-state error" role="alert">{errorPdf}</p>}
          </>
        )}

        {!loading && !error && !resultado && comisionado && (
          <form className="comisiones-operacion-form" onSubmit={guardarSolicitud}>
            <section aria-labelledby="viaticos-comisionado-heading">
              <h2 id="viaticos-comisionado-heading">Comisionado:</h2>
              <div className="comisiones-orden-grid">
                <label>
                  Nombre completo
                  <input value={comisionado.nombre_completo} readOnly />
                </label>
                <label>
                  CURP
                  <input value={comisionado.curp} readOnly />
                </label>
                <label>
                  RFC
                  <input value={comisionado.rfc} readOnly />
                </label>
                <label>
                  Correo electrónico
                  <input type="email" value={comisionado.correo} readOnly />
                </label>
                <label>
                  Teléfono celular
                  <input type="tel" value={comisionado.telefono} readOnly />
                </label>
                <label>
                  Teléfono Cisco
                  <input type="tel" value={comisionado.cisco} readOnly />
                </label>
              </div>
            </section>

            <section aria-labelledby="viaticos-emergencia-heading">
              <h2 id="viaticos-emergencia-heading">Contacto de emergencia</h2>
              <div className="comisiones-orden-grid">
                <label>
                  Puesto
                  <input
                    value={datosEmergencia.puesto}
                    maxLength={50}
                    required
                    onChange={(event) => actualizarDato('puesto', event.target.value)}
                  />
                </label>
                <label>
                  Nombre del contacto de emergencia
                  <input
                    value={datosEmergencia.contacto_emergencia_nombre}
                    maxLength={150}
                    onChange={(event) => actualizarDato('contacto_emergencia_nombre', event.target.value)}
                  />
                </label>
                <label>
                  Número del contacto de emergencia
                  <input
                    type="tel"
                    value={datosEmergencia.contacto_emergencia_numero}
                    maxLength={10}
                    onChange={(event) => actualizarDato('contacto_emergencia_numero', event.target.value)}
                  />
                </label>
              </div>
            </section>

            <section aria-labelledby="viaticos-unidad-heading">
              <h2 id="viaticos-unidad-heading">Unidad administrativa</h2>
              <div className="comisiones-orden-grid">
                <label>
                  Nombre de quien registra
                  <input value={comisionado.registrante.nombre} readOnly />
                </label>
                <label>
                  Correo electrónico
                  <input
                    type="email"
                    value={datosUnidad.correo}
                    onChange={(event) => actualizarDatoUnidad('correo', event.target.value)}
                  />
                </label>
                <label>
                  Teléfono Cisco
                  <input
                    type="tel"
                    value={datosUnidad.cisco}
                    onChange={(event) => actualizarDatoUnidad('cisco', event.target.value)}
                  />
                </label>
                <label>
                  Área
                  <input value="Dirección de Sistemas y Tecnologías" readOnly />
                </label>
                <label>
                  Financiamiento
                  <input value="25021 FONE" readOnly />
                </label>
                <label>
                  Programa
                  <input
                    value={datosUnidad.programa}
                    maxLength={10}
                    required
                    onChange={(event) => actualizarDatoUnidad('programa', event.target.value)}
                  />
                </label>
                <label>
                  Rubro
                  <input
                    value={datosUnidad.rubro}
                    onChange={(event) => actualizarDatoUnidad('rubro', event.target.value)}
                  />
                </label>
                <label>
                  Código contable
                  <input
                    value={datosUnidad.codigo_contable}
                    maxLength={15}
                    required
                    onChange={(event) => actualizarDatoUnidad('codigo_contable', event.target.value)}
                  />
                </label>
              </div>
              <p className="comisiones-operacion-state">
                El perfil del registrante no se modifica al guardar. El correo, Cisco y rubro escritos aquí no se guardan en la solicitud porque el esquema no tiene esos campos; el COG guardado es el que se selecciona en cada renglón.
              </p>
            </section>

            <section aria-labelledby="viaticos-comision-heading">
              <h2 id="viaticos-comision-heading">Comisión</h2>
              <div className="comisiones-orden-grid">
                <label>
                  Periodo
                  <input value={comisionado.periodo ?? 'Por definir'} readOnly />
                </label>
                <label>
                  Lugar
                  <input value={comisionado.lugar} readOnly />
                </label>
                <label>
                  Motivo
                  <textarea value={comisionado.motivo} readOnly rows={4} />
                </label>
              </div>
            </section>

            <section aria-labelledby="viaticos-partidas-heading">
              <div className="viaticos-partidas-heading">
                <h2 id="viaticos-partidas-heading">Partida, cuota e importe</h2>
                <button
                  className="viaticos-partidas-add"
                  type="button"
                  onClick={agregarRenglon}
                  aria-label="Agregar renglón"
                  title="Agregar renglón"
                >
                  +
                </button>
              </div>
              <div className="comisiones-orden-grid viaticos-partidas-options">
                <label>
                  Zona
                  <select
                    value={zona}
                    onChange={(event) => {
                      const nuevaZona = event.target.value
                      setZona(nuevaZona)
                      if (tipoComprobacion === 'CONSTANCIA' && !hayTarifaConstancia(Number(nuevaZona))) {
                        setTipoComprobacion('')
                      }
                      reiniciarImportesManuales()
                    }}
                    required
                  >
                    <option value="" disabled>Selecciona una zona</option>
                    {comisionado.opciones.zonas.map((opcion) => (
                      <option key={opcion.id_zona} value={opcion.id_zona}>{opcion.nombre}</option>
                    ))}
                  </select>
                </label>
                <label>
                  Tipo de comprobación
                  <select
                    value={tipoComprobacion}
                    onChange={(event) => {
                      const valor = event.target.value
                      if (valor === '' || valor === 'FACTURA' || valor === 'CONSTANCIA') {
                        setTipoComprobacion(valor)
                        reiniciarImportesManuales()
                      }
                    }}
                    required
                  >
                    <option value="" disabled>Selecciona comprobación</option>
                    <option value="FACTURA">Factura</option>
                    <option value="CONSTANCIA" disabled={!zona || !hayTarifaConstancia(Number(zona))}>
                      Constancia
                    </option>
                  </select>
                </label>
              </div>
              <div className="viaticos-partidas-table-wrapper">
                <table className="viaticos-partidas-table">
                  <thead>
                    <tr>
                      <th scope="col" aria-label="Acciones" />
                      <th scope="col">Partida / COG / concepto</th>
                      <th scope="col">Núm. días</th>
                      <th scope="col">Cuota diaria</th>
                      <th scope="col">Importe</th>
                    </tr>
                  </thead>
                  <tbody>
                    {renglonesViaticos.map((renglon, indice) => {
                      const cuotaDiaria = obtenerCuotaDiaria(renglon.num_dias)
                      const importeCalculado = calcularImporte(renglon.num_dias)
                      return (
                        <tr key={renglon.id}>
                          <td>
                            <button
                              className="viaticos-row-remove"
                              type="button"
                              onClick={() => eliminarRenglon(renglon.id)}
                              disabled={renglonesViaticos.length === 1}
                              aria-label={`Eliminar renglón ${indice + 1}`}
                              title="Eliminar renglón"
                            >
                              ×
                            </button>
                          </td>
                          <td>
                            <select
                              aria-label={`Partida, COG o concepto del renglón ${indice + 1}`}
                              value={renglon.id_codigo_contable_comision}
                              onChange={(event) => actualizarRenglon(
                                renglon.id,
                                'id_codigo_contable_comision',
                                event.target.value,
                              )}
                              required
                            >
                              <option value="" disabled>Selecciona partida</option>
                              {comisionado.opciones.codigos_contables.map((codigo) => (
                                <option
                                  key={codigo.id_codigo_contable_comision}
                                  value={codigo.id_codigo_contable_comision}
                                >
                                  {codigo.codigo_contable} · {codigo.descripcion}
                                </option>
                              ))}
                            </select>
                          </td>
                          <td>
                            <label className="viaticos-dias-field">
                              <input
                                aria-label={`Número de días del renglón ${indice + 1}`}
                                type="number"
                                min="0.5"
                                step="0.5"
                                value={renglon.num_dias}
                                onChange={(event) => actualizarRenglon(
                                  renglon.id,
                                  'num_dias',
                                  event.target.value,
                                )}
                              />
                              <output aria-live="polite">
                                {renglon.num_dias === ''
                                  ? ''
                                  : formatearDias(renglon.num_dias)
                                  ? `${formatearDias(renglon.num_dias)} ${Number(renglon.num_dias) === 0.5 ? 'día' : 'días'}`
                                  : 'Usa incrementos de 1/2'}
                              </output>
                            </label>
                          </td>
                          <td>
                            <output
                              className="viaticos-quota-value"
                              aria-label={`Cuota diaria aplicada del renglón ${indice + 1}`}
                            >
                              {cuotaDiaria === null ? '—' : `$${cuotaDiaria.toFixed(2)}`}
                            </output>
                          </td>
                          <td>
                            <input
                              aria-label={`Importe del renglón ${indice + 1}`}
                              type="number"
                              min="0"
                              max="9999999999"
                              step="1"
                              value={renglon.importeManual ? renglon.importe : importeCalculado}
                              required
                              onChange={(event) => actualizarRenglon(
                                renglon.id,
                                'importe',
                                event.target.value,
                              )}
                            />
                          </td>
                        </tr>
                      )
                    })}
                  </tbody>
                  <tfoot>
                    <tr>
                      <th scope="row" colSpan={4}>Total</th>
                      <td>
                        <output aria-label="Importe total">${obtenerTotalImportes()}</output>
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>
              {tipoComprobacion === 'CONSTANCIA'
                && !comisionado.opciones.zonas
                  .find((opcion) => opcion.id_zona === Number(zona))?.permite_constancia && (
                <p className="comisiones-operacion-state error" role="alert">
                  La zona seleccionada no permite comprobación con constancia.
                </p>
              )}
              {zona && tipoComprobacion && renglonesViaticos.some(
                (renglon) => renglon.num_dias !== ''
                  && formatearDias(renglon.num_dias) !== ''
                  && calcularImporte(renglon.num_dias) === '',
              ) && (
                <p className="comisiones-operacion-state error" role="alert">
                  No se encontró una cuota en el tabulador para la zona, comprobación y número de días seleccionados.
                </p>
              )}
            </section>

            <section aria-labelledby="viaticos-observaciones-heading">
              <h2 id="viaticos-observaciones-heading">Observaciones</h2>
              <label className="viaticos-observaciones-field">
                Observación
                <textarea
                  value={observaciones}
                  rows={5}
                  onChange={(event) => setObservaciones(event.target.value)}
                />
              </label>
            </section>
            {guardarError && (
              <p className="comisiones-operacion-state error" role="alert">{guardarError}</p>
            )}
            <div className="comisiones-operacion-actions">
              <span>
                El importe se guarda en pesos enteros conforme al esquema de la base de datos.
              </span>
              <button type="submit" disabled={guardando}>
                {guardando ? 'Guardando...' : 'Guardar solicitud de viáticos'}
              </button>
            </div>
          </form>
        )}
      </section>
    </main>
  )
}

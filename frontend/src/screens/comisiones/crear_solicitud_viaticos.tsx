import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { Navigate, useNavigate, useParams } from 'react-router-dom'
import { authService } from '../../services/auth_services.js'
import {
  consultarComisionadoSolicitudViaticos,
} from '../../services/comisiones_services'
import type { ComisionadoSolicitudViaticos } from '../../services/comisiones_services'
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
  const [zona, setZona] = useState('')
  const [tipoComprobacion, setTipoComprobacion] = useState<'FACTURA' | 'CONSTANCIA' | ''>('')
  const [renglonesViaticos, setRenglonesViaticos] = useState<RenglonViaticos[]>([
    { id: 1, id_codigo_contable_comision: '', num_dias: '0.5', importe: '' },
  ])
  const [siguienteRenglonId, setSiguienteRenglonId] = useState(2)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

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
      renglon.id === id ? { ...renglon, [campo]: valor } : renglon
    )))
  }

  function agregarRenglon() {
    setRenglonesViaticos((actuales) => [
      ...actuales,
      {
        id: siguienteRenglonId,
        id_codigo_contable_comision: '',
        num_dias: '0.5',
        importe: '',
      },
    ])
    setSiguienteRenglonId((id) => id + 1)
  }

  function obtenerCuotaDiaria(numDias: string): number | null {
    const dias = Number(numDias)
    if (
      !comisionado
      || !zona
      || !tipoComprobacion
      || !Number.isFinite(dias)
      || dias < 0.5
      || !Number.isInteger(dias * 2)
    ) return null
    const pernocta = dias >= 1 ? 1 : 0
    const tarifa = comisionado.opciones.tabulador.find((opcion) => (
      opcion.id_zona === Number(zona)
      && opcion.tipo_comprobacion === tipoComprobacion
      && Number(opcion.pernocta) === pernocta
    ))
    return tarifa ? Number(tarifa.monto_diario) : null
  }

  function evitarEnvio(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
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

        {!loading && !error && comisionado && (
          <form className="comisiones-operacion-form" onSubmit={evitarEnvio}>
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
                    onChange={(event) => actualizarDatoUnidad('codigo_contable', event.target.value)}
                  />
                </label>
              </div>
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
                      const zonaSeleccionada = comisionado.opciones.zonas.find(
                        (opcion) => opcion.id_zona === Number(nuevaZona),
                      )
                      if (tipoComprobacion === 'CONSTANCIA' && !Number(zonaSeleccionada?.permite_constancia)) {
                        setTipoComprobacion('')
                      }
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
                      }
                    }}
                    required
                  >
                    <option value="" disabled>Selecciona comprobación</option>
                    <option value="FACTURA">Factura</option>
                    <option value="CONSTANCIA" disabled={!comisionado.opciones.zonas
                      .find((opcion) => opcion.id_zona === Number(zona))?.permite_constancia}>
                      Constancia
                    </option>
                  </select>
                </label>
              </div>
              <div className="viaticos-partidas-table-wrapper">
                <table className="viaticos-partidas-table">
                  <thead>
                    <tr>
                      <th scope="col">Partida / COG / concepto</th>
                      <th scope="col">Núm. días</th>
                      <th scope="col">Cuota diaria</th>
                      <th scope="col">Importe</th>
                    </tr>
                  </thead>
                  <tbody>
                    {renglonesViaticos.map((renglon, indice) => {
                      const cuotaDiaria = obtenerCuotaDiaria(renglon.num_dias)
                      return (
                        <tr key={renglon.id}>
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
                                required
                              />
                              <output aria-live="polite">
                                {formatearDias(renglon.num_dias)
                                  ? `${formatearDias(renglon.num_dias)} ${Number(renglon.num_dias) === 0.5 ? 'día' : 'días'}`
                                  : 'Indica días en incrementos de 1/2'}
                              </output>
                            </label>
                          </td>
                          <td>
                            <output aria-label={`Cuota diaria del renglón ${indice + 1}`}>
                              {cuotaDiaria === null ? '—' : `$${cuotaDiaria.toFixed(2)}`}
                            </output>
                          </td>
                          <td>
                            <input
                              aria-label={`Importe del renglón ${indice + 1}`}
                              type="number"
                              min="0"
                              step="1"
                              value={renglon.importe}
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
                (renglon) => obtenerCuotaDiaria(renglon.num_dias) === null,
              ) && (
                <p className="comisiones-operacion-state error" role="alert">
                  No se encontró una cuota en el tabulador para la zona, comprobación y número de días seleccionados.
                </p>
              )}
            </section>
          </form>
        )}
      </section>
    </main>
  )
}

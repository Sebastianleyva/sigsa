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
          </form>
        )}
      </section>
    </main>
  )
}

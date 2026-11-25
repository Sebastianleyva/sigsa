import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import ConfirmationDialog from '../../components/ConfirmationDialog'
import {
  asignarComisionados,
  consultarOpcionesAsignacionComision,
} from '../../services/comisiones_services'
import type { OpcionesAsignacionComision } from '../../services/comisiones_services'
import './styles/comisiones_operacion.css'

export default function AsignarComision() {
  const navigate = useNavigate()
  const { id_comision: idComisionParam } = useParams()
  const idComision = Number(idComisionParam)
  const [opciones, setOpciones] = useState<OpcionesAsignacionComision | null>(null)
  const [seleccionados, setSeleccionados] = useState<number[]>([])
  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [resultadoAsignacion, setResultadoAsignacion] = useState<Awaited<ReturnType<typeof asignarComisionados>> | null>(null)
  const [mostrarAvisoSolicitudes, setMostrarAvisoSolicitudes] = useState(false)

  useEffect(() => {
    let active = true
    const consulta = Number.isInteger(idComision) && idComision > 0
      ? consultarOpcionesAsignacionComision(idComision)
      : Promise.reject(new Error('El identificador de la comisión no es válido.'))
    consulta
      .then((resultado) => {
        if (active) setOpciones(resultado)
      })
      .catch((loadError: unknown) => {
        if (active) {
          setError(loadError instanceof Error
            ? loadError.message
            : 'No fue posible cargar el personal del área.')
        }
      })
      .finally(() => {
        if (active) setLoading(false)
      })

    return () => {
      active = false
    }
  }, [idComision])

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (seleccionados.length === 0) {
      setError('Selecciona al menos una persona para la comisión.')
      return
    }

    setError(null)
    setSubmitting(true)
    try {
      const resultado = await asignarComisionados(idComision, seleccionados)
      setResultadoAsignacion(resultado)
      if (resultado.estado === 'PENDIENTE DE SOLICITUDES_V') {
        setMostrarAvisoSolicitudes(true)
      }
    } catch (submitError) {
      setError(submitError instanceof Error
        ? submitError.message
        : 'No fue posible asignar al personal e iniciar la comisión.')
    } finally {
      setSubmitting(false)
    }
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
        <button className="comisiones-operacion-back" type="button" onClick={() => navigate('/comisiones/pendientes')}>
          ← Volver a pendientes
        </button>
        <div className="comisiones-operacion-heading">
          <span className="comisiones-operacion-eyebrow">COMISIONES · SIGSA</span>
          <h1>Asignar comisionados</h1>
          <p>
            {opciones?.comision.tipo_comision === 'FORANEA'
              ? 'Selecciona una o más personas del área para asignarlas a esta comisión.'
              : 'Selecciona una o más personas del área para iniciar esta comisión.'}
          </p>
        </div>

        {loading && <p className="comisiones-operacion-state" role="status">Cargando comisión y personal...</p>}
        {!loading && !opciones && error && (
          <p className="comisiones-operacion-state error" role="alert">{error}</p>
        )}
        {resultadoAsignacion?.estado === 'EN CURSO' && (
          <div className="comisiones-operacion-state success" role="status">
            Comisión iniciada el {resultadoAsignacion.fecha_inicio}. Su estado cambió a EN CURSO.
            <button type="button" onClick={() => navigate('/comisiones/en-curso')}>
              Ver comisiones en curso <span aria-hidden="true">→</span>
            </button>
          </div>
        )}
        {resultadoAsignacion?.estado === 'PENDIENTE DE SOLICITUDES_V' && (
          <div className="comisiones-operacion-state success" role="status">
            Personal asignado correctamente. Estado: PENDIENTE DE SOLICITUDES_V.
            <button type="button" onClick={() => navigate('/comisiones/pendientes')}>
              Volver a comisiones pendientes <span aria-hidden="true">→</span>
            </button>
          </div>
        )}

        {!loading && opciones && !resultadoAsignacion && (
          <form className="comisiones-operacion-form" onSubmit={handleSubmit}>
            <section className="comisiones-operacion-summary" aria-label="Datos de la comisión">
              <span>COMISIÓN #{opciones.comision.id_comision}</span>
              <h2>{opciones.comision.institucion_solicita}</h2>
              <p>{opciones.comision.lugar} · {opciones.comision.area_nombre}</p>
              <p>Contacto: {opciones.comision.num_contacto}</p>
              <p>Fecha de comisión: {opciones.comision.fecha_comision ?? 'Por definir'}</p>
              <p>{opciones.comision.tipo_comision} · {opciones.comision.tipo_destino}</p>
              <p><strong>Falla reportada:</strong> {opciones.comision.falla_reportada}</p>
            </section>

            {opciones.personal.length === 0 ? (
              <p className="comisiones-operacion-state error">
                No hay personal activo disponible en el área de esta comisión.
              </p>
            ) : (
              <fieldset
                className="comisiones-operacion-personal"
                aria-describedby="personal-comisionado-hint"
              >
                <legend>
                  Personal del área <b aria-hidden="true">*</b>
                </legend>
                <div className="comisiones-operacion-personal-list">
                  {opciones.personal.map((persona) => (
                    <label key={persona.id_personal} htmlFor={`personal-${persona.id_personal}`}>
                      <input
                        id={`personal-${persona.id_personal}`}
                        type="checkbox"
                        checked={seleccionados.includes(persona.id_personal)}
                        onChange={(event) => {
                          setSeleccionados((actuales) => event.target.checked
                            ? [...actuales, persona.id_personal]
                            : actuales.filter((id) => id !== persona.id_personal))
                        }}
                      />
                      <span>
                        {persona.nombre} {persona.apellido_p} {persona.apellido_m}
                        {persona.puesto === 3 ? ' · Jefe de departamento' : ''}
                      </span>
                    </label>
                  ))}
                </div>
                <p className="comisiones-operacion-hint" id="personal-comisionado-hint">
                  Marca una o más personas para asignarlas a la comisión.
                </p>
              </fieldset>
            )}

            {error && <p className="comisiones-operacion-state error" role="alert">{error}</p>}
            <div className="comisiones-operacion-actions">
              <span>
                {opciones.comision.tipo_comision === 'FORANEA'
                  ? 'Al asignar, la comisión quedará pendiente de solicitudes de servicio.'
                  : 'Al aceptar, se registrará la fecha de inicio y la comisión quedará EN CURSO.'}
              </span>
              <button
                type="submit"
                disabled={submitting || opciones.personal.length === 0}
              >
                {submitting
                  ? opciones.comision.tipo_comision === 'FORANEA' ? 'Asignando...' : 'Iniciando...'
                  : opciones.comision.tipo_comision === 'FORANEA' ? 'Asignar' : 'Aceptar e iniciar comisión'}
                {opciones.comision.tipo_comision === 'URBANA' && <span aria-hidden="true">→</span>}
              </button>
            </div>
          </form>
        )}
      </section>
      <ConfirmationDialog
        open={mostrarAvisoSolicitudes}
        title="Solicitudes de servicio"
        message="Para continuar se tienen que llenar las solicitudes de servicio."
        actions={[]}
        cancelLabel="Entendido"
        onCancel={() => setMostrarAvisoSolicitudes(false)}
      />
    </main>
  )
}

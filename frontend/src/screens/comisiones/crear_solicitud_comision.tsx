import { useState } from 'react'
import type { FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { crearSolicitudComision } from '../../services/comisiones_services'
import type { SolicitudComisionData } from '../../services/comisiones_services'
import './styles/crear_solicitud_comision.css'

const initialForm: SolicitudComisionData = {
  institucion_solicita: '',
  ubicacion: '',
  nombre_solicita: '',
}

export default function CrearSolicitudComision() {
  const navigate = useNavigate()
  const [form, setForm] = useState(initialForm)
  const [error, setError] = useState<string | null>(null)
  const [solicitudCreada, setSolicitudCreada] = useState<number | string | null>(null)
  const [loading, setLoading] = useState(false)

  function updateField(field: keyof SolicitudComisionData, value: string) {
    setForm((current) => ({ ...current, [field]: value }))
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError(null)
    setLoading(true)
    try {
      const solicitud = await crearSolicitudComision({
        institucion_solicita: form.institucion_solicita.trim(),
        ubicacion: form.ubicacion.trim(),
        nombre_solicita: form.nombre_solicita.trim(),
      })
      setSolicitudCreada(solicitud.id_solicitud)
    } catch (submitError) {
      setError(submitError instanceof Error ? submitError.message : 'Ocurrió un error al crear la solicitud.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <main className="crear-solicitud-comision-page">
      <header className="crear-solicitud-comision-topbar">
        <button className="crear-solicitud-comision-brand" type="button" onClick={() => navigate('/home')}>
          <span className="crear-solicitud-comision-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </button>
        <span className="crear-solicitud-comision-topbar-label">MÓDULO DE COMISIONES</span>
      </header>

      <section className="crear-solicitud-comision-content">
        <button className="crear-solicitud-comision-back" type="button" onClick={() => navigate('/comisiones')}>
          ← Volver a comisiones
        </button>
        <div className="crear-solicitud-comision-heading">
          <span className="crear-solicitud-comision-eyebrow">COMISIONES · SIGSA</span>
          <h1>Crear solicitud</h1>
          <p>Captura la información de la solicitud que servirá como base para una comisión.</p>
        </div>

        <form className="crear-solicitud-comision-form" onSubmit={handleSubmit}>
          <div className="crear-solicitud-comision-form-heading">
            <h2>Datos de la solicitud</h2>
            <span><b>*</b> Campos obligatorios</span>
          </div>

          <div className="crear-solicitud-comision-fields">
            <label>
              Institución que solicita <b>*</b>
              <input
                maxLength={150}
                required
                value={form.institucion_solicita}
                onChange={(event) => updateField('institucion_solicita', event.target.value)}
                placeholder="Nombre de la institución"
              />
            </label>
            <label>
              Ubicación <b>*</b>
              <input
                maxLength={150}
                required
                value={form.ubicacion}
                onChange={(event) => updateField('ubicacion', event.target.value)}
                placeholder="Ciudad o dirección"
              />
            </label>
            <label>
              Nombre de quien solicita <b>*</b>
              <input
                maxLength={150}
                required
                value={form.nombre_solicita}
                onChange={(event) => updateField('nombre_solicita', event.target.value)}
                placeholder="Nombre completo"
              />
            </label>
          </div>

          {error && <div className="crear-solicitud-comision-message error" role="alert">{error}</div>}
          {solicitudCreada !== null && (
            <div className="crear-solicitud-comision-message success" role="status">
              <p>Solicitud #{solicitudCreada} creada correctamente.</p>
              <button
                type="button"
                onClick={() => navigate(`/comisiones/crear/${solicitudCreada}`)}
              >
                Crear comisión con esta solicitud <span aria-hidden="true">→</span>
              </button>
            </div>
          )}

          <div className="crear-solicitud-comision-actions">
            <span>La solicitud se registrará con la fecha actual y tu usuario.</span>
            <button type="submit" disabled={loading || solicitudCreada !== null}>
              {loading ? 'Enviando...' : 'Crear solicitud'} <span aria-hidden="true">→</span>
            </button>
          </div>
        </form>
      </section>
    </main>
  )
}

import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import {
  consultarOpcionesComision,
  crearComision,
} from '../../services/comisiones_services'
import type {
  ComisionData,
  OpcionesComision,
  SolicitudComision,
} from '../../services/comisiones_services'
import './styles/crear_comision.css'

const initialForm = {
  id_area: '',
  tipo_comision: '',
  tipo_destino: '',
}

export default function CrearComision() {
  const navigate = useNavigate()
  const { id_solicitud: idSolicitudParam } = useParams()
  const [opciones, setOpciones] = useState<OpcionesComision | null>(null)
  const [solicitud, setSolicitud] = useState<SolicitudComision | null>(null)
  const [form, setForm] = useState(initialForm)
  const [loading, setLoading] = useState(true)
  const [submitting, setSubmitting] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [comisionCreada, setComisionCreada] = useState<number | string | null>(null)

  useEffect(() => {
    let active = true
    consultarOpcionesComision()
      .then((resultado) => {
        if (!active) return
        setOpciones(resultado)
        const seleccionada = resultado.solicitudes.find(
          (item) => String(item.id_solicitud) === idSolicitudParam,
        )
        setSolicitud(seleccionada ?? null)
      })
      .catch((loadError: unknown) => {
        if (active) {
          setError(loadError instanceof Error
            ? loadError.message
            : 'No fue posible cargar los datos de la comisión.')
        }
      })
      .finally(() => {
        if (active) setLoading(false)
      })

    return () => {
      active = false
    }
  }, [idSolicitudParam])

  function updateField(field: keyof typeof initialForm, value: string) {
    setForm((current) => ({ ...current, [field]: value }))
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!solicitud) return

    setError(null)
    setSubmitting(true)
    try {
      const datos: ComisionData = {
        id_solicitud: Number(solicitud.id_solicitud),
        id_area: Number(form.id_area),
        tipo_comision: form.tipo_comision.trim(),
        tipo_destino: form.tipo_destino.trim(),
      }
      const resultado = await crearComision(datos)
      setComisionCreada(resultado.id_comision)
    } catch (submitError) {
      setError(submitError instanceof Error
        ? submitError.message
        : 'Ocurrió un error al crear la comisión.')
    } finally {
      setSubmitting(false)
    }
  }

  return (
    <main className="crear-comision-page">
      <header className="crear-comision-topbar">
        <button className="crear-comision-brand" type="button" onClick={() => navigate('/home')}>
          <span className="crear-comision-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </button>
        <span className="crear-comision-topbar-label">MÓDULO DE COMISIONES</span>
      </header>

      <section className="crear-comision-content">
        <button className="crear-comision-back" type="button" onClick={() => navigate('/comisiones/crear')}>
          ← Volver a solicitudes
        </button>
        <div className="crear-comision-heading">
          <span className="crear-comision-eyebrow">COMISIONES · SIGSA</span>
          <h1>Registrar comisión</h1>
          <p>Completa la información de la comisión para la solicitud seleccionada.</p>
        </div>

        {loading && <p className="crear-comision-state" role="status">Cargando datos...</p>}
        {!loading && error && <p className="crear-comision-state error" role="alert">{error}</p>}
        {!loading && !error && !solicitud && (
          <p className="crear-comision-state error" role="alert">
            Esta solicitud no está disponible o ya fue comisionada.
          </p>
        )}
        {comisionCreada !== null && (
          <div className="crear-comision-state success" role="status">
            Comisión #{comisionCreada} creada correctamente.
            <button type="button" onClick={() => navigate('/comisiones/crear')}>
              Volver a solicitudes pendientes <span aria-hidden="true">→</span>
            </button>
          </div>
        )}

        {!loading && !error && solicitud && comisionCreada === null && opciones && (
          <form className="crear-comision-form" onSubmit={handleSubmit}>
            <section className="crear-comision-solicitud" aria-label="Solicitud seleccionada">
              <span className="crear-comision-solicitud-label">SOLICITUD #{solicitud.id_solicitud}</span>
              <h2>{solicitud.institucion_solicita}</h2>
              <p>{solicitud.nombre_solicita} · {solicitud.ubicacion}</p>
              <p>Destino solicitado: {solicitud.tipo_destino}</p>
            </section>

            <div className="crear-comision-form-heading">
              <h2>Datos de la comisión</h2>
              <span><b>*</b> Campos obligatorios</span>
            </div>

            <div className="crear-comision-fields">
              <label>
                Área responsable <b>*</b>
                <select
                  required
                  value={form.id_area}
                  onChange={(event) => updateField('id_area', event.target.value)}
                >
                  <option value="" disabled>Selecciona un área</option>
                  {opciones.areas.map((area) => (
                    <option key={area.id_area} value={area.id_area}>{area.nombre}</option>
                  ))}
                </select>
              </label>
              <label>
                Tipo de comisión <b>*</b>
                <select
                  required
                  value={form.tipo_comision}
                  onChange={(event) => updateField('tipo_comision', event.target.value)}
                >
                  <option value="" disabled>Selecciona el tipo de comisión</option>
                  <option value="URBANA">Urbana</option>
                  <option value="FORANEA">Foránea</option>
                </select>
              </label>
              <label>
                Tipo de destino <b>*</b>
                <select
                  required
                  value={form.tipo_destino}
                  onChange={(event) => updateField('tipo_destino', event.target.value)}
                >
                  <option value="" disabled>Selecciona el tipo de destino</option>
                  <option value="CENTRO ESCOLAR">Centro escolar</option>
                  <option value="CENTRO ADMINISTRATIVO">Centro administrativo</option>
                </select>
              </label>
            </div>

            {error && <p className="crear-comision-state error" role="alert">{error}</p>}
            <div className="crear-comision-actions">
              <span>Al guardar, la solicitud quedará marcada como comisionada.</span>
              <button type="submit" disabled={submitting || opciones.areas.length === 0}>
                {submitting ? 'Guardando...' : 'Crear comisión'} <span aria-hidden="true">→</span>
              </button>
            </div>
          </form>
        )}
      </section>
    </main>
  )
}

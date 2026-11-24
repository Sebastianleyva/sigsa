import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import {
  consultarComisionesEnCurso,
  crearSolicitudSoporte,
} from '../../services/comisiones_services'
import type { ComisionEnCurso } from '../../services/comisiones_services'
import './styles/comisiones_operacion.css'

function fechaLocal(fecha: Date) {
  const anio = fecha.getFullYear()
  const mes = String(fecha.getMonth() + 1).padStart(2, '0')
  const dia = String(fecha.getDate()).padStart(2, '0')
  return `${anio}-${mes}-${dia}`
}

export default function ReporteSoporteMantenimiento() {
  const navigate = useNavigate()
  const { id_comision } = useParams()
  const idComision = Number(id_comision)
  const [comision, setComision] = useState<ComisionEnCurso | null>(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const [completado, setCompletado] = useState(false)
  const [form, setForm] = useState({
    direccion: '',
    departamento: '',
    nombre_reporta: '',
    cisco: '',
    fecha: fechaLocal(new Date()),
    hora: '',
    tecnico_asignado: '',
    solicitud_falla: '',
    equipos_atendidos: '',
    usuarios_beneficiados: '',
  })

  useEffect(() => {
    let active = true
    consultarComisionesEnCurso()
      .then((comisiones) => {
        if (!active) return
        const encontrada = comisiones.find(
          (item) => item.id_comision === idComision
            && item.tipo_destino.trim().toUpperCase() === 'CENTRO ADMINISTRATIVO',
        )
        if (!encontrada) {
          setError('La comisión no corresponde a un centro administrativo o no está en curso.')
          return
        }
        setComision(encontrada)
        setForm((actual) => ({
          ...actual,
          direccion: encontrada.lugar,
          departamento: encontrada.institucion_solicita,
          nombre_reporta: encontrada.nombre_solicita,
          solicitud_falla: encontrada.falla_reportada,
          tecnico_asignado: String(encontrada.comisionados[0]?.id_personal ?? ''),
        }))
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
  }, [idComision])

  function updateField(field: keyof typeof form, value: string) {
    setForm((actual) => ({ ...actual, [field]: value }))
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!comision || saving || completado) return

    setError('')
    setSaving(true)
    try {
      await crearSolicitudSoporte({
        id_comision: idComision,
        direccion: form.direccion.trim(),
        departamento: form.departamento.trim(),
        nombre_reporta: form.nombre_reporta.trim(),
        cisco: form.cisco.trim(),
        fecha: form.fecha,
        hora: form.hora,
        tecnico_asignado: Number(form.tecnico_asignado),
        solicitud_falla: form.solicitud_falla.trim(),
        equipos_atendidos: Number(form.equipos_atendidos),
        usuarios_beneficiados: Number(form.usuarios_beneficiados),
      })
      setCompletado(true)
    } catch (submitError) {
      setError(submitError instanceof Error
        ? submitError.message
        : 'No fue posible registrar la solicitud de soporte.')
    } finally {
      setSaving(false)
    }
  }

  return (
    <main className="comisiones-operacion-page">
      <header className="comisiones-operacion-topbar">
        <button className="comisiones-operacion-brand" type="button" onClick={() => navigate('/home')}>
          <span>SIGSA</span>
        </button>
        <span className="comisiones-operacion-topbar-label">MÓDULO DE COMISIONES</span>
      </header>
      <section className="comisiones-operacion-content">
        <button className="comisiones-operacion-back" type="button" onClick={() => navigate('/comisiones/en-curso')}>
          ← Volver a comisiones en curso
        </button>
        <div className="comisiones-operacion-heading">
          <span className="comisiones-operacion-eyebrow">COMISIONES · SIGSA</span>
          <h1>Solicitud de soporte y mantenimiento correctivo</h1>
          <p>Comisión #{id_comision}</p>
        </div>

        {loading && <p className="comisiones-operacion-state" role="status">Cargando datos...</p>}
        {error && <p className="comisiones-operacion-state error" role="alert">{error}</p>}
        {completado && (
          <p className="comisiones-operacion-state success" role="status">
            Solicitud de soporte registrada correctamente.
          </p>
        )}

        {!loading && comision && !completado && (
          <form className="comisiones-operacion-form" onSubmit={handleSubmit}>
            <div className="comisiones-orden-grid">
              <label>
                Dirección / domicilio
                <input
                  required
                  maxLength={150}
                  value={form.direccion}
                  onChange={(event) => updateField('direccion', event.target.value)}
                />
              </label>
              <label>
                Institución / departamento
                <input
                  required
                  maxLength={100}
                  value={form.departamento}
                  onChange={(event) => updateField('departamento', event.target.value)}
                />
              </label>
              <label>
                Nombre de quien reporta
                <input
                  required
                  maxLength={150}
                  value={form.nombre_reporta}
                  onChange={(event) => updateField('nombre_reporta', event.target.value)}
                />
              </label>
              <label>
                CISCO
                <input
                  required
                  maxLength={5}
                  value={form.cisco}
                  onChange={(event) => updateField('cisco', event.target.value.toUpperCase())}
                  placeholder="Ej. 12345"
                />
              </label>
              <label>
                Fecha
                <input
                  required
                  type="date"
                  value={form.fecha}
                  onChange={(event) => updateField('fecha', event.target.value)}
                />
              </label>
              <label>
                Hora
                <input
                  required
                  type="time"
                  value={form.hora}
                  onChange={(event) => updateField('hora', event.target.value)}
                />
              </label>
              <label>
                Técnico asignado
                <select
                  required
                  value={form.tecnico_asignado}
                  onChange={(event) => updateField('tecnico_asignado', event.target.value)}
                >
                  <option value="" disabled>Selecciona un técnico</option>
                  {comision.comisionados.map((persona) => (
                    <option key={persona.id_personal} value={persona.id_personal}>
                      {persona.nombre} {persona.apellido_p} {persona.apellido_m}
                    </option>
                  ))}
                </select>
              </label>
              <label>
                Equipos atendidos
                <input
                  required
                  type="number"
                  min={0}
                  value={form.equipos_atendidos}
                  onChange={(event) => updateField('equipos_atendidos', event.target.value)}
                />
              </label>
              <label>
                Usuarios beneficiados
                <input
                  required
                  type="number"
                  min={0}
                  value={form.usuarios_beneficiados}
                  onChange={(event) => updateField('usuarios_beneficiados', event.target.value)}
                />
              </label>
              <label className="comisiones-orden-full">
                Falla reportada
                <textarea
                  required
                  maxLength={2000}
                  rows={5}
                  value={form.solicitud_falla}
                  onChange={(event) => updateField('solicitud_falla', event.target.value)}
                />
              </label>
            </div>
            <div className="comisiones-operacion-actions">
              <span>Los datos iniciales provienen de la solicitud y la comisión.</span>
              <button type="submit" disabled={saving}>
                {saving ? 'Guardando...' : 'Guardar solicitud de soporte'}
              </button>
            </div>
          </form>
        )}
      </section>
    </main>
  )
}

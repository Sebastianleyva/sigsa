import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import {
  consultarComisionesEnCurso,
  crearOrdenServicio,
  obtenerPdfOrdenServicio,
} from '../../services/comisiones_services'
import type { ComisionEnCurso } from '../../services/comisiones_services'
import './styles/comisiones_operacion.css'

function fechaLocal(fecha: Date) {
  const anio = fecha.getFullYear()
  const mes = String(fecha.getMonth() + 1).padStart(2, '0')
  const dia = String(fecha.getDate()).padStart(2, '0')
  return `${anio}-${mes}-${dia}`
}

function numeroOpcional(valor: string) {
  return valor.trim() === '' ? null : Number(valor)
}

export default function OrdenServicio() {
  const navigate = useNavigate()
  const { id_comision } = useParams()
  const idComision = Number(id_comision)
  const [comision, setComision] = useState<ComisionEnCurso | null>(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const [completado, setCompletado] = useState(false)
  const [idOrden, setIdOrden] = useState<number | null>(null)
  const [form, setForm] = useState({
    fecha: fechaLocal(new Date()),
    n_reporte: '',
    responsable_institucion: '',
    nombre_institucion: '',
    clave_institucion: '',
    domicilio_institucion: '',
    localidad_institucion: '',
    municipio_institucion: '',
    tel_institucion: '',
    falla_reportada: '',
    actividad_realizada: '',
    observaciones: '',
    cpu: '',
    proyectores: '',
    impresoras: '',
    alumnos_benefi: '',
    donacion: '',
  })

  useEffect(() => {
    let active = true
    consultarComisionesEnCurso()
      .then((comisiones) => {
        if (!active) return
        const encontrada = comisiones.find(
          (item) => item.id_comision === idComision
            && item.tipo_destino.trim().toUpperCase() === 'CENTRO ESCOLAR',
        )
        if (!encontrada) {
          setError('La comisión no corresponde a un centro escolar o no está en curso.')
          return
        }
        setComision(encontrada)
        setForm((actual) => ({
          ...actual,
          responsable_institucion: encontrada.nombre_solicita,
          nombre_institucion: encontrada.institucion_solicita,
          domicilio_institucion: encontrada.lugar,
          falla_reportada: encontrada.falla_reportada,
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
      const orden = await crearOrdenServicio({
        id_comision: idComision,
        fecha: form.fecha,
        n_reporte: Number(form.n_reporte),
        responsable_institucion: form.responsable_institucion.trim(),
        nombre_institucion: form.nombre_institucion.trim(),
        clave_institucion: form.clave_institucion.trim(),
        domicilio_institucion: form.domicilio_institucion.trim(),
        localidad_institucion: form.localidad_institucion.trim(),
        municipio_institucion: form.municipio_institucion.trim(),
        tel_institucion: form.tel_institucion.trim(),
        falla_reportada: form.falla_reportada.trim(),
        actividad_realizada: form.actividad_realizada.trim(),
        observaciones: form.observaciones.trim(),
        cpu: numeroOpcional(form.cpu),
        proyectores: numeroOpcional(form.proyectores),
        impresoras: numeroOpcional(form.impresoras),
        alumnos_benefi: numeroOpcional(form.alumnos_benefi),
        donacion: numeroOpcional(form.donacion),
      })
      setIdOrden(orden.id_orden)
      setCompletado(true)
    } catch (submitError) {
      setError(submitError instanceof Error
        ? submitError.message
        : 'No fue posible registrar la orden de servicio.')
    } finally {
      setSaving(false)
    }
  }

  async function abrirPdf() {
    if (idOrden === null) return
    const ventana = window.open('', '_blank')
    if (!ventana) {
      setError('Permite las ventanas emergentes para abrir el documento.')
      return
    }

    ventana.opener = null
    setError('')
    try {
      ventana.location.href = await obtenerPdfOrdenServicio(idOrden)
    } catch (pdfError) {
      ventana.close()
      setError(pdfError instanceof Error
        ? pdfError.message
        : 'No fue posible generar el documento de la orden de servicio.')
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
          <h1>Orden de servicio</h1>
          <p>Comisión #{id_comision}</p>
        </div>
        {loading && <p className="comisiones-operacion-state" role="status">Cargando datos...</p>}
        {error && <p className="comisiones-operacion-state error" role="alert">{error}</p>}
        {completado && (
          <p className="comisiones-operacion-state success" role="status">
            Orden de servicio registrada y comisión finalizada correctamente.
            <button type="button" onClick={() => void abrirPdf()}>
              Imprimir / Guardar como PDF
            </button>
          </p>
        )}

        {!loading && comision && !completado && (
          <form className="comisiones-operacion-form" onSubmit={handleSubmit}>
            <div className="comisiones-orden-grid">
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
                Número de reporte
                <input
                  required
                  type="number"
                  min={1}
                  step={1}
                  value={form.n_reporte}
                  onChange={(event) => updateField('n_reporte', event.target.value)}
                />
              </label>
              <label>
                Responsable de la institución
                <input
                  required
                  maxLength={150}
                  value={form.responsable_institucion}
                  onChange={(event) => updateField('responsable_institucion', event.target.value)}
                />
              </label>
              <label>
                Nombre de la institución
                <input
                  required
                  maxLength={150}
                  value={form.nombre_institucion}
                  onChange={(event) => updateField('nombre_institucion', event.target.value)}
                />
              </label>
              <label>
                Clave de la institución
                <input
                  required
                  maxLength={10}
                  value={form.clave_institucion}
                  onChange={(event) => updateField('clave_institucion', event.target.value)}
                />
              </label>
              <label>
                Domicilio
                <input
                  required
                  maxLength={150}
                  value={form.domicilio_institucion}
                  onChange={(event) => updateField('domicilio_institucion', event.target.value)}
                />
              </label>
              <label>
                Localidad
                <input
                  required
                  maxLength={50}
                  value={form.localidad_institucion}
                  onChange={(event) => updateField('localidad_institucion', event.target.value)}
                />
              </label>
              <label>
                Municipio
                <input
                  required
                  maxLength={50}
                  value={form.municipio_institucion}
                  onChange={(event) => updateField('municipio_institucion', event.target.value)}
                />
              </label>
              <label>
                Teléfono de la institución
                <input
                  required
                  type="tel"
                  inputMode="numeric"
                  pattern="[0-9]{10}"
                  maxLength={10}
                  value={form.tel_institucion}
                  onChange={(event) => updateField('tel_institucion', event.target.value)}
                  placeholder="10 dígitos"
                />
              </label>
              <label>
                Actividad realizada
                <textarea
                  required
                  rows={4}
                  value={form.actividad_realizada}
                  onChange={(event) => updateField('actividad_realizada', event.target.value)}
                />
              </label>
              <label>
                CPU atendidas
                <input
                  type="number"
                  min={0}
                  step={1}
                  value={form.cpu}
                  onChange={(event) => updateField('cpu', event.target.value)}
                />
              </label>
              <label>
                Proyectores atendidos
                <input
                  type="number"
                  min={0}
                  step={1}
                  value={form.proyectores}
                  onChange={(event) => updateField('proyectores', event.target.value)}
                />
              </label>
              <label>
                Impresoras atendidas
                <input
                  type="number"
                  min={0}
                  step={1}
                  value={form.impresoras}
                  onChange={(event) => updateField('impresoras', event.target.value)}
                />
              </label>
              <label>
                Alumnos beneficiados
                <input
                  type="number"
                  min={0}
                  step={1}
                  value={form.alumnos_benefi}
                  onChange={(event) => updateField('alumnos_benefi', event.target.value)}
                />
              </label>
              <label>
                Donación
                <input
                  type="number"
                  min={0}
                  step={1}
                  value={form.donacion}
                  onChange={(event) => updateField('donacion', event.target.value)}
                />
              </label>
              <label className="comisiones-orden-full">
                Falla reportada
                <textarea
                  required
                  rows={4}
                  value={form.falla_reportada}
                  onChange={(event) => updateField('falla_reportada', event.target.value)}
                />
              </label>
              <label className="comisiones-orden-full">
                Observaciones
                <textarea
                  required
                  rows={4}
                  value={form.observaciones}
                  onChange={(event) => updateField('observaciones', event.target.value)}
                />
              </label>
            </div>
            <div className="comisiones-operacion-actions">
              <span>Al guardar la orden, la comisión quedará finalizada.</span>
              <button type="submit" disabled={saving}>
                {saving ? 'Guardando...' : 'Guardar orden y terminar comisión'}
              </button>
            </div>
          </form>
        )}
      </section>
    </main>
  )
}

import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import {
  consultarDatosInformeComision,
  crearInformeComision,
  obtenerPdfInformeComision,
} from '../../services/comisiones_services'
import type { DatosInformeComision } from '../../services/comisiones_services'
import './styles/comisiones_operacion.css'

type CampoInforme =
  | 'objetivo_comision'
  | 'actividad_desarrollada'
  | 'resultados'
  | 'contribucion'
  | 'conclusion'

const camposInforme: { campo: CampoInforme; etiqueta: string }[] = [
  { campo: 'objetivo_comision', etiqueta: 'Objetivo de la comisión' },
  { campo: 'actividad_desarrollada', etiqueta: 'Actividades desarrolladas' },
  { campo: 'resultados', etiqueta: 'Resultados obtenidos' },
  { campo: 'contribucion', etiqueta: 'Contribución a la institución' },
  { campo: 'conclusion', etiqueta: 'Conclusión' },
]

function formatearPeriodo(datos: DatosInformeComision) {
  const { fecha_inicio: inicio, fecha_final: final } = datos
  if (inicio && final) return inicio === final ? inicio : `${inicio} al ${final}`
  return inicio ?? final ?? datos.fecha_comision ?? 'Por definir'
}

export default function InformeComisionForm() {
  const navigate = useNavigate()
  const { id_comision } = useParams()
  const idComision = Number(id_comision)
  const [datos, setDatos] = useState<DatosInformeComision | null>(null)
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')
  const [idInforme, setIdInforme] = useState<number | null>(null)
  const [generandoPdf, setGenerandoPdf] = useState(false)
  const [errorPdf, setErrorPdf] = useState('')
  const completado = idInforme !== null
  const [form, setForm] = useState<Record<CampoInforme, string>>({
    objetivo_comision: '',
    actividad_desarrollada: '',
    resultados: '',
    contribucion: '',
    conclusion: '',
  })

  useEffect(() => {
    let active = true
    consultarDatosInformeComision(idComision)
      .then((resultado) => {
        if (!active) return
        if (resultado.informe_registrado) {
          setError('Ya registraste el informe de esta comisión.')
          return
        }
        if (!resultado.puede_registrar) {
          setError('Solo puedes registrar el informe de una comisión en curso o finalizada.')
          return
        }
        setDatos(resultado)
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

  function updateField(field: CampoInforme, value: string) {
    setForm((actual) => ({ ...actual, [field]: value }))
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    if (!datos || saving || completado) return

    setError('')
    setSaving(true)
    try {
      const informe = await crearInformeComision({
        id_comision: idComision,
        objetivo_comision: form.objetivo_comision.trim(),
        actividad_desarrollada: form.actividad_desarrollada.trim(),
        resultados: form.resultados.trim(),
        contribucion: form.contribucion.trim(),
        conclusion: form.conclusion.trim(),
      })
      setIdInforme(informe.id_informe_comision)
    } catch (submitError) {
      setError(submitError instanceof Error
        ? submitError.message
        : 'No fue posible guardar el informe de comisión.')
    } finally {
      setSaving(false)
    }
  }

  async function guardarPdf() {
    if (idInforme === null || generandoPdf) return

    const ventana = window.open('', '_blank')
    if (!ventana) {
      setErrorPdf('Permite las ventanas emergentes para abrir el documento imprimible.')
      return
    }

    setErrorPdf('')
    setGenerandoPdf(true)
    try {
      ventana.location.href = await obtenerPdfInformeComision(idInforme)
    } catch (pdfError) {
      ventana.close()
      setErrorPdf(pdfError instanceof Error
        ? pdfError.message
        : 'No fue posible generar el PDF del informe de comisión.')
    } finally {
      setGenerandoPdf(false)
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
        <button className="comisiones-operacion-back" type="button" onClick={() => navigate('/comisiones/informes')}>
          ← Volver a mis comisiones
        </button>
        <div className="comisiones-operacion-heading">
          <span className="comisiones-operacion-eyebrow">INFORMES · SIGSA</span>
          <h1>Informe de comisión</h1>
          <p>Comisión #{id_comision}</p>
        </div>

        {loading && <p className="comisiones-operacion-state" role="status">Cargando datos...</p>}
        {error && <p className="comisiones-operacion-state error" role="alert">{error}</p>}
        {completado && (
          <>
            <div className="comisiones-operacion-state success" role="status">
              <span>Informe registrado y comisión finalizada correctamente.</span>
              <div className="viaticos-solicitud-success-actions">
                <button type="button" onClick={() => void guardarPdf()} disabled={generandoPdf}>
                  {generandoPdf ? 'Generando...' : 'Guardar PDF'}
                </button>
                <button type="button" onClick={() => navigate('/comisiones/informes')}>
                  Volver a mis comisiones
                </button>
              </div>
            </div>
            {errorPdf && <p className="comisiones-operacion-state error" role="alert">{errorPdf}</p>}
          </>
        )}

        {!loading && datos && !completado && (
          <form className="comisiones-operacion-form" onSubmit={handleSubmit}>
            <section aria-labelledby="informe-comisionado-heading">
              <h2 id="informe-comisionado-heading">Datos del Personal Comisionado</h2>
              <div className="comisiones-orden-grid">
                <label>
                  Lugar de la comisión
                  <input value={datos.lugar} readOnly />
                </label>
                <label>
                  Periodo de la comisión
                  <input value={formatearPeriodo(datos)} readOnly />
                </label>
                <label>
                  Nombre del comisionado
                  <input value={datos.nombre_completo} readOnly />
                </label>
                <label>
                  RFC
                  <input value={datos.rfc} readOnly />
                </label>
                <label>
                  Área
                  <input value={datos.area_nombre} readOnly />
                </label>
              </div>
            </section>

            <section aria-labelledby="informe-contenido-heading">
              <h2 id="informe-contenido-heading">Informe</h2>
              <div className="comisiones-orden-grid">
                {camposInforme.map(({ campo, etiqueta }) => (
                  <label className="comisiones-orden-full" key={campo}>
                    {etiqueta}
                    <textarea
                      required
                      rows={5}
                      maxLength={5000}
                      value={form[campo]}
                      onChange={(event) => updateField(campo, event.target.value)}
                    />
                  </label>
                ))}
              </div>
            </section>

            <div className="comisiones-operacion-actions">
              <span>Al guardar el informe, la comisión quedará finalizada.</span>
              <button type="submit" disabled={saving}>
                {saving ? 'Guardando...' : 'Guardar'}
              </button>
            </div>
          </form>
        )}
      </section>
    </main>
  )
}

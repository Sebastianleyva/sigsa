import { useState } from 'react'
import type { FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { crearIncidencia } from '../../services/mantenimiento_services'
import './styles/reportar_incidencia.css'

type FormularioIncidencia = {
  lugar: string
  urgencia: string
  descripcion: string
}

type Aviso = {
  tipo: 'exito' | 'error'
  mensaje: string
}

const formularioInicial: FormularioIncidencia = {
  lugar: '',
  urgencia: '',
  descripcion: '',
}

export default function ReportarIncidencia() {
  const navigate = useNavigate()
  const [formulario, setFormulario] = useState(formularioInicial)
  const [aviso, setAviso] = useState<Aviso | null>(null)
  const [enviando, setEnviando] = useState(false)

  function actualizarCampo(campo: keyof FormularioIncidencia, valor: string) {
    setAviso(null)
    setFormulario((actual) => ({ ...actual, [campo]: valor }))
  }

  async function enviarFormulario(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setAviso(null)
    setEnviando(true)

    try {
      const incidencia = await crearIncidencia({
        lugar: formulario.lugar.trim(),
        urgencia: formulario.urgencia,
        descripcion: formulario.descripcion.trim(),
      })
      setAviso({
        tipo: 'exito',
        mensaje: `Incidencia${incidencia.id ? ` #${incidencia.id}` : ''} registrada y enviada a revisión.`,
      })
      setFormulario({ ...formularioInicial })
    } catch (error) {
      setAviso({
        tipo: 'error',
        mensaje: error instanceof Error ? error.message : 'No fue posible registrar la incidencia.',
      })
    } finally {
      setEnviando(false)
    }
  }

  return (
    <main className="reportar-incidencia-page">
      <header className="reportar-incidencia-topbar">
        <button
          className="reportar-incidencia-brand"
          type="button"
          onClick={() => navigate('/mantenimiento')}
        >
          <span className="reportar-incidencia-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </button>
        <div className="reportar-incidencia-topbar-meta">
          <span className="reportar-incidencia-topbar-dot" aria-hidden="true" />
          <span>MÓDULO DE MANTENIMIENTO</span>
        </div>
      </header>

      <section className="reportar-incidencia-content">
        <button
          className="reportar-incidencia-back"
          type="button"
          onClick={() => navigate('/mantenimiento')}
        >
          ← Volver al módulo
        </button>

        <div className="reportar-incidencia-heading">
          <span className="reportar-incidencia-eyebrow">OPERACIÓN · SIGSA</span>
          <h1>Reportar incidencia</h1>
          <p>Describe el problema y dónde ocurre para que el área responsable pueda revisarlo.</p>
        </div>

        <div className="reportar-incidencia-layout">
          <form className="reportar-incidencia-form" onSubmit={enviarFormulario}>
            <div className="reportar-incidencia-form-heading">
              <h2>Datos de la incidencia</h2>
              <span><b>*</b> Campos obligatorios</span>
            </div>

            <div className="reportar-incidencia-field">
              <label htmlFor="incidencia-lugar">Lugar <b>*</b></label>
              <input
                autoComplete="off"
                id="incidencia-lugar"
                maxLength={60}
                name="lugar"
                placeholder="Ej. Oficina o área"
                required
                value={formulario.lugar}
                onChange={(event) => actualizarCampo('lugar', event.target.value)}
              />
              <span className="reportar-incidencia-hint">Máximo 60 caracteres.</span>
            </div>

            <div className="reportar-incidencia-field">
              <label htmlFor="incidencia-urgencia">Nivel de urgencia <b>*</b></label>
              <select
                id="incidencia-urgencia"
                name="urgencia"
                required
                value={formulario.urgencia}
                onChange={(event) => actualizarCampo('urgencia', event.target.value)}
              >
                <option value="" disabled>Selecciona un nivel</option>
                <option value="BAJA">Baja</option>
                <option value="MEDIA">Media</option>
                <option value="ALTA">Alta</option>
              </select>
            </div>

            <div className="reportar-incidencia-field">
              <label htmlFor="incidencia-descripcion">Descripción <b>*</b></label>
              <textarea
                id="incidencia-descripcion"
                maxLength={5000}
                name="descripcion"
                placeholder="Explica qué sucede, desde cuándo y cualquier detalle que ayude a atender el problema."
                required
                rows={6}
                value={formulario.descripcion}
                onChange={(event) => actualizarCampo('descripcion', event.target.value)}
              />
              <span className="reportar-incidencia-hint">{formulario.descripcion.length}/5000</span>
            </div>

            {aviso && (
              <div
                className={`reportar-incidencia-notice reportar-incidencia-notice-${aviso.tipo}`}
                role={aviso.tipo === 'error' ? 'alert' : 'status'}
                aria-live="polite"
              >
                <span aria-hidden="true">{aviso.tipo === 'exito' ? '✓' : '!'}</span>
                <span>{aviso.mensaje}</span>
              </div>
            )}

            <div className="reportar-incidencia-actions">
              <span>La fecha se registrará automáticamente al enviar.</span>
              <button type="submit" disabled={enviando}>
                {enviando ? 'Enviando…' : 'Enviar incidencia'}
                <span aria-hidden="true">→</span>
              </button>
            </div>
          </form>

          <aside className="reportar-incidencia-aside">
            <span className="reportar-incidencia-aside-index">PROCESO</span>
            <span className="reportar-incidencia-aside-rule" aria-hidden="true" />
            <h2>¿Qué pasa después?</h2>
            <p>La incidencia se registrará con estado pendiente para que el personal autorizado la revise.</p>
            <div className="reportar-incidencia-status">
              <span aria-hidden="true">i</span>
              <span>Indica la ubicación y el nivel de urgencia con la mayor precisión posible.</span>
            </div>
          </aside>
        </div>
      </section>
    </main>
  )
}

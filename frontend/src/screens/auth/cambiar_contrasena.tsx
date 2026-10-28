import { useState } from 'react'
import type { FormEvent } from 'react'
import { useNavigate } from 'react-router-dom'
import { authService } from '../../services/auth_services.js'
import './cambiar_contrasena.css'

type FormularioContrasena = {
  contrasena: string
  nueva_contrasena: string
  confirmar_contrasena: string
}

type Aviso = {
  tipo: 'success' | 'error'
  texto: string
}

const formularioInicial: FormularioContrasena = {
  contrasena: '',
  nueva_contrasena: '',
  confirmar_contrasena: '',
}

export default function CambiarContrasenaScreen() {
  const [formulario, setFormulario] = useState(formularioInicial)
  const [aviso, setAviso] = useState<Aviso | null>(null)
  const [enviando, setEnviando] = useState(false)
  const navigate = useNavigate()

  function actualizarCampo(campo: keyof FormularioContrasena, valor: string) {
    setFormulario((actual) => ({ ...actual, [campo]: valor }))
  }

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setAviso(null)

    if (formulario.nueva_contrasena !== formulario.confirmar_contrasena) {
      setAviso({ tipo: 'error', texto: 'Las nuevas contraseñas no coinciden.' })
      return
    }

    setEnviando(true)
    try {
      const resultado = await authService.CambiarContrasena({
        contrasena: formulario.contrasena,
        nueva_contrasena: formulario.nueva_contrasena,
      })
      setFormulario({ ...formularioInicial })
      setAviso({
        tipo: 'success',
        texto: resultado.MESSAGE || 'La contraseña se actualizó correctamente.',
      })
    } catch (error) {
      setAviso({
        tipo: 'error',
        texto: error instanceof Error ? error.message : 'Ocurrió un error al cambiar la contraseña.',
      })
    } finally {
      setEnviando(false)
    }
  }

  return (
    <main className="cambiar-contrasena-page">
      <header className="cambiar-contrasena-topbar">
        <a className="cambiar-contrasena-brand" href="#inicio" onClick={(event) => event.preventDefault()}>
          <span className="cambiar-contrasena-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </a>
        <div className="cambiar-contrasena-topbar-meta">
          <span className="cambiar-contrasena-topbar-dot" aria-hidden="true" />
          <span>Seguridad de la cuenta</span>
        </div>
      </header>

      <section className="cambiar-contrasena-content" id="inicio">
        <button className="cambiar-contrasena-back" type="button" onClick={() => navigate('/home')}>
          <span aria-hidden="true">←</span> Volver al inicio
        </button>

        <div className="cambiar-contrasena-heading">
          <span className="cambiar-contrasena-eyebrow">CUENTA · SIGSA</span>
          <h1>Cambiar contraseña</h1>
          <p>Confirma tu contraseña actual y elige una nueva para proteger tu cuenta.</p>
        </div>

        <form className="cambiar-contrasena-form" onSubmit={handleSubmit}>
          <div className="cambiar-contrasena-form-heading">
            <span className="cambiar-contrasena-step">01</span>
            <h2>Actualizar credenciales</h2>
          </div>

          <div className="cambiar-contrasena-fields">
            <div className="cambiar-contrasena-field">
              <label htmlFor="contrasena-actual">Contraseña actual</label>
              <input
                id="contrasena-actual"
                name="contrasena"
                type="password"
                autoComplete="current-password"
                value={formulario.contrasena}
                onChange={(event) => actualizarCampo('contrasena', event.target.value)}
                required
              />
            </div>

            <div className="cambiar-contrasena-field">
              <label htmlFor="contrasena-nueva">Nueva contraseña</label>
              <input
                id="contrasena-nueva"
                name="nueva_contrasena"
                type="password"
                autoComplete="new-password"
                value={formulario.nueva_contrasena}
                onChange={(event) => actualizarCampo('nueva_contrasena', event.target.value)}
                required
              />
            </div>

            <div className="cambiar-contrasena-field">
              <label htmlFor="confirmar-contrasena">Confirmar nueva contraseña</label>
              <input
                id="confirmar-contrasena"
                name="confirmar_contrasena"
                type="password"
                autoComplete="new-password"
                value={formulario.confirmar_contrasena}
                onChange={(event) => actualizarCampo('confirmar_contrasena', event.target.value)}
                required
              />
            </div>
          </div>

          {aviso && (
            <p
              className={`cambiar-contrasena-notice cambiar-contrasena-notice-${aviso.tipo}`}
              role={aviso.tipo === 'error' ? 'alert' : 'status'}
            >
              <span aria-hidden="true">{aviso.tipo === 'success' ? '✓' : '!'}</span>
              {aviso.texto}
            </p>
          )}

          <div className="cambiar-contrasena-actions">
            <span>La contraseña actual es necesaria para confirmar el cambio.</span>
            <button type="submit" disabled={enviando}>
              {enviando ? 'Actualizando…' : 'Actualizar contraseña'}
              <span aria-hidden="true">→</span>
            </button>
          </div>
        </form>
      </section>

      <footer className="cambiar-contrasena-footer">
        <span>© SIGSA</span>
        <span>Sistema de gestión de servicios administrativos</span>
      </footer>
    </main>
  )
}

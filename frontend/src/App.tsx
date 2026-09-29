import { useState } from 'react'
import type { FormEvent } from 'react'
import './App.css'

type Screen = 'login' | 'register'

function App() {
  const [screen, setScreen] = useState<Screen>('login')
  const [passwordVisible, setPasswordVisible] = useState(false)
  const [notice, setNotice] = useState('')

  function changeScreen(nextScreen: Screen) {
    setScreen(nextScreen)
    setNotice('')
    setPasswordVisible(false)
  }

  function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setNotice('El formulario está listo; falta conectarlo al servicio de autenticación.')
  }

  return (
    <main className="auth-shell">
      <aside className="visual-panel" aria-label="Sistema SIGSA">
        <img
          className="visual-photo"
          src="https://images.unsplash.com/photo-1497366811353-6870744d04b2?auto=format&fit=crop&w=1500&q=85"
          alt="Espacio de trabajo con escritorios y luz natural"
        />
        <div className="visual-shade" />
        <div className="visual-content">
          <a className="brand brand-light" href="#inicio" onClick={(event) => event.preventDefault()}>
            <span className="brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
            <span>SIGSA</span>
          </a>
          <div className="visual-copy">
            <span className="eyebrow">SISTEMA INTEGRAL DE GESTIÓN</span>
            <h1>Todo en orden.<br />Cada día.</h1>
            <p>Un espacio de trabajo más claro para los equipos que hacen que las cosas sucedan.</p>
          </div>
          <span className="visual-caption">GESTIÓN · EQUIPO · SERVICIO</span>
        </div>
      </aside>

      <section className="form-panel" id="inicio">
        <header className="mobile-brand">
          <a className="brand" href="#inicio" onClick={(event) => event.preventDefault()}>
            <span className="brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
            <span>SIGSA</span>
          </a>
          <span className="mobile-tagline">Sistema integral de gestión</span>
        </header>

        <div className="form-content" key={screen}>
          {screen === 'login' ? (
            <>
              <span className="eyebrow form-eyebrow">BIENVENIDO DE NUEVO</span>
              <h2>Inicia sesión</h2>
              <p className="form-intro">Ingresa tus datos para continuar.</p>

              <form className="auth-form" onSubmit={handleSubmit}>
                <label htmlFor="login-email">Correo electrónico</label>
                <input
                  id="login-email"
                  name="correo"
                  type="email"
                  autoComplete="email"
                  placeholder="nombre@institucion.gob.mx"
                  required
                />

                <label htmlFor="login-password">Contraseña</label>
                <div className="password-field">
                  <input
                    id="login-password"
                    name="contrasena"
                    type={passwordVisible ? 'text' : 'password'}
                    autoComplete="current-password"
                    placeholder="Ingresa tu contraseña"
                    required
                  />
                  <button
                    className="visibility-button"
                    type="button"
                    aria-label={passwordVisible ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                    onClick={() => setPasswordVisible((visible) => !visible)}
                  >
                    {passwordVisible ? 'Ocultar' : 'Mostrar'}
                  </button>
                </div>

                <button className="submit-button" type="submit">
                  Iniciar sesión <span aria-hidden="true">→</span>
                </button>
              </form>

              {notice && <p className="form-notice" role="status">{notice}</p>}

              <p className="form-switch">
                ¿Aún no tienes una cuenta?{' '}
                <button type="button" onClick={() => changeScreen('register')}>Crear cuenta</button>
              </p>
            </>
          ) : (
            <>
              <span className="eyebrow form-eyebrow">NUEVO EN SIGSA</span>
              <h2>Crea tu cuenta</h2>
              <p className="form-intro">Completa tus datos para solicitar tu acceso.</p>

              <form className="auth-form registration-form" onSubmit={handleSubmit}>
                <div className="field-pair">
                  <div className="field-group">
                    <label htmlFor="register-name">Nombre(s)</label>
                    <input id="register-name" name="nombre" autoComplete="given-name" required />
                  </div>
                  <div className="field-group">
                    <label htmlFor="register-last-name">Apellido paterno</label>
                    <input id="register-last-name" name="apellido_p" autoComplete="family-name" required />
                  </div>
                </div>

                <div className="field-pair">
                  <div className="field-group">
                    <label htmlFor="register-second-last-name">Apellido materno</label>
                    <input id="register-second-last-name" name="apellido_m" required />
                  </div>
                  <div className="field-group">
                    <label htmlFor="register-phone">Teléfono</label>
                    <input id="register-phone" name="telefono" type="tel" autoComplete="tel" required />
                  </div>
                </div>

                <div className="field-pair">
                  <div className="field-group">
                    <label htmlFor="register-rfc">RFC</label>
                    <input id="register-rfc" name="rfc" autoComplete="off" required />
                  </div>
                  <div className="field-group">
                    <label htmlFor="register-curp">CURP</label>
                    <input id="register-curp" name="curp" autoComplete="off" required />
                  </div>
                </div>

                <label htmlFor="register-email">Correo electrónico</label>
                <input id="register-email" name="correo" type="email" autoComplete="email" required />

                <div className="field-pair">
                  <div className="field-group">
                    <label htmlFor="register-position">Puesto</label>
                    <input id="register-position" name="puesto" required />
                  </div>
                  <div className="field-group">
                    <label htmlFor="register-area">Área</label>
                    <input id="register-area" name="area" />
                  </div>
                </div>

                <label htmlFor="register-password">Contraseña</label>
                <div className="password-field">
                  <input
                    id="register-password"
                    name="contrasena"
                    type={passwordVisible ? 'text' : 'password'}
                    autoComplete="new-password"
                    required
                  />
                  <button
                    className="visibility-button"
                    type="button"
                    aria-label={passwordVisible ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                    onClick={() => setPasswordVisible((visible) => !visible)}
                  >
                    {passwordVisible ? 'Ocultar' : 'Mostrar'}
                  </button>
                </div>

                <button className="submit-button" type="submit">
                  Crear cuenta <span aria-hidden="true">→</span>
                </button>
              </form>

              {notice && <p className="form-notice" role="status">{notice}</p>}

              <p className="form-switch">
                ¿Ya tienes una cuenta?{' '}
                <button type="button" onClick={() => changeScreen('login')}>Volver al inicio de sesión</button>
              </p>
            </>
          )}
        </div>

        <footer className="form-footer">
          <span>© SIGSA</span>
          <span>Acceso seguro al sistema</span>
        </footer>
      </section>
    </main>
  )
}

export default App
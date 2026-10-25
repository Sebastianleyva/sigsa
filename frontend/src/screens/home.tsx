import { useNavigate } from 'react-router-dom'
import './home.css'

const modules = [
  {
    index: '01',
    title: 'Salidas',
    description: 'Registra una solicitud o consulta el historial de tus salidas.',
    category: 'OPERACIÓN',
    path: '/salidas',
  },
  {
    index: '02',
    title: 'Gestión de comisiones',
    description: 'Administra la información y organización del equipo de trabajo.',
    category: 'PERSONAL',
    path: '/comisiones',
  },
  {
    index: '03',
    title: 'Mantenimiento',
    description: 'Mantén actualizados los catálogos y datos del sistema.',
    category: 'SISTEMA',
    path: '/mantenimiento',
  },
]

export default function HomeScreen() {
  const navigate = useNavigate()

  return (
    <main className="home-page">
      <header className="home-topbar">
        <a className="home-brand" href="#inicio" onClick={(event) => event.preventDefault()}>
          <span className="home-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </a>
        <div className="home-topbar-meta">
          <span className="home-topbar-dot" aria-hidden="true" />
          <span>Sistema de gestión de servicios administrativos</span>
        </div>
      </header>

      <section className="home-content" id="inicio">
        <div className="home-heading">
          <span className="home-eyebrow">PANEL PRINCIPAL · SIGSA</span>
          <h1>Bienvenido a SIGSA</h1>
          <p>Un espacio para organizar y dar seguimiento a la gestión del equipo.</p>
        </div>

        <section className="home-modules" aria-labelledby="home-modules-title">
          <div className="home-section-heading">
            <div>
              <span className="home-section-index">01 / MÓDULOS</span>
              <h2 id="home-modules-title">Áreas del sistema</h2>
            </div>
            <span className="home-section-note">PLATAFORMA DE GESTIÓN</span>
          </div>

          <div className="home-module-grid">
            {modules.map((module) => (
              <button
                className={`home-module-card${module.path ? ' home-module-card-active' : ''}`}
                key={module.index}
                type="button"
                onClick={() => {
                  if (module.path) navigate(module.path)
                }}
              >
                <span className="home-module-category">{module.category}</span>
                <span className="home-module-index">{module.index}</span>
                <h3>{module.title}</h3>
                <p>{module.description}</p>
                <span className="home-module-rule" aria-hidden="true" />
              </button>
            ))}
          </div>
        </section>

        <footer className="home-footer">
          <span>© SIGSA</span>
          <span>Sistema de gestión de servicios administrativos</span>
        </footer>
      </section>
    </main>
  )
}

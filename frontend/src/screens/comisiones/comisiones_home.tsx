import { useNavigate } from 'react-router-dom'
import './styles/comisiones_home.css'
import { authService } from '../../services/auth_services.js'

const opciones = [
  {
    index: '01',
    category: 'NUEVA SOLICITUD',
    title: 'Crear solicitud',
    description: 'Registra los datos de la solicitud que dará origen a una comisión.',
    action: 'Crear solicitud',
    path: '/comisiones/solicitudes/crear',
  },
  {
    index: '02',
    category: 'NUEVA COMISIÓN',
    title: 'Crear comisión',
    description: 'Genera una comisión a partir de una solicitud registrada.',
    action: 'Crear comisión',
    path: '/comisiones/crear',
  },
]

export default function ComisionesHome() {
  const navigate = useNavigate()
  const usuario = authService.getUsuarioActual()
  const puedeCrearComision = [1, 2].includes(Number(usuario?.puesto))
    || Number(usuario?.area) === 6
  const opcionesVisibles = opciones.filter((opcion) => (
    opcion.path !== '/comisiones/crear' || puedeCrearComision
  ))

  return (
    <main className="comisiones-home-page">
      <header className="comisiones-home-topbar">
        <button className="comisiones-home-brand" type="button" onClick={() => navigate('/home')}>
          <span className="comisiones-home-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </button>
        <div className="comisiones-home-topbar-meta">
          <span className="comisiones-home-topbar-dot" aria-hidden="true" />
          <span>Sistema de gestión de servicios administrativos</span>
        </div>
      </header>

      <section className="comisiones-home-content">
        <div className="comisiones-home-heading">
          <span className="comisiones-home-eyebrow">PERSONAL · SIGSA</span>
          <h1>Gestión de comisiones</h1>
          <p>Registra solicitudes y crea comisiones para organizar las actividades fuera de la oficina.</p>
        </div>

        <section className="comisiones-home-options" aria-labelledby="comisiones-home-options-title">
          <div className="comisiones-home-section-heading">
            <div>
              <span className="comisiones-home-section-index">01 / ACCIONES</span>
              <h2 id="comisiones-home-options-title">¿Qué deseas hacer?</h2>
            </div>
            <span className="comisiones-home-section-note">MÓDULO DE COMISIONES</span>
          </div>

          <div className="comisiones-home-grid">
            {opcionesVisibles.map((opcion) => (
              <button
                className="comisiones-home-card"
                key={opcion.index}
                type="button"
                onClick={() => navigate(opcion.path)}
              >
                <span className="comisiones-home-card-category">{opcion.category}</span>
                <span className="comisiones-home-card-index">{opcion.index}</span>
                <h3>{opcion.title}</h3>
                <p>{opcion.description}</p>
                <span className="comisiones-home-card-action">
                  {opcion.action}<span aria-hidden="true">→</span>
                </span>
                <span className="comisiones-home-card-rule" aria-hidden="true" />
              </button>
            ))}
          </div>
        </section>

        <footer className="comisiones-home-footer">
          <button type="button" onClick={() => navigate('/home')}>← Volver a módulos</button>
          <span>© SIGSA · Sistema integral de gestión</span>
        </footer>
      </section>
    </main>
  )
}
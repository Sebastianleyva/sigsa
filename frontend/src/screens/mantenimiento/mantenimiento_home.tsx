import { useLocation, useNavigate } from 'react-router-dom'
import { authService } from '../../services/auth_services.js'
import './styles/mantenimiento_home.css'

type OpcionMantenimiento = {
    index: string
    category: string
    title: string
    description: string
    action: string
    path: string
}

const opcionesGenerales: OpcionMantenimiento[] = [
    {
        index: '01',
        category: 'CONSULTA',
        title: 'Ver calendario',
        description: 'Consulta las fechas programadas y los servicios de mantenimiento.',
        action: 'Abrir calendario',
        path: '/mantenimiento/calendario',
    },
    {
        index: '02',
        category: 'NUEVO REPORTE',
        title: 'Reportar incidencia',
        description: 'Registra un problema de mantenimiento para que pueda revisarse.',
        action: 'Reportar incidencia',
        path: '/mantenimiento/incidencias/crear',
    },
]

const opcionesAdministrativas: OpcionMantenimiento[] = [
  {
        index: '03',
        category: 'AUTORIZACIÓN',
        title: 'Ver incidencias',
        description: 'Revisa las incidencias reportadas y su estado de atención.',
        action: 'Consultar incidencias',
        path: '/mantenimiento/incidencias',
    },
    {
        index: '04',
        category: 'CONSULTA',
        title: 'Historial de reportes',
        description: 'Consulta los reportes de mantenimiento registrados.',
        action: 'Ver historial',
        path: '/mantenimiento/reportes/historial',
    },
    {
        index: '05',
        category: 'SEGUIMIENTO',
        title: 'Mantenimientos en curso',
        description: 'Da seguimiento a los trabajos de mantenimiento activos.',
        action: 'Ver mantenimientos',
        path: '/mantenimiento/reportes/en-curso',
    },
    {
        index: '06',
        category: 'CATÁLOGO',
        title: 'Servicios',
        description: 'Crea servicios de mantenimiento y desactiva los que ya no se utilicen.',
        action: 'Administrar servicios',
        path: '/mantenimiento/servicios',
    },
]

const destinos: Record<string, string> = {
    '/mantenimiento/calendario': 'Calendario de mantenimiento',
    '/mantenimiento/incidencias/crear': 'Reportar incidencia',
    '/mantenimiento/incidencias': 'Incidencias',
    '/mantenimiento/reportes/historial': 'Historial de reportes',
    '/mantenimiento/reportes/en-curso': 'Mantenimientos en curso',
    '/mantenimiento/servicios': 'Servicios',
}

export default function MantenimientoHome() {
    const navigate = useNavigate()
    const usuario = authService.getUsuarioActual()
    const puedeVerFuncionesAdministrativas = [1, 2, 3].includes(usuario?.puesto ?? 0)

    const renderOpciones = (opciones: OpcionMantenimiento[]) => opciones.map((opcion) => (
      <button
        className="mantenimiento-home-card"
        key={opcion.index}
        type="button"
        onClick={() => navigate(opcion.path)}
      >
        <span className="mantenimiento-home-card-category">{opcion.category}</span>
        <span className="mantenimiento-home-card-index">{opcion.index}</span>
        <h3>{opcion.title}</h3>
        <p>{opcion.description}</p>
        <span className="mantenimiento-home-card-action">
          {opcion.action}
          <span aria-hidden="true">→</span>
        </span>
        <span className="mantenimiento-home-card-rule" aria-hidden="true" />
      </button>
    ))

    return (
        <main className="mantenimiento-home-page">
            <header className="mantenimiento-home-topbar">
                <button className="mantenimiento-home-brand" type="button" onClick={() => navigate('/home')}>
                    <span className="mantenimiento-home-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
                    <span>SIGSA</span>
                </button>
                <div className="mantenimiento-home-topbar-meta">
                    <span className="mantenimiento-home-topbar-dot" aria-hidden="true" />
                    <span>Sistema de gestión de servicios administrativos</span>
                </div>
            </header>

            <section className="mantenimiento-home-content">
                <div className="mantenimiento-home-heading">
                    <span className="mantenimiento-home-eyebrow">OPERACIÓN · SIGSA</span>
                    <h1>Control de mantenimiento</h1>
                    <p>Organiza los servicios programados, reporta incidencias y consulta el avance de los trabajos.</p>
                </div>

                <section className="mantenimiento-home-options" aria-labelledby="mantenimiento-home-options-title">
                    <div className="mantenimiento-home-section-heading">
                        <div>
                            <span className="mantenimiento-home-section-index">01 / ACCIONES</span>
                            <h2 id="mantenimiento-home-options-title">¿Qué deseas hacer?</h2>
                        </div>
                        <span className="mantenimiento-home-section-note">MÓDULO DE MANTENIMIENTO</span>
                    </div>

                    <div className="mantenimiento-home-grid">
                        {renderOpciones(opcionesGenerales)}
                    </div>
                </section>

                {puedeVerFuncionesAdministrativas && (
                    <section className="mantenimiento-home-admin-options" aria-labelledby="mantenimiento-home-admin-title">
                        <div className="mantenimiento-home-section-heading">
                            <div>
                                <span className="mantenimiento-home-section-index">02 / ADMINISTRACIÓN</span>
                                <h2 id="mantenimiento-home-admin-title">Funciones administrativas</h2>
                            </div>
                        </div>
                        <div className="mantenimiento-home-grid mantenimiento-home-admin-grid">
                            {renderOpciones(opcionesAdministrativas)}
                        </div>
                    </section>
                )}

                <footer className="mantenimiento-home-footer">
                    <button type="button" onClick={() => navigate('/home')}>← Volver a módulos</button>
                    <span>© SIGSA · Sistema integral de gestión</span>
                </footer>
            </section>
        </main>
    )
}

export function MantenimientoDestino() {
    const navigate = useNavigate()
    const { pathname } = useLocation()
    const titulo = destinos[pathname.replace(/\/$/, '')] ?? 'Sección de mantenimiento'

    return (
        <main className="mantenimiento-home-page">
            <header className="mantenimiento-home-topbar">
                <button className="mantenimiento-home-brand" type="button" onClick={() => navigate('/home')}>
                    <span className="mantenimiento-home-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
                    <span>SIGSA</span>
                </button>
                <div className="mantenimiento-home-topbar-meta">
                    <span className="mantenimiento-home-topbar-dot" aria-hidden="true" />
                    <span>MÓDULO DE MANTENIMIENTO</span>
                </div>
            </header>
            <section className="mantenimiento-home-content mantenimiento-home-destination">
                <span className="mantenimiento-home-eyebrow">MANTENIMIENTO · SIGSA</span>
                <h1>{titulo}</h1>
                <p>Esta sección aún está en desarrollo.</p>
                <button className="mantenimiento-home-back" type="button" onClick={() => navigate('/mantenimiento')}>
                    ← Volver al módulo
                </button>
            </section>
        </main>
    )
}

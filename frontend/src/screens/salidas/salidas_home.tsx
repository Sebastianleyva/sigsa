import { useNavigate } from 'react-router-dom';
import './styles/salidas_home.css';
import {authService} from '../../services/auth_services.js'


type OpcionSalida = {
  index: string
  category: string
  title: string
  description: string
  action: string
  path: string
}

const opcionesGenerales: OpcionSalida[] = [
  {
    index: '01',
    category: 'NUEVA SOLICITUD',
    title: 'Crear salida',
    description: 'Registra una solicitud de salida para que pueda ser revisada.',
    action: 'Crear solicitud',
    path: '/salidas/crear',
  },
  {
    index: '02',
    category: 'CONSULTA',
    title: 'Ver salidas',
    description: 'Consulta el historial y los detalles de tus salidas registradas.',
    action: 'Ver historial',
    path: '/salidas/historial',
  },
];

const opcionesAdministrativas: OpcionSalida[] = [
  {
    index: '03',
    category: 'AUTORIZACIÓN',
    title: 'Ver salidas pendientes',
    description: 'Revisa las solicitudes de salida que están pendientes de autorización.',
    action: 'Ver pendientes',
    path: '/salidas/pendientes',
  },
  {
    index: '04',
    category: 'SEGUIMIENTO',
    title: 'Salidas en curso',
    description: 'Consulta las salidas autorizadas que continúan activas.',
    action: 'Ver salidas activas',
    path: '/salidas/en-curso',
  },
  {
    index: '05',
    category: 'CONSULTA',
    title: 'Historial general',
    description: 'Consulta las salidas registradas de todo el personal durante el último año.',
    action: 'Ver historial general',
    path: '/salidas/historial-general',
  },
];

export default function SalidasHome() {
  const navigate = useNavigate();
  const usuario = authService.getUsuarioActual()

  const puedeVerFuncionesAdministrativas = [1, 2, 3].includes(usuario?.puesto ?? 0);

  const renderOpciones = (opciones: OpcionSalida[]) => opciones.map((opcion) => (
    <button
      className="salidas-home-card"
      key={opcion.index}
      type="button"
      onClick={() => navigate(opcion.path)}
    >
      <span className="salidas-home-card-category">{opcion.category}</span>
      <span className="salidas-home-card-index">{opcion.index}</span>
      <h3>{opcion.title}</h3>
      <p>{opcion.description}</p>
      <span className="salidas-home-card-action">
        {opcion.action}
        <span aria-hidden="true">→</span>
      </span>
      <span className="salidas-home-card-rule" aria-hidden="true" />
    </button>
  ))

  return (
    <main className="salidas-home-page">
      <header className="salidas-home-topbar">
        <a className="salidas-home-brand" href="#inicio" onClick={(event) => event.preventDefault()}>
          <span className="salidas-home-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </a>
        <div className="salidas-home-topbar-meta">
          <span className="salidas-home-topbar-dot" aria-hidden="true" />
          <span>Sistema de gestión de servicios administrativos</span>
        </div>
      </header>

      <section className="salidas-home-content" id="inicio">
        <div className="salidas-home-heading">
          <span className="salidas-home-eyebrow">OPERACIÓN · SIGSA</span>
          <h1>Control de salidas</h1>
          <p>Accede a tus solicitudes para registrar una nueva salida o consultar las que ya realizaste.</p>
        </div>

        <section className="salidas-home-options" aria-labelledby="salidas-home-options-title">
          <div className="salidas-home-section-heading">
            <div>
              <span className="salidas-home-section-index">01 / ACCIONES</span>
              <h2 id="salidas-home-options-title">¿Qué deseas hacer?</h2>
            </div>
            <span className="salidas-home-section-note">MÓDULO DE SALIDAS</span>
          </div>

          <div className="salidas-home-grid">
            {renderOpciones(opcionesGenerales)}
          </div>
        </section>

        {puedeVerFuncionesAdministrativas && (
          <section className="salidas-home-admin-options" aria-labelledby="salidas-home-admin-title">
            <div className="salidas-home-section-heading">
              <div>
                <span className="salidas-home-section-index">02 / ADMINISTRACIÓN</span>
                <h2 id="salidas-home-admin-title">Funciones administrativas</h2>
              </div>
            </div>
            <div className="salidas-home-grid salidas-home-admin-grid">
              {renderOpciones(opcionesAdministrativas)}
            </div>
          </section>
        )}

        <footer className="salidas-home-footer">
          <span>© SIGSA</span>
          <span>Sistema integral de gestión</span>
        </footer>
      </section>
    </main>
  );
}

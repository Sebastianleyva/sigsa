import { useNavigate } from 'react-router-dom'
import './styles/comisiones_home.css'
import { authService } from '../../services/auth_services.js'

const opciones = [
  {
    index: '01',
    division: 'foraneas',
    category: 'NUEVA SOLICITUD',
    title: 'Crear solicitud',
    description: 'Registra los datos de la solicitud que dará origen a una comisión.',
    action: 'Crear solicitud',
    path: '/comisiones/solicitudes/crear',
  },
  {
    index: '02',
    division: 'foraneas',
    category: 'NUEVA COMISIÓN',
    title: 'Crear comisión',
    description: 'Genera una comisión a partir de una solicitud registrada.',
    action: 'Crear comisión',
    path: '/comisiones/crear',
  },
  {
    index: '03',
    division: 'foraneas',
    category: 'ASIGNACIÓN',
    title: 'Comisiones pendientes',
    description: 'Asigna al personal de tu departamento para iniciar sus comisiones.',
    action: 'Asignar comisionados',
    path: '/comisiones/pendientes',
  },
  {
    index: '04',
    division: 'foraneas',
    category: 'PROGRAMACIÓN',
    title: 'Comisiones programadas',
    description: 'Inicia las comisiones foráneas con viáticos completos al llegar su fecha de inicio.',
    action: 'Ver programadas',
    path: '/comisiones/programadas',
  },
  {
    index: '05',
    division: 'foraneas',
    category: 'SEGUIMIENTO',
    title: 'Comisiones en curso',
    description: 'Consulta el lugar, la fecha de inicio y el personal comisionado.',
    action: 'Ver comisiones',
    path: '/comisiones/en-curso',
  },
  {
    index: '06',
    division: 'foraneas',
    category: 'INFORMES',
    title: 'Informe de comisión',
    description: 'Consulta las comisiones en las que participas y registra su informe.',
    action: 'Informe de comisión',
    path: '/comisiones/informes',
  },
  {
    index: '07',
    division: 'viaticos',
    category: 'VIÁTICOS',
    title: 'Solicitudes de viáticos',
    description: 'Consulta y gestiona las solicitudes de viáticos de las comisiones.',
    action: 'Ver solicitudes',
    path: '/comisiones/solicitudes-viaticos',
  },
  {
    index: '08',
    division: 'viaticos',
    category: 'CONSULTA · VIÁTICOS',
    title: 'Ver solicitudes de viáticos',
    description: 'Consulta las solicitudes registradas por mes y descarga sus documentos PDF.',
    action: 'Consultar solicitudes',
    path: '/comisiones/ver-solicitudes-viaticos',
  },
  {
    index: '09',
    division: 'viaticos',
    category: 'CONSULTA · INFORMES',
    title: 'Ver informes de comisión',
    description: 'Consulta los informes de comisión por mes y descarga sus documentos PDF.',
    action: 'Consultar informes',
    path: '/comisiones/ver-informes-comision',
  },
]

export default function ComisionesHome() {
  const navigate = useNavigate()
  const usuario = authService.getUsuarioActual()
  const puedeCrearSolicitud = [1, 2].includes(Number(usuario?.puesto))
    || Number(usuario?.area) === 6
  const puedeCrearComision = [1, 2].includes(Number(usuario?.puesto))
    || Number(usuario?.area) === 6
  const puedeGestionarComisiones = [2, 3].includes(Number(usuario?.puesto))
  const puedeConsultarViaticos = [1, 2].includes(Number(usuario?.puesto))
    || Number(usuario?.area) === 6
  const opcionesVisibles = opciones.filter((opcion) => (
    (opcion.path !== '/comisiones/solicitudes/crear' || puedeCrearSolicitud)
    && (opcion.path !== '/comisiones/crear' || puedeCrearComision)
    && (opcion.path !== '/comisiones/pendientes' || puedeGestionarComisiones)
    && (opcion.path !== '/comisiones/solicitudes-viaticos' || puedeConsultarViaticos)
    && (opcion.path !== '/comisiones/ver-solicitudes-viaticos' || puedeConsultarViaticos)
    && (opcion.path !== '/comisiones/ver-informes-comision' || puedeConsultarViaticos)
  ))
  const opcionesComisionesForaneas = opcionesVisibles.filter((opcion) => opcion.division === 'foraneas')
  const opcionesViaticos = opcionesVisibles.filter((opcion) => opcion.division === 'viaticos')

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

        <section className="comisiones-home-options" aria-labelledby="comisiones-home-foraneas-title">
          <div className="comisiones-home-section-heading">
            <div>
              <span className="comisiones-home-section-index">01 / COMISIONES</span>
              <h2 id="comisiones-home-foraneas-title">¿Qué deseas hacer?</h2>
            </div>
            <span className="comisiones-home-section-note">REGISTRO Y SEGUIMIENTO</span>
          </div>

          <div className="comisiones-home-grid">
            {opcionesComisionesForaneas.map((opcion) => (
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

        {opcionesViaticos.length > 0 && (
          <section className="comisiones-home-options" aria-labelledby="comisiones-home-viaticos-title">
            <div className="comisiones-home-section-heading">
              <div>
                <span className="comisiones-home-section-index">02 / ADMINISTRACIÓN</span>
                <h2 id="comisiones-home-viaticos-title">Funciones administrativas</h2>
              </div>
              <span className="comisiones-home-section-note">CONSULTA Y VIÁTICOS</span>
            </div>

            <div className="comisiones-home-grid">
              {opcionesViaticos.map((opcion) => (
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
        )}

        <footer className="comisiones-home-footer">
          <button type="button" onClick={() => navigate('/home')}>← Volver a módulos</button>
          <span>© SIGSA · Sistema integral de gestión</span>
        </footer>
      </section>
    </main>
  )
}
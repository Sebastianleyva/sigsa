import { useEffect, useState } from 'react'
import { Link, Navigate, useNavigate } from 'react-router-dom'
import { authService } from '../../services/auth_services.js'
import {
  consultarPersonalPendienteSolicitudViaticos,
} from '../../services/comisiones_services'
import type { PersonaPendienteSolicitudViaticos } from '../../services/comisiones_services'
import './styles/comisiones_operacion.css'

export default function SolicitudesViaticos() {
  const navigate = useNavigate()
  const usuario = authService.getUsuarioActual()
  const puedeConsultar = [1, 2].includes(Number(usuario?.puesto))
    || Number(usuario?.area) === 6
  const [personal, setPersonal] = useState<PersonaPendienteSolicitudViaticos[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (!puedeConsultar) return

    let active = true
    consultarPersonalPendienteSolicitudViaticos()
      .then((resultado) => {
        if (active) setPersonal(resultado)
      })
      .catch((loadError: unknown) => {
        if (active) {
          setError(loadError instanceof Error
            ? loadError.message
            : 'No fue posible consultar el personal pendiente de solicitudes de viáticos.')
        }
      })
      .finally(() => {
        if (active) setLoading(false)
      })

    return () => {
      active = false
    }
  }, [puedeConsultar])

  if (!puedeConsultar) {
    return <Navigate to="/comisiones" replace />
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
        <button className="comisiones-operacion-back" type="button" onClick={() => navigate('/comisiones')}>
          ← Volver a comisiones
        </button>
        <div className="comisiones-operacion-heading">
          <span className="comisiones-operacion-eyebrow">COMISIONES · SIGSA</span>
          <h1>Solicitudes de viáticos</h1>
          <p>Personal asignado a comisiones pendientes de solicitudes de viáticos.</p>
        </div>

        {loading && <p className="comisiones-operacion-state" role="status">Cargando personal...</p>}
        {error && <p className="comisiones-operacion-state error" role="alert">{error}</p>}
        {!loading && !error && personal.length === 0 && (
          <p className="comisiones-operacion-state">
            No hay personal en comisiones pendientes de solicitudes de viáticos.
          </p>
        )}

        {!loading && !error && personal.length > 0 && (
          <section className="comisiones-operacion-list" aria-label="Personal pendiente de solicitudes de viáticos">
            {personal.map((persona) => (
              <article
                className="comisiones-operacion-card"
                key={`${persona.id_comision}-${persona.id_personal}`}
              >
                <div className="comisiones-operacion-card-header">
                  <span>COMISIÓN #{persona.id_comision} · {persona.estado}</span>
                  <span>{persona.tipo_comision}</span>
                </div>
                <h2>{persona.nombre} {persona.apellido_p} {persona.apellido_m}</h2>
                <dl>
                  <div><dt>Puesto</dt><dd>{persona.puesto_nombre}</dd></div>
                  <div><dt>Institución</dt><dd>{persona.institucion_solicita}</dd></div>
                  <div><dt>Lugar</dt><dd>{persona.lugar}</dd></div>
                  <div><dt>Fecha de comisión</dt><dd>{persona.fecha_comision ?? 'Por definir'}</dd></div>
                  <div><dt>Tipo de destino</dt><dd>{persona.tipo_destino}</dd></div>
                </dl>
                <Link
                  className="comisiones-operacion-card-action"
                  to={`/comisiones/solicitudes-viaticos/${persona.id_comision}/${persona.id_personal}/crear`}
                >
                  Crear solicitud de viáticos
                </Link>
              </article>
            ))}
          </section>
        )}
      </section>
    </main>
  )
}

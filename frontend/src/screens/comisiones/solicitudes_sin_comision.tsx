import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { consultarOpcionesComision } from '../../services/comisiones_services'
import type { SolicitudComision } from '../../services/comisiones_services'
import './styles/solicitudes_sin_comision.css'

export default function SolicitudesSinComision() {
  const navigate = useNavigate()
  const [solicitudes, setSolicitudes] = useState<SolicitudComision[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    let active = true
    consultarOpcionesComision()
      .then((opciones) => {
        if (active) setSolicitudes(opciones.solicitudes)
      })
      .catch((loadError: unknown) => {
        if (active) {
          setError(loadError instanceof Error
            ? loadError.message
            : 'No fue posible consultar las solicitudes.')
        }
      })
      .finally(() => {
        if (active) setLoading(false)
      })

    return () => {
      active = false
    }
  }, [])

  return (
    <main className="solicitudes-comision-page">
      <header className="solicitudes-comision-topbar">
        <button className="solicitudes-comision-brand" type="button" onClick={() => navigate('/home')}>
          <span className="solicitudes-comision-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </button>
        <span className="solicitudes-comision-topbar-label">MÓDULO DE COMISIONES</span>
      </header>

      <section className="solicitudes-comision-content">
        <button className="solicitudes-comision-back" type="button" onClick={() => navigate('/comisiones')}>
          ← Volver a comisiones
        </button>
        <div className="solicitudes-comision-heading">
          <span className="solicitudes-comision-eyebrow">COMISIONES · SIGSA</span>
          <h1>Solicitudes sin comisión</h1>
          <p>Selecciona una solicitud para registrar los datos de su comisión.</p>
        </div>

        {loading && <p className="solicitudes-comision-state" role="status">Cargando solicitudes...</p>}
        {error && <p className="solicitudes-comision-state error" role="alert">{error}</p>}
        {!loading && !error && solicitudes.length === 0 && (
          <p className="solicitudes-comision-state">No hay solicitudes pendientes de comisión.</p>
        )}

        {!loading && !error && solicitudes.length > 0 && (
          <section className="solicitudes-comision-list" aria-label="Solicitudes pendientes de comisión">
            {solicitudes.map((solicitud) => (
              <article className="solicitudes-comision-card" key={solicitud.id_solicitud}>
                <div className="solicitudes-comision-card-heading">
                  <span className="solicitudes-comision-card-id">SOLICITUD #{solicitud.id_solicitud}</span>
                  <time dateTime={solicitud.fecha}>{solicitud.fecha}</time>
                </div>
                <h2>{solicitud.institucion_solicita}</h2>
                <dl>
                  <div><dt>Solicita</dt><dd>{solicitud.nombre_solicita}</dd></div>
                  <div><dt>Ubicación</dt><dd>{solicitud.ubicacion}</dd></div>
                  <div><dt>Falla reportada</dt><dd>{solicitud.falla_reportada}</dd></div>
                </dl>
                <button
                  type="button"
                  onClick={() => navigate(`/comisiones/crear/${solicitud.id_solicitud}`)}
                >
                  Comisionar <span aria-hidden="true">→</span>
                </button>
              </article>
            ))}
          </section>
        )}
      </section>
    </main>
  )
}

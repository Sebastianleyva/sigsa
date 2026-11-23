import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { consultarComisionesPendientes } from '../../services/comisiones_services'
import type { ComisionPendiente } from '../../services/comisiones_services'
import './styles/comisiones_operacion.css'

export default function ComisionesPendientes() {
  const navigate = useNavigate()
  const [comisiones, setComisiones] = useState<ComisionPendiente[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    let active = true
    consultarComisionesPendientes()
      .then((resultado) => {
        if (active) setComisiones(resultado)
      })
      .catch((loadError: unknown) => {
        if (active) {
          setError(loadError instanceof Error
            ? loadError.message
            : 'No fue posible consultar las comisiones pendientes.')
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
          <h1>Comisiones pendientes</h1>
          <p>Asigna al personal de tu departamento para iniciar una comisión.</p>
        </div>

        {loading && <p className="comisiones-operacion-state" role="status">Cargando comisiones...</p>}
        {error && <p className="comisiones-operacion-state error" role="alert">{error}</p>}
        {!loading && !error && comisiones.length === 0 && (
          <p className="comisiones-operacion-state">No hay comisiones pendientes para tu área.</p>
        )}

        {!loading && !error && comisiones.length > 0 && (
          <section className="comisiones-operacion-list" aria-label="Comisiones pendientes">
            {comisiones.map((comision) => (
              <article className="comisiones-operacion-card" key={comision.id_comision}>
                <div className="comisiones-operacion-card-header">
                  <span>COMISIÓN #{comision.id_comision}</span>
                  <time dateTime={comision.fecha}>{comision.fecha}</time>
                </div>
                <h2>{comision.institucion_solicita}</h2>
                <dl>
                  <div><dt>Solicita</dt><dd>{comision.nombre_solicita}</dd></div>
                  <div><dt>Lugar</dt><dd>{comision.lugar}</dd></div>
                  <div><dt>Tipo de comisión</dt><dd>{comision.tipo_comision}</dd></div>
                  <div><dt>Tipo de destino</dt><dd>{comision.tipo_destino}</dd></div>
                </dl>
                <button
                  type="button"
                  onClick={() => navigate(`/comisiones/pendientes/${comision.id_comision}/asignar`)}
                >
                  Asignar comisionados <span aria-hidden="true">→</span>
                </button>
              </article>
            ))}
          </section>
        )}
      </section>
    </main>
  )
}

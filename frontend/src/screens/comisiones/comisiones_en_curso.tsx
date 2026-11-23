import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { consultarComisionesEnCurso } from '../../services/comisiones_services'
import type { ComisionEnCurso } from '../../services/comisiones_services'
import './styles/comisiones_operacion.css'

export default function ComisionesEnCurso() {
  const navigate = useNavigate()
  const [comisiones, setComisiones] = useState<ComisionEnCurso[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    let active = true
    consultarComisionesEnCurso()
      .then((resultado) => {
        if (active) setComisiones(resultado)
      })
      .catch((loadError: unknown) => {
        if (active) {
          setError(loadError instanceof Error
            ? loadError.message
            : 'No fue posible consultar las comisiones en curso.')
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
          <h1>Comisiones en curso</h1>
          <p>Consulta el lugar, la fecha de inicio y el personal asignado a cada comisión.</p>
        </div>

        {loading && <p className="comisiones-operacion-state" role="status">Cargando comisiones...</p>}
        {error && <p className="comisiones-operacion-state error" role="alert">{error}</p>}
        {!loading && !error && comisiones.length === 0 && (
          <p className="comisiones-operacion-state">No hay comisiones en curso para tu área.</p>
        )}

        {!loading && !error && comisiones.length > 0 && (
          <section className="comisiones-operacion-list" aria-label="Comisiones en curso">
            {comisiones.map((comision) => (
              <article className="comisiones-operacion-card" key={comision.id_comision}>
                <div className="comisiones-operacion-card-header">
                  <span>COMISIÓN #{comision.id_comision} · {comision.estado}</span>
                  <time dateTime={comision.fecha_inicio ?? ''}>
                    Inicio: {comision.fecha_inicio ?? 'Sin fecha'}
                  </time>
                </div>
                <h2>{comision.institucion_solicita}</h2>
                <dl>
                  <div><dt>Lugar</dt><dd>{comision.lugar}</dd></div>
                  <div><dt>Área</dt><dd>{comision.area_nombre}</dd></div>
                  <div><dt>Tipo de comisión</dt><dd>{comision.tipo_comision}</dd></div>
                  <div><dt>Tipo de destino</dt><dd>{comision.tipo_destino}</dd></div>
                </dl>
                <div className="comisiones-operacion-assignees">
                  <h3>Comisionados</h3>
                  {comision.comisionados.length === 0 ? (
                    <p>No hay personal asociado.</p>
                  ) : (
                    <ul>
                      {comision.comisionados.map((persona) => (
                        <li key={persona.id_personal}>
                          {persona.nombre} {persona.apellido_p} {persona.apellido_m}
                          {persona.puesto === 3 ? ' · Jefe de departamento' : ''}
                        </li>
                      ))}
                    </ul>
                  )}
                </div>
              </article>
            ))}
          </section>
        )}
      </section>
    </main>
  )
}

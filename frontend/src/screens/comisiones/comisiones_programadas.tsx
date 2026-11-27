import { useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import ConfirmationDialog from '../../components/ConfirmationDialog'
import { consultarComisionesProgramadas, iniciarComision } from '../../services/comisiones_services'
import type { ComisionProgramada } from '../../services/comisiones_services'
import './styles/comisiones_operacion.css'

function formatearFecha(fecha: string) {
  const [anio, mes, dia] = fecha.split('-')
  return anio && mes && dia ? `${dia}/${mes}/${anio}` : fecha
}

export default function ComisionesProgramadas() {
  const navigate = useNavigate()
  const [comisiones, setComisiones] = useState<ComisionProgramada[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [comisionAIniciar, setComisionAIniciar] = useState<ComisionProgramada | null>(null)
  const [iniciando, setIniciando] = useState(false)
  const [comisionIniciada, setComisionIniciada] = useState<number | null>(null)

  useEffect(() => {
    let active = true
    consultarComisionesProgramadas()
      .then((resultado) => {
        if (active) setComisiones(resultado)
      })
      .catch((loadError: unknown) => {
        if (active) {
          setError(loadError instanceof Error
            ? loadError.message
            : 'No fue posible consultar las comisiones programadas.')
        }
      })
      .finally(() => {
        if (active) setLoading(false)
      })

    return () => {
      active = false
    }
  }, [])

  async function handleIniciarComision() {
    if (!comisionAIniciar) return
    const idComision = comisionAIniciar.id_comision
    setComisionAIniciar(null)
    setError(null)
    setIniciando(true)
    try {
      await iniciarComision(idComision)
      setComisiones((actuales) => actuales.filter((comision) => comision.id_comision !== idComision))
      setComisionIniciada(idComision)
    } catch (startError) {
      setError(startError instanceof Error
        ? startError.message
        : 'No fue posible iniciar la comisión.')
    } finally {
      setIniciando(false)
    }
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
          <h1>Comisiones programadas</h1>
          <p>
            Comisiones foráneas con todas sus solicitudes de viáticos completas.
            Podrás iniciarlas a partir de su fecha de inicio.
          </p>
        </div>

        {loading && <p className="comisiones-operacion-state" role="status">Cargando comisiones...</p>}
        {error && <p className="comisiones-operacion-state error" role="alert">{error}</p>}
        {comisionIniciada !== null && (
          <div className="comisiones-operacion-state success" role="status">
            La comisión #{comisionIniciada} inició. Su estado cambió a EN CURSO.
            <button type="button" onClick={() => navigate('/comisiones/en-curso')}>
              Ver comisiones en curso <span aria-hidden="true">→</span>
            </button>
          </div>
        )}
        {!loading && comisiones.length === 0 && !error && (
          <p className="comisiones-operacion-state">No hay comisiones programadas.</p>
        )}

        {!loading && comisiones.length > 0 && (
          <section className="comisiones-operacion-list" aria-label="Comisiones programadas">
            {comisiones.map((comision) => (
              <article className="comisiones-operacion-card" key={comision.id_comision}>
                <div className="comisiones-operacion-card-header">
                  <span>COMISIÓN #{comision.id_comision} · {comision.estado}</span>
                  <time dateTime={comision.fecha_inicio}>
                    Del {formatearFecha(comision.fecha_inicio)} al {formatearFecha(comision.fecha_final)}
                  </time>
                </div>
                <h2>{comision.institucion_solicita}</h2>
                <dl>
                  <div><dt>Lugar</dt><dd>{comision.lugar}</dd></div>
                  <div><dt>Contacto</dt><dd>{comision.num_contacto}</dd></div>
                  <div><dt>Área</dt><dd>{comision.area_nombre}</dd></div>
                  <div><dt>Falla reportada</dt><dd>{comision.falla_reportada}</dd></div>
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
                {!comision.puede_iniciar && (
                  <p className="comisiones-operacion-card-hint" id={`inicio-${comision.id_comision}-hint`}>
                    Disponible a partir del {formatearFecha(comision.fecha_inicio)}.
                  </p>
                )}
                <button
                  type="button"
                  disabled={!comision.puede_iniciar || iniciando}
                  aria-describedby={comision.puede_iniciar ? undefined : `inicio-${comision.id_comision}-hint`}
                  onClick={() => setComisionAIniciar(comision)}
                >
                  {iniciando ? 'Iniciando...' : 'Iniciar comisión'}
                </button>
              </article>
            ))}
          </section>
        )}
      </section>

      <ConfirmationDialog
        open={comisionAIniciar !== null}
        title="Iniciar comisión"
        message={
          comisionAIniciar
            ? `¿Confirmas que deseas iniciar la comisión #${comisionAIniciar.id_comision}?`
            : ''
        }
        cancelLabel="Cancelar"
        onCancel={() => setComisionAIniciar(null)}
        actions={[
          {
            label: 'Iniciar comisión',
            variant: 'primary',
            onClick: handleIniciarComision,
          },
        ]}
      />
    </main>
  )
}

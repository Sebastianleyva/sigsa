import { type FormEvent, useCallback, useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { authService } from '../../services/auth_services.js'
import ConfirmationDialog from '../../components/ConfirmationDialog'
import {
  consultarServicios,
  crearServicio,
  desactivarServicio,
  actualizarFechaCalendario,
  type ServicioMantenimiento,
} from '../../services/mantenimiento_services'
import './styles/servicios_mantenimiento.css'

function fechaLocal(fecha: Date) {
  const anio = fecha.getFullYear()
  const mes = String(fecha.getMonth() + 1).padStart(2, '0')
  const dia = String(fecha.getDate()).padStart(2, '0')
  return `${anio}-${mes}-${dia}`
}

export default function ServiciosMantenimiento() {
  const navigate = useNavigate()
  const usuario = authService.getUsuarioActual()
  const esAdministrador = [1, 2, 3].includes(usuario?.puesto ?? 0)
  const fechaMinima = fechaLocal(new Date())
  const fechaMaxima = `${new Date().getFullYear() + 1}-12-31`
  const [servicios, setServicios] = useState<ServicioMantenimiento[]>([])
  const [nombre, setNombre] = useState('')
  const [descripcion, setDescripcion] = useState('')
  const [periodicidad, setPeriodicidad] = useState('')
  const [fechaInicio, setFechaInicio] = useState('')
  const [fechasEditadas, setFechasEditadas] = useState<Record<number, string>>({})
  const [cargando, setCargando] = useState(true)
  const [guardando, setGuardando] = useState(false)
  const [guardandoFecha, setGuardandoFecha] = useState<number | null>(null)
  const [servicioAConfirmar, setServicioAConfirmar] = useState<ServicioMantenimiento | null>(null)
  const [error, setError] = useState('')
  const [mensaje, setMensaje] = useState('')

  const cargarServicios = useCallback(async () => {
    setCargando(true)
    setError('')
    try {
      setServicios(await consultarServicios())
    } catch (errorCarga: unknown) {
      setError(errorCarga instanceof Error ? errorCarga.message : 'No fue posible cargar los servicios.')
    } finally {
      setCargando(false)
    }
  }, [])

  useEffect(() => {
    if (!esAdministrador) return

    let vigente = true
    consultarServicios()
      .then((resultado) => {
        if (vigente) setServicios(resultado)
      })
      .catch((errorCarga: unknown) => {
        if (vigente) {
          setError(errorCarga instanceof Error ? errorCarga.message : 'No fue posible cargar los servicios.')
        }
      })
      .finally(() => {
        if (vigente) setCargando(false)
      })

    return () => {
      vigente = false
    }
  }, [esAdministrador])

  async function manejarCreacion(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError('')
    setMensaje('')
    setGuardando(true)
    try {
      const servicio = await crearServicio({
        nombre: nombre.trim(),
        descripcion: descripcion.trim(),
        periodicidad: Number(periodicidad),
        ...(fechaInicio ? { fecha_inicio: fechaInicio } : {}),
      })
      setServicios((actuales) => [...actuales, servicio].sort((a, b) => a.nombre.localeCompare(b.nombre)))
      setNombre('')
      setDescripcion('')
      setPeriodicidad('')
      setFechaInicio('')
      setMensaje(`Servicio creado con ${servicio.fechas_destinadas.length} fechas programadas.`)
    } catch (errorCreacion: unknown) {
      setError(errorCreacion instanceof Error ? errorCreacion.message : 'No fue posible crear el servicio.')
    } finally {
      setGuardando(false)
    }
  }

  async function manejarDesactivacion(servicio: ServicioMantenimiento) {
    setError('')
    setMensaje('')
    try {
      await desactivarServicio(servicio.id_servicio)
      setServicios((actuales) => actuales.filter((actual) => actual.id_servicio !== servicio.id_servicio))
      setMensaje(`Se desactivó "${servicio.nombre}".`)
    } catch (errorDesactivacion: unknown) {
      setError(errorDesactivacion instanceof Error ? errorDesactivacion.message : 'No fue posible desactivar el servicio.')
    }
  }

  async function manejarCambioFecha(id_calendario: number) {
    const fecha_destinada = fechasEditadas[id_calendario]
    if (!fecha_destinada) return

    setError('')
    setMensaje('')
    setGuardandoFecha(id_calendario)
    try {
      const fechaActualizada = await actualizarFechaCalendario(id_calendario, fecha_destinada)
      setServicios((actuales) => actuales.map((servicio) => ({
        ...servicio,
        fechas_destinadas: servicio.fechas_destinadas.map((fecha) =>
          fecha.id_calendario === id_calendario
            ? { ...fecha, fecha_destinada: fechaActualizada.fecha_destinada }
            : fecha,
        ),
      })))
      setFechasEditadas((actuales) => {
        const siguientes = { ...actuales }
        delete siguientes[id_calendario]
        return siguientes
      })
      setMensaje('Fecha del mantenimiento actualizada.')
    } catch (errorActualizacion: unknown) {
      setError(errorActualizacion instanceof Error ? errorActualizacion.message : 'No fue posible cambiar la fecha.')
    } finally {
      setGuardandoFecha(null)
    }
  }

  if (!esAdministrador) {
    return (
      <main className="servicios-mantenimiento-page">
        <section className="servicios-mantenimiento-content">
          <h1>Acceso restringido</h1>
          <p>Solo los puestos administrativos pueden administrar servicios.</p>
          <button type="button" onClick={() => navigate('/mantenimiento')}>Volver al módulo</button>
        </section>
      </main>
    )
  }

  return (
    <main className="servicios-mantenimiento-page">
      <header className="servicios-mantenimiento-topbar">
        <button type="button" className="servicios-mantenimiento-brand" onClick={() => navigate('/mantenimiento')}>
          <span className="servicios-mantenimiento-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </button>
        <span>ADMINISTRACIÓN DE SERVICIOS</span>
      </header>

      <section className="servicios-mantenimiento-content">
        <button className="servicios-mantenimiento-back" type="button" onClick={() => navigate('/mantenimiento')}>
          ← Volver al módulo
        </button>
        <div className="servicios-mantenimiento-heading">
          <span className="servicios-mantenimiento-eyebrow">ADMINISTRACIÓN · SIGSA</span>
          <h1>Servicios de mantenimiento</h1>
          <p>Crea servicios y desactiva los que ya no deban aparecer para nuevas programaciones. El historial de calendario se conserva.</p>
        </div>

        {mensaje && <p className="servicios-mantenimiento-success" role="status">{mensaje}</p>}
        {error && <p className="servicios-mantenimiento-error" role="alert">{error}</p>}

        <div className="servicios-mantenimiento-layout">
          <section className="servicios-mantenimiento-panel" aria-labelledby="servicio-nuevo-titulo">
            <span className="servicios-mantenimiento-section-index">01 / CATÁLOGO</span>
            <h2 id="servicio-nuevo-titulo">Crear servicio</h2>
            <form onSubmit={manejarCreacion}>
              <label htmlFor="servicio-nombre">Nombre</label>
              <input
                id="servicio-nombre"
                name="nombre"
                value={nombre}
                onChange={(event) => setNombre(event.target.value)}
                maxLength={150}
                required
              />
              <label htmlFor="servicio-periodicidad">Periodicidad (semanas)</label>
              <input
                id="servicio-periodicidad"
                name="periodicidad"
                type="number"
                min="1"
                step="1"
                value={periodicidad}
                onChange={(event) => setPeriodicidad(event.target.value)}
                required
              />
              <label htmlFor="servicio-fecha-inicio">Fecha inicial (opcional)</label>
              <input
                id="servicio-fecha-inicio"
                name="fecha_inicio"
                type="date"
                value={fechaInicio}
                min={fechaMinima}
                max={fechaMaxima}
                onChange={(event) => setFechaInicio(event.target.value)}
              />
              <p className="servicios-mantenimiento-help">
                Si la dejas vacía se usará la fecha de registro. Se programará una fecha cada N semanas hasta fin de año y una sola ocurrencia adicional el año siguiente.
              </p>
              <label htmlFor="servicio-descripcion">Descripción</label>
              <textarea
                id="servicio-descripcion"
                name="descripcion"
                value={descripcion}
                onChange={(event) => setDescripcion(event.target.value)}
                rows={4}
                required
              />
              <button className="servicios-mantenimiento-submit" type="submit" disabled={guardando}>
                {guardando ? 'Guardando…' : 'Crear servicio'}
              </button>
            </form>
          </section>

          <section className="servicios-mantenimiento-panel" aria-labelledby="servicio-activos-titulo">
            <div className="servicios-mantenimiento-list-heading">
              <div>
                <span className="servicios-mantenimiento-section-index">02 / SERVICIOS ACTIVOS</span>
                <h2 id="servicio-activos-titulo">Catálogo actual</h2>
              </div>
              <button type="button" onClick={() => void cargarServicios()} disabled={cargando}>
                Actualizar
              </button>
            </div>
            {cargando && <p className="servicios-mantenimiento-muted" role="status">Cargando servicios…</p>}
            {!cargando && servicios.length === 0 && (
              <p className="servicios-mantenimiento-muted">No hay servicios activos registrados.</p>
            )}
            <div className="servicios-mantenimiento-list">
              {servicios.map((servicio) => (
                <article className="servicios-mantenimiento-item" key={servicio.id_servicio}>
                  <div>
                    <h3>{servicio.nombre}</h3>
                    <p>{servicio.descripcion}</p>
                    <span>Periodicidad: cada {servicio.periodicidad} semanas</span>
                    <div className="servicios-mantenimiento-fechas">
                      <strong>Próximas fechas</strong>
                      {servicio.fechas_destinadas.length === 0 && (
                        <p className="servicios-mantenimiento-muted">No hay fechas pendientes por reprogramar.</p>
                      )}
                      {servicio.fechas_destinadas.map((fecha) => {
                        const fechaActual = fechasEditadas[fecha.id_calendario] ?? fecha.fecha_destinada
                        return (
                          <div className="servicios-mantenimiento-fecha" key={fecha.id_calendario}>
                            <input
                              type="date"
                              value={fechaActual}
                              min={fechaMinima}
                              max={fechaMaxima}
                              aria-label={`Nueva fecha para ${servicio.nombre}, actualmente ${fecha.fecha_destinada}`}
                              onChange={(event) => setFechasEditadas((actuales) => ({
                                ...actuales,
                                [fecha.id_calendario]: event.target.value,
                              }))}
                            />
                            <button
                              type="button"
                              disabled={!fechaActual || fechaActual === fecha.fecha_destinada || guardandoFecha === fecha.id_calendario}
                              onClick={() => void manejarCambioFecha(fecha.id_calendario)}
                            >
                              {guardandoFecha === fecha.id_calendario ? 'Guardando…' : 'Cambiar fecha'}
                            </button>
                          </div>
                        )
                      })}
                    </div>
                  </div>
                  <button
                    className="servicios-mantenimiento-delete"
                    type="button"
                    onClick={() => setServicioAConfirmar(servicio)}
                    aria-label={`Desactivar ${servicio.nombre}`}
                  >
                    Desactivar
                  </button>
                </article>
              ))}
            </div>
          </section>
        </div>
      </section>
      {servicioAConfirmar && (
        <ConfirmationDialog
          open
          title="¿Desactivar este servicio?"
          message={`"${servicioAConfirmar.nombre}" dejará de aparecer para nuevas programaciones. El historial existente se conservará.`}
          cancelLabel="Conservar servicio"
          onCancel={() => setServicioAConfirmar(null)}
          actions={[{
            label: 'Desactivar servicio',
            variant: 'danger',
            onClick: () => {
              const servicio = servicioAConfirmar
              setServicioAConfirmar(null)
              void manejarDesactivacion(servicio)
            },
          }]}
        />
      )}
    </main>
  )
}

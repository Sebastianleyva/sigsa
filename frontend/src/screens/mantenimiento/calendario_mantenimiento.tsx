import { useEffect, useMemo, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import { consultarCalendarioMes, type CalendarioServicio } from '../../services/mantenimiento_services'
import './styles/calendario_mantenimiento.css'

const nombresMes = [
  'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
  'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
]
const diasSemana = ['L', 'M', 'M', 'J', 'V', 'S', 'D']

type MesCalendarioProps = {
  anio: number
  mes: number
  servicios: CalendarioServicio[]
  fechaSeleccionada: string | null
  onSeleccionarFecha: (fecha: string) => void
}

function MesCalendario({ anio, mes, servicios, fechaSeleccionada, onSeleccionarFecha }: MesCalendarioProps) {
  const primerDia = (new Date(anio, mes, 1).getDay() + 6) % 7
  const diasEnMes = new Date(anio, mes + 1, 0).getDate()
  const serviciosPorFecha = new Map<string, CalendarioServicio[]>()

  for (const servicio of servicios) {
    const existentes = serviciosPorFecha.get(servicio.fecha_destinada) ?? []
    existentes.push(servicio)
    serviciosPorFecha.set(servicio.fecha_destinada, existentes)
  }

  return (
    <article className="calendario-mes">
      <header className="calendario-mes-header">
        <h2>{nombresMes[mes]}</h2>
        <span>{servicios.length} {servicios.length === 1 ? 'servicio' : 'servicios'}</span>
      </header>
      <div className="calendario-mes-grid" role="group" aria-label={`${nombresMes[mes]} ${anio}`}>
        {diasSemana.map((dia, index) => (
          <span className="calendario-dia-semana" key={`${dia}-${index}`} aria-hidden="true">{dia}</span>
        ))}
        {Array.from({ length: primerDia }, (_, index) => (
          <span className="calendario-dia-vacio" key={`vacio-${index}`} aria-hidden="true" />
        ))}
        {Array.from({ length: diasEnMes }, (_, index) => {
          const dia = index + 1
          const fecha = `${anio}-${String(mes + 1).padStart(2, '0')}-${String(dia).padStart(2, '0')}`
          const serviciosDia = serviciosPorFecha.get(fecha) ?? []
          const seleccionado = fecha === fechaSeleccionada

          return (
            <button
              className={`calendario-dia${serviciosDia.length > 0 ? ' calendario-dia-con-servicio' : ''}${seleccionado ? ' calendario-dia-seleccionado' : ''}`}
              key={fecha}
              type="button"
              aria-pressed={seleccionado}
              aria-label={serviciosDia.length > 0
                ? `${dia} de ${nombresMes[mes]}, ${serviciosDia.length} servicios de mantenimiento`
                : `${dia} de ${nombresMes[mes]}`}
              onClick={() => onSeleccionarFecha(fecha)}
            >
              <span>{dia}</span>
              {serviciosDia.length > 0 && <span className="calendario-dia-marca">{serviciosDia.length}</span>}
            </button>
          )
        })}
      </div>
    </article>
  )
}

export default function CalendarioMantenimiento() {
  const navigate = useNavigate()
  const [anio, setAnio] = useState(() => new Date().getFullYear())
  const [calendario, setCalendario] = useState<CalendarioServicio[]>([])
  const [fechaSeleccionada, setFechaSeleccionada] = useState<string | null>(null)
  const [cargando, setCargando] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    let vigente = true
    Promise.all(
      Array.from({ length: 12 }, (_, index) => consultarCalendarioMes(anio, index + 1)),
    )
      .then((meses) => {
        if (vigente) setCalendario(meses.flat())
      })
      .catch((errorCarga: unknown) => {
        if (vigente) {
          setError(errorCarga instanceof Error ? errorCarga.message : 'No fue posible consultar el calendario.')
          setCalendario([])
        }
      })
      .finally(() => {
        if (vigente) setCargando(false)
      })

    return () => {
      vigente = false
    }
  }, [anio])

  function cambiarAnio(delta: number) {
    setCargando(true)
    setError('')
    setFechaSeleccionada(null)
    setCalendario([])
    setAnio((valor) => valor + delta)
  }

  const serviciosPorMes = useMemo(
    () => Array.from({ length: 12 }, (_, mes) =>
      calendario.filter((evento) => Number(evento.fecha_destinada.slice(5, 7)) === mes + 1)),
    [calendario],
  )
  const eventosSeleccionados = fechaSeleccionada
    ? calendario.filter((evento) => evento.fecha_destinada === fechaSeleccionada)
    : []

  return (
    <main className="calendario-mantenimiento-page">
      <header className="calendario-mantenimiento-topbar">
        <button type="button" className="calendario-mantenimiento-brand" onClick={() => navigate('/mantenimiento')}>
          <span className="calendario-mantenimiento-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </button>
        <span>CALENDARIO DE MANTENIMIENTO</span>
      </header>

      <section className="calendario-mantenimiento-content">
        <button className="calendario-mantenimiento-back" type="button" onClick={() => navigate('/mantenimiento')}>
          ← Volver al módulo
        </button>
        <div className="calendario-mantenimiento-heading">
          <div>
            <span className="calendario-mantenimiento-eyebrow">PROGRAMACIÓN · SIGSA</span>
            <h1>Calendario anual</h1>
            <p>Selecciona una fecha marcada para ver los servicios programados.</p>
          </div>
          <div className="calendario-anio-control" aria-label="Cambiar año">
            <button type="button" aria-label="Año anterior" onClick={() => cambiarAnio(-1)}>←</button>
            <span>{anio}</span>
            <button type="button" aria-label="Año siguiente" onClick={() => cambiarAnio(1)}>→</button>
          </div>
        </div>

        {cargando && <p className="calendario-mensaje" role="status">Cargando calendario…</p>}
        {!cargando && error && <p className="calendario-error" role="alert">{error}</p>}

        {!cargando && !error && (
          <>
            <div className="calendario-leyenda">
              <span className="calendario-leyenda-marca" aria-hidden="true" />
              <span>Fecha con mantenimiento programado</span>
            </div>
            <div className="calendario-anual-grid">
              {nombresMes.map((_, mes) => (
                <MesCalendario
                  key={`${anio}-${mes}`}
                  anio={anio}
                  mes={mes}
                  servicios={serviciosPorMes[mes]}
                  fechaSeleccionada={fechaSeleccionada}
                  onSeleccionarFecha={setFechaSeleccionada}
                />
              ))}
            </div>

            <section className="calendario-detalle" aria-live="polite">
              <h2>{fechaSeleccionada ? `Mantenimientos del ${fechaSeleccionada}` : 'Detalle del día'}</h2>
              {!fechaSeleccionada && <p>Selecciona un día del calendario para consultar sus servicios.</p>}
              {fechaSeleccionada && eventosSeleccionados.length === 0 && <p>No hay mantenimientos programados para esta fecha.</p>}
              {eventosSeleccionados.map((evento) => (
                <article className="calendario-evento" key={evento.id_calendario}>
                  <div>
                    <h3>{evento.servicio}</h3>
                    <p>{evento.descripcion_servicio}</p>
                  </div>
                  <span>{evento.estado}</span>
                </article>
              ))}
            </section>
          </>
        )}
      </section>
    </main>
  )
}

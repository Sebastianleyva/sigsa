import axios from 'axios'
import api from '../api/axios.js'

export type CalendarioServicio = {
  id_calendario: number
  fecha_destinada: string
  estado: string
  id_servicio: number
  servicio: string
  descripcion_servicio: string
  periodicidad: number
}

export type ServicioMantenimiento = {
  id_servicio: number
  nombre: string
  periodicidad: number
  descripcion: string
  activo: number
  fechas_destinadas: FechaServicio[]
}

export type FechaServicio = {
  id_calendario: number
  fecha_destinada: string
  estado: string
}

type RespuestaCalendario = { data: CalendarioServicio[] }
type RespuestaServicios = { data: ServicioMantenimiento[] }
export type IncidenciaMantenimiento = {
  id_incidencia: number
  id_personal: number
  id_autoriza: number | null
  reportante: string
  descripcion: string
  fecha: string
  urgencia: string
  estado: string
  lugar: string
}
type DatosIncidencia = {
  lugar: string
  urgencia: string
  descripcion: string
}
type IncidenciaCreada = DatosIncidencia & {
  id: number | string
  id_personal: number | string
  fecha: string
  estado: string
}
type RespuestaCreacionServicio = {
  servicio: Omit<ServicioMantenimiento, 'fechas_destinadas'> & { fechas_generadas: FechaServicio[] }
}

function obtenerError(error: unknown, mensaje: string): Error {
  if (axios.isAxiosError<{ ERROR?: string }>(error)) {
    return new Error(error.response?.data?.ERROR || mensaje)
  }
  return error instanceof Error ? error : new Error(mensaje)
}

export async function consultarCalendarioMes(anio: number, mes: number) {
  try {
    const response = await api.get<RespuestaCalendario>('mantenimiento/routes/ver_calendario.php', {
      params: { anio, mes },
    })
    return response.data.data
  } catch (error) {
    throw obtenerError(error, 'No fue posible consultar el calendario.')
  }
}

export async function crearIncidencia(datos: DatosIncidencia): Promise<IncidenciaCreada> {
  try {
    const response = await api.post<{ incidencia: IncidenciaCreada }>(
      'mantenimiento/routes/crear_incidencia.php',
      datos,
    )
    if (!response.data?.incidencia) {
      throw new Error('El servidor devolvió una respuesta inválida al registrar la incidencia.')
    }
    return response.data.incidencia
  } catch (error) {
    throw obtenerError(error, 'No fue posible registrar la incidencia.')
  }
}

export async function consultarIncidencias(): Promise<IncidenciaMantenimiento[]> {
  try {
    const response = await api.get<{ data: IncidenciaMantenimiento[] }>(
      'mantenimiento/routes/ver_incidencias.php',
    )
    if (!Array.isArray(response.data?.data)) {
      throw new Error('El servidor devolvió una respuesta inválida al consultar incidencias.')
    }
    return response.data.data.map((incidencia) => ({
      ...incidencia,
      id_incidencia: Number(incidencia.id_incidencia),
      id_personal: Number(incidencia.id_personal),
      id_autoriza: incidencia.id_autoriza === null ? null : Number(incidencia.id_autoriza),
    }))
  } catch (error) {
    throw obtenerError(error, 'No fue posible consultar las incidencias.')
  }
}

async function procesarIncidencia(id_incidencia: number, decision: 'aceptar' | 'rechazar') {
  try {
    const response = await api.patch<{ incidencia: IncidenciaMantenimiento }>(
      `mantenimiento/routes/${decision}_incidencia.php`,
      { id_incidencia },
    )
    if (!response.data?.incidencia) {
      throw new Error('El servidor devolvió una respuesta inválida al procesar la incidencia.')
    }
    return response.data.incidencia
  } catch (error) {
    const accion = decision === 'aceptar' ? 'aceptar' : 'rechazar'
    throw obtenerError(error, `No fue posible ${accion} la incidencia.`)
  }
}

export function aceptarIncidencia(id_incidencia: number) {
  return procesarIncidencia(id_incidencia, 'aceptar')
}

export function rechazarIncidencia(id_incidencia: number) {
  return procesarIncidencia(id_incidencia, 'rechazar')
}

export async function consultarServicios() {
  try {
    const response = await api.get<RespuestaServicios>('mantenimiento/routes/ver_servicios.php')
    if (!Array.isArray(response.data?.data)) {
      throw new Error('El servidor devolvió una respuesta inválida al consultar servicios.')
    }
    return response.data.data.map((servicio) => ({
      ...servicio,
      id_servicio: Number(servicio.id_servicio),
      periodicidad: Number(servicio.periodicidad),
      activo: Number(servicio.activo),
      fechas_destinadas: servicio.fechas_destinadas.map((fecha) => ({
        ...fecha,
        id_calendario: Number(fecha.id_calendario),
      })),
    }))
  } catch (error) {
    throw obtenerError(error, 'No fue posible cargar los servicios.')
  }
}

export async function crearServicio(datos: Pick<ServicioMantenimiento, 'nombre' | 'periodicidad' | 'descripcion'> & { fecha_inicio?: string }) {
  try {
    const response = await api.post<RespuestaCreacionServicio>('mantenimiento/routes/crear_servicio.php', datos)
    return {
      ...response.data.servicio,
      id_servicio: Number(response.data.servicio.id_servicio),
      periodicidad: Number(response.data.servicio.periodicidad),
      activo: Number(response.data.servicio.activo),
      fechas_destinadas: response.data.servicio.fechas_generadas.map((fecha) => ({
        ...fecha,
        id_calendario: Number(fecha.id_calendario),
      })),
    }
  } catch (error) {
    throw obtenerError(error, 'No fue posible crear el servicio.')
  }
}

export async function actualizarFechaCalendario(id_calendario: number, fecha_destinada: string) {
  try {
    const response = await api.patch<{ calendario: FechaServicio }>(
      'mantenimiento/routes/actualizar_fecha_calendario.php',
      { id_calendario, fecha_destinada },
    )
    return response.data.calendario
  } catch (error) {
    throw obtenerError(error, 'No fue posible cambiar la fecha del mantenimiento.')
  }
}

export async function desactivarServicio(id_servicio: number) {
  try {
    await api.delete('mantenimiento/routes/eliminar_servicio.php', {
      data: { id_servicio },
    })
  } catch (error) {
    throw obtenerError(error, 'No fue posible eliminar el servicio.')
  }
}

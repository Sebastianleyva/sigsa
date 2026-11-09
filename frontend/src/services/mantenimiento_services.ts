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
}

type RespuestaCalendario = { data: CalendarioServicio[] }
type RespuestaServicios = { data: ServicioMantenimiento[] }
type RespuestaCreacionServicio = { servicio: ServicioMantenimiento }

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
    }))
  } catch (error) {
    throw obtenerError(error, 'No fue posible cargar los servicios.')
  }
}

export async function crearServicio(datos: Pick<ServicioMantenimiento, 'nombre' | 'periodicidad' | 'descripcion'>) {
  try {
    const response = await api.post<RespuestaCreacionServicio>('mantenimiento/routes/crear_servicio.php', datos)
    return {
      ...response.data.servicio,
      id_servicio: Number(response.data.servicio.id_servicio),
      periodicidad: Number(response.data.servicio.periodicidad),
      activo: Number(response.data.servicio.activo),
    }
  } catch (error) {
    throw obtenerError(error, 'No fue posible crear el servicio.')
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

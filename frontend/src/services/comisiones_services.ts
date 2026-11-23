import axios from 'axios'
import api from '../api/axios.js'

export type SolicitudComisionData = {
  institucion_solicita: string
  ubicacion: string
  tipo_destino: string
  nombre_solicita: string
}

export type SolicitudComision = SolicitudComisionData & {
  id_solicitud: number | string
  fecha: string
  id_personal?: number | string
}

export type ComisionData = {
  id_solicitud: number
  id_area: number
  tipo_comision: string
  tipo_destino: string
}

export type Comision = ComisionData & {
  id_comision: number | string
  fecha: string
  estado: string
}

export type AreaComision = {
  id_area: number
  nombre: string
}

export type OpcionesComision = {
  solicitudes: SolicitudComision[]
  areas: AreaComision[]
}

function obtenerError(error: unknown, mensaje: string): Error {
  if (axios.isAxiosError<{ ERROR?: string; error?: string }>(error)) {
    return new Error(error.response?.data?.ERROR || error.response?.data?.error || mensaje)
  }
  return error instanceof Error ? error : new Error(mensaje)
}

export async function crearSolicitudComision(datos: SolicitudComisionData): Promise<SolicitudComision> {
  try {
    const response = await api.post<{ solicitud: SolicitudComision }>(
      'comisiones/routes/crear_solicitud.php',
      datos,
    )
    if (!response.data?.solicitud) {
      throw new Error('El servidor devolvió una respuesta inválida al crear la solicitud.')
    }
    return response.data.solicitud
  } catch (error) {
    throw obtenerError(error, 'No fue posible crear la solicitud.')
  }
}

export async function crearComision(datos: ComisionData): Promise<Comision> {
  try {
    const response = await api.post<{ comision: Comision }>(
      'comisiones/routes/crear_comision.php',
      datos,
    )
    if (!response.data?.comision) {
      throw new Error('El servidor devolvió una respuesta inválida al crear la comisión.')
    }
    return response.data.comision
  } catch (error) {
    throw obtenerError(error, 'No fue posible crear la comisión.')
  }
}

export async function consultarOpcionesComision(): Promise<OpcionesComision> {
  try {
    const response = await api.get<OpcionesComision>(
      'comisiones/routes/ver_solicitudes_sin_comision.php',
    )
    if (!Array.isArray(response.data?.solicitudes) || !Array.isArray(response.data?.areas)) {
      throw new Error('El servidor devolvió una respuesta inválida al consultar las opciones de comisión.')
    }
    return {
      solicitudes: response.data.solicitudes.map((solicitud) => ({
        ...solicitud,
        id_solicitud: Number(solicitud.id_solicitud),
      })),
      areas: response.data.areas.map((area) => ({
        ...area,
        id_area: Number(area.id_area),
      })),
    }
  } catch (error) {
    throw obtenerError(error, 'No fue posible consultar las solicitudes pendientes de comisión.')
  }
}

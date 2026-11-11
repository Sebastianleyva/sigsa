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
export type ReporteMantenimiento = {
  id_reporte: number
  tipo: string
  id_incidencia: number | null
  id_calendario: number | null
  fecha_inicio: string
  fecha_final: string | null
  estado: string
  descripcion: string
  lugar: string | null
  urgencia: string | null
  fecha_destinada: string | null
  servicio: string | null
  reportante: string | null
}
export type ReporteRequisicion = ReporteMantenimiento & {
  id_requisicion: number | null
  estado_requisicion: string | null
}
export type RequisicionMantenimiento = {
  id_requisicion: number | string
  id_reporte: number
  justificacion: string
  fecha: string
  descripcion_servicio: string
  estado: string
  tipo_financiamiento: string
}
type ReporteIncidenciaCreado = {
  id_reporte: number | string
  estado: string
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

export async function consultarReportesEnCurso(): Promise<ReporteMantenimiento[]> {
  try {
    const response = await api.get<{ data: ReporteMantenimiento[] }>(
      'mantenimiento/routes/ver_reportes_en_curso.php',
    )
    if (!Array.isArray(response.data?.data)) {
      throw new Error('El servidor devolvió una respuesta inválida al consultar reportes en curso.')
    }
    return response.data.data.map((reporte) => ({
      ...reporte,
      id_reporte: Number(reporte.id_reporte),
      id_incidencia: reporte.id_incidencia === null ? null : Number(reporte.id_incidencia),
      id_calendario: reporte.id_calendario === null ? null : Number(reporte.id_calendario),
    }))
  } catch (error) {
    throw obtenerError(error, 'No fue posible consultar los mantenimientos en curso.')
  }
}

export async function consultarReportesRequisicion(): Promise<ReporteRequisicion[]> {
  try {
    const response = await api.get<{ data: ReporteRequisicion[] }>(
      'mantenimiento/routes/ver_reportes_requisicion.php',
    )
    if (!Array.isArray(response.data?.data)) {
      throw new Error('El servidor devolvió una respuesta inválida al consultar requisiciones.')
    }
    return response.data.data.map((reporte) => ({
      ...reporte,
      id_reporte: Number(reporte.id_reporte),
      id_incidencia: reporte.id_incidencia === null ? null : Number(reporte.id_incidencia),
      id_calendario: reporte.id_calendario === null ? null : Number(reporte.id_calendario),
      id_requisicion: reporte.id_requisicion === null ? null : Number(reporte.id_requisicion),
    }))
  } catch (error) {
    throw obtenerError(error, 'No fue posible consultar las requisiciones.')
  }
}

export async function crearRequisicion(
  datos: Pick<RequisicionMantenimiento, 'id_reporte' | 'justificacion' | 'fecha' | 'descripcion_servicio' | 'tipo_financiamiento'>,
): Promise<RequisicionMantenimiento> {
  try {
    const response = await api.post<{ requisicion: RequisicionMantenimiento }>(
      'mantenimiento/routes/generar_requisicion.php',
      datos,
    )
    if (!response.data?.requisicion) {
      throw new Error('El servidor devolvió una respuesta inválida al crear la requisición.')
    }
    return response.data.requisicion
  } catch (error) {
    throw obtenerError(error, 'No fue posible crear la requisición.')
  }
}

async function procesarIncidencia(
  id_incidencia: number,
  decision: 'aceptar' | 'rechazar',
  requiere_requisicion?: boolean,
) {
  try {
    const response = await api.patch<{
      incidencia: IncidenciaMantenimiento
      reporte?: ReporteIncidenciaCreado
    }>(
      `mantenimiento/routes/${decision}_incidencia.php`,
      decision === 'aceptar'
        ? { id_incidencia, requiere_requisicion }
        : { id_incidencia },
    )
    if (!response.data?.incidencia || (decision === 'aceptar' && !response.data.reporte)) {
      throw new Error('El servidor devolvió una respuesta inválida al procesar la incidencia.')
    }
    return response.data
  } catch (error) {
    const accion = decision === 'aceptar' ? 'aceptar' : 'rechazar'
    throw obtenerError(error, `No fue posible ${accion} la incidencia.`)
  }
}

export async function aceptarIncidencia(id_incidencia: number, requiere_requisicion: boolean) {
  const resultado = await procesarIncidencia(id_incidencia, 'aceptar', requiere_requisicion)
  if (!resultado.reporte) {
    throw new Error('El servidor no devolvió el reporte creado para la incidencia.')
  }
  return { incidencia: resultado.incidencia, reporte: resultado.reporte }
}

export async function rechazarIncidencia(id_incidencia: number) {
  const resultado = await procesarIncidencia(id_incidencia, 'rechazar')
  return resultado.incidencia
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

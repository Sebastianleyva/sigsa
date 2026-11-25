import axios from 'axios'
import api from '../api/axios.js'

export type SolicitudComisionData = {
  institucion_solicita: string
  ubicacion: string
  nombre_solicita: string
  falla_reportada: string
  num_contacto: string
  fecha_comision: string | null
}

export type SolicitudComision = SolicitudComisionData & {
  id_solicitud: number | string
  fecha_registro: string
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

export type PersonalComision = {
  id_personal: number
  nombre: string
  apellido_p: string
  apellido_m: string
  puesto: number
}

export type ComisionPendiente = {
  id_comision: number
  id_solicitud: number
  fecha: string
  tipo_comision: 'URBANA' | 'FORANEA'
  tipo_destino: string
  id_area: number
  estado: string
  lugar: string
  institucion_solicita: string
  nombre_solicita: string
  falla_reportada: string
  num_contacto: string
  fecha_comision: string | null
}

export type OpcionesAsignacionComision = {
  comision: ComisionPendiente & { area_nombre: string }
  personal: PersonalComision[]
}

export type ResultadoAsignacionComision =
  | {
    id_comision: number
    estado: 'EN CURSO'
    fecha_inicio: string
    ids_personal: number[]
  }
  | {
    id_comision: number
    estado: 'PENDIENTE DE SOLICITUDES_V'
    fecha_inicio: null
    ids_personal: number[]
  }

export type ComisionEnCurso = ComisionPendiente & {
  fecha_inicio: string | null
  area_nombre: string
  comisionados: PersonalComision[]
  puede_terminar: boolean
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

export async function consultarComisionesPendientes(): Promise<ComisionPendiente[]> {
  try {
    const response = await api.get<{ comisiones: ComisionPendiente[] }>(
      'comisiones/routes/ver_comisiones_pendientes.php',
    )
    if (!Array.isArray(response.data?.comisiones)) {
      throw new Error('El servidor devolvió una respuesta inválida al consultar las comisiones pendientes.')
    }
    return response.data.comisiones.map((comision) => ({
      ...comision,
      id_comision: Number(comision.id_comision),
      id_solicitud: Number(comision.id_solicitud),
      id_area: Number(comision.id_area),
    }))
  } catch (error) {
    throw obtenerError(error, 'No fue posible consultar las comisiones pendientes.')
  }
}

export async function consultarOpcionesAsignacionComision(
  id_comision: number,
): Promise<OpcionesAsignacionComision> {
  try {
    const response = await api.get<OpcionesAsignacionComision>(
      'comisiones/routes/ver_personal_comision.php',
      { params: { id_comision } },
    )
    if (!response.data?.comision || !Array.isArray(response.data?.personal)) {
      throw new Error('El servidor devolvió una respuesta inválida al consultar el personal asignable.')
    }
    return {
      comision: {
        ...response.data.comision,
        id_comision: Number(response.data.comision.id_comision),
        id_solicitud: Number(response.data.comision.id_solicitud),
        id_area: Number(response.data.comision.id_area),
      },
      personal: response.data.personal.map((persona) => ({
        ...persona,
        id_personal: Number(persona.id_personal),
        puesto: Number(persona.puesto),
      })),
    }
  } catch (error) {
    throw obtenerError(error, 'No fue posible consultar el personal para esta comisión.')
  }
}

export async function asignarComisionados(
  id_comision: number,
  ids_personal: number[],
): Promise<ResultadoAsignacionComision> {
  try {
    const response = await api.post<{
      comision: ResultadoAsignacionComision
    }>(
      'comisiones/routes/asignar_comision.php',
      { id_comision, ids_personal },
    )
    const resultado = response.data?.comision
    if (
      !resultado
      || !Array.isArray(resultado.ids_personal)
      || (resultado.estado === 'EN CURSO' && typeof resultado.fecha_inicio !== 'string')
      || (resultado.estado === 'PENDIENTE DE SOLICITUDES_V' && resultado.fecha_inicio !== null)
      || !['EN CURSO', 'PENDIENTE DE SOLICITUDES_V'].includes(resultado.estado)
    ) {
      throw new Error('El servidor devolvió una respuesta inválida al asignar la comisión.')
    }
    return resultado
  } catch (error) {
    throw obtenerError(error, 'No fue posible asignar al personal.')
  }
}

export async function consultarComisionesEnCurso(): Promise<ComisionEnCurso[]> {
  try {
    const response = await api.get<{ comisiones: ComisionEnCurso[] }>(
      'comisiones/routes/ver_comisiones_en_curso.php',
    )
    if (!Array.isArray(response.data?.comisiones)) {
      throw new Error('El servidor devolvió una respuesta inválida al consultar las comisiones en curso.')
    }
    return response.data.comisiones.map((comision) => ({
      ...comision,
      id_comision: Number(comision.id_comision),
      id_solicitud: Number(comision.id_solicitud),
      id_area: Number(comision.id_area),
      comisionados: (comision.comisionados ?? []).map((persona) => ({
        ...persona,
        id_personal: Number(persona.id_personal),
        puesto: Number(persona.puesto),
      })),
    }))
  } catch (error) {
    throw obtenerError(error, 'No fue posible consultar las comisiones en curso.')
  }
}

export type SolicitudSoporteData = {
  id_comision: number
  direccion: string
  departamento: string
  nombre_reporta: string
  cisco: string
  fecha: string
  hora: string
  tecnico_asignado: number
  solicitud_falla: string
  equipos_atendidos: number
  usuarios_beneficiados: number
}

export async function crearSolicitudSoporte(datos: SolicitudSoporteData) {
  try {
    const response = await api.post<{ solicitud: Record<string, unknown> }>(
      'comisiones/routes/crear_solicitud_soporte.php',
      datos,
    )
    if (!response.data?.solicitud) {
      throw new Error('El servidor devolvió una respuesta inválida al registrar la solicitud de soporte.')
    }
    return response.data.solicitud
  } catch (error) {
    throw obtenerError(error, 'No fue posible registrar la solicitud de soporte.')
  }
}

export async function obtenerPdfSolicitudSoporte(id_comision: number) {
  try {
    const response = await api.get<Blob>(
      `comisiones/routes/generar_pdf_solicitud_soporte.php?id=${id_comision}`,
      { responseType: 'blob' },
    )
    return URL.createObjectURL(response.data)
  } catch (error) {
    throw obtenerError(error, 'No fue posible generar el documento de soporte.')
  }
}

export type OrdenServicioData = {
  id_comision: number
  fecha: string
  n_reporte: number
  responsable_institucion: string
  nombre_institucion: string
  clave_institucion: string
  domicilio_institucion: string
  localidad_institucion: string
  municipio_institucion: string
  tel_institucion: string
  falla_reportada: string
  actividad_realizada: string
  observaciones: string
  cpu: number | null
  proyectores: number | null
  impresoras: number | null
  alumnos_benefi: number | null
  donacion: number | null
}

export async function crearOrdenServicio(datos: OrdenServicioData) {
  try {
    const response = await api.post<{ orden: { id_orden: number | string } }>(
      'comisiones/routes/crear_orden_servicio.php',
      datos,
    )
    const idOrden = Number(response.data?.orden?.id_orden)
    if (!Number.isInteger(idOrden) || idOrden < 1) {
      throw new Error('El servidor devolvió una respuesta inválida al registrar la orden de servicio.')
    }
    return { ...response.data.orden, id_orden: idOrden }
  } catch (error) {
    throw obtenerError(error, 'No fue posible registrar la orden de servicio.')
  }
}

export async function obtenerPdfOrdenServicio(id_orden: number) {
  try {
    const response = await api.get<Blob>(
      `comisiones/routes/generar_pdf_orden_servicio.php?id=${id_orden}`,
      { responseType: 'blob' },
    )
    return URL.createObjectURL(response.data)
  } catch (error) {
    throw obtenerError(error, 'No fue posible generar el documento de la orden de servicio.')
  }
}


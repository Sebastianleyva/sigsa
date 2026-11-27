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
  tipo_comision: 'URBANA' | 'FORANEA'
  tipo_destino: string
  fecha_inicio: string | null
  fecha_final: string | null
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

export type PersonalAsignable = PersonalComision & {
  disponible: boolean
  motivo: string | null
}

export type OpcionesAsignacionComision = {
  comision: ComisionPendiente & {
    area_nombre: string
    fecha_inicio: string | null
    fecha_final: string | null
  }
  personal: PersonalAsignable[]
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
    fecha_inicio: string | null
    ids_personal: number[]
  }

export type ComisionEnCurso = ComisionPendiente & {
  fecha_inicio: string | null
  area_nombre: string
  comisionados: PersonalComision[]
  puede_terminar: boolean
}

export type ComisionProgramada = ComisionPendiente & {
  fecha_inicio: string
  fecha_final: string
  area_nombre: string
  comisionados: PersonalComision[]
  puede_iniciar: boolean
}

export type ResultadoInicioComision = {
  id_comision: number
  estado: 'EN CURSO'
  fecha_inicio: string
  ids_personal: number[]
}

export type PersonaPendienteSolicitudViaticos = {
  id_comision: number
  tipo_comision: string
  tipo_destino: string
  estado: string
  institucion_solicita: string
  lugar: string
  fecha_comision: string | null
  id_personal: number
  nombre: string
  apellido_p: string
  apellido_m: string
  puesto_nombre: string
}

export type SolicitudViaticosResumen = {
  id_solicitud_viaticos: number
  id_comision: number
  fecha: string
  fecha_comision: string | null
  comisionado: string
  lugar: string
  estado_comision: string
  total_importe: number
}

export type ComisionadoSolicitudViaticos = {
  id_comision: number
  id_personal: number
  id_puesto: number
  nombre_completo: string
  curp: string
  rfc: string
  correo: string
  telefono: string
  cisco: string
  periodo: string | null
  lugar: string
  motivo: string
  registrante: {
    nombre: string
    correo: string
    cisco: string
  }
  opciones: OpcionesSolicitudViaticos
}

export type RenglonSolicitudViaticosData = {
  id_codigo_contable_comision: number
  num_dias: number | null
  importe: number
}

export type SolicitudViaticosData = {
  id_comision: number
  id_personal_comisionado: number
  id_zona: number
  tipo_comprobacion: 'FACTURA' | 'CONSTANCIA'
  puesto: string
  contacto_emergencia_nombre: string
  contacto_emergencia_numero: string
  programa: string
  clave_cobro: string
  observaciones: string
  renglones: RenglonSolicitudViaticosData[]
}

export type ResultadoSolicitudViaticos = {
  id_solicitud_viaticos: number
  id_comision: number
  id_personal_comisionado: number
  estado_comision: 'PENDIENTE DE SOLICITUDES_V' | 'PROGRAMADA'
  personal_pendiente: number
  mensaje: string
}

export type OpcionesSolicitudViaticos = {
  codigos_contables: {
    id_codigo_contable_comision: number
    codigo_contable: string
    descripcion: string
  }[]
  zonas: {
    id_zona: number
    nombre: string
    permite_constancia: number | string
  }[]
  tabulador: {
    id_zona: number
    tipo_comprobacion: 'FACTURA' | 'CONSTANCIA'
    pernocta: number | string
    monto_diario: number | string
  }[]
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
        disponible: persona.disponible === true,
        motivo: persona.motivo ?? null,
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
      || (resultado.estado === 'PENDIENTE DE SOLICITUDES_V'
        && resultado.fecha_inicio !== null
        && typeof resultado.fecha_inicio !== 'string')
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

export async function consultarComisionesProgramadas(): Promise<ComisionProgramada[]> {
  try {
    const response = await api.get<{ comisiones: ComisionProgramada[] }>(
      'comisiones/routes/ver_comisiones_programadas.php',
    )
    if (!Array.isArray(response.data?.comisiones)) {
      throw new Error('El servidor devolvió una respuesta inválida al consultar las comisiones programadas.')
    }
    return response.data.comisiones.map((comision) => ({
      ...comision,
      id_comision: Number(comision.id_comision),
      id_solicitud: Number(comision.id_solicitud),
      id_area: Number(comision.id_area),
      puede_iniciar: comision.puede_iniciar === true,
      comisionados: (comision.comisionados ?? []).map((persona) => ({
        ...persona,
        id_personal: Number(persona.id_personal),
        puesto: Number(persona.puesto),
      })),
    }))
  } catch (error) {
    throw obtenerError(error, 'No fue posible consultar las comisiones programadas.')
  }
}

export async function iniciarComision(id_comision: number): Promise<ResultadoInicioComision> {
  try {
    const response = await api.post<{ comision: ResultadoInicioComision }>(
      'comisiones/routes/iniciar_comision.php',
      { id_comision },
    )
    const resultado = response.data?.comision
    if (!resultado || resultado.estado !== 'EN CURSO' || typeof resultado.fecha_inicio !== 'string') {
      throw new Error('El servidor devolvió una respuesta inválida al iniciar la comisión.')
    }
    return { ...resultado, id_comision: Number(resultado.id_comision) }
  } catch (error) {
    throw obtenerError(error, 'No fue posible iniciar la comisión.')
  }
}

export type ComisionParticipante = ComisionPendiente & {
  fecha_inicio: string | null
  fecha_final: string | null
  area_nombre: string
  comisionados: PersonalComision[]
}

export async function consultarComisionesParticipante(): Promise<ComisionParticipante[]> {
  try {
    const response = await api.get<{ comisiones: ComisionParticipante[] }>(
      'comisiones/routes/ver_comisiones_participante.php',
    )
    if (!Array.isArray(response.data?.comisiones)) {
      throw new Error('El servidor devolvió una respuesta inválida al consultar tus comisiones.')
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
    throw obtenerError(error, 'No fue posible consultar tus comisiones.')
  }
}

export async function consultarPersonalPendienteSolicitudViaticos(): Promise<PersonaPendienteSolicitudViaticos[]> {
  try {
    const response = await api.get<{ personal: PersonaPendienteSolicitudViaticos[] }>(
      'comisiones/routes/ver_personal_solicitudes_viaticos.php',
    )
    if (!Array.isArray(response.data?.personal)) {
      throw new Error('El servidor devolvió una respuesta inválida al consultar el personal pendiente de viáticos.')
    }
    return response.data.personal.map((persona) => ({
      ...persona,
      id_comision: Number(persona.id_comision),
      id_personal: Number(persona.id_personal),
    }))
  } catch (error) {
    throw obtenerError(error, 'No fue posible consultar el personal pendiente de solicitudes de viáticos.')
  }
}

export async function consultarSolicitudesViaticosMes(
  mes: number,
  anio: number,
): Promise<SolicitudViaticosResumen[]> {
  try {
    const response = await api.get<{ solicitudes: SolicitudViaticosResumen[] }>(
      'comisiones/routes/ver_solicitudes_viaticos_mes.php',
      { params: { mes, anio } },
    )
    if (!Array.isArray(response.data?.solicitudes)) {
      throw new Error('El servidor devolvió una respuesta inválida al consultar las solicitudes de viáticos.')
    }
    return response.data.solicitudes.map((solicitud) => ({
      ...solicitud,
      id_solicitud_viaticos: Number(solicitud.id_solicitud_viaticos),
      id_comision: Number(solicitud.id_comision),
      total_importe: Number(solicitud.total_importe),
    }))
  } catch (error) {
    throw obtenerError(error, 'No fue posible consultar las solicitudes de viáticos.')
  }
}

export async function consultarComisionadoSolicitudViaticos(
  id_comision: number,
  id_personal: number,
): Promise<ComisionadoSolicitudViaticos> {
  try {
    const response = await api.get<{
      comisionado: Omit<ComisionadoSolicitudViaticos, 'registrante' | 'periodo'> & {
        fecha_comision: string | null
      }
      registrante: ComisionadoSolicitudViaticos['registrante']
      opciones: OpcionesSolicitudViaticos
    }>(
      'comisiones/routes/ver_comisionado_solicitud_viaticos.php',
      { params: { id_comision, id_personal } },
    )
    if (
      !response.data?.comisionado
      || !response.data?.registrante
      || !Array.isArray(response.data?.opciones?.codigos_contables)
      || !Array.isArray(response.data?.opciones?.zonas)
      || !Array.isArray(response.data?.opciones?.tabulador)
    ) {
      throw new Error('El servidor devolvió una respuesta inválida al consultar los datos del comisionado.')
    }
    const { fecha_comision: periodo, ...datosComisionado } = response.data.comisionado
    return {
      ...datosComisionado,
      id_comision: Number(response.data.comisionado.id_comision),
      id_personal: Number(response.data.comisionado.id_personal),
      id_puesto: Number(response.data.comisionado.id_puesto),
      periodo,
      registrante: response.data.registrante,
      opciones: {
        codigos_contables: response.data.opciones.codigos_contables.map((codigo) => ({
          ...codigo,
          id_codigo_contable_comision: Number(codigo.id_codigo_contable_comision),
        })),
        zonas: response.data.opciones.zonas.map((zona) => ({
          ...zona,
          id_zona: Number(zona.id_zona),
          permite_constancia: Number(zona.permite_constancia),
        })),
        tabulador: response.data.opciones.tabulador.map((tarifa) => ({
          ...tarifa,
          id_zona: Number(tarifa.id_zona),
          pernocta: Number(tarifa.pernocta),
          monto_diario: Number(tarifa.monto_diario),
        })),
      },
    }
  } catch (error) {
    throw obtenerError(error, 'No fue posible consultar los datos del comisionado.')
  }
}

export async function crearSolicitudViaticos(
  datos: SolicitudViaticosData,
): Promise<ResultadoSolicitudViaticos> {
  try {
    const response = await api.post<{
      mensaje: string
      solicitud: Omit<ResultadoSolicitudViaticos, 'mensaje'>
    }>(
      'comisiones/routes/crear_solicitud_viaticos.php',
      datos,
    )
    const solicitud = response.data?.solicitud
    const idSolicitud = Number(solicitud?.id_solicitud_viaticos)
    const idComision = Number(solicitud?.id_comision)
    const idPersonal = Number(solicitud?.id_personal_comisionado)
    const personalPendiente = Number(solicitud?.personal_pendiente)
    if (
      !solicitud
      || !Number.isInteger(idSolicitud)
      || idSolicitud < 1
      || !Number.isInteger(idComision)
      || idComision < 1
      || !Number.isInteger(idPersonal)
      || idPersonal < 1
      || !['PENDIENTE DE SOLICITUDES_V', 'PROGRAMADA'].includes(solicitud.estado_comision)
      || !Number.isInteger(personalPendiente)
      || personalPendiente < 0
      || typeof response.data.mensaje !== 'string'
    ) {
      throw new Error('El servidor devolvió una respuesta inválida al guardar la solicitud de viáticos.')
    }
    return {
      ...solicitud,
      id_solicitud_viaticos: idSolicitud,
      id_comision: idComision,
      id_personal_comisionado: idPersonal,
      personal_pendiente: personalPendiente,
      mensaje: response.data.mensaje,
    }
  } catch (error) {
    throw obtenerError(error, 'No fue posible guardar la solicitud de viáticos.')
  }
}

export async function obtenerPdfSolicitudViaticos(id_solicitud_viaticos: number): Promise<string> {
  try {
    const response = await api.get<Blob>(
      `comisiones/routes/generar_pdf_solicitud_viaticos.php?id=${id_solicitud_viaticos}`,
      { responseType: 'blob' },
    )
    if (!response.data || response.data.size === 0) {
      throw new Error('El servidor devolvió un documento vacío.')
    }
    return URL.createObjectURL(response.data)
  } catch (error) {
    throw obtenerError(error, 'No fue posible generar el PDF de la solicitud de viáticos.')
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


export type DatosInformeComision = {
  id_comision: number
  estado: string
  lugar: string
  fecha_inicio: string | null
  fecha_final: string | null
  fecha_comision: string | null
  nombre_completo: string
  rfc: string
  area_nombre: string
  informe_registrado: boolean
  puede_registrar: boolean
}

export type InformeComisionData = {
  id_comision: number
  objetivo_comision: string
  actividad_desarrollada: string
  resultados: string
  contribucion: string
  conclusion: string
}

export type ResultadoInformeComision = {
  id_informe_comision: number
  id_comision: number
  id_personal: number
  estado_comision: 'FINALIZADA'
}

export async function consultarDatosInformeComision(id_comision: number): Promise<DatosInformeComision> {
  try {
    const response = await api.get<{ datos: DatosInformeComision }>(
      'comisiones/routes/ver_datos_informe_comision.php',
      { params: { id_comision } },
    )
    if (!response.data?.datos) {
      throw new Error('El servidor devolvió una respuesta inválida al consultar los datos del informe.')
    }
    return {
      ...response.data.datos,
      id_comision: Number(response.data.datos.id_comision),
      informe_registrado: Boolean(response.data.datos.informe_registrado),
      puede_registrar: Boolean(response.data.datos.puede_registrar),
    }
  } catch (error) {
    throw obtenerError(error, 'No fue posible consultar los datos del informe de comisión.')
  }
}

export async function crearInformeComision(
  datos: InformeComisionData,
): Promise<ResultadoInformeComision> {
  try {
    const response = await api.post<{ mensaje: string; informe: ResultadoInformeComision }>(
      'comisiones/routes/crear_informe_comision.php',
      datos,
    )
    const idInforme = Number(response.data?.informe?.id_informe_comision)
    if (!Number.isInteger(idInforme) || idInforme < 1) {
      throw new Error('El servidor devolvió una respuesta inválida al guardar el informe.')
    }
    return {
      ...response.data.informe,
      id_informe_comision: idInforme,
      id_comision: Number(response.data.informe.id_comision),
      id_personal: Number(response.data.informe.id_personal),
    }
  } catch (error) {
    throw obtenerError(error, 'No fue posible guardar el informe de comisión.')
  }
}

export async function obtenerPdfInformeComision(id_informe_comision: number): Promise<string> {
  try {
    const response = await api.get<Blob>(
      `comisiones/routes/generar_pdf_informe_comision.php?id=${id_informe_comision}`,
      { responseType: 'blob' },
    )
    if (!response.data || response.data.size === 0) {
      throw new Error('El servidor devolvió un documento vacío.')
    }
    return URL.createObjectURL(response.data)
  } catch (error) {
    throw obtenerError(error, 'No fue posible generar el PDF del informe de comisión.')
  }
}

export type InformeComisionResumen = {
  id_informe_comision: number
  id_comision: number
  comisionado: string
  area_nombre: string | null
  fecha_inicio: string | null
  fecha_final: string | null
  fecha_comision: string | null
  fecha_referencia: string | null
  estado_comision: string
  lugar: string
  institucion_solicita: string
}

export async function consultarInformesComisionMes(
  mes: number,
  anio: number,
): Promise<InformeComisionResumen[]> {
  try {
    const response = await api.get<{ informes: InformeComisionResumen[] }>(
      'comisiones/routes/ver_informes_comision_mes.php',
      { params: { mes, anio } },
    )
    if (!Array.isArray(response.data?.informes)) {
      throw new Error('El servidor devolvió una respuesta inválida al consultar los informes de comisión.')
    }
    return response.data.informes.map((informe) => ({
      ...informe,
      id_informe_comision: Number(informe.id_informe_comision),
      id_comision: Number(informe.id_comision),
    }))
  } catch (error) {
    throw obtenerError(error, 'No fue posible consultar los informes de comisión.')
  }
}

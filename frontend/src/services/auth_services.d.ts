export type RegisterUserData = {
  nombre: string
  apellido_p: string
  apellido_m: string
  telefono: string
  rfc: string
  curp: string
  correo: string
  puesto: number
  area: number | null
  id_jefe: number | null
  contrasena: string
}

export type RegisterResponse = {
  message?: string
  usuario?: {
    id: string
    nombre: string
    curp: string
    correo: string
  }
}

export type LoginCredencials = {
  correo : string,
  contrasena : string
}
export type LoginResponse ={
  message?: string
}

export type CrearSalidaData = {
  tipo_salida: string
  motivo: string
  destino: string
}

export type CrearSalidaResponse = {
  message?: string
  salida?: {
    id_salida: string
    tipo_salida: string
    motivo: string
    destino: string
    estado: string
  }
}

export type VerSalida = {
  numero_salidas: number
  id_salida: number
  tipo_salida: string
  motivo: string
  destino: string
  horade_salida: string | null
  horade_regreso: string | null
  estado: string
  adeudo: string | null
}

export type VerSalidasPendientesResponse = {
  id_salida: number,
  tipo_salida: string,
  motivo: string,
  destino: string,
  // horade_salida: string | null,
  // horade_regreso: string | null,
  // adeudo: string | null,
  nombre: string,
  apellido_p: string,
  apellido_m: string,
  id_personal: number
}

export type Usuario = {
  id_personal: number
  nombre: string
  apellido_p: string
  apellido_m: string
  correo: string
  puesto: number
  area: number
  id_jefe: number | null
}

export type VerSalidasEnCursoResponse = {
  id_salida: number,
  tipo_salida: string,
  motivo: string,
  destino: string,
  fecha: string,
  hora_salida: string,
  hora_estimada: string,
  nombre: string,
  apellido_p: string,
  apellido_m: string,
  id_personal: number
}

export type AceptarSalidaData = {
  id_salida: number
}
export type AceptarSalidaResponse = {
  id_salida: number,
  estado: string,
  hora_salida: string,
  hora_llegada: string
}

export type RechazarSalidaData = {
  id_salida: number
}
export type RechazarSalidaResponse = {
  id_salida: number
}

export type VerHistorialSalida = {
  id_salida: number
  destino: string
  motivo: string
  hora_llegada: string | null
  hora_salida: string | null
  id_personal: number
  id_autoriza: number | null
  fecha: string
  adeudo_tiempo: string | null
  nombre: string
  apellido_p: string
  apellido_m: string
}

export type VerHistorialResponse = {
  message: string
  "Número de salidas": number
  Salidas: VerHistorialSalida[]
}

export const authService: {
  register(userData: RegisterUserData): Promise<RegisterResponse>
    login(userData: LoginCredencials): Promise<LoginResponse>
    CrearSalida(solicitud: CrearSalidaData): Promise<CrearSalidaResponse>
    VerHistorialPersonal(): Promise<VerSalida[]>
    VerSalidasPendientes(): Promise<VerSalidasPendientesResponse[]>
    VerSalidasEnCurso(): Promise<VerSalidasEnCursoResponse[]>
    AceptarSalida(id: AceptarSalidaData): Promise<AceptarSalidaResponse> 
    RechazarSalida(id: RechazarSalidaData): Promise<RechazarSalidaResponse>
    getUsuarioActual(): Usuario | null
    VerHistorial(): Promise<VerHistorialResponse>
}

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

export const authService: {
  register(userData: RegisterUserData): Promise<RegisterResponse>
    login(userData: LoginCredencials): Promise<LoginResponse>
    CrearSalida(solicitud: CrearSalidaData): Promise<CrearSalidaResponse>

}

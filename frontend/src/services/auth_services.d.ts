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

export const authService: {
  register(userData: RegisterUserData): Promise<RegisterResponse>
}

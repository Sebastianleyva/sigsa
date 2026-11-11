import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import './index.css'
import LoginScreen from './screens/auth/login.tsx'
import App from './screens/home.tsx'
import CrearSalida from './screens/salidas/crear_salida.tsx'
import SalidasHome from './screens/salidas/salidas_home.tsx'
import VerHistorialPersonal from './screens/salidas/ver_historial_personal.tsx'
import Ver_Salidas_Pendientes from './screens/salidas/ver_salidas_pendientes.tsx'
import Ver_Salidas_En_Curso from './screens/salidas/ver_salidas_en_curso.tsx'
import VerHistorial from './screens/salidas/ver_historial.tsx'
import RegistrarScreen from './screens/auth/registrar.tsx'
import CambiarContrasenaScreen from './screens/auth/cambiar_contrasena.tsx'
import MantenimientoHome, { MantenimientoDestino } from './screens/mantenimiento/mantenimiento_home.tsx'
import CalendarioMantenimiento from './screens/mantenimiento/calendario_mantenimiento.tsx'
import ServiciosMantenimiento from './screens/mantenimiento/servicios_mantenimiento.tsx'
import MantenimientosEnCurso from './screens/mantenimiento/mantenimientos_en_curso.tsx'
import RequisicionesMantenimiento from './screens/mantenimiento/requisiciones_mantenimiento.tsx'
import ReportarIncidencia from './screens/mantenimiento/reportar_incidencia.tsx'
import VerIncidenciasMantenimiento from './screens/mantenimiento/ver_incidencias.tsx'


createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Navigate to="/login" replace />} />
        <Route path="/login" element={<LoginScreen />} />
        <Route path="/home" element={<App />} />
        <Route path="/mantenimiento" element={<MantenimientoHome />} />
        <Route path="/mantenimiento/calendario" element={<CalendarioMantenimiento />} />
        <Route path="/mantenimiento/servicios" element={<ServiciosMantenimiento />} />
        <Route path="/mantenimiento/reportes/en-curso" element={<MantenimientosEnCurso />} />
        <Route path="/mantenimiento/requisiciones" element={<RequisicionesMantenimiento />} />
        <Route path="/mantenimiento/incidencias/crear" element={<ReportarIncidencia />} />
        <Route path="/mantenimiento/incidencias" element={<VerIncidenciasMantenimiento />} />
        <Route path="/mantenimiento/*" element={<MantenimientoDestino />} />
        <Route path="/salidas" element={<SalidasHome />} />
        <Route path="/salidas/crear" element={<CrearSalida />} />
        <Route path="/salidas/historial" element={<VerHistorialPersonal />} />
        <Route path="/salidas/pendientes" element={<Ver_Salidas_Pendientes />} />
        <Route path="/salidas/en-curso" element={<Ver_Salidas_En_Curso />} />
        <Route path="/salidas/historial-general" element={<VerHistorial />} />
        <Route path="/registrarse" element={<RegistrarScreen />} />
        <Route path="/cambiar-contrasena" element={<CambiarContrasenaScreen />} />
      </Routes>
    </BrowserRouter>
  </StrictMode>,
)

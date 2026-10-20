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


createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <BrowserRouter>
      <Routes>
        <Route path="/" element={<Navigate to="/login" replace />} />
        <Route path="/login" element={<LoginScreen />} />
        <Route path="/home" element={<App />} />
        <Route path="/salidas" element={<SalidasHome />} />
        <Route path="/salidas/crear" element={<CrearSalida />} />
        <Route path="/salidas/historial" element={<VerHistorialPersonal />} />
        <Route path="/salidas/pendientes" element={<Ver_Salidas_Pendientes />} />
      </Routes>
    </BrowserRouter>
  </StrictMode>,
)

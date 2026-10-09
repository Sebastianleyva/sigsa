import { StrictMode } from 'react'
import { createRoot } from 'react-dom/client'
import './index.css'
// import App from './App.tsx'
import CrearSalidaScreen from './screens/salidas/screens/crear_salida_screen.tsx'

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <CrearSalidaScreen />
  </StrictMode>,
)

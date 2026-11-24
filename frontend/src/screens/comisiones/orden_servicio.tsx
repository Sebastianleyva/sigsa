import { useNavigate, useParams } from 'react-router-dom'
import './styles/comisiones_operacion.css'

export default function OrdenServicio() {
  const navigate = useNavigate()
  const { id_comision } = useParams()

  return (
    <main className="comisiones-operacion-page">
      <header className="comisiones-operacion-topbar">
        <button className="comisiones-operacion-brand" type="button" onClick={() => navigate('/home')}>
          <span>SIGSA</span>
        </button>
        <span className="comisiones-operacion-topbar-label">MÓDULO DE COMISIONES</span>
      </header>
      <section className="comisiones-operacion-content">
        <button className="comisiones-operacion-back" type="button" onClick={() => navigate('/comisiones/en-curso')}>
          ← Volver a comisiones en curso
        </button>
        <div className="comisiones-operacion-heading">
          <span className="comisiones-operacion-eyebrow">COMISIONES · SIGSA</span>
          <h1>Orden de servicio</h1>
          <p>Comisión #{id_comision}</p>
        </div>
      </section>
    </main>
  )
}

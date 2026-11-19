import { type FormEvent, useCallback, useEffect, useState } from 'react'
import { useNavigate } from 'react-router-dom'
import ConfirmationDialog from '../../components/ConfirmationDialog'
import { authService } from '../../services/auth_services.js'
import {
  actualizarCodigoContable,
  consultarCodigosContables,
  crearCodigoContable,
  desactivarCodigoContable,
  puedeGestionarMantenimiento,
  type CodigoContableMantenimiento,
} from '../../services/mantenimiento_services'
import './styles/codigos_contables_mantenimiento.css'

export default function CodigosContablesMantenimiento() {
  const navigate = useNavigate()
  const usuario = authService.getUsuarioActual()
  const esAdministrador = puedeGestionarMantenimiento(usuario)
  const [codigos, setCodigos] = useState<CodigoContableMantenimiento[]>([])
  const [codigo, setCodigo] = useState('')
  const [descripcion, setDescripcion] = useState('')
  const [codigoEnEdicion, setCodigoEnEdicion] = useState<number | null>(null)
  const [codigoAConfirmar, setCodigoAConfirmar] = useState<CodigoContableMantenimiento | null>(null)
  const [cargando, setCargando] = useState(true)
  const [guardando, setGuardando] = useState(false)
  const [desactivando, setDesactivando] = useState<number | null>(null)
  const [error, setError] = useState('')
  const [mensaje, setMensaje] = useState('')

  const cargarCodigos = useCallback(async () => {
    setCargando(true)
    setError('')
    try {
      setCodigos(await consultarCodigosContables())
    } catch (errorCarga: unknown) {
      setError(errorCarga instanceof Error ? errorCarga.message : 'No fue posible consultar los códigos contables.')
    } finally {
      setCargando(false)
    }
  }, [])

  useEffect(() => {
    if (!esAdministrador) return
    let vigente = true
    consultarCodigosContables()
      .then((resultado) => {
        if (vigente) setCodigos(resultado)
      })
      .catch((errorCarga: unknown) => {
        if (vigente) {
          setError(errorCarga instanceof Error ? errorCarga.message : 'No fue posible consultar los códigos contables.')
        }
      })
      .finally(() => {
        if (vigente) setCargando(false)
      })
    return () => {
      vigente = false
    }
  }, [esAdministrador])

  function limpiarFormulario() {
    setCodigo('')
    setDescripcion('')
    setCodigoEnEdicion(null)
  }

  function editar(codigoContable: CodigoContableMantenimiento) {
    setCodigoEnEdicion(codigoContable.id_codigo)
    setCodigo(codigoContable.codigo_contable)
    setDescripcion(codigoContable.descripcion ?? '')
    setError('')
    setMensaje('')
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  async function guardar(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError('')
    setMensaje('')
    setGuardando(true)
    try {
      if (codigoEnEdicion === null) {
        const creado = await crearCodigoContable({
          codigo_contable: codigo.trim(),
          descripcion: descripcion.trim(),
        })
        setCodigos((actuales) => [...actuales, creado].sort((a, b) => a.codigo_contable.localeCompare(b.codigo_contable)))
        setMensaje('Código contable agregado.')
      } else {
        const actualizado = await actualizarCodigoContable({
          id_codigo: codigoEnEdicion,
          codigo_contable: codigo.trim(),
          descripcion: descripcion.trim(),
        })
        setCodigos((actuales) => actuales.map((actual) => (
          actual.id_codigo === codigoEnEdicion ? { ...actual, ...actualizado } : actual
        )).sort((a, b) => b.activo - a.activo || a.codigo_contable.localeCompare(b.codigo_contable)))
        setMensaje('Código contable actualizado.')
      }
      limpiarFormulario()
    } catch (errorGuardado: unknown) {
      setError(errorGuardado instanceof Error ? errorGuardado.message : 'No fue posible guardar el código contable.')
    } finally {
      setGuardando(false)
    }
  }

  async function desactivar(codigoContable: CodigoContableMantenimiento) {
    setError('')
    setMensaje('')
    setDesactivando(codigoContable.id_codigo)
    try {
      await desactivarCodigoContable(codigoContable.id_codigo)
      setCodigos((actuales) => actuales.map((actual) => (
        actual.id_codigo === codigoContable.id_codigo ? { ...actual, activo: 0 } : actual
      )).sort((a, b) => b.activo - a.activo || a.codigo_contable.localeCompare(b.codigo_contable)))
      setMensaje(`Se desactivó el código ${codigoContable.codigo_contable}.`)
    } catch (errorDesactivacion: unknown) {
      setError(errorDesactivacion instanceof Error ? errorDesactivacion.message : 'No fue posible desactivar el código contable.')
    } finally {
      setDesactivando(null)
    }
  }

  if (!esAdministrador) {
    return (
      <main className="codigos-contables-page">
        <section className="codigos-contables-content">
          <h1>Acceso restringido</h1>
          <p>Solo dirección, subdirección y el Departamento de Operaciones y Logística pueden administrar códigos contables.</p>
          <button type="button" onClick={() => navigate('/mantenimiento')}>Volver al módulo</button>
        </section>
      </main>
    )
  }

  return (
    <main className="codigos-contables-page">
      <header className="codigos-contables-topbar">
        <button type="button" className="codigos-contables-brand" onClick={() => navigate('/mantenimiento')}>
          <span className="codigos-contables-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
          <span>SIGSA</span>
        </button>
        <span>ADMINISTRACIÓN DE CÓDIGOS CONTABLES</span>
      </header>

      <section className="codigos-contables-content">
        <button className="codigos-contables-back" type="button" onClick={() => navigate('/mantenimiento')}>
          ← Volver al módulo
        </button>
        <div className="codigos-contables-heading">
          <span className="codigos-contables-eyebrow">ADMINISTRACIÓN · SIGSA</span>
          <h1>Códigos contables</h1>
          <p>Agrega y modifica códigos del catálogo, o desactiva los que ya no deban aparecer en nuevas requisiciones.</p>
        </div>

        {mensaje && <p className="codigos-contables-success" role="status">{mensaje}</p>}
        {error && <p className="codigos-contables-error" role="alert">{error}</p>}

        <div className="codigos-contables-layout">
          <section className="codigos-contables-panel" aria-labelledby="codigo-form-title">
            <span className="codigos-contables-section-index">01 / CATÁLOGO</span>
            <h2 id="codigo-form-title">{codigoEnEdicion === null ? 'Agregar código' : 'Modificar código'}</h2>
            <form onSubmit={(event) => void guardar(event)}>
              <label htmlFor="codigo-contable">Código contable</label>
              <input
                id="codigo-contable"
                value={codigo}
                onChange={(event) => setCodigo(event.target.value)}
                maxLength={14}
                required
              />
              <label htmlFor="codigo-descripcion">Descripción</label>
              <textarea
                id="codigo-descripcion"
                value={descripcion}
                onChange={(event) => setDescripcion(event.target.value)}
                rows={4}
                required
              />
              <div className="codigos-contables-form-actions">
                <button className="codigos-contables-submit" type="submit" disabled={guardando}>
                  {guardando ? 'Guardando…' : codigoEnEdicion === null ? 'Agregar código' : 'Guardar cambios'}
                </button>
                {codigoEnEdicion !== null && (
                  <button className="codigos-contables-cancel" type="button" onClick={limpiarFormulario} disabled={guardando}>
                    Cancelar edición
                  </button>
                )}
              </div>
            </form>
          </section>

          <section className="codigos-contables-panel" aria-labelledby="codigo-list-title">
            <div className="codigos-contables-list-heading">
              <div>
                <span className="codigos-contables-section-index">02 / CÓDIGOS REGISTRADOS</span>
                <h2 id="codigo-list-title">Catálogo actual</h2>
              </div>
              <button type="button" onClick={() => void cargarCodigos()} disabled={cargando}>Actualizar</button>
            </div>
            {cargando && <p className="codigos-contables-muted" role="status">Cargando códigos contables…</p>}
            {!cargando && codigos.length === 0 && (
              <p className="codigos-contables-muted">No hay códigos contables registrados.</p>
            )}
            <div className="codigos-contables-list">
              {codigos.map((item) => (
                <article className={`codigos-contables-item${item.activo ? '' : ' codigos-contables-item-inactive'}`} key={item.id_codigo}>
                  <div className="codigos-contables-item-info">
                    <h3>{item.codigo_contable}</h3>
                    <p>{item.descripcion || 'Sin descripción'}</p>
                    <span className="codigos-contables-status">{item.activo ? 'Activo' : 'Desactivado'}</span>
                  </div>
                  <div className="codigos-contables-item-actions">
                    <button type="button" onClick={() => editar(item)}>Modificar</button>
                    {item.activo === 1 && (
                      <button
                        className="codigos-contables-delete"
                        type="button"
                        onClick={() => setCodigoAConfirmar(item)}
                        disabled={desactivando === item.id_codigo}
                      >
                        {desactivando === item.id_codigo ? 'Desactivando…' : 'Desactivar'}
                      </button>
                    )}
                  </div>
                </article>
              ))}
            </div>
          </section>
        </div>
      </section>
      {codigoAConfirmar && (
        <ConfirmationDialog
          open
          title="¿Desactivar este código contable?"
          message={`"${codigoAConfirmar.codigo_contable} — ${codigoAConfirmar.descripcion ?? ''}" dejará de estar disponible para nuevas requisiciones. Las requisiciones existentes se conservarán.`}
          cancelLabel="Conservar código"
          onCancel={() => setCodigoAConfirmar(null)}
          actions={[{
            label: 'Desactivar código',
            variant: 'danger',
            onClick: () => {
              const seleccionado = codigoAConfirmar
              setCodigoAConfirmar(null)
              void desactivar(seleccionado)
            },
          }]}
        />
      )}
    </main>
  )
}

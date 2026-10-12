import { useState } from 'react'
import type { FormEvent } from 'react'
import './styles/crear_salida_screen.css'

type SolicitudResponse = {
    message?: string
    ERROR?: string
}

const API_URL = `${import.meta.env.VITE_API_BASE_URL ?? '/sigsa/backend'}/modulos/salidas/routes/crear_solicitud.php`

function CrearSalidaScreen() {
    const [tipoSalida, setTipoSalida] = useState('')
    const [motivo, setMotivo] = useState('')
    const [destino, setDestino] = useState('')
    const [notice, setNotice] = useState<{ kind: 'success' | 'error'; text: string } | null>(null)
    const [isSubmitting, setIsSubmitting] = useState(false)

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault()
        setNotice(null)

        const token = sessionStorage.getItem('token')
        const csrfToken = sessionStorage.getItem('csrf_token')

        if (!token || !csrfToken) {
            setNotice({ kind: 'error', text: 'Tu sesión no está disponible. Inicia sesión nuevamente.' })
            return
        }

        setIsSubmitting(true)
        try {
            const response = await fetch(API_URL, {
                method: 'POST',
                credentials: 'include',
                headers: {
                    'Content-Type': 'application/json',
                    Authorization: `Bearer ${token}`,
                    'X-CSRF-Token': csrfToken,
                },
                body: JSON.stringify({ tipo_salida: tipoSalida, motivo: motivo.trim(), destino: destino.trim() }),
            })
            const result = await response.json() as SolicitudResponse

            if (!response.ok) {
                throw new Error(result.ERROR ?? 'No fue posible registrar la solicitud. Intenta nuevamente.')
            }

            setNotice({ kind: 'success', text: result.message ?? 'Solicitud enviada para autorización.' })
            setTipoSalida('')
            setMotivo('')
            setDestino('')
        } catch (error) {
            setNotice({
                kind: 'error',
                text: error instanceof Error ? error.message : 'Ocurrió un error de conexión. Intenta nuevamente.',
            })
        } finally {
            setIsSubmitting(false)
        }
    }

    return (
        <main className="salida-page">
            <header className="salida-topbar">
                <a className="salida-brand" href="#inicio" onClick={(event) => event.preventDefault()}>
                    <span className="salida-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
                    <span>SIGSA</span>
                </a>
                <div className="salida-topbar-meta">
                    <span className="salida-topbar-dot" aria-hidden="true" />
                    <span>Gestión de personal</span>
                </div>
            </header>

            <section className="salida-content" id="inicio">
                <div className="salida-heading">
                    <span className="salida-eyebrow">CONTROL DE SALIDAS</span>
                    <h1>Nueva solicitud</h1>
                    <p>Registra los datos de tu salida para iniciar el proceso de autorización.</p>
                </div>

                <div className="salida-layout">
                    <form className="salida-form" onSubmit={handleSubmit}>
                        <div className="salida-form-heading">
                            <div>
                                <span className="salida-step">01</span>
                                <h2>Datos de la solicitud</h2>
                            </div>
                            <span className="salida-required-note"><span aria-hidden="true">*</span> Obligatorio</span>
                        </div>

                        <div className="salida-field">
                            <label htmlFor="tipo-salida">Tipo de salida <span aria-hidden="true">*</span></label>
                            <select
                                id="tipo-salida"
                                name="tipo_salida"
                                value={tipoSalida}
                                onChange={(event) => setTipoSalida(event.target.value)}
                                required
                            >
                                <option value="" disabled>Selecciona una opción</option>
                                <option value="OFICIAL">Oficial</option>
                                <option value="Personal">Personal</option>
                                <option value="permiso economico">Permiso económico</option>
                            </select>
                        </div>

                        <div className="salida-field">
                            <label htmlFor="motivo-salida">Motivo <span aria-hidden="true">*</span></label>
                            <textarea
                                id="motivo-salida"
                                name="motivo"
                                value={motivo}
                                onChange={(event) => setMotivo(event.target.value)}
                                placeholder="Describe brevemente el motivo de tu salida"
                                rows={4}
                                maxLength={1000}
                                required
                            />
                            <span className="salida-field-hint">Máximo 1,000 caracteres</span>
                        </div>

                        <div className="salida-field">
                            <label htmlFor="destino-salida">Destino <span aria-hidden="true">*</span></label>
                            <input
                                id="destino-salida"
                                name="destino"
                                value={destino}
                                onChange={(event) => setDestino(event.target.value)}
                                placeholder="Dirección, institución o lugar"
                                maxLength={1000}
                                required
                            />
                        </div>

                        {notice && (
                            <p className={`salida-notice salida-notice-${notice.kind}`} role="status">
                                <span className="salida-notice-mark" aria-hidden="true">{notice.kind === 'success' ? '✓' : '!'}</span>
                                {notice.text}
                            </p>
                        )}

                        <div className="salida-form-actions">
                            <span>La solicitud quedará pendiente de autorización.</span>
                            <button type="submit" disabled={isSubmitting}>
                                {isSubmitting ? 'Enviando…' : 'Enviar solicitud'}
                                <span aria-hidden="true">→</span>
                            </button>
                        </div>
                    </form>

                    <aside className="salida-aside" aria-label="Información de la solicitud">
                        <span className="salida-aside-index">SIGSA / SALIDAS</span>
                        <div className="salida-aside-rule" />
                        <h2>Un registro claro facilita el seguimiento.</h2>
                        <p>Tu solicitud se enviará a la persona responsable de autorizarla.</p>
                        <div className="salida-aside-status">
                            <span className="salida-status-icon" aria-hidden="true">i</span>
                            <span>Solo puedes tener una solicitud pendiente a la vez.</span>
                        </div>
                        <span className="salida-aside-footer">GESTIÓN · EQUIPO · SERVICIO</span>
                    </aside>
                </div>
            </section>

            <footer className="salida-footer">
                <span>© SIGSA</span>
                <span>Sistema integral de gestión</span>
            </footer>
        </main>
    )
}

export default CrearSalidaScreen
import { useState } from "react";
import type { FormEvent } from "react";
import { authService } from '../../services/auth_services.js'
import './styles/crear_salida.css'


type SalidaForm = {
    tipo_salida: string,
    motivo: string,
    destino: string,
    solicita_respaldo: boolean
} 
const initialForm: SalidaForm ={
    tipo_salida:'',
    motivo:'',
    destino:'',
    solicita_respaldo: false
}

type Notice = {
    kind: "success" | "error";
    text: string;
}

export default function CrearSalida() {
    const [form, setForm] =  useState<SalidaForm>(initialForm)
    const [notice, setNotice] = useState<Notice | null>(null)
    const [loading, setLoading] = useState(false)
    const usuario = authService.getUsuarioActual()

    function updateField<Field extends keyof SalidaForm>(field: Field, value: SalidaForm[Field]) {
        setForm((current) => ({ ...current, [field]: value }))
    }

    async function handleSubmit(event: FormEvent<HTMLFormElement>){
        event.preventDefault()
        setNotice(null)
        setLoading(true)

        try{
            const result = await authService.CrearSalida({
                tipo_salida: form.tipo_salida.trim(),
                motivo: form.motivo.trim(),
                destino: form.destino.trim(),
                solicita_respaldo: form.solicita_respaldo
            })
            
            setNotice ({kind: "success", text: result.message ?? "La salida fue creada con éxito"})
            setForm({...initialForm})
        }
        catch(error){
            setNotice ({kind: "error",
                text: error instanceof Error ? error.message : 'Ocurrió un error al crear la salida.'})
        }
        finally{
            setLoading(false)
        }
    }

    return(
        <main className="crear-salida-page">
            <header className="crear-salida-topbar">
                <a className="crear-salida-brand" href="#inicio" onClick={(event) => event.preventDefault()}>
                    <span className="crear-salida-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
                    <span>SIGSA</span>
                </a>
                <div className="crear-salida-topbar-meta">
                    <span className="crear-salida-topbar-dot" aria-hidden="true" />
                    <span>Sistema integral de gestión</span>
                </div>
            </header>

            <section className="crear-salida-content">
                <div className="crear-salida-heading">
                    <span className="crear-salida-eyebrow">OPERACIÓN · SIGSA</span>
                    <h1>Solicitud de salida</h1>
                    <p>Registra los datos de tu salida para que pueda ser revisada.</p>
                </div>

                <div className="crear-salida-layout">
                    <form className="crear-salida-form" onSubmit={handleSubmit}>
                        <div className="crear-salida-form-heading">
                            <div>
                                <h2>Datos de la solicitud</h2>
                            </div>
                            <span className="crear-salida-required-note"><span>*</span> Campos obligatorios</span>
                        </div>

                        <div className="crear-salida-field">
                            <label htmlFor="tipo_salida">Tipo de salida <span>*</span></label>
                            <select
                                id="tipo_salida"
                                name="tipo_salida"
                                value={form.tipo_salida}
                                onChange={(event) => updateField("tipo_salida", event.target.value)}
                                required
                            >
                                <option value="" disabled>Selecciona un tipo de salida</option>
                                <option value="PERSONAL">PERSONAL</option>
                                <option value="MEDICA">MÉDICA</option>
                                <option value="CITA">CITA</option>
                            </select>
                        </div>

                        <div className="crear-salida-field">
                            <label htmlFor="destino">Destino <span>*</span></label>
                            <textarea
                                id="destino"
                                name="destino"
                                value={form.destino}
                                onChange={(event) => updateField("destino", event.target.value)}
                                placeholder="Indica el lugar al que te diriges"
                                required
                            />
                        </div>

                        <div className="crear-salida-field">
                            <label htmlFor="motivo">Motivo <span>*</span></label>
                            <textarea
                                id="motivo"
                                name="motivo"
                                value={form.motivo}
                                onChange={(event) => updateField("motivo", event.target.value)}
                                placeholder="Describe brevemente el motivo de la salida"
                                required
                            />
                        </div>

                        {Number(usuario?.puesto) === 4 && (
                            <label className="crear-salida-respaldo">
                                <input
                                    type="checkbox"
                                    checked={form.solicita_respaldo}
                                    onChange={(event) => updateField("solicita_respaldo", event.target.checked)}
                                />
                                <span>
                                    Solicitar revisión del subdirector porque mi jefe directo no está disponible.
                                    <small>La solicitud seguirá visible para tu jefe directo y el subdirector de tu subdirección.</small>
                                </span>
                            </label>
                        )}

                        {notice && (
                            <div
                                className={`crear-salida-notice crear-salida-notice-${notice.kind}`}
                                role={notice.kind === "error" ? "alert" : "status"}
                                aria-live="polite"
                            >
                                <span className="crear-salida-notice-mark" aria-hidden="true">
                                    {notice.kind === "success" ? "✓" : "!"}
                                </span>
                                <span>{notice.text}</span>
                            </div>
                        )}

                        <div className="crear-salida-form-actions">
                            <span>Revisa que la información sea correcta antes de enviar.</span>
                            <button type="submit" disabled={loading}>
                                {loading ? "Enviando..." : "Crear solicitud"}
                                <span aria-hidden="true">→</span>
                            </button>
                        </div>
                    </form>

                    <aside className="crear-salida-aside">
                        <span className="crear-salida-aside-index">INFORMACIÓN</span>
                        <span className="crear-salida-aside-rule" aria-hidden="true" />
                        <h2>Antes de enviar</h2>
                        <p>
                            {[1, 2].includes(Number(usuario?.puesto))
                                ? 'Tu salida se registrará directamente en curso y no necesitará autorización.'
                                : 'Una vez enviada, la solicitud quedará pendiente de revisión por tu jefe directo.'}
                        </p>
                        <div className="crear-salida-aside-status">
                            <span className="crear-salida-status-icon" aria-hidden="true">i</span>
                            <span>Podrás consultar el estado de tu solicitud en el módulo de salidas.</span>
                        </div>
                    </aside>
                </div>
            </section>
        </main>
    )
}
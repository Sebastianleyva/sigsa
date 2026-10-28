import { useEffect, useState } from 'react'
import type { FormEvent } from 'react'
import { authService } from '../../services/auth_services.js'
import type { OpcionesRegistroResponse } from '../../services/auth_services.js'
import './registrar.css'

type RegisterForm = {
    nombre: string,
    apellido_p: string,
    apellido_m: string,
    telefono: string,
    rfc: string,
    curp: string,
    correo: string,
    puesto: string,
    area: string,
    id_jefe: string,
    contrasena: string,
    confirmarContrasena: string
} 

type Notice = {
    kind: 'success' | 'error',
    text: string
}

const initialForm: RegisterForm = {
    nombre: '',
    apellido_p: '',
    apellido_m: '',
    telefono: '',
    rfc: '',
    curp: '',
    correo: '',
    puesto: '',
    area: '',
    id_jefe: '',
    contrasena: '',
    confirmarContrasena: '',
}

function RegistrarScreen() {
    const [form, setForm] = useState<RegisterForm>(initialForm)
    const [notice, setNotice] = useState<Notice | null>(null)
    const [isSubmitting, setIsSubmitting] = useState(false)
    const [passwordVisible, setPasswordVisible] = useState(false)
    const [opciones, setOpciones] = useState<OpcionesRegistroResponse | null>(null)
    const [loadingOptions, setLoadingOptions] = useState(true)

    useEffect(() => {
        let mounted = true
        authService.ObtenerOpcionesRegistro()
            .then((result) => {
                if (mounted) setOpciones(result)
            })
            .catch((error: unknown) => {
                if (mounted) {
                    setNotice({
                        kind: 'error',
                        text: error instanceof Error ? error.message : 'No fue posible cargar las opciones de registro.',
                    })
                }
            })
            .finally(() => {
                if (mounted) setLoadingOptions(false)
            })

        return () => {
            mounted = false
        }
    }, [])

    const puestoSeleccionado = Number(form.puesto)
    const areasDisponibles = opciones?.areas.filter((area) => {
        if (puestoSeleccionado === 2) {
            return area.id_subdireccion == null && area.nombre.toUpperCase().startsWith('SUBDIRECCION')
        }
        return (puestoSeleccionado === 3 || puestoSeleccionado === 4) && area.id_subdireccion != null
    }) ?? []

    const jefesDisponibles = opciones?.personal.filter((personal) => {
        if (puestoSeleccionado === 2)         return Number(personal.puesto) === 1
        if (puestoSeleccionado === 3) {
            const areaSeleccionada = opciones.areas.find((area) => Number(area.id_area) === Number(form.area))
            return Number(personal.puesto) === 2 && Number(personal.area) === Number(areaSeleccionada?.id_subdireccion)
        }
        if (puestoSeleccionado === 4) {
            return Number(personal.puesto) === 3 && Number(personal.area) === Number(form.area)
        }
        return false
    }) ?? []

    const hayDirectorActivo = opciones?.personal.some((personal) => Number(personal.puesto) === 1) ?? false
    const subdirectoresActivos = opciones?.personal.filter((personal) => Number(personal.puesto) === 2).length ?? 0
    const subdireccionOcupada = opciones?.personal.some((personal) => (
        Number(personal.puesto) === 2 && Number(personal.area) === Number(form.area)
    )) ?? false
    const hayCupoDisponible = puestoSeleccionado === 1
        ? !hayDirectorActivo
        : puestoSeleccionado === 2
            ? subdirectoresActivos < 2 && !subdireccionOcupada
            : true

    function updateField(field: keyof RegisterForm, value: string) {
        setForm((current) => {
            if (field === 'puesto') return { ...current, puesto: value, area: '', id_jefe: '' }
            if (field === 'area') return { ...current, area: value, id_jefe: '' }
            return { ...current, [field]: value }
        })
    }

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault()
        setNotice(null)

        if (form.contrasena !== form.confirmarContrasena) {
            setNotice({ kind: 'error', text: 'Las contraseñas no coinciden.' })
            return
        }

        if (!opciones) {
            setNotice({ kind: 'error', text: 'No se pudieron cargar las opciones de registro.' })
            return
        }

        if (!hayCupoDisponible) {
            setNotice({
                kind: 'error',
                text: puestoSeleccionado === 1
                    ? 'Ya existe un director activo.'
                    : subdirectoresActivos >= 2
                        ? 'Ya están ocupadas las dos plazas de subdirector.'
                        : 'Ya existe un subdirector activo en esta subdirección.',
            })
            return
        }

        if (puestoSeleccionado !== 1 && !jefesDisponibles.some((jefe) => Number(jefe.id_personal) === Number(form.id_jefe))) {
            setNotice({ kind: 'error', text: 'Selecciona un jefe activo que corresponda al puesto y al área.' })
            return
        }

        setIsSubmitting(true)
        try {
            const result = await authService.register({
                nombre: form.nombre.trim(),
                apellido_p: form.apellido_p.trim(),
                apellido_m: form.apellido_m.trim(),
                telefono: form.telefono.trim(),
                rfc: form.rfc.trim(),
                curp: form.curp.trim(),
                correo: form.correo.trim(),
                puesto: puestoSeleccionado,
                area: puestoSeleccionado === 1 ? null : Number(form.area),
                id_jefe: puestoSeleccionado === 1 ? null : Number(form.id_jefe),
                contrasena: form.contrasena,
            })

            setNotice({ kind: 'success', text: result.message ?? 'Usuario registrado con éxito.' })
            setForm({ ...initialForm })
            try {
                setOpciones(await authService.ObtenerOpcionesRegistro())
            } catch (error) {
                setNotice({
                    kind: 'error',
                    text: `El usuario se registró, pero no se actualizaron las opciones: ${
                        error instanceof Error ? error.message : 'recarga la pantalla para ver los cambios.'
                    }`,
                })
            }
        } catch (error) {
            setNotice({
                kind: 'error',
                text: error instanceof Error ? error.message : 'Ocurrió un error al registrar el usuario.',
            })
        } finally {
            setIsSubmitting(false)
        }
    }

    return (
        <main className="registro-page">
            <header className="registro-topbar">
                <a className="registro-brand" href="#inicio" onClick={(event) => event.preventDefault()}>
                    <span className="registro-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
                    <span>SIGSA</span>
                </a>
                <div className="registro-topbar-meta">
                    <span className="registro-topbar-dot" aria-hidden="true" />
                    <span>Gestión de personal</span>
                </div>
            </header>

            <section className="registro-content" id="inicio">
                <div className="registro-heading">
                    <span className="registro-eyebrow">ACCESO A SIGSA</span>
                    <h1>Registrar usuario</h1>
                    <p>Completa tus datos para crear una cuenta en el sistema.</p>
                </div>

                <div className="registro-layout">
                    <form className="registro-form" onSubmit={handleSubmit}>
                        <div className="registro-form-heading">
                            <div>
                                <span className="registro-step">01</span>
                                <h2>Datos del usuario</h2>
                            </div>
                            <span className="registro-required-note"><span aria-hidden="true">*</span> Obligatorio</span>
                        </div>

                        <div className="registro-fields">
                            <div className="registro-field">
                                <label htmlFor="registro-nombre">Nombre(s) <span aria-hidden="true">*</span></label>
                                <input
                                    id="registro-nombre"
                                    name="nombre"
                                    value={form.nombre}
                                    onChange={(event) => updateField('nombre', event.target.value)}
                                    autoComplete="given-name"
                                    maxLength={20}
                                    required
                                />
                            </div>

                            <div className="registro-field">
                                <label htmlFor="registro-apellido-p">Apellido paterno <span aria-hidden="true">*</span></label>
                                <input
                                    id="registro-apellido-p"
                                    name="apellido_p"
                                    value={form.apellido_p}
                                    onChange={(event) => updateField('apellido_p', event.target.value)}
                                    autoComplete="family-name"
                                    maxLength={25}
                                    required
                                />
                            </div>

                            <div className="registro-field">
                                <label htmlFor="registro-apellido-m">Apellido materno <span aria-hidden="true">*</span></label>
                                <input
                                    id="registro-apellido-m"
                                    name="apellido_m"
                                    value={form.apellido_m}
                                    onChange={(event) => updateField('apellido_m', event.target.value)}
                                    maxLength={25}
                                    required
                                />
                            </div>

                            <div className="registro-field">
                                <label htmlFor="registro-telefono">Teléfono <span aria-hidden="true">*</span></label>
                                <input
                                    id="registro-telefono"
                                    name="telefono"
                                    type="tel"
                                    value={form.telefono}
                                    onChange={(event) => updateField('telefono', event.target.value)}
                                    autoComplete="tel"
                                    maxLength={12}
                                    required
                                />
                            </div>

                            <div className="registro-field">
                                <label htmlFor="registro-rfc">RFC <span aria-hidden="true">*</span></label>
                                <input
                                    id="registro-rfc"
                                    name="rfc"
                                    value={form.rfc}
                                    onChange={(event) => updateField('rfc', event.target.value)}
                                    autoComplete="off"
                                    maxLength={13}
                                    required
                                />
                            </div>

                            <div className="registro-field">
                                <label htmlFor="registro-curp">CURP <span aria-hidden="true">*</span></label>
                                <input
                                    id="registro-curp"
                                    name="curp"
                                    value={form.curp}
                                    onChange={(event) => updateField('curp', event.target.value)}
                                    autoComplete="off"
                                    maxLength={18}
                                    required
                                />
                            </div>

                            <div className="registro-field registro-field-wide">
                                <label htmlFor="registro-correo">Correo electrónico <span aria-hidden="true">*</span></label>
                                <input
                                    id="registro-correo"
                                    name="correo"
                                    type="email"
                                    value={form.correo}
                                    onChange={(event) => updateField('correo', event.target.value)}
                                    autoComplete="email"
                                    maxLength={150}
                                    placeholder="nombre@institucion.gob.mx"
                                    required
                                />
                            </div>

                            <div className="registro-field">
                                <label htmlFor="registro-puesto">Puesto <span aria-hidden="true">*</span></label>
                                <select
                                    id="registro-puesto"
                                    name="puesto"
                                    value={form.puesto}
                                    onChange={(event) => updateField('puesto', event.target.value)}
                                    required
                                >
                                    <option value="" disabled>Selecciona un puesto</option>
                                    <option value="1">Director</option>
                                    <option value="2">Subdirector</option>
                                    <option value="3">Jefe de departamento</option>
                                    <option value="4">Trabajador de departamento</option>
                                </select>
                            </div>

                            {form.puesto && puestoSeleccionado !== 1 && (
                                <div className="registro-field">
                                    <label htmlFor="registro-area">Área <span aria-hidden="true">*</span></label>
                                    <select
                                        id="registro-area"
                                        name="area"
                                        value={form.area}
                                        onChange={(event) => updateField('area', event.target.value)}
                                        required
                                        disabled={!form.puesto || loadingOptions}
                                    >
                                        <option value="" disabled>Selecciona un área</option>
                                        {areasDisponibles.map((area) => (
                                            <option key={area.id_area} value={area.id_area}>{area.nombre}</option>
                                        ))}
                                    </select>
                                    {form.puesto && areasDisponibles.length === 0 && (
                                        <span className="registro-field-help">
                                            No hay áreas disponibles para este puesto. Verifica la jerarquía de áreas.
                                        </span>
                                    )}
                                </div>
                            )}

                            {form.puesto && puestoSeleccionado !== 1 && (
                                <div className="registro-field">
                                    <label htmlFor="registro-jefe">Jefe directo <span aria-hidden="true">*</span></label>
                                    <select
                                        id="registro-jefe"
                                        name="id_jefe"
                                        value={form.id_jefe}
                                        onChange={(event) => updateField('id_jefe', event.target.value)}
                                        required
                                        disabled={!form.area || loadingOptions || jefesDisponibles.length === 0}
                                    >
                                        <option value="" disabled>Selecciona un jefe</option>
                                        {jefesDisponibles.map((jefe) => (
                                            <option key={jefe.id_personal} value={jefe.id_personal}>
                                                {jefe.nombre} {jefe.apellido_p} {jefe.apellido_m}
                                            </option>
                                        ))}
                                    </select>
                                    {form.area && jefesDisponibles.length === 0 && (
                                        <span className="registro-field-help">
                                            No hay un jefe activo disponible para el área seleccionada.
                                        </span>
                                    )}
                                </div>
                            )}

                            {!hayCupoDisponible && (
                                <p className="registro-field-help registro-field-wide">
                                    {puestoSeleccionado === 1
                                        ? 'El puesto de director ya está ocupado por un usuario activo.'
                                        : subdirectoresActivos >= 2
                                            ? 'Las dos plazas de subdirector ya están ocupadas.'
                                            : 'El puesto de subdirector ya está ocupado en esta subdirección.'}
                                </p>
                            )}

                            <div className="registro-field">
                                <label htmlFor="registro-contrasena">Contraseña <span aria-hidden="true">*</span></label>
                                <div className="registro-password">
                                    <input
                                        id="registro-contrasena"
                                        name="contrasena"
                                        type={passwordVisible ? 'text' : 'password'}
                                        value={form.contrasena}
                                        onChange={(event) => updateField('contrasena', event.target.value)}
                                        autoComplete="new-password"
                                        required
                                    />
                                    <button
                                        type="button"
                                        aria-label={passwordVisible ? 'Ocultar contraseña' : 'Mostrar contraseña'}
                                        onClick={() => setPasswordVisible((visible) => !visible)}
                                    >
                                        {passwordVisible ? 'Ocultar' : 'Mostrar'}
                                    </button>
                                </div>
                            </div>

                            <div className="registro-field">
                                <label htmlFor="registro-confirmar-contrasena">Confirmar contraseña <span aria-hidden="true">*</span></label>
                                <input
                                    id="registro-confirmar-contrasena"
                                    name="confirmar_contrasena"
                                    type={passwordVisible ? 'text' : 'password'}
                                    value={form.confirmarContrasena}
                                    onChange={(event) => updateField('confirmarContrasena', event.target.value)}
                                    autoComplete="new-password"
                                    required
                                />
                            </div>
                        </div>

                        {notice && (
                            <p className={`registro-notice registro-notice-${notice.kind}`} role={notice.kind === 'error' ? 'alert' : 'status'}>
                                <span aria-hidden="true">{notice.kind === 'success' ? '✓' : '!'}</span>
                                {notice.text}
                            </p>
                        )}

                        <div className="registro-form-actions">
                            <span>Los campos marcados con * son obligatorios.</span>
                            <button type="submit" disabled={isSubmitting || loadingOptions || !opciones}>
                                {loadingOptions ? 'Cargando opciones…' : isSubmitting ? 'Registrando…' : 'Crear cuenta'}
                                <span aria-hidden="true">→</span>
                            </button>
                        </div>
                    </form>

                    <aside className="registro-aside" aria-label="Información del registro">
                        <span className="registro-aside-index">SIGSA / USUARIOS</span>
                        <div className="registro-aside-rule" />
                        <h2>Un buen comienzo para trabajar en equipo.</h2>
                        <p>Registra tu información para contar con acceso a las herramientas de gestión de SIGSA.</p>
                        <div className="registro-aside-status">
                            <span aria-hidden="true">i</span>
                            <span>El correo, RFC, CURP y teléfono deben ser únicos.</span>
                        </div>
                        <span className="registro-aside-footer">GESTIÓN · EQUIPO · SERVICIO</span>
                    </aside>
                </div>
            </section>

            <footer className="registro-footer">
                <span>© SIGSA</span>
                <a href="/">Volver al inicio de sesión</a>
            </footer>
        </main>
    )
}

export default RegistrarScreen

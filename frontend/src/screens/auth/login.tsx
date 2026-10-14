import { useState } from "react";
import type { FormEvent } from "react";
import { authService } from "../../services/auth_services";
import "./login.css";
import { useNavigate } from "react-router-dom";

type LoginForm = {
    correo: string;
    contrasena: string;
};

type Notice = {
    kind: "success" | "error";
    text: string;
};

const initialForm: LoginForm = {
    correo: "",
    contrasena: "",
};

export default function LoginScreen() {
    const [form, setForm] = useState<LoginForm>(initialForm);
    const [notice, setNotice] = useState<Notice | null>(null);
    const [loading, setLoading] = useState<boolean>(false);
    const navigate = useNavigate();

    function updateField(field: keyof LoginForm, value: string) {
        setForm((current) => ({ ...current, [field]: value }));
    }

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setNotice(null);
        setLoading(true);

        try {
            const result = await authService.login({
                correo: form.correo.trim(),
                contrasena: form.contrasena.trim(),
            });
            setNotice({
                kind: "success",
                text: result.message ?? "Inicio de sesión exitosso",
            });
            // Error: useNavigate solo funciona cuando la app está dentro de <BrowserRouter>; 
            // sin el provider, React lanza el error de contexto y la navegación no ejecuta. 
            // Se usa la ruta absoluta /home para que el redirect se resuelva dentro del árbol de 
            // rutas correcto.
            setTimeout(() => {
                navigate("/home");
            }, 1000);
            setForm(initialForm);
        } catch (error) {
            setNotice({
                kind: "error",
                text:
                    error instanceof Error
                        ? error.message
                        : "Ocurrió un error al iniciar sesión.",
            });
        } finally {
            setLoading(false);
        }
    }

    return (
        <main className="pagina-login">
            <header className="login-topbar">
                <a
                    className="login-brand"
                    href="#inicio"
                    aria-label="Logo de SIGSA"
                    onClick={(event) => event.preventDefault()}
                >
                    <span className="login-brand-mark" aria-hidden="true">
                        <i />
                        <i />
                        <i />
                        <i />
                    </span>
                    <span>SIGSA</span>
                </a>
                <div className="login-topbar-meta">
                    <span className="login-topbar-dot" aria-hidden="true" />
                    <span>Acceso al sistema</span>
                </div>
            </header>

            <section className="login-conctent">
                <form className="login-layout" onSubmit={handleSubmit}>
                    <div className="login-heading">
                        <span className="login-eyebrow">ACCESO A SIGSA</span>
                        <h1>Iniciar sesión</h1>
                    </div>

                    {notice && (
                        <div className={`alerta ${notice.kind}`} role="alert">
                            {notice.text}
                        </div>
                    )}

                    <label className="label-input" htmlFor="input-correo">
                        Correo electrónico
                    </label>
                    <input
                        className="input"
                        id="input-correo"
                        type="email"
                        value={form.correo}
                        onChange={(event) => updateField("correo", event.target.value)}
                        placeholder="correo@ejemplo.com"
                        autoComplete="email"
                        required
                    />

                    <label className="label-input" htmlFor="input-contrasena">
                        Contraseña
                    </label>
                    <input
                        className="input"
                        id="input-contrasena"
                        type="password"
                        value={form.contrasena}
                        onChange={(event) => updateField("contrasena", event.target.value)}
                        placeholder="••••••••"
                        autoComplete="current-password"
                        required
                    />

                    <button type="submit" className="btn-submit" disabled={loading}>
                        {loading ? "Cargando..." : "Iniciar Sesión"}
                    </button>
                </form>
            </section>
        </main>
    );
}

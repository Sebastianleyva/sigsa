import { useEffect, useState } from "react";
import { authService } from '../../services/auth_services.js';
import './styles/ver_historial_personal.css';

type Salida = {
    numero_salidas: number,
    id_salida: number,
    tipo_salida: string,
    motivo: string,
    destino: string,
    horade_salida: string | null,
    horade_regreso: string | null,
    adeudo: string | null
};

export default function Ver_Historial_Personal() {
    const [salidas, setSalidas] = useState<Salida[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(() => {
        async function fetchSalidas() {
            try {
                const datos = await authService.VerHistorialPersonal();
                setSalidas(datos);
            } catch (error) {
                console.error("Error fetching salidas:", error);
            } finally {
                setLoading(false);
            }
        }

        fetchSalidas();
    }, []);

    return (
        <main className="historial-personal-page">
            <header className="historial-personal-topbar">
                <a className="historial-personal-brand" href="#inicio" onClick={(event) => event.preventDefault()}>
                    <span className="historial-personal-brand-mark" aria-hidden="true"><i /><i /><i /><i /></span>
                    <span>SIGSA</span>
                </a>
                <div className="historial-personal-topbar-meta">
                    <span className="historial-personal-topbar-dot" aria-hidden="true" />
                    <span>Sistema integral de gestión</span>
                </div>
            </header>

            <section className="historial-personal-content">
                <div className="historial-personal-heading">
                    <span className="historial-personal-eyebrow">HISTORIAL · SIGSA</span>
                    <h1>Mis salidas</h1>
                    <p>Consulta el registro de tus solicitudes y revisa el detalle de cada salida programada.</p>
                </div>

                {loading ? (
                    <div className="historial-personal-status historial-personal-status-loading">Cargando...</div>
                ) : salidas.length === 0 ? (
                    <div className="historial-personal-empty">
                        <span className="historial-personal-empty-icon" aria-hidden="true">✓</span>
                        <h2>No hay salidas registradas</h2>
                        <p>Aún no tienes movimientos en tu historial personal.</p>
                    </div>
                ) : (
                    <div className="historial-personal-list">
                        {salidas.map((salida) => (
                            <article key={salida.id_salida} className="historial-personal-card">
                                <div className="historial-personal-card-header">
                                    <div>
                                        <span className="historial-personal-card-kicker">Solicitud</span>
                                        <h2>{salida.tipo_salida}</h2>
                                    </div>
                                    <span className="historial-personal-card-number">#{salida.numero_salidas}</span>
                                </div>

                                <div className="historial-personal-card-body">
                                    <div className="historial-personal-field">
                                        <span className="historial-personal-label">Motivo</span>
                                        <p>{salida.motivo}</p>
                                    </div>

                                    <div className="historial-personal-field">
                                        <span className="historial-personal-label">Destino</span>
                                        <p>{salida.destino}</p>
                                    </div>

                                    <div className="historial-personal-meta-grid">
                                        <div className="historial-personal-field">
                                            <span className="historial-personal-label">Salida</span>
                                            <p>{salida.horade_salida}</p>
                                        </div>

                                        <div className="historial-personal-field">
                                            <span className="historial-personal-label">Regreso</span>
                                            <p>{salida.horade_regreso}</p>
                                        </div>
                                    </div>

                                    <div className="historial-personal-field historial-personal-field-adeudo">
                                        <span className="historial-personal-label">Adeudo</span>
                                        <p>{salida.adeudo ?? 'No registrado'}</p>
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                )}
            </section>
        </main>
    );
}
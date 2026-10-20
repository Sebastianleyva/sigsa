import {useState, useEffect} from 'react';
import {authService} from '../../services/auth_services.js';

type SalidaPendiente = {
    id_salida: number,
    tipo_salida: string,
    motivo: string,
    destino: string,
    nombre: string,
    apellido_p: string,
    apellido_m: string,
    id_personal: number
}

export default function Ver_Salidas_Pendientes() {
    const [salidasPendientes, setSalidasPendientes] = useState<SalidaPendiente[]>([]);
    const [loading, setLoading] = useState(true);

    useEffect(()=>{
        async function fetchSalidasPendientes() {
            try{
                const datos = await authService.VerSalidasPendientes();
                setSalidasPendientes(datos);
            }
            catch(error){
                console.error("Error fetching salidas pendientes:", error);
            }finally{
                setLoading(false);
            }
        }
        fetchSalidasPendientes();
    },[]);


    return(
        <main>
            <form>
                {loading ? (
                    <div>Cargando...</div>
                    ): 
                    salidasPendientes.length === 0 ? (
                        <div>No hay salidas pendientes</div>
                    ) : (
                        <div>
                            {salidasPendientes.map((salida) => (
                                <div key={salida.id_salida}>
                                    <p>{salida.nombre} {salida.apellido_p} {salida.apellido_m}</p>
                                    <p>{salida.tipo_salida}</p>
                                    <p>{salida.motivo}</p>
                                    <p>{salida.destino}</p>
                                </div>
                            ))}
                        </div>
                    )
                }
            </form>
        </main>
    )
}
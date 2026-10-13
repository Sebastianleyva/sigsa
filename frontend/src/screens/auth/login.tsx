import {useState} from 'react';
import type {FormEvent} from 'react'
import { authService } from '../../services/auth_services';

type LoginForm = {
    correo : string,
    contrasena : string
}

type Notice = {
    kind: "success" | "error",
    text:  string
}

const initialForm: LoginForm = {
    correo : "",
    contrasena : ""
}

export default function LoginScreen(){
    const [form, setForm] = useState<LoginForm>(initialForm);
    const [notice, setNotice] = useState<Notice | null>(null);

    function updateField(field: keyof LoginForm, value: string){
        setForm((current) => ({ ...current, [field]: value }))
    }

    async function handleSubmit(event: FormEvent<HTMLFormElement>) {
        event.preventDefault()
        setNotice(null)

        try{
            const result = await authService.login({
                correo : form.correo.trim(),
                contrasena : form.contrasena.trim()
            })
            setNotice({
                kind: "success",
                text : result.message ?? "Inicio de sesión exitoso"
            })
            setForm(initialForm)
        }
        catch(error){
                setNotice({
                    kind: 'error',
                    text: error instanceof Error ? error.message : 'Ocurrió un error al registrar el usuario.',
                })
        }
    }

    return(
        <a>Hola</a>
    )
}
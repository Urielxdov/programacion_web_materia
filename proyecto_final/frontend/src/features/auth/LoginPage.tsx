import { Eye, EyeOff, LockKeyhole, LogIn } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Navigate, useNavigate, useSearchParams } from 'react-router-dom';
import { ApiError } from '../../api/cliente';
import { useLogin, useSesion } from '../../api/hooks';
import { Logo } from '../../components/ilustraciones/Ilustraciones';
import { Boton } from '../../components/ui/Boton';
import { CampoTexto } from '../../components/ui/Campo';
import { useTituloPagina } from '../../hooks/dom';
import { useFormulario, v } from '../../hooks/useFormulario';
import { avisar } from '../../stores/avisos.store';

/** Solo se permite volver a rutas internas del panel (evita redirecciones abiertas). */
function destinoSeguro(volver: string | null) {
  return volver && volver.startsWith('/panel') ? volver : '/panel';
}

export function LoginPage() {
  useTituloPagina('Iniciar sesión');
  const { data: usuario } = useSesion();
  const login = useLogin();
  const navigate = useNavigate();
  const [params] = useSearchParams();
  const [verPassword, setVerPassword] = useState(false);
  const [errorGeneral, setErrorGeneral] = useState<string | null>(null);
  const alerta = useRef<HTMLDivElement>(null);

  const form = useFormulario(
    { email: '', password: '' },
    {
      email: [v.requerido('Escribe tu correo electrónico.'), v.email()],
      password: [v.requerido('Escribe tu contraseña.')],
    },
  );

  // Cuando aparece un error, se lleva el foco al mensaje para que se lea de inmediato.
  useEffect(() => {
    if (errorGeneral) alerta.current?.focus();
  }, [errorGeneral]);

  if (usuario) return <Navigate to={destinoSeguro(params.get('volver'))} replace />;

  return (
    <div className="contenedor flex justify-center py-12">
      <div className="tarjeta w-full max-w-lg p-8 sm:p-10">
        <div className="mb-8 flex flex-col items-center gap-3 text-center">
          <Logo className="size-16" />
          <h1 className="text-3xl">Acceso para personal</h1>
          <p className="text-tinta-suave">Ingresa con la cuenta que te asignó el administrador.</p>
        </div>

        {errorGeneral && (
          <div
            ref={alerta}
            tabIndex={-1}
            role="alert"
            className="mb-6 flex items-start gap-3 rounded-xl border-2 border-red-300 bg-red-50 p-4 text-red-900"
          >
            <LockKeyhole className="mt-0.5 size-6 shrink-0" aria-hidden />
            <p className="font-bold">{errorGeneral}</p>
          </div>
        )}

        <form
          noValidate
          className="space-y-5"
          onSubmit={form.alEnviar((valores) => {
            setErrorGeneral(null);
            login.mutate(
              { email: valores.email.trim(), password: valores.password },
              {
                onSuccess: ({ usuario: u }) => {
                  avisar.exito(`¡Bienvenido(a), ${u.nombre.split(' ')[0]}!`);
                  navigate(destinoSeguro(params.get('volver')), { replace: true });
                },
                onError: (e) => setErrorGeneral((e as ApiError).message),
              },
            );
          })}
        >
          <CampoTexto
            etiqueta="Correo electrónico"
            obligatorio
            type="email"
            autoComplete="username"
            inputMode="email"
            value={form.valores.email}
            onChange={(e) => form.cambiar('email', e.target.value)}
            {...form.campo('email')}
          />
          <div className="space-y-2">
            <CampoTexto
              etiqueta="Contraseña"
              obligatorio
              type={verPassword ? 'text' : 'password'}
              autoComplete="current-password"
              value={form.valores.password}
              onChange={(e) => form.cambiar('password', e.target.value)}
              {...form.campo('password')}
            />
            <button
              type="button"
              onClick={() => setVerPassword((x) => !x)}
              aria-pressed={verPassword}
              className="flex min-h-11 items-center gap-2 rounded-lg px-2 font-bold text-marca-800 hover:bg-marca-50"
            >
              {verPassword ? <EyeOff className="size-5" aria-hidden /> : <Eye className="size-5" aria-hidden />}
              {verPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}
            </button>
          </div>
          <Boton type="submit" tamano="grande" className="w-full" cargando={login.isPending} icono={<LogIn className="size-6" aria-hidden />}>
            Entrar
          </Boton>
        </form>
        <p className="mt-6 text-center text-sm text-tinta-suave">
          ¿Olvidaste tu contraseña? Pide al administrador que la restablezca.
        </p>
      </div>
    </div>
  );
}

import { ShieldAlert } from 'lucide-react';
import type { ReactNode } from 'react';
import { Navigate, useLocation } from 'react-router-dom';
import { useSesion } from '../api/hooks';
import type { Rol } from '../api/tipos';
import { BotonEnlace } from '../components/ui/Boton';
import { Cargando } from '../components/ui/Estados';

/**
 * Protege rutas del panel en el navegador. Es solo una mejora de experiencia:
 * la API rechaza igualmente cualquier petición sin sesión o sin el rol adecuado.
 */
export function RutaProtegida({ roles, children }: { roles?: Rol[]; children: ReactNode }) {
  const { data: usuario, isLoading } = useSesion();
  const location = useLocation();

  if (isLoading) return <Cargando texto="Verificando tu sesión…" />;
  if (!usuario) {
    return <Navigate to={`/entrar?volver=${encodeURIComponent(location.pathname + location.search)}`} replace />;
  }
  if (roles && !roles.includes(usuario.rol)) {
    return (
      <div className="tarjeta mx-auto mt-8 flex max-w-xl flex-col items-center gap-4 p-10 text-center">
        <ShieldAlert className="size-14 text-amber-600" aria-hidden />
        <h1 className="text-2xl">No tienes acceso a esta sección</h1>
        <p className="text-tinta-suave">Tu rol no permite ver esta página. Si crees que es un error, contacta al administrador.</p>
        <BotonEnlace to="/panel">Volver al panel</BotonEnlace>
      </div>
    );
  }
  return <>{children}</>;
}

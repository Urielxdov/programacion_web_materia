import clsx from 'clsx';
import {
  BarChart3,
  Boxes,
  Globe,
  LayoutDashboard,
  LogOut,
  Megaphone,
  Menu,
  PlusCircle,
  Tags,
  Truck,
  Users,
  X,
} from 'lucide-react';
import { useEffect } from 'react';
import type { ReactNode } from 'react';
import { Link, NavLink, Outlet, useLocation, useNavigate } from 'react-router-dom';
import { useLogout, useSesion } from '../../api/hooks';
import type { Rol } from '../../api/tipos';
import { useTeclaEscape } from '../../hooks/dom';
import { ROL } from '../../lib/formato';
import { avisar } from '../../stores/avisos.store';
import { useUiStore } from '../../stores/ui.store';
import { Logo } from '../ilustraciones/Ilustraciones';
import { ControlesLectura } from './ControlesLectura';

interface ElementoMenu {
  a: string;
  etiqueta: string;
  icono: ReactNode;
  roles?: Rol[];
  fin?: boolean;
}

const GESTION: Rol[] = ['RESPONSABLE', 'ADMIN'];

/** El menú se arma según el rol; el servidor de todos modos valida cada acción. */
const MENU: { grupo: string; elementos: ElementoMenu[] }[] = [
  {
    grupo: 'Inventario',
    elementos: [
      { a: '/panel', etiqueta: 'Panel principal', icono: <LayoutDashboard className="size-6" aria-hidden />, fin: true },
      { a: '/panel/equipos', etiqueta: 'Equipos', icono: <Boxes className="size-6" aria-hidden />, fin: true },
      { a: '/panel/equipos/nuevo', etiqueta: 'Registrar equipo', icono: <PlusCircle className="size-6" aria-hidden /> },
    ],
  },
  {
    grupo: 'Gestión ambiental',
    elementos: [
      { a: '/panel/campanas', etiqueta: 'Campañas', icono: <Megaphone className="size-6" aria-hidden />, roles: GESTION },
      { a: '/panel/reportes', etiqueta: 'Reportes', icono: <BarChart3 className="size-6" aria-hidden />, roles: GESTION },
      { a: '/panel/gestores', etiqueta: 'Gestores', icono: <Truck className="size-6" aria-hidden />, roles: GESTION },
    ],
  },
  {
    grupo: 'Administración',
    elementos: [
      { a: '/panel/usuarios', etiqueta: 'Usuarios', icono: <Users className="size-6" aria-hidden />, roles: ['ADMIN'] },
      { a: '/panel/categorias', etiqueta: 'Categorías', icono: <Tags className="size-6" aria-hidden />, roles: ['ADMIN'] },
    ],
  },
];

function Navegacion({ rol }: { rol: Rol }) {
  return (
    <nav aria-label="Menú del panel" className="flex-1 space-y-6 overflow-y-auto px-3 py-4">
      {MENU.map((g) => {
        const visibles = g.elementos.filter((e) => !e.roles || e.roles.includes(rol));
        if (visibles.length === 0) return null;
        return (
          <div key={g.grupo}>
            <p className="px-3 pb-2 text-sm font-bold uppercase tracking-wide text-marca-200">{g.grupo}</p>
            <ul className="space-y-1">
              {visibles.map((e) => (
                <li key={e.a}>
                  <NavLink
                    to={e.a}
                    end={e.fin}
                    className={({ isActive }) =>
                      clsx(
                        'flex min-h-12 items-center gap-3 rounded-xl px-3 py-2.5 text-lg font-bold transition-colors',
                        isActive ? 'bg-white text-marca-900' : 'text-marca-50 hover:bg-marca-800',
                      )
                    }
                  >
                    {e.icono}
                    {e.etiqueta}
                  </NavLink>
                </li>
              ))}
            </ul>
          </div>
        );
      })}
      <Link
        to="/"
        className="flex min-h-12 items-center gap-3 rounded-xl px-3 py-2.5 text-lg font-bold text-marca-50 hover:bg-marca-800"
      >
        <Globe className="size-6" aria-hidden />
        Ver sitio público
      </Link>
    </nav>
  );
}

export function PrivateLayout() {
  const { data: usuario } = useSesion();
  const logout = useLogout();
  const navigate = useNavigate();
  const { pathname } = useLocation();
  const { menuAbierto, setMenuAbierto } = useUiStore();

  useEffect(() => setMenuAbierto(false), [pathname, setMenuAbierto]);
  useTeclaEscape(menuAbierto, () => setMenuAbierto(false));

  if (!usuario) return null;

  const salir = () =>
    logout.mutate(undefined, {
      onSettled: () => {
        avisar.info('Cerraste tu sesión', 'Gracias por cuidar los equipos de la institución.');
        navigate('/entrar', { replace: true });
      },
    });

  const barraLateral = (
    <div className="flex h-full flex-col bg-marca-900">
      <Link to="/panel" className="flex items-center gap-3 border-b border-marca-800 px-5 py-5 text-white">
        <Logo />
        <span className="text-xl font-bold">
          E-Waste <span className="text-marca-300">360°</span>
        </span>
      </Link>
      <Navegacion rol={usuario.rol} />
      <div className="border-t border-marca-800 p-4">
        <p className="font-bold text-white">{usuario.nombre}</p>
        <p className="text-sm text-marca-200">{ROL[usuario.rol].etiqueta}</p>
        <button
          type="button"
          onClick={salir}
          disabled={logout.isPending}
          className="mt-3 flex min-h-12 w-full items-center justify-center gap-2 rounded-xl border-2 border-marca-600 font-bold text-white hover:bg-marca-800"
        >
          <LogOut className="size-5" aria-hidden />
          Cerrar sesión
        </button>
      </div>
    </div>
  );

  return (
    <div className="min-h-screen lg:pl-72">
      <a href="#contenido" className="salto-contenido">
        Saltar al contenido principal
      </a>

      {/* Barra lateral fija en pantallas grandes */}
      <aside className="no-imprimir fixed inset-y-0 left-0 z-30 hidden w-72 lg:block">{barraLateral}</aside>

      {/* Menú deslizable en celulares y tabletas */}
      {menuAbierto && (
        <div className="no-imprimir fixed inset-0 z-40 lg:hidden">
          <button
            type="button"
            className="absolute inset-0 bg-tinta/60"
            aria-label="Cerrar menú"
            onClick={() => setMenuAbierto(false)}
          />
          <aside id="menu-panel" className="animar-aparecer absolute inset-y-0 left-0 w-80 max-w-[85%] shadow-2xl">
            {barraLateral}
          </aside>
        </div>
      )}

      <header className="no-imprimir sticky top-0 z-20 border-b border-tierra-200 bg-white/95 backdrop-blur">
        <div className="flex items-center justify-between gap-3 px-4 py-3 sm:px-6">
          <button
            type="button"
            onClick={() => setMenuAbierto(!menuAbierto)}
            aria-expanded={menuAbierto}
            aria-controls="menu-panel"
            className="flex min-h-12 items-center gap-2 rounded-xl border-2 border-tierra-300 px-4 font-bold lg:hidden"
          >
            {menuAbierto ? <X className="size-6" aria-hidden /> : <Menu className="size-6" aria-hidden />}
            Menú
          </button>
          <p className="hidden text-tinta-suave lg:block">
            Hola, <strong className="text-tinta">{usuario.nombre.split(' ')[0]}</strong>
          </p>
          <ControlesLectura compacto />
        </div>
      </header>

      <main id="contenido" tabIndex={-1} className="px-4 py-8 focus:outline-none sm:px-6 lg:px-10">
        <div className="mx-auto max-w-6xl">
          <Outlet />
        </div>
      </main>
    </div>
  );
}

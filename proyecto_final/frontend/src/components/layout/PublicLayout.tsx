import clsx from 'clsx';
import { LayoutDashboard, LogIn, Menu, X } from 'lucide-react';
import { useEffect } from 'react';
import { Link, NavLink, Outlet, useLocation } from 'react-router-dom';
import { useSesion } from '../../api/hooks';
import { useTeclaEscape } from '../../hooks/dom';
import { useUiStore } from '../../stores/ui.store';
import { Logo } from '../ilustraciones/Ilustraciones';
import { clasesBoton } from '../ui/Boton';
import { ControlesLectura } from './ControlesLectura';

const enlaces = [
  { a: '/', etiqueta: 'Inicio', fin: true },
  { a: '/guia', etiqueta: 'Cuida tus equipos' },
  { a: '/campanas', etiqueta: 'Campañas' },
];

export function PublicLayout() {
  const { data: usuario } = useSesion();
  const { menuAbierto, setMenuAbierto } = useUiStore();
  const { pathname } = useLocation();

  useEffect(() => setMenuAbierto(false), [pathname, setMenuAbierto]);
  useTeclaEscape(menuAbierto, () => setMenuAbierto(false));

  const botonCuenta = usuario ? (
    <Link to="/panel" className={clasesBoton('primario')}>
      <LayoutDashboard className="size-5" aria-hidden /> Ir a mi panel
    </Link>
  ) : (
    <Link to="/entrar" className={clasesBoton('primario')}>
      <LogIn className="size-5" aria-hidden /> Iniciar sesión
    </Link>
  );

  return (
    <div className="flex min-h-screen flex-col">
      <a href="#contenido" className="salto-contenido">
        Saltar al contenido principal
      </a>

      <div className="no-imprimir border-b border-tierra-200 bg-white">
        <div className="contenedor flex justify-end py-2">
          <ControlesLectura />
        </div>
      </div>

      <header className="no-imprimir sticky top-0 z-30 border-b border-tierra-200 bg-white/95 backdrop-blur">
        <div className="contenedor flex items-center justify-between gap-4 py-3">
          <Link to="/" className="flex items-center gap-3" aria-label="E-Waste 360°, ir al inicio">
            <Logo />
            <span className="text-xl font-bold leading-tight">
              E-Waste <span className="text-marca-700">360°</span>
              <span className="block text-sm font-normal text-tinta-suave">Residuos electrónicos con destino responsable</span>
            </span>
          </Link>

          <nav aria-label="Menú principal" className="hidden items-center gap-1 lg:flex">
            {enlaces.map((e) => (
              <NavLink
                key={e.a}
                to={e.a}
                end={e.fin}
                className={({ isActive }) =>
                  clsx(
                    'rounded-xl px-4 py-3 text-lg font-bold transition-colors',
                    isActive ? 'bg-marca-100 text-marca-900' : 'text-tinta hover:bg-tierra-100',
                  )
                }
              >
                {e.etiqueta}
              </NavLink>
            ))}
            <span className="ml-3">{botonCuenta}</span>
          </nav>

          <button
            type="button"
            className={clsx(clasesBoton('secundario'), 'lg:hidden')}
            aria-expanded={menuAbierto}
            aria-controls="menu-movil"
            onClick={() => setMenuAbierto(!menuAbierto)}
          >
            {menuAbierto ? <X className="size-6" aria-hidden /> : <Menu className="size-6" aria-hidden />}
            Menú
          </button>
        </div>

        {menuAbierto && (
          <nav id="menu-movil" aria-label="Menú principal" className="animar-aparecer border-t border-tierra-200 bg-white lg:hidden">
            <ul className="contenedor flex flex-col gap-1 py-4">
              {enlaces.map((e) => (
                <li key={e.a}>
                  <NavLink
                    to={e.a}
                    end={e.fin}
                    className={({ isActive }) =>
                      clsx('block rounded-xl px-4 py-4 text-xl font-bold', isActive ? 'bg-marca-100 text-marca-900' : 'hover:bg-tierra-100')
                    }
                  >
                    {e.etiqueta}
                  </NavLink>
                </li>
              ))}
              <li className="pt-2">{botonCuenta}</li>
            </ul>
          </nav>
        )}
      </header>

      <main id="contenido" tabIndex={-1} className="flex-1 focus:outline-none">
        <Outlet />
      </main>

      <footer className="no-imprimir mt-16 bg-marca-900 text-marca-50">
        <div className="contenedor grid gap-8 py-12 md:grid-cols-3">
          <div className="space-y-3">
            <div className="flex items-center gap-3">
              <Logo />
              <span className="text-xl font-bold">E-Waste 360°</span>
            </div>
            <p className="text-marca-100">
              Plataforma para dar seguimiento a los equipos electrónicos de la institución: repararlos, reutilizarlos y
              entregarlos de forma responsable.
            </p>
          </div>
          <div>
            <h2 className="mb-3 text-lg text-white">Secciones</h2>
            <ul className="space-y-2">
              {enlaces.map((e) => (
                <li key={e.a}>
                  <Link to={e.a} className="underline underline-offset-4 hover:text-white">
                    {e.etiqueta}
                  </Link>
                </li>
              ))}
              <li>
                <Link to="/entrar" className="underline underline-offset-4 hover:text-white">
                  Acceso para personal
                </Link>
              </li>
            </ul>
          </div>
          <div>
            <h2 className="mb-3 text-lg text-white">Privacidad</h2>
            <p className="text-marca-100">
              Solo pedimos los datos indispensables para registrar tu participación en una campaña. No compartimos tu
              información con terceros.
            </p>
          </div>
        </div>
        <p className="border-t border-marca-800 py-4 text-center text-sm text-marca-200">
          Proyecto integrador · Programación Web y Desarrollo Sustentable · {new Date().getFullYear()}
        </p>
      </footer>
    </div>
  );
}

import { useEffect, useRef } from 'react';
import { createBrowserRouter, Outlet, useLocation } from 'react-router-dom';
import { PrivateLayout } from '../components/layout/PrivateLayout';
import { PublicLayout } from '../components/layout/PublicLayout';
import { Avisos } from '../components/ui/Avisos';
import { CategoriasPage } from '../features/admin/CategoriasPage';
import { GestoresPage } from '../features/admin/GestoresPage';
import { UsuariosPage } from '../features/admin/UsuariosPage';
import { LoginPage } from '../features/auth/LoginPage';
import { CampanaGestionPage } from '../features/campanas/CampanaGestionPage';
import { CampanasGestionPage } from '../features/campanas/CampanasGestionPage';
import { EquipoDetallePage } from '../features/equipos/EquipoDetallePage';
import { EquipoEditarPage } from '../features/equipos/EquipoEditarPage';
import { EquipoNuevoPage } from '../features/equipos/EquipoNuevoPage';
import { EquiposPage } from '../features/equipos/EquiposPage';
import { PanelPage } from '../features/panel/PanelPage';
import { CampanaPublicaPage } from '../features/publico/CampanaPublicaPage';
import { CampanasPublicasPage } from '../features/publico/CampanasPublicasPage';
import { GuiaPage } from '../features/publico/GuiaPage';
import { InicioPage } from '../features/publico/InicioPage';
import { NoEncontradaPage } from '../features/publico/NoEncontradaPage';
import { ReportesPage } from '../features/reportes/ReportesPage';
import { RutaProtegida } from './RutaProtegida';

/**
 * Al cambiar de página: sube al inicio y lleva el foco al contenido principal,
 * para que quien usa lector de pantalla o teclado sepa que la página cambió.
 */
function Raiz() {
  const { pathname, hash } = useLocation();
  const primera = useRef(true);
  useEffect(() => {
    if (primera.current) {
      primera.current = false;
      return;
    }
    if (hash) {
      document.getElementById(hash.slice(1))?.scrollIntoView({ behavior: 'smooth' });
      return;
    }
    window.scrollTo({ top: 0 });
    document.getElementById('contenido')?.focus({ preventScroll: true });
  }, [pathname, hash]);

  return (
    <>
      <Outlet />
      <Avisos />
    </>
  );
}

const GESTION = ['RESPONSABLE', 'ADMIN'] as const;

export const router = createBrowserRouter([
  {
    element: <Raiz />,
    children: [
      {
        element: <PublicLayout />,
        children: [
          { path: '/', element: <InicioPage /> },
          { path: '/guia', element: <GuiaPage /> },
          { path: '/campanas', element: <CampanasPublicasPage /> },
          { path: '/campanas/:id', element: <CampanaPublicaPage /> },
          { path: '/entrar', element: <LoginPage /> },
          { path: '*', element: <NoEncontradaPage /> },
        ],
      },
      {
        path: '/panel',
        element: (
          <RutaProtegida>
            <PrivateLayout />
          </RutaProtegida>
        ),
        children: [
          { index: true, element: <PanelPage /> },
          { path: 'equipos', element: <EquiposPage /> },
          { path: 'equipos/nuevo', element: <EquipoNuevoPage /> },
          { path: 'equipos/:id', element: <EquipoDetallePage /> },
          { path: 'equipos/:id/editar', element: <EquipoEditarPage /> },
          { path: 'campanas', element: <RutaProtegida roles={[...GESTION]}><CampanasGestionPage /></RutaProtegida> },
          { path: 'campanas/nueva', element: <RutaProtegida roles={[...GESTION]}><CampanaGestionPage /></RutaProtegida> },
          { path: 'campanas/:id', element: <RutaProtegida roles={[...GESTION]}><CampanaGestionPage /></RutaProtegida> },
          { path: 'reportes', element: <RutaProtegida roles={[...GESTION]}><ReportesPage /></RutaProtegida> },
          { path: 'gestores', element: <RutaProtegida roles={[...GESTION]}><GestoresPage /></RutaProtegida> },
          { path: 'usuarios', element: <RutaProtegida roles={['ADMIN']}><UsuariosPage /></RutaProtegida> },
          { path: 'categorias', element: <RutaProtegida roles={['ADMIN']}><CategoriasPage /></RutaProtegida> },
          { path: '*', element: <NoEncontradaPage /> },
        ],
      },
    ],
  },
]);

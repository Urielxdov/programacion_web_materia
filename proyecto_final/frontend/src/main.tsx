import { MutationCache, QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { StrictMode } from 'react';
import { createRoot } from 'react-dom/client';
import { RouterProvider } from 'react-router-dom';
import { ApiError } from './api/cliente';
import { claves } from './api/hooks';
import { router } from './app/router';
import './index.css';
import './stores/ui.store';

const queryClient: QueryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 30_000,
      refetchOnWindowFocus: false,
      // No se reintenta si el problema es de permisos o datos (4xx).
      retry: (intentos, error) => !(error instanceof ApiError && error.status >= 400 && error.status < 500) && intentos < 2,
    },
  },
  mutationCache: new MutationCache({
    onError: (error) => {
      // Si la sesión expiró mientras se trabajaba, se refleja en toda la interfaz.
      if (error instanceof ApiError && error.status === 401) {
        queryClient.setQueryData(claves.sesion, null);
      }
    },
  }),
});

createRoot(document.getElementById('root')!).render(
  <StrictMode>
    <QueryClientProvider client={queryClient}>
      <RouterProvider router={router} />
    </QueryClientProvider>
  </StrictMode>,
);

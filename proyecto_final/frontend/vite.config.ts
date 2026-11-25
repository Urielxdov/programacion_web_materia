import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import { defineConfig } from 'vite';

// En desarrollo, /api se redirige al backend Nest: el navegador ve un solo origen
// (igual que en producción), así las cookies de sesión funcionan sin CORS.
export default defineConfig({
  plugins: [react(), tailwindcss()],
  build: {
    rollupOptions: {
      output: {
        // Separa librerías grandes para que el navegador las guarde en caché aparte.
        manualChunks: {
          react: ['react', 'react-dom', 'react-router-dom'],
          datos: ['@tanstack/react-query', 'zustand'],
          graficas: ['recharts'],
        },
      },
    },
  },
  server: {
    port: 5173,
    proxy: { '/api': { target: 'http://localhost:3000', changeOrigin: false } },
  },
});

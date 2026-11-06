import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// Built into ../../builds/react and served by Bootgly's Web\App\SPA handler under /react/.
export default defineConfig({
  base: '/react/',
  plugins: [react()],
  build: {
    outDir: '../../builds/react',
    emptyOutDir: true,
    assetsDir: 'assets',
  },
  server: {
    port: 5173,
    strictPort: true,
    // `npm run dev` talks to a running Bootgly project for the JSON API.
    proxy: {
      '/api': 'http://localhost:8092',
    },
  },
});

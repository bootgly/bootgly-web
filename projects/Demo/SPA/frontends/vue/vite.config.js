import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

// Built into ../../builds/vue and served by Bootgly's Web\App\SPA handler under /vue/.
export default defineConfig({
  base: '/vue/',
  plugins: [vue()],
  build: {
    outDir: '../../builds/vue',
    emptyOutDir: true,
    assetsDir: 'assets',
  },
  server: {
    port: 5174,
    strictPort: true,
    proxy: {
      '/api': 'http://localhost:8092',
    },
  },
});

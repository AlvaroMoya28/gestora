import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    // Puerto fijo: si está ocupado preferimos fallar antes que arrancar en otro
    // y dejar el CORS del backend apuntando a un origen equivocado.
    port: 8080,
    strictPort: true,
  },
})

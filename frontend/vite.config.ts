import tailwindcss from '@tailwindcss/vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'

const inDocker = process.env.DOCKER === '1'

export default defineConfig({
  plugins: [vue(), tailwindcss()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
  server: {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    ...(inDocker
      ? {
          allowedHosts: ['localhost', '127.0.0.1'],
          watch: {
            usePolling: true,
          },
          hmr: {
            host: 'localhost',
            clientPort: 8080,
          },
        }
      : {}),
  },
})

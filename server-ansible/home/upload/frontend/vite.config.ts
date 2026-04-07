import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
  plugins: [
    react(),
    tailwindcss(),
  ],
  server: {
    host: '0.0.0.0',
    port: 5173,
    proxy: {
      /*
      '/api': {
        target: process.env.VITE_API_URL || 'http://localhost:8080',
        changeOrigin: true,
        rewrite: (path) => path.replace(/^\/api/, ''),
      },
    */
      '/api/events/pop': {
        target: 'http://localhost:8080/api/bridge.php?cmd=bx:webhook-pop',
        changeOrigin: true,
        ignorePath: true // Чтобы не добавлять хвост URL к bridge.php
      },
      '/api/events': {
        target: 'http://localhost:8080/api/bridge.php?cmd=bx:webhook-reg',
        changeOrigin: true,
        ignorePath: true
      }
    },
  },
})

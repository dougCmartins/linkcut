import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vitest/config'
import vue from '@vitejs/plugin-vue'
import tailwindcss from '@tailwindcss/vite'
import VueI18nPlugin from '@intlify/unplugin-vue-i18n/vite'

export default defineConfig({
  plugins: [
    vue(),
    tailwindcss(),
    // Messages are compiled at build time, so the browser gets the small runtime-only vue-i18n (no message compiler).
    VueI18nPlugin({
      include: [
        fileURLToPath(new URL('./src/i18n/pt-BR.ts', import.meta.url)),
        fileURLToPath(new URL('./src/i18n/privacyPolicy.pt-BR.ts', import.meta.url)),
        fileURLToPath(new URL('./src/i18n/termsOfUse.pt-BR.ts', import.meta.url)),
      ],
      runtimeOnly: true,
      compositionOnly: true,
      fullInstall: false,
    }),
  ],
  resolve: {
    alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
  },
  server: {
    host: true,
    port: 5173,
    // Vite answers only known addresses. Cloudflare quick tunnels (a new *.trycloudflare.com each time) are allowed;
    // other ones go in VITE_ALLOWED_HOSTS (comma-separated).
    allowedHosts: ['.trycloudflare.com', ...(process.env.VITE_ALLOWED_HOSTS ?? '').split(',').filter(Boolean)],
    // The SPA calls the API on its own address (/api, /storage): the same setup works on this computer and on a
    // phone on the same Wi-Fi (http://<computer IP>:5173). In Docker the API is http://backend:8085.
    proxy: {
      '/api': process.env.VITE_PROXY_TARGET ?? 'http://localhost:8087',
      '/storage': process.env.VITE_PROXY_TARGET ?? 'http://localhost:8087',
    },
  },
  test: {
    environment: 'jsdom',
    globals: true,
    include: ['src/**/*.test.ts'],
  },
})

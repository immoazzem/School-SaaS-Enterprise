import { fileURLToPath } from 'node:url'

const i18nConfigPath = fileURLToPath(new URL('./i18n.config.ts', import.meta.url))

// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  ssr: false,
  devtools: { enabled: true },
  modules: [
    '@nuxtjs/tailwindcss',
    '@pinia/nuxt',
    '@nuxtjs/i18n',
    '@vite-pwa/nuxt',
  ],
  runtimeConfig: {
    public: {
      apiBase: process.env.NUXT_PUBLIC_API_BASE || 'http://127.0.0.1:8000/api',
    },
  },
  tailwindcss: {
    cssPath: '~/assets/css/main.css',
    exposeConfig: true,
  },
  i18n: {
    defaultLocale: 'en',
    detectBrowserLanguage: false,
    locales: [
      { code: 'en', language: 'en-US', name: 'English' },
      { code: 'bn', language: 'bn-BD', name: 'বাংলা' },
    ],
    strategy: 'no_prefix',
    vueI18n: i18nConfigPath,
  },
  pwa: {
    registerType: 'autoUpdate',
    manifest: {
      name: 'School SaaS Enterprise',
      short_name: 'School SaaS',
      description: 'School operations workspace for attendance, marks, finance, and academic workflows.',
      theme_color: '#2563eb',
      background_color: '#f8fafc',
      display: 'standalone',
      start_url: '/',
      icons: [
        {
          src: '/pwa-icon.svg',
          sizes: 'any',
          type: 'image/svg+xml',
          purpose: 'any maskable',
        },
      ],
    },
    workbox: {
      navigateFallback: '/',
      runtimeCaching: [
        {
          urlPattern: /\/schools\/.+\/(attendance|marks)/,
          handler: 'NetworkFirst',
          options: {
            cacheName: 'school-saas-critical-workspaces',
            cacheableResponse: {
              statuses: [0, 200],
            },
            expiration: {
              maxAgeSeconds: 60 * 60 * 24 * 7,
              maxEntries: 30,
            },
          },
        },
      ],
    },
    devOptions: {
      enabled: true,
      type: 'module',
    },
  },
})

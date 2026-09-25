// https://nuxt.com/docs/api/configuration/nuxt-config
export default defineNuxtConfig({
  compatibilityDate: '2025-07-15',
  devtools: { enabled: false },
  modules: ['@pinia/nuxt'],
  css: ['sweetalert2/dist/sweetalert2.min.css', '~/assets/css/main.css'],
  vite: {
    server: {
      watch: { usePolling: true, interval: 300 }
    }
  },
  watchers: {
    chokidar: { usePolling: true, interval: 300 }
  },
  runtimeConfig: {
    public: {
      apiBaseUrl: '',
      appVersion: '',
      appEnv: ''
    }
  }
})

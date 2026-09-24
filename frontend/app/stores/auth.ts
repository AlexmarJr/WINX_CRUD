import { defineStore } from 'pinia'

export interface AuthUser {
  id: string
  name: string
  email: string
}

interface LoginPayload {
  email: string
  password: string
  remember: boolean
}

interface RegisterPayload {
  company_name: string
  company_abbreviation: string
  name: string
  email: string
  password: string
  password_confirmation: string
}

function apiBaseUrl(): string {
  const configured = useRuntimeConfig().public.apiBaseUrl

  if (configured) {
    return configured.replace(/\/$/, '')
  }

  return `http://${window.location.hostname}:8000`
}

function xsrfToken(): string {
  const cookie = document.cookie.split('; ').find(value => value.startsWith('XSRF-TOKEN='))
  return cookie ? decodeURIComponent(cookie.substring('XSRF-TOKEN='.length)) : ''
}

async function initializeCsrf(): Promise<void> {
  await $fetch(`${apiBaseUrl()}/sanctum/csrf-cookie`, {
    credentials: 'include',
    headers: { Accept: 'application/json' }
  })
}

async function postAuth<T>(path: string, body?: object): Promise<T> {
  await initializeCsrf()

  return await $fetch<T>(`${apiBaseUrl()}${path}`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      Accept: 'application/json',
      'X-XSRF-TOKEN': xsrfToken()
    },
    body
  })
}

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as AuthUser | null,
    initialized: false
  }),
  actions: {
    async register(payload: RegisterPayload): Promise<void> {
      this.user = await postAuth<AuthUser>('/api/register', payload)
      this.initialized = true
    },
    async login(payload: LoginPayload): Promise<void> {
      this.user = await postAuth<AuthUser>('/api/login', payload)
      this.initialized = true
    },
    async refresh(): Promise<boolean> {
      try {
        this.user = await $fetch<AuthUser>(`${apiBaseUrl()}/api/user`, {
          credentials: 'include',
          headers: { Accept: 'application/json' }
        })
      } catch {
        this.user = null
      }

      this.initialized = true
      return this.user !== null
    },
    async logout(): Promise<void> {
      await postAuth<void>('/api/logout')
      this.user = null
      this.initialized = true
    }
  }
})

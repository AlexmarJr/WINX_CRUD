import { defineStore } from 'pinia'
import { apiGet, apiWrite } from '~/utils/api'
import { useInventoryStore } from '~/stores/inventory'
import { useUsersStore } from '~/stores/users'

export interface AuthUser {
  id: string
  name: string
  email: string
  role: string
  status: 'active' | 'inactive'
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

export const useAuthStore = defineStore('auth', {
  state: () => ({
    user: null as AuthUser | null,
    initialized: false
  }),
  actions: {
    async register(payload: RegisterPayload): Promise<void> {
      this.user = await apiWrite<AuthUser>('/api/v1/register', 'POST', payload)
      this.initialized = true
    },
    async acceptInvite(token: string, payload: { name: string, password: string, password_confirmation: string }): Promise<void> {
      this.user = await apiWrite<AuthUser>(`/api/v1/invites/${encodeURIComponent(token)}/accept`, 'POST', payload)
      this.initialized = true
    },
    async login(payload: LoginPayload): Promise<void> {
      this.user = await apiWrite<AuthUser>('/api/v1/login', 'POST', payload)
      this.initialized = true
    },
    async refresh(): Promise<boolean> {
      try {
        this.user = await apiGet<AuthUser>('/api/v1/user')
      } catch {
        this.user = null
      }

      this.initialized = true
      return this.user !== null
    },
    async changeEmail(payload: { email: string, current_password: string }): Promise<void> {
      const response = await apiWrite<{ data: AuthUser }>('/api/v1/profile/email', 'PATCH', payload)
      this.user = response.data
    },
    async changePassword(payload: { current_password: string, password: string, password_confirmation: string }): Promise<void> {
      await apiWrite<void>('/api/v1/profile/password', 'PATCH', payload)
    },
    async logout(): Promise<void> {
      await apiWrite<void>('/api/v1/logout', 'POST')
      useInventoryStore().resetInventory()
      useUsersStore().resetUsers()
      this.user = null
      this.initialized = true
    }
  }
})

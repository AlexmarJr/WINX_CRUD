import { defineStore } from 'pinia'
import { apiErrorMessage, apiGet, apiWrite } from '~/utils/api'

export interface TeamUser {
  id: string
  name: string
  email: string
  role: string
  status: 'active' | 'inactive'
  created_at: string | null
  updated_at: string | null
}

interface PageResponse<T> {
  data: T[]
  meta: { current_page: number, last_page: number, total: number }
}

interface PaginationState {
  currentPage: number
  lastPage: number
  total: number
}

const emptyPagination = (): PaginationState => ({ currentPage: 1, lastPage: 1, total: 0 })
let usersRequest = 0
let usersAbortController: AbortController | undefined

export const useUsersStore = defineStore('users', {
  state: () => ({
    users: [] as TeamUser[],
    pagination: emptyPagination(),
    loading: false,
    error: ''
  }),
  actions: {
    cancelUsersRequest(): void {
      usersRequest += 1
      usersAbortController?.abort()
      usersAbortController = undefined
      this.loading = false
    },

    resetUsers(): void {
      this.cancelUsersRequest()
      this.$reset()
    },

    async updateUser(id: string, payload: { name: string, email: string, role: string, status: 'active' | 'inactive' }): Promise<TeamUser> {
      const response = await apiWrite<{ data: TeamUser }>('/api/v1/users/' + encodeURIComponent(id), 'PATCH', payload)
      this.users = this.users.map(user => user.id === id ? response.data : user)
      return response.data
    },

    async deleteUser(id: string): Promise<void> {
      await apiWrite<void>('/api/v1/users/' + encodeURIComponent(id), 'DELETE')
      this.users = this.users.filter(user => user.id !== id)
      this.pagination.total = Math.max(0, this.pagination.total - 1)
    },

    async loadUsers(page = 1, search = '', status = '', sortBy = '', sortDir: 'asc' | 'desc' = 'asc'): Promise<void> {
      this.cancelUsersRequest()
      const request = usersRequest
      const controller = new AbortController()
      usersAbortController = controller
      this.loading = true
      this.error = ''

      try {
        const response = await apiGet<PageResponse<TeamUser>>('/api/v1/users', {
          page,
          per_page: 20,
          ...(search ? { search } : {}),
          ...(status ? { status } : {}),
          ...(sortBy ? { sort_by: sortBy, sort_dir: sortDir } : {})
        }, controller.signal)
        if (request !== usersRequest) return
        this.users = response.data
        this.pagination = {
          currentPage: response.meta.current_page,
          lastPage: response.meta.last_page,
          total: response.meta.total
        }
      } catch (error) {
        if (request !== usersRequest || controller.signal.aborted) return
        this.users = []
        this.pagination = emptyPagination()
        this.error = apiErrorMessage(error, 'Não foi possível carregar os usuários.')
      } finally {
        if (request === usersRequest) {
          usersAbortController = undefined
          this.loading = false
        }
      }
    }
  }
})

<script setup lang="ts">
import type { TeamUser } from '~/stores/users'
import { apiErrorMessage } from '~/utils/api'
import { confirmDanger, showActionError, showSuccessToast } from '~/utils/confirmAction'

useHead({ title: 'Usuários · Winx' })

const users = useUsersStore()
const auth = useAuthStore()
const search = ref('')
const statusFilter = ref<'active' | 'inactive' | ''>('')
const page = ref(1)
type UserSort = 'name' | 'email' | 'role' | 'status'
const sortBy = ref<UserSort | null>(null)
const sortDir = ref<'asc' | 'desc'>('asc')
const selectedUser = ref<TeamUser | null>(null)
const editingUser = ref(false)
const updatingUser = ref(false)
const deletingUser = ref(false)
const editError = ref('')
const editForm = reactive({ name: '', email: '', role: 'employee', status: 'active' as 'active' | 'inactive' })
const canManageUsers = computed(() => auth.user?.role === 'admin' && auth.user.status === 'active')

let searchTimer: ReturnType<typeof setTimeout> | undefined

function loadPage(nextPage: number): void {
  page.value = nextPage
  void users.loadUsers(page.value, search.value.trim(), statusFilter.value, sortBy.value ?? '', sortDir.value)
}

watch(() => auth.user?.id, (id) => {
  if (!id || !import.meta.client) return
  users.resetUsers()
  loadPage(1)
}, { immediate: true })

watch(search, () => {
  if (!auth.user || !import.meta.client) return
  clearTimeout(searchTimer)
  users.cancelUsersRequest()
  searchTimer = setTimeout(() => loadPage(1), 500)
}, { flush: 'sync' })

watch(statusFilter, () => {
  if (!auth.user || !import.meta.client) return
  clearTimeout(searchTimer)
  loadPage(1)
})

onUnmounted(() => {
  clearTimeout(searchTimer)
  users.cancelUsersRequest()
})

function toggleSort(field: UserSort): void {
  sortDir.value = sortBy.value === field && sortDir.value === 'asc' ? 'desc' : 'asc'
  sortBy.value = field
  clearTimeout(searchTimer)
  loadPage(1)
}

function sortIcon(field: UserSort): 'sort' | 'sortUp' | 'sortDown' {
  if (sortBy.value !== field) return 'sort'
  return sortDir.value === 'asc' ? 'sortUp' : 'sortDown'
}

function ariaSort(field: UserSort): 'none' | 'ascending' | 'descending' {
  if (sortBy.value !== field) return 'none'
  return sortDir.value === 'asc' ? 'ascending' : 'descending'
}

function roleLabel(role: string): string {
  if (role === 'admin') return 'Administrador'
  if (role === 'employee') return 'Funcionário'
  return 'Usuário'
}

function openUserDetails(user: TeamUser): void {
  selectedUser.value = user
  editingUser.value = false
  editError.value = ''
}

function closeUserDetails(): void {
  selectedUser.value = null
  editingUser.value = false
  editError.value = ''
}

function startUserEdit(): void {
  if (!selectedUser.value || !canManageUsers.value) return

  Object.assign(editForm, {
    name: selectedUser.value.name,
    email: selectedUser.value.email,
    role: selectedUser.value.role === 'user' ? 'employee' : selectedUser.value.role,
    status: selectedUser.value.status
  })
  editError.value = ''
  editingUser.value = true
}

async function saveUserUpdate(): Promise<void> {
  if (!selectedUser.value || updatingUser.value) return

  const id = selectedUser.value.id
  updatingUser.value = true
  editError.value = ''

  try {
    const updated = await users.updateUser(id, {
      name: editForm.name.trim(),
      email: editForm.email.trim(),
      role: editForm.role,
      status: editForm.status
    })
    selectedUser.value = updated
    editingUser.value = false
    if (id === auth.user?.id) await auth.refresh()
    loadPage(page.value)
    void showSuccessToast('Usuário atualizado com sucesso.')
  } catch (error) {
    editError.value = apiErrorMessage(error, 'Não foi possível atualizar o usuário.')
  } finally {
    updatingUser.value = false
  }
}

async function deleteSelectedUser(): Promise<void> {
  const user = selectedUser.value
  if (!user || deletingUser.value || user.id === auth.user?.id) return

  deletingUser.value = true
  closeUserDetails()
  await nextTick()

  try {
    const confirmed = await confirmDanger({
      title: 'Excluir usuário?',
      text: user.name + ' perderá o acesso à plataforma.',
      confirmText: 'Excluir usuário'
    })
    if (!confirmed) {
      openUserDetails(user)
      return
    }

    await users.deleteUser(user.id)
    loadPage(users.users.length === 0 && page.value > 1 ? page.value - 1 : page.value)
    void showSuccessToast('Usuário excluído com sucesso.')
  } catch (error) {
    await showActionError('Não foi possível excluir o usuário', apiErrorMessage(error, 'Tente novamente.'))
    openUserDetails(user)
  } finally {
    deletingUser.value = false
  }
}

const dateFormatter = new Intl.DateTimeFormat('pt-BR', { dateStyle: 'medium' })

function formatDate(value: string | null): string {
  return value ? dateFormatter.format(new Date(value)) : '—'
}
</script>

<template>
  <DashboardShell fit-viewport inventory-viewport>
    <div class="inventory-page">
      <div class="inventory-page-heading">
        <h1>Usuários</h1>
      </div>

      <section class="inventory-panel" aria-label="Lista de usuários">
        <div class="inventory-toolbar inventory-toolbar-categories">
          <label class="inventory-search"><span>Buscar usuário</span><input v-model.trim="search" type="search" placeholder="Nome ou e-mail"></label>
          <label class="inventory-filter">
            <span>Status</span>
            <select v-model="statusFilter">
              <option value="">Todos</option>
              <option value="active">Ativos</option>
              <option value="inactive">Inativos</option>
            </select>
          </label>
          <span class="inventory-toolbar-count">{{ users.pagination.total }} {{ users.pagination.total === 1 ? 'usuário encontrado' : 'usuários encontrados' }}</span>
        </div>

        <div v-if="users.error" class="inventory-error" role="alert">{{ users.error }}</div>

        <div class="inventory-table-wrap">
          <table class="inventory-table users-table">
            <thead><tr>
              <th class="inventory-sort-header" :aria-sort="ariaSort('name')"><button class="inventory-sort-button" type="button" @click="toggleSort('name')">Nome <AppIcon :name="sortIcon('name')" aria-hidden="true" /></button></th>
              <th class="inventory-sort-header" :aria-sort="ariaSort('email')"><button class="inventory-sort-button" type="button" @click="toggleSort('email')">E-mail <AppIcon :name="sortIcon('email')" aria-hidden="true" /></button></th>
              <th class="inventory-sort-header" :aria-sort="ariaSort('role')"><button class="inventory-sort-button" type="button" @click="toggleSort('role')">Perfil <AppIcon :name="sortIcon('role')" aria-hidden="true" /></button></th>
              <th class="inventory-sort-header" :aria-sort="ariaSort('status')"><button class="inventory-sort-button" type="button" @click="toggleSort('status')">Status <AppIcon :name="sortIcon('status')" aria-hidden="true" /></button></th>
              <th class="inventory-actions-header">Ações</th>
            </tr></thead>
            <tbody>
              <tr v-for="user in users.users" :key="user.id">
                <td><strong>{{ user.name }}</strong><small v-if="user.id === auth.user?.id">Você</small></td>
                <td>{{ user.email }}</td>
                <td>{{ roleLabel(user.role) }}</td>
                <td><span class="inventory-status" :class="{ 'inventory-status-inactive': user.status === 'inactive' }">{{ user.status === 'active' ? 'Ativo' : 'Inativo' }}</span></td>
                <td><div class="inventory-row-actions"><button type="button" @click="openUserDetails(user)"><AppIcon name="eye" aria-hidden="true" /> Abrir</button></div></td>
              </tr>
              <tr v-if="users.loading"><td colspan="5" class="inventory-empty">Carregando usuários...</td></tr>
              <tr v-else-if="!users.users.length"><td colspan="5" class="inventory-empty">Nenhum usuário encontrado.</td></tr>
            </tbody>
          </table>
        </div>
        <div class="inventory-panel-footer"><span>{{ users.pagination.total }} usuário(s)</span><div class="inventory-pagination"><button type="button" :disabled="page <= 1 || users.loading" @click="loadPage(page - 1)">Anterior</button><span>{{ page }} / {{ users.pagination.lastPage }}</span><button type="button" :disabled="page >= users.pagination.lastPage || users.loading" @click="loadPage(page + 1)">Próxima</button></div></div>
      </section>
    </div>

    <InventoryModal :open="Boolean(selectedUser)" :title="selectedUser?.name ?? 'Usuário'" :eyebrow="editingUser ? 'Editar usuário' : 'Detalhes do usuário'" @close="closeUserDetails">
      <form v-if="selectedUser && editingUser" id="edit-user-form" class="inventory-form" @submit.prevent="saveUserUpdate">
        <label>Nome <input v-model.trim="editForm.name" type="text" maxlength="255" required></label>
        <label>E-mail <input v-model.trim="editForm.email" type="email" maxlength="255" :disabled="selectedUser.id === auth.user?.id" required></label>
        <label>Perfil <select v-model="editForm.role" :disabled="selectedUser.id === auth.user?.id"><option value="employee">Funcionário</option><option value="admin">Administrador</option></select></label>
        <label>Status <select v-model="editForm.status" :disabled="selectedUser.id === auth.user?.id"><option value="active">Ativo</option><option value="inactive">Inativo</option></select></label>
        <p v-if="selectedUser.id === auth.user?.id" class="inventory-form-note">Altere seu e-mail no Perfil. Seu próprio perfil e status não podem ser alterados aqui.</p>
        <p v-if="editError" class="inventory-error" role="alert">{{ editError }}</p>
      </form>
      <div v-else-if="selectedUser" class="inventory-detail-sections">
        <dl class="inventory-detail-grid">
          <div><dt>Nome</dt><dd>{{ selectedUser.name }}</dd></div>
          <div><dt>Perfil</dt><dd>{{ roleLabel(selectedUser.role) }}</dd></div>
          <div class="inventory-detail-wide"><dt>E-mail</dt><dd>{{ selectedUser.email }}</dd></div>
          <div><dt>Status</dt><dd>{{ selectedUser.status === 'active' ? 'Ativo' : 'Inativo' }}</dd></div>
          <div><dt>Cadastro</dt><dd>{{ formatDate(selectedUser.created_at) }}</dd></div>
          <div><dt>Última atualização</dt><dd>{{ formatDate(selectedUser.updated_at) }}</dd></div>
        </dl>
      </div>
      <template #footer>
        <template v-if="editingUser">
          <button class="button button-outline" type="button" :disabled="updatingUser" @click="editingUser = false">Cancelar</button>
          <button class="button button-navy" type="submit" form="edit-user-form" :disabled="updatingUser">{{ updatingUser ? 'Salvando...' : 'Salvar alterações' }}</button>
        </template>
        <template v-else>
          <button v-if="canManageUsers && selectedUser?.id !== auth.user?.id" class="button button-danger" type="button" :disabled="deletingUser" @click="deleteSelectedUser"><AppIcon name="trash" aria-hidden="true" /> Excluir</button>
          <button class="button button-outline" type="button" @click="closeUserDetails">Fechar</button>
          <button v-if="canManageUsers" class="button button-navy" type="button" @click="startUserEdit"><AppIcon name="edit" aria-hidden="true" /> Editar</button>
        </template>
      </template>
    </InventoryModal>
  </DashboardShell>
</template>

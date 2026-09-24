<script setup lang="ts">
defineProps<{ fitViewport?: boolean, inventoryViewport?: boolean }>()

const auth = useAuthStore()
const loading = ref(true)
const signingOut = ref(false)
const userMenuOpen = ref(false)
const userMenu = ref<HTMLElement | null>(null)
const inviteOpen = ref(false)
const inviteForm = reactive({ email: '', role: 'employee' as 'employee' | 'admin' })
const preparedInvite = ref<{ email: string, role: 'employee' | 'admin' } | null>(null)

const initials = computed(() => {
  const names = auth.user?.name.trim().split(/\s+/).filter(Boolean) ?? []

  if (names.length > 1) {
    return `${names[0]?.charAt(0) ?? ''}${names.at(-1)?.charAt(0) ?? ''}`.toLocaleUpperCase('pt-BR')
  }

  return (names[0]?.slice(0, 2) || 'US').toLocaleUpperCase('pt-BR')
})

function closeMenuOnOutside(event: PointerEvent): void {
  if (userMenu.value && !userMenu.value.contains(event.target as Node)) {
    userMenuOpen.value = false
  }
}

function openInvite(): void {
  userMenuOpen.value = false
  Object.assign(inviteForm, { email: '', role: 'employee' })
  preparedInvite.value = null
  inviteOpen.value = true
}

function prepareInvite(): void {
  preparedInvite.value = { email: inviteForm.email.trim(), role: inviteForm.role }
}

onMounted(async () => {
  document.addEventListener('pointerdown', closeMenuOnOutside)

  if (!auth.user) {
    await auth.refresh()
  }

  loading.value = false

  if (!auth.user) {
    await navigateTo('/login')
  }
})

onUnmounted(() => {
  document.removeEventListener('pointerdown', closeMenuOnOutside)
})

async function logout(): Promise<void> {
  userMenuOpen.value = false
  signingOut.value = true

  try {
    await auth.logout()
    await navigateTo('/login')
  } finally {
    signingOut.value = false
  }
}
</script>

<template>
  <div class="dashboard-page" :class="{ 'dashboard-page-fit': fitViewport, 'dashboard-page-inventory': inventoryViewport }">
    <header class="site-header page-container">
      <BrandMark />
      <div v-if="auth.user" ref="userMenu" class="user-menu" @keydown.esc="userMenuOpen = false">
        <button class="user-menu-trigger" type="button" :aria-expanded="userMenuOpen" aria-controls="user-menu-panel" :aria-label="`Abrir menu de ${auth.user.name}`" @click="userMenuOpen = !userMenuOpen">
          <span class="user-avatar">{{ initials }}</span>
          <span class="user-menu-name">{{ auth.user.name }}</span>
          <AppIcon class="user-menu-chevron-icon" name="chevronDown" aria-hidden="true" />
        </button>

        <div v-show="userMenuOpen" id="user-menu-panel" class="user-menu-panel">
          <div class="user-menu-identity">
            <strong>{{ auth.user.name }}</strong>
            <span>{{ auth.user.email }}</span>
          </div>
          <nav aria-label="Menu do usuário">
            <NuxtLink to="/profile" @click="userMenuOpen = false"><AppIcon name="user" aria-hidden="true" /> Perfil</NuxtLink>
            <NuxtLink to="/settings" @click="userMenuOpen = false"><AppIcon name="settings" aria-hidden="true" /> Configurações</NuxtLink>
            <button type="button" @click="openInvite"><AppIcon name="invite" aria-hidden="true" /> Convidar usuário</button>
          </nav>
          <div class="user-menu-divider" />
          <button type="button" :disabled="signingOut" @click="logout">
            <AppIcon name="logout" aria-hidden="true" /> {{ signingOut ? 'Saindo...' : 'Sair da conta' }}
          </button>
        </div>
      </div>
    </header>

    <div class="dashboard-workspace page-container">
      <aside v-if="auth.user" class="dashboard-sidebar">
        <span class="sidebar-label">Navegação</span>
        <nav class="sidebar-nav" aria-label="Navegação principal">
          <NuxtLink to="/dashboard" exact-active-class="is-active">
            <AppIcon class="sidebar-icon" name="dashboard" aria-hidden="true" />
            Dashboard
          </NuxtLink>
          <NuxtLink to="/products" exact-active-class="is-active">
            <AppIcon class="sidebar-icon" name="products" aria-hidden="true" />
            Produtos
          </NuxtLink>
          <NuxtLink to="/categories" exact-active-class="is-active">
            <AppIcon class="sidebar-icon" name="categories" aria-hidden="true" />
            Categorias
          </NuxtLink>
        </nav>
        <div class="sidebar-footer">Versão 0.1.0</div>
      </aside>

      <main class="dashboard-main">
        <div v-if="loading" class="dashboard-card">Carregando sua conta...</div>
        <slot v-else-if="auth.user" />
      </main>
    </div>

    <InventoryModal :open="inviteOpen" title="Convidar usuário" eyebrow="Equipe" @close="inviteOpen = false">
      <form v-if="!preparedInvite" id="invite-user-form" class="inventory-form" @submit.prevent="prepareInvite">
        <label>E-mail <input v-model.trim="inviteForm.email" type="email" autocomplete="email" placeholder="nome@empresa.com" required></label>
        <label>Papel <select v-model="inviteForm.role" required><option value="employee">Funcionário</option><option value="admin">Admin</option></select></label>
        <p class="inventory-form-note">O envio do convite será ativado quando a API de convites estiver disponível.</p>
      </form>
      <p v-else class="inventory-confirmation" role="status">
        Convite para {{ preparedInvite.email }} ({{ preparedInvite.role === 'admin' ? 'Admin' : 'Funcionário' }}) preparado. Nenhum e-mail foi enviado.
      </p>
      <template #footer>
        <button v-if="preparedInvite" class="button button-navy" type="button" @click="inviteOpen = false">Fechar</button>
        <template v-else><button class="button button-outline" type="button" @click="inviteOpen = false">Cancelar</button><button class="button button-navy" type="submit" form="invite-user-form">Preparar convite</button></template>
      </template>
    </InventoryModal>
  </div>
</template>

<script setup lang="ts">
import { apiErrorMessage, apiWrite } from '~/utils/api'

defineProps<{ fitViewport?: boolean, inventoryViewport?: boolean }>()

type CreatedInvite = { email: string, invite_url: string, company_name: string | null }

const auth = useAuthStore()
const appVersion = useRuntimeConfig().public.appVersion
const loading = ref(true)
const isLocal = useRuntimeConfig().public.appEnv === 'local'
const signingOut = ref(false)
const userMenuOpen = ref(false)
const userMenu = ref<HTMLElement | null>(null)
const inviteOpen = ref(false)
const inviteForm = reactive({ email: '', role: 'employee' as 'employee' | 'admin' })
const sentInvite = ref<CreatedInvite | null>(null)
const sendingInvite = ref(false)
const inviteError = ref('')
const linkCopied = ref(false)
const copyError = ref('')

const whatsappInviteUrl = computed(() => {
  if (!sentInvite.value) return ''

  const company = sentInvite.value.company_name
  const message = `Olá! Você recebeu um convite para acessar a Winx${company ? ` com a equipe da ${company}` : ''}. Crie sua conta pelo link: ${sentInvite.value.invite_url}`

  return `https://wa.me/?text=${encodeURIComponent(message)}`
})

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
  sentInvite.value = null
  inviteError.value = ''
  linkCopied.value = false
  copyError.value = ''
  inviteOpen.value = true
}

async function copyInviteLink(): Promise<void> {
  if (!sentInvite.value) return

  copyError.value = ''

  try {
    await navigator.clipboard.writeText(sentInvite.value.invite_url)
    linkCopied.value = true
  } catch {
    copyError.value = 'Não foi possível copiar o link. Selecione e copie o endereço acima.'
  }
}

async function sendInvite(): Promise<void> {
  inviteError.value = ''
  sendingInvite.value = true

  try {
    const response = await apiWrite<{ data: CreatedInvite }>('/api/v1/invites', 'POST', inviteForm)
    sentInvite.value = response.data
  } catch (error) {
    inviteError.value = apiErrorMessage(error, 'Não foi possível enviar o convite. Tente novamente.')
  } finally {
    sendingInvite.value = false
  }
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
      <div v-if="auth.user" class="production-link-slot">
        <a v-if="isLocal" class="production-link" href="https://winx.930f6cf3.sslip.io/" target="_blank" rel="noopener noreferrer">App em produção</a>
      </div>
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
          <NuxtLink to="/users" exact-active-class="is-active">
            <AppIcon class="sidebar-icon" name="users" aria-hidden="true" />
            Usuários
          </NuxtLink>
        </nav>
        <div class="sidebar-footer">Versão {{ appVersion }}</div>
      </aside>

      <main class="dashboard-main">
        <div v-if="loading" class="dashboard-card">Carregando sua conta...</div>
        <slot v-else-if="auth.user" />
      </main>
    </div>

    <AiChat />

    <InventoryModal :open="inviteOpen" title="Convidar usuário" eyebrow="Equipe" @close="inviteOpen = false">
      <form v-if="!sentInvite" id="invite-user-form" class="inventory-form" @submit.prevent="sendInvite">
        <label>E-mail <input v-model.trim="inviteForm.email" type="email" autocomplete="email" placeholder="nome@empresa.com" required></label>
        <label>Papel <select v-model="inviteForm.role" required><option value="employee">Funcionário</option><option value="admin">Admin</option></select></label>
        <p v-if="inviteError" class="form-alert" role="alert">{{ inviteError }}</p>
      </form>
      <div v-else class="invite-success">
        <p class="inventory-confirmation" role="status">
          Um email foi enviado para <strong>{{ sentInvite.email }}</strong>.<br>
          Caso não encontre o convite, confira também a caixa de spam.
        </p>
        <div class="invite-link-group">
          <span class="invite-link-label">Link do convite</span>
          <a class="invite-link" :href="sentInvite.invite_url" target="_blank" rel="noopener noreferrer">{{ sentInvite.invite_url }}</a>
        </div>
        <div class="invite-share-actions">
          <button class="button button-outline" type="button" @click="copyInviteLink"><AppIcon name="copy" aria-hidden="true" /> {{ linkCopied ? 'Copiado!' : 'Copiar link' }}</button>
          <a class="button button-navy" :href="whatsappInviteUrl" target="_blank" rel="noopener noreferrer"><AppIcon name="message" aria-hidden="true" /> Enviar pelo WhatsApp</a>
        </div>
        <p v-if="copyError" class="form-alert" role="alert">{{ copyError }}</p>
      </div>
      <template #footer>
        <button v-if="sentInvite" class="button button-navy" type="button" @click="inviteOpen = false">Fechar</button>
        <template v-else><button class="button button-outline" type="button" :disabled="sendingInvite" @click="inviteOpen = false">Cancelar</button><button class="button button-navy" type="submit" form="invite-user-form" :disabled="sendingInvite">{{ sendingInvite ? 'Enviando...' : 'Enviar convite' }}</button></template>
      </template>
    </InventoryModal>
  </div>
</template>

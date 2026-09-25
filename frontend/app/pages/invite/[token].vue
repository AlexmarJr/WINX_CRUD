<script setup lang="ts">
import { apiErrorMessage, apiGet } from '~/utils/api'

interface InviteDetails {
  data: { email: string, company_name: string, expires_at: string }
}

useHead({ title: 'Aceitar convite · Winx' })

const route = useRoute()
const auth = useAuthStore()
const token = String(route.params.token)
const invite = ref<InviteDetails['data'] | null>(null)
const loading = ref(true)
const pending = ref(false)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string>>({})
const form = reactive({ name: '', password: '', password_confirmation: '' })

onMounted(async () => {
  try {
    invite.value = (await apiGet<InviteDetails>(`/api/v1/invites/${encodeURIComponent(token)}`)).data
  } catch (error) {
    errorMessage.value = apiErrorMessage(error, 'Este convite não está disponível. Entre em contato com quem enviou o convite e peça um novo link.')
  } finally {
    loading.value = false
  }
})

async function submit(): Promise<void> {
  errorMessage.value = ''
  fieldErrors.value = {}
  pending.value = true

  try {
    await auth.acceptInvite(token, form)
    await navigateTo('/dashboard')
  } catch (error) {
    const response = error as { data?: { errors?: Record<string, string[]> } }

    for (const [field, messages] of Object.entries(response.data?.errors ?? {})) {
      fieldErrors.value[field] = messages[0] || 'Confira este campo.'
    }

    errorMessage.value = apiErrorMessage(error, 'Não foi possível aceitar o convite. Tente novamente.')
    if (fieldErrors.value.token) invite.value = null
  } finally {
    pending.value = false
  }
}
</script>

<template>
  <AuthShell>
    <div class="form-intro">
      <span class="eyebrow"><span class="eyebrow-dot" /> Convite para a equipe</span>
      <h2 v-if="!loading && !invite">Convite <em>indisponível.</em></h2>
      <h2 v-else>Seu espaço <em>espera por você.</em></h2>
      <p v-if="invite">Entre para {{ invite.company_name }} com o email {{ invite.email }}.</p>
    </div>

    <p v-if="loading">Carregando convite...</p>
    <div v-else-if="!invite" class="form-alert" role="alert">{{ errorMessage }}</div>
    <form v-else class="auth-form" @submit.prevent="submit">
      <div v-if="errorMessage" class="form-alert" role="alert">{{ errorMessage }}</div>

      <div class="form-field">
        <label for="invite-name">Nome completo</label>
        <input id="invite-name" v-model.trim="form.name" type="text" autocomplete="name" maxlength="255" required :aria-invalid="Boolean(fieldErrors.name)">
        <span v-if="fieldErrors.name" class="field-error">{{ fieldErrors.name }}</span>
      </div>

      <div class="form-field">
        <label for="invite-password">Senha</label>
        <input id="invite-password" v-model="form.password" type="password" autocomplete="new-password" required :aria-invalid="Boolean(fieldErrors.password)">
        <span v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password }}</span>
      </div>

      <div class="form-field">
        <label for="invite-password-confirmation">Confirmar senha</label>
        <input id="invite-password-confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" required>
      </div>

      <button class="button button-navy button-full" type="submit" :disabled="pending">
        {{ pending ? 'Criando conta...' : 'Aceitar convite' }} <AppIcon name="arrowRight" aria-hidden="true" />
      </button>
    </form>
  </AuthShell>
</template>

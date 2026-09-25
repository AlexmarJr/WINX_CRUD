<script setup lang="ts">
import { apiErrorMessage, apiWrite } from '~/utils/api'

useHead({ title: 'Redefinir senha · Winx' })

const route = useRoute()
const token = String(route.params.token)
const form = reactive({
  email: typeof route.query.email === 'string' ? route.query.email : '',
  password: '',
  password_confirmation: ''
})
const pending = ref(false)
const completed = ref(false)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string>>({})

async function submit(): Promise<void> {
  errorMessage.value = ''
  fieldErrors.value = {}
  pending.value = true

  try {
    await apiWrite('/api/v1/reset-password', 'POST', { ...form, token })
    completed.value = true
  } catch (error) {
    const response = error as { data?: { errors?: Record<string, string[]> } }

    for (const [field, messages] of Object.entries(response.data?.errors ?? {})) {
      fieldErrors.value[field] = messages[0] || 'Confira este campo.'
    }

    errorMessage.value = apiErrorMessage(error, 'Não foi possível redefinir a senha. Tente novamente.')
  } finally {
    pending.value = false
  }
}
</script>

<template>
  <AuthShell>
    <div class="form-intro">
      <span class="eyebrow"><span class="eyebrow-dot" /> Recuperar acesso</span>
      <h2>Crie sua nova <em>senha.</em></h2>
      <p>Escolha uma nova senha para voltar à sua conta.</p>
    </div>

    <template v-if="completed">
      <div class="form-alert form-alert-success" role="status">Senha redefinida com sucesso. Entre com sua nova senha.</div>
      <NuxtLink to="/login" class="button button-navy button-full">
        Entrar <AppIcon name="arrowRight" aria-hidden="true" />
      </NuxtLink>
    </template>
    <form v-else class="auth-form" @submit.prevent="submit">
      <div v-if="errorMessage" class="form-alert" role="alert">{{ errorMessage }}</div>

      <div class="form-field">
        <label for="reset-email">E-mail</label>
        <input id="reset-email" v-model.trim="form.email" type="email" name="email" autocomplete="email" required :aria-invalid="Boolean(fieldErrors.email)">
        <span v-if="fieldErrors.email" class="field-error">{{ fieldErrors.email }}</span>
      </div>

      <div class="form-field">
        <label for="reset-password">Nova senha</label>
        <input id="reset-password" v-model="form.password" type="password" name="password" autocomplete="new-password" required :aria-invalid="Boolean(fieldErrors.password)">
        <span v-if="fieldErrors.password" class="field-error">{{ fieldErrors.password }}</span>
      </div>

      <div class="form-field">
        <label for="reset-password-confirmation">Confirmar nova senha</label>
        <input id="reset-password-confirmation" v-model="form.password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>
      </div>

      <button class="button button-navy button-full" type="submit" :disabled="pending">
        {{ pending ? 'Salvando...' : 'Salvar nova senha' }} <AppIcon name="arrowRight" aria-hidden="true" />
      </button>
    </form>

    <p class="form-switch"><NuxtLink to="/login">Voltar para entrar</NuxtLink></p>
  </AuthShell>
</template>

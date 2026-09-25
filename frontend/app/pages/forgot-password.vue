<script setup lang="ts">
import { apiErrorMessage, apiWrite } from '~/utils/api'

useHead({ title: 'Recuperar senha · Winx' })

const email = ref('')
const pending = ref(false)
const sent = ref(false)
const errorMessage = ref('')

async function submit(): Promise<void> {
  errorMessage.value = ''
  pending.value = true

  try {
    await apiWrite('/api/v1/forgot-password', 'POST', { email: email.value })
    sent.value = true
  } catch (error) {
    errorMessage.value = apiErrorMessage(error, 'Não foi possível enviar o link agora. Tente novamente.')
  } finally {
    pending.value = false
  }
}
</script>

<template>
  <AuthShell>
    <div class="form-intro">
      <span class="eyebrow"><span class="eyebrow-dot" /> Recuperar acesso</span>
      <h2>Vamos recuperar sua <em>senha.</em></h2>
      <p>Informe seu e-mail para receber um link de redefinição.</p>
    </div>

    <div v-if="sent" class="form-alert form-alert-success" role="status">
      Se existir uma conta para {{ email }}, enviaremos um link para redefinir a senha. Confira também a pasta de spam.
    </div>
    <form v-else class="auth-form" @submit.prevent="submit">
      <div v-if="errorMessage" class="form-alert" role="alert">{{ errorMessage }}</div>

      <div class="form-field">
        <label for="forgot-email">E-mail</label>
        <input id="forgot-email" v-model.trim="email" type="email" name="email" placeholder="voce@empresa.com" autocomplete="email" required>
      </div>

      <button class="button button-navy button-full" type="submit" :disabled="pending">
        {{ pending ? 'Enviando...' : 'Enviar link' }} <AppIcon name="arrowRight" aria-hidden="true" />
      </button>
    </form>

    <p class="form-switch"><NuxtLink to="/login">Voltar para entrar</NuxtLink></p>
  </AuthShell>
</template>

<script setup lang="ts">
import { apiErrorMessage } from '~/utils/api'

useHead({ title: 'Entrar · Winx' })

const auth = useAuthStore()
const form = reactive({ email: '', password: '', remember: false })
const pending = ref(false)
const errorMessage = ref('')

async function submit(): Promise<void> {
  errorMessage.value = ''
  pending.value = true

  try {
    await auth.login(form)
    await navigateTo('/dashboard')
  } catch (error) {
    const response = error as { statusCode?: number }
    errorMessage.value = response.statusCode === 422
      ? apiErrorMessage(error, 'E-mail ou senha incorretos. Confira os dados e tente novamente.')
      : 'Não foi possível entrar agora. Tente novamente em instantes.'
  } finally {
    pending.value = false
  }
}
</script>

<template>
  <AuthShell>
    <div class="form-intro">
      <span class="eyebrow"><span class="eyebrow-dot" /> Bem-vindo de volta</span>
      <h2>Bom ter você <em>por aqui.</em></h2>
      <p>Entre na sua conta para continuar de onde parou.</p>
    </div>

    <form class="auth-form" @submit.prevent="submit">
      <div v-if="errorMessage" class="form-alert" role="alert">{{ errorMessage }}</div>

      <div class="form-field">
        <label for="login-email">E-mail</label>
        <input id="login-email" v-model.trim="form.email" type="email" name="email" placeholder="voce@empresa.com" autocomplete="email" required>
      </div>

      <div class="form-field">
        <label for="login-password">Senha</label>
        <input id="login-password" v-model="form.password" type="password" name="password" placeholder="Sua senha" autocomplete="current-password" required>
      </div>

      <div class="login-options">
        <label class="remember-option">
          <input v-model="form.remember" type="checkbox" name="remember">
          <span>Manter conectado</span>
        </label>
        <NuxtLink to="/forgot-password" class="back-link">Esqueceu a senha?</NuxtLink>
      </div>

      <button class="button button-navy button-full" type="submit" :disabled="pending">
        {{ pending ? 'Entrando...' : 'Entrar' }} <AppIcon name="arrowRight" aria-hidden="true" />
      </button>
    </form>

    <p class="form-switch">Ainda não tem uma conta? <NuxtLink to="/register">Criar conta</NuxtLink></p>
  </AuthShell>
</template>

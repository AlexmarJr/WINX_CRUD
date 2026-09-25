<script setup lang="ts">
import { apiErrorMessage } from '~/utils/api'

useHead({ title: 'Perfil · Winx' })

const auth = useAuthStore()
const emailForm = reactive({ email: '', current_password: '' })
const passwordForm = reactive({ current_password: '', password: '', password_confirmation: '' })
const savingEmail = ref(false)
const savingPassword = ref(false)
const emailError = ref('')
const passwordError = ref('')
const emailSuccess = ref('')
const passwordSuccess = ref('')

watch(() => auth.user?.email, email => {
  emailForm.email = email ?? ''
}, { immediate: true })

const passwordComparison = computed<'match' | 'mismatch' | null>(() => {
  if (!passwordForm.password_confirmation) return null
  return passwordForm.password === passwordForm.password_confirmation ? 'match' : 'mismatch'
})

async function saveEmail(): Promise<void> {
  if (savingEmail.value) return
  emailError.value = ''
  emailSuccess.value = ''
  savingEmail.value = true

  try {
    await auth.changeEmail({
      email: emailForm.email.trim(),
      current_password: emailForm.current_password
    })
    emailForm.current_password = ''
    emailSuccess.value = 'E-mail atualizado com sucesso.'
  } catch (error) {
    emailError.value = apiErrorMessage(error, 'Não foi possível atualizar o e-mail.')
  } finally {
    savingEmail.value = false
  }
}

async function savePassword(): Promise<void> {
  if (savingPassword.value || passwordComparison.value !== 'match') return
  passwordError.value = ''
  passwordSuccess.value = ''
  savingPassword.value = true

  try {
    await auth.changePassword({ ...passwordForm })
    Object.assign(passwordForm, { current_password: '', password: '', password_confirmation: '' })
    passwordSuccess.value = 'Senha atualizada com sucesso.'
  } catch (error) {
    passwordError.value = apiErrorMessage(error, 'Não foi possível atualizar a senha.')
  } finally {
    savingPassword.value = false
  }
}
</script>

<template>
  <DashboardShell>
    <div v-if="auth.user" class="profile-page">
      <section class="dashboard-card account-page-card">
        <span class="eyebrow"><span class="eyebrow-dot" /> Sua conta</span>
        <h1>Perfil</h1>
        <div class="account-info"><span>Nome</span><strong>{{ auth.user.name }}</strong></div>
        <div class="account-info"><span>E-mail</span><strong>{{ auth.user.email }}</strong></div>
      </section>

      <div class="profile-settings">
        <section class="dashboard-card profile-setting-card">
          <h2>Trocar e-mail</h2>
          <p>Atualize o endereço usado para acessar sua conta.</p>
          <form class="auth-form" @submit.prevent="saveEmail">
            <div class="form-field">
              <label for="profile-email">Novo e-mail</label>
              <input id="profile-email" v-model.trim="emailForm.email" type="email" autocomplete="email" maxlength="255" required>
            </div>
            <div class="form-field">
              <label for="profile-email-current-password">Senha atual</label>
              <input id="profile-email-current-password" v-model="emailForm.current_password" type="password" autocomplete="current-password" required>
            </div>
            <p v-if="emailError" class="form-alert" role="alert">{{ emailError }}</p>
            <p v-if="emailSuccess" class="form-alert form-alert-success" role="status">{{ emailSuccess }}</p>
            <button class="button button-navy button-full" type="submit" :disabled="savingEmail">
              {{ savingEmail ? 'Salvando...' : 'Salvar e-mail' }} <AppIcon name="arrowRight" aria-hidden="true" />
            </button>
          </form>
        </section>

        <section class="dashboard-card profile-setting-card">
          <h2>Trocar senha</h2>
          <p>Confirme sua senha atual e escolha uma nova.</p>
          <form class="auth-form" @submit.prevent="savePassword">
            <div class="form-field">
              <label for="profile-current-password">Senha atual</label>
              <input id="profile-current-password" v-model="passwordForm.current_password" type="password" autocomplete="current-password" required>
            </div>
            <div class="form-field">
              <label for="profile-new-password">Nova senha</label>
              <input id="profile-new-password" v-model="passwordForm.password" type="password" autocomplete="new-password" minlength="8" required>
            </div>
            <div class="form-field">
              <label for="profile-confirm-password">Repetir nova senha</label>
              <input id="profile-confirm-password" v-model="passwordForm.password_confirmation" type="password" autocomplete="new-password" required>
              <span v-if="passwordComparison" class="password-comparison" :class="passwordComparison" aria-live="polite">
                {{ passwordComparison === 'match' ? 'As senhas são iguais.' : 'As senhas não são iguais.' }}
              </span>
            </div>
            <p v-if="passwordError" class="form-alert" role="alert">{{ passwordError }}</p>
            <p v-if="passwordSuccess" class="form-alert form-alert-success" role="status">{{ passwordSuccess }}</p>
            <button class="button button-navy button-full" type="submit" :disabled="savingPassword || passwordComparison !== 'match'">
              {{ savingPassword ? 'Salvando...' : 'Salvar senha' }} <AppIcon name="arrowRight" aria-hidden="true" />
            </button>
          </form>
        </section>
      </div>
    </div>
  </DashboardShell>
</template>

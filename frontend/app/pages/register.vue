<script setup lang="ts">
useHead({ title: 'Criar conta · Winx' })

const auth = useAuthStore()
const form = reactive({ company_name: '', company_abbreviation: '', name: '', email: '', password: '', password_confirmation: '' })
const step = ref<1 | 2>(1)
const pending = ref(false)
const errorMessage = ref('')
const fieldErrors = ref<Record<string, string>>({})

function nextStep(): void {
  errorMessage.value = ''
  fieldErrors.value = {}
  step.value = 2
}

async function submit(): Promise<void> {
  errorMessage.value = ''
  fieldErrors.value = {}
  pending.value = true

  try {
    await auth.register(form)
    await navigateTo('/dashboard')
  } catch (error) {
    const response = error as { statusCode?: number, data?: { errors?: Record<string, string[]> } }

    if (response.statusCode === 422) {
      const errors = response.data?.errors || {}
      for (const [field, messages] of Object.entries(errors)) {
        fieldErrors.value[field] = messages[0] || 'Confira este campo.'
      }
      if (errors.company_name || errors.company_abbreviation) step.value = 1
      errorMessage.value = 'Confira os campos destacados e tente novamente.'
    } else {
      errorMessage.value = 'Não foi possível criar sua conta agora. Tente novamente em instantes.'
    }
  } finally {
    pending.value = false
  }
}
</script>

<template>
  <AuthShell>
    <div class="form-intro">
      <span class="eyebrow"><span class="eyebrow-dot" /> Etapa {{ step }} de 2</span>
      <h2 v-if="step === 1">Conte sobre sua <em>empresa.</em></h2>
      <h2 v-else>Agora, sobre <em>você.</em></h2>
      <p v-if="step === 1">Vamos criar o espaço da sua empresa primeiro.</p>
      <p v-else>Crie sua conta de administrador para começar.</p>
    </div>

    <form class="auth-form" @submit.prevent="step === 1 ? nextStep() : submit()">
      <div v-if="errorMessage" class="form-alert" role="alert">{{ errorMessage }}</div>

      <template v-if="step === 1">
        <div class="form-field">
          <label for="register-company-name">Nome da sua empresa</label>
          <input id="register-company-name" v-model.trim="form.company_name" type="text" name="company_name" placeholder="Ex.: Minha Empresa" autocomplete="organization" maxlength="255" required :aria-invalid="Boolean(fieldErrors.company_name)" :aria-describedby="fieldErrors.company_name ? 'company-name-error' : undefined">
          <span v-if="fieldErrors.company_name" id="company-name-error" class="field-error">{{ fieldErrors.company_name }}</span>
        </div>

        <div class="form-field">
          <label for="register-company-abbreviation">Abreviação da empresa</label>
          <input id="register-company-abbreviation" v-model.trim="form.company_abbreviation" type="text" name="company_abbreviation" placeholder="Ex.: ME" maxlength="50" required :aria-invalid="Boolean(fieldErrors.company_abbreviation)" :aria-describedby="fieldErrors.company_abbreviation ? 'company-abbreviation-error' : undefined">
          <span v-if="fieldErrors.company_abbreviation" id="company-abbreviation-error" class="field-error">{{ fieldErrors.company_abbreviation }}</span>
        </div>

        <button class="button button-navy button-full" type="submit">
          Próximo <AppIcon name="arrowRight" aria-hidden="true" />
        </button>
      </template>

      <template v-else>
        <div class="form-field">
          <label for="register-name">Nome completo</label>
          <input id="register-name" v-model.trim="form.name" type="text" name="name" placeholder="Como podemos chamar você?" autocomplete="name" required :aria-invalid="Boolean(fieldErrors.name)" :aria-describedby="fieldErrors.name ? 'name-error' : undefined">
          <span v-if="fieldErrors.name" id="name-error" class="field-error">{{ fieldErrors.name }}</span>
        </div>

        <div class="form-field">
          <label for="register-email">E-mail</label>
          <input id="register-email" v-model.trim="form.email" type="email" name="email" placeholder="voce@empresa.com" autocomplete="email" required :aria-invalid="Boolean(fieldErrors.email)" :aria-describedby="fieldErrors.email ? 'email-error' : undefined">
          <span v-if="fieldErrors.email" id="email-error" class="field-error">{{ fieldErrors.email }}</span>
        </div>

        <div class="form-row">
          <div class="form-field">
            <label for="register-password">Senha</label>
            <input id="register-password" v-model="form.password" type="password" name="password" placeholder="Sua senha" autocomplete="new-password" required :aria-invalid="Boolean(fieldErrors.password)" :aria-describedby="fieldErrors.password ? 'password-error' : undefined">
          </div>
          <div class="form-field">
            <label for="register-confirmation">Confirmar senha</label>
            <input id="register-confirmation" v-model="form.password_confirmation" type="password" name="password_confirmation" placeholder="Repita sua senha" autocomplete="new-password" required>
          </div>
        </div>
        <span v-if="fieldErrors.password" id="password-error" class="field-error">{{ fieldErrors.password }}</span>

        <button class="back-link" type="button" :disabled="pending" @click="step = 1"><AppIcon name="arrowLeft" aria-hidden="true" /> Voltar para empresa</button>
        <button class="button button-navy button-full" type="submit" :disabled="pending">
          {{ pending ? 'Criando conta...' : 'Criar minha conta' }} <AppIcon name="arrowRight" aria-hidden="true" />
        </button>
      </template>
    </form>

    <p class="form-switch">Já tem uma conta? <NuxtLink to="/login">Entrar</NuxtLink></p>
  </AuthShell>
</template>

<script setup lang="ts">
useHead({ title: 'Perfil · Winx' })

const auth = useAuthStore()
</script>

<template>
  <DashboardShell>
    <section v-if="auth.user" class="dashboard-card account-page-card">
      <span class="eyebrow"><span class="eyebrow-dot" /> Sua conta</span>
      <h1>Perfil</h1>
      <div class="account-info">
        <span>Nome</span>
        <strong>{{ auth.user.name }}</strong>
      </div>
      <div class="account-info">
        <span>E-mail</span>
        <strong>{{ auth.user.email }}</strong>
      </div>
    </section>
  </DashboardShell>
</template>

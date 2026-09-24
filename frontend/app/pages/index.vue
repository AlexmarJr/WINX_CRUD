<script setup lang="ts">
useHead({
  title: 'Winx · Pessoas no centro',
  meta: [{ name: 'description', content: 'Uma experiência para conectar pessoas, cultura e novas possibilidades.' }]
})
</script>

<template>
  <div class="home-page">
    <header class="site-header page-container">
      <BrandMark />
      <nav class="header-actions" aria-label="Acesso à conta">
        <NuxtLink to="/login" class="text-link">Entrar</NuxtLink>
        <NuxtLink to="/register" class="button button-small button-navy">Criar conta <AppIcon name="arrowUpRight" aria-hidden="true" /></NuxtLink>
      </nav>
    </header>

    <main>
      <section class="hero page-container">
        <div class="hero-copy">
          <span class="eyebrow"><span class="eyebrow-dot" /> Pessoas no centro de tudo</span>
          <h1><span class="underlined">Crescer</span> é melhor quando acontece <em>junto.</em></h1>
          <p>Um espaço para criar conexões, descobrir possibilidades e dar o próximo passo com mais confiança.</p>
          <div class="hero-actions">
            <NuxtLink to="/register" class="button button-navy">Começar agora <AppIcon name="arrowUpRight" aria-hidden="true" /></NuxtLink>
            <NuxtLink to="/login" class="button button-outline">Já tenho uma conta</NuxtLink>
          </div>
          <div class="hero-note"><span class="note-line" /> Feito para pessoas em movimento</div>
        </div>
        <div class="hero-visual" aria-hidden="true">
          <div class="shape shape-teal"><span class="shape-sparkle">✦</span></div>
          <div class="shape shape-yellow"><span class="shape-ring" /></div>
          <div class="shape shape-orange"><span class="shape-arc" /></div>
          <div class="shape shape-sky"><span class="shape-dots">● ● ●</span></div>
          <div class="shape shape-light"><span class="shape-cross">+</span></div>
          <div class="shape shape-navy"><span class="shape-caption">gente<br>move<br>o mundo.</span></div>
        </div>
      </section>

      <section class="welcome-band">
        <div class="page-container welcome-band-inner">
          <span>01 / Seu ponto de partida</span>
          <p>Boas ideias começam com gente. <strong>Vamos nessa?</strong></p>
          <NuxtLink to="/register" aria-label="Criar conta"><AppIcon name="arrowUpRight" aria-hidden="true" /></NuxtLink>
        </div>
      </section>
    </main>
  </div>
</template>

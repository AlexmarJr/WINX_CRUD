<template>
  <div class="auth-shell">
    <aside class="auth-aside">
      <BrandMark />
      <div class="auth-aside-copy">
        <span class="eyebrow eyebrow-light">Pessoas no centro</span>
        <h1>Uma nova forma de <em>evoluir juntos.</em></h1>
        <p>Conexões mais fortes começam com um primeiro passo.</p>
      </div>
      <div class="auth-mosaic" aria-hidden="true">
        <span class="mosaic-piece mosaic-teal" />
        <span class="mosaic-piece mosaic-yellow" />
        <span class="mosaic-piece mosaic-sky" />
        <span class="mosaic-piece mosaic-orange" />
      </div>
      <span class="aside-footnote">Winx · Gente e cultura em movimento</span>
    </aside>

    <main class="auth-main">
      <div class="auth-topbar">
        <BrandMark />
        <NuxtLink to="/" class="back-link">← Voltar ao início</NuxtLink>
      </div>
      <div class="auth-content">
        <slot />
      </div>
      <div class="auth-footer">Um espaço para começar. Mais possibilidades pela frente.</div>
    </main>
  </div>
</template>

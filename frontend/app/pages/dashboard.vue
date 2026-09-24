<script setup lang="ts">
useHead({ title: 'Minha área · Winx' })

const auth = useAuthStore()

const lowStockProducts = [
  { name: 'Mouse sem fio M185', category: 'Periféricos', quantity: 2, status: 'Crítico' },
  { name: 'Teclado mecânico K500', category: 'Periféricos', quantity: 3, status: 'Atenção' },
  { name: 'Monitor LED 24"', category: 'Monitores', quantity: 5, status: 'Atenção' },
  { name: 'Headset USB Pro', category: 'Áudio', quantity: 7, status: 'Atenção' },
  { name: 'Webcam Full HD', category: 'Vídeo', quantity: 8, status: 'Atenção' },
  { name: 'Cabo HDMI 2 m', category: 'Acessórios', quantity: 9, status: 'Atenção' },
  { name: 'Hub USB-C 6 portas', category: 'Acessórios', quantity: 10, status: 'Atenção' },
  { name: 'Suporte para notebook', category: 'Acessórios', quantity: 11, status: 'Atenção' },
  { name: 'Caixa de som compacta', category: 'Áudio', quantity: 12, status: 'Atenção' },
  { name: 'Adaptador de rede USB', category: 'Acessórios', quantity: 13, status: 'Atenção' }
]
</script>

<template>
  <DashboardShell fit-viewport>
    <div v-if="auth.user" class="dashboard-overview">
      <div class="overview-heading">
        <div>
          <span class="eyebrow"><span class="eyebrow-dot" /> Visão geral</span>
          <h1>Olá, <em>{{ auth.user.name.split(' ')[0] }}.</em></h1>
          <p>Resumo do estoque e itens que pedem atenção.</p>
        </div>
        <span class="sample-data-label">Dados de exemplo</span>
      </div>

      <section class="overview-cards" aria-label="Resumo do estoque">
        <article class="overview-card">
          <div class="overview-card-heading">
            <span class="overview-card-icon overview-card-icon-teal"><AppIcon name="products" aria-hidden="true" /></span>
            <div>
              <span class="overview-card-kicker">Estoque</span>
              <h2>Quantidade de produtos</h2>
            </div>
          </div>
          <div class="overview-card-metrics">
            <div>
              <strong>128</strong>
              <span>Produtos individuais</span>
            </div>
            <div>
              <strong>3.824</strong>
              <span>Unidades no total</span>
            </div>
          </div>
        </article>

        <article class="overview-card">
          <div class="overview-card-heading">
            <span class="overview-card-icon overview-card-icon-orange"><AppIcon name="money" aria-hidden="true" /></span>
            <div>
              <span class="overview-card-kicker">Valores</span>
              <h2>Valor dos produtos</h2>
            </div>
          </div>
          <div class="overview-card-metrics overview-card-metrics-money">
            <div>
              <strong>R$ 48.760,00</strong>
              <span>Compra total</span>
            </div>
            <div>
              <strong>R$ 79.940,00</strong>
              <span>Revenda total</span>
            </div>
          </div>
        </article>
      </section>

      <section class="stock-attention" aria-labelledby="stock-attention-title">
        <div class="stock-attention-heading">
          <span class="stock-attention-icon"><AppIcon name="warning" aria-hidden="true" /></span>
          <div>
            <span class="overview-card-kicker">Atenção</span>
            <h2 id="stock-attention-title">Produtos com menos estoque</h2>
          </div>
        </div>

        <ul class="stock-list">
          <li v-for="product in lowStockProducts" :key="product.name" class="stock-item">
            <div class="stock-product">
              <strong>{{ product.name }}</strong>
              <span>{{ product.category }}</span>
            </div>
            <div class="stock-quantity">
              <strong>{{ product.quantity }}</strong>
              <span>unidades</span>
            </div>
            <span class="stock-status" :class="{ 'stock-status-critical': product.status === 'Crítico' }">{{ product.status }}</span>
          </li>
        </ul>
      </section>
    </div>
  </DashboardShell>
</template>

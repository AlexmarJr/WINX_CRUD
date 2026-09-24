<script setup lang="ts">
useHead({ title: 'Minha área · Winx' })

const auth = useAuthStore()
const inventory = useInventoryStore()
const currency = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })
const integer = new Intl.NumberFormat('pt-BR')

watch(() => auth.user?.id, (id) => {
  if (id && import.meta.client) void inventory.loadSummary()
}, { immediate: true })
</script>

<template>
  <DashboardShell fit-viewport>
    <div v-if="auth.user" class="dashboard-overview">
      <div class="overview-heading">
        <div>
          <h1>Olá, <em>{{ auth.user.name.split(' ')[0] }}.</em></h1>
        </div>
      </div>

      <p v-if="inventory.summaryError" class="inventory-error" role="alert">{{ inventory.summaryError }}</p>

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
              <strong>{{ inventory.summary ? integer.format(inventory.summary.product_count) : '—' }}</strong>
              <span>Produtos individuais</span>
            </div>
            <div>
              <strong>{{ inventory.summary ? integer.format(inventory.summary.total_units) : '—' }}</strong>
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
              <strong>{{ inventory.summary ? currency.format(Number(inventory.summary.purchase_total)) : '—' }}</strong>
              <span>Compra total</span>
            </div>
            <div>
              <strong>{{ inventory.summary ? currency.format(Number(inventory.summary.resale_total)) : '—' }}</strong>
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
          <li v-for="product in inventory.summary?.low_stock ?? []" :key="product.id" class="stock-item">
            <div class="stock-product">
              <strong>{{ product.name }}</strong>
              <span>{{ product.category ?? 'Sem categoria' }}</span>
            </div>
            <div class="stock-quantity">
              <strong>{{ product.stock }}</strong>
              <span>unidades</span>
            </div>
            <span class="stock-status" :class="{ 'stock-status-critical': product.stock <= 3 }">{{ product.stock <= 3 ? 'Crítico' : 'Atenção' }}</span>
          </li>
          <li v-if="inventory.summaryLoading" class="stock-item">Carregando estoque...</li>
          <li v-else-if="inventory.summary && !inventory.summary.low_stock.length" class="stock-item">Nenhum produto com estoque baixo.</li>
        </ul>
      </section>
    </div>
  </DashboardShell>
</template>

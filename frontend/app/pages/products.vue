<script setup lang="ts">
import type { InventoryProduct } from '~/stores/inventory'

useHead({ title: 'Produtos · Winx' })

const inventory = useInventoryStore()
const search = ref('')
const categoryFilter = ref('')
const selectedProduct = ref<InventoryProduct | null>(null)
const productToDelete = ref<InventoryProduct | null>(null)
const creating = ref(false)
const form = reactive({ name: '', category_id: '', description: '', cost: '', price: '', stock: '0', status: 'active' as 'active' | 'inactive', image: '' })

const filteredProducts = computed(() => inventory.products.filter(product => {
  const matchesSearch = product.name.toLocaleLowerCase('pt-BR').includes(search.value.trim().toLocaleLowerCase('pt-BR'))
  return matchesSearch && (!categoryFilter.value || product.category_id === categoryFilter.value)
}))

const currency = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })

function updateMoney(field: 'cost' | 'price', event: Event): void {
  const input = event.target as HTMLInputElement
  const digits = input.value.replace(/\D/g, '').slice(0, 10)
  const formatted = digits ? currency.format(Number(digits) / 100) : ''

  form[field] = formatted
  input.value = formatted
}

function moneyValue(value: string): number {
  return Number(value.replace(/\D/g, '')) / 100
}

function categoryName(id: string): string {
  return inventory.categories.find(category => category.id === id)?.name ?? 'Sem categoria'
}

function openCreate(): void {
  Object.assign(form, { name: '', category_id: '', description: '', cost: '', price: '', stock: '0', status: 'active', image: '' })
  creating.value = true
}

function saveProduct(): void {
  inventory.addProduct({
    name: form.name.trim(),
    category_id: form.category_id,
    description: form.description.trim() || null,
    cost: form.cost === '' ? null : moneyValue(form.cost),
    price: moneyValue(form.price),
    stock: Number(form.stock),
    status: form.status,
    image: form.image.trim() || null,
    meta: null
  })
  creating.value = false
}

function deleteProduct(): void {
  if (productToDelete.value) inventory.removeProduct(productToDelete.value.id)
  productToDelete.value = null
}
</script>

<template>
  <DashboardShell fit-viewport inventory-viewport>
    <div class="inventory-page">
      <div class="inventory-page-heading">
        <div>
          <h1>Produtos</h1>
        </div>
        <div class="inventory-heading-actions">
          <span class="sample-data-label">Dados de exemplo</span>
          <button class="button button-navy" type="button" @click="openCreate">Novo produto <AppIcon name="plus" aria-hidden="true" /></button>
        </div>
      </div>

      <section class="inventory-panel" aria-label="Lista de produtos">
        <div class="inventory-toolbar">
          <label class="inventory-search">
            <span>Buscar produto</span>
            <input v-model.trim="search" type="search" placeholder="Nome do produto">
          </label>
          <label class="inventory-filter">
            <span>Categoria</span>
            <select v-model="categoryFilter">
              <option value="">Todas as categorias</option>
              <option v-for="category in inventory.categories" :key="category.id" :value="category.id">{{ category.name }}</option>
            </select>
          </label>
        </div>

        <div class="inventory-table-wrap">
          <table class="inventory-table products-table">
            <thead><tr><th>Produto</th><th>Categoria</th><th>Preço</th><th>Estoque</th><th>Status</th><th class="inventory-actions-header">Ações</th></tr></thead>
            <tbody>
              <tr v-for="product in filteredProducts" :key="product.id">
                <td><strong>{{ product.name }}</strong><small>{{ product.meta?.sku ?? 'Sem SKU' }}</small></td>
                <td>{{ categoryName(product.category_id) }}</td>
                <td>{{ currency.format(product.price) }}</td>
                <td><span class="inventory-stock" :class="{ 'inventory-stock-low': product.stock <= 5 }">{{ product.stock }} un.</span></td>
                <td><span class="inventory-status" :class="{ 'inventory-status-inactive': product.status === 'inactive' }">{{ product.status === 'active' ? 'Ativo' : 'Inativo' }}</span></td>
                <td class="inventory-actions-cell"><div class="inventory-row-actions"><button type="button" @click="selectedProduct = product"><AppIcon name="eye" aria-hidden="true" /> Abrir</button><button class="inventory-delete" type="button" :aria-label="`Excluir ${product.name}`" @click="productToDelete = product"><AppIcon name="trash" aria-hidden="true" /> Excluir</button></div></td>
              </tr>
              <tr v-if="!filteredProducts.length"><td colspan="6" class="inventory-empty">Nenhum produto encontrado.</td></tr>
            </tbody>
          </table>
        </div>
        <div class="inventory-panel-footer">{{ filteredProducts.length }} produto(s) exibido(s) · alterações locais de demonstração</div>
      </section>
    </div>

    <InventoryModal :open="Boolean(selectedProduct)" :title="selectedProduct?.name ?? 'Produto'" eyebrow="Detalhes do produto" @close="selectedProduct = null">
      <div v-if="selectedProduct" class="inventory-detail-sections">
        <dl class="inventory-detail-grid">
          <div><dt>Categoria</dt><dd>{{ categoryName(selectedProduct.category_id) }}</dd></div>
          <div><dt>Status</dt><dd>{{ selectedProduct.status === 'active' ? 'Ativo' : 'Inativo' }}</dd></div>
          <div class="inventory-detail-wide"><dt>Descrição</dt><dd>{{ selectedProduct.description || '—' }}</dd></div>
          <div><dt>Custo de compra</dt><dd>{{ selectedProduct.cost === null ? '—' : currency.format(selectedProduct.cost) }}</dd></div>
          <div><dt>Preço de revenda</dt><dd>{{ currency.format(selectedProduct.price) }}</dd></div>
          <div><dt>Estoque</dt><dd>{{ selectedProduct.stock }} unidades</dd></div>
          <div><dt>Imagem</dt><dd>{{ selectedProduct.image || '—' }}</dd></div>
        </dl>
      </div>
      <template #footer><button class="button button-outline" type="button" @click="selectedProduct = null">Fechar</button></template>
    </InventoryModal>

    <InventoryModal :open="creating" title="Novo produto" eyebrow="Catálogo" @close="creating = false">
      <form id="create-product-form" class="inventory-form" @submit.prevent="saveProduct">
        <label>Nome do produto <input v-model.trim="form.name" type="text" maxlength="255" required></label>
        <label>Categoria <select v-model="form.category_id" required><option value="" disabled>Selecione uma categoria</option><option v-for="category in inventory.categories" :key="category.id" :value="category.id">{{ category.name }}</option></select></label>
        <label>Descrição <textarea v-model.trim="form.description" rows="3" /></label>
        <div class="inventory-form-row"><label>Custo de compra <input :value="form.cost" type="text" inputmode="numeric" autocomplete="off" placeholder="R$ 0,00 (opcional)" @input="updateMoney('cost', $event)"></label><label>Preço de revenda <input :value="form.price" type="text" inputmode="numeric" autocomplete="off" placeholder="R$ 0,00" required @input="updateMoney('price', $event)"></label></div>
        <div class="inventory-form-row"><label>Estoque <input v-model="form.stock" type="number" min="0" step="1" required></label><label>Status <select v-model="form.status"><option value="active">Ativo</option><option value="inactive">Inativo</option></select></label></div>
        <label>Imagem (URL ou caminho) <input v-model.trim="form.image" type="text" placeholder="Opcional"></label>
        <p class="inventory-form-note">ID, tenancy, usuário e datas são preenchidos apenas na demonstração.</p>
      </form>
      <template #footer><button class="button button-outline" type="button" @click="creating = false">Cancelar</button><button class="button button-navy" type="submit" form="create-product-form">Adicionar produto</button></template>
    </InventoryModal>

    <InventoryModal :open="Boolean(productToDelete)" title="Excluir produto?" eyebrow="Confirmação" @close="productToDelete = null">
      <p class="inventory-confirmation">{{ productToDelete?.name }} será removido apenas desta lista de demonstração.</p>
      <template #footer><button class="button button-outline" type="button" @click="productToDelete = null">Cancelar</button><button class="button button-danger" type="button" @click="deleteProduct">Excluir produto</button></template>
    </InventoryModal>
  </DashboardShell>
</template>

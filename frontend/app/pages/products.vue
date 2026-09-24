<script setup lang="ts">
import type { InventoryProduct } from '~/stores/inventory'
import { apiErrorMessage } from '~/utils/api'

useHead({ title: 'Produtos · Winx' })

const FIRST_PAGE = 1
const inventory = useInventoryStore()
const auth = useAuthStore()
const search = ref('')
const categoryFilter = ref('')
const priceCeilingCents = ref(0)
const minPriceCents = ref(0)
const maxPriceCents = ref(0)
const priceRangeError = ref('')
const page = ref(FIRST_PAGE)
type ProductSort = 'name' | 'category' | 'price' | 'stock' | 'status'
const sortBy = ref<ProductSort | null>(null)
const sortDir = ref<'asc' | 'desc'>('asc')
const selectedProduct = ref<InventoryProduct | null>(null)
const editingProduct = ref(false)
const updatingProduct = ref(false)
const editError = ref('')
const productToDelete = ref<InventoryProduct | null>(null)
const creating = ref(false)
const saving = ref(false)
const deleting = ref(false)
const actionError = ref('')
const optionsError = ref('')
const form = reactive({ name: '', category_id: '', description: '', cost: '', price: '', stock: '0', status: 'active' as 'active' | 'inactive', image: '' })
const editForm = reactive({ name: '', category_id: '', description: '', cost: '', price: '', stock: '0', status: 'active' as 'active' | 'inactive', image: '' })

let filterTimer: ReturnType<typeof setTimeout> | undefined

function loadPage(nextPage: number): void {
  page.value = nextPage
  const minPrice = minPriceCents.value > 0 ? (minPriceCents.value / 100).toFixed(2) : ''
  const maxPrice = maxPriceCents.value < priceCeilingCents.value ? (maxPriceCents.value / 100).toFixed(2) : ''
  void inventory.loadProducts(page.value, search.value.trim(), categoryFilter.value, minPrice, maxPrice, sortBy.value ?? '', sortDir.value)
}

function refreshProducts(nextPage: number): void {
  clearTimeout(filterTimer)
  loadPage(nextPage)
  void loadPriceRange()
}

async function loadPriceRange(): Promise<void> {
  priceRangeError.value = ''
  try {
    const previousCeiling = priceCeilingCents.value
    const nextCeiling = Math.round(Number(await inventory.getProductMaxPrice()) * 100)
    priceCeilingCents.value = nextCeiling
    minPriceCents.value = Math.min(minPriceCents.value, nextCeiling)
    maxPriceCents.value = maxPriceCents.value === previousCeiling ? nextCeiling : Math.min(maxPriceCents.value, nextCeiling)
  } catch (error) {
    priceRangeError.value = apiErrorMessage(error, 'Não foi possível carregar a faixa de preços.')
  }
}

watch(() => auth.user?.id, (id) => {
  if (!id || !import.meta.client) return
  loadPage(FIRST_PAGE)
  void loadPriceRange()
  void inventory.loadCategoryOptions().catch((error: unknown) => {
    optionsError.value = apiErrorMessage(error, 'Não foi possível carregar as categorias.')
  })
  void inventory.loadActiveCategoryOptions().catch((error: unknown) => {
    optionsError.value = apiErrorMessage(error, 'Não foi possível carregar as categorias ativas.')
  })
}, { immediate: true })

watch([search, minPriceCents, maxPriceCents], () => {
  if (!auth.user || !import.meta.client) return
  clearTimeout(filterTimer)
  inventory.cancelProductsRequest()
  filterTimer = setTimeout(() => loadPage(FIRST_PAGE), 500)
}, { flush: 'sync' })

watch(categoryFilter, () => {
  if (!auth.user || !import.meta.client) return
  clearTimeout(filterTimer)
  loadPage(FIRST_PAGE)
})

onUnmounted(() => {
  clearTimeout(filterTimer)
  inventory.cancelProductsRequest()
})

function toggleSort(field: ProductSort): void {
  sortDir.value = sortBy.value === field && sortDir.value === 'asc' ? 'desc' : 'asc'
  sortBy.value = field
  clearTimeout(filterTimer)
  loadPage(FIRST_PAGE)
}

function sortIcon(field: ProductSort): 'sort' | 'sortUp' | 'sortDown' {
  if (sortBy.value !== field) return 'sort'
  return sortDir.value === 'asc' ? 'sortUp' : 'sortDown'
}

function ariaSort(field: ProductSort): 'none' | 'ascending' | 'descending' {
  if (sortBy.value !== field) return 'none'
  return sortDir.value === 'asc' ? 'ascending' : 'descending'
}

const currency = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })

const priceTrackStyle = computed(() => {
  const ceiling = priceCeilingCents.value || 1
  return {
    '--price-start': `${minPriceCents.value / ceiling * 100}%`,
    '--price-end': `${maxPriceCents.value / ceiling * 100}%`
  }
})

function updatePriceRange(field: 'min' | 'max', event: Event): void {
  const value = Number((event.target as HTMLInputElement).value)
  if (field === 'min') minPriceCents.value = Math.min(value, maxPriceCents.value)
  else maxPriceCents.value = Math.max(value, minPriceCents.value)
}

function updateMoney(field: 'cost' | 'price', event: Event, values: typeof form = form): void {
  const input = event.target as HTMLInputElement
  const digits = input.value.replace(/\D/g, '').slice(0, 10)
  const formatted = digits ? currency.format(Number(digits) / 100) : ''

  values[field] = formatted
  input.value = formatted
}

function moneyValue(value: string): number {
  return Number(value.replace(/\D/g, '')) / 100
}

function categoryName(id: string): string {
  return inventory.categoryOptions.find(category => category.id === id)?.name ?? 'Sem categoria'
}

function productInput(values: typeof form): Pick<InventoryProduct, 'category_id' | 'name' | 'description' | 'cost' | 'price' | 'stock' | 'status' | 'image'> {
  return {
    name: values.name.trim(),
    category_id: values.category_id,
    description: values.description.trim() || null,
    cost: values.cost === '' ? null : moneyValue(values.cost).toFixed(2),
    price: moneyValue(values.price).toFixed(2),
    stock: Number(values.stock),
    status: values.status,
    image: values.image.trim() || null
  }
}

function openProductDetails(product: InventoryProduct): void {
  selectedProduct.value = product
  editingProduct.value = false
  editError.value = ''
}

function closeProductDetails(): void {
  selectedProduct.value = null
  editingProduct.value = false
  editError.value = ''
}

function startProductEdit(): void {
  const product = selectedProduct.value
  if (!product) return

  Object.assign(editForm, {
    name: product.name,
    category_id: product.category_id,
    description: product.description ?? '',
    cost: product.cost === null ? '' : currency.format(Number(product.cost)),
    price: currency.format(Number(product.price)),
    stock: String(product.stock),
    status: product.status,
    image: product.image ?? ''
  })
  editError.value = ''
  editingProduct.value = true
}

function cancelProductEdit(): void {
  editingProduct.value = false
  editError.value = ''
}

async function saveProductUpdate(): Promise<void> {
  const product = selectedProduct.value
  if (!product) return

  updatingProduct.value = true
  editError.value = ''

  try {
    const updated = await inventory.updateProduct(product.id, productInput(editForm))
    if (selectedProduct.value?.id === product.id) {
      selectedProduct.value = updated
      editingProduct.value = false
    }
    refreshProducts(FIRST_PAGE)
  } catch (error) {
    editError.value = apiErrorMessage(error, 'Não foi possível atualizar o produto.')
  } finally {
    updatingProduct.value = false
  }
}

function openCreate(): void {
  Object.assign(form, { name: '', category_id: '', description: '', cost: '', price: '', stock: '0', status: 'active', image: '' })
  actionError.value = ''
  creating.value = true
}

async function saveProduct(): Promise<void> {
  saving.value = true
  actionError.value = ''

  try {
    await inventory.createProduct(productInput(form))
    creating.value = false
    refreshProducts(FIRST_PAGE)
  } catch (error) {
    actionError.value = apiErrorMessage(error, 'Não foi possível criar o produto.')
  } finally {
    saving.value = false
  }
}

async function deleteProduct(): Promise<void> {
  if (!productToDelete.value) return
  deleting.value = true
  actionError.value = ''

  try {
    await inventory.deleteProduct(productToDelete.value.id)
    productToDelete.value = null
    refreshProducts(page.value > FIRST_PAGE && inventory.products.length === 1 ? page.value - 1 : page.value)
  } catch (error) {
    actionError.value = apiErrorMessage(error, 'Não foi possível excluir o produto.')
  } finally {
    deleting.value = false
  }
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
          <button class="button button-navy" type="button" :disabled="!inventory.activeCategoryOptions.length" @click="openCreate">Novo produto <AppIcon name="plus" aria-hidden="true" /></button>
        </div>
      </div>

      <section class="inventory-panel" aria-label="Lista de produtos">
        <div class="inventory-toolbar inventory-toolbar-products">
          <label class="inventory-search">
            <span>Buscar produto</span>
            <input v-model.trim="search" type="search" placeholder="Nome do produto">
          </label>
          <label class="inventory-filter inventory-category-filter">
            <span>Categoria</span>
            <select v-model="categoryFilter">
              <option value="">Todas as categorias</option>
              <option v-for="category in inventory.categoryOptions" :key="category.id" :value="category.id">{{ category.name }}</option>
            </select>
          </label>
          <div class="inventory-price-range" role="group" aria-label="Faixa de preço">
            <div class="inventory-price-range-heading"><span>Faixa de preço</span><output>{{ currency.format(minPriceCents / 100) }} – {{ currency.format(maxPriceCents / 100) }}</output></div>
            <div class="inventory-price-range-track" :style="priceTrackStyle">
              <input type="range" :min="0" :max="priceCeilingCents" :value="minPriceCents" :disabled="priceCeilingCents === 0" aria-label="Preço mínimo" :aria-valuetext="currency.format(minPriceCents / 100)" @input="updatePriceRange('min', $event)">
              <input type="range" :min="0" :max="priceCeilingCents" :value="maxPriceCents" :disabled="priceCeilingCents === 0" aria-label="Preço máximo" :aria-valuetext="currency.format(maxPriceCents / 100)" @input="updatePriceRange('max', $event)">
            </div>
            <div class="inventory-price-range-limits"><span>{{ currency.format(0) }}</span><span>{{ currency.format(priceCeilingCents / 100) }}</span></div>
          </div>
        </div>

        <div v-if="inventory.productsError || optionsError || priceRangeError" class="inventory-error" role="alert">{{ inventory.productsError || optionsError || priceRangeError }}</div>

        <div class="inventory-table-wrap">
          <table class="inventory-table products-table">
            <thead><tr>
              <th class="inventory-sort-header" :aria-sort="ariaSort('name')"><button class="inventory-sort-button" type="button" @click="toggleSort('name')">Produto <AppIcon :name="sortIcon('name')" aria-hidden="true" /></button></th>
              <th class="inventory-sort-header" :aria-sort="ariaSort('category')"><button class="inventory-sort-button" type="button" @click="toggleSort('category')">Categoria <AppIcon :name="sortIcon('category')" aria-hidden="true" /></button></th>
              <th class="inventory-sort-header" :aria-sort="ariaSort('price')"><button class="inventory-sort-button" type="button" @click="toggleSort('price')">Preço <AppIcon :name="sortIcon('price')" aria-hidden="true" /></button></th>
              <th class="inventory-sort-header" :aria-sort="ariaSort('stock')"><button class="inventory-sort-button" type="button" @click="toggleSort('stock')">Estoque <AppIcon :name="sortIcon('stock')" aria-hidden="true" /></button></th>
              <th class="inventory-sort-header" :aria-sort="ariaSort('status')"><button class="inventory-sort-button" type="button" @click="toggleSort('status')">Status <AppIcon :name="sortIcon('status')" aria-hidden="true" /></button></th>
              <th class="inventory-actions-header">Ações</th>
            </tr></thead>
            <tbody>
              <tr v-for="product in inventory.products" :key="product.id">
                <td><strong>{{ product.name }}</strong></td>
                <td>{{ product.category?.name ?? categoryName(product.category_id) }}</td>
                <td>{{ currency.format(Number(product.price)) }}</td>
                <td><span class="inventory-stock" :class="{ 'inventory-stock-low': product.stock <= 5 }">{{ product.stock }} un.</span></td>
                <td><span class="inventory-status" :class="{ 'inventory-status-inactive': product.status === 'inactive' }">{{ product.status === 'active' ? 'Ativo' : 'Inativo' }}</span></td>
                <td class="inventory-actions-cell"><div class="inventory-row-actions"><button type="button" @click="openProductDetails(product)"><AppIcon name="eye" aria-hidden="true" /> Abrir</button><button class="inventory-delete" type="button" :aria-label="`Excluir ${product.name}`" @click="productToDelete = product"><AppIcon name="trash" aria-hidden="true" /> Excluir</button></div></td>
              </tr>
              <tr v-if="inventory.productsLoading"><td colspan="6" class="inventory-empty">Carregando produtos...</td></tr>
              <tr v-else-if="!inventory.products.length"><td colspan="6" class="inventory-empty">Nenhum produto encontrado.</td></tr>
            </tbody>
          </table>
        </div>
        <div class="inventory-panel-footer"><span>{{ inventory.productsPagination.total }} produto(s)</span><div class="inventory-pagination"><button type="button" :disabled="page <= FIRST_PAGE || inventory.productsLoading" @click="loadPage(page - 1)">Anterior</button><span>{{ page }} / {{ inventory.productsPagination.lastPage }}</span><button type="button" :disabled="page >= inventory.productsPagination.lastPage || inventory.productsLoading" @click="loadPage(page + 1)">Próxima</button></div></div>
      </section>
    </div>

    <InventoryModal :open="Boolean(selectedProduct)" :title="selectedProduct?.name ?? 'Produto'" :eyebrow="editingProduct ? 'Editar produto' : 'Detalhes do produto'" @close="closeProductDetails">
      <form v-if="selectedProduct && editingProduct" id="edit-product-form" class="inventory-form" @submit.prevent="saveProductUpdate">
        <label>Nome do produto <input v-model.trim="editForm.name" type="text" maxlength="255" required></label>
        <label>Categoria <select v-model="editForm.category_id" required><option value="" disabled>Selecione uma categoria</option><option v-for="category in inventory.categoryOptions" :key="category.id" :value="category.id">{{ category.name }}</option></select></label>
        <label>Descrição <textarea v-model.trim="editForm.description" rows="3" /></label>
        <div class="inventory-form-row"><label>Custo de compra <input :value="editForm.cost" type="text" inputmode="numeric" autocomplete="off" placeholder="R$ 0,00 (opcional)" @input="updateMoney('cost', $event, editForm)"></label><label>Preço de revenda <input :value="editForm.price" type="text" inputmode="numeric" autocomplete="off" required @input="updateMoney('price', $event, editForm)"></label></div>
        <div class="inventory-form-row"><label>Estoque <input v-model="editForm.stock" type="number" min="0" step="1" required></label><label>Status <select v-model="editForm.status"><option value="active">Ativo</option><option value="inactive">Inativo</option></select></label></div>
        <label>Imagem (URL ou caminho) <input v-model.trim="editForm.image" type="text" maxlength="255" placeholder="Opcional"></label>
        <p v-if="editError" class="inventory-error" role="alert">{{ editError }}</p>
      </form>
      <div v-else-if="selectedProduct" class="inventory-detail-sections">
        <dl class="inventory-detail-grid">
          <div><dt>Categoria</dt><dd>{{ categoryName(selectedProduct.category_id) }}</dd></div>
          <div><dt>Status</dt><dd>{{ selectedProduct.status === 'active' ? 'Ativo' : 'Inativo' }}</dd></div>
          <div class="inventory-detail-wide"><dt>Descrição</dt><dd>{{ selectedProduct.description || '—' }}</dd></div>
          <div><dt>Custo de compra</dt><dd>{{ selectedProduct.cost === null ? '—' : currency.format(Number(selectedProduct.cost)) }}</dd></div>
          <div><dt>Preço de revenda</dt><dd>{{ currency.format(Number(selectedProduct.price)) }}</dd></div>
          <div><dt>Estoque</dt><dd>{{ selectedProduct.stock }} unidades</dd></div>
          <div><dt>Imagem</dt><dd>{{ selectedProduct.image || '—' }}</dd></div>
        </dl>
      </div>
      <template #footer>
        <template v-if="editingProduct"><button class="button button-outline" type="button" :disabled="updatingProduct" @click="cancelProductEdit">Cancelar</button><button class="button button-navy" type="submit" form="edit-product-form" :disabled="updatingProduct">{{ updatingProduct ? 'Salvando...' : 'Salvar alterações' }}</button></template>
        <template v-else><button class="button button-outline" type="button" @click="closeProductDetails">Fechar</button><button class="button button-navy" type="button" @click="startProductEdit"><AppIcon name="edit" aria-hidden="true" /> Editar</button></template>
      </template>
    </InventoryModal>

    <InventoryModal :open="creating" title="Novo produto" eyebrow="Catálogo" @close="creating = false">
      <form id="create-product-form" class="inventory-form" @submit.prevent="saveProduct">
        <label>Nome do produto <input v-model.trim="form.name" type="text" maxlength="255" required></label>
        <label>Categoria <select v-model="form.category_id" required><option value="" disabled>Selecione uma categoria</option><option v-for="category in inventory.activeCategoryOptions" :key="category.id" :value="category.id">{{ category.name }}</option></select></label>
        <label>Descrição <textarea v-model.trim="form.description" rows="3" /></label>
        <div class="inventory-form-row"><label>Custo de compra <input :value="form.cost" type="text" inputmode="numeric" autocomplete="off" placeholder="R$ 0,00 (opcional)" @input="updateMoney('cost', $event)"></label><label>Preço de revenda <input :value="form.price" type="text" inputmode="numeric" autocomplete="off" placeholder="R$ 0,00" required @input="updateMoney('price', $event)"></label></div>
        <div class="inventory-form-row"><label>Estoque <input v-model="form.stock" type="number" min="0" step="1" required></label><label>Status <select v-model="form.status"><option value="active">Ativo</option><option value="inactive">Inativo</option></select></label></div>
        <label>Imagem (URL ou caminho) <input v-model.trim="form.image" type="text" placeholder="Opcional"></label>
        <p v-if="actionError" class="inventory-error" role="alert">{{ actionError }}</p>
      </form>
      <template #footer><button class="button button-outline" type="button" @click="creating = false">Cancelar</button><button class="button button-navy" type="submit" form="create-product-form" :disabled="saving">{{ saving ? 'Salvando...' : 'Adicionar produto' }}</button></template>
    </InventoryModal>

    <InventoryModal :open="Boolean(productToDelete)" title="Excluir produto?" eyebrow="Confirmação" @close="productToDelete = null">
      <p class="inventory-confirmation">{{ productToDelete?.name }} será excluído.</p>
      <p v-if="actionError" class="inventory-error" role="alert">{{ actionError }}</p>
      <template #footer><button class="button button-outline" type="button" @click="productToDelete = null">Cancelar</button><button class="button button-danger" type="button" :disabled="deleting" @click="deleteProduct">{{ deleting ? 'Excluindo...' : 'Excluir produto' }}</button></template>
    </InventoryModal>
  </DashboardShell>
</template>

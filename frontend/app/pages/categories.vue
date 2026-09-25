<script setup lang="ts">
import type { InventoryCategory } from '~/stores/inventory'
import { apiErrorMessage } from '~/utils/api'

useHead({ title: 'Categorias · Winx' })

const inventory = useInventoryStore()
const auth = useAuthStore()
const search = ref('')
const statusFilter = ref<'active' | 'inactive' | ''>('active')
const page = ref(1)
type CategorySort = 'name' | 'description' | 'products_count' | 'status'
const sortBy = ref<CategorySort | null>(null)
const sortDir = ref<'asc' | 'desc'>('asc')
const selectedCategory = ref<InventoryCategory | null>(null)
const editingCategory = ref(false)
const updatingCategory = ref(false)
const editError = ref('')
const creating = ref(false)
const saving = ref(false)
const actionError = ref('')
const form = reactive({ name: '', description: '', status: 'active' as 'active' | 'inactive' })
const editForm = reactive({ name: '', description: '', status: 'active' as 'active' | 'inactive' })

let searchTimer: ReturnType<typeof setTimeout> | undefined

function loadPage(nextPage: number): void {
  page.value = nextPage
  void inventory.loadCategories(page.value, search.value.trim(), sortBy.value ?? '', sortDir.value, statusFilter.value)
}

watch(() => auth.user?.id, (id) => {
  if (id && import.meta.client) loadPage(1)
}, { immediate: true })

watch(search, () => {
  if (!auth.user || !import.meta.client) return
  clearTimeout(searchTimer)
  inventory.cancelCategoriesRequest()
  searchTimer = setTimeout(() => loadPage(1), 500)
}, { flush: 'sync' })

watch(statusFilter, () => {
  if (!auth.user || !import.meta.client) return
  clearTimeout(searchTimer)
  loadPage(1)
})

onUnmounted(() => {
  clearTimeout(searchTimer)
  inventory.cancelCategoriesRequest()
})

function toggleSort(field: CategorySort): void {
  sortDir.value = sortBy.value === field && sortDir.value === 'asc' ? 'desc' : 'asc'
  sortBy.value = field
  clearTimeout(searchTimer)
  loadPage(1)
}

function sortIcon(field: CategorySort): 'sort' | 'sortUp' | 'sortDown' {
  if (sortBy.value !== field) return 'sort'
  return sortDir.value === 'asc' ? 'sortUp' : 'sortDown'
}

function ariaSort(field: CategorySort): 'none' | 'ascending' | 'descending' {
  if (sortBy.value !== field) return 'none'
  return sortDir.value === 'asc' ? 'ascending' : 'descending'
}

function productCount(categoryId: string): number {
  return inventory.categories.find(category => category.id === categoryId)?.products_count ?? 0
}

function openCategoryDetails(category: InventoryCategory): void {
  selectedCategory.value = category
  editingCategory.value = false
  editError.value = ''
}

function closeCategoryDetails(): void {
  selectedCategory.value = null
  editingCategory.value = false
  editError.value = ''
}

function startCategoryEdit(): void {
  const category = selectedCategory.value
  if (!category) return

  Object.assign(editForm, {
    name: category.name,
    description: category.description ?? '',
    status: category.status
  })
  editError.value = ''
  editingCategory.value = true
}

function cancelCategoryEdit(): void {
  editingCategory.value = false
  editError.value = ''
}

async function saveCategoryUpdate(): Promise<void> {
  const category = selectedCategory.value
  if (!category) return

  updatingCategory.value = true
  editError.value = ''

  try {
    const updated = await inventory.updateCategory(category.id, {
      name: editForm.name.trim(),
      description: editForm.description.trim() || null,
      status: editForm.status
    })
    if (selectedCategory.value?.id === category.id) {
      selectedCategory.value = { ...updated, products_count: category.products_count }
      editingCategory.value = false
    }
    clearTimeout(searchTimer)
    loadPage(1)
  } catch (error) {
    editError.value = apiErrorMessage(error, 'Não foi possível atualizar a categoria.')
  } finally {
    updatingCategory.value = false
  }
}

function openCreate(): void {
  Object.assign(form, { name: '', description: '', status: 'active' })
  actionError.value = ''
  creating.value = true
}

async function saveCategory(): Promise<void> {
  saving.value = true
  actionError.value = ''

  try {
    await inventory.createCategory({ name: form.name.trim(), description: form.description.trim() || null, status: form.status })
    creating.value = false
    clearTimeout(searchTimer)
    loadPage(1)
  } catch (error) {
    actionError.value = apiErrorMessage(error, 'Não foi possível criar a categoria.')
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <DashboardShell fit-viewport inventory-viewport>
    <div class="inventory-page">
      <div class="inventory-page-heading">
        <div>
          <h1>Categorias</h1>
        </div>
        <div class="inventory-heading-actions">
          <button class="button button-navy" type="button" @click="openCreate">Adicionar categoria <AppIcon name="plus" aria-hidden="true" /></button>
        </div>
      </div>

      <section class="inventory-panel" aria-label="Lista de categorias">
        <div class="inventory-toolbar inventory-toolbar-categories">
          <label class="inventory-search"><span>Buscar categoria</span><input v-model.trim="search" type="search" placeholder="Nome da categoria"></label>
          <label class="inventory-filter">
            <span>Status</span>
            <select v-model="statusFilter">
              <option value="active">Ativas</option>
              <option value="inactive">Inativas</option>
              <option value="">Todas</option>
            </select>
          </label>
          <span class="inventory-toolbar-count">{{ inventory.categoriesPagination.total }} {{ inventory.categoriesPagination.total === 1 ? 'categoria encontrada' : 'categorias encontradas' }}</span>
        </div>
        <div v-if="inventory.categoriesError" class="inventory-error" role="alert">{{ inventory.categoriesError }}</div>
        <div class="inventory-table-wrap">
          <table class="inventory-table categories-table">
            <thead><tr>
              <th class="inventory-sort-header" :aria-sort="ariaSort('name')"><button class="inventory-sort-button" type="button" @click="toggleSort('name')">Categoria <AppIcon :name="sortIcon('name')" aria-hidden="true" /></button></th>
              <th class="inventory-sort-header" :aria-sort="ariaSort('description')"><button class="inventory-sort-button" type="button" @click="toggleSort('description')">Descrição <AppIcon :name="sortIcon('description')" aria-hidden="true" /></button></th>
              <th class="inventory-sort-header" :aria-sort="ariaSort('products_count')"><button class="inventory-sort-button" type="button" @click="toggleSort('products_count')">Produtos <AppIcon :name="sortIcon('products_count')" aria-hidden="true" /></button></th>
              <th class="inventory-sort-header" :aria-sort="ariaSort('status')"><button class="inventory-sort-button" type="button" @click="toggleSort('status')">Status <AppIcon :name="sortIcon('status')" aria-hidden="true" /></button></th>
              <th class="inventory-actions-header">Ações</th>
            </tr></thead>
            <tbody>
              <tr v-for="category in inventory.categories" :key="category.id">
                <td><strong>{{ category.name }}</strong></td>
                <td class="inventory-description-cell">{{ category.description || 'Sem descrição' }}</td>
                <td>{{ productCount(category.id) }}</td>
                <td><span class="inventory-status" :class="{ 'inventory-status-inactive': category.status === 'inactive' }">{{ category.status === 'active' ? 'Ativa' : 'Inativa' }}</span></td>
                <td><div class="inventory-row-actions"><button type="button" @click="openCategoryDetails(category)"><AppIcon name="eye" aria-hidden="true" /> Abrir</button></div></td>
              </tr>
              <tr v-if="inventory.categoriesLoading"><td colspan="5" class="inventory-empty">Carregando categorias...</td></tr>
              <tr v-else-if="!inventory.categories.length"><td colspan="5" class="inventory-empty">Nenhuma categoria encontrada.</td></tr>
            </tbody>
          </table>
        </div>
        <div class="inventory-panel-footer"><span>{{ inventory.categoriesPagination.total }} categoria(s)</span><div class="inventory-pagination"><button type="button" :disabled="page <= 1 || inventory.categoriesLoading" @click="loadPage(page - 1)">Anterior</button><span>{{ page }} / {{ inventory.categoriesPagination.lastPage }}</span><button type="button" :disabled="page >= inventory.categoriesPagination.lastPage || inventory.categoriesLoading" @click="loadPage(page + 1)">Próxima</button></div></div>
      </section>
    </div>

    <InventoryModal :open="Boolean(selectedCategory)" :title="selectedCategory?.name ?? 'Categoria'" :eyebrow="editingCategory ? 'Editar categoria' : 'Detalhes da categoria'" @close="closeCategoryDetails">
      <form v-if="selectedCategory && editingCategory" id="edit-category-form" class="inventory-form" @submit.prevent="saveCategoryUpdate">
        <label>Nome da categoria <input v-model.trim="editForm.name" type="text" maxlength="255" required></label>
        <label>Descrição <textarea v-model.trim="editForm.description" rows="4" /></label>
        <label>Status <select v-model="editForm.status"><option value="active">Ativa</option><option value="inactive">Inativa</option></select></label>
        <p v-if="editError" class="inventory-error" role="alert">{{ editError }}</p>
      </form>
      <div v-else-if="selectedCategory" class="inventory-detail-sections">
        <dl class="inventory-detail-grid">
          <div><dt>Nome</dt><dd>{{ selectedCategory.name }}</dd></div>
          <div><dt>Status</dt><dd>{{ selectedCategory.status === 'active' ? 'Ativa' : 'Inativa' }}</dd></div>
          <div class="inventory-detail-wide"><dt>Descrição</dt><dd>{{ selectedCategory.description || '—' }}</dd></div>
          <div><dt>Produtos vinculados</dt><dd>{{ selectedCategory.products_count ?? productCount(selectedCategory.id) }}</dd></div>
        </dl>
      </div>
      <template #footer>
        <template v-if="editingCategory"><button class="button button-outline" type="button" :disabled="updatingCategory" @click="cancelCategoryEdit">Cancelar</button><button class="button button-navy" type="submit" form="edit-category-form" :disabled="updatingCategory">{{ updatingCategory ? 'Salvando...' : 'Salvar alterações' }}</button></template>
        <template v-else><button class="button button-outline" type="button" @click="closeCategoryDetails">Fechar</button><button class="button button-navy" type="button" @click="startCategoryEdit"><AppIcon name="edit" aria-hidden="true" /> Editar</button></template>
      </template>
    </InventoryModal>

    <InventoryModal :open="creating" title="Adicionar categoria" eyebrow="Organização" @close="creating = false">
      <form id="create-category-form" class="inventory-form" @submit.prevent="saveCategory">
        <label>Nome da categoria <input v-model.trim="form.name" type="text" maxlength="255" required></label>
        <label>Descrição <textarea v-model.trim="form.description" rows="4" /></label>
        <label>Status <select v-model="form.status"><option value="active">Ativa</option><option value="inactive">Inativa</option></select></label>
        <p v-if="actionError" class="inventory-error" role="alert">{{ actionError }}</p>
      </form>
      <template #footer><button class="button button-outline" type="button" @click="creating = false">Cancelar</button><button class="button button-navy" type="submit" form="create-category-form" :disabled="saving">{{ saving ? 'Salvando...' : 'Adicionar categoria' }}</button></template>
    </InventoryModal>
  </DashboardShell>
</template>

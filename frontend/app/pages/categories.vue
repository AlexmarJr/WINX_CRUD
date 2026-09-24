<script setup lang="ts">
import type { InventoryCategory } from '~/stores/inventory'

useHead({ title: 'Categorias · Winx' })

const inventory = useInventoryStore()
const search = ref('')
const selectedCategory = ref<InventoryCategory | null>(null)
const creating = ref(false)
const form = reactive({ name: '', description: '', status: 'active' as 'active' | 'inactive' })

const filteredCategories = computed(() => inventory.categories.filter(category =>
  category.name.toLocaleLowerCase('pt-BR').includes(search.value.trim().toLocaleLowerCase('pt-BR'))
))

function productCount(categoryId: string): number {
  return inventory.products.filter(product => product.category_id === categoryId).length
}

function openCreate(): void {
  Object.assign(form, { name: '', description: '', status: 'active' })
  creating.value = true
}

function saveCategory(): void {
  inventory.addCategory({ name: form.name.trim(), description: form.description.trim() || null, status: form.status, meta: null })
  creating.value = false
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
          <span class="sample-data-label">Dados de exemplo</span>
          <button class="button button-navy" type="button" @click="openCreate">Adicionar categoria <AppIcon name="plus" aria-hidden="true" /></button>
        </div>
      </div>

      <section class="inventory-panel" aria-label="Lista de categorias">
        <div class="inventory-toolbar">
          <label class="inventory-search"><span>Buscar categoria</span><input v-model.trim="search" type="search" placeholder="Nome da categoria"></label>
          <span class="inventory-toolbar-count">{{ inventory.categories.length }} categorias no catálogo</span>
        </div>
        <div class="inventory-table-wrap">
          <table class="inventory-table">
            <thead><tr><th>Categoria</th><th>Descrição</th><th>Produtos</th><th>Status</th><th class="inventory-actions-header">Ações</th></tr></thead>
            <tbody>
              <tr v-for="category in filteredCategories" :key="category.id">
                <td><strong>{{ category.name }}</strong></td>
                <td class="inventory-description-cell">{{ category.description || 'Sem descrição' }}</td>
                <td>{{ productCount(category.id) }}</td>
                <td><span class="inventory-status" :class="{ 'inventory-status-inactive': category.status === 'inactive' }">{{ category.status === 'active' ? 'Ativa' : 'Inativa' }}</span></td>
                <td><div class="inventory-row-actions"><button type="button" @click="selectedCategory = category"><AppIcon name="eye" aria-hidden="true" /> Abrir</button></div></td>
              </tr>
              <tr v-if="!filteredCategories.length"><td colspan="5" class="inventory-empty">Nenhuma categoria encontrada.</td></tr>
            </tbody>
          </table>
        </div>
        <div class="inventory-panel-footer">{{ filteredCategories.length }} categoria(s) exibida(s) · alterações locais de demonstração</div>
      </section>
    </div>

    <InventoryModal :open="Boolean(selectedCategory)" :title="selectedCategory?.name ?? 'Categoria'" eyebrow="Detalhes da categoria" @close="selectedCategory = null">
      <div v-if="selectedCategory" class="inventory-detail-sections">
        <dl class="inventory-detail-grid">
          <div><dt>Nome</dt><dd>{{ selectedCategory.name }}</dd></div>
          <div><dt>Status</dt><dd>{{ selectedCategory.status === 'active' ? 'Ativa' : 'Inativa' }}</dd></div>
          <div class="inventory-detail-wide"><dt>Descrição</dt><dd>{{ selectedCategory.description || '—' }}</dd></div>
          <div><dt>Produtos vinculados</dt><dd>{{ productCount(selectedCategory.id) }}</dd></div>
        </dl>
      </div>
      <template #footer><button class="button button-outline" type="button" @click="selectedCategory = null">Fechar</button></template>
    </InventoryModal>

    <InventoryModal :open="creating" title="Adicionar categoria" eyebrow="Organização" @close="creating = false">
      <form id="create-category-form" class="inventory-form" @submit.prevent="saveCategory">
        <label>Nome da categoria <input v-model.trim="form.name" type="text" maxlength="255" required></label>
        <label>Descrição <textarea v-model.trim="form.description" rows="4" /></label>
        <label>Status <select v-model="form.status"><option value="active">Ativa</option><option value="inactive">Inativa</option></select></label>
        <p class="inventory-form-note">ID, tenancy, usuário e datas são preenchidos apenas na demonstração.</p>
      </form>
      <template #footer><button class="button button-outline" type="button" @click="creating = false">Cancelar</button><button class="button button-navy" type="submit" form="create-category-form">Adicionar categoria</button></template>
    </InventoryModal>
  </DashboardShell>
</template>

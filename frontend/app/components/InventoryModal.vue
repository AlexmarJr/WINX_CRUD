<script setup lang="ts">
const props = defineProps<{ open: boolean, title: string, eyebrow?: string }>()
const emit = defineEmits<{ close: [] }>()
const dialog = ref<HTMLDialogElement | null>(null)

watch(() => props.open, async (open) => {
  await nextTick()

  if (open && !dialog.value?.open) dialog.value?.showModal()
  if (!open && dialog.value?.open) dialog.value.close()
}, { immediate: true })
</script>

<template>
  <dialog ref="dialog" class="inventory-dialog" :aria-label="title" @close="emit('close')">
    <div class="inventory-dialog-content">
      <header class="inventory-dialog-header">
        <div>
          <span v-if="eyebrow" class="overview-card-kicker">{{ eyebrow }}</span>
          <h2>{{ title }}</h2>
        </div>
        <button class="inventory-dialog-close" type="button" aria-label="Fechar" @click="emit('close')"><AppIcon name="close" aria-hidden="true" /></button>
      </header>
      <div class="inventory-dialog-body"><slot /></div>
      <footer v-if="$slots.footer" class="inventory-dialog-footer"><slot name="footer" /></footer>
    </div>
  </dialog>
</template>

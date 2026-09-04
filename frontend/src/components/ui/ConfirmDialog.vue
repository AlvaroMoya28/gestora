<script setup lang="ts">
import BaseButton from './BaseButton.vue'
import BaseModal from './BaseModal.vue'

withDefaults(
  defineProps<{
    open: boolean
    title: string
    message: string
    confirmLabel?: string
    variant?: 'primary' | 'danger'
    loading?: boolean
  }>(),
  { confirmLabel: 'Confirmar', variant: 'primary' },
)

const emit = defineEmits<{ confirm: []; cancel: [] }>()
</script>

<template>
  <BaseModal :open="open" :title="title" width="440px" @close="emit('cancel')">
    <p>{{ message }}</p>

    <template #footer>
      <BaseButton variant="ghost" :disabled="loading" @click="emit('cancel')">Cancelar</BaseButton>
      <BaseButton :variant="variant" :loading="loading" @click="emit('confirm')">
        {{ confirmLabel }}
      </BaseButton>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { computed, useId } from 'vue'

/**
 * Envoltura común de los campos de formulario: etiqueta, ayuda y error.
 * Cada control concreto (texto, número, select, textarea) se pasa por el slot,
 * así no hay un componente distinto por tipo de input.
 */
const props = defineProps<{
  label: string
  error?: string
  hint?: string
  required?: boolean
  span2?: boolean
}>()

const id = useId()
const describedBy = computed(() => (props.error || props.hint ? `${id}-help` : undefined))
</script>

<template>
  <div class="field" :class="{ 'span-2': span2 }">
    <label :for="id">
      {{ label }}
      <span v-if="required" class="req" aria-hidden="true">*</span>
    </label>

    <slot :id="id" :invalid="!!error" :described-by="describedBy" />

    <p v-if="error" :id="`${id}-help`" class="error">{{ error }}</p>
    <p v-else-if="hint" :id="`${id}-help`" class="hint">{{ hint }}</p>
  </div>
</template>

<style scoped>
.req {
  color: var(--danger-500);
  margin-left: 2px;
}
</style>

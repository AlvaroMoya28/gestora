<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppIcon from '@/components/ui/AppIcon.vue'
import { useTours } from '@/help/tours'

defineProps<{ title: string; description?: string }>()

/**
 * El botón "¿Cómo se usa?" sale solo en las pantallas que tienen recorrido. Vive aquí
 * y no en cada vista: toda pantalla de módulo usa este encabezado, así que ninguna
 * puede quedarse sin él por olvido.
 */
const route = useRoute()
const { startModule, hasModuleTour } = useTours()

const moduleKey = computed(() => route.meta.module as string | undefined)
const showHowTo = computed(() => !!moduleKey.value && hasModuleTour(moduleKey.value))
</script>

<template>
  <header class="page-header">
    <div data-tour="page-title">
      <div class="title-row">
        <h1>{{ title }}</h1>
        <button
          v-if="showHowTo"
          type="button"
          class="how-to"
          data-tour="page-help"
          @click="startModule(moduleKey!)"
        >
          <AppIcon name="help" :size="15" />
          ¿Cómo se usa?
        </button>
      </div>
      <p v-if="description" class="muted">{{ description }}</p>
    </div>
    <div class="actions" data-tour="page-actions">
      <slot name="actions" />
    </div>
  </header>
</template>

<style scoped>
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 20px;
}

.title-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

h1 {
  font-size: 20px;
  font-weight: 650;
  letter-spacing: -0.01em;
}

.how-to {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: var(--brand-50);
  color: var(--brand-700);
  border: 1px solid var(--brand-100);
  border-radius: 999px;
  padding: 3px 10px 3px 8px;
  font-size: 12.5px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
}

.how-to:hover {
  background: var(--brand-100);
}

.how-to:focus-visible {
  outline: 2px solid var(--brand-500);
  outline-offset: 2px;
}

p {
  font-size: 13.5px;
  margin-top: 3px;
}

.actions {
  display: flex;
  gap: 9px;
  flex-wrap: wrap;
}
</style>

<script setup lang="ts" generic="T extends Record<string, any>">
import BaseButton from './BaseButton.vue'
import type { Column } from './table'

/**
 * Tabla de datos genérica: cabecera, estados de carga y vacío, y paginación.
 * El contenido de cada celda se resuelve con un slot con el nombre de la columna,
 * de modo que la tabla no sabe nada del dominio que está mostrando.
 */
withDefaults(
  defineProps<{
    columns: Column[]
    rows: T[]
    /** Campo que identifica cada fila de forma única. */
    rowKey?: string
    loading?: boolean
    emptyTitle?: string
    emptyMessage?: string
    page?: number
    pageSize?: number
    total?: number
  }>(),
  {
    rowKey: 'id',
    emptyTitle: 'Sin resultados',
    emptyMessage: 'No hay registros que coincidan con la búsqueda.',
    page: 1,
    pageSize: 20,
    total: 0,
  },
)

const emit = defineEmits<{ 'update:page': [value: number] }>()
</script>

<template>
  <div class="table-wrap">
    <div class="scroll">
      <table>
        <thead>
          <tr>
            <th
              v-for="column in columns"
              :key="column.key"
              :style="{ width: column.width, textAlign: column.align ?? 'left' }"
            >
              {{ column.label }}
            </th>
          </tr>
        </thead>

        <tbody v-if="loading">
          <tr v-for="n in 5" :key="`skeleton-${n}`" class="skeleton-row">
            <td v-for="column in columns" :key="column.key"><span class="skeleton" /></td>
          </tr>
        </tbody>

        <tbody v-else-if="rows.length">
          <tr v-for="row in rows" :key="String(row[rowKey])">
            <td
              v-for="column in columns"
              :key="column.key"
              :style="{ textAlign: column.align ?? 'left' }"
            >
              <slot :name="column.key" :row="row">{{ row[column.key] }}</slot>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div v-if="!loading && !rows.length" class="empty">
      <p class="empty-title">{{ emptyTitle }}</p>
      <p class="muted">{{ emptyMessage }}</p>
      <slot name="empty-action" />
    </div>

    <div v-if="total > pageSize" class="pagination">
      <span class="muted">
        {{ (page - 1) * pageSize + 1 }}–{{ Math.min(page * pageSize, total) }} de {{ total }}
      </span>
      <div class="row">
        <BaseButton size="sm" :disabled="page <= 1" @click="emit('update:page', page - 1)">
          Anterior
        </BaseButton>
        <span class="muted">Página {{ page }} de {{ Math.ceil(total / pageSize) }}</span>
        <BaseButton
          size="sm"
          :disabled="page >= Math.ceil(total / pageSize)"
          @click="emit('update:page', page + 1)"
        >
          Siguiente
        </BaseButton>
      </div>
    </div>
  </div>
</template>

<style scoped>
.table-wrap {
  background: var(--surface);
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.scroll {
  overflow-x: auto;
}

table {
  width: 100%;
  border-collapse: collapse;
  min-width: 620px;
}

th {
  text-align: left;
  padding: 10px 16px;
  font-size: 11.5px;
  font-weight: 650;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--ink-500);
  background: var(--ink-100);
  border-bottom: 1px solid var(--ink-200);
  white-space: nowrap;
}

td {
  padding: 11px 16px;
  border-bottom: 1px solid var(--ink-100);
  vertical-align: middle;
}

tbody tr:last-child td {
  border-bottom: none;
}

tbody tr:hover td {
  background: var(--brand-50);
}

.skeleton-row td {
  padding: 13px 16px;
}

.skeleton {
  display: block;
  height: 11px;
  border-radius: 4px;
  background: linear-gradient(90deg, var(--ink-100), var(--ink-200), var(--ink-100));
  background-size: 200% 100%;
  animation: shimmer 1.3s infinite;
}

@keyframes shimmer {
  to {
    background-position: -200% 0;
  }
}

.empty {
  padding: 48px 20px;
  text-align: center;
}

.empty-title {
  font-weight: 600;
  margin-bottom: 4px;
}

.empty :deep(button) {
  margin-top: 14px;
}

.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  flex-wrap: wrap;
  padding: 11px 16px;
  border-top: 1px solid var(--ink-200);
  font-size: 13px;
}
</style>

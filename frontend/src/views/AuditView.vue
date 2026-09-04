<script setup lang="ts">
import { onMounted } from 'vue'
import DataTable from '@/components/ui/DataTable.vue'
import PageHeader from '@/components/ui/PageHeader.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import type { Column } from '@/components/ui/table'
import { useFormat } from '@/composables/useFormat'
import { useResourceList } from '@/composables/useResourceList'
import { auditApi } from '@/services/api'
import type { AuditEntry } from '@/types'

const { dateTime } = useFormat()
const list = useResourceList<AuditEntry>(auditApi.list, {
  pageSize: 25,
  initialFilters: { module: null },
})

const columns: Column[] = [
  { key: 'occurredAt', label: 'Fecha', width: '150px' },
  { key: 'userName', label: 'Usuario', width: '180px' },
  { key: 'action', label: 'Acción', width: '190px' },
  { key: 'detail', label: 'Detalle' },
]

const modules = [
  { value: null, label: 'Todos los módulos' },
  { value: 'auth', label: 'Sesiones' },
  { value: 'customers', label: 'Clientes' },
  { value: 'suppliers', label: 'Proveedores' },
  { value: 'products', label: 'Productos' },
  { value: 'inventory', label: 'Inventario' },
  { value: 'users', label: 'Usuarios' },
]

onMounted(list.load)
</script>

<template>
  <div>
    <PageHeader
      title="Auditoría"
      description="Registro de las operaciones sensibles. Solo lectura: no se edita ni se borra."
    />

    <div class="toolbar">
      <input v-model="list.search.value" class="control search" type="search" placeholder="Buscar por usuario, acción o detalle…" />
      <select v-model="list.filters.value.module" class="control filter">
        <option v-for="option in modules" :key="option.label" :value="option.value">{{ option.label }}</option>
      </select>
    </div>

    <DataTable
      :columns="columns"
      :rows="list.items.value"
      :loading="list.loading.value"
      :page="list.page.value"
      :page-size="list.pageSize"
      :total="list.total.value"
      empty-title="Sin registros"
      empty-message="Aquí aparecerán los cambios sobre dinero, inventario y accesos."
      @update:page="list.page.value = $event"
    >
      <template #occurredAt="{ row }">
        <span class="num">{{ dateTime(row.occurredAt) }}</span>
      </template>

      <template #userName="{ row }">
        <strong>{{ row.userName || 'Sistema' }}</strong>
        <small v-if="row.ipAddress" class="muted block num">{{ row.ipAddress }}</small>
      </template>

      <template #action="{ row }">
        <StatusBadge :tone="row.action.includes('Bloqueo') ? 'danger' : 'neutral'">{{ row.action }}</StatusBadge>
        <small class="muted block">{{ row.module }}</small>
      </template>

      <template #detail="{ row }">
        <span>{{ row.description || '—' }}</span>
        <small v-if="row.oldValue || row.newValue" class="muted block change">
          <span class="old">{{ row.oldValue }}</span>
          <span aria-hidden="true"> → </span>
          <span class="new">{{ row.newValue }}</span>
        </small>
      </template>
    </DataTable>
  </div>
</template>

<style scoped>
.toolbar {
  display: flex;
  gap: 10px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}

.search {
  flex: 1;
  min-width: 220px;
  max-width: 400px;
}

.filter {
  width: 190px;
}

.block {
  display: block;
}

.change {
  font-size: 12px;
  margin-top: 3px;
}

.old {
  text-decoration: line-through;
}

.new {
  color: var(--ink-700);
  font-weight: 600;
}
</style>

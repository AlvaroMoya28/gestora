<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import DataTable from '@/components/ui/DataTable.vue'
import PageHeader from '@/components/ui/PageHeader.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import type { Column } from '@/components/ui/table'
import { useFormat } from '@/composables/useFormat'
import { useResourceList } from '@/composables/useResourceList'
import { lookupsApi, productsApi } from '@/services/api'
import { errorMessage, fieldErrors } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import { ProductType, productTypeLabels, type Category, type Product, type Unit } from '@/types'

const auth = useAuthStore()
const notifications = useNotificationStore()
const { currency, number } = useFormat()

const list = useResourceList<Product>(productsApi.list, { initialFilters: { active: true, type: null } })

const categories = ref<Category[]>([])
const units = ref<Unit[]>([])

const columns: Column[] = [
  { key: 'code', label: 'Código', width: '110px' },
  { key: 'name', label: 'Producto' },
  { key: 'type', label: 'Tipo', width: '150px' },
  { key: 'stock', label: 'Existencia', align: 'right', width: '150px' },
  { key: 'price', label: 'Precio', align: 'right', width: '120px' },
  { key: 'actions', label: '', align: 'right', width: '150px' },
]

const emptyForm = () => ({
  code: '',
  name: '',
  description: '',
  barcode: '',
  type: ProductType.FinishedGood as number,
  categoryId: null as number | null,
  unitId: 0,
  cost: 0,
  price: 0,
  taxRate: 13,
  minStock: 0,
  initialStock: 0,
})

const modalOpen = ref(false)
const editing = ref<Product | null>(null)
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive(emptyForm())

/** Margen estimado, para que el precio no se fije a ciegas. */
const margin = computed(() => {
  if (!form.price) return null
  return ((form.price - form.cost) / form.price) * 100
})

function openCreate() {
  editing.value = null
  errors.value = {}
  Object.assign(form, emptyForm(), { unitId: units.value[0]?.id ?? 0 })
  modalOpen.value = true
}

function openEdit(product: Product) {
  editing.value = product
  errors.value = {}
  Object.assign(form, {
    code: product.code,
    name: product.name,
    description: product.description ?? '',
    barcode: product.barcode ?? '',
    type: product.type as number,
    categoryId: product.categoryId,
    unitId: product.unitId,
    cost: product.cost,
    price: product.price,
    taxRate: product.taxRate,
    minStock: product.minStock,
    initialStock: 0,
  })
  modalOpen.value = true
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    const payload: Record<string, unknown> = {
      ...form,
      type: Number(form.type),
      categoryId: form.categoryId || null,
      unitId: Number(form.unitId),
    }
    // La existencia inicial solo tiene sentido al crear; después se mueve por inventario.
    if (editing.value) delete payload.initialStock

    if (editing.value) {
      await productsApi.update(editing.value.id, payload)
      notifications.success('Producto actualizado.')
    } else {
      await productsApi.create(payload)
      notifications.success('Producto registrado.')
    }
    modalOpen.value = false
    await list.load()
  } catch (error) {
    errors.value = fieldErrors(error)
    notifications.error(errorMessage(error))
  } finally {
    saving.value = false
  }
}

const confirming = ref<Product | null>(null)
const toggling = ref(false)

async function confirmToggle() {
  if (!confirming.value) return
  toggling.value = true
  try {
    const target = confirming.value
    await productsApi.setActive(target.id, !target.isActive)
    notifications.success(target.isActive ? 'Producto inactivado.' : 'Producto reactivado.')
    confirming.value = null
    await list.load()
  } catch (error) {
    notifications.error(errorMessage(error))
  } finally {
    toggling.value = false
  }
}

const stockTone = (status: string) =>
  status === 'agotado' ? 'danger' : status === 'bajo' ? 'warning' : 'success'

onMounted(async () => {
  await Promise.all([
    list.load(),
    lookupsApi.categories(true).then((data) => (categories.value = data)),
    lookupsApi.units(true).then((data) => (units.value = data)),
  ]).catch((error) => notifications.error(errorMessage(error)))
})
</script>

<template>
  <div>
    <PageHeader
      title="Productos y materiales"
      description="Catálogo único: lo que se fabrica, lo que se compra y lo que se vende."
    >
      <template #actions>
        <BaseButton v-if="auth.canWrite('products')" variant="primary" @click="openCreate">
          Nuevo producto
        </BaseButton>
      </template>
    </PageHeader>

    <div class="toolbar">
      <input v-model="list.search.value" class="control search" type="search" placeholder="Buscar por nombre, código o código de barras…" />
      <select v-model="list.filters.value.type" class="control filter">
        <option :value="null">Todos los tipos</option>
        <option :value="ProductType.FinishedGood">Producto terminado</option>
        <option :value="ProductType.RawMaterial">Materia prima</option>
        <option :value="ProductType.Service">Servicio</option>
      </select>
      <select v-model="list.filters.value.active" class="control filter">
        <option :value="true">Activos</option>
        <option :value="false">Inactivos</option>
        <option :value="null">Todos</option>
      </select>
    </div>

    <DataTable
      :columns="columns"
      :rows="list.items.value"
      :loading="list.loading.value"
      :page="list.page.value"
      :page-size="list.pageSize"
      :total="list.total.value"
      empty-title="Catálogo vacío"
      empty-message="Registre sus productos y materiales para poder controlar existencias, compras y ventas."
      @update:page="list.page.value = $event"
    >
      <template #code="{ row }"><span class="num">{{ row.code }}</span></template>

      <template #name="{ row }">
        <strong>{{ row.name }}</strong>
        <small class="muted block">{{ row.categoryName || 'Sin categoría' }}</small>
      </template>

      <template #type="{ row }">
        <StatusBadge :tone="row.type === ProductType.RawMaterial ? 'info' : 'brand'">
          {{ productTypeLabels[row.type] }}
        </StatusBadge>
      </template>

      <template #stock="{ row }">
        <template v-if="row.type !== ProductType.Service">
          <span class="num">{{ number(row.stock) }} {{ row.unitAbbreviation }}</span>
          <StatusBadge :tone="stockTone(row.stockStatus)" class="badge-inline">
            {{ row.stockStatus === 'agotado' ? 'Agotado' : row.stockStatus === 'bajo' ? 'Bajo' : 'Normal' }}
          </StatusBadge>
        </template>
        <span v-else class="muted">—</span>
      </template>

      <template #price="{ row }">
        <span class="num">{{ currency(row.price) }}</span>
        <small class="muted block num">costo {{ currency(row.cost) }}</small>
      </template>

      <template #actions="{ row }">
        <div class="row-actions">
          <BaseButton size="sm" @click="openEdit(row)">
            {{ auth.canWrite('products') ? 'Editar' : 'Ver' }}
          </BaseButton>
          <BaseButton v-if="auth.canWrite('products')" size="sm" variant="ghost" @click="confirming = row">
            {{ row.isActive ? 'Inactivar' : 'Activar' }}
          </BaseButton>
        </div>
      </template>

      <template #empty-action>
        <BaseButton v-if="auth.canWrite('products')" variant="primary" @click="openCreate">
          Nuevo producto
        </BaseButton>
      </template>
    </DataTable>

    <BaseModal
      :open="modalOpen"
      :title="editing ? 'Editar producto' : 'Nuevo producto'"
      subtitle="La existencia se ajusta desde Inventario, nunca escribiéndola a mano."
      width="720px"
      @close="modalOpen = false"
    >
      <form id="product-form" class="form-grid" @submit.prevent="save">
        <BaseField v-slot="{ id, invalid }" label="Código" hint="Se genera solo si lo deja vacío." :error="errors.code">
          <input :id="id" v-model="form.code" class="control" :class="{ 'is-invalid': invalid }" maxlength="30" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Código de barras">
          <input :id="id" v-model="form.barcode" class="control" maxlength="60" />
        </BaseField>

        <BaseField v-slot="{ id, invalid }" label="Nombre" required span2 :error="errors.name">
          <input :id="id" v-model="form.name" class="control" :class="{ 'is-invalid': invalid }" required maxlength="150" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Tipo">
          <select :id="id" v-model.number="form.type" class="control">
            <option :value="ProductType.FinishedGood">Producto terminado</option>
            <option :value="ProductType.RawMaterial">Materia prima</option>
            <option :value="ProductType.Service">Servicio</option>
          </select>
        </BaseField>

        <BaseField v-slot="{ id }" label="Categoría">
          <select :id="id" v-model="form.categoryId" class="control">
            <option :value="null">Sin categoría</option>
            <option v-for="category in categories" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </BaseField>

        <BaseField v-slot="{ id, invalid }" label="Unidad de medida" required :error="errors.unitId">
          <select :id="id" v-model.number="form.unitId" class="control" :class="{ 'is-invalid': invalid }" required>
            <option :value="0" disabled>Seleccione…</option>
            <option v-for="unit in units" :key="unit.id" :value="unit.id">
              {{ unit.name }} ({{ unit.abbreviation }})
            </option>
          </select>
        </BaseField>

        <BaseField v-slot="{ id }" label="Existencia mínima" hint="Bajo este nivel aparece la alerta.">
          <input :id="id" v-model.number="form.minStock" class="control" type="number" min="0" step="0.01" />
        </BaseField>

        <BaseField v-slot="{ id, invalid }" label="Costo unitario" :error="errors.cost">
          <input :id="id" v-model.number="form.cost" class="control" :class="{ 'is-invalid': invalid }" type="number" min="0" step="0.01" />
        </BaseField>

        <BaseField
          v-slot="{ id, invalid }"
          label="Precio de venta"
          :error="errors.price"
          :hint="margin !== null ? `Margen estimado: ${number(margin, 1)} %` : undefined"
        >
          <input :id="id" v-model.number="form.price" class="control" :class="{ 'is-invalid': invalid }" type="number" min="0" step="0.01" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Impuesto (%)">
          <input :id="id" v-model.number="form.taxRate" class="control" type="number" min="0" max="100" step="0.01" />
        </BaseField>

        <BaseField
          v-if="!editing"
          v-slot="{ id }"
          label="Existencia inicial"
          hint="Se registra como movimiento de carga inicial."
        >
          <input :id="id" v-model.number="form.initialStock" class="control" type="number" min="0" step="0.01" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Descripción" span2>
          <textarea :id="id" v-model="form.description" class="control" maxlength="500" />
        </BaseField>
      </form>

      <template #footer>
        <BaseButton variant="ghost" @click="modalOpen = false">Cancelar</BaseButton>
        <BaseButton
          v-if="auth.canWrite('products')"
          type="submit"
          form="product-form"
          variant="primary"
          :loading="saving"
        >
          {{ editing ? 'Guardar cambios' : 'Registrar producto' }}
        </BaseButton>
      </template>
    </BaseModal>

    <ConfirmDialog
      :open="!!confirming"
      :title="confirming?.isActive ? 'Inactivar producto' : 'Reactivar producto'"
      :message="
        confirming?.isActive
          ? `${confirming?.name} dejará de aparecer en nuevas operaciones. Su historial de movimientos se conserva.`
          : `${confirming?.name} volverá a estar disponible.`
      "
      :confirm-label="confirming?.isActive ? 'Inactivar' : 'Reactivar'"
      :variant="confirming?.isActive ? 'danger' : 'primary'"
      :loading="toggling"
      @cancel="confirming = null"
      @confirm="confirmToggle"
    />
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
  max-width: 380px;
}

.filter {
  width: 175px;
}

.block {
  display: block;
}

.badge-inline {
  margin-left: 7px;
}

.row-actions {
  display: flex;
  gap: 6px;
  justify-content: flex-end;
}
</style>

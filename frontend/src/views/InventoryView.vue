<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import DataTable from '@/components/ui/DataTable.vue'
import PageHeader from '@/components/ui/PageHeader.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import type { Column } from '@/components/ui/table'
import { useFormat } from '@/composables/useFormat'
import { useResourceList } from '@/composables/useResourceList'
import { inventoryApi, productsApi } from '@/services/api'
import { errorMessage, fieldErrors } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import { MovementType, ProductType, type InventoryMovement, type Product } from '@/types'

const auth = useAuthStore()
const notifications = useNotificationStore()
const { number, currency, dateTime } = useFormat()

const list = useResourceList<InventoryMovement>(inventoryApi.movements, {
  pageSize: 25,
  initialFilters: { productId: null },
})

/** Solo los productos que llevan existencia: los servicios no se inventarían. */
const products = ref<Product[]>([])
const stockedProducts = computed(() => products.value.filter((p) => p.type !== ProductType.Service))

const columns: Column[] = [
  { key: 'occurredAt', label: 'Fecha', width: '150px' },
  { key: 'product', label: 'Producto' },
  { key: 'type', label: 'Motivo', width: '190px' },
  { key: 'quantity', label: 'Cantidad', align: 'right', width: '130px' },
  { key: 'stockAfter', label: 'Existencia', align: 'right', width: '120px' },
  { key: 'user', label: 'Usuario', width: '160px' },
]

// -------------------------------------------------- Entrada / salida manual ----

const movementOpen = ref(false)
const savingMovement = ref(false)
const movementErrors = ref<Record<string, string>>({})
const movement = reactive({
  productId: 0,
  type: MovementType.Purchase as number,
  quantity: 1,
  unitCost: 0,
  reason: '',
})

const selectedProduct = computed(() => products.value.find((p) => p.id === Number(movement.productId)))

const movementOptions = [
  { value: MovementType.Purchase, label: 'Entrada por compra' },
  { value: MovementType.ReturnIn, label: 'Devolución de cliente' },
  { value: MovementType.Sale, label: 'Salida por venta' },
  { value: MovementType.ReturnOut, label: 'Devolución a proveedor' },
  { value: MovementType.RepairConsumption, label: 'Consumo en reparación' },
]

const isInbound = computed(() =>
  [MovementType.Purchase, MovementType.ReturnIn].includes(Number(movement.type) as never),
)

function openMovement() {
  movementErrors.value = {}
  Object.assign(movement, {
    productId: stockedProducts.value[0]?.id ?? 0,
    type: MovementType.Purchase as number,
    quantity: 1,
    unitCost: 0,
    reason: '',
  })
  movementOpen.value = true
}

async function saveMovement() {
  savingMovement.value = true
  movementErrors.value = {}
  try {
    await inventoryApi.createMovement({
      productId: Number(movement.productId),
      type: Number(movement.type),
      quantity: Number(movement.quantity),
      unitCost: movement.unitCost || null,
      reason: movement.reason || null,
    })
    notifications.success('Movimiento registrado.')
    movementOpen.value = false
    await refresh()
  } catch (error) {
    movementErrors.value = fieldErrors(error)
    notifications.error(errorMessage(error))
  } finally {
    savingMovement.value = false
  }
}

// ---------------------------------------------------- Ajuste por conteo ----

const adjustOpen = ref(false)
const savingAdjust = ref(false)
const adjustErrors = ref<Record<string, string>>({})
const adjust = reactive({ productId: 0, countedStock: 0, reason: '' })

const adjustProduct = computed(() => products.value.find((p) => p.id === Number(adjust.productId)))

const adjustDelta = computed(() => {
  if (!adjustProduct.value) return 0
  return Number(adjust.countedStock) - adjustProduct.value.stock
})

function openAdjust() {
  adjustErrors.value = {}
  const first = stockedProducts.value[0]
  Object.assign(adjust, { productId: first?.id ?? 0, countedStock: first?.stock ?? 0, reason: '' })
  adjustOpen.value = true
}

function onAdjustProductChange() {
  adjust.countedStock = adjustProduct.value?.stock ?? 0
}

async function saveAdjust() {
  savingAdjust.value = true
  adjustErrors.value = {}
  try {
    await inventoryApi.adjust({
      productId: Number(adjust.productId),
      countedStock: Number(adjust.countedStock),
      reason: adjust.reason,
    })
    notifications.success('Ajuste aplicado.')
    adjustOpen.value = false
    await refresh()
  } catch (error) {
    adjustErrors.value = fieldErrors(error)
    notifications.error(errorMessage(error))
  } finally {
    savingAdjust.value = false
  }
}

async function loadProducts() {
  const result = await productsApi.list({ pageSize: 200, active: true })
  products.value = result.items
}

async function refresh() {
  await Promise.all([list.load(), loadProducts()])
}

onMounted(() => refresh().catch((error) => notifications.error(errorMessage(error))))
</script>

<template>
  <div>
    <PageHeader
      title="Inventario"
      description="Cada variación de existencias queda registrada con su motivo y su responsable."
    >
      <template #actions>
        <template v-if="auth.canWrite('inventory')">
          <BaseButton @click="openAdjust">Ajuste por conteo</BaseButton>
          <BaseButton variant="primary" @click="openMovement">Registrar movimiento</BaseButton>
        </template>
      </template>
    </PageHeader>

    <div class="toolbar">
      <input v-model="list.search.value" class="control search" type="search" placeholder="Buscar por producto o motivo…" />
      <select v-model="list.filters.value.productId" class="control filter">
        <option :value="null">Todos los productos</option>
        <option v-for="product in stockedProducts" :key="product.id" :value="product.id">
          {{ product.name }}
        </option>
      </select>
    </div>

    <DataTable
      :columns="columns"
      :rows="list.items.value"
      :loading="list.loading.value"
      :page="list.page.value"
      :page-size="list.pageSize"
      :total="list.total.value"
      empty-title="Sin movimientos"
      empty-message="Cuando registre entradas, salidas o ajustes, aparecerán aquí en orden cronológico."
      @update:page="list.page.value = $event"
    >
      <template #occurredAt="{ row }">
        <span class="num">{{ dateTime(row.occurredAt) }}</span>
      </template>

      <template #product="{ row }">
        <strong>{{ row.productName }}</strong>
        <small class="muted block num">{{ row.productCode }}</small>
      </template>

      <template #type="{ row }">
        <StatusBadge :tone="row.direction > 0 ? 'success' : 'warning'">{{ row.typeName }}</StatusBadge>
        <small v-if="row.reason" class="muted block reason">{{ row.reason }}</small>
      </template>

      <template #quantity="{ row }">
        <span class="num" :class="row.direction > 0 ? 'up' : 'down'">
          {{ row.direction > 0 ? '+' : '−' }}{{ number(row.quantity) }}
        </span>
        <small v-if="row.unitCost" class="muted block num">{{ currency(row.unitCost) }}</small>
      </template>

      <template #stockAfter="{ row }">
        <span class="num">{{ number(row.stockAfter) }}</span>
      </template>

      <template #user="{ row }">
        <span class="muted">{{ row.userName || '—' }}</span>
      </template>
    </DataTable>

    <!-- Movimiento manual -->
    <BaseModal
      :open="movementOpen"
      title="Registrar movimiento"
      subtitle="La existencia se recalcula al guardar; no se puede editar directamente."
      width="560px"
      @close="movementOpen = false"
    >
      <form id="movement-form" class="form-grid" @submit.prevent="saveMovement">
        <BaseField v-slot="{ id, invalid }" label="Producto" required span2 :error="movementErrors.productId">
          <select :id="id" v-model.number="movement.productId" class="control" :class="{ 'is-invalid': invalid }" required>
            <option :value="0" disabled>Seleccione…</option>
            <option v-for="product in stockedProducts" :key="product.id" :value="product.id">
              {{ product.code }} · {{ product.name }} (existencia {{ number(product.stock) }})
            </option>
          </select>
        </BaseField>

        <BaseField v-slot="{ id }" label="Motivo">
          <select :id="id" v-model.number="movement.type" class="control">
            <option v-for="option in movementOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </option>
          </select>
        </BaseField>

        <BaseField
          v-slot="{ id, invalid }"
          label="Cantidad"
          required
          :error="movementErrors.quantity"
          :hint="selectedProduct ? `Existencia actual: ${number(selectedProduct.stock)} ${selectedProduct.unitAbbreviation}` : undefined"
        >
          <input
            :id="id"
            v-model.number="movement.quantity"
            class="control"
            :class="{ 'is-invalid': invalid }"
            type="number"
            min="0.0001"
            step="0.01"
            required
          />
        </BaseField>

        <BaseField v-if="isInbound" v-slot="{ id }" label="Costo unitario" span2>
          <input :id="id" v-model.number="movement.unitCost" class="control" type="number" min="0" step="0.01" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Observaciones" span2 hint="Quedará visible en el historial.">
          <textarea :id="id" v-model="movement.reason" class="control" maxlength="250" />
        </BaseField>
      </form>

      <template #footer>
        <BaseButton variant="ghost" @click="movementOpen = false">Cancelar</BaseButton>
        <BaseButton type="submit" form="movement-form" variant="primary" :loading="savingMovement">
          Registrar
        </BaseButton>
      </template>
    </BaseModal>

    <!-- Ajuste por conteo físico -->
    <BaseModal
      :open="adjustOpen"
      title="Ajuste por conteo físico"
      subtitle="Indique la existencia real contada; el sistema calcula y registra la diferencia."
      width="560px"
      @close="adjustOpen = false"
    >
      <form id="adjust-form" class="form-grid" @submit.prevent="saveAdjust">
        <BaseField v-slot="{ id }" label="Producto" required span2>
          <select :id="id" v-model.number="adjust.productId" class="control" required @change="onAdjustProductChange">
            <option :value="0" disabled>Seleccione…</option>
            <option v-for="product in stockedProducts" :key="product.id" :value="product.id">
              {{ product.code }} · {{ product.name }}
            </option>
          </select>
        </BaseField>

        <BaseField v-slot="{ id }" label="Existencia registrada">
          <input :id="id" class="control" :value="number(adjustProduct?.stock ?? 0)" disabled />
        </BaseField>

        <BaseField
          v-slot="{ id, invalid }"
          label="Existencia contada"
          required
          :error="adjustErrors.countedStock"
          :hint="adjustDelta !== 0 ? `Diferencia: ${adjustDelta > 0 ? '+' : '−'}${number(Math.abs(adjustDelta))}` : 'Sin diferencia.'"
        >
          <input
            :id="id"
            v-model.number="adjust.countedStock"
            class="control"
            :class="{ 'is-invalid': invalid }"
            type="number"
            min="0"
            step="0.01"
            required
          />
        </BaseField>

        <BaseField v-slot="{ id, invalid }" label="Motivo del ajuste" required span2 :error="adjustErrors.reason">
          <textarea
            :id="id"
            v-model="adjust.reason"
            class="control"
            :class="{ 'is-invalid': invalid }"
            maxlength="250"
            required
            placeholder="Conteo mensual, producto dañado, error de digitación…"
          />
        </BaseField>
      </form>

      <template #footer>
        <BaseButton variant="ghost" @click="adjustOpen = false">Cancelar</BaseButton>
        <BaseButton
          type="submit"
          form="adjust-form"
          variant="primary"
          :loading="savingAdjust"
          :disabled="adjustDelta === 0"
        >
          Aplicar ajuste
        </BaseButton>
      </template>
    </BaseModal>
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
  width: 230px;
}

.block {
  display: block;
}

.reason {
  font-size: 12px;
  margin-top: 3px;
}

.up {
  color: var(--success-600);
  font-weight: 600;
}

.down {
  color: var(--accent-600);
  font-weight: 600;
}
</style>

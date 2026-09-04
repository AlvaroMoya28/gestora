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
import { productsApi, purchasesApi, suppliersApi } from '@/services/api'
import { errorMessage, fieldErrors } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import { PurchaseStatus, type Product, type Purchase, type PurchaseSummary, type Supplier } from '@/types'

const auth = useAuthStore()
const notifications = useNotificationStore()
const { currency, date } = useFormat()

const list = useResourceList<PurchaseSummary>(purchasesApi.list, { initialFilters: { status: null } })

const suppliers = ref<Supplier[]>([])
const products = ref<Product[]>([])
const stockedProducts = computed(() => products.value.filter((p) => p.isActive))

const columns: Column[] = [
  { key: 'number', label: 'Número', width: '110px' },
  { key: 'date', label: 'Fecha', width: '120px' },
  { key: 'supplier', label: 'Proveedor' },
  { key: 'status', label: 'Estado', width: '130px' },
  { key: 'total', label: 'Total', align: 'right', width: '130px' },
  { key: 'actions', label: '', align: 'right', width: '190px' },
]

const statusTone = (status: number) =>
  status === PurchaseStatus.Confirmed ? 'success' : status === PurchaseStatus.Cancelled ? 'neutral' : 'warning'

// ------------------------------------------------------------- Formulario ----

interface ItemRow {
  productId: number
  quantity: number
  unitCost: number
  taxRate: number
}

const emptyRow = (): ItemRow => {
  const first = stockedProducts.value[0]
  return { productId: first?.id ?? 0, quantity: 1, unitCost: first?.cost ?? 0, taxRate: first?.taxRate ?? 0 }
}

const modalOpen = ref(false)
const editing = ref<Purchase | null>(null)
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive({ supplierId: 0, supplierInvoiceNumber: '', notes: '' })
const rows = ref<ItemRow[]>([])

function productOf(id: number) {
  return products.value.find((p) => p.id === id)
}

function onProductChange(row: ItemRow) {
  const product = productOf(row.productId)
  if (product) {
    row.unitCost = product.cost
    row.taxRate = product.taxRate
  }
}

const lineSubtotal = (row: ItemRow) => row.quantity * row.unitCost
const totals = computed(() => {
  const subtotal = rows.value.reduce((sum, row) => sum + lineSubtotal(row), 0)
  const tax = rows.value.reduce((sum, row) => sum + (lineSubtotal(row) * row.taxRate) / 100, 0)
  return { subtotal, tax, total: subtotal + tax }
})

function openCreate() {
  editing.value = null
  errors.value = {}
  Object.assign(form, { supplierId: suppliers.value[0]?.id ?? 0, supplierInvoiceNumber: '', notes: '' })
  rows.value = [emptyRow()]
  modalOpen.value = true
}

async function openView(summary: PurchaseSummary) {
  const purchase = await purchasesApi.get(summary.id).catch((error) => {
    notifications.error(errorMessage(error))
    return null
  })
  if (!purchase) return

  editing.value = purchase
  errors.value = {}
  Object.assign(form, {
    supplierId: purchase.supplierId,
    supplierInvoiceNumber: purchase.supplierInvoiceNumber ?? '',
    notes: purchase.notes ?? '',
  })
  rows.value = purchase.items.map((item) => ({
    productId: item.productId,
    quantity: item.quantity,
    unitCost: item.unitCost,
    taxRate: item.taxRate,
  }))
  modalOpen.value = true
}

function addRow() {
  rows.value.push(emptyRow())
}

function removeRow(index: number) {
  rows.value.splice(index, 1)
}

const isDraft = computed(() => !editing.value || editing.value.status === PurchaseStatus.Draft)

async function save() {
  saving.value = true
  errors.value = {}
  try {
    const payload = {
      supplierId: Number(form.supplierId),
      supplierInvoiceNumber: form.supplierInvoiceNumber || null,
      notes: form.notes || null,
      items: rows.value.map((row) => ({
        productId: Number(row.productId),
        quantity: Number(row.quantity),
        unitCost: Number(row.unitCost),
        taxRate: Number(row.taxRate),
      })),
    }

    if (editing.value) {
      await purchasesApi.update(editing.value.id, payload)
      notifications.success('Compra actualizada.')
    } else {
      await purchasesApi.create(payload)
      notifications.success('Compra registrada como borrador.')
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

// ------------------------------------------------- Confirmar / cancelar ----

const confirming = ref<PurchaseSummary | null>(null)
const cancelling = ref<PurchaseSummary | null>(null)
const acting = ref(false)

async function doConfirm() {
  if (!confirming.value) return
  acting.value = true
  try {
    await purchasesApi.confirm(confirming.value.id)
    notifications.success('Compra confirmada: se actualizó el inventario y se generó la cuenta por pagar.')
    confirming.value = null
    await list.load()
  } catch (error) {
    notifications.error(errorMessage(error))
  } finally {
    acting.value = false
  }
}

async function doCancel() {
  if (!cancelling.value) return
  acting.value = true
  try {
    await purchasesApi.cancel(cancelling.value.id)
    notifications.success('Compra cancelada.')
    cancelling.value = null
    await list.load()
  } catch (error) {
    notifications.error(errorMessage(error))
  } finally {
    acting.value = false
  }
}

onMounted(async () => {
  await Promise.all([
    list.load(),
    suppliersApi.list({ pageSize: 200, active: true }).then((r) => (suppliers.value = r.items)),
    productsApi.list({ pageSize: 200, active: true }).then((r) => (products.value = r.items)),
  ]).catch((error) => notifications.error(errorMessage(error)))
})
</script>

<template>
  <div>
    <PageHeader
      title="Compras"
      description="Cada compra confirmada actualiza el inventario y genera la cuenta por pagar."
    >
      <template #actions>
        <BaseButton v-if="auth.canWrite('purchases')" variant="primary" @click="openCreate">
          Nueva compra
        </BaseButton>
      </template>
    </PageHeader>

    <div class="toolbar">
      <input v-model="list.search.value" class="control search" type="search" placeholder="Buscar por número, proveedor o factura…" />
      <select v-model="list.filters.value.status" class="control filter">
        <option :value="null">Todos los estados</option>
        <option :value="PurchaseStatus.Draft">Borrador</option>
        <option :value="PurchaseStatus.Confirmed">Confirmada</option>
        <option :value="PurchaseStatus.Cancelled">Cancelada</option>
      </select>
    </div>

    <DataTable
      :columns="columns"
      :rows="list.items.value"
      :loading="list.loading.value"
      :page="list.page.value"
      :page-size="list.pageSize"
      :total="list.total.value"
      empty-title="Sin compras"
      empty-message="Registre su primera compra a un proveedor."
      @update:page="list.page.value = $event"
    >
      <template #number="{ row }">
        <button class="link" type="button" @click="openView(row)">{{ row.number }}</button>
      </template>

      <template #date="{ row }"><span class="num">{{ date(row.date) }}</span></template>

      <template #supplier="{ row }">{{ row.supplierName }}</template>

      <template #status="{ row }">
        <StatusBadge :tone="statusTone(row.status)">{{ row.statusName }}</StatusBadge>
      </template>

      <template #total="{ row }"><span class="num">{{ currency(row.total) }}</span></template>

      <template #actions="{ row }">
        <div class="row-actions">
          <BaseButton size="sm" @click="openView(row)">
            {{ auth.canWrite('purchases') && row.status === PurchaseStatus.Draft ? 'Editar' : 'Ver' }}
          </BaseButton>
          <template v-if="auth.canWrite('purchases') && row.status === PurchaseStatus.Draft">
            <BaseButton size="sm" variant="primary" @click="confirming = row">Confirmar</BaseButton>
            <BaseButton size="sm" variant="ghost" @click="cancelling = row">Cancelar</BaseButton>
          </template>
        </div>
      </template>

      <template #empty-action>
        <BaseButton v-if="auth.canWrite('purchases')" variant="primary" @click="openCreate">
          Nueva compra
        </BaseButton>
      </template>
    </DataTable>

    <BaseModal
      :open="modalOpen"
      :title="editing ? `Compra ${editing.number}` : 'Nueva compra'"
      :subtitle="isDraft ? 'Mientras esté en borrador se puede editar libremente.' : 'Compra confirmada: solo lectura.'"
      width="820px"
      @close="modalOpen = false"
    >
      <form id="purchase-form" @submit.prevent="save">
        <div class="form-grid">
          <BaseField v-slot="{ id, invalid }" label="Proveedor" required :error="errors.supplierId">
            <select :id="id" v-model.number="form.supplierId" class="control" :class="{ 'is-invalid': invalid }" required :disabled="!isDraft">
              <option :value="0" disabled>Seleccione…</option>
              <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                {{ supplier.name }}
              </option>
            </select>
          </BaseField>

          <BaseField v-slot="{ id }" label="Número de factura del proveedor">
            <input :id="id" v-model="form.supplierInvoiceNumber" class="control" maxlength="60" :disabled="!isDraft" />
          </BaseField>

          <BaseField v-slot="{ id }" label="Observaciones" span2>
            <textarea :id="id" v-model="form.notes" class="control" maxlength="500" :disabled="!isDraft" />
          </BaseField>
        </div>

        <div class="items">
          <div class="items-header">
            <span>Producto</span>
            <span class="text-right">Cantidad</span>
            <span class="text-right">Costo unitario</span>
            <span class="text-right">Impuesto %</span>
            <span class="text-right">Subtotal</span>
            <span></span>
          </div>

          <div v-for="(row, index) in rows" :key="index" class="item-row">
            <select v-model.number="row.productId" class="control" :disabled="!isDraft" @change="onProductChange(row)">
              <option :value="0" disabled>Seleccione…</option>
              <option v-for="product in stockedProducts" :key="product.id" :value="product.id">
                {{ product.code }} · {{ product.name }}
              </option>
            </select>
            <input v-model.number="row.quantity" class="control text-right" type="number" min="0.0001" step="0.01" :disabled="!isDraft" />
            <input v-model.number="row.unitCost" class="control text-right" type="number" min="0" step="0.01" :disabled="!isDraft" />
            <input v-model.number="row.taxRate" class="control text-right" type="number" min="0" max="100" step="0.01" :disabled="!isDraft" />
            <span class="num text-right">{{ currency(lineSubtotal(row)) }}</span>
            <BaseButton v-if="isDraft" size="sm" variant="ghost" type="button" :disabled="rows.length <= 1" @click="removeRow(index)">
              ✕
            </BaseButton>
            <span v-else></span>
          </div>

          <BaseButton v-if="isDraft" size="sm" type="button" @click="addRow">Agregar línea</BaseButton>
        </div>

        <div class="totals">
          <div><span class="muted">Subtotal</span><span class="num">{{ currency(totals.subtotal) }}</span></div>
          <div><span class="muted">Impuesto</span><span class="num">{{ currency(totals.tax) }}</span></div>
          <div class="grand"><span>Total</span><span class="num">{{ currency(totals.total) }}</span></div>
        </div>
      </form>

      <template #footer>
        <BaseButton variant="ghost" @click="modalOpen = false">Cerrar</BaseButton>
        <BaseButton
          v-if="auth.canWrite('purchases') && isDraft"
          type="submit"
          form="purchase-form"
          variant="primary"
          :loading="saving"
          :disabled="rows.length === 0"
        >
          {{ editing ? 'Guardar cambios' : 'Guardar borrador' }}
        </BaseButton>
      </template>
    </BaseModal>

    <ConfirmDialog
      :open="!!confirming"
      title="Confirmar compra"
      :message="`Se registrará la entrada de inventario de ${confirming?.number} y se creará la cuenta por pagar a ${confirming?.supplierName}. Esta acción no se puede deshacer.`"
      confirm-label="Confirmar compra"
      :loading="acting"
      @cancel="confirming = null"
      @confirm="doConfirm"
    />

    <ConfirmDialog
      :open="!!cancelling"
      title="Cancelar compra"
      :message="`${cancelling?.number} quedará marcada como cancelada. No se puede confirmar después.`"
      confirm-label="Cancelar compra"
      variant="danger"
      :loading="acting"
      @cancel="cancelling = null"
      @confirm="doCancel"
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
  width: 180px;
}

.link {
  background: none;
  border: none;
  padding: 0;
  color: var(--brand-600);
  font-weight: 600;
  cursor: pointer;
  font: inherit;
}

.link:hover {
  text-decoration: underline;
}

.row-actions {
  display: flex;
  gap: 6px;
  justify-content: flex-end;
  flex-wrap: wrap;
}

.items {
  margin-top: 18px;
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  padding: 12px;
}

.items-header,
.item-row {
  display: grid;
  grid-template-columns: 2fr 100px 120px 90px 120px 36px;
  gap: 8px;
  align-items: center;
}

.items-header {
  font-size: 11.5px;
  font-weight: 650;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--ink-500);
  padding: 0 2px 8px;
}

.item-row {
  padding: 4px 0;
}

.items > .btn {
  margin-top: 8px;
}

.totals {
  margin-top: 16px;
  margin-left: auto;
  width: 260px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.totals > div {
  display: flex;
  justify-content: space-between;
  font-size: 13.5px;
}

.totals .grand {
  font-weight: 700;
  font-size: 15px;
  border-top: 1px solid var(--ink-200);
  padding-top: 8px;
  margin-top: 2px;
}
</style>

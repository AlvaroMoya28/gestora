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
import {
  DUE_CUSTOM,
  DUE_OPTIONS,
  todayIso,
  usePaymentTerms,
} from '@/composables/usePaymentTerms'
import { useResourceList } from '@/composables/useResourceList'
import { customersApi, productsApi, salesApi } from '@/services/api'
import { errorMessage, fieldErrors } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import { PaymentTerm, SaleStatus, type Customer, type Product, type Sale, type SaleSummary } from '@/types'

const auth = useAuthStore()
const notifications = useNotificationStore()
const { currency, date } = useFormat()

const list = useResourceList<SaleSummary>(salesApi.list, { initialFilters: { status: null } })

const customers = ref<Customer[]>([])
const products = ref<Product[]>([])
const sellable = computed(() => products.value.filter((p) => p.isActive))

const columns: Column[] = [
  { key: 'number', label: 'Número', width: '105px' },
  { key: 'date', label: 'Fecha', width: '115px' },
  { key: 'customer', label: 'Cliente' },
  { key: 'term', label: 'Condición', width: '105px' },
  { key: 'dueDate', label: 'Vence', width: '115px' },
  { key: 'status', label: 'Estado', width: '115px' },
  { key: 'total', label: 'Total', align: 'right', width: '125px' },
  { key: 'actions', label: '', align: 'right', width: '185px' },
]

const statusTone = (status: number) =>
  status === SaleStatus.Confirmed ? 'success' : status === SaleStatus.Cancelled ? 'neutral' : 'warning'

// ------------------------------------------------------------- Formulario ----

interface ItemRow {
  productId: number
  quantity: number
  unitPrice: number
  discountRate: number
  taxRate: number
  stock: number
}

const emptyRow = (): ItemRow => {
  const first = sellable.value[0]
  return {
    productId: first?.id ?? 0,
    quantity: 1,
    unitPrice: first?.price ?? 0,
    discountRate: 0,
    taxRate: first?.taxRate ?? 0,
    stock: first?.stock ?? 0,
  }
}

const modalOpen = ref(false)
const editing = ref<Sale | null>(null)
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive({ customerId: 0, date: todayIso(), notes: '' })
const rows = ref<ItemRow[]>([])

const terms = usePaymentTerms(computed(() => form.date))

function onProductChange(row: ItemRow) {
  const product = products.value.find((p) => p.id === row.productId)
  if (product) {
    row.unitPrice = product.price
    row.taxRate = product.taxRate
    row.stock = product.stock
  }
}

/** Al elegir cliente se trae su condición pactada, que igual se puede cambiar. */
function onCustomerChange() {
  const customer = customers.value.find((c) => c.id === Number(form.customerId))
  if (!customer) return

  terms.loadFrom(customer.creditDays, customer.paymentTerm === PaymentTerm.Credit)
}

const lineSubtotal = (row: ItemRow) => {
  const gross = row.quantity * row.unitPrice
  return gross - (gross * row.discountRate) / 100
}

const totals = computed(() => {
  const subtotal = rows.value.reduce((sum, row) => sum + lineSubtotal(row), 0)
  const discount = rows.value.reduce(
    (sum, row) => sum + (row.quantity * row.unitPrice * row.discountRate) / 100,
    0,
  )
  const tax = rows.value.reduce((sum, row) => sum + (lineSubtotal(row) * row.taxRate) / 100, 0)
  return { subtotal, discount, tax, total: subtotal + tax }
})

/** Aviso temprano: el backend igual rechaza la confirmación si no alcanza la existencia. */
const shortStock = computed(() =>
  rows.value.filter((row) => {
    const product = products.value.find((p) => p.id === row.productId)
    return product && product.stockStatus !== undefined && product.stock < row.quantity && product.type !== 2
  }),
)

function openCreate() {
  editing.value = null
  errors.value = {}
  Object.assign(form, { customerId: customers.value[0]?.id ?? 0, date: todayIso(), notes: '' })
  onCustomerChange()
  rows.value = [emptyRow()]
  modalOpen.value = true
}

async function openView(summary: SaleSummary) {
  const sale = await salesApi.get(summary.id).catch((error) => {
    notifications.error(errorMessage(error))
    return null
  })
  if (!sale) return

  editing.value = sale
  errors.value = {}
  Object.assign(form, {
    customerId: sale.customerId,
    date: sale.date.slice(0, 10),
    notes: sale.notes ?? '',
  })
  terms.loadFrom(sale.creditDays, sale.paymentTerm === PaymentTerm.Credit)
  rows.value = sale.items.map((item) => ({
    productId: item.productId,
    quantity: item.quantity,
    unitPrice: item.unitPrice,
    discountRate: item.discountRate,
    taxRate: item.taxRate,
    stock: products.value.find((p) => p.id === item.productId)?.stock ?? 0,
  }))
  modalOpen.value = true
}

const addRow = () => rows.value.push(emptyRow())
const removeRow = (index: number) => rows.value.splice(index, 1)

const isDraft = computed(() => !editing.value || editing.value.status === SaleStatus.Draft)

async function save() {
  saving.value = true
  errors.value = {}
  try {
    const payload = {
      customerId: Number(form.customerId),
      date: form.date,
      paymentTerm: terms.isCredit.value ? PaymentTerm.Credit : PaymentTerm.Cash,
      creditDays: terms.creditDays.value,
      notes: form.notes || null,
      items: rows.value.map((row) => ({
        productId: Number(row.productId),
        quantity: Number(row.quantity),
        unitPrice: Number(row.unitPrice),
        discountRate: Number(row.discountRate),
        taxRate: Number(row.taxRate),
      })),
    }

    if (editing.value) {
      await salesApi.update(editing.value.id, payload)
      notifications.success('Venta actualizada.')
    } else {
      await salesApi.create(payload)
      notifications.success('Venta registrada como borrador.')
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

const confirming = ref<SaleSummary | null>(null)
const cancelling = ref<SaleSummary | null>(null)
const acting = ref(false)

async function doConfirm() {
  if (!confirming.value) return
  acting.value = true
  try {
    await salesApi.confirm(confirming.value.id)
    notifications.success('Venta confirmada: se descargó el inventario y se generó la cuenta por cobrar.')
    confirming.value = null
    await Promise.all([list.load(), reloadProducts()])
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
    await salesApi.cancel(cancelling.value.id)
    notifications.success('Venta cancelada.')
    cancelling.value = null
    await list.load()
  } catch (error) {
    notifications.error(errorMessage(error))
  } finally {
    acting.value = false
  }
}

const reloadProducts = () =>
  productsApi.list({ pageSize: 200, active: true }).then((r) => (products.value = r.items))

onMounted(async () => {
  await Promise.all([
    list.load(),
    customersApi.list({ pageSize: 200, active: true }).then((r) => (customers.value = r.items)),
    reloadProducts(),
  ]).catch((error) => notifications.error(errorMessage(error)))
})
</script>

<template>
  <div>
    <PageHeader
      title="Ventas"
      description="Cada venta confirmada descarga el inventario y genera la cuenta por cobrar."
    >
      <template #actions>
        <BaseButton v-if="auth.canWrite('sales')" variant="primary" @click="openCreate">
          Nueva venta
        </BaseButton>
      </template>
    </PageHeader>

    <div class="toolbar">
      <input
        v-model="list.search.value"
        class="control search"
        type="search"
        placeholder="Buscar por número o cliente…"
      />
      <select v-model="list.filters.value.status" class="control filter">
        <option :value="null">Todos los estados</option>
        <option :value="SaleStatus.Draft">Borrador</option>
        <option :value="SaleStatus.Confirmed">Confirmada</option>
        <option :value="SaleStatus.Cancelled">Cancelada</option>
      </select>
    </div>

    <DataTable
      :columns="columns"
      :rows="list.items.value"
      :loading="list.loading.value"
      :page="list.page.value"
      :page-size="list.pageSize"
      :total="list.total.value"
      empty-title="Sin ventas"
      empty-message="Registre su primera venta a un cliente."
      @update:page="list.page.value = $event"
    >
      <template #number="{ row }">
        <button class="link" type="button" @click="openView(row)">{{ row.number }}</button>
      </template>

      <template #date="{ row }"><span class="num">{{ date(row.date) }}</span></template>

      <template #customer="{ row }">{{ row.customerName }}</template>

      <template #term="{ row }">
        <span class="muted">{{ row.paymentTerm === PaymentTerm.Credit ? 'Crédito' : 'Contado' }}</span>
      </template>

      <template #dueDate="{ row }">
        <span class="num muted">{{ date(row.dueDate) }}</span>
      </template>

      <template #status="{ row }">
        <StatusBadge :tone="statusTone(row.status)">{{ row.statusName }}</StatusBadge>
      </template>

      <template #total="{ row }"><span class="num">{{ currency(row.total) }}</span></template>

      <template #actions="{ row }">
        <div class="row-actions">
          <BaseButton size="sm" @click="openView(row)">
            {{ auth.canWrite('sales') && row.status === SaleStatus.Draft ? 'Editar' : 'Ver' }}
          </BaseButton>
          <template v-if="auth.canWrite('sales') && row.status === SaleStatus.Draft">
            <BaseButton size="sm" variant="primary" @click="confirming = row">Confirmar</BaseButton>
            <BaseButton size="sm" variant="ghost" @click="cancelling = row">Cancelar</BaseButton>
          </template>
        </div>
      </template>

      <template #empty-action>
        <BaseButton v-if="auth.canWrite('sales')" variant="primary" @click="openCreate">
          Nueva venta
        </BaseButton>
      </template>
    </DataTable>

    <BaseModal
      :open="modalOpen"
      :title="editing ? `Venta ${editing.number}` : 'Nueva venta'"
      :subtitle="isDraft ? 'Mientras esté en borrador se puede editar libremente.' : 'Venta confirmada: solo lectura.'"
      width="880px"
      @close="modalOpen = false"
    >
      <form id="sale-form" @submit.prevent="save">
        <div class="form-grid">
          <BaseField v-slot="{ id, invalid }" label="Cliente" required :error="errors.customerId">
            <select
              :id="id"
              v-model.number="form.customerId"
              class="control"
              :class="{ 'is-invalid': invalid }"
              required
              :disabled="!isDraft"
              @change="onCustomerChange"
            >
              <option :value="0" disabled>Seleccione…</option>
              <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                {{ customer.name }}
              </option>
            </select>
          </BaseField>

          <BaseField
            v-slot="{ id }"
            label="Fecha de la venta"
            required
            hint="Cuándo se hizo la venta, no la de hoy. Desde ella se cuenta el plazo."
          >
            <input :id="id" v-model="form.date" class="control" type="date" required :disabled="!isDraft" />
          </BaseField>

          <BaseField v-slot="{ id }" label="¿Cuándo paga el cliente?" hint="Plazo acordado con el cliente.">
            <select :id="id" v-model="terms.option.value" class="control" :disabled="!isDraft">
              <option v-for="opt in DUE_OPTIONS" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </option>
            </select>
          </BaseField>

          <BaseField v-if="terms.option.value === DUE_CUSTOM" v-slot="{ id }" label="Vence el">
            <input
              :id="id"
              v-model="terms.customDate.value"
              class="control"
              type="date"
              :min="form.date"
              :disabled="!isDraft"
            />
          </BaseField>

          <!-- Siempre a la vista la fecha concreta: es hasta cuándo tiene el cliente para pagar. -->
          <p class="due span2">
            El cliente debe pagar a más tardar el
            <strong>{{ date(terms.dueDateIso.value) }}</strong>
            <span v-if="terms.isCredit.value" class="muted">
              · {{ terms.creditDays.value }} día(s) de plazo
            </span>
          </p>

          <BaseField v-slot="{ id }" label="Observaciones" span2>
            <textarea :id="id" v-model="form.notes" class="control" maxlength="500" :disabled="!isDraft" />
          </BaseField>
        </div>

        <div class="items">
          <div class="items-header">
            <span>Producto</span>
            <span class="text-right">Cantidad</span>
            <span class="text-right">Precio</span>
            <span class="text-right">Desc. %</span>
            <span class="text-right">Imp. %</span>
            <span class="text-right">Subtotal</span>
            <span></span>
          </div>

          <div v-for="(row, index) in rows" :key="index" class="item-row">
            <div>
              <select
                v-model.number="row.productId"
                class="control"
                :disabled="!isDraft"
                @change="onProductChange(row)"
              >
                <option :value="0" disabled>Seleccione…</option>
                <option v-for="product in sellable" :key="product.id" :value="product.id">
                  {{ product.code }} · {{ product.name }}
                </option>
              </select>
              <small class="stock muted">Existencia: {{ row.stock }}</small>
            </div>
            <input v-model.number="row.quantity" class="control text-right" type="number" min="0.0001" step="any" :disabled="!isDraft" />
            <input v-model.number="row.unitPrice" class="control text-right" type="number" min="0" step="0.01" :disabled="!isDraft" />
            <input v-model.number="row.discountRate" class="control text-right" type="number" min="0" max="100" step="0.01" :disabled="!isDraft" />
            <input v-model.number="row.taxRate" class="control text-right" type="number" min="0" max="100" step="0.01" :disabled="!isDraft" />
            <span class="num text-right">{{ currency(lineSubtotal(row)) }}</span>
            <BaseButton v-if="isDraft" size="sm" variant="ghost" type="button" :disabled="rows.length <= 1" @click="removeRow(index)">
              ✕
            </BaseButton>
            <span v-else></span>
          </div>

          <BaseButton v-if="isDraft" size="sm" type="button" @click="addRow">Agregar línea</BaseButton>
        </div>

        <p v-if="shortStock.length && isDraft" class="warn">
          Hay líneas con más cantidad que existencia disponible. La confirmación será rechazada.
        </p>

        <div class="totals">
          <div><span class="muted">Subtotal</span><span class="num">{{ currency(totals.subtotal) }}</span></div>
          <div v-if="totals.discount > 0">
            <span class="muted">Descuento</span><span class="num">− {{ currency(totals.discount) }}</span>
          </div>
          <div><span class="muted">Impuesto</span><span class="num">{{ currency(totals.tax) }}</span></div>
          <div class="grand"><span>Total</span><span class="num">{{ currency(totals.total) }}</span></div>
        </div>
      </form>

      <template #footer>
        <BaseButton variant="ghost" @click="modalOpen = false">Cerrar</BaseButton>
        <BaseButton
          v-if="auth.canWrite('sales') && isDraft"
          type="submit"
          form="sale-form"
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
      title="Confirmar venta"
      :message="`Se descargará el inventario de ${confirming?.number} y se creará la cuenta por cobrar a ${confirming?.customerName}. Esta acción no se puede deshacer.`"
      confirm-label="Confirmar venta"
      :loading="acting"
      @cancel="confirming = null"
      @confirm="doConfirm"
    />

    <ConfirmDialog
      :open="!!cancelling"
      title="Cancelar venta"
      :message="`${cancelling?.number} quedará marcada como cancelada. No se puede confirmar después.`"
      confirm-label="Cancelar venta"
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
  grid-template-columns: 2fr 90px 110px 80px 80px 120px 36px;
  gap: 8px;
  align-items: start;
}

.items-header {
  font-size: 11.5px;
  font-weight: 650;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--ink-500);
  padding: 0 2px 8px;
  align-items: center;
}

.item-row {
  padding: 4px 0;
}

.item-row > input,
.item-row > span,
.item-row > .btn {
  margin-top: 0;
}

.stock {
  display: block;
  font-size: 11px;
  margin-top: 3px;
}

.items > .btn {
  margin-top: 8px;
}

.due {
  margin: 2px 0 0;
  font-size: 13px;
  align-self: center;
}

.warn {
  margin-top: 12px;
  background: var(--warning-100, #fff4e0);
  color: var(--warning-600, #92400e);
  border-radius: var(--radius-sm);
  padding: 9px 12px;
  font-size: 13px;
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

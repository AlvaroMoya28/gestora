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
import { customersApi, productsApi, repairsApi } from '@/services/api'
import { errorMessage, fieldErrors } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import {
  PaymentTerm,
  RepairStatus,
  type Customer,
  type Product,
  type RepairOrder,
  type RepairOrderSummary,
} from '@/types'

const auth = useAuthStore()
const notifications = useNotificationStore()
const { currency, number, date } = useFormat()

const list = useResourceList<RepairOrderSummary>(repairsApi.list, {
  initialFilters: { status: null, late: null },
})

const customers = ref<Customer[]>([])
const products = ref<Product[]>([])
const usable = computed(() => products.value.filter((p) => p.isActive))

const columns: Column[] = [
  { key: 'number', label: 'Orden', width: '105px' },
  { key: 'customer', label: 'Cliente' },
  { key: 'item', label: 'Artículo' },
  { key: 'promised', label: 'Promesa', width: '125px' },
  { key: 'status', label: 'Estado', width: '150px' },
  { key: 'total', label: 'Total', align: 'right', width: '120px' },
  { key: 'actions', label: '', align: 'right', width: '210px' },
]

const statusTone = (row: RepairOrderSummary) => {
  if (row.status === RepairStatus.Delivered) return 'success'
  if (row.status === RepairStatus.Cancelled) return 'neutral'
  if (row.isLate) return 'danger'
  if (row.status === RepairStatus.Ready) return 'info'
  return 'warning'
}

// ------------------------------------------------------------- Formulario ----

interface MaterialRow {
  productId: number
  quantity: number
  unitPrice: number
}

const modalOpen = ref(false)
const editing = ref<RepairOrder | null>(null)
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive({
  customerId: 0,
  itemDescription: '',
  reportedIssue: '',
  diagnosis: '',
  promisedAt: '',
  laborCost: 0,
  taxRate: 13,
  paymentTerm: PaymentTerm.Cash as number,
  creditDays: 0,
  notes: '',
})
const materialRows = ref<MaterialRow[]>([])

const productOf = (id: number) => products.value.find((p) => p.id === id)

function onMaterialChange(row: MaterialRow) {
  const product = productOf(row.productId)
  if (product) row.unitPrice = product.price
}

const totals = computed(() => {
  const materials = materialRows.value.reduce((sum, row) => sum + row.quantity * row.unitPrice, 0)
  const taxable = materials + Number(form.laborCost || 0)
  const tax = (taxable * Number(form.taxRate || 0)) / 100
  return { materials, taxable, tax, total: taxable + tax }
})

/** Editable mientras no haya descargado material ni se haya entregado. */
const editable = computed(
  () =>
    !editing.value ||
    editing.value.status === RepairStatus.Received ||
    editing.value.status === RepairStatus.InProgress,
)

function openCreate() {
  editing.value = null
  errors.value = {}
  Object.assign(form, {
    customerId: customers.value[0]?.id ?? 0,
    itemDescription: '',
    reportedIssue: '',
    diagnosis: '',
    promisedAt: '',
    laborCost: 0,
    taxRate: 13,
    paymentTerm: PaymentTerm.Cash as number,
    creditDays: 0,
    notes: '',
  })
  materialRows.value = []
  modalOpen.value = true
}

async function openView(summary: RepairOrderSummary) {
  const repair = await repairsApi.get(summary.id).catch((error) => {
    notifications.error(errorMessage(error))
    return null
  })
  if (!repair) return

  editing.value = repair
  errors.value = {}
  Object.assign(form, {
    customerId: repair.customerId,
    itemDescription: repair.itemDescription,
    reportedIssue: repair.reportedIssue ?? '',
    diagnosis: repair.diagnosis ?? '',
    promisedAt: repair.promisedAt ? repair.promisedAt.slice(0, 10) : '',
    laborCost: repair.laborCost,
    taxRate: repair.taxRate,
    paymentTerm: repair.paymentTerm as number,
    creditDays: repair.creditDays,
    notes: repair.notes ?? '',
  })
  materialRows.value = repair.materials.map((m) => ({
    productId: m.productId,
    quantity: m.quantity,
    unitPrice: m.unitPrice,
  }))
  modalOpen.value = true
}

const addMaterial = () => {
  const first = usable.value[0]
  materialRows.value.push({ productId: first?.id ?? 0, quantity: 1, unitPrice: first?.price ?? 0 })
}
const removeMaterial = (index: number) => materialRows.value.splice(index, 1)

async function save() {
  saving.value = true
  errors.value = {}
  try {
    const payload = {
      customerId: Number(form.customerId),
      itemDescription: form.itemDescription,
      reportedIssue: form.reportedIssue || null,
      diagnosis: form.diagnosis || null,
      promisedAt: form.promisedAt || null,
      laborCost: Number(form.laborCost),
      taxRate: Number(form.taxRate),
      paymentTerm: Number(form.paymentTerm),
      creditDays: Number(form.creditDays),
      notes: form.notes || null,
      materials: materialRows.value.map((row) => ({
        productId: Number(row.productId),
        quantity: Number(row.quantity),
        unitPrice: Number(row.unitPrice),
      })),
    }

    if (editing.value) {
      await repairsApi.update(editing.value.id, payload)
      notifications.success('Reparación actualizada.')
    } else {
      await repairsApi.create(payload)
      notifications.success('Reparación registrada.')
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

// ------------------------------------------------------------- Transiciones ----

const acting = ref(false)
const delivering = ref<RepairOrderSummary | null>(null)
const cancelling = ref<RepairOrderSummary | null>(null)

async function start(row: RepairOrderSummary) {
  try {
    await repairsApi.start(row.id)
    notifications.success('Reparación en proceso.')
    await list.load()
  } catch (error) {
    notifications.error(errorMessage(error))
  }
}

const completeModal = ref(false)
const completing = ref(false)
const completeTarget = ref<RepairOrder | null>(null)
const completeForm = reactive({ diagnosis: '', laborCost: 0 })

async function openComplete(summary: RepairOrderSummary) {
  const repair = await repairsApi.get(summary.id).catch((error) => {
    notifications.error(errorMessage(error))
    return null
  })
  if (!repair) return

  completeTarget.value = repair
  Object.assign(completeForm, { diagnosis: repair.diagnosis ?? '', laborCost: repair.laborCost })
  completeModal.value = true
}

async function submitComplete() {
  if (!completeTarget.value) return
  completing.value = true
  try {
    await repairsApi.complete(completeTarget.value.id, {
      diagnosis: completeForm.diagnosis || null,
      laborCost: Number(completeForm.laborCost),
    })
    notifications.success('Reparación terminada: el material usado salió del inventario.')
    completeModal.value = false
    await Promise.all([list.load(), loadProducts()])
  } catch (error) {
    notifications.error(errorMessage(error))
  } finally {
    completing.value = false
  }
}

async function doDeliver() {
  if (!delivering.value) return
  acting.value = true
  try {
    await repairsApi.deliver(delivering.value.id)
    notifications.success('Reparación entregada: se generó la cuenta por cobrar.')
    delivering.value = null
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
    await repairsApi.cancel(cancelling.value.id)
    notifications.success('Reparación cancelada.')
    cancelling.value = null
    await list.load()
  } catch (error) {
    notifications.error(errorMessage(error))
  } finally {
    acting.value = false
  }
}

const lateCount = computed(() => list.items.value.filter((r) => r.isLate).length)

const loadProducts = () =>
  productsApi.list({ pageSize: 300, active: true }).then((r) => (products.value = r.items))

onMounted(async () => {
  await Promise.all([
    list.load(),
    customersApi.list({ pageSize: 200, active: true }).then((r) => (customers.value = r.items)),
    loadProducts(),
  ]).catch((error) => notifications.error(errorMessage(error)))
})
</script>

<template>
  <div>
    <PageHeader
      title="Reparaciones"
      description="El material sale del inventario al terminar el trabajo; el cobro nace al entregar."
    >
      <template #actions>
        <BaseButton v-if="auth.canWrite('repairs')" variant="primary" @click="openCreate">
          Recibir artículo
        </BaseButton>
      </template>
    </PageHeader>

    <p v-if="lateCount > 0" class="alert">
      {{ lateCount }} reparación(es) pasaron su fecha prometida.
    </p>

    <div class="toolbar">
      <input
        v-model="list.search.value"
        class="control search"
        type="search"
        placeholder="Buscar por número, cliente o artículo…"
      />
      <select v-model="list.filters.value.status" class="control filter">
        <option :value="null">Todos los estados</option>
        <option :value="RepairStatus.Received">Recibida</option>
        <option :value="RepairStatus.InProgress">En proceso</option>
        <option :value="RepairStatus.Ready">Lista para entregar</option>
        <option :value="RepairStatus.Delivered">Entregada</option>
        <option :value="RepairStatus.Cancelled">Cancelada</option>
      </select>
      <select v-model="list.filters.value.late" class="control filter">
        <option :value="null">Todas las fechas</option>
        <option :value="true">Solo atrasadas</option>
      </select>
    </div>

    <DataTable
      :columns="columns"
      :rows="list.items.value"
      :loading="list.loading.value"
      :page="list.page.value"
      :page-size="list.pageSize"
      :total="list.total.value"
      empty-title="Sin reparaciones"
      empty-message="Registre el primer artículo que reciba a reparar."
      @update:page="list.page.value = $event"
    >
      <template #number="{ row }">
        <button class="link" type="button" @click="openView(row)">{{ row.number }}</button>
      </template>

      <template #customer="{ row }">{{ row.customerName }}</template>

      <template #item="{ row }"><span class="item">{{ row.itemDescription }}</span></template>

      <template #promised="{ row }">
        <span class="num" :class="{ late: row.isLate }">{{ date(row.promisedAt) }}</span>
      </template>

      <template #status="{ row }">
        <StatusBadge :tone="statusTone(row)">
          {{ row.isLate ? `${row.statusName} · atrasada` : row.statusName }}
        </StatusBadge>
      </template>

      <template #total="{ row }"><span class="num">{{ currency(row.total) }}</span></template>

      <template #actions="{ row }">
        <div class="row-actions">
          <BaseButton size="sm" @click="openView(row)">Ver</BaseButton>
          <template v-if="auth.canWrite('repairs')">
            <BaseButton v-if="row.status === RepairStatus.Received" size="sm" @click="start(row)">
              Iniciar
            </BaseButton>
            <BaseButton
              v-if="row.status === RepairStatus.Received || row.status === RepairStatus.InProgress"
              size="sm"
              variant="primary"
              @click="openComplete(row)"
            >
              Terminar
            </BaseButton>
            <BaseButton
              v-if="row.status === RepairStatus.Ready"
              size="sm"
              variant="primary"
              @click="delivering = row"
            >
              Entregar
            </BaseButton>
            <BaseButton
              v-if="row.status === RepairStatus.Received || row.status === RepairStatus.InProgress"
              size="sm"
              variant="ghost"
              @click="cancelling = row"
            >
              Cancelar
            </BaseButton>
          </template>
        </div>
      </template>

      <template #empty-action>
        <BaseButton v-if="auth.canWrite('repairs')" variant="primary" @click="openCreate">
          Recibir artículo
        </BaseButton>
      </template>
    </DataTable>

    <!-- Alta / edición -->
    <BaseModal
      :open="modalOpen"
      :title="editing ? `Reparación ${editing.number}` : 'Recibir artículo a reparar'"
      :subtitle="editing && !editable ? 'Esta orden ya no admite cambios.' : undefined"
      width="840px"
      @close="modalOpen = false"
    >
      <form id="repair-form" @submit.prevent="save">
        <div class="form-grid">
          <BaseField v-slot="{ id, invalid }" label="Cliente" required :error="errors.customerId">
            <select
              :id="id"
              v-model.number="form.customerId"
              class="control"
              :class="{ 'is-invalid': invalid }"
              required
              :disabled="!editable"
            >
              <option :value="0" disabled>Seleccione…</option>
              <option v-for="customer in customers" :key="customer.id" :value="customer.id">
                {{ customer.name }}
              </option>
            </select>
          </BaseField>

          <BaseField v-slot="{ id }" label="Fecha prometida">
            <input :id="id" v-model="form.promisedAt" class="control" type="date" :disabled="!editable" />
          </BaseField>

          <BaseField
            v-slot="{ id, invalid }"
            label="Artículo recibido"
            required
            span2
            :error="errors.itemDescription"
            hint="Qué se recibió: marca, modelo, señas particulares."
          >
            <input
              :id="id"
              v-model="form.itemDescription"
              class="control"
              :class="{ 'is-invalid': invalid }"
              maxlength="200"
              required
              :disabled="!editable"
            />
          </BaseField>

          <BaseField v-slot="{ id }" label="Falla reportada por el cliente" span2>
            <textarea :id="id" v-model="form.reportedIssue" class="control" maxlength="1000" :disabled="!editable" />
          </BaseField>

          <BaseField v-slot="{ id }" label="Diagnóstico del taller" span2>
            <textarea :id="id" v-model="form.diagnosis" class="control" maxlength="1000" :disabled="!editable" />
          </BaseField>

          <BaseField v-slot="{ id }" label="Mano de obra" required>
            <input :id="id" v-model.number="form.laborCost" class="control" type="number" min="0" step="0.01" :disabled="!editable" />
          </BaseField>

          <BaseField v-slot="{ id }" label="Impuesto %">
            <input :id="id" v-model.number="form.taxRate" class="control" type="number" min="0" max="100" step="0.01" :disabled="!editable" />
          </BaseField>

          <BaseField v-slot="{ id }" label="Condición de pago">
            <select :id="id" v-model.number="form.paymentTerm" class="control" :disabled="!editable">
              <option :value="PaymentTerm.Cash">Contado</option>
              <option :value="PaymentTerm.Credit">Crédito</option>
            </select>
          </BaseField>

          <BaseField v-slot="{ id }" label="Días de crédito">
            <input
              :id="id"
              v-model.number="form.creditDays"
              class="control"
              type="number"
              min="0"
              max="365"
              :disabled="!editable || form.paymentTerm !== PaymentTerm.Credit"
            />
          </BaseField>

          <BaseField v-slot="{ id }" label="Observaciones" span2>
            <textarea :id="id" v-model="form.notes" class="control" maxlength="500" :disabled="!editable" />
          </BaseField>
        </div>

        <div class="items">
          <div class="items-header">
            <span>Material o repuesto</span>
            <span class="text-right">Cantidad</span>
            <span class="text-right">Precio</span>
            <span class="text-right">Subtotal</span>
            <span></span>
          </div>

          <div v-for="(row, index) in materialRows" :key="index" class="item-row">
            <select v-model.number="row.productId" class="control" :disabled="!editable" @change="onMaterialChange(row)">
              <option v-for="product in usable" :key="product.id" :value="product.id">
                {{ product.code }} · {{ product.name }} (hay {{ number(product.stock) }})
              </option>
            </select>
            <input v-model.number="row.quantity" class="control text-right" type="number" min="0.0001" step="any" :disabled="!editable" />
            <input v-model.number="row.unitPrice" class="control text-right" type="number" min="0" step="0.01" :disabled="!editable" />
            <span class="num text-right">{{ currency(row.quantity * row.unitPrice) }}</span>
            <BaseButton v-if="editable" size="sm" variant="ghost" type="button" @click="removeMaterial(index)">
              ✕
            </BaseButton>
            <span v-else></span>
          </div>

          <p v-if="!materialRows.length" class="muted no-materials">
            Sin materiales: solo se cobra mano de obra.
          </p>

          <BaseButton v-if="editable" size="sm" type="button" @click="addMaterial">
            Agregar material
          </BaseButton>
        </div>

        <div class="totals">
          <div><span class="muted">Materiales</span><span class="num">{{ currency(totals.materials) }}</span></div>
          <div><span class="muted">Mano de obra</span><span class="num">{{ currency(Number(form.laborCost || 0)) }}</span></div>
          <div><span class="muted">Impuesto</span><span class="num">{{ currency(totals.tax) }}</span></div>
          <div class="grand"><span>Total a cobrar</span><span class="num">{{ currency(totals.total) }}</span></div>
        </div>
      </form>

      <template #footer>
        <BaseButton variant="ghost" @click="modalOpen = false">Cerrar</BaseButton>
        <BaseButton
          v-if="auth.canWrite('repairs') && editable"
          type="submit"
          form="repair-form"
          variant="primary"
          :loading="saving"
        >
          {{ editing ? 'Guardar cambios' : 'Registrar reparación' }}
        </BaseButton>
      </template>
    </BaseModal>

    <!-- Terminar -->
    <BaseModal
      :open="completeModal"
      title="Terminar reparación"
      :subtitle="completeTarget ? `${completeTarget.number} · ${completeTarget.itemDescription}` : undefined"
      width="560px"
      @close="completeModal = false"
    >
      <form id="complete-repair-form" class="form-grid" @submit.prevent="submitComplete">
        <BaseField v-slot="{ id }" label="Diagnóstico final" span2>
          <textarea :id="id" v-model="completeForm.diagnosis" class="control" maxlength="1000" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Mano de obra" hint="Ajústela si el trabajo resultó distinto.">
          <input :id="id" v-model.number="completeForm.laborCost" class="control" type="number" min="0" step="0.01" />
        </BaseField>
      </form>

      <p class="hint-box">
        Al confirmar, los materiales cargados a esta orden salen del inventario. Después ya no se
        podrán cambiar sus líneas.
      </p>

      <template #footer>
        <BaseButton variant="ghost" @click="completeModal = false">Cancelar</BaseButton>
        <BaseButton type="submit" form="complete-repair-form" variant="primary" :loading="completing">
          Terminar reparación
        </BaseButton>
      </template>
    </BaseModal>

    <ConfirmDialog
      :open="!!delivering"
      title="Entregar reparación"
      :message="`Se entregará ${delivering?.number} a ${delivering?.customerName} y se generará la cuenta por cobrar por ${currency(delivering?.total ?? 0)}.`"
      confirm-label="Entregar"
      :loading="acting"
      @cancel="delivering = null"
      @confirm="doDeliver"
    />

    <ConfirmDialog
      :open="!!cancelling"
      title="Cancelar reparación"
      :message="`${cancelling?.number} quedará cancelada. No se descargará material ni se cobrará nada.`"
      confirm-label="Cancelar reparación"
      variant="danger"
      :loading="acting"
      @cancel="cancelling = null"
      @confirm="doCancel"
    />
  </div>
</template>

<style scoped>
.alert {
  background: var(--danger-100);
  color: var(--danger-600);
  border-radius: var(--radius-sm);
  padding: 9px 12px;
  font-size: 13px;
  margin-bottom: 12px;
}

.toolbar {
  display: flex;
  gap: 10px;
  margin-bottom: 14px;
  flex-wrap: wrap;
}

.search {
  flex: 1;
  min-width: 220px;
  max-width: 360px;
}

.filter {
  width: 170px;
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

.item {
  display: block;
  max-width: 320px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.late {
  color: var(--danger-600);
  font-weight: 600;
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
  grid-template-columns: 2fr 100px 120px 120px 36px;
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

.no-materials {
  font-size: 13px;
  padding: 6px 2px;
}

.items > .btn {
  margin-top: 8px;
}

.hint-box {
  background: var(--ink-50, #f6f7f9);
  border-radius: var(--radius-sm);
  padding: 10px 12px;
  font-size: 13px;
  color: var(--ink-500);
  margin-top: 4px;
}

.totals {
  margin-top: 16px;
  margin-left: auto;
  width: 280px;
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
}
</style>

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
import { payablesApi } from '@/services/api'
import { errorMessage, fieldErrors } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import { PayableStatus, type AccountPayable } from '@/types'

const auth = useAuthStore()
const notifications = useNotificationStore()
const { currency, date, dateTime } = useFormat()

const list = useResourceList<AccountPayable>(payablesApi.list, {
  initialFilters: { status: null, overdue: null },
})

const columns: Column[] = [
  { key: 'documentNumber', label: 'Documento', width: '150px' },
  { key: 'supplier', label: 'Proveedor' },
  { key: 'dueDate', label: 'Vence', width: '130px' },
  { key: 'total', label: 'Total', align: 'right', width: '120px' },
  { key: 'balance', label: 'Saldo', align: 'right', width: '120px' },
  { key: 'status', label: 'Estado', width: '130px' },
  { key: 'actions', label: '', align: 'right', width: '110px' },
]

const statusTone = (payable: AccountPayable) => {
  if (payable.status === PayableStatus.Paid) return 'success'
  if (payable.isOverdue) return 'danger'
  if (payable.status === PayableStatus.PartiallyPaid) return 'warning'
  return 'neutral'
}

// -------------------------------------------------------------- Detalle ----

const detailOpen = ref(false)
const detail = ref<AccountPayable | null>(null)

async function openDetail(row: AccountPayable) {
  detail.value = await payablesApi.get(row.id).catch((error) => {
    notifications.error(errorMessage(error))
    return null
  })
  if (detail.value) detailOpen.value = true
}

// ------------------------------------------------------- Registrar pago ----

const payOpen = ref(false)
const paying = ref(false)
const payErrors = ref<Record<string, string>>({})
const payTarget = ref<AccountPayable | null>(null)
const payForm = reactive({ amount: 0, method: 'Transferencia', reference: '', notes: '' })

const methods = ['Efectivo', 'Transferencia', 'Cheque', 'Tarjeta']

function openPay(row: AccountPayable) {
  payTarget.value = row
  payErrors.value = {}
  Object.assign(payForm, { amount: row.balance, method: 'Transferencia', reference: '', notes: '' })
  payOpen.value = true
}

async function submitPayment() {
  if (!payTarget.value) return
  paying.value = true
  payErrors.value = {}
  try {
    await payablesApi.registerPayment(payTarget.value.id, {
      amount: Number(payForm.amount),
      method: payForm.method,
      reference: payForm.reference || null,
      notes: payForm.notes || null,
    })
    notifications.success('Pago registrado.')
    payOpen.value = false
    if (detail.value?.id === payTarget.value.id) await openDetail(payTarget.value)
    await list.load()
  } catch (error) {
    payErrors.value = fieldErrors(error)
    notifications.error(errorMessage(error))
  } finally {
    paying.value = false
  }
}

const overdueCount = computed(() => list.items.value.filter((p) => p.isOverdue).length)

onMounted(list.load)
</script>

<template>
  <div>
    <PageHeader
      title="Cuentas por pagar"
      description="Lo que la empresa debe a sus proveedores, con historial de pagos."
    />

    <p v-if="overdueCount > 0" class="alert">
      {{ overdueCount }} cuenta(s) vencida(s) en esta página.
    </p>

    <div class="toolbar">
      <input v-model="list.search.value" class="control search" type="search" placeholder="Buscar por documento o proveedor…" />
      <select v-model="list.filters.value.status" class="control filter">
        <option :value="null">Todos los estados</option>
        <option :value="PayableStatus.Pending">Pendiente</option>
        <option :value="PayableStatus.PartiallyPaid">Pago parcial</option>
        <option :value="PayableStatus.Paid">Pagada</option>
      </select>
      <select v-model="list.filters.value.overdue" class="control filter">
        <option :value="null">Todas las fechas</option>
        <option :value="true">Solo vencidas</option>
      </select>
    </div>

    <DataTable
      :columns="columns"
      :rows="list.items.value"
      :loading="list.loading.value"
      :page="list.page.value"
      :page-size="list.pageSize"
      :total="list.total.value"
      empty-title="Sin cuentas por pagar"
      empty-message="Aparecerán aquí al confirmar compras a crédito o de contado."
      @update:page="list.page.value = $event"
    >
      <template #documentNumber="{ row }">
        <button class="link" type="button" @click="openDetail(row)">{{ row.documentNumber }}</button>
      </template>

      <template #supplier="{ row }">{{ row.supplierName }}</template>

      <template #dueDate="{ row }">
        <span class="num" :class="{ overdue: row.isOverdue }">{{ date(row.dueDate) }}</span>
      </template>

      <template #total="{ row }"><span class="num">{{ currency(row.total) }}</span></template>

      <template #balance="{ row }"><span class="num">{{ currency(row.balance) }}</span></template>

      <template #status="{ row }">
        <StatusBadge :tone="statusTone(row)">
          {{ row.isOverdue && row.status !== PayableStatus.Paid ? 'Vencida' : row.statusName }}
        </StatusBadge>
      </template>

      <template #actions="{ row }">
        <BaseButton
          v-if="auth.canWrite('payables') && row.status !== PayableStatus.Paid"
          size="sm"
          variant="primary"
          @click="openPay(row)"
        >
          Registrar pago
        </BaseButton>
        <BaseButton v-else size="sm" @click="openDetail(row)">Ver</BaseButton>
      </template>
    </DataTable>

    <!-- Detalle con historial de pagos -->
    <BaseModal :open="detailOpen" :title="detail?.documentNumber ?? ''" :subtitle="detail?.supplierName" @close="detailOpen = false">
      <template v-if="detail">
        <dl class="summary">
          <div><dt>Emisión</dt><dd>{{ date(detail.issueDate) }}</dd></div>
          <div><dt>Vencimiento</dt><dd :class="{ overdue: detail.isOverdue }">{{ date(detail.dueDate) }}</dd></div>
          <div><dt>Total</dt><dd class="num">{{ currency(detail.total) }}</dd></div>
          <div><dt>Pagado</dt><dd class="num">{{ currency(detail.paidAmount) }}</dd></div>
          <div><dt>Saldo</dt><dd class="num strong">{{ currency(detail.balance) }}</dd></div>
        </dl>

        <h4>Pagos registrados</h4>
        <ul v-if="detail.payments.length" class="payments">
          <li v-for="payment in detail.payments" :key="payment.id">
            <div>
              <strong class="num">{{ currency(payment.amount) }}</strong>
              <small class="muted block">{{ payment.method }}{{ payment.reference ? ` · ${payment.reference}` : '' }}</small>
            </div>
            <small class="muted">{{ dateTime(payment.date) }}</small>
          </li>
        </ul>
        <p v-else class="muted empty">Todavía no se ha registrado ningún pago.</p>
      </template>

      <template #footer>
        <BaseButton variant="ghost" @click="detailOpen = false">Cerrar</BaseButton>
        <BaseButton
          v-if="detail && auth.canWrite('payables') && detail.status !== PayableStatus.Paid"
          variant="primary"
          @click="openPay(detail); detailOpen = false"
        >
          Registrar pago
        </BaseButton>
      </template>
    </BaseModal>

    <!-- Registrar pago -->
    <BaseModal
      :open="payOpen"
      title="Registrar pago"
      :subtitle="payTarget ? `${payTarget.documentNumber} · ${payTarget.supplierName} · saldo ${currency(payTarget.balance)}` : undefined"
      width="480px"
      @close="payOpen = false"
    >
      <form id="pay-form" class="form-grid" @submit.prevent="submitPayment">
        <BaseField v-slot="{ id, invalid }" label="Monto" required span2 :error="payErrors.amount">
          <input
            :id="id"
            v-model.number="payForm.amount"
            class="control"
            :class="{ 'is-invalid': invalid }"
            type="number"
            min="0.01"
            :max="payTarget?.balance"
            step="0.01"
            required
          />
        </BaseField>

        <BaseField v-slot="{ id }" label="Método de pago" required>
          <select :id="id" v-model="payForm.method" class="control" required>
            <option v-for="method in methods" :key="method" :value="method">{{ method }}</option>
          </select>
        </BaseField>

        <BaseField v-slot="{ id }" label="Referencia" hint="Número de comprobante o transferencia.">
          <input :id="id" v-model="payForm.reference" class="control" maxlength="80" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Observaciones" span2>
          <textarea :id="id" v-model="payForm.notes" class="control" maxlength="250" />
        </BaseField>
      </form>

      <template #footer>
        <BaseButton variant="ghost" @click="payOpen = false">Cancelar</BaseButton>
        <BaseButton type="submit" form="pay-form" variant="primary" :loading="paying">
          Registrar pago
        </BaseButton>
      </template>
    </BaseModal>
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
  max-width: 380px;
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

.overdue {
  color: var(--danger-600);
  font-weight: 600;
}

.summary {
  display: grid;
  gap: 8px;
  margin: 0 0 18px;
}

.summary > div {
  display: flex;
  justify-content: space-between;
  border-bottom: 1px solid var(--ink-100);
  padding-bottom: 7px;
}

.summary dt {
  color: var(--ink-500);
  font-size: 13px;
}

.summary dd {
  margin: 0;
  font-weight: 600;
}

.summary .strong {
  font-size: 16px;
}

h4 {
  font-size: 12.5px;
  font-weight: 650;
  color: var(--ink-500);
  margin-bottom: 8px;
}

.payments {
  list-style: none;
  margin: 0;
  padding: 0;
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  overflow: hidden;
}

.payments li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 13px;
  border-bottom: 1px solid var(--ink-100);
}

.payments li:last-child {
  border-bottom: none;
}

.block {
  display: block;
}

.empty {
  text-align: center;
  padding: 20px;
  font-size: 13px;
}
</style>

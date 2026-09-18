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
import { receivablesApi } from '@/services/api'
import { errorMessage, fieldErrors } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import { ReceivableStatus, type AccountReceivable } from '@/types'

const auth = useAuthStore()
const notifications = useNotificationStore()
const { currency, date, dateTime } = useFormat()

const list = useResourceList<AccountReceivable>(receivablesApi.list, {
  initialFilters: { status: null, overdue: null },
})

const columns: Column[] = [
  { key: 'documentNumber', label: 'Documento', width: '150px' },
  { key: 'customer', label: 'Cliente' },
  { key: 'origin', label: 'Origen', width: '120px' },
  { key: 'dueDate', label: 'Vence', width: '130px' },
  { key: 'total', label: 'Total', align: 'right', width: '120px' },
  { key: 'balance', label: 'Saldo', align: 'right', width: '120px' },
  { key: 'status', label: 'Estado', width: '130px' },
  { key: 'actions', label: '', align: 'right', width: '120px' },
]

const statusTone = (row: AccountReceivable) => {
  if (row.status === ReceivableStatus.Collected) return 'success'
  if (row.isOverdue) return 'danger'
  if (row.status === ReceivableStatus.PartiallyCollected) return 'warning'
  return 'neutral'
}

const originOf = (row: AccountReceivable) =>
  row.saleNumber ? `Venta ${row.saleNumber}` : row.repairNumber ? `Reparación ${row.repairNumber}` : '—'

// -------------------------------------------------------------- Detalle ----

const detailOpen = ref(false)
const detail = ref<AccountReceivable | null>(null)

async function openDetail(row: AccountReceivable) {
  detail.value = await receivablesApi.get(row.id).catch((error) => {
    notifications.error(errorMessage(error))
    return null
  })
  if (detail.value) detailOpen.value = true
}

// ------------------------------------------------------ Registrar cobro ----

const collectOpen = ref(false)
const collecting = ref(false)
const collectErrors = ref<Record<string, string>>({})
const target = ref<AccountReceivable | null>(null)
const collectForm = reactive({ amount: 0, method: 'Transferencia', reference: '', notes: '' })

const methods = ['Efectivo', 'Transferencia', 'Sinpe Móvil', 'Tarjeta', 'Cheque']

function openCollect(row: AccountReceivable) {
  target.value = row
  collectErrors.value = {}
  Object.assign(collectForm, { amount: row.balance, method: 'Transferencia', reference: '', notes: '' })
  collectOpen.value = true
}

async function submitReceipt() {
  if (!target.value) return
  collecting.value = true
  collectErrors.value = {}
  try {
    await receivablesApi.registerReceipt(target.value.id, {
      amount: Number(collectForm.amount),
      method: collectForm.method,
      reference: collectForm.reference || null,
      notes: collectForm.notes || null,
    })
    notifications.success('Cobro registrado. Quedó anotado también en ingresos.')
    collectOpen.value = false
    if (detail.value?.id === target.value.id) await openDetail(target.value)
    await list.load()
  } catch (error) {
    collectErrors.value = fieldErrors(error)
    notifications.error(errorMessage(error))
  } finally {
    collecting.value = false
  }
}

const overdueCount = computed(() => list.items.value.filter((r) => r.isOverdue).length)
const pendingBalance = computed(() => list.items.value.reduce((sum, r) => sum + r.balance, 0))

onMounted(list.load)
</script>

<template>
  <div>
    <PageHeader
      title="Cuentas por cobrar"
      description="Lo que los clientes deben a la empresa, con su historial de cobros."
    />

    <div class="summary-bar">
      <div>
        <span class="muted">Saldo en esta página</span>
        <strong class="num">{{ currency(pendingBalance) }}</strong>
      </div>
      <p v-if="overdueCount > 0" class="alert">{{ overdueCount }} cuenta(s) vencida(s).</p>
    </div>

    <div class="toolbar">
      <input
        v-model="list.search.value"
        class="control search"
        type="search"
        placeholder="Buscar por documento o cliente…"
      />
      <select v-model="list.filters.value.status" class="control filter">
        <option :value="null">Todos los estados</option>
        <option :value="ReceivableStatus.Pending">Pendiente</option>
        <option :value="ReceivableStatus.PartiallyCollected">Cobro parcial</option>
        <option :value="ReceivableStatus.Collected">Cobrada</option>
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
      empty-title="Sin cuentas por cobrar"
      empty-message="Aparecerán aquí al confirmar ventas o entregar reparaciones."
      @update:page="list.page.value = $event"
    >
      <template #documentNumber="{ row }">
        <button class="link" type="button" @click="openDetail(row)">{{ row.documentNumber }}</button>
      </template>

      <template #customer="{ row }">{{ row.customerName }}</template>

      <template #origin="{ row }"><span class="muted">{{ originOf(row) }}</span></template>

      <template #dueDate="{ row }">
        <span class="num" :class="{ overdue: row.isOverdue }">{{ date(row.dueDate) }}</span>
      </template>

      <template #total="{ row }"><span class="num">{{ currency(row.total) }}</span></template>

      <template #balance="{ row }"><span class="num">{{ currency(row.balance) }}</span></template>

      <template #status="{ row }">
        <StatusBadge :tone="statusTone(row)">
          {{ row.isOverdue && row.status !== ReceivableStatus.Collected ? 'Vencida' : row.statusName }}
        </StatusBadge>
      </template>

      <template #actions="{ row }">
        <BaseButton
          v-if="auth.canWrite('receivables') && row.status !== ReceivableStatus.Collected"
          size="sm"
          variant="primary"
          @click="openCollect(row)"
        >
          Registrar cobro
        </BaseButton>
        <BaseButton v-else size="sm" @click="openDetail(row)">Ver</BaseButton>
      </template>
    </DataTable>

    <!-- Detalle con historial de cobros -->
    <BaseModal
      :open="detailOpen"
      :title="detail?.documentNumber ?? ''"
      :subtitle="detail?.customerName"
      @close="detailOpen = false"
    >
      <template v-if="detail">
        <dl class="summary">
          <div><dt>Origen</dt><dd>{{ originOf(detail) }}</dd></div>
          <div><dt>Emisión</dt><dd>{{ date(detail.issueDate) }}</dd></div>
          <div>
            <dt>Vencimiento</dt>
            <dd :class="{ overdue: detail.isOverdue }">{{ date(detail.dueDate) }}</dd>
          </div>
          <div><dt>Total</dt><dd class="num">{{ currency(detail.total) }}</dd></div>
          <div><dt>Cobrado</dt><dd class="num">{{ currency(detail.collectedAmount) }}</dd></div>
          <div><dt>Saldo</dt><dd class="num strong">{{ currency(detail.balance) }}</dd></div>
        </dl>

        <h4>Cobros registrados</h4>
        <ul v-if="detail.receipts.length" class="receipts">
          <li v-for="receipt in detail.receipts" :key="receipt.id">
            <div>
              <strong class="num">{{ currency(receipt.amount) }}</strong>
              <small class="muted block">
                {{ receipt.method }}{{ receipt.reference ? ` · ${receipt.reference}` : '' }}
              </small>
            </div>
            <small class="muted">{{ dateTime(receipt.date) }}</small>
          </li>
        </ul>
        <p v-else class="muted empty">Todavía no se ha registrado ningún cobro.</p>
      </template>

      <template #footer>
        <BaseButton variant="ghost" @click="detailOpen = false">Cerrar</BaseButton>
        <BaseButton
          v-if="detail && auth.canWrite('receivables') && detail.status !== ReceivableStatus.Collected"
          variant="primary"
          @click="openCollect(detail); detailOpen = false"
        >
          Registrar cobro
        </BaseButton>
      </template>
    </BaseModal>

    <!-- Registrar cobro -->
    <BaseModal
      :open="collectOpen"
      title="Registrar cobro"
      :subtitle="target ? `${target.documentNumber} · ${target.customerName} · saldo ${currency(target.balance)}` : undefined"
      width="480px"
      @close="collectOpen = false"
    >
      <form id="collect-form" class="form-grid" @submit.prevent="submitReceipt">
        <BaseField v-slot="{ id, invalid }" label="Monto" required span2 :error="collectErrors.amount">
          <input
            :id="id"
            v-model.number="collectForm.amount"
            class="control"
            :class="{ 'is-invalid': invalid }"
            type="number"
            min="0.01"
            :max="target?.balance"
            step="0.01"
            required
          />
        </BaseField>

        <BaseField v-slot="{ id }" label="Medio de cobro" required>
          <select :id="id" v-model="collectForm.method" class="control" required>
            <option v-for="method in methods" :key="method" :value="method">{{ method }}</option>
          </select>
        </BaseField>

        <BaseField v-slot="{ id }" label="Referencia" hint="Número de comprobante o transferencia.">
          <input :id="id" v-model="collectForm.reference" class="control" maxlength="80" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Observaciones" span2>
          <textarea :id="id" v-model="collectForm.notes" class="control" maxlength="250" />
        </BaseField>
      </form>

      <template #footer>
        <BaseButton variant="ghost" @click="collectOpen = false">Cancelar</BaseButton>
        <BaseButton type="submit" form="collect-form" variant="primary" :loading="collecting">
          Registrar cobro
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<style scoped>
.summary-bar {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}

.summary-bar > div {
  display: flex;
  flex-direction: column;
}

.summary-bar .num {
  font-size: 18px;
}

.summary-bar .muted {
  font-size: 12px;
}

.alert {
  background: var(--danger-100);
  color: var(--danger-600);
  border-radius: var(--radius-sm);
  padding: 7px 12px;
  font-size: 13px;
  margin: 0;
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

.receipts {
  list-style: none;
  margin: 0;
  padding: 0;
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  overflow: hidden;
}

.receipts li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 10px 13px;
  border-bottom: 1px solid var(--ink-100);
}

.receipts li:last-child {
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

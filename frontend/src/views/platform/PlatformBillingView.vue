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
import { platformApi } from '@/services/api'
import { errorMessage, fieldErrors } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import { SubscriptionStatus, type Plan, type Subscription } from '@/types'

const auth = useAuthStore()
const notifications = useNotificationStore()
const { currency, date, dateTime } = useFormat()

const list = useResourceList<Subscription>(platformApi.subscriptions, {
  initialFilters: { status: null, expiringInDays: null },
})

const plans = ref<Plan[]>([])

const columns: Column[] = [
  { key: 'company', label: 'Empresa' },
  { key: 'plan', label: 'Plan', width: '150px' },
  { key: 'endDate', label: 'Vence', width: '150px' },
  { key: 'price', label: 'Precio', align: 'right', width: '120px' },
  { key: 'status', label: 'Estado', width: '120px' },
  { key: 'actions', label: '', align: 'right', width: '190px' },
]

const statusTone = (s: Subscription) => {
  if (s.status === SubscriptionStatus.Cancelled) return 'neutral'
  if (s.isExpired) return 'danger'
  if (s.status === SubscriptionStatus.Trial) return 'info'
  return s.daysToExpiry <= 7 ? 'warning' : 'success'
}

// ------------------------------------------------------------- Detalle ----

const detailOpen = ref(false)
const detail = ref<Subscription | null>(null)

async function openDetail(row: Subscription) {
  detail.value = await platformApi.subscription(row.id).catch((error) => {
    notifications.error(errorMessage(error))
    return null
  })
  if (detail.value) detailOpen.value = true
}

// ------------------------------------------------------- Registrar cobro ----

const chargeOpen = ref(false)
const charging = ref(false)
const chargeErrors = ref<Record<string, string>>({})
const chargeTarget = ref<Subscription | null>(null)
const chargeForm = reactive({ amount: 0, method: 'Transferencia', reference: '', periods: 1, notes: '' })

const methods = ['Transferencia', 'Efectivo', 'SINPE Móvil', 'Tarjeta', 'Depósito']

/** El monto sugerido es el precio pactado por la cantidad de períodos cobrados. */
const suggestedAmount = computed(() =>
  chargeTarget.value ? chargeTarget.value.price * chargeForm.periods : 0,
)

function openCharge(row: Subscription) {
  chargeTarget.value = row
  chargeErrors.value = {}
  Object.assign(chargeForm, { amount: row.price, method: 'Transferencia', reference: '', periods: 1, notes: '' })
  chargeOpen.value = true
}

function onPeriodsChange() {
  chargeForm.amount = suggestedAmount.value
}

async function submitCharge() {
  if (!chargeTarget.value) return
  charging.value = true
  chargeErrors.value = {}
  try {
    await platformApi.registerCharge(chargeTarget.value.id, {
      amount: Number(chargeForm.amount),
      method: chargeForm.method,
      reference: chargeForm.reference || null,
      periods: Number(chargeForm.periods),
      notes: chargeForm.notes || null,
    })
    notifications.success('Cobro registrado y vencimiento actualizado.')
    chargeOpen.value = false
    if (detail.value?.id === chargeTarget.value.id) await openDetail(chargeTarget.value)
    await list.load()
  } catch (error) {
    chargeErrors.value = fieldErrors(error)
    notifications.error(errorMessage(error))
  } finally {
    charging.value = false
  }
}

// -------------------------------------------------------- Cambiar plan ----

const planOpen = ref(false)
const changingPlan = ref(false)
const planTarget = ref<Subscription | null>(null)
const planForm = reactive({ planId: 0, price: null as number | null, reason: '' })

function openChangePlan(row: Subscription) {
  planTarget.value = row
  Object.assign(planForm, { planId: row.planId, price: row.price, reason: '' })
  planOpen.value = true
}

async function submitChangePlan() {
  if (!planTarget.value) return
  changingPlan.value = true
  try {
    await platformApi.changePlan(planTarget.value.id, {
      planId: Number(planForm.planId),
      price: planForm.price === null ? null : Number(planForm.price),
      reason: planForm.reason || null,
    })
    notifications.success('Plan actualizado.')
    planOpen.value = false
    await list.load()
  } catch (error) {
    notifications.error(errorMessage(error))
  } finally {
    changingPlan.value = false
  }
}

const expiredCount = computed(() => list.items.value.filter((s) => s.isExpired).length)

onMounted(async () => {
  await Promise.all([
    list.load(),
    platformApi.plans(true).then((data) => (plans.value = data)),
  ]).catch((error) => notifications.error(errorMessage(error)))
})
</script>

<template>
  <div>
    <PageHeader
      title="Suscripciones"
      description="A quién hay que cobrarle, cuándo vence cada cuenta y el historial de cobros."
    />

    <p v-if="expiredCount > 0" class="alert">
      {{ expiredCount }} suscripción(es) vencida(s) en esta página.
    </p>

    <div class="toolbar">
      <input v-model="list.search.value" class="control search" type="search" placeholder="Buscar por empresa, correo o plan…" />
      <select v-model="list.filters.value.status" class="control filter">
        <option :value="null">Todos los estados</option>
        <option :value="SubscriptionStatus.Active">Activas</option>
        <option :value="SubscriptionStatus.Trial">En prueba</option>
        <option :value="SubscriptionStatus.Expired">Vencidas</option>
        <option :value="SubscriptionStatus.Cancelled">Dadas de baja</option>
      </select>
      <select v-model="list.filters.value.expiringInDays" class="control filter">
        <option :value="null">Cualquier vencimiento</option>
        <option :value="0">Ya vencidas</option>
        <option :value="7">Vencen en 7 días</option>
        <option :value="30">Vencen en 30 días</option>
      </select>
    </div>

    <DataTable
      :columns="columns"
      :rows="list.items.value"
      :loading="list.loading.value"
      :page="list.page.value"
      :page-size="list.pageSize"
      :total="list.total.value"
      empty-title="Sin suscripciones"
      empty-message="Se crean automáticamente al registrar una empresa."
      @update:page="list.page.value = $event"
    >
      <template #company="{ row }">
        <button class="link" type="button" @click="openDetail(row)">{{ row.companyName }}</button>
        <small class="muted block">{{ row.accountEmail }}</small>
      </template>

      <template #plan="{ row }">
        <StatusBadge tone="brand">{{ row.planName }}</StatusBadge>
      </template>

      <template #endDate="{ row }">
        <span class="num" :class="{ overdue: row.isExpired }">{{ date(row.endDate) }}</span>
        <small class="muted block">
          {{ row.isExpired ? `Vencida hace ${Math.abs(row.daysToExpiry)} d` : `${row.daysToExpiry} d` }}
        </small>
      </template>

      <template #price="{ row }"><span class="num">{{ currency(row.price) }}</span></template>

      <template #status="{ row }">
        <StatusBadge :tone="statusTone(row)">{{ row.statusName }}</StatusBadge>
      </template>

      <template #actions="{ row }">
        <div class="row-actions">
          <BaseButton
            v-if="auth.canWrite('platform_billing') && row.status !== SubscriptionStatus.Cancelled"
            size="sm"
            variant="primary"
            @click="openCharge(row)"
          >
            Registrar cobro
          </BaseButton>
          <BaseButton
            v-if="auth.canWrite('platform_billing') && row.status !== SubscriptionStatus.Cancelled"
            size="sm"
            variant="ghost"
            @click="openChangePlan(row)"
          >
            Cambiar plan
          </BaseButton>
        </div>
      </template>
    </DataTable>

    <!-- Detalle con historial de cobros -->
    <BaseModal :open="detailOpen" :title="detail?.companyName ?? ''" :subtitle="detail?.planName" @close="detailOpen = false">
      <template v-if="detail">
        <dl class="summary">
          <div><dt>Inicio</dt><dd>{{ date(detail.startDate) }}</dd></div>
          <div><dt>Vence</dt><dd :class="{ overdue: detail.isExpired }">{{ date(detail.endDate) }}</dd></div>
          <div><dt>Precio</dt><dd class="num">{{ currency(detail.price) }}</dd></div>
          <div><dt>Estado</dt><dd>{{ detail.statusName }}</dd></div>
        </dl>

        <h4>Cobros registrados</h4>
        <ul v-if="detail.payments.length" class="payments">
          <li v-for="payment in detail.payments" :key="payment.id">
            <div>
              <strong class="num">{{ currency(payment.amount) }}</strong>
              <small class="muted block">
                {{ payment.method }}{{ payment.reference ? ` · ${payment.reference}` : '' }} ·
                cubre {{ date(payment.periodFrom) }} – {{ date(payment.periodTo) }}
              </small>
            </div>
            <small class="muted">{{ dateTime(payment.date) }}</small>
          </li>
        </ul>
        <p v-else class="muted empty">Todavía no se ha registrado ningún cobro.</p>
      </template>

      <template #footer>
        <BaseButton variant="ghost" @click="detailOpen = false">Cerrar</BaseButton>
      </template>
    </BaseModal>

    <!-- Registrar cobro -->
    <BaseModal
      :open="chargeOpen"
      title="Registrar cobro"
      :subtitle="chargeTarget ? `${chargeTarget.companyName} · vence ${date(chargeTarget.endDate)}` : undefined"
      width="500px"
      @close="chargeOpen = false"
    >
      <form id="charge-form" class="form-grid" @submit.prevent="submitCharge">
        <BaseField
          v-slot="{ id }"
          label="Períodos que cubre"
          hint="El vencimiento se corre desde la fecha actual de vencimiento."
        >
          <input :id="id" v-model.number="chargeForm.periods" class="control" type="number" min="1" max="60" @change="onPeriodsChange" />
        </BaseField>

        <BaseField v-slot="{ id, invalid }" label="Monto" required :error="chargeErrors.amount">
          <input :id="id" v-model.number="chargeForm.amount" class="control" :class="{ 'is-invalid': invalid }" type="number" min="0.01" step="0.01" required />
        </BaseField>

        <BaseField v-slot="{ id }" label="Método" required>
          <select :id="id" v-model="chargeForm.method" class="control" required>
            <option v-for="method in methods" :key="method" :value="method">{{ method }}</option>
          </select>
        </BaseField>

        <BaseField v-slot="{ id }" label="Referencia">
          <input :id="id" v-model="chargeForm.reference" class="control" maxlength="80" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Observaciones" span2>
          <textarea :id="id" v-model="chargeForm.notes" class="control" maxlength="250" />
        </BaseField>
      </form>

      <template #footer>
        <BaseButton variant="ghost" @click="chargeOpen = false">Cancelar</BaseButton>
        <BaseButton type="submit" form="charge-form" variant="primary" :loading="charging">
          Registrar cobro
        </BaseButton>
      </template>
    </BaseModal>

    <!-- Cambiar plan -->
    <BaseModal
      :open="planOpen"
      title="Cambiar de plan"
      :subtitle="planTarget?.companyName"
      width="480px"
      @close="planOpen = false"
    >
      <form id="plan-change-form" class="form-grid" @submit.prevent="submitChangePlan">
        <BaseField v-slot="{ id }" label="Plan" required span2>
          <select :id="id" v-model.number="planForm.planId" class="control" required>
            <option v-for="plan in plans" :key="plan.id" :value="plan.id">
              {{ plan.name }} — {{ currency(plan.price) }} / {{ plan.billingPeriodMonths }} mes(es)
            </option>
          </select>
        </BaseField>

        <BaseField v-slot="{ id }" label="Precio pactado" span2 hint="Vacío = precio de lista del plan.">
          <input :id="id" v-model="planForm.price" class="control" type="number" min="0" step="0.01" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Motivo del cambio" span2>
          <input :id="id" v-model="planForm.reason" class="control" maxlength="250" />
        </BaseField>
      </form>

      <template #footer>
        <BaseButton variant="ghost" @click="planOpen = false">Cancelar</BaseButton>
        <BaseButton type="submit" form="plan-change-form" variant="primary" :loading="changingPlan">
          Cambiar plan
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
  max-width: 340px;
}

.filter {
  width: 185px;
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

.block {
  display: block;
  font-size: 12.5px;
}

.overdue {
  color: var(--danger-600);
  font-weight: 600;
}

.row-actions {
  display: flex;
  gap: 6px;
  justify-content: flex-end;
  flex-wrap: wrap;
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
  gap: 12px;
  padding: 10px 13px;
  border-bottom: 1px solid var(--ink-100);
}

.payments li:last-child {
  border-bottom: none;
}

.empty {
  text-align: center;
  padding: 20px;
  font-size: 13px;
}
</style>

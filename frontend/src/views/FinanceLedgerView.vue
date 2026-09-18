<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
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
import { expensesApi, incomeApi } from '@/services/api'
import { errorMessage, fieldErrors } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import type { FinanceCategory, FinanceEntry, FinanceSummary } from '@/types'

/**
 * Libro de ingresos o de gastos. Es la misma pantalla para los dos: cambia el módulo,
 * el color y los textos, no la mecánica. Los movimientos que vienen de un cobro o de
 * un pago se muestran, pero no se editan desde acá.
 */
const props = defineProps<{ kind: 'income' | 'expense' }>()

const auth = useAuthStore()
const notifications = useNotificationStore()
const { currency, date } = useFormat()

const isIncome = computed(() => props.kind === 'income')
const module = computed(() => (isIncome.value ? 'income' : 'expenses'))
const api = computed(() => (isIncome.value ? incomeApi : expensesApi))

const texts = computed(() =>
  isIncome.value
    ? {
        title: 'Ingresos',
        description: 'Todo el dinero que entra. Los cobros a clientes se anotan solos.',
        action: 'Registrar ingreso',
        one: 'ingreso',
        empty: 'Aquí aparecerán los cobros a clientes y los ingresos que registre a mano.',
      }
    : {
        title: 'Gastos',
        description: 'Todo el dinero que sale. Los pagos a proveedores se anotan solos.',
        action: 'Registrar gasto',
        one: 'gasto',
        empty: 'Aquí aparecerán los pagos a proveedores y los gastos que registre a mano.',
      },
)

const period = reactive({ from: '', to: '' })

const list = useResourceList<FinanceEntry>((query) => api.value.list(query), {
  initialFilters: { categoryId: null, manual: null, from: null, to: null },
})

const categories = ref<FinanceCategory[]>([])
const summary = ref<FinanceSummary | null>(null)

const columns: Column[] = [
  { key: 'date', label: 'Fecha', width: '120px' },
  { key: 'description', label: 'Detalle' },
  { key: 'category', label: 'Categoría', width: '190px' },
  { key: 'method', label: 'Medio', width: '130px' },
  { key: 'amount', label: 'Monto', align: 'right', width: '130px' },
  { key: 'actions', label: '', align: 'right', width: '130px' },
]

async function loadSummary() {
  summary.value = await api.value
    .summary({ ...list.filters.value, search: list.search.value })
    .catch(() => null)
}

/** El período es un filtro más: al cambiarlo se recargan lista y totales. */
function applyPeriod() {
  list.filters.value = {
    ...list.filters.value,
    from: period.from || null,
    to: period.to || null,
  }
}

watch(() => [list.items.value, list.filters.value], loadSummary, { deep: true })

// ------------------------------------------------------------- Formulario ----

const modalOpen = ref(false)
const editing = ref<FinanceEntry | null>(null)
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive({
  categoryId: 0,
  date: new Date().toISOString().slice(0, 10),
  amount: 0,
  description: '',
  method: 'Efectivo',
  reference: '',
  notes: '',
})

const methods = ['Efectivo', 'Transferencia', 'Sinpe Móvil', 'Tarjeta', 'Cheque']
const assignable = computed(() => categories.value.filter((c) => c.isActive))

function openCreate() {
  editing.value = null
  errors.value = {}
  Object.assign(form, {
    categoryId: assignable.value.find((c) => !c.isSystem)?.id ?? assignable.value[0]?.id ?? 0,
    date: new Date().toISOString().slice(0, 10),
    amount: 0,
    description: '',
    method: 'Efectivo',
    reference: '',
    notes: '',
  })
  modalOpen.value = true
}

function openEdit(entry: FinanceEntry) {
  editing.value = entry
  errors.value = {}
  Object.assign(form, {
    categoryId: entry.categoryId,
    date: entry.date.slice(0, 10),
    amount: entry.amount,
    description: entry.description,
    method: entry.method,
    reference: entry.reference ?? '',
    notes: entry.notes ?? '',
  })
  modalOpen.value = true
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    const payload = {
      categoryId: Number(form.categoryId),
      date: form.date,
      amount: Number(form.amount),
      description: form.description,
      method: form.method,
      reference: form.reference || null,
      notes: form.notes || null,
    }

    if (editing.value) {
      await api.value.update(editing.value.id, payload)
      notifications.success('Movimiento actualizado.')
    } else {
      await api.value.create(payload)
      notifications.success(`Se registró el ${texts.value.one}.`)
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

const deleting = ref<FinanceEntry | null>(null)
const removing = ref(false)

async function doDelete() {
  if (!deleting.value) return
  removing.value = true
  try {
    await api.value.remove(deleting.value.id)
    notifications.success('Movimiento eliminado.')
    deleting.value = null
    await list.load()
  } catch (error) {
    notifications.error(errorMessage(error))
  } finally {
    removing.value = false
  }
}

async function loadCategories() {
  categories.value = await api.value.categories().catch(() => [])
}

// Al navegar entre ingresos y gastos se reutiliza el componente: hay que recargar todo.
watch(
  () => props.kind,
  async () => {
    list.filters.value = { categoryId: null, manual: null, from: null, to: null }
    period.from = ''
    period.to = ''
    await Promise.all([list.load(), loadCategories()])
  },
)

onMounted(async () => {
  await Promise.all([list.load(), loadCategories()]).catch((error) =>
    notifications.error(errorMessage(error)),
  )
})
</script>

<template>
  <div>
    <PageHeader :title="texts.title" :description="texts.description">
      <template #actions>
        <BaseButton v-if="auth.canWrite(module)" variant="primary" @click="openCreate">
          {{ texts.action }}
        </BaseButton>
      </template>
    </PageHeader>

    <div v-if="summary" class="metrics">
      <article :class="['metric', isIncome ? 'in' : 'out']">
        <span class="label">Total del filtro</span>
        <strong class="num">{{ currency(summary.total) }}</strong>
        <small class="muted">{{ summary.count }} movimiento(s)</small>
      </article>
      <article class="metric">
        <span class="label">Este mes</span>
        <strong class="num">{{ currency(summary.monthTotal) }}</strong>
      </article>
      <article v-if="summary.byCategory.length" class="metric wide">
        <span class="label">Por categoría</span>
        <ul class="breakdown">
          <li v-for="row in summary.byCategory.slice(0, 4)" :key="row.categoryId">
            <span>{{ row.categoryName }}</span>
            <span class="num">{{ currency(row.total) }}</span>
          </li>
        </ul>
      </article>
    </div>

    <div class="toolbar">
      <input
        v-model="list.search.value"
        class="control search"
        type="search"
        placeholder="Buscar por detalle, categoría o referencia…"
      />
      <select v-model="list.filters.value.categoryId" class="control filter">
        <option :value="null">Todas las categorías</option>
        <option v-for="category in categories" :key="category.id" :value="category.id">
          {{ category.name }}
        </option>
      </select>
      <select v-model="list.filters.value.manual" class="control filter">
        <option :value="null">Todo origen</option>
        <option :value="true">Solo manuales</option>
        <option :value="false">Solo automáticos</option>
      </select>
      <div class="period">
        <input v-model="period.from" class="control" type="date" aria-label="Desde" @change="applyPeriod" />
        <span class="muted">a</span>
        <input v-model="period.to" class="control" type="date" aria-label="Hasta" @change="applyPeriod" />
      </div>
    </div>

    <DataTable
      :columns="columns"
      :rows="list.items.value"
      :loading="list.loading.value"
      :page="list.page.value"
      :page-size="list.pageSize"
      :total="list.total.value"
      :empty-title="`Sin ${texts.title.toLowerCase()}`"
      :empty-message="texts.empty"
      @update:page="list.page.value = $event"
    >
      <template #date="{ row }"><span class="num">{{ date(row.date) }}</span></template>

      <template #description="{ row }">
        <div class="detail">
          <span>{{ row.description }}</span>
          <StatusBadge v-if="!row.isManual" tone="info">{{ row.sourceName }}</StatusBadge>
        </div>
        <small v-if="row.reference" class="muted">Ref. {{ row.reference }}</small>
      </template>

      <template #category="{ row }">{{ row.categoryName }}</template>

      <template #method="{ row }"><span class="muted">{{ row.method }}</span></template>

      <template #amount="{ row }">
        <span class="num amount" :class="isIncome ? 'in' : 'out'">
          {{ isIncome ? '+' : '−' }} {{ currency(row.amount) }}
        </span>
      </template>

      <template #actions="{ row }">
        <div v-if="auth.canWrite(module) && row.isManual" class="row-actions">
          <BaseButton size="sm" @click="openEdit(row)">Editar</BaseButton>
          <BaseButton size="sm" variant="ghost" @click="deleting = row">Eliminar</BaseButton>
        </div>
        <span v-else class="muted locked">Automático</span>
      </template>

      <template #empty-action>
        <BaseButton v-if="auth.canWrite(module)" variant="primary" @click="openCreate">
          {{ texts.action }}
        </BaseButton>
      </template>
    </DataTable>

    <BaseModal
      :open="modalOpen"
      :title="editing ? 'Editar movimiento' : texts.action"
      width="560px"
      @close="modalOpen = false"
    >
      <form id="finance-form" class="form-grid" @submit.prevent="save">
        <BaseField v-slot="{ id, invalid }" label="Detalle" required span2 :error="errors.description">
          <input
            :id="id"
            v-model="form.description"
            class="control"
            :class="{ 'is-invalid': invalid }"
            maxlength="200"
            required
          />
        </BaseField>

        <BaseField v-slot="{ id, invalid }" label="Categoría" required :error="errors.categoryId">
          <select
            :id="id"
            v-model.number="form.categoryId"
            class="control"
            :class="{ 'is-invalid': invalid }"
            required
          >
            <option :value="0" disabled>Seleccione…</option>
            <option v-for="category in assignable" :key="category.id" :value="category.id">
              {{ category.name }}
            </option>
          </select>
        </BaseField>

        <BaseField v-slot="{ id, invalid }" label="Monto" required :error="errors.amount">
          <input
            :id="id"
            v-model.number="form.amount"
            class="control"
            :class="{ 'is-invalid': invalid }"
            type="number"
            min="0.01"
            step="0.01"
            required
          />
        </BaseField>

        <BaseField v-slot="{ id }" label="Fecha" required>
          <input :id="id" v-model="form.date" class="control" type="date" required />
        </BaseField>

        <BaseField v-slot="{ id }" label="Medio de pago" required>
          <select :id="id" v-model="form.method" class="control" required>
            <option v-for="method in methods" :key="method" :value="method">{{ method }}</option>
          </select>
        </BaseField>

        <BaseField v-slot="{ id }" label="Referencia" span2 hint="Factura, comprobante o número de transferencia.">
          <input :id="id" v-model="form.reference" class="control" maxlength="80" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Observaciones" span2>
          <textarea :id="id" v-model="form.notes" class="control" maxlength="500" />
        </BaseField>
      </form>

      <template #footer>
        <BaseButton variant="ghost" @click="modalOpen = false">Cancelar</BaseButton>
        <BaseButton type="submit" form="finance-form" variant="primary" :loading="saving">
          {{ editing ? 'Guardar cambios' : 'Registrar' }}
        </BaseButton>
      </template>
    </BaseModal>

    <ConfirmDialog
      :open="!!deleting"
      title="Eliminar movimiento"
      :message="`Se eliminará «${deleting?.description}» por ${currency(deleting?.amount ?? 0)}. La eliminación queda registrada en la bitácora.`"
      confirm-label="Eliminar"
      variant="danger"
      :loading="removing"
      @cancel="deleting = null"
      @confirm="doDelete"
    />
  </div>
</template>

<style scoped>
.metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
  gap: 12px;
  margin-bottom: 16px;
}

.metric {
  background: var(--surface);
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  padding: 13px 15px;
  display: flex;
  flex-direction: column;
  gap: 3px;
}

.metric.wide {
  grid-column: span 2;
}

.metric .label {
  font-size: 11.5px;
  font-weight: 650;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--ink-500);
}

.metric .num {
  font-size: 20px;
}

.metric.in .num {
  color: var(--success-600, #15803d);
}

.metric.out .num {
  color: var(--danger-600);
}

.breakdown {
  list-style: none;
  margin: 4px 0 0;
  padding: 0;
  display: grid;
  gap: 3px;
}

.breakdown li {
  display: flex;
  justify-content: space-between;
  font-size: 12.5px;
}

.toolbar {
  display: flex;
  gap: 10px;
  margin-bottom: 14px;
  flex-wrap: wrap;
  align-items: center;
}

.search {
  flex: 1;
  min-width: 200px;
  max-width: 320px;
}

.filter {
  width: 180px;
}

.period {
  display: flex;
  align-items: center;
  gap: 6px;
  font-size: 13px;
}

.period .control {
  width: 145px;
}

.detail {
  display: flex;
  align-items: center;
  gap: 8px;
}

.amount.in {
  color: var(--success-600, #15803d);
  font-weight: 650;
}

.amount.out {
  color: var(--danger-600);
  font-weight: 650;
}

.row-actions {
  display: flex;
  gap: 6px;
  justify-content: flex-end;
}

.locked {
  font-size: 12px;
}
</style>

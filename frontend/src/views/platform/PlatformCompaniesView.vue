<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
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
import { platformApi } from '@/services/api'
import { errorMessage, fieldErrors } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import { CompanyStatus, type Plan, type PlatformCompany, type RegisterCompanyResult } from '@/types'

const auth = useAuthStore()
const notifications = useNotificationStore()
const router = useRouter()
const { date, currency } = useFormat()

const list = useResourceList<PlatformCompany>(platformApi.companies, {
  initialFilters: { status: null, expired: null },
})

const plans = ref<Plan[]>([])

const columns: Column[] = [
  { key: 'name', label: 'Empresa' },
  { key: 'subscription', label: 'Plan', width: '160px' },
  { key: 'expiry', label: 'Vence', width: '150px' },
  { key: 'users', label: 'Usuarios', align: 'right', width: '90px' },
  { key: 'status', label: 'Estado', width: '120px' },
  { key: 'actions', label: '', align: 'right', width: '210px' },
]

const statusTone = (status: number) =>
  status === CompanyStatus.Active ? 'success' : status === CompanyStatus.Suspended ? 'warning' : 'neutral'

// ------------------------------------------------------------- Alta y edición ----

const emptyForm = () => ({
  name: '',
  accountEmail: '',
  taxId: '',
  phone: '',
  address: '',
  currency: 'CRC',
  defaultTaxRate: 13,
  notes: '',
  planId: 0,
  price: null as number | null,
  trialPeriod: false,
  adminFirstName: '',
  adminLastName: '',
  adminPassword: '',
})

const modalOpen = ref(false)
const editing = ref<PlatformCompany | null>(null)
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive(emptyForm())

/** Las credenciales generadas se muestran una única vez, justo después del alta. */
const credentials = ref<RegisterCompanyResult | null>(null)

function openCreate() {
  editing.value = null
  errors.value = {}
  Object.assign(form, emptyForm(), { planId: plans.value[0]?.id ?? 0 })
  modalOpen.value = true
}

function openEdit(company: PlatformCompany) {
  editing.value = company
  errors.value = {}
  Object.assign(form, emptyForm(), {
    name: company.name,
    accountEmail: company.accountEmail,
    taxId: company.taxId ?? '',
    phone: company.phone ?? '',
    address: company.address ?? '',
    currency: company.currency,
    notes: company.notes ?? '',
  })
  modalOpen.value = true
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    if (editing.value) {
      await platformApi.updateCompany(editing.value.id, {
        name: form.name,
        accountEmail: form.accountEmail,
        taxId: form.taxId || null,
        phone: form.phone || null,
        address: form.address || null,
        currency: form.currency,
        defaultTaxRate: Number(form.defaultTaxRate),
        notes: form.notes || null,
      })
      notifications.success('Empresa actualizada.')
      modalOpen.value = false
    } else {
      const result = await platformApi.registerCompany({
        ...form,
        planId: Number(form.planId),
        price: form.price === null || form.price === ('' as never) ? null : Number(form.price),
        adminPassword: form.adminPassword || null,
        defaultTaxRate: Number(form.defaultTaxRate),
      })
      modalOpen.value = false
      // Si la contraseña se generó, hay que entregársela al cliente ahora.
      if (result.temporaryPassword) credentials.value = result
      else notifications.success('Empresa registrada.')
    }
    await list.load()
  } catch (error) {
    errors.value = fieldErrors(error)
    notifications.error(errorMessage(error))
  } finally {
    saving.value = false
  }
}

async function copyCredentials() {
  if (!credentials.value) return
  const text = `Gestora — acceso de ${credentials.value.company.name}\nCorreo: ${credentials.value.adminEmail}\nContraseña temporal: ${credentials.value.temporaryPassword}`
  try {
    await navigator.clipboard.writeText(text)
    notifications.success('Credenciales copiadas.')
  } catch {
    notifications.error('No se pudieron copiar. Cópielas manualmente.')
  }
}

// --------------------------------------------------------- Estado de la cuenta ----

const confirming = ref<{ company: PlatformCompany; action: 'suspend' | 'reactivate' } | null>(null)
const acting = ref(false)

async function confirmStatus() {
  if (!confirming.value) return
  acting.value = true
  try {
    const { company, action } = confirming.value
    if (action === 'suspend') await platformApi.suspendCompany(company.id, 'Suspensión desde administración')
    else await platformApi.reactivateCompany(company.id)
    notifications.success(action === 'suspend' ? 'Empresa suspendida.' : 'Empresa reactivada.')
    confirming.value = null
    await list.load()
  } catch (error) {
    notifications.error(errorMessage(error))
  } finally {
    acting.value = false
  }
}

// -------------------------------------------------------------- Ver como ----

async function viewAs(company: PlatformCompany) {
  try {
    await auth.viewAsCompany(company.id)
    notifications.info(`Viendo el sistema como ${company.name}.`)
    router.push({ name: 'dashboard' })
  } catch (error) {
    notifications.error(errorMessage(error))
  }
}

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
      title="Empresas"
      description="Cuentas suscritas a Gestora. Aquí se administra la cuenta, no la información interna de cada empresa."
    >
      <template #actions>
        <BaseButton v-if="auth.canWrite('platform_companies')" variant="primary" @click="openCreate">
          Registrar empresa
        </BaseButton>
      </template>
    </PageHeader>

    <div class="toolbar">
      <input v-model="list.search.value" class="control search" type="search" placeholder="Buscar por nombre, correo o cédula…" />
      <select v-model="list.filters.value.status" class="control filter">
        <option :value="null">Todos los estados</option>
        <option :value="CompanyStatus.Active">Activas</option>
        <option :value="CompanyStatus.Suspended">Suspendidas</option>
        <option :value="CompanyStatus.Cancelled">Dadas de baja</option>
      </select>
      <select v-model="list.filters.value.expired" class="control filter">
        <option :value="null">Todas</option>
        <option :value="true">Con suscripción vencida</option>
      </select>
    </div>

    <DataTable
      :columns="columns"
      :rows="list.items.value"
      :loading="list.loading.value"
      :page="list.page.value"
      :page-size="list.pageSize"
      :total="list.total.value"
      empty-title="Sin empresas"
      empty-message="Registre la primera empresa suscrita para empezar."
      @update:page="list.page.value = $event"
    >
      <template #name="{ row }">
        <strong>{{ row.name }}</strong>
        <small class="muted block">{{ row.accountEmail }}</small>
      </template>

      <template #subscription="{ row }">
        <template v-if="row.subscription">
          <StatusBadge tone="brand">{{ row.subscription.planName }}</StatusBadge>
          <small class="muted block num">{{ currency(row.subscription.price) }}</small>
        </template>
        <span v-else class="muted">Sin suscripción</span>
      </template>

      <template #expiry="{ row }">
        <template v-if="row.subscription">
          <span class="num" :class="{ overdue: row.subscription.isExpired }">
            {{ date(row.subscription.endDate) }}
          </span>
          <small class="muted block">
            {{ row.subscription.isExpired
              ? `Vencida hace ${Math.abs(row.subscription.daysToExpiry)} d`
              : `${row.subscription.daysToExpiry} d` }}
          </small>
        </template>
        <span v-else class="muted">—</span>
      </template>

      <template #users="{ row }"><span class="num">{{ row.userCount }}</span></template>

      <template #status="{ row }">
        <StatusBadge :tone="statusTone(row.status)">{{ row.statusName }}</StatusBadge>
      </template>

      <template #actions="{ row }">
        <div class="row-actions">
          <BaseButton
            v-if="auth.isDeveloper"
            size="sm"
            variant="primary"
            title="Entrar a ver el sistema como esta empresa"
            @click="viewAs(row)"
          >
            Ver como
          </BaseButton>
          <BaseButton v-if="auth.canWrite('platform_companies')" size="sm" @click="openEdit(row)">
            Editar
          </BaseButton>
          <BaseButton
            v-if="auth.canWrite('platform_companies')"
            size="sm"
            variant="ghost"
            @click="confirming = { company: row, action: row.status === CompanyStatus.Active ? 'suspend' : 'reactivate' }"
          >
            {{ row.status === CompanyStatus.Active ? 'Suspender' : 'Reactivar' }}
          </BaseButton>
        </div>
      </template>

      <template #empty-action>
        <BaseButton v-if="auth.canWrite('platform_companies')" variant="primary" @click="openCreate">
          Registrar empresa
        </BaseButton>
      </template>
    </DataTable>

    <!-- Alta / edición -->
    <BaseModal
      :open="modalOpen"
      :title="editing ? `Editar ${editing.name}` : 'Registrar empresa'"
      :subtitle="editing ? undefined : 'Se crean la empresa, su suscripción y la cuenta con la que entrará.'"
      width="760px"
      @close="modalOpen = false"
    >
      <form id="company-form" class="form-grid" @submit.prevent="save">
        <BaseField v-slot="{ id, invalid }" label="Nombre de la empresa" required span2 :error="errors.name">
          <input :id="id" v-model="form.name" class="control" :class="{ 'is-invalid': invalid }" required maxlength="150" />
        </BaseField>

        <BaseField
          v-slot="{ id, invalid }"
          label="Correo de la cuenta"
          required
          hint="Con este correo entra el administrador de la empresa."
          :error="errors.accountEmail"
        >
          <input :id="id" v-model="form.accountEmail" class="control" :class="{ 'is-invalid': invalid }" type="email" required maxlength="150" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Cédula jurídica">
          <input :id="id" v-model="form.taxId" class="control" maxlength="50" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Teléfono">
          <input :id="id" v-model="form.phone" class="control" maxlength="30" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Moneda">
          <select :id="id" v-model="form.currency" class="control">
            <option value="CRC">Colón (CRC)</option>
            <option value="USD">Dólar (USD)</option>
          </select>
        </BaseField>

        <BaseField v-slot="{ id }" label="Dirección" span2>
          <input :id="id" v-model="form.address" class="control" maxlength="250" />
        </BaseField>

        <template v-if="!editing">
          <div class="section span-2">Suscripción</div>

          <BaseField v-slot="{ id, invalid }" label="Plan" required :error="errors.planId">
            <select :id="id" v-model.number="form.planId" class="control" :class="{ 'is-invalid': invalid }" required>
              <option :value="0" disabled>Seleccione…</option>
              <option v-for="plan in plans" :key="plan.id" :value="plan.id">
                {{ plan.name }} — {{ currency(plan.price) }} / {{ plan.billingPeriodMonths }} mes(es)
              </option>
            </select>
          </BaseField>

          <BaseField v-slot="{ id }" label="Precio pactado" hint="Vacío = precio de lista del plan.">
            <input :id="id" v-model="form.price" class="control" type="number" min="0" step="0.01" />
          </BaseField>

          <BaseField v-slot="{ id }" label="Período de prueba" span2>
            <label class="check">
              <input :id="id" v-model="form.trialPeriod" type="checkbox" />
              <span>Iniciar como prueba, sin cobro todavía</span>
            </label>
          </BaseField>

          <div class="section span-2">Cuenta de administrador</div>

          <BaseField v-slot="{ id, invalid }" label="Nombre del contacto" required :error="errors.adminFirstName">
            <input :id="id" v-model="form.adminFirstName" class="control" :class="{ 'is-invalid': invalid }" required maxlength="100" />
          </BaseField>

          <BaseField v-slot="{ id }" label="Apellidos">
            <input :id="id" v-model="form.adminLastName" class="control" maxlength="150" />
          </BaseField>

          <BaseField
            v-slot="{ id, invalid }"
            label="Contraseña"
            span2
            hint="Si la deja vacía, se genera una temporal y se muestra una sola vez."
            :error="errors.adminPassword"
          >
            <input :id="id" v-model="form.adminPassword" class="control" :class="{ 'is-invalid': invalid }" type="text" minlength="8" autocomplete="off" />
          </BaseField>
        </template>

        <BaseField v-slot="{ id }" label="Notas internas" span2>
          <textarea :id="id" v-model="form.notes" class="control" maxlength="500" />
        </BaseField>
      </form>

      <template #footer>
        <BaseButton variant="ghost" @click="modalOpen = false">Cancelar</BaseButton>
        <BaseButton type="submit" form="company-form" variant="primary" :loading="saving">
          {{ editing ? 'Guardar cambios' : 'Registrar empresa' }}
        </BaseButton>
      </template>
    </BaseModal>

    <!-- Credenciales generadas: se muestran una única vez -->
    <BaseModal
      :open="!!credentials"
      title="Empresa registrada"
      subtitle="Entregue estas credenciales al cliente. La contraseña no se puede volver a consultar."
      width="480px"
      @close="credentials = null"
    >
      <template v-if="credentials">
        <p class="lead">{{ credentials.company.name }}</p>
        <dl class="creds">
          <div><dt>Correo</dt><dd class="num">{{ credentials.adminEmail }}</dd></div>
          <div><dt>Contraseña temporal</dt><dd class="num strong">{{ credentials.temporaryPassword }}</dd></div>
        </dl>
        <p class="muted note">
          Pídale al cliente que la cambie desde «Mi cuenta» la primera vez que entre.
        </p>
      </template>

      <template #footer>
        <BaseButton @click="copyCredentials">Copiar</BaseButton>
        <BaseButton variant="primary" @click="credentials = null">Listo</BaseButton>
      </template>
    </BaseModal>

    <ConfirmDialog
      :open="!!confirming"
      :title="confirming?.action === 'suspend' ? 'Suspender empresa' : 'Reactivar empresa'"
      :message="
        confirming?.action === 'suspend'
          ? `${confirming?.company.name} no podrá entrar al sistema y sus sesiones abiertas se cerrarán. Su información se conserva intacta.`
          : `${confirming?.company.name} podrá volver a entrar al sistema.`
      "
      :confirm-label="confirming?.action === 'suspend' ? 'Suspender' : 'Reactivar'"
      :variant="confirming?.action === 'suspend' ? 'danger' : 'primary'"
      :loading="acting"
      @cancel="confirming = null"
      @confirm="confirmStatus"
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
  max-width: 360px;
}

.filter {
  width: 190px;
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

.section {
  font-size: 11.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--ink-500);
  border-top: 1px solid var(--ink-200);
  padding-top: 14px;
  margin-top: 4px;
}

.check {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13.5px;
  font-weight: 400;
}

.lead {
  font-weight: 650;
  margin-bottom: 12px;
}

.creds {
  margin: 0;
  display: grid;
  gap: 10px;
  background: var(--ink-100);
  border-radius: var(--radius);
  padding: 14px 16px;
}

.creds > div {
  display: flex;
  justify-content: space-between;
  gap: 16px;
}

.creds dt {
  color: var(--ink-500);
  font-size: 13px;
}

.creds dd {
  margin: 0;
  font-weight: 600;
}

.creds .strong {
  font-size: 15px;
  color: var(--brand-700);
}

.note {
  font-size: 12.5px;
  margin-top: 12px;
}
</style>

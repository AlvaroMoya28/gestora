<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
import DataTable from '@/components/ui/DataTable.vue'
import PageHeader from '@/components/ui/PageHeader.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import type { Column } from '@/components/ui/table'
import { useResourceList } from '@/composables/useResourceList'
import { suppliersApi } from '@/services/api'
import { errorMessage, fieldErrors } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import { PaymentTerm, type Supplier } from '@/types'

const auth = useAuthStore()
const notifications = useNotificationStore()
const list = useResourceList<Supplier>(suppliersApi.list, { initialFilters: { active: true } })

const columns: Column[] = [
  { key: 'code', label: 'Código', width: '110px' },
  { key: 'name', label: 'Proveedor' },
  { key: 'contact', label: 'Contacto' },
  { key: 'terms', label: 'Condición' },
  { key: 'status', label: 'Estado', width: '100px' },
  { key: 'actions', label: '', align: 'right', width: '150px' },
]

const emptyForm = () => ({
  code: '',
  name: '',
  tradeName: '',
  taxId: '',
  phone: '',
  email: '',
  address: '',
  contactName: '',
  paymentTerm: PaymentTerm.Cash as number,
  creditDays: 0,
  notes: '',
})

const modalOpen = ref(false)
const editing = ref<Supplier | null>(null)
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive(emptyForm())

function openCreate() {
  editing.value = null
  errors.value = {}
  Object.assign(form, emptyForm())
  modalOpen.value = true
}

function openEdit(supplier: Supplier) {
  editing.value = supplier
  errors.value = {}
  Object.assign(form, {
    code: supplier.code,
    name: supplier.name,
    tradeName: supplier.tradeName ?? '',
    taxId: supplier.taxId ?? '',
    phone: supplier.phone ?? '',
    email: supplier.email ?? '',
    address: supplier.address ?? '',
    contactName: supplier.contactName ?? '',
    paymentTerm: supplier.paymentTerm as number,
    creditDays: supplier.creditDays,
    notes: supplier.notes ?? '',
  })
  modalOpen.value = true
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    const payload = { ...form, paymentTerm: Number(form.paymentTerm) } as Partial<Supplier>
    if (editing.value) {
      await suppliersApi.update(editing.value.id, payload)
      notifications.success('Proveedor actualizado.')
    } else {
      await suppliersApi.create(payload)
      notifications.success('Proveedor registrado.')
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

const confirming = ref<Supplier | null>(null)
const toggling = ref(false)

async function confirmToggle() {
  if (!confirming.value) return
  toggling.value = true
  try {
    const target = confirming.value
    await suppliersApi.setActive(target.id, !target.isActive)
    notifications.success(target.isActive ? 'Proveedor inactivado.' : 'Proveedor reactivado.')
    confirming.value = null
    await list.load()
  } catch (error) {
    notifications.error(errorMessage(error))
  } finally {
    toggling.value = false
  }
}

onMounted(list.load)
</script>

<template>
  <div>
    <PageHeader title="Proveedores" description="Quiénes le venden materiales, insumos y servicios.">
      <template #actions>
        <BaseButton v-if="auth.canWrite('suppliers')" variant="primary" @click="openCreate">
          Nuevo proveedor
        </BaseButton>
      </template>
    </PageHeader>

    <div class="toolbar">
      <input v-model="list.search.value" class="control search" type="search" placeholder="Buscar por nombre, código o cédula…" />
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
      empty-title="Sin proveedores"
      empty-message="Registre a quienes le suministran materiales para poder controlar sus compras."
      @update:page="list.page.value = $event"
    >
      <template #code="{ row }"><span class="num">{{ row.code }}</span></template>

      <template #name="{ row }">
        <strong>{{ row.name }}</strong>
        <small v-if="row.tradeName" class="muted block">{{ row.tradeName }}</small>
      </template>

      <template #contact="{ row }">
        <span v-if="row.contactName || row.phone || row.email">
          {{ row.contactName || '—' }}
          <small class="muted block">{{ row.phone || row.email }}</small>
        </span>
        <span v-else class="muted">—</span>
      </template>

      <template #terms="{ row }">
        <StatusBadge :tone="row.paymentTerm === PaymentTerm.Credit ? 'info' : 'neutral'">
          {{ row.paymentTerm === PaymentTerm.Credit ? `Crédito ${row.creditDays} d` : 'Contado' }}
        </StatusBadge>
      </template>

      <template #status="{ row }">
        <StatusBadge :tone="row.isActive ? 'success' : 'neutral'">
          {{ row.isActive ? 'Activo' : 'Inactivo' }}
        </StatusBadge>
      </template>

      <template #actions="{ row }">
        <div class="row-actions">
          <BaseButton size="sm" @click="openEdit(row)">
            {{ auth.canWrite('suppliers') ? 'Editar' : 'Ver' }}
          </BaseButton>
          <BaseButton v-if="auth.canWrite('suppliers')" size="sm" variant="ghost" @click="confirming = row">
            {{ row.isActive ? 'Inactivar' : 'Activar' }}
          </BaseButton>
        </div>
      </template>

      <template #empty-action>
        <BaseButton v-if="auth.canWrite('suppliers')" variant="primary" @click="openCreate">
          Nuevo proveedor
        </BaseButton>
      </template>
    </DataTable>

    <BaseModal
      :open="modalOpen"
      :title="editing ? 'Editar proveedor' : 'Nuevo proveedor'"
      @close="modalOpen = false"
    >
      <form id="supplier-form" class="form-grid" @submit.prevent="save">
        <BaseField v-slot="{ id, invalid }" label="Código" hint="Se genera solo si lo deja vacío." :error="errors.code">
          <input :id="id" v-model="form.code" class="control" :class="{ 'is-invalid': invalid }" maxlength="20" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Cédula jurídica o física">
          <input :id="id" v-model="form.taxId" class="control" maxlength="50" />
        </BaseField>

        <BaseField v-slot="{ id, invalid }" label="Nombre o razón social" required span2 :error="errors.name">
          <input :id="id" v-model="form.name" class="control" :class="{ 'is-invalid': invalid }" required maxlength="150" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Nombre comercial" span2>
          <input :id="id" v-model="form.tradeName" class="control" maxlength="150" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Persona de contacto">
          <input :id="id" v-model="form.contactName" class="control" maxlength="120" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Teléfono">
          <input :id="id" v-model="form.phone" class="control" maxlength="30" />
        </BaseField>

        <BaseField v-slot="{ id, invalid }" label="Correo electrónico" :error="errors.email">
          <input :id="id" v-model="form.email" class="control" :class="{ 'is-invalid': invalid }" type="email" maxlength="150" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Condición de pago">
          <select :id="id" v-model.number="form.paymentTerm" class="control">
            <option :value="PaymentTerm.Cash">Contado</option>
            <option :value="PaymentTerm.Credit">Crédito</option>
          </select>
        </BaseField>

        <BaseField v-slot="{ id }" label="Días de crédito" span2>
          <input
            :id="id"
            v-model.number="form.creditDays"
            class="control"
            type="number"
            min="0"
            max="365"
            :disabled="Number(form.paymentTerm) === PaymentTerm.Cash"
          />
        </BaseField>

        <BaseField v-slot="{ id }" label="Dirección" span2>
          <input :id="id" v-model="form.address" class="control" maxlength="250" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Notas" span2>
          <textarea :id="id" v-model="form.notes" class="control" maxlength="500" />
        </BaseField>
      </form>

      <template #footer>
        <BaseButton variant="ghost" @click="modalOpen = false">Cancelar</BaseButton>
        <BaseButton
          v-if="auth.canWrite('suppliers')"
          type="submit"
          form="supplier-form"
          variant="primary"
          :loading="saving"
        >
          {{ editing ? 'Guardar cambios' : 'Registrar proveedor' }}
        </BaseButton>
      </template>
    </BaseModal>

    <ConfirmDialog
      :open="!!confirming"
      :title="confirming?.isActive ? 'Inactivar proveedor' : 'Reactivar proveedor'"
      :message="
        confirming?.isActive
          ? `${confirming?.name} dejará de aparecer en nuevas compras, pero su historial se conserva.`
          : `${confirming?.name} volverá a estar disponible para nuevas compras.`
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
  max-width: 420px;
}

.filter {
  width: 150px;
}

.block {
  display: block;
}

.row-actions {
  display: flex;
  gap: 6px;
  justify-content: flex-end;
}
</style>

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
import { useFormat } from '@/composables/useFormat'
import { useResourceList } from '@/composables/useResourceList'
import { usersApi } from '@/services/api'
import { errorMessage, fieldErrors } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import type { Role, User } from '@/types'

const auth = useAuthStore()
const notifications = useNotificationStore()
const { dateTime } = useFormat()

const list = useResourceList<User>(usersApi.list, { initialFilters: { active: null } })
const roles = ref<Role[]>([])

const columns: Column[] = [
  { key: 'name', label: 'Usuario' },
  { key: 'role', label: 'Rol', width: '180px' },
  { key: 'lastLogin', label: 'Último ingreso', width: '170px' },
  { key: 'status', label: 'Estado', width: '100px' },
  { key: 'actions', label: '', align: 'right', width: '150px' },
]

const emptyForm = () => ({
  firstName: '',
  lastName: '',
  email: '',
  phone: '',
  roleId: 0,
  password: '',
})

const modalOpen = ref(false)
const editing = ref<User | null>(null)
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive(emptyForm())

function openCreate() {
  editing.value = null
  errors.value = {}
  Object.assign(form, emptyForm(), { roleId: roles.value[0]?.id ?? 0 })
  modalOpen.value = true
}

function openEdit(user: User) {
  editing.value = user
  errors.value = {}
  Object.assign(form, {
    firstName: user.firstName,
    lastName: user.lastName,
    email: user.email,
    phone: user.phone ?? '',
    roleId: user.roleId,
    password: '',
  })
  modalOpen.value = true
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    const payload: Record<string, unknown> = { ...form, roleId: Number(form.roleId) }
    // Al editar, una contraseña vacía significa "no cambiarla".
    if (editing.value && !form.password) delete payload.password

    if (editing.value) {
      await usersApi.update(editing.value.id, payload)
      notifications.success('Usuario actualizado.')
    } else {
      await usersApi.create(payload)
      notifications.success('Usuario creado.')
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

const confirming = ref<User | null>(null)
const toggling = ref(false)

async function confirmToggle() {
  if (!confirming.value) return
  toggling.value = true
  try {
    const target = confirming.value
    await usersApi.setActive(target.id, !target.isActive)
    notifications.success(target.isActive ? 'Usuario desactivado.' : 'Usuario reactivado.')
    confirming.value = null
    await list.load()
  } catch (error) {
    notifications.error(errorMessage(error))
  } finally {
    toggling.value = false
  }
}

onMounted(async () => {
  await Promise.all([
    list.load(),
    usersApi.roles().then((data) => (roles.value = data)),
  ]).catch((error) => notifications.error(errorMessage(error)))
})
</script>

<template>
  <div>
    <PageHeader title="Usuarios y roles" description="Quién entra al sistema y qué módulos puede usar.">
      <template #actions>
        <BaseButton v-if="auth.canWrite('users')" variant="primary" @click="openCreate">
          Nuevo usuario
        </BaseButton>
      </template>
    </PageHeader>

    <section class="roles">
      <article v-for="role in roles" :key="role.id" class="role-card">
        <div class="row-between">
          <strong>{{ role.name }}</strong>
          <StatusBadge tone="neutral">{{ role.userCount }} usuario(s)</StatusBadge>
        </div>
        <p class="muted">{{ role.description }}</p>
        <p class="modules muted">
          {{ role.permissions.filter((p) => p.canWrite).length }} módulo(s) con edición ·
          {{ role.permissions.filter((p) => p.canRead && !p.canWrite).length }} solo lectura
        </p>
      </article>
    </section>

    <div class="toolbar">
      <input v-model="list.search.value" class="control search" type="search" placeholder="Buscar por nombre o correo…" />
      <select v-model="list.filters.value.active" class="control filter">
        <option :value="null">Todos</option>
        <option :value="true">Activos</option>
        <option :value="false">Inactivos</option>
      </select>
    </div>

    <DataTable
      :columns="columns"
      :rows="list.items.value"
      :loading="list.loading.value"
      :page="list.page.value"
      :page-size="list.pageSize"
      :total="list.total.value"
      empty-title="Sin usuarios"
      empty-message="Cree cuentas para las personas que usarán el sistema."
      @update:page="list.page.value = $event"
    >
      <template #name="{ row }">
        <strong>{{ row.fullName }}</strong>
        <small class="muted block">{{ row.email }}</small>
      </template>

      <template #role="{ row }">
        <StatusBadge tone="brand">{{ row.roleName }}</StatusBadge>
      </template>

      <template #lastLogin="{ row }">
        <span class="muted num">{{ dateTime(row.lastLoginAt) }}</span>
      </template>

      <template #status="{ row }">
        <StatusBadge :tone="row.isActive ? 'success' : 'neutral'">
          {{ row.isActive ? 'Activo' : 'Inactivo' }}
        </StatusBadge>
      </template>

      <template #actions="{ row }">
        <div class="row-actions">
          <BaseButton v-if="auth.canWrite('users')" size="sm" @click="openEdit(row)">Editar</BaseButton>
          <BaseButton
            v-if="auth.canWrite('users') && row.id !== auth.user?.id"
            size="sm"
            variant="ghost"
            @click="confirming = row"
          >
            {{ row.isActive ? 'Desactivar' : 'Activar' }}
          </BaseButton>
        </div>
      </template>
    </DataTable>

    <BaseModal
      :open="modalOpen"
      :title="editing ? 'Editar usuario' : 'Nuevo usuario'"
      subtitle="El rol determina a qué módulos entra y si puede modificar información."
      @close="modalOpen = false"
    >
      <form id="user-form" class="form-grid" @submit.prevent="save">
        <BaseField v-slot="{ id, invalid }" label="Nombre" required :error="errors.firstName">
          <input :id="id" v-model="form.firstName" class="control" :class="{ 'is-invalid': invalid }" required maxlength="100" />
        </BaseField>

        <BaseField v-slot="{ id, invalid }" label="Apellidos" required :error="errors.lastName">
          <input :id="id" v-model="form.lastName" class="control" :class="{ 'is-invalid': invalid }" required maxlength="150" />
        </BaseField>

        <BaseField v-slot="{ id, invalid }" label="Correo electrónico" required :error="errors.email">
          <input :id="id" v-model="form.email" class="control" :class="{ 'is-invalid': invalid }" type="email" required maxlength="150" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Teléfono">
          <input :id="id" v-model="form.phone" class="control" maxlength="30" />
        </BaseField>

        <BaseField v-slot="{ id, invalid }" label="Rol" required :error="errors.roleId">
          <select :id="id" v-model.number="form.roleId" class="control" :class="{ 'is-invalid': invalid }" required>
            <option :value="0" disabled>Seleccione…</option>
            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
          </select>
        </BaseField>

        <BaseField
          v-slot="{ id, invalid }"
          :label="editing ? 'Nueva contraseña' : 'Contraseña'"
          :required="!editing"
          :error="errors.password"
          :hint="editing ? 'Déjela vacía para no cambiarla. Al cambiarla se cierran sus sesiones.' : 'Mínimo 8 caracteres.'"
        >
          <input
            :id="id"
            v-model="form.password"
            class="control"
            :class="{ 'is-invalid': invalid }"
            type="password"
            autocomplete="new-password"
            :required="!editing"
            minlength="8"
          />
        </BaseField>
      </form>

      <template #footer>
        <BaseButton variant="ghost" @click="modalOpen = false">Cancelar</BaseButton>
        <BaseButton type="submit" form="user-form" variant="primary" :loading="saving">
          {{ editing ? 'Guardar cambios' : 'Crear usuario' }}
        </BaseButton>
      </template>
    </BaseModal>

    <ConfirmDialog
      :open="!!confirming"
      :title="confirming?.isActive ? 'Desactivar usuario' : 'Reactivar usuario'"
      :message="
        confirming?.isActive
          ? `${confirming?.fullName} no podrá volver a entrar y sus sesiones abiertas se cerrarán.`
          : `${confirming?.fullName} podrá volver a iniciar sesión.`
      "
      :confirm-label="confirming?.isActive ? 'Desactivar' : 'Reactivar'"
      :variant="confirming?.isActive ? 'danger' : 'primary'"
      :loading="toggling"
      @cancel="confirming = null"
      @confirm="confirmToggle"
    />
  </div>
</template>

<style scoped>
.roles {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: 12px;
  margin-bottom: 20px;
}

.role-card {
  background: var(--surface);
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  padding: 13px 15px;
  box-shadow: var(--shadow-sm);
}

.role-card p {
  font-size: 12.5px;
  margin-top: 5px;
}

.modules {
  font-size: 12px;
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

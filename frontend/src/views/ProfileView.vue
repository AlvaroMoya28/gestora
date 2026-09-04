<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import PageHeader from '@/components/ui/PageHeader.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import { authApi } from '@/services/api'
import { errorMessage, fieldErrors } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'

const auth = useAuthStore()
const notifications = useNotificationStore()
const router = useRouter()

const form = reactive({ currentPassword: '', newPassword: '', confirmPassword: '' })
const errors = ref<Record<string, string>>({})
const saving = ref(false)

async function changePassword() {
  errors.value = {}

  if (form.newPassword !== form.confirmPassword) {
    errors.value.confirmPassword = 'Las contraseñas no coinciden.'
    return
  }

  saving.value = true
  try {
    await authApi.changePassword(form.currentPassword, form.newPassword)
    notifications.success('Contraseña actualizada. Vuelva a iniciar sesión.')
    // El backend revoca las sesiones abiertas al cambiar la contraseña.
    await auth.logout()
    router.push({ name: 'login' })
  } catch (error) {
    errors.value = { ...errors.value, ...fieldErrors(error) }
    notifications.error(errorMessage(error))
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div>
    <PageHeader title="Mi cuenta" description="Sus datos de acceso y los módulos que tiene habilitados." />

    <div class="grid">
      <section class="card">
        <h3>Datos de la cuenta</h3>
        <dl>
          <div><dt>Nombre</dt><dd>{{ auth.user?.fullName }}</dd></div>
          <div><dt>Correo</dt><dd>{{ auth.user?.email }}</dd></div>
          <div><dt>Rol</dt><dd>{{ auth.user?.roleName }}</dd></div>
          <div><dt>Empresa</dt><dd>{{ auth.user?.companyName }}</dd></div>
        </dl>

        <h4>Módulos habilitados</h4>
        <div class="modules">
          <StatusBadge
            v-for="item in auth.modules"
            :key="item.key"
            :tone="item.canWrite ? 'brand' : 'neutral'"
          >
            {{ item.name }}{{ item.canWrite ? '' : ' · lectura' }}
          </StatusBadge>
        </div>
      </section>

      <section class="card">
        <h3>Cambiar contraseña</h3>
        <p class="muted intro">Al cambiarla se cerrarán todas sus sesiones abiertas.</p>

        <form class="stack" @submit.prevent="changePassword">
          <BaseField v-slot="{ id, invalid }" label="Contraseña actual" required :error="errors.currentPassword">
            <input
              :id="id"
              v-model="form.currentPassword"
              class="control"
              :class="{ 'is-invalid': invalid }"
              type="password"
              autocomplete="current-password"
              required
            />
          </BaseField>

          <BaseField
            v-slot="{ id, invalid }"
            label="Nueva contraseña"
            required
            hint="Mínimo 8 caracteres."
            :error="errors.newPassword"
          >
            <input
              :id="id"
              v-model="form.newPassword"
              class="control"
              :class="{ 'is-invalid': invalid }"
              type="password"
              autocomplete="new-password"
              minlength="8"
              required
            />
          </BaseField>

          <BaseField v-slot="{ id, invalid }" label="Confirmar nueva contraseña" required :error="errors.confirmPassword">
            <input
              :id="id"
              v-model="form.confirmPassword"
              class="control"
              :class="{ 'is-invalid': invalid }"
              type="password"
              autocomplete="new-password"
              required
            />
          </BaseField>

          <BaseButton type="submit" variant="primary" :loading="saving">Cambiar contraseña</BaseButton>
        </form>
      </section>
    </div>
  </div>
</template>

<style scoped>
.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 16px;
  align-items: start;
}

.card {
  background: var(--surface);
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  padding: 18px 20px;
}

h3 {
  font-size: 14.5px;
  font-weight: 650;
  margin-bottom: 14px;
}

h4 {
  font-size: 12.5px;
  font-weight: 650;
  color: var(--ink-500);
  margin: 20px 0 10px;
}

.intro {
  font-size: 13px;
  margin: -8px 0 16px;
}

dl {
  margin: 0;
  display: grid;
  gap: 10px;
}

dl > div {
  display: flex;
  justify-content: space-between;
  gap: 16px;
  border-bottom: 1px solid var(--ink-100);
  padding-bottom: 9px;
}

dt {
  color: var(--ink-500);
  font-size: 13px;
}

dd {
  margin: 0;
  font-weight: 600;
  font-size: 13.5px;
  text-align: right;
}

.modules {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
}

.stack {
  display: flex;
  flex-direction: column;
  gap: 14px;
  align-items: flex-start;
}

.stack :deep(.field) {
  width: 100%;
}
</style>

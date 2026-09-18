<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import PageHeader from '@/components/ui/PageHeader.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import { useFormat } from '@/composables/useFormat'
import { platformApi } from '@/services/api'
import { errorMessage, fieldErrors } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import type { Plan } from '@/types'

const auth = useAuthStore()
const notifications = useNotificationStore()
const { currency } = useFormat()

const plans = ref<Plan[]>([])
const loading = ref(true)

const emptyForm = () => ({
  name: '',
  description: '',
  price: 0,
  currency: 'CRC',
  billingPeriodMonths: 1,
  maxUsers: 2,
  sortOrder: 0,
})

const modalOpen = ref(false)
const editing = ref<Plan | null>(null)
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive(emptyForm())

function openCreate() {
  editing.value = null
  errors.value = {}
  Object.assign(form, emptyForm(), { sortOrder: plans.value.length + 1 })
  modalOpen.value = true
}

function openEdit(plan: Plan) {
  editing.value = plan
  errors.value = {}
  Object.assign(form, {
    name: plan.name,
    description: plan.description ?? '',
    price: plan.price,
    currency: plan.currency,
    billingPeriodMonths: plan.billingPeriodMonths,
    maxUsers: plan.maxUsers,
    sortOrder: plan.sortOrder,
  })
  modalOpen.value = true
}

async function save() {
  saving.value = true
  errors.value = {}
  try {
    const payload = {
      ...form,
      price: Number(form.price),
      billingPeriodMonths: Number(form.billingPeriodMonths),
      maxUsers: Number(form.maxUsers),
      sortOrder: Number(form.sortOrder),
      description: form.description || null,
    }
    if (editing.value) {
      await platformApi.updatePlan(editing.value.id, payload)
      notifications.success('Plan actualizado.')
    } else {
      await platformApi.createPlan(payload)
      notifications.success('Plan creado.')
    }
    modalOpen.value = false
    await load()
  } catch (error) {
    errors.value = fieldErrors(error)
    notifications.error(errorMessage(error))
  } finally {
    saving.value = false
  }
}

async function toggle(plan: Plan) {
  try {
    await platformApi.setPlanActive(plan.id, !plan.isActive)
    notifications.success(plan.isActive ? 'Plan retirado del catálogo.' : 'Plan reactivado.')
    await load()
  } catch (error) {
    notifications.error(errorMessage(error))
  }
}

const periodLabel = (months: number) =>
  months === 1 ? 'mensual' : months === 12 ? 'anual' : `cada ${months} meses`

async function load() {
  loading.value = true
  try {
    plans.value = await platformApi.plans()
  } catch (error) {
    notifications.error(errorMessage(error))
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <PageHeader
      title="Planes"
      description="Catálogo comercial de Gestora. El precio de lista no altera las suscripciones ya pactadas."
    >
      <template #actions>
        <BaseButton v-if="auth.canWrite('platform_plans')" variant="primary" @click="openCreate">
          Nuevo plan
        </BaseButton>
      </template>
    </PageHeader>

    <section v-if="loading" class="grid">
      <div v-for="n in 3" :key="n" class="plan-card loading-card" />
    </section>

    <section v-else-if="plans.length" class="grid">
      <article v-for="plan in plans" :key="plan.id" class="plan-card" :class="{ retired: !plan.isActive }">
        <header>
          <h3>{{ plan.name }}</h3>
          <StatusBadge :tone="plan.isActive ? 'success' : 'neutral'">
            {{ plan.isActive ? 'En catálogo' : 'Retirado' }}
          </StatusBadge>
        </header>

        <p class="price">
          <span class="num">{{ currency(plan.price) }}</span>
          <small class="muted">{{ periodLabel(plan.billingPeriodMonths) }}</small>
        </p>

        <p v-if="plan.description" class="muted description">{{ plan.description }}</p>

        <dl>
          <div>
            <dt>Usuarios</dt>
            <dd>{{ plan.maxUsers > 0 ? plan.maxUsers : 'Sin límite' }}</dd>
          </div>
          <div>
            <dt>Empresas</dt>
            <dd>{{ plan.companyCount }}</dd>
          </div>
        </dl>

        <footer v-if="auth.canWrite('platform_plans')">
          <BaseButton size="sm" @click="openEdit(plan)">Editar</BaseButton>
          <BaseButton size="sm" variant="ghost" @click="toggle(plan)">
            {{ plan.isActive ? 'Retirar' : 'Reactivar' }}
          </BaseButton>
        </footer>
      </article>
    </section>

    <p v-else class="empty muted">Todavía no hay planes definidos.</p>

    <BaseModal
      :open="modalOpen"
      :title="editing ? `Editar ${editing.name}` : 'Nuevo plan'"
      width="560px"
      @close="modalOpen = false"
    >
      <form id="plan-form" class="form-grid" @submit.prevent="save">
        <BaseField v-slot="{ id, invalid }" label="Nombre" required span2 :error="errors.name">
          <input :id="id" v-model="form.name" class="control" :class="{ 'is-invalid': invalid }" required maxlength="60" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Descripción" span2>
          <textarea :id="id" v-model="form.description" class="control" maxlength="300" />
        </BaseField>

        <BaseField v-slot="{ id, invalid }" label="Precio por período" required :error="errors.price">
          <input :id="id" v-model.number="form.price" class="control" :class="{ 'is-invalid': invalid }" type="number" min="0" step="0.01" required />
        </BaseField>

        <BaseField v-slot="{ id }" label="Moneda">
          <select :id="id" v-model="form.currency" class="control">
            <option value="CRC">Colón (CRC)</option>
            <option value="USD">Dólar (USD)</option>
          </select>
        </BaseField>

        <BaseField v-slot="{ id, invalid }" label="Meses por período" required hint="1 mensual, 12 anual." :error="errors.billingPeriodMonths">
          <input :id="id" v-model.number="form.billingPeriodMonths" class="control" :class="{ 'is-invalid': invalid }" type="number" min="1" max="60" required />
        </BaseField>

        <BaseField v-slot="{ id }" label="Máximo de usuarios" hint="0 = sin límite.">
          <input :id="id" v-model.number="form.maxUsers" class="control" type="number" min="0" max="500" />
        </BaseField>

        <BaseField v-slot="{ id }" label="Orden en el catálogo" span2>
          <input :id="id" v-model.number="form.sortOrder" class="control" type="number" min="0" />
        </BaseField>
      </form>

      <template #footer>
        <BaseButton variant="ghost" @click="modalOpen = false">Cancelar</BaseButton>
        <BaseButton type="submit" form="plan-form" variant="primary" :loading="saving">
          {{ editing ? 'Guardar cambios' : 'Crear plan' }}
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<style scoped>
.grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
  gap: 16px;
}

.plan-card {
  background: var(--surface);
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  padding: 18px 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.plan-card.retired {
  opacity: 0.66;
}

.loading-card {
  height: 210px;
  background: linear-gradient(90deg, var(--ink-100), var(--ink-200), var(--ink-100));
  background-size: 200% 100%;
  animation: shimmer 1.3s infinite;
  border-color: transparent;
}

@keyframes shimmer {
  to {
    background-position: -200% 0;
  }
}

.plan-card header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
}

h3 {
  font-size: 15px;
  font-weight: 650;
}

.price {
  display: flex;
  align-items: baseline;
  gap: 7px;
}

.price .num {
  font-size: 22px;
  font-weight: 700;
  letter-spacing: -0.02em;
}

.price small {
  font-size: 12.5px;
}

.description {
  font-size: 13px;
  flex: 1;
}

dl {
  margin: 0;
  display: grid;
  gap: 6px;
  border-top: 1px solid var(--ink-100);
  padding-top: 10px;
}

dl > div {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
}

dt {
  color: var(--ink-500);
}

dd {
  margin: 0;
  font-weight: 600;
}

.plan-card footer {
  display: flex;
  gap: 7px;
  padding-top: 4px;
}

.empty {
  text-align: center;
  padding: 50px;
  font-size: 13.5px;
}
</style>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseField from '@/components/ui/BaseField.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import PageHeader from '@/components/ui/PageHeader.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import { useFormat } from '@/composables/useFormat'
import { financeCategoriesApi, settingsApi } from '@/services/api'
import { errorMessage, fieldErrors } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import { FinanceKind, type CompanySettings, type FinanceCategory } from '@/types'

const auth = useAuthStore()
const notifications = useNotificationStore()
const { date, integer } = useFormat()

const canWrite = computed(() => auth.canWrite('settings'))

// ------------------------------------------------------ Datos de la empresa ----

const settings = ref<CompanySettings | null>(null)
const saving = ref(false)
const errors = ref<Record<string, string>>({})
const form = reactive({
  name: '',
  taxId: '',
  phone: '',
  address: '',
  currency: 'CRC',
  defaultTaxRate: 13,
})

const currencies = [
  { code: 'CRC', label: 'CRC · Colón costarricense' },
  { code: 'USD', label: 'USD · Dólar estadounidense' },
  { code: 'EUR', label: 'EUR · Euro' },
]

function fill(data: CompanySettings) {
  settings.value = data
  Object.assign(form, {
    name: data.name,
    taxId: data.taxId ?? '',
    phone: data.phone ?? '',
    address: data.address ?? '',
    currency: data.currency,
    defaultTaxRate: data.defaultTaxRate,
  })
}

async function saveCompany() {
  saving.value = true
  errors.value = {}
  try {
    const updated = await settingsApi.update({
      name: form.name,
      taxId: form.taxId || null,
      phone: form.phone || null,
      address: form.address || null,
      currency: form.currency,
      defaultTaxRate: Number(form.defaultTaxRate),
    })
    fill(updated)
    notifications.success('Datos de la empresa actualizados.')

    // La moneda alimenta el formato de todo el sistema: la sesión debe enterarse.
    await auth.refreshProfile()
  } catch (error) {
    errors.value = fieldErrors(error)
    notifications.error(errorMessage(error))
  } finally {
    saving.value = false
  }
}

// ------------------------------------------------- Categorías de ingresos y gastos ----

const categories = ref<FinanceCategory[]>([])

const income = computed(() => categories.value.filter((c) => c.kind === FinanceKind.Income))
const expense = computed(() => categories.value.filter((c) => c.kind === FinanceKind.Expense))

const categoryModal = ref(false)
const editingCategory = ref<FinanceCategory | null>(null)
const savingCategory = ref(false)
const categoryErrors = ref<Record<string, string>>({})
const categoryForm = reactive({ name: '', kind: FinanceKind.Expense as number, description: '' })

function openCategory(kind: number, category?: FinanceCategory) {
  editingCategory.value = category ?? null
  categoryErrors.value = {}
  Object.assign(categoryForm, {
    name: category?.name ?? '',
    kind: category?.kind ?? kind,
    description: category?.description ?? '',
  })
  categoryModal.value = true
}

async function saveCategory() {
  savingCategory.value = true
  categoryErrors.value = {}
  try {
    const payload = {
      name: categoryForm.name,
      kind: Number(categoryForm.kind),
      description: categoryForm.description || null,
    }

    if (editingCategory.value) {
      await financeCategoriesApi.update(editingCategory.value.id, payload)
      notifications.success('Categoría actualizada.')
    } else {
      await financeCategoriesApi.create(payload)
      notifications.success('Categoría creada.')
    }
    categoryModal.value = false
    await loadCategories()
  } catch (error) {
    categoryErrors.value = fieldErrors(error)
    notifications.error(errorMessage(error))
  } finally {
    savingCategory.value = false
  }
}

async function toggleCategory(category: FinanceCategory) {
  try {
    await financeCategoriesApi.setActive(category.id, !category.isActive)
    notifications.success(category.isActive ? 'Categoría inactivada.' : 'Categoría reactivada.')
    await loadCategories()
  } catch (error) {
    notifications.error(errorMessage(error))
  }
}

const loadCategories = () =>
  financeCategoriesApi.list().then((r) => (categories.value = r)).catch(() => undefined)

onMounted(async () => {
  try {
    fill(await settingsApi.get())
    await loadCategories()
  } catch (error) {
    notifications.error(errorMessage(error))
  }
})
</script>

<template>
  <div>
    <PageHeader
      title="Configuración"
      description="Los datos de su empresa y las categorías con las que clasifica el dinero."
    />

    <div class="grid">
      <!-- ---------------------------------------------------- Ficha de la empresa -->
      <section class="panel" data-tour="settings-company">
        <header>
          <h2>Datos de la empresa</h2>
          <p class="muted">Aparecen en los documentos y definen el formato de los importes.</p>
        </header>

        <form id="company-form" class="form-grid" @submit.prevent="saveCompany">
          <BaseField v-slot="{ id, invalid }" label="Nombre" required span2 :error="errors.name">
            <input
              :id="id"
              v-model="form.name"
              class="control"
              :class="{ 'is-invalid': invalid }"
              maxlength="150"
              required
              :disabled="!canWrite"
            />
          </BaseField>

          <BaseField v-slot="{ id }" label="Cédula jurídica">
            <input :id="id" v-model="form.taxId" class="control" maxlength="50" :disabled="!canWrite" />
          </BaseField>

          <BaseField v-slot="{ id }" label="Teléfono">
            <input :id="id" v-model="form.phone" class="control" maxlength="30" :disabled="!canWrite" />
          </BaseField>

          <BaseField v-slot="{ id }" label="Dirección" span2>
            <textarea :id="id" v-model="form.address" class="control" maxlength="250" :disabled="!canWrite" />
          </BaseField>

          <BaseField v-slot="{ id }" label="Moneda" required :error="errors.currency">
            <select :id="id" v-model="form.currency" class="control" required :disabled="!canWrite">
              <option v-for="item in currencies" :key="item.code" :value="item.code">
                {{ item.label }}
              </option>
            </select>
          </BaseField>

          <BaseField
            v-slot="{ id }"
            label="Impuesto por defecto %"
            hint="Se propone al crear documentos nuevos."
            :error="errors.defaultTaxRate"
          >
            <input
              :id="id"
              v-model.number="form.defaultTaxRate"
              class="control"
              type="number"
              min="0"
              max="100"
              step="0.01"
              :disabled="!canWrite"
            />
          </BaseField>

          <BaseField
            v-slot="{ id }"
            label="Correo de la cuenta"
            span2
            hint="Es el identificador de su suscripción. Para cambiarlo, contacte a Gestora."
          >
            <input :id="id" class="control" :value="settings?.accountEmail" disabled />
          </BaseField>
        </form>

        <footer v-if="canWrite">
          <BaseButton type="submit" form="company-form" variant="primary" :loading="saving">
            Guardar cambios
          </BaseButton>
        </footer>
      </section>

      <!-- ----------------------------------------------------------- Resumen -->
      <section v-if="settings" class="panel compact">
        <header>
          <h2>Su cuenta</h2>
        </header>

        <dl class="stats">
          <div><dt>Estado</dt><dd><StatusBadge tone="success">{{ settings.statusName }}</StatusBadge></dd></div>
          <div><dt>Registrada</dt><dd>{{ date(settings.createdAt) }}</dd></div>
          <div><dt>Usuarios activos</dt><dd class="num">{{ integer(settings.stats.users) }}</dd></div>
          <div><dt>Clientes</dt><dd class="num">{{ integer(settings.stats.customers) }}</dd></div>
          <div><dt>Proveedores</dt><dd class="num">{{ integer(settings.stats.suppliers) }}</dd></div>
          <div><dt>Productos</dt><dd class="num">{{ integer(settings.stats.products) }}</dd></div>
          <div><dt>Ventas</dt><dd class="num">{{ integer(settings.stats.sales) }}</dd></div>
          <div><dt>Compras</dt><dd class="num">{{ integer(settings.stats.purchases) }}</dd></div>
          <div><dt>Reparaciones</dt><dd class="num">{{ integer(settings.stats.repairs) }}</dd></div>
          <div><dt>Órdenes de producción</dt><dd class="num">{{ integer(settings.stats.productionOrders) }}</dd></div>
        </dl>
      </section>
    </div>

    <!-- ------------------------------------------------------- Categorías -->
    <section class="panel" data-tour="settings-categories">
      <header class="with-action">
        <div>
          <h2>Categorías de ingresos y gastos</h2>
          <p class="muted">
            Clasifican el dinero en los reportes. Las del sistema respaldan los cobros y pagos
            automáticos, y por eso no se editan.
          </p>
        </div>
      </header>

      <div class="category-columns">
        <div>
          <div class="column-head">
            <h3>Ingresos</h3>
            <BaseButton v-if="canWrite" size="sm" @click="openCategory(FinanceKind.Income)">
              Agregar
            </BaseButton>
          </div>
          <ul class="categories">
            <li v-for="category in income" :key="category.id" :class="{ off: !category.isActive }">
              <div>
                <strong>{{ category.name }}</strong>
                <small v-if="category.description" class="muted block">{{ category.description }}</small>
                <small class="muted block">{{ category.entryCount }} movimiento(s)</small>
              </div>
              <div class="actions">
                <StatusBadge v-if="category.isSystem" tone="info">Sistema</StatusBadge>
                <template v-else-if="canWrite">
                  <BaseButton size="sm" variant="ghost" @click="openCategory(FinanceKind.Income, category)">
                    Editar
                  </BaseButton>
                  <BaseButton size="sm" variant="ghost" @click="toggleCategory(category)">
                    {{ category.isActive ? 'Inactivar' : 'Activar' }}
                  </BaseButton>
                </template>
              </div>
            </li>
          </ul>
        </div>

        <div>
          <div class="column-head">
            <h3>Gastos</h3>
            <BaseButton v-if="canWrite" size="sm" @click="openCategory(FinanceKind.Expense)">
              Agregar
            </BaseButton>
          </div>
          <ul class="categories">
            <li v-for="category in expense" :key="category.id" :class="{ off: !category.isActive }">
              <div>
                <strong>{{ category.name }}</strong>
                <small v-if="category.description" class="muted block">{{ category.description }}</small>
                <small class="muted block">{{ category.entryCount }} movimiento(s)</small>
              </div>
              <div class="actions">
                <StatusBadge v-if="category.isSystem" tone="info">Sistema</StatusBadge>
                <template v-else-if="canWrite">
                  <BaseButton size="sm" variant="ghost" @click="openCategory(FinanceKind.Expense, category)">
                    Editar
                  </BaseButton>
                  <BaseButton size="sm" variant="ghost" @click="toggleCategory(category)">
                    {{ category.isActive ? 'Inactivar' : 'Activar' }}
                  </BaseButton>
                </template>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </section>

    <BaseModal
      :open="categoryModal"
      :title="editingCategory ? 'Editar categoría' : 'Nueva categoría'"
      width="480px"
      @close="categoryModal = false"
    >
      <form id="category-form" class="form-grid" @submit.prevent="saveCategory">
        <BaseField v-slot="{ id, invalid }" label="Nombre" required span2 :error="categoryErrors.name">
          <input
            :id="id"
            v-model="categoryForm.name"
            class="control"
            :class="{ 'is-invalid': invalid }"
            maxlength="80"
            required
          />
        </BaseField>

        <BaseField v-slot="{ id }" label="Tipo" required>
          <select :id="id" v-model.number="categoryForm.kind" class="control" :disabled="!!editingCategory">
            <option :value="FinanceKind.Income">Ingreso</option>
            <option :value="FinanceKind.Expense">Gasto</option>
          </select>
        </BaseField>

        <BaseField v-slot="{ id }" label="Descripción" span2>
          <textarea :id="id" v-model="categoryForm.description" class="control" maxlength="250" />
        </BaseField>
      </form>

      <template #footer>
        <BaseButton variant="ghost" @click="categoryModal = false">Cancelar</BaseButton>
        <BaseButton type="submit" form="category-form" variant="primary" :loading="savingCategory">
          {{ editingCategory ? 'Guardar cambios' : 'Crear categoría' }}
        </BaseButton>
      </template>
    </BaseModal>
  </div>
</template>

<style scoped>
.grid {
  display: grid;
  grid-template-columns: 1fr 300px;
  gap: 18px;
  align-items: start;
  margin-bottom: 18px;
}

@media (max-width: 900px) {
  .grid {
    grid-template-columns: 1fr;
  }
}

.panel {
  background: var(--surface);
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  padding: 18px;
  margin-bottom: 18px;
}

.panel header {
  margin-bottom: 14px;
}

.panel header.with-action {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 14px;
}

.panel h2 {
  font-size: 15px;
  margin: 0;
}

.panel header p {
  margin: 3px 0 0;
  font-size: 12.5px;
  max-width: 640px;
}

.panel footer {
  margin-top: 16px;
  display: flex;
  justify-content: flex-end;
}

.compact {
  margin-bottom: 0;
}

.stats {
  margin: 0;
  display: grid;
  gap: 7px;
}

.stats > div {
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 1px solid var(--ink-100);
  padding-bottom: 6px;
  font-size: 13px;
}

.stats > div:last-child {
  border-bottom: none;
}

.stats dt {
  color: var(--ink-500);
}

.stats dd {
  margin: 0;
  font-weight: 600;
}

.category-columns {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 20px;
}

@media (max-width: 760px) {
  .category-columns {
    grid-template-columns: 1fr;
  }
}

.column-head {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 8px;
}

.column-head h3 {
  font-size: 12.5px;
  font-weight: 650;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--ink-500);
  margin: 0;
}

.categories {
  list-style: none;
  margin: 0;
  padding: 0;
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  overflow: hidden;
}

.categories li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 10px;
  padding: 10px 13px;
  border-bottom: 1px solid var(--ink-100);
}

.categories li:last-child {
  border-bottom: none;
}

.categories li.off {
  opacity: 0.55;
}

.categories strong {
  font-size: 13.5px;
}

.block {
  display: block;
  font-size: 11.5px;
}

.actions {
  display: flex;
  gap: 4px;
  align-items: center;
  flex-shrink: 0;
}
</style>

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
import { productionApi, productsApi } from '@/services/api'
import { errorMessage, fieldErrors } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import {
  ProductionStatus,
  ProductType,
  type Product,
  type ProductionOrder,
  type ProductionOrderSummary,
  type Recipe,
} from '@/types'

const auth = useAuthStore()
const notifications = useNotificationStore()
const { currency, number, date, dateTime } = useFormat()

const tab = ref<'orders' | 'recipes'>('orders')

const list = useResourceList<ProductionOrderSummary>(productionApi.list, {
  initialFilters: { status: null },
})

const products = ref<Product[]>([])
const recipes = ref<Recipe[]>([])

/** Lo que se puede fabricar: nunca un servicio. */
const outputs = computed(() => products.value.filter((p) => p.isActive && p.type !== ProductType.Service))
/** Lo que se puede consumir como material. */
const materials = computed(() => products.value.filter((p) => p.isActive && p.type !== ProductType.Service))

const columns: Column[] = [
  { key: 'number', label: 'Orden', width: '110px' },
  { key: 'date', label: 'Fecha', width: '120px' },
  { key: 'product', label: 'Producto' },
  { key: 'quantity', label: 'Cantidad', align: 'right', width: '150px' },
  { key: 'status', label: 'Estado', width: '130px' },
  { key: 'cost', label: 'Costo', align: 'right', width: '120px' },
  { key: 'actions', label: '', align: 'right', width: '210px' },
]

const statusTone = (status: number) =>
  status === ProductionStatus.Completed
    ? 'success'
    : status === ProductionStatus.Cancelled
      ? 'neutral'
      : status === ProductionStatus.InProgress
        ? 'info'
        : 'warning'

// ==================================================== Órdenes de producción ====

interface MaterialRow {
  productId: number
  plannedQuantity: number
}

const orderModal = ref(false)
const editingOrder = ref<ProductionOrder | null>(null)
const savingOrder = ref(false)
const orderErrors = ref<Record<string, string>>({})
const orderForm = reactive({ productId: 0, recipeId: 0, quantity: 1, laborCost: 0, notes: '' })
const materialRows = ref<MaterialRow[]>([])

/** Recetas del producto elegido: son las únicas que tiene sentido ofrecer. */
const recipesForProduct = computed(() =>
  recipes.value.filter((r) => r.isActive && r.productId === Number(orderForm.productId)),
)

/** Con receta los materiales los calcula el backend; a mano los define el usuario. */
const usingRecipe = computed(() => Number(orderForm.recipeId) > 0)

const materialOf = (id: number) => products.value.find((p) => p.id === id)

const plannedCost = computed(() => {
  if (usingRecipe.value) {
    const recipe = recipes.value.find((r) => r.id === Number(orderForm.recipeId))
    if (!recipe || recipe.outputQuantity <= 0) return 0
    const factor = Number(orderForm.quantity) / recipe.outputQuantity
    return (recipe.materialsCost + recipe.laborCost) * factor
  }
  const rows = materialRows.value.reduce(
    (sum, row) => sum + row.plannedQuantity * (materialOf(row.productId)?.cost ?? 0),
    0,
  )
  return rows + Number(orderForm.laborCost || 0)
})

// Al cambiar de producto, la receta elegida deja de tener sentido.
watch(
  () => orderForm.productId,
  () => {
    orderForm.recipeId = 0
  },
)

function openCreateOrder() {
  editingOrder.value = null
  orderErrors.value = {}
  Object.assign(orderForm, {
    productId: outputs.value[0]?.id ?? 0,
    recipeId: 0,
    quantity: 1,
    laborCost: 0,
    notes: '',
  })
  materialRows.value = [{ productId: materials.value[0]?.id ?? 0, plannedQuantity: 1 }]
  orderModal.value = true
}

async function openOrder(summary: ProductionOrderSummary) {
  const order = await productionApi.get(summary.id).catch((error) => {
    notifications.error(errorMessage(error))
    return null
  })
  if (!order) return

  editingOrder.value = order
  orderErrors.value = {}
  Object.assign(orderForm, {
    productId: order.productId,
    recipeId: order.recipeId ?? 0,
    quantity: order.quantity,
    laborCost: order.laborCost,
    notes: order.notes ?? '',
  })
  materialRows.value = order.materials.map((m) => ({
    productId: m.productId,
    plannedQuantity: m.plannedQuantity,
  }))
  orderModal.value = true
}

const orderEditable = computed(
  () =>
    !editingOrder.value ||
    editingOrder.value.status === ProductionStatus.Planned ||
    editingOrder.value.status === ProductionStatus.InProgress,
)

const addMaterial = () =>
  materialRows.value.push({ productId: materials.value[0]?.id ?? 0, plannedQuantity: 1 })
const removeMaterial = (index: number) => materialRows.value.splice(index, 1)

async function saveOrder() {
  savingOrder.value = true
  orderErrors.value = {}
  try {
    const payload = {
      productId: Number(orderForm.productId),
      recipeId: Number(orderForm.recipeId) || null,
      quantity: Number(orderForm.quantity),
      laborCost: Number(orderForm.laborCost),
      notes: orderForm.notes || null,
      materials: usingRecipe.value
        ? []
        : materialRows.value.map((row) => ({
            productId: Number(row.productId),
            plannedQuantity: Number(row.plannedQuantity),
          })),
    }

    if (editingOrder.value) {
      await productionApi.update(editingOrder.value.id, payload)
      notifications.success('Orden actualizada.')
    } else {
      await productionApi.create(payload)
      notifications.success('Orden de producción creada.')
    }
    orderModal.value = false
    await list.load()
  } catch (error) {
    orderErrors.value = fieldErrors(error)
    notifications.error(errorMessage(error))
  } finally {
    savingOrder.value = false
  }
}

// ----------------------------------------------- Iniciar, terminar, cancelar ----

const acting = ref(false)
const cancellingOrder = ref<ProductionOrderSummary | null>(null)

async function startOrder(row: ProductionOrderSummary) {
  try {
    await productionApi.start(row.id)
    notifications.success('Orden en proceso.')
    await list.load()
  } catch (error) {
    notifications.error(errorMessage(error))
  }
}

async function cancelOrder() {
  if (!cancellingOrder.value) return
  acting.value = true
  try {
    await productionApi.cancel(cancellingOrder.value.id)
    notifications.success('Orden cancelada.')
    cancellingOrder.value = null
    await list.load()
  } catch (error) {
    notifications.error(errorMessage(error))
  } finally {
    acting.value = false
  }
}

const completeModal = ref(false)
const completing = ref(false)
const completeTarget = ref<ProductionOrder | null>(null)
const completeForm = reactive({ producedQuantity: 0, laborCost: 0 })
const consumption = ref<{ productId: number; name: string; unit: string; planned: number; consumed: number; stock: number }[]>([])

const completeCost = computed(() => {
  const mats = consumption.value.reduce(
    (sum, row) => sum + row.consumed * (materialOf(row.productId)?.cost ?? 0),
    0,
  )
  const total = mats + Number(completeForm.laborCost || 0)
  const qty = Number(completeForm.producedQuantity || 0)
  return { materials: mats, total, unit: qty > 0 ? total / qty : 0 }
})

async function openComplete(summary: ProductionOrderSummary) {
  const order = await productionApi.get(summary.id).catch((error) => {
    notifications.error(errorMessage(error))
    return null
  })
  if (!order) return

  completeTarget.value = order
  Object.assign(completeForm, { producedQuantity: order.quantity, laborCost: order.laborCost })
  consumption.value = order.materials.map((m) => ({
    productId: m.productId,
    name: m.productName,
    unit: m.unitAbbreviation,
    planned: m.plannedQuantity,
    consumed: m.plannedQuantity,
    stock: m.stock,
  }))
  completeModal.value = true
}

async function submitComplete() {
  if (!completeTarget.value) return
  completing.value = true
  try {
    await productionApi.complete(completeTarget.value.id, {
      producedQuantity: Number(completeForm.producedQuantity),
      laborCost: Number(completeForm.laborCost),
      materials: consumption.value.map((row) => ({
        productId: row.productId,
        consumedQuantity: Number(row.consumed),
      })),
    })
    notifications.success('Orden terminada: se consumió el material y entró el producto fabricado.')
    completeModal.value = false
    await Promise.all([list.load(), loadProducts()])
  } catch (error) {
    notifications.error(errorMessage(error))
  } finally {
    completing.value = false
  }
}

// ================================================================= Recetas ====

interface RecipeRow {
  productId: number
  quantity: number
}

const recipeModal = ref(false)
const editingRecipe = ref<Recipe | null>(null)
const savingRecipe = ref(false)
const recipeErrors = ref<Record<string, string>>({})
const recipeForm = reactive({ name: '', productId: 0, outputQuantity: 1, laborCost: 0, notes: '' })
const recipeRows = ref<RecipeRow[]>([])

const recipeCost = computed(() => {
  const mats = recipeRows.value.reduce(
    (sum, row) => sum + row.quantity * (materialOf(row.productId)?.cost ?? 0),
    0,
  )
  const total = mats + Number(recipeForm.laborCost || 0)
  const output = Number(recipeForm.outputQuantity || 0)
  return { materials: mats, total, unit: output > 0 ? total / output : 0 }
})

function openCreateRecipe() {
  editingRecipe.value = null
  recipeErrors.value = {}
  Object.assign(recipeForm, {
    name: '',
    productId: outputs.value[0]?.id ?? 0,
    outputQuantity: 1,
    laborCost: 0,
    notes: '',
  })
  recipeRows.value = [{ productId: materials.value[0]?.id ?? 0, quantity: 1 }]
  recipeModal.value = true
}

function openRecipe(recipe: Recipe) {
  editingRecipe.value = recipe
  recipeErrors.value = {}
  Object.assign(recipeForm, {
    name: recipe.name,
    productId: recipe.productId,
    outputQuantity: recipe.outputQuantity,
    laborCost: recipe.laborCost,
    notes: recipe.notes ?? '',
  })
  recipeRows.value = recipe.items.map((i) => ({ productId: i.productId, quantity: i.quantity }))
  recipeModal.value = true
}

const addRecipeRow = () =>
  recipeRows.value.push({ productId: materials.value[0]?.id ?? 0, quantity: 1 })
const removeRecipeRow = (index: number) => recipeRows.value.splice(index, 1)

async function saveRecipe() {
  savingRecipe.value = true
  recipeErrors.value = {}
  try {
    const payload = {
      name: recipeForm.name,
      productId: Number(recipeForm.productId),
      outputQuantity: Number(recipeForm.outputQuantity),
      laborCost: Number(recipeForm.laborCost),
      notes: recipeForm.notes || null,
      items: recipeRows.value.map((row) => ({
        productId: Number(row.productId),
        quantity: Number(row.quantity),
      })),
    }

    if (editingRecipe.value) {
      await productionApi.updateRecipe(editingRecipe.value.id, payload)
      notifications.success('Receta actualizada.')
    } else {
      await productionApi.createRecipe(payload)
      notifications.success('Receta creada.')
    }
    recipeModal.value = false
    await loadRecipes()
  } catch (error) {
    recipeErrors.value = fieldErrors(error)
    notifications.error(errorMessage(error))
  } finally {
    savingRecipe.value = false
  }
}

async function toggleRecipe(recipe: Recipe) {
  try {
    await productionApi.setRecipeActive(recipe.id, !recipe.isActive)
    notifications.success(recipe.isActive ? 'Receta retirada.' : 'Receta reactivada.')
    await loadRecipes()
  } catch (error) {
    notifications.error(errorMessage(error))
  }
}

// ------------------------------------------------------------------ Carga ----

const loadProducts = () =>
  productsApi.list({ pageSize: 300, active: true }).then((r) => (products.value = r.items))
const loadRecipes = () => productionApi.recipes().then((r) => (recipes.value = r))

onMounted(async () => {
  await Promise.all([list.load(), loadProducts(), loadRecipes()]).catch((error) =>
    notifications.error(errorMessage(error)),
  )
})
</script>

<template>
  <div>
    <PageHeader
      title="Producción"
      description="Al terminar una orden sale el material consumido y entra el producto fabricado, con su costo real."
    >
      <template #actions>
        <BaseButton
          v-if="auth.canWrite('production')"
          variant="primary"
          @click="tab === 'orders' ? openCreateOrder() : openCreateRecipe()"
        >
          {{ tab === 'orders' ? 'Nueva orden' : 'Nueva receta' }}
        </BaseButton>
      </template>
    </PageHeader>

    <div class="tabs" role="tablist">
      <button :class="{ active: tab === 'orders' }" role="tab" type="button" @click="tab = 'orders'">
        Órdenes
      </button>
      <button :class="{ active: tab === 'recipes' }" role="tab" type="button" @click="tab = 'recipes'">
        Recetas <span class="count">{{ recipes.length }}</span>
      </button>
    </div>

    <!-- ============================================================ Órdenes -->
    <template v-if="tab === 'orders'">
      <div class="toolbar">
        <input
          v-model="list.search.value"
          class="control search"
          type="search"
          placeholder="Buscar por número o producto…"
        />
        <select v-model="list.filters.value.status" class="control filter">
          <option :value="null">Todos los estados</option>
          <option :value="ProductionStatus.Planned">Planificada</option>
          <option :value="ProductionStatus.InProgress">En proceso</option>
          <option :value="ProductionStatus.Completed">Terminada</option>
          <option :value="ProductionStatus.Cancelled">Cancelada</option>
        </select>
      </div>

      <DataTable
        :columns="columns"
        :rows="list.items.value"
        :loading="list.loading.value"
        :page="list.page.value"
        :page-size="list.pageSize"
        :total="list.total.value"
        empty-title="Sin órdenes de producción"
        empty-message="Cree una orden para fabricar a partir de una receta o de materiales sueltos."
        @update:page="list.page.value = $event"
      >
        <template #number="{ row }">
          <button class="link" type="button" @click="openOrder(row)">{{ row.number }}</button>
        </template>

        <template #date="{ row }"><span class="num">{{ date(row.date) }}</span></template>

        <template #product="{ row }">{{ row.productName }}</template>

        <template #quantity="{ row }">
          <span class="num">
            {{ number(row.status === ProductionStatus.Completed ? row.producedQuantity : row.quantity) }}
            <small v-if="row.status === ProductionStatus.Completed && row.producedQuantity !== row.quantity" class="muted">
              de {{ number(row.quantity) }}
            </small>
          </span>
        </template>

        <template #status="{ row }">
          <StatusBadge :tone="statusTone(row.status)">{{ row.statusName }}</StatusBadge>
        </template>

        <template #cost="{ row }">
          <span class="num">{{ row.totalCost > 0 ? currency(row.totalCost) : '—' }}</span>
        </template>

        <template #actions="{ row }">
          <div class="row-actions">
            <BaseButton size="sm" @click="openOrder(row)">Ver</BaseButton>
            <template v-if="auth.canWrite('production')">
              <BaseButton
                v-if="row.status === ProductionStatus.Planned"
                size="sm"
                @click="startOrder(row)"
              >
                Iniciar
              </BaseButton>
              <BaseButton
                v-if="row.status === ProductionStatus.Planned || row.status === ProductionStatus.InProgress"
                size="sm"
                variant="primary"
                @click="openComplete(row)"
              >
                Terminar
              </BaseButton>
              <BaseButton
                v-if="row.status === ProductionStatus.Planned || row.status === ProductionStatus.InProgress"
                size="sm"
                variant="ghost"
                @click="cancellingOrder = row"
              >
                Cancelar
              </BaseButton>
            </template>
          </div>
        </template>

        <template #empty-action>
          <BaseButton v-if="auth.canWrite('production')" variant="primary" @click="openCreateOrder">
            Nueva orden
          </BaseButton>
        </template>
      </DataTable>
    </template>

    <!-- ============================================================ Recetas -->
    <template v-else>
      <div v-if="recipes.length" class="recipe-grid">
        <article v-for="recipe in recipes" :key="recipe.id" class="card" :class="{ retired: !recipe.isActive }">
          <header>
            <div>
              <h3>{{ recipe.name }}</h3>
              <p class="muted">{{ recipe.productName }}</p>
            </div>
            <StatusBadge :tone="recipe.isActive ? 'success' : 'neutral'">
              {{ recipe.isActive ? 'Activa' : 'Retirada' }}
            </StatusBadge>
          </header>

          <dl>
            <div>
              <dt>Rinde</dt>
              <dd class="num">{{ number(recipe.outputQuantity) }} {{ recipe.unitAbbreviation }}</dd>
            </div>
            <div><dt>Materiales</dt><dd class="num">{{ currency(recipe.materialsCost) }}</dd></div>
            <div><dt>Mano de obra</dt><dd class="num">{{ currency(recipe.laborCost) }}</dd></div>
            <div class="highlight">
              <dt>Costo unitario</dt>
              <dd class="num">{{ currency(recipe.unitCost) }}</dd>
            </div>
          </dl>

          <ul class="ingredients">
            <li v-for="item in recipe.items" :key="item.id">
              <span>{{ item.productName }}</span>
              <span class="num muted">{{ number(item.quantity) }} {{ item.unitAbbreviation }}</span>
            </li>
          </ul>

          <footer v-if="auth.canWrite('production')">
            <BaseButton size="sm" @click="openRecipe(recipe)">Editar</BaseButton>
            <BaseButton size="sm" variant="ghost" @click="toggleRecipe(recipe)">
              {{ recipe.isActive ? 'Retirar' : 'Reactivar' }}
            </BaseButton>
          </footer>
        </article>
      </div>

      <div v-else class="placeholder">
        <h3>Sin recetas</h3>
        <p class="muted">
          Una receta define cuánto material lleva fabricar cierta cantidad. Con ella, una orden se
          arma sola a partir de lo que se quiere producir.
        </p>
        <BaseButton v-if="auth.canWrite('production')" variant="primary" @click="openCreateRecipe">
          Nueva receta
        </BaseButton>
      </div>
    </template>

    <!-- ============================================ Modal: orden de producción -->
    <BaseModal
      :open="orderModal"
      :title="editingOrder ? `Orden ${editingOrder.number}` : 'Nueva orden de producción'"
      :subtitle="editingOrder && !orderEditable ? 'Orden cerrada: solo lectura.' : 'El material se descuenta al terminar la orden, no ahora.'"
      width="820px"
      @close="orderModal = false"
    >
      <form id="order-form" @submit.prevent="saveOrder">
        <div class="form-grid">
          <BaseField v-slot="{ id, invalid }" label="Producto a fabricar" required :error="orderErrors.productId">
            <select
              :id="id"
              v-model.number="orderForm.productId"
              class="control"
              :class="{ 'is-invalid': invalid }"
              required
              :disabled="!orderEditable"
            >
              <option :value="0" disabled>Seleccione…</option>
              <option v-for="product in outputs" :key="product.id" :value="product.id">
                {{ product.code }} · {{ product.name }}
              </option>
            </select>
          </BaseField>

          <BaseField v-slot="{ id }" label="Cantidad a producir" required :error="orderErrors.quantity">
            <input
              :id="id"
              v-model.number="orderForm.quantity"
              class="control"
              type="number"
              min="0.0001"
              step="any"
              required
              :disabled="!orderEditable"
            />
          </BaseField>

          <BaseField
            v-slot="{ id }"
            label="Receta"
            :hint="recipesForProduct.length ? 'Con receta, los materiales se calculan solos.' : 'Este producto no tiene recetas: cargue los materiales a mano.'"
          >
            <select
              :id="id"
              v-model.number="orderForm.recipeId"
              class="control"
              :disabled="!orderEditable || !recipesForProduct.length"
            >
              <option :value="0">Sin receta (materiales a mano)</option>
              <option v-for="recipe in recipesForProduct" :key="recipe.id" :value="recipe.id">
                {{ recipe.name }} (rinde {{ number(recipe.outputQuantity) }})
              </option>
            </select>
          </BaseField>

          <BaseField v-slot="{ id }" label="Mano de obra" hint="Costo total de la orden, no por unidad.">
            <input
              :id="id"
              v-model.number="orderForm.laborCost"
              class="control"
              type="number"
              min="0"
              step="0.01"
              :disabled="!orderEditable"
            />
          </BaseField>

          <BaseField v-slot="{ id }" label="Observaciones" span2>
            <textarea :id="id" v-model="orderForm.notes" class="control" maxlength="500" :disabled="!orderEditable" />
          </BaseField>
        </div>

        <template v-if="!usingRecipe">
          <div class="items">
            <div class="items-header">
              <span>Material</span>
              <span class="text-right">Cantidad</span>
              <span class="text-right">Costo</span>
              <span></span>
            </div>

            <div v-for="(row, index) in materialRows" :key="index" class="item-row">
              <select v-model.number="row.productId" class="control" :disabled="!orderEditable">
                <option v-for="product in materials" :key="product.id" :value="product.id">
                  {{ product.code }} · {{ product.name }} (hay {{ number(product.stock) }})
                </option>
              </select>
              <input v-model.number="row.plannedQuantity" class="control text-right" type="number" min="0.0001" step="any" :disabled="!orderEditable" />
              <span class="num text-right">
                {{ currency(row.plannedQuantity * (materialOf(row.productId)?.cost ?? 0)) }}
              </span>
              <BaseButton v-if="orderEditable" size="sm" variant="ghost" type="button" :disabled="materialRows.length <= 1" @click="removeMaterial(index)">
                ✕
              </BaseButton>
              <span v-else></span>
            </div>

            <BaseButton v-if="orderEditable" size="sm" type="button" @click="addMaterial">
              Agregar material
            </BaseButton>
          </div>
        </template>

        <p v-else class="hint-box">
          Los materiales salen de la receta, escalados a {{ number(orderForm.quantity) }} unidades.
        </p>

        <div class="totals">
          <div><span class="muted">Costo estimado</span><span class="num">{{ currency(plannedCost) }}</span></div>
          <div v-if="Number(orderForm.quantity) > 0">
            <span class="muted">Por unidad</span>
            <span class="num">{{ currency(plannedCost / Number(orderForm.quantity)) }}</span>
          </div>
        </div>

        <!-- Cierre real de la orden, cuando ya se terminó -->
        <dl v-if="editingOrder && editingOrder.status === ProductionStatus.Completed" class="closed">
          <div><dt>Producido</dt><dd class="num">{{ number(editingOrder.producedQuantity) }}</dd></div>
          <div><dt>Materiales</dt><dd class="num">{{ currency(editingOrder.materialsCost) }}</dd></div>
          <div><dt>Mano de obra</dt><dd class="num">{{ currency(editingOrder.laborCost) }}</dd></div>
          <div><dt>Costo total</dt><dd class="num">{{ currency(editingOrder.totalCost) }}</dd></div>
          <div class="highlight"><dt>Costo unitario</dt><dd class="num">{{ currency(editingOrder.unitCost) }}</dd></div>
          <div><dt>Terminada</dt><dd>{{ dateTime(editingOrder.completedAt) }}</dd></div>
        </dl>
      </form>

      <template #footer>
        <BaseButton variant="ghost" @click="orderModal = false">Cerrar</BaseButton>
        <BaseButton
          v-if="auth.canWrite('production') && orderEditable"
          type="submit"
          form="order-form"
          variant="primary"
          :loading="savingOrder"
        >
          {{ editingOrder ? 'Guardar cambios' : 'Crear orden' }}
        </BaseButton>
      </template>
    </BaseModal>

    <!-- ==================================================== Modal: terminar -->
    <BaseModal
      :open="completeModal"
      title="Terminar orden"
      :subtitle="completeTarget ? `${completeTarget.number} · ${completeTarget.productName}` : undefined"
      width="720px"
      @close="completeModal = false"
    >
      <form id="complete-form" @submit.prevent="submitComplete">
        <div class="form-grid">
          <BaseField v-slot="{ id }" label="Cantidad producida" required hint="Lo que realmente salió del taller.">
            <input
              :id="id"
              v-model.number="completeForm.producedQuantity"
              class="control"
              type="number"
              min="0.0001"
              step="any"
              required
            />
          </BaseField>

          <BaseField v-slot="{ id }" label="Mano de obra">
            <input :id="id" v-model.number="completeForm.laborCost" class="control" type="number" min="0" step="0.01" />
          </BaseField>
        </div>

        <h4>Material consumido</h4>
        <div class="items">
          <div class="items-header consume">
            <span>Material</span>
            <span class="text-right">Planificado</span>
            <span class="text-right">Consumido</span>
            <span class="text-right">Existencia</span>
          </div>
          <div v-for="row in consumption" :key="row.productId" class="item-row consume">
            <span>{{ row.name }} <small class="muted">({{ row.unit }})</small></span>
            <span class="num text-right muted">{{ number(row.planned) }}</span>
            <input v-model.number="row.consumed" class="control text-right" type="number" min="0" step="0.01" />
            <span class="num text-right" :class="{ short: row.stock < row.consumed }">
              {{ number(row.stock) }}
            </span>
          </div>
        </div>

        <div class="totals">
          <div><span class="muted">Materiales</span><span class="num">{{ currency(completeCost.materials) }}</span></div>
          <div><span class="muted">Costo total</span><span class="num">{{ currency(completeCost.total) }}</span></div>
          <div class="grand"><span>Costo unitario</span><span class="num">{{ currency(completeCost.unit) }}</span></div>
        </div>

        <p class="hint-box">
          Al confirmar, el material sale del inventario y entra el producto fabricado con este costo.
        </p>
      </form>

      <template #footer>
        <BaseButton variant="ghost" @click="completeModal = false">Cancelar</BaseButton>
        <BaseButton type="submit" form="complete-form" variant="primary" :loading="completing">
          Terminar orden
        </BaseButton>
      </template>
    </BaseModal>

    <!-- ===================================================== Modal: receta -->
    <BaseModal
      :open="recipeModal"
      :title="editingRecipe ? `Receta ${editingRecipe.name}` : 'Nueva receta'"
      subtitle="Define cuánto material lleva producir una cantidad determinada."
      width="760px"
      @close="recipeModal = false"
    >
      <form id="recipe-form" @submit.prevent="saveRecipe">
        <div class="form-grid">
          <BaseField v-slot="{ id, invalid }" label="Nombre" required span2 :error="recipeErrors.name">
            <input
              :id="id"
              v-model="recipeForm.name"
              class="control"
              :class="{ 'is-invalid': invalid }"
              maxlength="120"
              required
            />
          </BaseField>

          <BaseField v-slot="{ id }" label="Producto que resulta" required :error="recipeErrors.productId">
            <select :id="id" v-model.number="recipeForm.productId" class="control" required>
              <option :value="0" disabled>Seleccione…</option>
              <option v-for="product in outputs" :key="product.id" :value="product.id">
                {{ product.code }} · {{ product.name }}
              </option>
            </select>
          </BaseField>

          <BaseField v-slot="{ id }" label="Rendimiento" required hint="Cuánto producto rinden estas cantidades.">
            <input
              :id="id"
              v-model.number="recipeForm.outputQuantity"
              class="control"
              type="number"
              min="0.0001"
              step="any"
              required
            />
          </BaseField>

          <BaseField v-slot="{ id }" label="Mano de obra" hint="Por cada rendimiento declarado.">
            <input :id="id" v-model.number="recipeForm.laborCost" class="control" type="number" min="0" step="0.01" />
          </BaseField>

          <BaseField v-slot="{ id }" label="Notas">
            <input :id="id" v-model="recipeForm.notes" class="control" maxlength="500" />
          </BaseField>
        </div>

        <div class="items">
          <div class="items-header">
            <span>Material</span>
            <span class="text-right">Cantidad</span>
            <span class="text-right">Costo</span>
            <span></span>
          </div>

          <div v-for="(row, index) in recipeRows" :key="index" class="item-row">
            <select v-model.number="row.productId" class="control">
              <option v-for="product in materials" :key="product.id" :value="product.id">
                {{ product.code }} · {{ product.name }}
              </option>
            </select>
            <input v-model.number="row.quantity" class="control text-right" type="number" min="0.0001" step="any" />
            <span class="num text-right">
              {{ currency(row.quantity * (materialOf(row.productId)?.cost ?? 0)) }}
            </span>
            <BaseButton size="sm" variant="ghost" type="button" :disabled="recipeRows.length <= 1" @click="removeRecipeRow(index)">
              ✕
            </BaseButton>
          </div>

          <BaseButton size="sm" type="button" @click="addRecipeRow">Agregar material</BaseButton>
        </div>

        <div class="totals">
          <div><span class="muted">Materiales</span><span class="num">{{ currency(recipeCost.materials) }}</span></div>
          <div><span class="muted">Total por lote</span><span class="num">{{ currency(recipeCost.total) }}</span></div>
          <div class="grand"><span>Costo unitario</span><span class="num">{{ currency(recipeCost.unit) }}</span></div>
        </div>
      </form>

      <template #footer>
        <BaseButton variant="ghost" @click="recipeModal = false">Cerrar</BaseButton>
        <BaseButton type="submit" form="recipe-form" variant="primary" :loading="savingRecipe">
          {{ editingRecipe ? 'Guardar cambios' : 'Crear receta' }}
        </BaseButton>
      </template>
    </BaseModal>

    <ConfirmDialog
      :open="!!cancellingOrder"
      title="Cancelar orden"
      :message="`${cancellingOrder?.number} quedará cancelada. No habrá consumo de material ni producto fabricado.`"
      confirm-label="Cancelar orden"
      variant="danger"
      :loading="acting"
      @cancel="cancellingOrder = null"
      @confirm="cancelOrder"
    />
  </div>
</template>

<style scoped>
.tabs {
  display: flex;
  gap: 4px;
  border-bottom: 1px solid var(--ink-200);
  margin-bottom: 16px;
}

.tabs button {
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  padding: 9px 14px;
  font: inherit;
  font-weight: 600;
  font-size: 13.5px;
  color: var(--ink-500);
  cursor: pointer;
  margin-bottom: -1px;
}

.tabs button.active {
  color: var(--brand-600);
  border-bottom-color: var(--brand-600);
}

.count {
  display: inline-block;
  background: var(--ink-100);
  border-radius: 10px;
  padding: 1px 7px;
  font-size: 11px;
  margin-left: 4px;
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
  width: 180px;
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

.row-actions {
  display: flex;
  gap: 6px;
  justify-content: flex-end;
  flex-wrap: wrap;
}

/* --- Recetas --- */
.recipe-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
  gap: 14px;
}

.card {
  background: var(--surface);
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  padding: 16px;
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.card.retired {
  opacity: 0.65;
}

.card header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  gap: 10px;
}

.card h3 {
  margin: 0;
  font-size: 15px;
}

.card header p {
  margin: 2px 0 0;
  font-size: 12.5px;
}

.card dl {
  margin: 0;
  display: grid;
  gap: 5px;
}

.card dl > div {
  display: flex;
  justify-content: space-between;
  font-size: 13px;
}

.card dt {
  color: var(--ink-500);
}

.card dd {
  margin: 0;
  font-weight: 600;
}

.highlight {
  border-top: 1px solid var(--ink-200);
  padding-top: 6px;
  margin-top: 2px;
}

.ingredients {
  list-style: none;
  margin: 0;
  padding: 0;
  border-top: 1px solid var(--ink-100);
  padding-top: 9px;
  display: grid;
  gap: 4px;
}

.ingredients li {
  display: flex;
  justify-content: space-between;
  font-size: 12.5px;
}

.card footer {
  display: flex;
  gap: 6px;
  margin-top: auto;
}

.placeholder {
  text-align: center;
  padding: 50px 20px;
  border: 1px dashed var(--ink-200);
  border-radius: var(--radius);
}

.placeholder h3 {
  margin: 0 0 6px;
  font-size: 15px;
}

.placeholder p {
  max-width: 440px;
  margin: 0 auto 16px;
  font-size: 13.5px;
}

/* --- Líneas de materiales --- */
.items {
  margin-top: 18px;
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  padding: 12px;
}

.items-header,
.item-row {
  display: grid;
  grid-template-columns: 2fr 110px 120px 36px;
  gap: 8px;
  align-items: center;
}

.items-header.consume,
.item-row.consume {
  grid-template-columns: 2fr 110px 120px 110px;
}

.items-header {
  font-size: 11.5px;
  font-weight: 650;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--ink-500);
  padding: 0 2px 8px;
}

.item-row {
  padding: 4px 0;
}

.items > .btn {
  margin-top: 8px;
}

.short {
  color: var(--danger-600);
  font-weight: 650;
}

h4 {
  font-size: 12.5px;
  font-weight: 650;
  color: var(--ink-500);
  margin: 18px 0 0;
}

.hint-box {
  margin-top: 14px;
  background: var(--ink-50, #f6f7f9);
  border-radius: var(--radius-sm);
  padding: 9px 12px;
  font-size: 13px;
  color: var(--ink-500);
}

.totals {
  margin-top: 16px;
  margin-left: auto;
  width: 280px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.totals > div {
  display: flex;
  justify-content: space-between;
  font-size: 13.5px;
}

.totals .grand {
  font-weight: 700;
  font-size: 15px;
  border-top: 1px solid var(--ink-200);
  padding-top: 8px;
}

.closed {
  margin: 18px 0 0;
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  padding: 14px;
  display: grid;
  gap: 6px;
}

.closed > div {
  display: flex;
  justify-content: space-between;
  font-size: 13.5px;
}

.closed dt {
  color: var(--ink-500);
}

.closed dd {
  margin: 0;
  font-weight: 600;
}
</style>

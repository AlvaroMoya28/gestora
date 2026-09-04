<script setup lang="ts">
import { onMounted, ref } from 'vue'
import AppIcon from '@/components/ui/AppIcon.vue'
import PageHeader from '@/components/ui/PageHeader.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import { useFormat } from '@/composables/useFormat'
import { dashboardApi } from '@/services/api'
import { errorMessage } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import type { DashboardData } from '@/types'

const auth = useAuthStore()
const notifications = useNotificationStore()
const { metric, number, dateTime } = useFormat()

const data = ref<DashboardData | null>(null)
const loading = ref(true)

onMounted(async () => {
  try {
    data.value = await dashboardApi.get()
  } catch (error) {
    notifications.error(errorMessage(error, 'No se pudo cargar el panel.'))
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <PageHeader
      :title="`Hola, ${auth.user?.firstName ?? ''}`"
      description="Resumen del estado actual de la empresa."
    />

    <section v-if="loading" class="metrics">
      <div v-for="n in 6" :key="n" class="metric-card loading-card" />
    </section>

    <template v-else-if="data">
      <section class="metrics">
        <article v-for="item in data.metrics" :key="item.key" class="metric-card">
          <p class="label">{{ item.label }}</p>
          <p class="value">{{ metric(item.value, item.format) }}</p>
          <p v-if="item.hint" class="hint muted">{{ item.hint }}</p>
        </article>
      </section>

      <section v-if="data.alerts.length" class="alerts">
        <article v-for="alert in data.alerts" :key="alert.title" class="alert" :class="alert.level">
          <AppIcon name="alert" :size="18" />
          <div>
            <strong>{{ alert.title }}</strong>
            <p class="muted">{{ alert.detail }}</p>
          </div>
          <RouterLink
            v-if="alert.moduleKey && auth.canRead(alert.moduleKey)"
            :to="{ name: alert.moduleKey }"
          >
            Ver
          </RouterLink>
        </article>
      </section>

      <section class="grid">
        <article class="panel">
          <header>
            <h3>Productos por reponer</h3>
            <RouterLink v-if="auth.canRead('inventory')" :to="{ name: 'inventory' }">Inventario</RouterLink>
          </header>

          <ul v-if="data.lowStock.length" class="list">
            <li v-for="item in data.lowStock" :key="item.productId">
              <div>
                <strong>{{ item.name }}</strong>
                <small class="muted">{{ item.code }}</small>
              </div>
              <div class="row">
                <span class="num">{{ number(item.stock) }} / {{ number(item.minStock) }} {{ item.unit }}</span>
                <StatusBadge :tone="item.stock <= 0 ? 'danger' : 'warning'">
                  {{ item.stock <= 0 ? 'Agotado' : 'Bajo' }}
                </StatusBadge>
              </div>
            </li>
          </ul>

          <p v-else class="empty muted">Todas las existencias están sobre su nivel mínimo.</p>
        </article>

        <article class="panel">
          <header>
            <h3>Actividad reciente</h3>
            <RouterLink v-if="auth.canRead('audit')" :to="{ name: 'audit' }">Auditoría</RouterLink>
          </header>

          <ul v-if="data.recentActivity.length" class="list">
            <li v-for="(item, index) in data.recentActivity" :key="index" class="activity">
              <div>
                <strong>{{ item.action }}</strong>
                <p class="muted">{{ item.description }}</p>
              </div>
              <small class="muted">{{ dateTime(item.occurredAt) }}</small>
            </li>
          </ul>

          <p v-else class="empty muted">Todavía no hay movimientos registrados.</p>
        </article>
      </section>
    </template>
  </div>
</template>

<style scoped>
.metrics {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(212px, 1fr));
  gap: 14px;
  margin-bottom: 20px;
}

.metric-card {
  background: var(--surface);
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  padding: 15px 17px;
  box-shadow: var(--shadow-sm);
}

.loading-card {
  height: 92px;
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

.label {
  font-size: 12.5px;
  color: var(--ink-500);
  font-weight: 500;
}

.value {
  font-size: 23px;
  font-weight: 650;
  letter-spacing: -0.02em;
  margin-top: 5px;
  font-variant-numeric: tabular-nums;
}

.hint {
  font-size: 12px;
  margin-top: 2px;
}

.alerts {
  display: flex;
  flex-direction: column;
  gap: 9px;
  margin-bottom: 20px;
}

.alert {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 15px;
  border-radius: var(--radius);
  border: 1px solid var(--ink-200);
  background: var(--surface);
}

.alert p {
  font-size: 13px;
}

.alert strong {
  font-size: 13.5px;
}

.alert > a {
  margin-left: auto;
  font-size: 13px;
  font-weight: 600;
}

.alert.danger :deep(svg) {
  color: var(--danger-500);
}

.alert.warning :deep(svg) {
  color: var(--accent-500);
}

.alert.info :deep(svg) {
  color: var(--info-600);
}

.grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(330px, 1fr));
  gap: 16px;
}

.panel {
  background: var(--surface);
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
}

.panel header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 13px 17px;
  border-bottom: 1px solid var(--ink-200);
}

.panel h3 {
  font-size: 14px;
  font-weight: 650;
}

.panel header a {
  font-size: 12.5px;
  font-weight: 600;
}

.list {
  list-style: none;
  margin: 0;
  padding: 0;
}

.list li {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 14px;
  padding: 11px 17px;
  border-bottom: 1px solid var(--ink-100);
}

.list li:last-child {
  border-bottom: none;
}

.list strong {
  font-size: 13.5px;
  font-weight: 600;
  display: block;
}

.list small,
.list p {
  font-size: 12.5px;
}

.activity small {
  white-space: nowrap;
  font-size: 12px;
}

.empty {
  padding: 30px 17px;
  text-align: center;
  font-size: 13px;
}
</style>

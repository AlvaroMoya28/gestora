<script setup lang="ts">
import { onMounted, ref } from 'vue'
import PageHeader from '@/components/ui/PageHeader.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import { useFormat } from '@/composables/useFormat'
import { platformApi } from '@/services/api'
import { errorMessage } from '@/services/http'
import { useNotificationStore } from '@/stores/notifications'
import type { PlatformOverview } from '@/types'

const notifications = useNotificationStore()
const { metric, date } = useFormat()

const data = ref<PlatformOverview | null>(null)
const loading = ref(true)

onMounted(async () => {
  try {
    data.value = await platformApi.overview()
  } catch (error) {
    notifications.error(errorMessage(error, 'No se pudo cargar el resumen.'))
  } finally {
    loading.value = false
  }
})

/** Cuanto más cerca del vencimiento, más urgente el cobro. */
const expiryTone = (days: number) => (days < 0 ? 'danger' : days <= 7 ? 'warning' : 'neutral')

const expiryLabel = (days: number) =>
  days < 0 ? `Vencida hace ${Math.abs(days)} d` : days === 0 ? 'Vence hoy' : `${days} d`
</script>

<template>
  <div>
    <PageHeader
      title="Resumen de Gestora"
      description="Estado del negocio: empresas suscritas, ingreso recurrente y próximos cobros."
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

      <section class="grid">
        <article class="panel">
          <header>
            <h3>Próximos vencimientos</h3>
            <RouterLink :to="{ name: 'platform_billing' }">Suscripciones</RouterLink>
          </header>

          <ul v-if="data.expiringSoon.length" class="list">
            <li v-for="item in data.expiringSoon" :key="item.id">
              <div>
                <strong>{{ item.companyName }}</strong>
                <small class="muted block">{{ item.planName }} · {{ date(item.endDate) }}</small>
              </div>
              <StatusBadge :tone="expiryTone(item.daysToExpiry)">
                {{ expiryLabel(item.daysToExpiry) }}
              </StatusBadge>
            </li>
          </ul>

          <p v-else class="empty muted">Ninguna suscripción vence en los próximos 30 días.</p>
        </article>

        <article class="panel">
          <header>
            <h3>Últimas empresas registradas</h3>
            <RouterLink :to="{ name: 'platform_companies' }">Empresas</RouterLink>
          </header>

          <ul v-if="data.recentCompanies.length" class="list">
            <li v-for="company in data.recentCompanies" :key="company.id">
              <div>
                <strong>{{ company.name }}</strong>
                <small class="muted block">{{ company.accountEmail }}</small>
              </div>
              <small class="muted">{{ date(company.createdAt) }}</small>
            </li>
          </ul>

          <p v-else class="empty muted">Todavía no hay empresas registradas.</p>
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

.block {
  display: block;
  font-size: 12.5px;
}

.empty {
  padding: 30px 17px;
  text-align: center;
  font-size: 13px;
}
</style>

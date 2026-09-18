<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import DataTable from '@/components/ui/DataTable.vue'
import PageHeader from '@/components/ui/PageHeader.vue'
import type { Column } from '@/components/ui/table'
import { useFormat } from '@/composables/useFormat'
import { reportsApi } from '@/services/api'
import { errorMessage } from '@/services/http'
import { useNotificationStore } from '@/stores/notifications'
import type { Report, ReportDefinition } from '@/types'

/**
 * Una sola pantalla para todos los reportes. El backend devuelve siempre la misma
 * estructura —cifras, serie y tabla, con el formato de cada columna— así que agregar
 * un reporte no obliga a escribir una vista nueva.
 */
const notifications = useNotificationStore()
const { currency, number, integer, date, metric: formatMetric } = useFormat()

const definitions = ref<ReportDefinition[]>([])
const selected = ref<string>('')
const report = ref<Report | null>(null)
const loading = ref(false)
const downloading = ref(false)

const today = new Date()
const firstOfMonth = new Date(today.getFullYear(), today.getMonth(), 1)
const iso = (value: Date) => value.toISOString().slice(0, 10)

const period = reactive({ from: iso(firstOfMonth), to: iso(today) })

/** Los reportes agrupados como los ve el usuario: comercial, operación, finanzas. */
const groups = computed(() => {
  const map = new Map<string, ReportDefinition[]>()
  for (const definition of definitions.value) {
    const list = map.get(definition.group) ?? []
    list.push(definition)
    map.set(definition.group, list)
  }
  return [...map.entries()].map(([name, items]) => ({ name, items }))
})

const columns = computed<Column[]>(
  () => report.value?.columns.map((c) => ({ key: c.key, label: c.label, align: c.align })) ?? [],
)

/**
 * Las filas de un reporte son agregados sin identidad propia: no tienen `id`. Se les
 * agrega uno por posición para que la tabla pueda distinguirlas al redibujar.
 */
const rows = computed(() => report.value?.rows.map((row, index) => ({ ...row, _row: index })) ?? [])

/** Cada celda se formatea según lo que el backend declaró para su columna. */
function cell(row: Record<string, unknown>, key: string, format: string) {
  const value = row[key]
  if (value === null || value === undefined || value === '') return '—'

  switch (format) {
    case 'money':
      return currency(Number(value))
    case 'decimal':
      return number(Number(value))
    case 'integer':
      return integer(Number(value))
    case 'percent':
      return `${number(Number(value), 1)} %`
    case 'date':
      return date(String(value))
    default:
      return String(value)
  }
}

/** Escala de las barras: proporción respecto al valor absoluto mayor de la serie. */
const seriesScale = computed(() => {
  const values = report.value?.series.map((p) => Math.abs(p.value)) ?? []
  return values.length ? Math.max(...values) : 0
})

const barWidth = (value: number) =>
  seriesScale.value > 0 ? `${Math.max((Math.abs(value) / seriesScale.value) * 100, 1.5)}%` : '0%'

async function load(key: string) {
  selected.value = key
  loading.value = true
  try {
    const definition = definitions.value.find((d) => d.key === key)
    report.value = await reportsApi.get(
      key,
      definition?.needsPeriod ? { from: period.from, to: period.to } : undefined,
    )
  } catch (error) {
    notifications.error(errorMessage(error))
    report.value = null
  } finally {
    loading.value = false
  }
}

const reload = () => (selected.value ? load(selected.value) : undefined)

/**
 * La descarga pasa por axios porque la API exige el token: con un enlace directo el
 * navegador pediría el archivo sin la cabecera de autorización.
 */
async function download() {
  if (!report.value) return
  downloading.value = true
  try {
    const definition = definitions.value.find((d) => d.key === selected.value)
    const blob = await reportsApi.csv(
      selected.value,
      definition?.needsPeriod ? { from: period.from, to: period.to } : undefined,
    )

    const url = URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url
    link.download = `${selected.value}-${iso(new Date())}.csv`
    link.click()
    URL.revokeObjectURL(url)
  } catch (error) {
    notifications.error(errorMessage(error))
  } finally {
    downloading.value = false
  }
}

onMounted(async () => {
  try {
    definitions.value = await reportsApi.available()
    if (definitions.value.length) await load(definitions.value[0].key)
  } catch (error) {
    notifications.error(errorMessage(error))
  }
})
</script>

<template>
  <div class="layout">
    <aside class="picker">
      <h2>Reportes</h2>
      <div v-for="group in groups" :key="group.name" class="group">
        <h3>{{ group.name }}</h3>
        <button
          v-for="definition in group.items"
          :key="definition.key"
          type="button"
          class="entry"
          :class="{ active: selected === definition.key }"
          @click="load(definition.key)"
        >
          <strong>{{ definition.name }}</strong>
          <small>{{ definition.description }}</small>
        </button>
      </div>
    </aside>

    <section class="content">
      <PageHeader
        :title="report?.title ?? 'Reportes'"
        :description="report?.description ?? 'Elija un reporte de la lista.'"
      >
        <template #actions>
          <BaseButton v-if="report" :loading="downloading" @click="download">
            Descargar CSV
          </BaseButton>
        </template>
      </PageHeader>

      <div v-if="report?.needsPeriod" class="period">
        <label>
          <span class="muted">Desde</span>
          <input v-model="period.from" class="control" type="date" @change="reload" />
        </label>
        <label>
          <span class="muted">Hasta</span>
          <input v-model="period.to" class="control" type="date" @change="reload" />
        </label>
      </div>

      <template v-if="report">
        <div class="metrics">
          <article v-for="item in report.metrics" :key="item.key" class="metric">
            <span class="label">{{ item.label }}</span>
            <strong class="num">{{ formatMetric(item.value, item.format) }}</strong>
            <small v-if="item.hint" class="muted">{{ item.hint }}</small>
          </article>
        </div>

        <!-- Serie: una sola medida por etiqueta, así que no hace falta leyenda;
             el título dice qué se está midiendo. -->
        <figure v-if="report.series.length" class="chart">
          <figcaption>{{ report.seriesLabel }}</figcaption>
          <div class="bars">
            <div v-for="point in report.series" :key="point.label" class="bar-row">
              <span class="bar-label">{{ point.label }}</span>
              <div class="track">
                <div
                  class="bar"
                  :class="{ negative: point.value < 0 }"
                  :style="{ width: barWidth(point.value) }"
                  :title="`${point.label}: ${currency(point.value)}`"
                />
              </div>
              <span class="num bar-value">{{ currency(point.value) }}</span>
            </div>
          </div>
        </figure>

        <DataTable
          :columns="columns"
          :rows="rows"
          row-key="_row"
          :loading="loading"
          :page="1"
          :page-size="rows.length || 1"
          :total="rows.length"
          empty-title="Sin datos"
          empty-message="No hay información para el período seleccionado."
        >
          <template v-for="column in report.columns" #[column.key]="{ row }" :key="column.key">
            <span :class="{ num: column.format !== 'text' && column.format !== 'date' }">
              {{ cell(row, column.key, column.format) }}
            </span>
          </template>
        </DataTable>
      </template>

      <p v-else-if="loading" class="muted state">Cargando reporte…</p>
      <p v-else class="muted state">Seleccione un reporte para verlo.</p>
    </section>
  </div>
</template>

<style scoped>
.layout {
  display: grid;
  grid-template-columns: 260px 1fr;
  gap: 22px;
  align-items: start;
}

@media (max-width: 900px) {
  .layout {
    grid-template-columns: 1fr;
  }
}

.picker {
  background: var(--surface);
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  padding: 14px;
  position: sticky;
  top: 12px;
}

.picker h2 {
  font-size: 13px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  color: var(--ink-500);
  margin: 0 0 10px;
}

.group {
  margin-bottom: 14px;
}

.group h3 {
  font-size: 11.5px;
  font-weight: 650;
  color: var(--ink-400, var(--ink-500));
  margin: 0 0 5px;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.entry {
  display: block;
  width: 100%;
  text-align: left;
  background: none;
  border: none;
  border-radius: var(--radius-sm);
  padding: 8px 10px;
  cursor: pointer;
  font: inherit;
  margin-bottom: 2px;
}

.entry:hover {
  background: var(--ink-100);
}

.entry.active {
  background: var(--brand-100, #eef2ff);
}

.entry.active strong {
  color: var(--brand-600);
}

.entry strong {
  display: block;
  font-size: 13px;
  font-weight: 600;
}

.entry small {
  display: block;
  font-size: 11.5px;
  color: var(--ink-500);
  line-height: 1.35;
  margin-top: 1px;
}

.period {
  display: flex;
  gap: 14px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.period label {
  display: flex;
  flex-direction: column;
  gap: 3px;
  font-size: 12px;
}

.period .control {
  width: 165px;
}

.metrics {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
  gap: 12px;
  margin-bottom: 18px;
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

/* --- Serie --- */
.chart {
  margin: 0 0 18px;
  background: var(--surface);
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  padding: 16px;
}

.chart figcaption {
  font-size: 12.5px;
  font-weight: 650;
  color: var(--ink-500);
  margin-bottom: 12px;
}

.bars {
  display: grid;
  gap: 8px;
}

.bar-row {
  display: grid;
  grid-template-columns: 130px 1fr 120px;
  gap: 10px;
  align-items: center;
}

.bar-label {
  font-size: 12.5px;
  color: var(--ink-500);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.track {
  background: var(--ink-100);
  border-radius: 4px;
  height: 14px;
  overflow: hidden;
}

.bar {
  height: 100%;
  background: var(--brand-500, var(--brand-600));
  border-radius: 0 4px 4px 0;
  transition: width 0.25s ease;
}

.bar.negative {
  background: var(--danger-500, var(--danger-600));
}

.bar-value {
  font-size: 12.5px;
  text-align: right;
}

@media (max-width: 620px) {
  .bar-row {
    grid-template-columns: 100px 1fr;
  }

  .bar-value {
    display: none;
  }
}

.state {
  text-align: center;
  padding: 40px;
  font-size: 13.5px;
}
</style>

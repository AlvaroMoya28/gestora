<script setup lang="ts">
import { onMounted, reactive, ref } from 'vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import BaseModal from '@/components/ui/BaseModal.vue'
import DataTable from '@/components/ui/DataTable.vue'
import PageHeader from '@/components/ui/PageHeader.vue'
import StatusBadge from '@/components/ui/StatusBadge.vue'
import type { Column } from '@/components/ui/table'
import { useFormat } from '@/composables/useFormat'
import { useResourceList } from '@/composables/useResourceList'
import { productsApi, receivedDocumentsApi } from '@/services/api'
import { errorMessage } from '@/services/http'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'
import { ReceivedStatus, type Product, type ReceivedDocument } from '@/types'

const auth = useAuthStore()
const notifications = useNotificationStore()
const { currency, date, dateTime } = useFormat()

const list = useResourceList<ReceivedDocument>(receivedDocumentsApi.list, {
  initialFilters: { status: ReceivedStatus.Pending },
})

const columns: Column[] = [
  { key: 'issuer', label: 'Emisor' },
  { key: 'consecutive', label: 'Comprobante', width: '210px' },
  { key: 'issueDate', label: 'Emisión', width: '110px' },
  { key: 'total', label: 'Total', align: 'right', width: '130px' },
  { key: 'status', label: 'Estado', width: '120px' },
  { key: 'actions', label: '', align: 'right', width: '110px' },
]

const tone = (doc: ReceivedDocument) =>
  doc.status === ReceivedStatus.Imported ? 'success' : doc.status === ReceivedStatus.Discarded ? 'neutral' : 'warning'

// ------------------------------------------------- Dirección de recepción ----

const inbound = ref<{ address: string | null; configured: boolean } | null>(null)

async function copyAddress() {
  if (!inbound.value?.address) return
  await navigator.clipboard.writeText(inbound.value.address).catch(() => undefined)
  notifications.success('Dirección copiada.')
}

// -------------------------------------------------------------- Subida XML ----

const fileInput = ref<HTMLInputElement | null>(null)
const uploading = ref(false)

async function onFiles(event: Event) {
  const input = event.target as HTMLInputElement
  const files = Array.from(input.files ?? [])
  input.value = ''
  if (!files.length) return

  uploading.value = true
  try {
    const results = await receivedDocumentsApi.upload(files)
    const added = results.filter((r) => r.ok && !r.duplicate).length
    const repeated = results.filter((r) => r.duplicate).length
    const failed = results.filter((r) => !r.ok)

    if (added) notifications.success(`${added} comprobante(s) recibido(s).`)
    if (repeated) notifications.info(`${repeated} ya estaba(n) en la bandeja.`)
    failed.forEach((r) => notifications.error(`${r.fileName}: ${r.message}`))
    list.filters.value.status = null
    await list.load()
  } catch (error) {
    notifications.error(errorMessage(error))
  } finally {
    uploading.value = false
  }
}

// ----------------------------------------------------------------- Detalle ----

const detailOpen = ref(false)
const detail = ref<ReceivedDocument | null>(null)
const products = ref<Product[]>([])
const choice = reactive<Record<number, number | null>>({})
const confirmNow = ref(false)
const working = ref(false)

async function openDetail(row: ReceivedDocument) {
  try {
    detail.value = await receivedDocumentsApi.get(row.id)
  } catch (error) {
    notifications.error(errorMessage(error))
    return
  }
  Object.keys(choice).forEach((k) => delete choice[Number(k)])
  detail.value.lines.forEach((line) => (choice[line.number] = line.suggestedProductId))
  confirmNow.value = false
  detailOpen.value = true
}

async function importDocument() {
  if (!detail.value) return
  working.value = true
  try {
    const result = await receivedDocumentsApi.importDocument(detail.value.id, {
      lines: detail.value.lines.map((l) => ({ number: l.number, productId: choice[l.number] ?? null })),
      confirm: confirmNow.value,
    })
    notifications.success(
      confirmNow.value
        ? `Compra ${result.purchaseNumber} confirmada: inventario y cuenta por pagar actualizados.`
        : `Compra ${result.purchaseNumber} creada en borrador. Revísela en Compras.`,
    )
    detailOpen.value = false
    await list.load()
  } catch (error) {
    notifications.error(errorMessage(error))
  } finally {
    working.value = false
  }
}

async function discard() {
  if (!detail.value) return
  working.value = true
  try {
    await receivedDocumentsApi.discard(detail.value.id)
    notifications.success('Comprobante descartado.')
    detailOpen.value = false
    await list.load()
  } catch (error) {
    notifications.error(errorMessage(error))
  } finally {
    working.value = false
  }
}

async function downloadXml() {
  if (!detail.value) return
  const xml = await receivedDocumentsApi.xml(detail.value.id)
  const url = URL.createObjectURL(new Blob([xml], { type: 'application/xml' }))
  const link = document.createElement('a')
  link.href = url
  link.download = `${detail.value.key}.xml`
  link.click()
  URL.revokeObjectURL(url)
}

const isNote = (doc: ReceivedDocument) => doc.documentType.startsWith('Nota')

onMounted(async () => {
  await list.load()
  receivedDocumentsApi.address().then((r) => (inbound.value = r)).catch(() => undefined)
  productsApi.list({ pageSize: 200, active: true }).then((r) => (products.value = r.items)).catch(() => undefined)
})
</script>

<template>
  <div>
    <PageHeader
      title="Facturas recibidas"
      description="Comprobantes electrónicos de sus proveedores. Revíselos y conviértalos en compras con un clic."
    >
      <template #actions>
        <input ref="fileInput" type="file" accept=".xml,text/xml" multiple hidden @change="onFiles" />
        <BaseButton v-if="auth.canWrite('einvoices')" variant="primary" :loading="uploading" @click="fileInput?.click()">
          Subir XML
        </BaseButton>
      </template>
    </PageHeader>

    <p v-if="inbound?.address" class="inbound">
      Pida a sus proveedores que envíen las facturas a
      <button class="link" type="button" title="Copiar" @click="copyAddress">{{ inbound.address }}</button>
      o reenvíe ahí los correos que traigan el XML: llegarán solos a esta bandeja.
    </p>

    <div class="toolbar">
      <input v-model="list.search.value" class="control search" type="search" placeholder="Buscar por emisor, cédula o número…" />
      <select v-model="list.filters.value.status" class="control filter">
        <option :value="null">Todos los estados</option>
        <option :value="ReceivedStatus.Pending">Pendientes</option>
        <option :value="ReceivedStatus.Imported">Importadas</option>
        <option :value="ReceivedStatus.Discarded">Descartadas</option>
      </select>
    </div>

    <DataTable
      :columns="columns"
      :rows="list.items.value"
      :loading="list.loading.value"
      :page="list.page.value"
      :page-size="list.pageSize"
      :total="list.total.value"
      empty-title="Bandeja vacía"
      empty-message="Suba un XML o espere a que lleguen facturas por correo."
      @update:page="list.page.value = $event"
    >
      <template #issuer="{ row }">
        <button class="link" type="button" @click="openDetail(row)">{{ row.issuerName }}</button>
        <small class="muted block">{{ row.issuerTaxId }} · {{ row.sourceName }}</small>
      </template>
      <template #consecutive="{ row }">
        <span class="num">{{ row.consecutive }}</span>
        <small class="muted block">{{ row.typeName }}</small>
      </template>
      <template #issueDate="{ row }">{{ date(row.issueDate) }}</template>
      <template #total="{ row }"><span class="num">{{ currency(row.total) }}</span></template>
      <template #status="{ row }"><StatusBadge :tone="tone(row)">{{ row.statusName }}</StatusBadge></template>
      <template #actions="{ row }">
        <BaseButton
          v-if="row.status === ReceivedStatus.Pending && auth.canWrite('einvoices')"
          size="sm"
          variant="primary"
          @click="openDetail(row)"
        >
          Revisar
        </BaseButton>
        <BaseButton v-else size="sm" @click="openDetail(row)">Ver</BaseButton>
      </template>
    </DataTable>

    <BaseModal
      :open="detailOpen"
      :title="detail ? `${detail.typeName} ${detail.consecutive}` : ''"
      :subtitle="detail?.issuerName"
      width="860px"
      @close="detailOpen = false"
    >
      <template v-if="detail">
        <dl class="summary">
          <div><dt>Emisor</dt><dd>{{ detail.issuerName }} · {{ detail.issuerTaxId }}</dd></div>
          <div><dt>Proveedor en el sistema</dt><dd>{{ detail.supplierName ?? 'No existe: se creará al importar' }}</dd></div>
          <div><dt>Emisión</dt><dd>{{ date(detail.issueDate) }} · {{ detail.isCredit ? `crédito ${detail.creditDays} días` : 'contado' }}</dd></div>
          <div><dt>Recibida</dt><dd>{{ dateTime(detail.receivedAt) }} · {{ detail.sourceName }}</dd></div>
          <div v-if="detail.purchaseNumber"><dt>Compra generada</dt><dd>{{ detail.purchaseNumber }}</dd></div>
        </dl>

        <table class="lines">
          <thead>
            <tr>
              <th>Detalle</th><th class="r">Cant.</th><th class="r">Costo unit.</th><th class="r">IVA</th><th>Producto en el sistema</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="line in detail.lines" :key="line.number">
              <td>{{ line.description }}<small v-if="line.code" class="muted block">{{ line.code }}</small></td>
              <td class="r num">{{ line.quantity }}</td>
              <td class="r num">{{ currency(line.unitCost) }}</td>
              <td class="r num">{{ line.taxRate }}%</td>
              <td>
                <select
                  v-if="detail.status === ReceivedStatus.Pending && auth.canWrite('einvoices')"
                  v-model="choice[line.number]"
                  class="control"
                >
                  <option :value="null">➕ Crear producto nuevo</option>
                  <option v-for="p in products" :key="p.id" :value="p.id">{{ p.code }} · {{ p.name }}</option>
                </select>
                <span v-else class="muted">—</span>
              </td>
            </tr>
          </tbody>
        </table>

        <dl class="totals">
          <div><dt>Subtotal</dt><dd class="num">{{ currency(detail.subtotal) }}</dd></div>
          <div><dt>Impuesto</dt><dd class="num">{{ currency(detail.taxAmount) }}</dd></div>
          <div><dt>Total</dt><dd class="num strong">{{ currency(detail.total) }}</dd></div>
        </dl>

        <p v-if="isNote(detail) && detail.status === ReceivedStatus.Pending" class="warn">
          Las notas de crédito y débito todavía no se importan automáticamente. Descártela y ajuste la compra a mano.
        </p>
        <label
          v-else-if="detail.status === ReceivedStatus.Pending && auth.canWrite('einvoices')"
          class="check"
        >
          <input v-model="confirmNow" type="checkbox" />
          Confirmar la compra de una vez (entra al inventario y se crea la cuenta por pagar)
        </label>
      </template>

      <template #footer>
        <BaseButton variant="ghost" @click="downloadXml">Descargar XML</BaseButton>
        <BaseButton variant="ghost" @click="detailOpen = false">Cerrar</BaseButton>
        <template v-if="detail?.status === ReceivedStatus.Pending && auth.canWrite('einvoices')">
          <BaseButton :loading="working" @click="discard">Descartar</BaseButton>
          <BaseButton v-if="!isNote(detail)" variant="primary" :loading="working" @click="importDocument">
            Importar como compra
          </BaseButton>
        </template>
      </template>
    </BaseModal>
  </div>
</template>

<style scoped>
.inbound {
  background: var(--brand-50, var(--ink-100));
  border-radius: var(--radius-sm);
  padding: 9px 12px;
  font-size: 13px;
  margin-bottom: 12px;
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
  width: 170px;
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

.block {
  display: block;
}

.summary,
.totals {
  display: grid;
  gap: 8px;
  margin: 0 0 16px;
}

.summary > div,
.totals > div {
  display: flex;
  justify-content: space-between;
  border-bottom: 1px solid var(--ink-100);
  padding-bottom: 7px;
}

.summary dt,
.totals dt {
  color: var(--ink-500);
  font-size: 13px;
}

.summary dd,
.totals dd {
  margin: 0;
  font-weight: 600;
}

.totals {
  margin-left: auto;
  max-width: 280px;
}

.strong {
  font-size: 16px;
}

.lines {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 14px;
  font-size: 13px;
}

.lines th {
  text-align: left;
  font-size: 12px;
  color: var(--ink-500);
  font-weight: 650;
  padding: 6px 8px;
  border-bottom: 1px solid var(--ink-200);
}

.lines td {
  padding: 8px;
  border-bottom: 1px solid var(--ink-100);
  vertical-align: top;
}

.lines .r {
  text-align: right;
}

.lines select {
  min-width: 220px;
}

.check {
  display: flex;
  gap: 8px;
  align-items: center;
  font-size: 13px;
  margin-top: 8px;
}

.warn {
  background: var(--danger-100);
  color: var(--danger-600);
  border-radius: var(--radius-sm);
  padding: 9px 12px;
  font-size: 13px;
}
</style>

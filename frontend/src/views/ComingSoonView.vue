<script setup lang="ts">
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppIcon from '@/components/ui/AppIcon.vue'
import PageHeader from '@/components/ui/PageHeader.vue'

/**
 * Marcador de los módulos ya contemplados en el modelo de datos y en la
 * navegación, pero que se implementan en etapas posteriores del plan.
 */
const route = useRoute()
const title = computed(() => (route.meta.title as string) ?? 'Módulo')

const roadmap: Record<string, string> = {
  purchases: 'Órdenes de compra, recepción de mercadería y factura del proveedor, con entrada automática al inventario.',
  sales: 'Pedidos, ventas, registro de la factura electrónica externa y salida de inventario.',
  production: 'Recetas de materiales, órdenes de producción y consumo de materia prima contra producto terminado.',
  repairs: 'Órdenes de reparación con diagnóstico, materiales utilizados, costo y entrega.',
  receivables: 'Saldos por cobrar por cliente y factura, con pagos parciales y vencimientos.',
  payables: 'Saldos por pagar por proveedor y factura, con pagos parciales y vencimientos.',
  income: 'Ingresos que no provienen de una venta.',
  expenses: 'Gastos operativos por categoría, con comprobante y método de pago.',
  reports: 'Reportes de ventas, compras, inventario, producción y finanzas, con exportación.',
  settings: 'Datos de la empresa, impuestos y parámetros generales.',
}

const description = computed(() => roadmap[route.meta.module as string] ?? '')
</script>

<template>
  <div>
    <PageHeader :title="title" />

    <section class="card">
      <AppIcon name="alert" :size="26" />
      <h2>Módulo en construcción</h2>
      <p class="muted">{{ description }}</p>
      <p class="note muted">
        El modelo de datos y los permisos de este módulo ya están definidos; su implementación
        corresponde a una etapa posterior del plan.
      </p>
    </section>
  </div>
</template>

<style scoped>
.card {
  background: var(--surface);
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  box-shadow: var(--shadow-sm);
  padding: 52px 32px;
  text-align: center;
  max-width: 620px;
  margin: 0 auto;
}

.card :deep(svg) {
  color: var(--accent-500);
  margin-bottom: 14px;
}

h2 {
  font-size: 17px;
  font-weight: 650;
  margin-bottom: 8px;
}

p {
  font-size: 13.5px;
  max-width: 52ch;
  margin: 0 auto;
}

.note {
  margin-top: 16px;
  font-size: 12.5px;
  padding-top: 16px;
  border-top: 1px solid var(--ink-100);
}
</style>

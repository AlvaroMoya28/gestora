<script setup lang="ts">
import { computed } from 'vue'

/**
 * Iconos como trazos SVG en línea. Las claves coinciden con las del catálogo de
 * módulos del backend, así que agregar un módulo solo requiere sumar su trazo aquí.
 */
const paths: Record<string, string> = {
  gauge: 'M12 13.5 16 9M3.5 18a9 9 0 1 1 17 0',
  users: 'M16 19v-1.5a3.5 3.5 0 0 0-3.5-3.5h-5A3.5 3.5 0 0 0 4 17.5V19M10 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7M20 19v-1.5a3.5 3.5 0 0 0-2.6-3.4M15.5 4.2a3.5 3.5 0 0 1 0 6.6',
  truck: 'M3 7h11v9H3zM14 10h4l3 3v3h-7M7.5 19a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3M17.5 19a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3',
  box: 'M20.5 8.5 12 4 3.5 8.5m17 0V16L12 20.5 3.5 16V8.5m17 0L12 13m0 0L3.5 8.5M12 13v7.5',
  layers: 'M12 3.5 3 8l9 4.5L21 8zM3 12.5 12 17l9-4.5M3 16.8 12 21l9-4.2',
  cart: 'M3 4h2l2.2 10.4a1.5 1.5 0 0 0 1.5 1.2h8.1a1.5 1.5 0 0 0 1.5-1.2L20 7.5H6M9.5 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2M17 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2',
  receipt: 'M6 3.5h12v17l-2.5-1.6-2.5 1.6-2.5-1.6L8 20.5l-2-1.3zM9.5 8.5h5M9.5 12.5h5',
  factory: 'M3 20.5h18M4.5 20.5V10l5 3.5V10l5 3.5V6h5v14.5',
  wrench: 'M15.5 3.7a5.5 5.5 0 0 0-6.9 6.9L3.6 15.6a2 2 0 0 0 2.8 2.8l5-5a5.5 5.5 0 0 0 6.9-6.9l-3 3-2.8-2.8z',
  'arrow-in': 'M12 3.5v11m0 0 4-4m-4 4-4-4M4.5 20.5h15',
  'arrow-out': 'M12 20.5v-11m0 0 4 4m-4-4-4 4M4.5 3.5h15',
  plus: 'M12 5v14M5 12h14',
  minus: 'M5 12h14',
  chart: 'M4 20V9M10 20V4M16 20v-7M22 20H2',
  shield: 'M12 3.5 5 6.2v5.2c0 4.2 2.9 7.6 7 9.1 4.1-1.5 7-4.9 7-9.1V6.2z',
  history: 'M3.5 12a8.5 8.5 0 1 0 2.6-6.1M3.5 5v4h4M12 8v4.3l3 1.8',
  cog: 'M12 15.2a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4M12 2.5l1.4 2.6 2.9-.5.6 2.9 2.6 1.4-1.5 2.6 1.5 2.6-2.6 1.4-.6 2.9-2.9-.5L12 21.5l-1.4-2.6-2.9.5-.6-2.9-2.6-1.4L6 12.5 4.5 9.9l2.6-1.4.6-2.9 2.9.5z',
  logout: 'M9 20.5H5.5a1.5 1.5 0 0 1-1.5-1.5v-14a1.5 1.5 0 0 1 1.5-1.5H9M15 16l4-4-4-4M19 12H9',
  search: 'M10.8 17.5a6.7 6.7 0 1 0 0-13.4 6.7 6.7 0 0 0 0 13.4M20 20l-4.5-4.5',
  menu: 'M4 7h16M4 12h16M4 17h16',
  close: 'M6 6l12 12M18 6 6 18',
  edit: 'M4 20h4L19 9a2.1 2.1 0 0 0-3-3L5 17z',
  check: 'M5 12.5 9.5 17 19 7.5',
  ban: 'M12 20.5a8.5 8.5 0 1 0 0-17 8.5 8.5 0 0 0 0 17M6 6l12 12',
  alert: 'M12 8.5v4.5M12 16.5h.01M10.3 4.2 2.8 17.2a2 2 0 0 0 1.7 3h15a2 2 0 0 0 1.7-3L13.7 4.2a2 2 0 0 0-3.4 0z',
  building: 'M4 20.5V5a1.5 1.5 0 0 1 1.5-1.5h8A1.5 1.5 0 0 1 15 5v15.5M15 10h3.5A1.5 1.5 0 0 1 20 11.5v9M3 20.5h18M7.5 7.5h4M7.5 11h4M7.5 14.5h4',
  tag: 'M3.5 11.4V4.5a1 1 0 0 1 1-1h6.9a1 1 0 0 1 .7.3l8.1 8.1a1 1 0 0 1 0 1.4l-6.9 6.9a1 1 0 0 1-1.4 0L3.8 12.1a1 1 0 0 1-.3-.7M7.5 8h.01',
  eye: 'M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6',
  help: 'M12 20.5a8.5 8.5 0 1 0 0-17 8.5 8.5 0 0 0 0 17M9.6 9.3a2.5 2.5 0 0 1 4.8 1c0 1.7-2.4 2.2-2.4 3.7M12 17h.01',
  book: 'M4 5.5A1.5 1.5 0 0 1 5.5 4H11v16H5.5A1.5 1.5 0 0 1 4 18.5zM20 5.5A1.5 1.5 0 0 0 18.5 4H13v16h5.5a1.5 1.5 0 0 0 1.5-1.5z',
  play: 'M12 20.5a8.5 8.5 0 1 0 0-17 8.5 8.5 0 0 0 0 17M10 8.8v6.4l5-3.2z',
  'chevron-right': 'M9.5 6 15.5 12l-6 6',
}

const props = withDefaults(defineProps<{ name: string; size?: number }>(), { size: 18 })

const path = computed(() => paths[props.name] ?? paths.box)
</script>

<template>
  <svg
    :width="size"
    :height="size"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="1.6"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
  >
    <path :d="path" />
  </svg>
</template>

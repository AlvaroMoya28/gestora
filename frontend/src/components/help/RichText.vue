<script setup lang="ts">
import { computed } from 'vue'

/**
 * Texto de la ayuda con el mínimo formato que necesita: párrafos separados por una
 * línea en blanco y **negrita** para nombrar botones y campos tal como se ven.
 *
 * Se arma con nodos y no con v-html a propósito: la ayuda nunca puede inyectar marcado,
 * aunque algún día sus textos vengan de otro lado.
 */
const props = withDefaults(defineProps<{ text: string; inline?: boolean }>(), { inline: false })

interface Segment {
  text: string
  strong: boolean
}

function segments(text: string): Segment[] {
  return text
    .split(/(\*\*[^*]+\*\*)/)
    .filter(Boolean)
    .map((part) =>
      part.startsWith('**') && part.endsWith('**')
        ? { text: part.slice(2, -2), strong: true }
        : { text: part, strong: false },
    )
}

const blocks = computed(() =>
  props.text
    .split(/\n\s*\n/)
    .map((block) => block.trim())
    .filter(Boolean)
    .map(segments),
)
</script>

<template>
  <template v-if="inline">
    <template v-for="(segment, i) in blocks[0] ?? []" :key="i">
      <strong v-if="segment.strong">{{ segment.text }}</strong>
      <template v-else>{{ segment.text }}</template>
    </template>
  </template>
  <template v-else>
    <p v-for="(block, b) in blocks" :key="b">
      <template v-for="(segment, i) in block" :key="i">
        <strong v-if="segment.strong">{{ segment.text }}</strong>
        <template v-else>{{ segment.text }}</template>
      </template>
    </p>
  </template>
</template>

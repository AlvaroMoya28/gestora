<script setup lang="ts">
import { computed, useId } from 'vue'

/**
 * Isotipo de Gestora: una G recortada dentro del cuadrado de la marca.
 *
 * La letra no está dibujada encima del cuadrado, está calada: se ve el fondo a través
 * de ella. Por eso el color se toma de `currentColor` y una sola figura sirve para los
 * tres mundos —empresa, plataforma y fondos oscuros— sin mantener tres archivos.
 *
 * La forma está pensada para 16 px. No la adelgace ni le agregue detalle: a ese tamaño
 * el trazo de 3.5 (sobre una retícula de 32) es lo que mantiene la G legible en la
 * pestaña del navegador.
 */
const props = withDefaults(
  defineProps<{
    size?: number
    /** empresa (verde) · plataforma (tinta) · claro (blanco, para fondos de color) */
    tone?: 'brand' | 'platform' | 'light'
    /**
     * Texto alternativo. Se deja vacío cuando la palabra «Gestora» va al lado: ahí el
     * isotipo es decorativo y repetirlo solo ensucia el lector de pantalla.
     */
    label?: string
  }>(),
  { size: 32, tone: 'brand', label: '' },
)

/** Dos isotipos en la misma página no pueden compartir el id de la máscara. */
const maskId = `gestora-mark-${useId()}`

const decorative = computed(() => props.label.length === 0)
</script>

<template>
  <svg
    :class="['brand-mark', `tone-${tone}`]"
    :width="size"
    :height="size"
    viewBox="0 0 32 32"
    xmlns="http://www.w3.org/2000/svg"
    :role="decorative ? undefined : 'img'"
    :aria-hidden="decorative ? 'true' : undefined"
    :aria-label="decorative ? undefined : label"
  >
    <defs>
      <mask :id="maskId">
        <rect width="32" height="32" fill="white" />
        <path
          d="M22 10H14a6 6 0 0 0-6 6v1a6 6 0 0 0 6 6h8v-6h-6"
          fill="none"
          stroke="black"
          stroke-width="3.5"
          stroke-linecap="round"
          stroke-linejoin="round"
        />
      </mask>
    </defs>
    <rect
      x="1"
      y="1"
      width="30"
      height="30"
      rx="7"
      fill="currentColor"
      :mask="`url(#${maskId})`"
    />
  </svg>
</template>

<style scoped>
.brand-mark {
  flex-shrink: 0;
  display: block;
}

/*
 * Los tonos van con prefijo a propósito. La raíz de un componente hijo también recibe
 * el scope del padre, así que una clase llamada `brand` heredaría los estilos del
 * contenedor `.brand` de la cabecera —padding y borde incluidos— y el isotipo se
 * deformaría hasta desaparecer.
 */
.brand-mark.tone-brand {
  color: var(--brand-500);
}

/* La plataforma se distingue del mundo de la empresa a simple vista. */
.brand-mark.tone-platform {
  color: var(--ink-900);
}

.brand-mark.tone-light {
  color: #fff;
}
</style>

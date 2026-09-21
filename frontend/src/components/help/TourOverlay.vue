<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppIcon from '@/components/ui/AppIcon.vue'
import BaseButton from '@/components/ui/BaseButton.vue'
import RichText from '@/components/help/RichText.vue'
import { useTourStore } from '@/stores/tour'

/**
 * Recorrido guiado: oscurece la pantalla, deja iluminado un solo elemento y pone al
 * lado un cuadro que explica qué es.
 *
 * El oscurecido no es una capa con un agujero recortado: es la sombra gigante de un
 * rectángulo transparente del tamaño del elemento. Así el hueco puede moverse de un
 * paso al siguiente con una transición de CSS, sin recalcular ninguna figura.
 *
 * La pantalla queda bloqueada mientras dura el recorrido: se avanza solo con los
 * botones del cuadro. Dejar que la persona haga clic en lo iluminado parece más
 * natural, pero un clic que abre un formulario a mitad del recorrido la deja perdida.
 */
const tour = useTourStore()
const router = useRouter()
const route = useRoute()

/** Margen de luz alrededor del elemento y separación entre el hueco y el cuadro. */
const PAD = 6
const GAP = 14
const EDGE = 12

const rect = ref<DOMRect | null>(null)
const ready = ref(false)
const viewport = reactive({ w: window.innerWidth, h: window.innerHeight })
const card = ref<HTMLElement | null>(null)
const cardSize = reactive({ w: 360, h: 220 })
const primary = ref<InstanceType<typeof BaseButton> | null>(null)

let target: HTMLElement | null = null
/** Cada paso nuevo invalida la búsqueda del anterior, por si alguien avanza rápido. */
let run = 0

// ---------------------------------------------------------------- Pasos ----

watch(
  () => (tour.active ? `${tour.index}/${tour.total}` : null),
  (value) => {
    if (value) void show()
  },
  { immediate: true },
)

async function show() {
  const current = ++run
  const step = tour.current
  if (!step) return

  ready.value = false
  target = null
  rect.value = null

  if (step.route && route.name !== step.route) {
    await router.push({ name: step.route }).catch(() => undefined)
  }
  if (current !== run) return

  if (step.target) {
    target = await waitFor(step.target)
    if (current !== run) return

    if (!target && step.optional) {
      if (!tour.skipUnavailable()) finish()
      return
    }

    // Lo que está fuera de la vista se trae al centro antes de iluminarlo.
    target?.scrollIntoView({ block: 'center', inline: 'nearest' })
    await frame()
  }

  measure()
  ready.value = true
  await nextTick()
  primary.value?.$el?.focus({ preventScroll: true })

  // La barra lateral del celular entra deslizándose: se vuelve a medir cuando termina.
  window.setTimeout(() => current === run && measure(), 260)
}

/**
 * Espera a que el elemento exista y se vea. Las pantallas cargan sus datos después de
 * abrirse, así que un botón puede tardar unos instantes en aparecer.
 */
function waitFor(selector: string, timeout = 2500): Promise<HTMLElement | null> {
  const started = performance.now()
  return new Promise((resolve) => {
    const tick = () => {
      const found = document.querySelector<HTMLElement>(selector)
      if (found && isVisible(found)) return resolve(found)
      if (performance.now() - started > timeout) return resolve(null)
      requestAnimationFrame(tick)
    }
    tick()
  })
}

function isVisible(element: HTMLElement) {
  const r = element.getBoundingClientRect()
  return r.width > 4 && r.height > 4
}

const frame = () => new Promise((resolve) => requestAnimationFrame(() => resolve(null)))

function measure() {
  viewport.w = window.innerWidth
  viewport.h = window.innerHeight
  rect.value = target?.isConnected && isVisible(target) ? target.getBoundingClientRect() : null
}

// ------------------------------------------------------------- Posición ----

/** El hueco iluminado. Sin elemento se encoge al centro y la pantalla queda toda oscura. */
const hole = computed(() => {
  const r = rect.value
  if (!r) return { top: viewport.h / 2, left: viewport.w / 2, width: 0, height: 0 }
  return {
    top: r.top - PAD,
    left: r.left - PAD,
    width: r.width + PAD * 2,
    height: r.height + PAD * 2,
  }
})

type Side = 'right' | 'left' | 'bottom' | 'top'

/**
 * El cuadro va del lado que se pida si cabe; si no, del primero que quepa. Si el
 * elemento ocupa casi toda la pantalla —una tabla, por ejemplo— el cuadro se apoya
 * abajo, encima del elemento, que es mejor que taparlo a medias por un costado.
 */
const cardPosition = computed(() => {
  const { w, h } = cardSize
  const step = tour.current
  const clampX = (x: number) => Math.min(Math.max(x, EDGE), viewport.w - w - EDGE)
  const clampY = (y: number) => Math.min(Math.max(y, EDGE), viewport.h - h - EDGE)

  if (!rect.value || step?.placement === 'center') {
    return { top: clampY((viewport.h - h) / 2), left: clampX((viewport.w - w) / 2) }
  }

  const r = hole.value
  const fits: Record<Side, boolean> = {
    right: r.left + r.width + GAP + w <= viewport.w - EDGE,
    left: r.left - GAP - w >= EDGE,
    bottom: r.top + r.height + GAP + h <= viewport.h - EDGE,
    top: r.top - GAP - h >= EDGE,
  }

  const preferred = step?.placement && step.placement !== 'auto' ? [step.placement as Side] : []
  const side = [...preferred, 'right', 'bottom', 'left', 'top'].find((s) => fits[s as Side]) as
    | Side
    | undefined

  const middleY = clampY(r.top + r.height / 2 - h / 2)
  const middleX = clampX(r.left + r.width / 2 - w / 2)

  switch (side) {
    case 'right':
      return { top: middleY, left: r.left + r.width + GAP }
    case 'left':
      return { top: middleY, left: r.left - GAP - w }
    case 'bottom':
      return { top: r.top + r.height + GAP, left: middleX }
    case 'top':
      return { top: r.top - GAP - h, left: middleX }
    default:
      return { top: viewport.h - h - EDGE, left: middleX }
  }
})

let observer: ResizeObserver | null = null

watch(card, (element) => {
  observer?.disconnect()
  if (!element) return
  observer = new ResizeObserver(() => {
    cardSize.w = element.offsetWidth
    cardSize.h = element.offsetHeight
  })
  observer.observe(element)
})

// ------------------------------------------------------------- Acciones ----

function goNext() {
  if (!tour.next()) finish()
}

function goPrev() {
  tour.prev()
}

function finish() {
  run++
  const back = tour.close()
  if (back && back !== route.fullPath) void router.push(back)
}

const progress = computed(() => ((tour.index + 1) / Math.max(tour.total, 1)) * 100)

function onKey(event: KeyboardEvent) {
  if (!tour.active) return
  if (event.key === 'Escape') finish()
  else if (event.key === 'ArrowRight') goNext()
  else if (event.key === 'ArrowLeft') goPrev()
}

// Cualquier cambio de tamaño o desplazamiento mueve el elemento: se vuelve a medir.
let pending = false
function remeasure() {
  if (!tour.active || pending) return
  pending = true
  requestAnimationFrame(() => {
    pending = false
    measure()
  })
}

onMounted(() => {
  window.addEventListener('keydown', onKey)
  window.addEventListener('resize', remeasure)
  window.addEventListener('scroll', remeasure, true)
})

onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKey)
  window.removeEventListener('resize', remeasure)
  window.removeEventListener('scroll', remeasure, true)
  observer?.disconnect()
})
</script>

<template>
  <Teleport to="body">
    <div v-if="tour.active" class="tour">
      <!-- Bloquea la pantalla: mientras dure el recorrido, se avanza solo con el cuadro. -->
      <div class="blocker" />

      <div
        class="spotlight"
        :class="{ empty: !rect }"
        :style="{
          top: `${hole.top}px`,
          left: `${hole.left}px`,
          width: `${hole.width}px`,
          height: `${hole.height}px`,
        }"
      />

      <section
        v-if="tour.current"
        ref="card"
        class="card"
        :class="{ ready }"
        :style="{ top: `${cardPosition.top}px`, left: `${cardPosition.left}px` }"
        role="dialog"
        aria-modal="true"
        aria-labelledby="tour-title"
        aria-describedby="tour-body"
      >
        <header>
          <span class="count">Paso {{ tour.index + 1 }} de {{ tour.total }}</span>
          <button
            type="button"
            class="close"
            :aria-label="tour.kind === 'welcome' ? 'Saltar el recorrido' : 'Cerrar'"
            :title="tour.kind === 'welcome' ? 'Saltar el recorrido' : 'Cerrar'"
            @click="finish"
          >
            <AppIcon name="close" :size="16" />
          </button>
        </header>

        <div class="bar" aria-hidden="true">
          <span :style="{ width: `${progress}%` }" />
        </div>

        <h3 id="tour-title">{{ tour.current.title }}</h3>
        <div id="tour-body" class="body">
          <RichText :text="tour.current.body" />
        </div>

        <footer>
          <button type="button" class="skip" @click="finish">
            {{ tour.kind === 'welcome' ? 'Saltar recorrido' : 'Cerrar' }}
          </button>
          <div class="nav">
            <BaseButton v-if="!tour.isFirst" size="sm" @click="goPrev">Anterior</BaseButton>
            <BaseButton ref="primary" size="sm" variant="primary" @click="goNext">
              {{ tour.isLast ? 'Terminar' : 'Siguiente' }}
            </BaseButton>
          </div>
        </footer>
      </section>
    </div>
  </Teleport>
</template>

<style scoped>
.tour {
  position: fixed;
  inset: 0;
  z-index: 1000;
}

.blocker {
  position: absolute;
  inset: 0;
}

/*
 * El hueco: transparente por dentro, con un borde blanco que lo recorta del fondo y
 * una sombra que oscurece todo lo demás.
 */
.spotlight {
  position: fixed;
  border-radius: 10px;
  box-shadow:
    0 0 0 3px rgba(255, 255, 255, 0.95),
    0 0 0 9999px rgba(12, 15, 19, 0.68);
  pointer-events: none;
  transition:
    top 0.32s ease,
    left 0.32s ease,
    width 0.32s ease,
    height 0.32s ease;
}

.spotlight.empty {
  box-shadow: 0 0 0 9999px rgba(12, 15, 19, 0.68);
}

.card {
  position: fixed;
  width: min(360px, calc(100vw - 24px));
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  padding: 16px 18px 14px;
  opacity: 0;
  transform: translateY(4px);
  transition:
    top 0.32s ease,
    left 0.32s ease,
    opacity 0.18s ease,
    transform 0.18s ease;
}

.card.ready {
  opacity: 1;
  transform: none;
}

header {
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.count {
  font-size: 11.5px;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: var(--brand-600);
}

.close {
  background: none;
  border: none;
  color: var(--ink-400);
  cursor: pointer;
  padding: 4px;
  border-radius: var(--radius-sm);
  display: grid;
  place-items: center;
}

.close:hover {
  background: var(--ink-100);
  color: var(--ink-700);
}

.bar {
  height: 3px;
  background: var(--ink-100);
  border-radius: 3px;
  margin: 8px 0 12px;
  overflow: hidden;
}

.bar span {
  display: block;
  height: 100%;
  background: var(--brand-500);
  border-radius: 3px;
  transition: width 0.3s ease;
}

h3 {
  font-size: 16.5px;
  font-weight: 650;
  letter-spacing: -0.01em;
  color: var(--ink-900);
}

.body {
  margin-top: 6px;
  font-size: 14px;
  line-height: 1.55;
  color: var(--ink-700);
  max-height: 46vh;
  overflow-y: auto;
}

.body :deep(p + p) {
  margin-top: 8px;
}

.body :deep(strong) {
  color: var(--ink-900);
  font-weight: 650;
}

footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  margin-top: 14px;
}

.skip {
  background: none;
  border: none;
  color: var(--ink-500);
  font-size: 13px;
  cursor: pointer;
  padding: 4px 2px;
}

.skip:hover {
  color: var(--ink-900);
  text-decoration: underline;
}

.nav {
  display: flex;
  gap: 8px;
}

@media (prefers-reduced-motion: reduce) {
  .spotlight,
  .card,
  .bar span {
    transition: none;
  }
}
</style>

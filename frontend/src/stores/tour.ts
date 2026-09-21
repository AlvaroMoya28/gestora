import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'

export type TourPlacement = 'auto' | 'right' | 'left' | 'top' | 'bottom' | 'center'

/**
 * Un paso del recorrido: qué se ilumina, dónde hay que estar para verlo y qué se dice.
 */
export interface TourStep {
  /** Selector CSS del elemento que se ilumina. Sin selector, el cuadro sale al centro. */
  target?: string
  /** Nombre de la ruta a la que hay que ir antes de mostrar el paso. */
  route?: string
  title: string
  /** Texto del paso. Los párrafos se separan con una línea en blanco; **así** va negrita. */
  body: string
  placement?: TourPlacement
  /**
   * El paso habla de algo que no siempre está en pantalla (las alertas del panel solo
   * aparecen si hay alertas). Si el elemento no aparece, se salta en vez de mostrar un
   * cuadro que habla de algo que la persona no ve.
   */
  optional?: boolean
}

/** El de bienvenida recorre el sistema completo; el de módulo, una sola pantalla. */
export type TourKind = 'welcome' | 'module'

/**
 * Estado del recorrido guiado. Solo guarda en qué paso se va: moverse entre pantallas,
 * buscar el elemento y dibujar el cuadro es trabajo de TourOverlay, que tiene el router.
 */
export const useTourStore = defineStore('tour', () => {
  const steps = ref<TourStep[]>([])
  const index = ref(0)
  const kind = ref<TourKind>('welcome')
  /** Hacia dónde se movió la persona: si un paso opcional no aplica, se sigue en ese sentido. */
  const direction = ref<1 | -1>(1)
  /** Pantalla a la que se vuelve al terminar el recorrido de bienvenida. */
  const returnTo = ref<string | null>(null)

  const active = computed(() => steps.value.length > 0)
  const current = computed(() => steps.value[index.value] ?? null)
  const total = computed(() => steps.value.length)
  const isFirst = computed(() => index.value === 0)
  const isLast = computed(() => index.value === steps.value.length - 1)

  /**
   * El paso señala algo de la barra lateral. En el celular esa barra vive escondida,
   * así que el layout la abre mientras dure el paso.
   */
  const wantsSidebar = computed(
    () => active.value && /\[data-tour="(menu|brand)/.test(current.value?.target ?? ''),
  )

  function start(list: TourStep[], tourKind: TourKind, origin: string | null = null) {
    if (list.length === 0) return
    steps.value = list
    index.value = 0
    direction.value = 1
    kind.value = tourKind
    returnTo.value = origin
  }

  /** Avanza. Devuelve falso si ya estaba en el último paso: cerrar le toca a quien llama. */
  function next() {
    direction.value = 1
    if (isLast.value) return false
    index.value++
    return true
  }

  function prev() {
    if (isFirst.value) return
    direction.value = -1
    index.value--
  }

  /**
   * Se salta el paso actual porque su elemento no está en pantalla, en el mismo
   * sentido en que la persona venía avanzando.
   */
  function skipUnavailable() {
    if (direction.value === 1) return next()
    if (isFirst.value) return next()
    index.value--
    return true
  }

  /**
   * Cierra el recorrido. Terminarlo o saltarlo cuenta igual: la bienvenida ya no se
   * vuelve a mostrar sola, y quien quiera repetirla lo hace desde Ayuda.
   * Devuelve la pantalla a la que hay que volver, si corresponde.
   */
  function close() {
    const back = returnTo.value
    if (kind.value === 'welcome') void useAuthStore().markTourCompleted()
    steps.value = []
    index.value = 0
    returnTo.value = null
    return back
  }

  return {
    steps,
    index,
    kind,
    active,
    current,
    total,
    isFirst,
    isLast,
    wantsSidebar,
    returnTo,
    start,
    next,
    prev,
    skipUnavailable,
    close,
  }
})

import { ref, watch } from 'vue'
import { errorMessage } from '@/services/http'
import type { ListQuery } from '@/services/api'
import { useNotificationStore } from '@/stores/notifications'
import type { PagedResult } from '@/types'

/**
 * Lógica compartida de los listados: búsqueda con retardo, paginación, filtros y
 * manejo de errores. Cada vista aporta solo su función de carga y sus columnas,
 * en lugar de repetir este mismo bloque cinco veces.
 */
export function useResourceList<T>(
  fetcher: (query: ListQuery) => Promise<PagedResult<T>>,
  options: { pageSize?: number; initialFilters?: Record<string, unknown> } = {},
) {
  const notifications = useNotificationStore()

  const items = ref<T[]>([]) as { value: T[] }
  const total = ref(0)
  const page = ref(1)
  const pageSize = options.pageSize ?? 20
  const search = ref('')
  const filters = ref<Record<string, unknown>>({ ...options.initialFilters })
  const loading = ref(false)

  async function load() {
    loading.value = true
    try {
      const result = await fetcher({ page: page.value, pageSize, search: search.value, ...filters.value })
      items.value = result.items
      total.value = result.total

      // Si al filtrar la página actual queda fuera de rango, se retrocede.
      if (result.items.length === 0 && page.value > 1) {
        page.value = 1
      }
    } catch (error) {
      notifications.error(errorMessage(error, 'No se pudo cargar la información.'))
    } finally {
      loading.value = false
    }
  }

  let searchTimer: number | undefined
  watch(search, () => {
    window.clearTimeout(searchTimer)
    // 350 ms: suficiente para no lanzar una petición por tecla y no sentirse lento.
    searchTimer = window.setTimeout(() => {
      page.value = 1
      load()
    }, 350)
  })

  watch(page, load)
  watch(filters, () => {
    page.value = 1
    load()
  }, { deep: true })

  return { items, total, page, pageSize, search, filters, loading, load }
}

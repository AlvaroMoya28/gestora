import { defineStore } from 'pinia'
import { computed, ref } from 'vue'
import { authApi } from '@/services/api'
import { setSessionExpiredHandler, tokenStorage } from '@/services/http'
import { RoleKeys, type AuthenticatedUser } from '@/types'

const USER_KEY = 'gestora.user'

/**
 * Sesión del usuario. Guarda el perfil y, sobre todo, los módulos que tiene
 * permitidos: el menú y las guardas de ruta se derivan de esa misma lista, que es
 * la que emitió el backend. No hay una segunda tabla de permisos en el frontend.
 */
export const useAuthStore = defineStore('auth', () => {
  const user = ref<AuthenticatedUser | null>(readCachedUser())
  const loading = ref(false)

  const isAuthenticated = computed(() => !!user.value && !!tokenStorage.access)

  const modules = computed(() => user.value?.modules ?? [])

  /** Sesión de plataforma: el usuario administra Gestora, no una empresa. */
  const isPlatform = computed(() => user.value?.scope === 'Platform')
  const isDeveloper = computed(() => user.value?.roleKey === RoleKeys.Developer)
  const isImpersonating = computed(() => user.value?.isImpersonating === true)

  /** Dónde aterriza el usuario al entrar, según el mundo al que pertenece. */
  const homeRoute = computed(() => (isPlatform.value ? 'platform_overview' : 'dashboard'))

  /** Módulos disponibles agrupados en el orden en que llegan del catálogo. */
  const menuGroups = computed(() => {
    const groups = new Map<string, typeof modules.value>()
    for (const item of modules.value) {
      if (!groups.has(item.group)) groups.set(item.group, [])
      groups.get(item.group)!.push(item)
    }
    return [...groups.entries()].map(([name, items]) => ({ name, items }))
  })

  function canRead(moduleKey: string) {
    return modules.value.some((m) => m.key === moduleKey)
  }

  function canWrite(moduleKey: string) {
    return modules.value.some((m) => m.key === moduleKey && m.canWrite)
  }

  function persist(value: AuthenticatedUser | null) {
    user.value = value
    if (value) localStorage.setItem(USER_KEY, JSON.stringify(value))
    else localStorage.removeItem(USER_KEY)
  }

  async function login(email: string, password: string) {
    loading.value = true
    try {
      const response = await authApi.login(email, password)
      tokenStorage.save(response.accessToken, response.refreshToken)
      persist(response.user)
      return response.user
    } finally {
      loading.value = false
    }
  }

  /** Revalida la sesión contra el backend al recargar la página. */
  async function restore() {
    if (!tokenStorage.access) {
      clearSession()
      return false
    }
    try {
      persist(await authApi.me())
      return true
    } catch {
      clearSession()
      return false
    }
  }

  /**
   * Vuelve a leer el perfil sin tocar los tokens. Se usa cuando cambia algo que la
   * sesión lleva copiado —la moneda de la empresa, por ejemplo— para que el formato
   * de toda la aplicación se entere sin obligar a volver a entrar.
   */
  async function refreshProfile() {
    persist(await authApi.me())
  }

  async function logout() {
    const refresh = tokenStorage.refresh
    if (refresh) {
      // El cierre de sesión local no debe depender de que el servidor responda.
      await authApi.logout(refresh).catch(() => undefined)
    }
    clearSession()
  }

  function clearSession() {
    tokenStorage.clear()
    persist(null)
  }

  /**
   * Cambia la sesión a la vista de una empresa. El backend emite un token nuevo con
   * los permisos de esa empresa, así que a partir de aquí se ve exactamente lo mismo
   * que ve el cliente. Solo el desarrollador puede hacerlo.
   */
  async function viewAsCompany(companyId: number) {
    const response = await authApi.viewAsCompany(companyId)
    tokenStorage.save(response.accessToken, response.refreshToken)
    persist(response.user)
    return response.user
  }

  async function backToPlatform() {
    const response = await authApi.backToPlatform()
    tokenStorage.save(response.accessToken, response.refreshToken)
    persist(response.user)
    return response.user
  }

  return {
    user,
    loading,
    isAuthenticated,
    isPlatform,
    isDeveloper,
    isImpersonating,
    homeRoute,
    modules,
    menuGroups,
    canRead,
    canWrite,
    login,
    logout,
    restore,
    refreshProfile,
    clearSession,
    viewAsCompany,
    backToPlatform,
  }
})

function readCachedUser(): AuthenticatedUser | null {
  try {
    const raw = localStorage.getItem(USER_KEY)
    return raw ? (JSON.parse(raw) as AuthenticatedUser) : null
  } catch {
    return null
  }
}

/** Conecta el interceptor HTTP con el store: un 401 irrecuperable cierra la sesión. */
export function bindSessionExpiry(redirect: () => void) {
  setSessionExpiredHandler(() => {
    useAuthStore().clearSession()
    redirect()
  })
}

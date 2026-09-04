import axios, { AxiosError, type AxiosInstance, type InternalAxiosRequestConfig } from 'axios'
import type { ApiErrorBody } from '@/types'

/**
 * Cliente HTTP único de la aplicación.
 *
 * La URL del backend sale exclusivamente de VITE_API_URL: no hay direcciones
 * repartidas por las vistas. Aquí también viven el envío del token, la renovación
 * automática de la sesión y la traducción de cualquier fallo a un mensaje legible.
 */
const baseURL = import.meta.env.VITE_API_URL || 'http://localhost:5240/api'

const ACCESS_TOKEN_KEY = 'gestora.accessToken'
const REFRESH_TOKEN_KEY = 'gestora.refreshToken'

export const tokenStorage = {
  get access() {
    return localStorage.getItem(ACCESS_TOKEN_KEY)
  },
  get refresh() {
    return localStorage.getItem(REFRESH_TOKEN_KEY)
  },
  save(access: string, refresh: string) {
    localStorage.setItem(ACCESS_TOKEN_KEY, access)
    localStorage.setItem(REFRESH_TOKEN_KEY, refresh)
  },
  clear() {
    localStorage.removeItem(ACCESS_TOKEN_KEY)
    localStorage.removeItem(REFRESH_TOKEN_KEY)
  },
}

export const http: AxiosInstance = axios.create({
  baseURL,
  timeout: 20000,
  headers: { 'Content-Type': 'application/json' },
})

http.interceptors.request.use((config: InternalAxiosRequestConfig) => {
  const token = tokenStorage.access
  if (token) config.headers.Authorization = `Bearer ${token}`
  return config
})

/** Se registra desde el store de sesión para cerrar la sesión sin crear un ciclo de imports. */
let onSessionExpired: (() => void) | null = null
export function setSessionExpiredHandler(handler: () => void) {
  onSessionExpired = handler
}

type RetriableConfig = InternalAxiosRequestConfig & { _retried?: boolean }

// Una sola renovación en curso: si varias peticiones fallan a la vez con 401,
// todas esperan al mismo refresh en lugar de disparar uno cada una.
let refreshPromise: Promise<string> | null = null

async function refreshSession(): Promise<string> {
  const refreshToken = tokenStorage.refresh
  if (!refreshToken) throw new Error('sin refresh token')

  const { data } = await axios.post(`${baseURL}/auth/refresh`, { refreshToken })
  tokenStorage.save(data.accessToken, data.refreshToken)
  return data.accessToken as string
}

http.interceptors.response.use(
  (response) => response,
  async (error: AxiosError<ApiErrorBody>) => {
    const config = error.config as RetriableConfig | undefined
    const status = error.response?.status
    const isAuthCall = config?.url?.includes('/auth/login') || config?.url?.includes('/auth/refresh')

    if (status === 401 && config && !config._retried && !isAuthCall && tokenStorage.refresh) {
      config._retried = true
      try {
        refreshPromise ??= refreshSession().finally(() => {
          refreshPromise = null
        })
        const token = await refreshPromise
        config.headers.Authorization = `Bearer ${token}`
        return http(config)
      } catch {
        tokenStorage.clear()
        onSessionExpired?.()
      }
    }

    return Promise.reject(error)
  },
)

/**
 * Extrae el mensaje que se le puede enseñar al usuario. El backend siempre
 * responde con { message }, así que la interfaz nunca muestra detalles técnicos.
 */
export function errorMessage(error: unknown, fallback = 'No se pudo completar la operación.'): string {
  if (axios.isAxiosError<ApiErrorBody>(error)) {
    if (error.response?.data?.message) return error.response.data.message
    if (error.code === 'ECONNABORTED') return 'La operación tardó demasiado. Intente de nuevo.'
    if (!error.response) return 'No hay conexión con el servidor. Verifique que el backend esté en ejecución.'
  }
  return fallback
}

/** Errores de validación por campo, para resaltarlos en el formulario. */
export function fieldErrors(error: unknown): Record<string, string> {
  const result: Record<string, string> = {}
  if (axios.isAxiosError<ApiErrorBody>(error) && error.response?.data?.errors) {
    for (const [field, messages] of Object.entries(error.response.data.errors)) {
      const key = field.charAt(0).toLowerCase() + field.slice(1)
      result[key] = messages[0]
    }
  }
  return result
}

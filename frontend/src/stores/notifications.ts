import { defineStore } from 'pinia'
import { ref } from 'vue'

export type ToastLevel = 'success' | 'error' | 'info'

export interface Toast {
  id: number
  level: ToastLevel
  message: string
}

/** Avisos breves. Sin librerías externas: son cuatro líneas y un componente. */
export const useNotificationStore = defineStore('notifications', () => {
  const toasts = ref<Toast[]>([])
  let nextId = 1

  function push(level: ToastLevel, message: string, duration = 4000) {
    const id = nextId++
    toasts.value.push({ id, level, message })
    window.setTimeout(() => dismiss(id), duration)
  }

  function dismiss(id: number) {
    toasts.value = toasts.value.filter((t) => t.id !== id)
  }

  return {
    toasts,
    dismiss,
    success: (message: string) => push('success', message),
    error: (message: string) => push('error', message, 6000),
    info: (message: string) => push('info', message),
  }
})

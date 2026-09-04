import { useAuthStore } from '@/stores/auth'

/**
 * Formato de números, dinero y fechas en un solo lugar. La moneda sale de la
 * empresa de la sesión, no de una constante escrita en las vistas.
 */
export function useFormat() {
  const auth = useAuthStore()

  const currency = (value: number | null | undefined) =>
    new Intl.NumberFormat('es-CR', {
      style: 'currency',
      currency: auth.user?.currency || 'CRC',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    }).format(value ?? 0)

  const number = (value: number | null | undefined, decimals = 2) =>
    new Intl.NumberFormat('es-CR', {
      minimumFractionDigits: 0,
      maximumFractionDigits: decimals,
    }).format(value ?? 0)

  const integer = (value: number | null | undefined) => number(value, 0)

  const dateTime = (value: string | null | undefined) =>
    value
      ? new Date(value).toLocaleString('es-CR', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
        })
      : '—'

  const date = (value: string | null | undefined) =>
    value ? new Date(value).toLocaleDateString('es-CR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—'

  /** Aplica el formato que el backend indicó para cada métrica del panel. */
  const metric = (value: number, format: string) => {
    if (format === 'money') return currency(value)
    if (format === 'percent') return `${number(value, 1)} %`
    return integer(value)
  }

  return { currency, number, integer, dateTime, date, metric }
}

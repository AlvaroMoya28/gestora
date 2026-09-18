import { computed, ref, type Ref } from 'vue'

/**
 * Plazo de pago de un documento (venta o compra).
 *
 * El sistema guarda el plazo en días, pero nadie negocia en días: se pacta "a 30 días",
 * "a tres meses" o "el 15 de diciembre". Este composable traduce entre las dos cosas —
 * lo que la persona dice y lo que el documento guarda— y deja siempre a la vista la
 * fecha concreta de vencimiento, que es lo único que de verdad importa después.
 */

export interface DueOption {
  value: string
  label: string
  days?: number
  months?: number
}

/** Contado: se paga el mismo día del documento. */
export const DUE_CASH = 'cash'
/** Fecha específica: la persona señala el día en el calendario. */
export const DUE_CUSTOM = 'custom'

/**
 * Contado y crédito viven en la misma lista a propósito. Antes eran dos campos —
 * «condición de pago» y «plazo»— y el de plazo solo aparecía al elegir crédito, así
 * que los plazos en meses quedaban escondidos detrás de un campo que nadie asociaba
 * con ellos. Preguntar una sola cosa, "¿cuándo se paga?", se entiende sin explicación.
 */
export const DUE_OPTIONS: DueOption[] = [
  { value: DUE_CASH, label: 'Contado — el mismo día', days: 0 },
  { value: 'd8', label: '8 días', days: 8 },
  { value: 'd15', label: '15 días', days: 15 },
  { value: 'd30', label: '30 días', days: 30 },
  { value: 'd45', label: '45 días', days: 45 },
  { value: 'd60', label: '60 días', days: 60 },
  { value: 'd90', label: '90 días', days: 90 },
  { value: 'm1', label: '1 mes', months: 1 },
  { value: 'm2', label: '2 meses', months: 2 },
  { value: 'm3', label: '3 meses', months: 3 },
  { value: 'm6', label: '6 meses', months: 6 },
  { value: 'm12', label: '1 año', months: 12 },
  { value: DUE_CUSTOM, label: 'Fecha específica…' },
]

export const todayIso = () => new Date().toISOString().slice(0, 10)

/** Fecha local a partir de un "yyyy-mm-dd", sin que la zona horaria la corra un día. */
export function parseIso(value: string): Date {
  const [year, month, day] = value.split('-').map(Number)
  return new Date(year, (month ?? 1) - 1, day ?? 1)
}

export const toIso = (date: Date) =>
  `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`

/**
 * Suma meses respetando el fin de mes: el 31 de enero más un mes es el 28 de febrero,
 * no el 3 de marzo, que es lo que haría el cálculo ingenuo de JavaScript.
 */
export function addMonths(date: Date, months: number): Date {
  const day = date.getDate()
  const result = new Date(date.getFullYear(), date.getMonth() + months, 1)
  const lastDay = new Date(result.getFullYear(), result.getMonth() + 1, 0).getDate()
  result.setDate(Math.min(day, lastDay))
  return result
}

export const daysBetween = (from: Date, to: Date) =>
  Math.round((to.getTime() - from.getTime()) / 86_400_000)

/**
 * @param documentDate fecha del documento, en "yyyy-mm-dd"
 */
export function usePaymentTerms(documentDate: Ref<string>) {
  const option = ref<string>(DUE_CASH)
  const customDate = ref<string>(todayIso())

  /** Todo lo que no sea contado es una venta o compra a crédito. */
  const isCredit = computed(() => option.value !== DUE_CASH)

  /** Plazo en días, que es lo que viaja al backend. */
  const creditDays = computed(() => {
    if (!isCredit.value) return 0

    const start = parseIso(documentDate.value || todayIso())

    if (option.value === DUE_CUSTOM) {
      return Math.max(daysBetween(start, parseIso(customDate.value)), 0)
    }

    const chosen = DUE_OPTIONS.find((o) => o.value === option.value)
    if (!chosen) return 0
    if (chosen.days !== undefined) return chosen.days

    return Math.max(daysBetween(start, addMonths(start, chosen.months ?? 0)), 0)
  })

  const dueDate = computed(() => {
    const start = parseIso(documentDate.value || todayIso())
    const due = new Date(start)
    due.setDate(due.getDate() + creditDays.value)
    return due
  })

  const dueDateIso = computed(() => toIso(dueDate.value))

  /**
   * Reconstruye la selección al abrir un documento existente: se busca el plazo con
   * nombre que coincida con sus días y, si ninguno cuadra, se muestra como fecha
   * específica. Así un documento pactado "a 37 días" se reabre sin perder nada.
   */
  function loadFrom(days: number, credit: boolean) {
    if (!credit || days <= 0) {
      option.value = DUE_CASH
      customDate.value = todayIso()
      return
    }

    const start = parseIso(documentDate.value || todayIso())

    const byDays = DUE_OPTIONS.find((o) => o.days === days)
    if (byDays) {
      option.value = byDays.value
      return
    }

    const byMonths = DUE_OPTIONS.find(
      (o) => o.months !== undefined && daysBetween(start, addMonths(start, o.months)) === days,
    )
    if (byMonths) {
      option.value = byMonths.value
      return
    }

    const due = new Date(start)
    due.setDate(due.getDate() + days)
    option.value = DUE_CUSTOM
    customDate.value = toIso(due)
  }

  return { option, customDate, isCredit, creditDays, dueDate, dueDateIso, loadFrom }
}

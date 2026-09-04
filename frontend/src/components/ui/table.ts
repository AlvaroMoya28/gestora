/** Definición de columna que consume DataTable. */
export interface Column {
  key: string
  label: string
  align?: 'left' | 'right' | 'center'
  width?: string
}

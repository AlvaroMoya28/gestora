import { http } from './http'
import type {
  AccountPayable,
  AuditEntry,
  AuthResponse,
  AuthenticatedUser,
  Category,
  Customer,
  DashboardData,
  InventoryMovement,
  ModuleDefinition,
  PagedResult,
  Product,
  Purchase,
  PurchaseSummary,
  Role,
  Supplier,
  Unit,
  User,
} from '@/types'

/**
 * Capa de servicios: un método por endpoint, agrupados por módulo.
 * Las vistas no conocen rutas ni verbos HTTP; solo llaman a estas funciones.
 */

export interface ListQuery {
  page?: number
  pageSize?: number
  search?: string
  active?: boolean | null
  [key: string]: unknown
}

/** Quita los parámetros vacíos para no ensuciar la URL ni confundir al backend. */
function clean(query: ListQuery = {}): Record<string, unknown> {
  return Object.fromEntries(
    Object.entries(query).filter(([, value]) => value !== undefined && value !== null && value !== ''),
  )
}

export const authApi = {
  login: (email: string, password: string) =>
    http.post<AuthResponse>('/auth/login', { email, password }).then((r) => r.data),
  logout: (refreshToken: string) => http.post('/auth/logout', { refreshToken }),
  me: () => http.get<AuthenticatedUser>('/auth/me').then((r) => r.data),
  modules: () => http.get<ModuleDefinition[]>('/auth/modules').then((r) => r.data),
  changePassword: (currentPassword: string, newPassword: string) =>
    http.post('/auth/change-password', { currentPassword, newPassword }),
}

export const dashboardApi = {
  get: () => http.get<DashboardData>('/dashboard').then((r) => r.data),
}

export const customersApi = {
  list: (query?: ListQuery) =>
    http.get<PagedResult<Customer>>('/clientes', { params: clean(query) }).then((r) => r.data),
  create: (payload: Partial<Customer>) => http.post<Customer>('/clientes', payload).then((r) => r.data),
  update: (id: number, payload: Partial<Customer>) =>
    http.put<Customer>(`/clientes/${id}`, payload).then((r) => r.data),
  setActive: (id: number, active: boolean) =>
    http.post<Customer>(`/clientes/${id}/${active ? 'activar' : 'inactivar'}`).then((r) => r.data),
}

export const suppliersApi = {
  list: (query?: ListQuery) =>
    http.get<PagedResult<Supplier>>('/proveedores', { params: clean(query) }).then((r) => r.data),
  create: (payload: Partial<Supplier>) => http.post<Supplier>('/proveedores', payload).then((r) => r.data),
  update: (id: number, payload: Partial<Supplier>) =>
    http.put<Supplier>(`/proveedores/${id}`, payload).then((r) => r.data),
  setActive: (id: number, active: boolean) =>
    http.post<Supplier>(`/proveedores/${id}/${active ? 'activar' : 'inactivar'}`).then((r) => r.data),
}

export const productsApi = {
  list: (query?: ListQuery) =>
    http.get<PagedResult<Product>>('/productos', { params: clean(query) }).then((r) => r.data),
  create: (payload: Record<string, unknown>) => http.post<Product>('/productos', payload).then((r) => r.data),
  update: (id: number, payload: Record<string, unknown>) =>
    http.put<Product>(`/productos/${id}`, payload).then((r) => r.data),
  setActive: (id: number, active: boolean) =>
    http.post<Product>(`/productos/${id}/${active ? 'activar' : 'inactivar'}`).then((r) => r.data),
}

export const lookupsApi = {
  categories: (active?: boolean) =>
    http.get<Category[]>('/categorias', { params: clean({ active }) }).then((r) => r.data),
  createCategory: (payload: { name: string; description?: string | null }) =>
    http.post<Category>('/categorias', payload).then((r) => r.data),
  units: (active?: boolean) => http.get<Unit[]>('/unidades', { params: clean({ active }) }).then((r) => r.data),
  createUnit: (payload: { name: string; abbreviation: string; decimalPlaces: number }) =>
    http.post<Unit>('/unidades', payload).then((r) => r.data),
}

export const inventoryApi = {
  movements: (query?: ListQuery) =>
    http
      .get<PagedResult<InventoryMovement>>('/inventario/movimientos', { params: clean(query) })
      .then((r) => r.data),
  createMovement: (payload: Record<string, unknown>) =>
    http.post<InventoryMovement>('/inventario/movimientos', payload).then((r) => r.data),
  adjust: (payload: { productId: number; countedStock: number; reason: string }) =>
    http.post<InventoryMovement>('/inventario/ajustes', payload).then((r) => r.data),
}

export const usersApi = {
  list: (query?: ListQuery) =>
    http.get<PagedResult<User>>('/usuarios', { params: clean(query) }).then((r) => r.data),
  create: (payload: Record<string, unknown>) => http.post<User>('/usuarios', payload).then((r) => r.data),
  update: (id: number, payload: Record<string, unknown>) =>
    http.put<User>(`/usuarios/${id}`, payload).then((r) => r.data),
  setActive: (id: number, active: boolean) =>
    http.post<User>(`/usuarios/${id}/${active ? 'activar' : 'inactivar'}`).then((r) => r.data),
  roles: () => http.get<Role[]>('/roles').then((r) => r.data),
}

export const purchasesApi = {
  list: (query?: ListQuery) =>
    http.get<PagedResult<PurchaseSummary>>('/compras', { params: clean(query) }).then((r) => r.data),
  get: (id: number) => http.get<Purchase>(`/compras/${id}`).then((r) => r.data),
  create: (payload: Record<string, unknown>) => http.post<Purchase>('/compras', payload).then((r) => r.data),
  update: (id: number, payload: Record<string, unknown>) =>
    http.put<Purchase>(`/compras/${id}`, payload).then((r) => r.data),
  confirm: (id: number) => http.post<Purchase>(`/compras/${id}/confirmar`).then((r) => r.data),
  cancel: (id: number) => http.post<Purchase>(`/compras/${id}/cancelar`).then((r) => r.data),
}

export const payablesApi = {
  list: (query?: ListQuery) =>
    http.get<PagedResult<AccountPayable>>('/cuentas-por-pagar', { params: clean(query) }).then((r) => r.data),
  get: (id: number) => http.get<AccountPayable>(`/cuentas-por-pagar/${id}`).then((r) => r.data),
  registerPayment: (id: number, payload: Record<string, unknown>) =>
    http.post<AccountPayable>(`/cuentas-por-pagar/${id}/pagos`, payload).then((r) => r.data),
}

export const auditApi = {
  list: (query?: ListQuery) =>
    http.get<PagedResult<AuditEntry>>('/auditoria', { params: clean(query) }).then((r) => r.data),
}

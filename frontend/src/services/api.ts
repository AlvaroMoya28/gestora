import { http } from './http'
import type {
  AccountPayable,
  AccountReceivable,
  AuditEntry,
  AuthResponse,
  AuthenticatedUser,
  Category,
  CompanySettings,
  Customer,
  DashboardData,
  FinanceCategory,
  FinanceEntry,
  FinanceSummary,
  InventoryMovement,
  ModuleDefinition,
  PagedResult,
  Plan,
  PlatformCompany,
  PlatformOverview,
  Product,
  ProductionOrder,
  ProductionOrderSummary,
  Purchase,
  PurchaseSummary,
  Recipe,
  RegisterCompanyResult,
  RepairOrder,
  RepairOrderSummary,
  Report,
  ReportDefinition,
  Role,
  Sale,
  SaleSummary,
  Subscription,
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

  /** Solo el desarrollador: cambia la sesión a la vista de una empresa. */
  viewAsCompany: (companyId: number) =>
    http.post<AuthResponse>(`/auth/ver-como/${companyId}`).then((r) => r.data),
  backToPlatform: () => http.post<AuthResponse>('/auth/volver-a-plataforma').then((r) => r.data),
}

/** Administración de Gestora como producto. Ningún usuario de empresa llega acá. */
export const platformApi = {
  overview: () => http.get<PlatformOverview>('/plataforma/resumen').then((r) => r.data),

  companies: (query?: ListQuery) =>
    http.get<PagedResult<PlatformCompany>>('/plataforma/empresas', { params: clean(query) }).then((r) => r.data),
  company: (id: number) => http.get<PlatformCompany>(`/plataforma/empresas/${id}`).then((r) => r.data),
  registerCompany: (payload: Record<string, unknown>) =>
    http.post<RegisterCompanyResult>('/plataforma/empresas', payload).then((r) => r.data),
  updateCompany: (id: number, payload: Record<string, unknown>) =>
    http.put<PlatformCompany>(`/plataforma/empresas/${id}`, payload).then((r) => r.data),
  suspendCompany: (id: number, motivo?: string) =>
    http.post<PlatformCompany>(`/plataforma/empresas/${id}/suspender`, null, { params: clean({ motivo }) }).then((r) => r.data),
  reactivateCompany: (id: number) =>
    http.post<PlatformCompany>(`/plataforma/empresas/${id}/reactivar`).then((r) => r.data),
  cancelCompany: (id: number, motivo?: string) =>
    http.post<PlatformCompany>(`/plataforma/empresas/${id}/dar-de-baja`, null, { params: clean({ motivo }) }).then((r) => r.data),

  subscriptions: (query?: ListQuery) =>
    http.get<PagedResult<Subscription>>('/plataforma/suscripciones', { params: clean(query) }).then((r) => r.data),
  subscription: (id: number) =>
    http.get<Subscription>(`/plataforma/suscripciones/${id}`).then((r) => r.data),
  registerCharge: (id: number, payload: Record<string, unknown>) =>
    http.post<Subscription>(`/plataforma/suscripciones/${id}/cobros`, payload).then((r) => r.data),
  changePlan: (id: number, payload: Record<string, unknown>) =>
    http.post<Subscription>(`/plataforma/suscripciones/${id}/cambiar-plan`, payload).then((r) => r.data),
  cancelSubscription: (id: number, motivo?: string) =>
    http.post<Subscription>(`/plataforma/suscripciones/${id}/dar-de-baja`, null, { params: clean({ motivo }) }).then((r) => r.data),

  plans: (active?: boolean) =>
    http.get<Plan[]>('/plataforma/planes', { params: clean({ active }) }).then((r) => r.data),
  createPlan: (payload: Record<string, unknown>) =>
    http.post<Plan>('/plataforma/planes', payload).then((r) => r.data),
  updatePlan: (id: number, payload: Record<string, unknown>) =>
    http.put<Plan>(`/plataforma/planes/${id}`, payload).then((r) => r.data),
  setPlanActive: (id: number, active: boolean) =>
    http.post<Plan>(`/plataforma/planes/${id}/estado`, null, { params: { active } }).then((r) => r.data),
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

export const salesApi = {
  list: (query?: ListQuery) =>
    http.get<PagedResult<SaleSummary>>('/ventas', { params: clean(query) }).then((r) => r.data),
  get: (id: number) => http.get<Sale>(`/ventas/${id}`).then((r) => r.data),
  create: (payload: Record<string, unknown>) => http.post<Sale>('/ventas', payload).then((r) => r.data),
  update: (id: number, payload: Record<string, unknown>) =>
    http.put<Sale>(`/ventas/${id}`, payload).then((r) => r.data),
  confirm: (id: number) => http.post<Sale>(`/ventas/${id}/confirmar`).then((r) => r.data),
  cancel: (id: number) => http.post<Sale>(`/ventas/${id}/cancelar`).then((r) => r.data),
}

export const receivablesApi = {
  list: (query?: ListQuery) =>
    http.get<PagedResult<AccountReceivable>>('/cuentas-por-cobrar', { params: clean(query) }).then((r) => r.data),
  get: (id: number) => http.get<AccountReceivable>(`/cuentas-por-cobrar/${id}`).then((r) => r.data),
  registerReceipt: (id: number, payload: Record<string, unknown>) =>
    http.post<AccountReceivable>(`/cuentas-por-cobrar/${id}/cobros`, payload).then((r) => r.data),
}

export const productionApi = {
  list: (query?: ListQuery) =>
    http.get<PagedResult<ProductionOrderSummary>>('/produccion/ordenes', { params: clean(query) }).then((r) => r.data),
  get: (id: number) => http.get<ProductionOrder>(`/produccion/ordenes/${id}`).then((r) => r.data),
  create: (payload: Record<string, unknown>) =>
    http.post<ProductionOrder>('/produccion/ordenes', payload).then((r) => r.data),
  update: (id: number, payload: Record<string, unknown>) =>
    http.put<ProductionOrder>(`/produccion/ordenes/${id}`, payload).then((r) => r.data),
  start: (id: number) => http.post<ProductionOrder>(`/produccion/ordenes/${id}/iniciar`).then((r) => r.data),
  complete: (id: number, payload: Record<string, unknown>) =>
    http.post<ProductionOrder>(`/produccion/ordenes/${id}/terminar`, payload).then((r) => r.data),
  cancel: (id: number) => http.post<ProductionOrder>(`/produccion/ordenes/${id}/cancelar`).then((r) => r.data),

  recipes: (active?: boolean) =>
    http.get<Recipe[]>('/produccion/recetas', { params: clean({ active }) }).then((r) => r.data),
  recipe: (id: number) => http.get<Recipe>(`/produccion/recetas/${id}`).then((r) => r.data),
  createRecipe: (payload: Record<string, unknown>) =>
    http.post<Recipe>('/produccion/recetas', payload).then((r) => r.data),
  updateRecipe: (id: number, payload: Record<string, unknown>) =>
    http.put<Recipe>(`/produccion/recetas/${id}`, payload).then((r) => r.data),
  setRecipeActive: (id: number, active: boolean) =>
    http.post<Recipe>(`/produccion/recetas/${id}/${active ? 'activar' : 'inactivar'}`).then((r) => r.data),
}

export const repairsApi = {
  list: (query?: ListQuery) =>
    http.get<PagedResult<RepairOrderSummary>>('/reparaciones', { params: clean(query) }).then((r) => r.data),
  get: (id: number) => http.get<RepairOrder>(`/reparaciones/${id}`).then((r) => r.data),
  create: (payload: Record<string, unknown>) =>
    http.post<RepairOrder>('/reparaciones', payload).then((r) => r.data),
  update: (id: number, payload: Record<string, unknown>) =>
    http.put<RepairOrder>(`/reparaciones/${id}`, payload).then((r) => r.data),
  start: (id: number) => http.post<RepairOrder>(`/reparaciones/${id}/iniciar`).then((r) => r.data),
  complete: (id: number, payload: Record<string, unknown>) =>
    http.post<RepairOrder>(`/reparaciones/${id}/terminar`, payload).then((r) => r.data),
  deliver: (id: number) => http.post<RepairOrder>(`/reparaciones/${id}/entregar`).then((r) => r.data),
  cancel: (id: number) => http.post<RepairOrder>(`/reparaciones/${id}/cancelar`).then((r) => r.data),
}

/**
 * Ingresos y gastos comparten forma: una sola fábrica evita escribir dos veces el
 * mismo bloque, y cada módulo conserva su ruta y sus permisos en el backend.
 */
function financeApi(base: 'ingresos' | 'gastos') {
  return {
    list: (query?: ListQuery) =>
      http.get<PagedResult<FinanceEntry>>(`/${base}`, { params: clean(query) }).then((r) => r.data),
    summary: (query?: ListQuery) =>
      http.get<FinanceSummary>(`/${base}/resumen`, { params: clean(query) }).then((r) => r.data),
    create: (payload: Record<string, unknown>) =>
      http.post<FinanceEntry>(`/${base}`, payload).then((r) => r.data),
    update: (id: number, payload: Record<string, unknown>) =>
      http.put<FinanceEntry>(`/${base}/${id}`, payload).then((r) => r.data),
    remove: (id: number) => http.delete(`/${base}/${id}`),
    categories: (active?: boolean) =>
      http.get<FinanceCategory[]>(`/${base}/categorias`, { params: clean({ active }) }).then((r) => r.data),
  }
}

export const incomeApi = financeApi('ingresos')
export const expensesApi = financeApi('gastos')

export const financeCategoriesApi = {
  list: (kind?: number, active?: boolean) =>
    http.get<FinanceCategory[]>('/finanzas/categorias', { params: clean({ kind, active }) }).then((r) => r.data),
  create: (payload: Record<string, unknown>) =>
    http.post<FinanceCategory>('/finanzas/categorias', payload).then((r) => r.data),
  update: (id: number, payload: Record<string, unknown>) =>
    http.put<FinanceCategory>(`/finanzas/categorias/${id}`, payload).then((r) => r.data),
  setActive: (id: number, active: boolean) =>
    http.post<FinanceCategory>(`/finanzas/categorias/${id}/${active ? 'activar' : 'inactivar'}`).then((r) => r.data),
}

export const reportsApi = {
  available: () => http.get<ReportDefinition[]>('/reportes').then((r) => r.data),
  get: (key: string, params?: { from?: string; to?: string }) =>
    http.get<Report>(`/reportes/${key}`, { params: clean(params) }).then((r) => r.data),
  /** Descarga el CSV como blob: el navegador no puede seguir la URL sin el token. */
  csv: (key: string, params?: { from?: string; to?: string }) =>
    http.get<Blob>(`/reportes/${key}/csv`, { params: clean(params), responseType: 'blob' }).then((r) => r.data),
}

export const settingsApi = {
  get: () => http.get<CompanySettings>('/configuracion').then((r) => r.data),
  update: (payload: Record<string, unknown>) =>
    http.put<CompanySettings>('/configuracion', payload).then((r) => r.data),
}

export const auditApi = {
  list: (query?: ListQuery) =>
    http.get<PagedResult<AuditEntry>>('/auditoria', { params: clean(query) }).then((r) => r.data),
}

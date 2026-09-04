/** Contratos compartidos con la API. Un único lugar donde se declara la forma de los datos. */

export interface PagedResult<T> {
  items: T[]
  total: number
  page: number
  pageSize: number
  totalPages: number
}

export interface ApiErrorBody {
  message: string
  errors?: Record<string, string[]>
  traceId?: string
}

// ------------------------------------------------------------------ Sesión ----

export interface ModulePermission {
  key: string
  name: string
  group: string
  icon: string
  available: boolean
  canWrite: boolean
}

export interface AuthenticatedUser {
  id: number
  firstName: string
  lastName: string
  fullName: string
  email: string
  phone: string | null
  roleId: number
  roleName: string
  companyId: number
  companyName: string
  currency: string
  modules: ModulePermission[]
}

export interface AuthResponse {
  accessToken: string
  refreshToken: string
  expiresInSeconds: number
  user: AuthenticatedUser
}

// --------------------------------------------------------------- Catálogo ----

/** Debe coincidir con Domain.PaymentTerm del backend. */
export const PaymentTerm = { Cash: 0, Credit: 1 } as const
export type PaymentTermValue = (typeof PaymentTerm)[keyof typeof PaymentTerm]

/** Debe coincidir con Domain.ProductType del backend. */
export const ProductType = { RawMaterial: 0, FinishedGood: 1, Service: 2 } as const
export type ProductTypeValue = (typeof ProductType)[keyof typeof ProductType]

export const productTypeLabels: Record<number, string> = {
  0: 'Materia prima',
  1: 'Producto terminado',
  2: 'Servicio',
}

export interface Customer {
  id: number
  code: string
  name: string
  tradeName: string | null
  taxId: string | null
  phone: string | null
  email: string | null
  address: string | null
  contactName: string | null
  paymentTerm: PaymentTermValue
  creditDays: number
  creditLimit: number
  notes: string | null
  isActive: boolean
}

export interface Supplier {
  id: number
  code: string
  name: string
  tradeName: string | null
  taxId: string | null
  phone: string | null
  email: string | null
  address: string | null
  contactName: string | null
  paymentTerm: PaymentTermValue
  creditDays: number
  notes: string | null
  isActive: boolean
}

export interface Product {
  id: number
  code: string
  name: string
  description: string | null
  barcode: string | null
  type: ProductTypeValue
  categoryId: number | null
  categoryName: string | null
  unitId: number
  unitName: string
  unitAbbreviation: string
  cost: number
  price: number
  taxRate: number
  stock: number
  minStock: number
  isActive: boolean
  stockStatus: 'normal' | 'bajo' | 'agotado'
}

export interface Category {
  id: number
  name: string
  description: string | null
  isActive: boolean
  productCount: number
}

export interface Unit {
  id: number
  name: string
  abbreviation: string
  decimalPlaces: number
  isActive: boolean
  productCount: number
}

// -------------------------------------------------------------- Inventario ----

export const MovementType = {
  Purchase: 1,
  Sale: 2,
  ProductionInput: 3,
  ProductionOutput: 4,
  Adjustment: 5,
  ReturnIn: 6,
  ReturnOut: 7,
  RepairConsumption: 8,
  InitialStock: 9,
} as const
export type MovementTypeValue = (typeof MovementType)[keyof typeof MovementType]

export interface InventoryMovement {
  id: number
  productId: number
  productCode: string
  productName: string
  type: MovementTypeValue
  typeName: string
  direction: number
  quantity: number
  stockAfter: number
  unitCost: number | null
  reason: string | null
  referenceType: string | null
  referenceId: number | null
  occurredAt: string
  userName: string | null
}

// ------------------------------------------------------ Usuarios y roles ----

export interface User {
  id: number
  firstName: string
  lastName: string
  fullName: string
  email: string
  phone: string | null
  roleId: number
  roleName: string
  isActive: boolean
  lastLoginAt: string | null
  createdAt: string
}

export interface RolePermission {
  moduleKey: string
  canRead: boolean
  canWrite: boolean
}

export interface Role {
  id: number
  name: string
  description: string | null
  isSystem: boolean
  isActive: boolean
  userCount: number
  permissions: RolePermission[]
}

export interface ModuleDefinition {
  key: string
  name: string
  group: string
  icon: string
  available: boolean
}

// --------------------------------------------------------------- Compras ----

/** Debe coincidir con Domain.PurchaseStatus del backend. */
export const PurchaseStatus = { Draft: 0, Confirmed: 1, Cancelled: 2 } as const
export type PurchaseStatusValue = (typeof PurchaseStatus)[keyof typeof PurchaseStatus]

export interface PurchaseItem {
  id: number
  productId: number
  productCode: string
  productName: string
  unitAbbreviation: string
  quantity: number
  unitCost: number
  taxRate: number
  subtotal: number
}

export interface Purchase {
  id: number
  number: string
  supplierId: number
  supplierName: string
  date: string
  status: PurchaseStatusValue
  statusName: string
  subtotal: number
  taxAmount: number
  total: number
  supplierInvoiceNumber: string | null
  notes: string | null
  items: PurchaseItem[]
}

export interface PurchaseSummary {
  id: number
  number: string
  supplierName: string
  date: string
  status: PurchaseStatusValue
  statusName: string
  total: number
}

// ------------------------------------------------------ Cuentas por pagar ----

/** Debe coincidir con Domain.PayableStatus del backend. */
export const PayableStatus = { Pending: 0, PartiallyPaid: 1, Paid: 2, Cancelled: 3 } as const
export type PayableStatusValue = (typeof PayableStatus)[keyof typeof PayableStatus]

export interface Payment {
  id: number
  date: string
  amount: number
  method: string
  reference: string | null
  notes: string | null
  userName: string | null
}

export interface AccountPayable {
  id: number
  documentNumber: string
  supplierId: number
  supplierName: string
  purchaseId: number | null
  purchaseNumber: string | null
  issueDate: string
  dueDate: string
  total: number
  paidAmount: number
  balance: number
  status: PayableStatusValue
  statusName: string
  isOverdue: boolean
  notes: string | null
  payments: Payment[]
}

// ------------------------------------------------------------- Dashboard ----

export interface Metric {
  key: string
  label: string
  value: number
  format: 'money' | 'integer' | 'percent'
  hint: string | null
}

export interface DashboardAlert {
  level: 'info' | 'warning' | 'danger'
  title: string
  detail: string
  moduleKey: string | null
}

export interface RecentActivity {
  action: string
  module: string
  description: string
  userName: string
  occurredAt: string
}

export interface LowStockItem {
  productId: number
  code: string
  name: string
  stock: number
  minStock: number
  unit: string
}

export interface DashboardData {
  metrics: Metric[]
  alerts: DashboardAlert[]
  recentActivity: RecentActivity[]
  lowStock: LowStockItem[]
}

// -------------------------------------------------------------- Auditoría ----

export interface AuditEntry {
  id: number
  userName: string
  action: string
  module: string
  entityName: string | null
  entityId: number | null
  description: string | null
  oldValue: string | null
  newValue: string | null
  ipAddress: string | null
  occurredAt: string
}

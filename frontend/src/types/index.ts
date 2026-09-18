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

/** Dónde vive la sesión: la plataforma Gestora o una empresa suscrita. */
export type Scope = 'Platform' | 'Company'

export const RoleKeys = {
  Developer: 'developer',
  PlatformAdmin: 'platform_admin',
  CompanyAdmin: 'company_admin',
  CompanyViewer: 'company_viewer',
} as const

export interface AuthenticatedUser {
  id: number
  firstName: string
  lastName: string
  fullName: string
  email: string
  phone: string | null
  roleId: number
  roleKey: string
  roleName: string
  scope: Scope
  /** Empresa que se está viendo. 0 cuando la sesión es de plataforma. */
  companyId: number
  companyName: string | null
  currency: string
  /** El desarrollador está viendo el sistema como una empresa. */
  isImpersonating: boolean
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
  roleKey: string
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

/** Catálogo fijo de la plataforma: una empresa solo puede asignar los de su alcance. */
export interface Role {
  id: number
  key: string
  name: string
  description: string | null
  scope: Scope
  userCount: number
  permissions: RolePermission[]
}

export interface ModuleDefinition {
  key: string
  name: string
  group: string
  icon: string
  available: boolean
  scope: Scope
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
  paymentTerm: PaymentTermValue
  creditDays: number
  dueDate: string
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
  dueDate: string
  status: PurchaseStatusValue
  statusName: string
  paymentTerm: PaymentTermValue
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

// ---------------------------------------------------------------- Ventas ----

/** Debe coincidir con Domain.SaleStatus del backend. */
export const SaleStatus = { Draft: 0, Confirmed: 1, Cancelled: 2 } as const
export type SaleStatusValue = (typeof SaleStatus)[keyof typeof SaleStatus]

export interface SaleItem {
  id: number
  productId: number
  productCode: string
  productName: string
  unitAbbreviation: string
  quantity: number
  unitPrice: number
  discountRate: number
  taxRate: number
  subtotal: number
}

export interface Sale {
  id: number
  number: string
  customerId: number
  customerName: string
  date: string
  status: SaleStatusValue
  statusName: string
  paymentTerm: PaymentTermValue
  creditDays: number
  dueDate: string
  subtotal: number
  discountAmount: number
  taxAmount: number
  total: number
  notes: string | null
  items: SaleItem[]
}

export interface SaleSummary {
  id: number
  number: string
  customerName: string
  date: string
  dueDate: string
  status: SaleStatusValue
  statusName: string
  paymentTerm: PaymentTermValue
  total: number
}

// ----------------------------------------------------- Cuentas por cobrar ----

/** Debe coincidir con Domain.ReceivableStatus del backend. */
export const ReceivableStatus = {
  Pending: 0,
  PartiallyCollected: 1,
  Collected: 2,
  Cancelled: 3,
} as const
export type ReceivableStatusValue = (typeof ReceivableStatus)[keyof typeof ReceivableStatus]

export interface Receipt {
  id: number
  date: string
  amount: number
  method: string
  reference: string | null
  notes: string | null
  userName: string | null
}

export interface AccountReceivable {
  id: number
  documentNumber: string
  customerId: number
  customerName: string
  saleId: number | null
  saleNumber: string | null
  repairOrderId: number | null
  repairNumber: string | null
  issueDate: string
  dueDate: string
  total: number
  collectedAmount: number
  balance: number
  status: ReceivableStatusValue
  statusName: string
  isOverdue: boolean
  notes: string | null
  receipts: Receipt[]
}

// ------------------------------------------------------------ Producción ----

/** Debe coincidir con Domain.ProductionStatus del backend. */
export const ProductionStatus = { Planned: 0, InProgress: 1, Completed: 2, Cancelled: 3 } as const
export type ProductionStatusValue = (typeof ProductionStatus)[keyof typeof ProductionStatus]

export interface RecipeItem {
  id: number
  productId: number
  productCode: string
  productName: string
  unitAbbreviation: string
  quantity: number
  unitCost: number
  subtotal: number
  stock: number
}

export interface Recipe {
  id: number
  name: string
  productId: number
  productCode: string
  productName: string
  unitAbbreviation: string
  outputQuantity: number
  laborCost: number
  materialsCost: number
  unitCost: number
  notes: string | null
  isActive: boolean
  items: RecipeItem[]
}

export interface ProductionMaterial {
  id: number
  productId: number
  productCode: string
  productName: string
  unitAbbreviation: string
  plannedQuantity: number
  consumedQuantity: number
  unitCost: number
  stock: number
  hasEnoughStock: boolean
}

export interface ProductionOrder {
  id: number
  number: string
  productId: number
  productCode: string
  productName: string
  unitAbbreviation: string
  recipeId: number | null
  recipeName: string | null
  quantity: number
  producedQuantity: number
  status: ProductionStatusValue
  statusName: string
  date: string
  startedAt: string | null
  completedAt: string | null
  laborCost: number
  materialsCost: number
  totalCost: number
  unitCost: number
  notes: string | null
  canStart: boolean
  canComplete: boolean
  materials: ProductionMaterial[]
}

export interface ProductionOrderSummary {
  id: number
  number: string
  productName: string
  quantity: number
  producedQuantity: number
  status: ProductionStatusValue
  statusName: string
  date: string
  totalCost: number
}

// ---------------------------------------------------------- Reparaciones ----

/** Debe coincidir con Domain.RepairStatus del backend. */
export const RepairStatus = {
  Received: 0,
  InProgress: 1,
  Ready: 2,
  Delivered: 3,
  Cancelled: 4,
} as const
export type RepairStatusValue = (typeof RepairStatus)[keyof typeof RepairStatus]

export interface RepairMaterial {
  id: number
  productId: number
  productCode: string
  productName: string
  unitAbbreviation: string
  quantity: number
  unitPrice: number
  subtotal: number
  stock: number
}

export interface RepairOrder {
  id: number
  number: string
  customerId: number
  customerName: string
  itemDescription: string
  reportedIssue: string | null
  diagnosis: string | null
  status: RepairStatusValue
  statusName: string
  receivedAt: string
  promisedAt: string | null
  completedAt: string | null
  deliveredAt: string | null
  laborCost: number
  materialsCost: number
  taxRate: number
  taxAmount: number
  total: number
  paymentTerm: PaymentTermValue
  creditDays: number
  notes: string | null
  isLate: boolean
  materials: RepairMaterial[]
}

export interface RepairOrderSummary {
  id: number
  number: string
  customerName: string
  itemDescription: string
  status: RepairStatusValue
  statusName: string
  receivedAt: string
  promisedAt: string | null
  total: number
  isLate: boolean
}

// ------------------------------------------------------ Ingresos y gastos ----

/** Debe coincidir con Domain.FinanceKind del backend. */
export const FinanceKind = { Income: 0, Expense: 1 } as const
export type FinanceKindValue = (typeof FinanceKind)[keyof typeof FinanceKind]

/** Debe coincidir con Domain.FinanceSource del backend. */
export const FinanceSource = { Manual: 0, Receipt: 1, Payment: 2 } as const
export type FinanceSourceValue = (typeof FinanceSource)[keyof typeof FinanceSource]

export interface FinanceCategory {
  id: number
  name: string
  kind: FinanceKindValue
  kindName: string
  description: string | null
  isSystem: boolean
  isActive: boolean
  entryCount: number
}

export interface FinanceEntry {
  id: number
  kind: FinanceKindValue
  kindName: string
  categoryId: number
  categoryName: string
  date: string
  amount: number
  description: string
  method: string
  reference: string | null
  notes: string | null
  source: FinanceSourceValue
  sourceName: string
  sourceId: number | null
  isManual: boolean
  userName: string | null
  createdAt: string
}

export interface FinanceCategoryTotal {
  categoryId: number
  categoryName: string
  total: number
  count: number
}

export interface FinanceSummary {
  total: number
  monthTotal: number
  count: number
  byCategory: FinanceCategoryTotal[]
}

// --------------------------------------------------------------- Reportes ----

export interface ReportDefinition {
  key: string
  name: string
  description: string
  group: string
  needsPeriod: boolean
}

export interface ReportColumn {
  key: string
  label: string
  /** Cómo formatear la celda: money, decimal, integer, percent, date o text. */
  format: string
  align: 'left' | 'right' | 'center'
}

export interface ReportPoint {
  label: string
  value: number
}

export interface Report {
  key: string
  title: string
  description: string
  from: string
  to: string
  needsPeriod: boolean
  metrics: Metric[]
  columns: ReportColumn[]
  rows: Record<string, unknown>[]
  series: ReportPoint[]
  seriesLabel: string | null
}

// ---------------------------------------------------------- Configuración ----

export interface CompanyStats {
  users: number
  customers: number
  suppliers: number
  products: number
  sales: number
  purchases: number
  repairs: number
  productionOrders: number
}

export interface CompanySettings {
  id: number
  name: string
  taxId: string | null
  phone: string | null
  accountEmail: string
  address: string | null
  currency: string
  defaultTaxRate: number
  statusName: string
  createdAt: string
  stats: CompanyStats
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

// =========================================================== PLATAFORMA ===
// Gestora como producto: empresas suscritas, planes y cobros. Nada de esto lo
// ve un usuario de empresa.

export const CompanyStatus = { Active: 0, Suspended: 1, Cancelled: 2 } as const
export type CompanyStatusValue = (typeof CompanyStatus)[keyof typeof CompanyStatus]

export const SubscriptionStatus = { Trial: 0, Active: 1, Expired: 2, Cancelled: 3 } as const
export type SubscriptionStatusValue = (typeof SubscriptionStatus)[keyof typeof SubscriptionStatus]

export interface SubscriptionSummary {
  id: number
  planId: number
  planName: string
  startDate: string
  endDate: string
  status: SubscriptionStatusValue
  statusName: string
  price: number
  daysToExpiry: number
  isExpired: boolean
}

export interface PlatformCompany {
  id: number
  name: string
  accountEmail: string
  taxId: string | null
  phone: string | null
  address: string | null
  currency: string
  status: CompanyStatusValue
  statusName: string
  notes: string | null
  createdAt: string
  userCount: number
  subscription: SubscriptionSummary | null
}

/** La contraseña temporal llega una única vez, al dar de alta la empresa. */
export interface RegisterCompanyResult {
  company: PlatformCompany
  adminEmail: string
  temporaryPassword: string | null
}

export interface Plan {
  id: number
  name: string
  description: string | null
  price: number
  currency: string
  billingPeriodMonths: number
  maxUsers: number
  isActive: boolean
  sortOrder: number
  companyCount: number
}

export interface SubscriptionPayment {
  id: number
  date: string
  amount: number
  method: string
  reference: string | null
  periodFrom: string
  periodTo: string
  notes: string | null
  userName: string | null
}

export interface Subscription {
  id: number
  companyId: number
  companyName: string
  accountEmail: string
  planId: number
  planName: string
  startDate: string
  endDate: string
  status: SubscriptionStatusValue
  statusName: string
  price: number
  daysToExpiry: number
  isExpired: boolean
  notes: string | null
  payments: SubscriptionPayment[]
}

export interface PlatformOverview {
  metrics: Metric[]
  expiringSoon: Subscription[]
  recentCompanies: PlatformCompany[]
}

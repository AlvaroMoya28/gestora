import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import { useAuthStore } from '@/stores/auth'

/**
 * Cada ruta declara el módulo que exige en `meta.module`. La guarda compara ese
 * valor con los permisos que el backend puso en la sesión, así que la navegación
 * y la autorización del servidor se apoyan en la misma lista de módulos.
 */
const routes: RouteRecordRaw[] = [
  {
    path: '/login',
    name: 'login',
    component: () => import('@/views/LoginView.vue'),
    meta: { public: true },
  },
  {
    path: '/',
    component: AppLayout,
    children: [
      // La raíz manda a cada quien a su mundo: plataforma o empresa.
      { path: '', name: 'home', redirect: () => ({ name: useAuthStore().homeRoute }) },

      // --- Plataforma: la administración de Gestora ---------------------------
      {
        path: 'gestora',
        name: 'platform_overview',
        component: () => import('@/views/platform/PlatformOverviewView.vue'),
        meta: { module: 'platform_overview', title: 'Resumen de Gestora' },
      },
      {
        path: 'gestora/empresas',
        name: 'platform_companies',
        component: () => import('@/views/platform/PlatformCompaniesView.vue'),
        meta: { module: 'platform_companies', title: 'Empresas' },
      },
      {
        path: 'gestora/suscripciones',
        name: 'platform_billing',
        component: () => import('@/views/platform/PlatformBillingView.vue'),
        meta: { module: 'platform_billing', title: 'Suscripciones' },
      },
      {
        path: 'gestora/planes',
        name: 'platform_plans',
        component: () => import('@/views/platform/PlatformPlansView.vue'),
        meta: { module: 'platform_plans', title: 'Planes' },
      },
      {
        path: 'gestora/bitacora',
        name: 'platform_audit',
        component: () => import('@/views/AuditView.vue'),
        meta: { module: 'platform_audit', title: 'Bitácora de la plataforma' },
      },

      // --- Empresa: la operación diaria del cliente ---------------------------
      {
        path: 'panel',
        name: 'dashboard',
        component: () => import('@/views/DashboardView.vue'),
        meta: { module: 'dashboard', title: 'Panel' },
      },
      {
        path: 'clientes',
        name: 'customers',
        component: () => import('@/views/CustomersView.vue'),
        meta: { module: 'customers', title: 'Clientes' },
      },
      {
        path: 'proveedores',
        name: 'suppliers',
        component: () => import('@/views/SuppliersView.vue'),
        meta: { module: 'suppliers', title: 'Proveedores' },
      },
      {
        path: 'productos',
        name: 'products',
        component: () => import('@/views/ProductsView.vue'),
        meta: { module: 'products', title: 'Productos' },
      },
      {
        path: 'inventario',
        name: 'inventory',
        component: () => import('@/views/InventoryView.vue'),
        meta: { module: 'inventory', title: 'Inventario' },
      },
      {
        path: 'usuarios',
        name: 'users',
        component: () => import('@/views/UsersView.vue'),
        meta: { module: 'users', title: 'Usuarios y roles' },
      },
      {
        path: 'auditoria',
        name: 'audit',
        component: () => import('@/views/AuditView.vue'),
        meta: { module: 'audit', title: 'Auditoría' },
      },
      {
        path: 'mi-cuenta',
        name: 'profile',
        component: () => import('@/views/ProfileView.vue'),
        meta: { title: 'Mi cuenta' },
      },

      {
        path: 'compras',
        name: 'purchases',
        component: () => import('@/views/PurchasesView.vue'),
        meta: { module: 'purchases', title: 'Compras' },
      },
      {
        path: 'cuentas-por-pagar',
        name: 'payables',
        component: () => import('@/views/PayablesView.vue'),
        meta: { module: 'payables', title: 'Cuentas por pagar' },
      },

      {
        path: 'ventas',
        name: 'sales',
        component: () => import('@/views/SalesView.vue'),
        meta: { module: 'sales', title: 'Ventas' },
      },
      {
        path: 'cuentas-por-cobrar',
        name: 'receivables',
        component: () => import('@/views/ReceivablesView.vue'),
        meta: { module: 'receivables', title: 'Cuentas por cobrar' },
      },
      {
        path: 'produccion',
        name: 'production',
        component: () => import('@/views/ProductionView.vue'),
        meta: { module: 'production', title: 'Producción' },
      },
      {
        path: 'reparaciones',
        name: 'repairs',
        component: () => import('@/views/RepairsView.vue'),
        meta: { module: 'repairs', title: 'Reparaciones' },
      },

      // Ingresos y gastos son la misma pantalla con distinto signo: se le pasa cuál
      // por props, en vez de duplicar el componente.
      {
        path: 'ingresos',
        name: 'income',
        component: () => import('@/views/FinanceLedgerView.vue'),
        props: { kind: 'income' },
        meta: { module: 'income', title: 'Ingresos' },
      },
      {
        path: 'gastos',
        name: 'expenses',
        component: () => import('@/views/FinanceLedgerView.vue'),
        props: { kind: 'expense' },
        meta: { module: 'expenses', title: 'Gastos' },
      },

      {
        path: 'reportes',
        name: 'reports',
        component: () => import('@/views/ReportsView.vue'),
        meta: { module: 'reports', title: 'Reportes' },
      },
      {
        path: 'configuracion',
        name: 'settings',
        component: () => import('@/views/SettingsView.vue'),
        meta: { module: 'settings', title: 'Configuración' },
      },
    ],
  },
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('@/views/NotFoundView.vue'),
    meta: { public: true },
  },
]

export const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior: () => ({ top: 0 }),
})

/**
 * El perfil en caché se revalida una sola vez por carga de la página.
 *
 * Sin esto, el perfil guardado en localStorage se daba por bueno indefinidamente: un
 * permiso revocado, un rol cambiado o un módulo nuevo no se veían hasta cerrar sesión.
 * Es una petición por visita, y garantiza que el menú y las guardas trabajen con lo
 * que el backend dice hoy, no con lo que dijo la última vez que alguien inició sesión.
 */
let profileRevalidated = false

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (to.meta.public) {
    // Quien ya tiene sesión no debería quedarse en el login.
    if (to.name === 'login' && auth.isAuthenticated) return { name: auth.homeRoute }
    return true
  }

  if (!auth.isAuthenticated) {
    return { name: 'login', query: to.fullPath !== '/' ? { redirect: to.fullPath } : undefined }
  }

  if (!auth.user || !profileRevalidated) {
    const restored = await auth.restore()
    profileRevalidated = true
    if (!restored) return { name: 'login' }
  }

  // El permiso de módulo decide todo: un usuario de empresa no tiene los módulos de
  // plataforma y viceversa, así que basta con esta comprobación para separar los mundos.
  const required = to.meta.module as string | undefined
  if (required && !auth.canRead(required)) {
    return { name: auth.homeRoute }
  }

  return true
})

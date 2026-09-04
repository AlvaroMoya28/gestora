import { createRouter, createWebHistory, type RouteRecordRaw } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'
import { useAuthStore } from '@/stores/auth'

const ComingSoon = () => import('@/views/ComingSoonView.vue')

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
      { path: '', redirect: { name: 'dashboard' } },
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

      // Módulos con el modelo y la navegación ya definidos, pendientes de
      // implementarse en las siguientes etapas del plan.
      { path: 'compras', name: 'purchases', component: ComingSoon, meta: { module: 'purchases', title: 'Compras' } },
      { path: 'ventas', name: 'sales', component: ComingSoon, meta: { module: 'sales', title: 'Ventas' } },
      { path: 'produccion', name: 'production', component: ComingSoon, meta: { module: 'production', title: 'Producción' } },
      { path: 'reparaciones', name: 'repairs', component: ComingSoon, meta: { module: 'repairs', title: 'Reparaciones' } },
      { path: 'cuentas-por-cobrar', name: 'receivables', component: ComingSoon, meta: { module: 'receivables', title: 'Cuentas por cobrar' } },
      { path: 'cuentas-por-pagar', name: 'payables', component: ComingSoon, meta: { module: 'payables', title: 'Cuentas por pagar' } },
      { path: 'ingresos', name: 'income', component: ComingSoon, meta: { module: 'income', title: 'Ingresos' } },
      { path: 'gastos', name: 'expenses', component: ComingSoon, meta: { module: 'expenses', title: 'Gastos' } },
      { path: 'reportes', name: 'reports', component: ComingSoon, meta: { module: 'reports', title: 'Reportes' } },
      { path: 'configuracion', name: 'settings', component: ComingSoon, meta: { module: 'settings', title: 'Configuración' } },
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

router.beforeEach(async (to) => {
  const auth = useAuthStore()

  if (to.meta.public) {
    // Quien ya tiene sesión no debería quedarse en el login.
    if (to.name === 'login' && auth.isAuthenticated) return { name: 'dashboard' }
    return true
  }

  if (!auth.isAuthenticated) {
    return { name: 'login', query: to.fullPath !== '/' ? { redirect: to.fullPath } : undefined }
  }

  // Al recargar la página el perfil viene de localStorage: se revalida una vez
  // contra el backend para no confiar en permisos que pudieron cambiar.
  if (!auth.user) {
    const restored = await auth.restore()
    if (!restored) return { name: 'login' }
  }

  const required = to.meta.module as string | undefined
  if (required && !auth.canRead(required)) {
    return { name: 'dashboard' }
  }

  return true
})

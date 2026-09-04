<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppIcon from '@/components/ui/AppIcon.vue'
import { useAuthStore } from '@/stores/auth'

/**
 * Estructura de la aplicación: barra lateral, encabezado y área de contenido.
 * El menú se dibuja con los módulos que envió el backend, nunca con una lista fija:
 * lo que un usuario ve es exactamente lo que su rol le permite abrir.
 */
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()

const sidebarOpen = ref(false)
const profileOpen = ref(false)

const currentTitle = computed(() => (route.meta.title as string) ?? 'Gestora')

const initials = computed(() => {
  const user = auth.user
  if (!user) return '?'
  return `${user.firstName.charAt(0)}${user.lastName.charAt(0)}`.toUpperCase()
})

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="shell">
    <aside :class="['sidebar', { open: sidebarOpen }]">
      <div class="brand">
        <span class="mark">G</span>
        <div>
          <strong>Gestora</strong>
          <small class="muted">{{ auth.user?.companyName }}</small>
        </div>
      </div>

      <nav>
        <div v-for="group in auth.menuGroups" :key="group.name" class="group">
          <p class="group-label">{{ group.name }}</p>
          <RouterLink
            v-for="item in group.items"
            :key="item.key"
            :to="{ name: item.key }"
            class="nav-item"
            :class="{ pending: !item.available }"
            @click="sidebarOpen = false"
          >
            <AppIcon :name="item.icon" :size="17" />
            <span>{{ item.name }}</span>
            <em v-if="!item.available" title="En construcción">·</em>
          </RouterLink>
        </div>
      </nav>

      <footer class="sidebar-footer muted">v0.1 · fase 1</footer>
    </aside>

    <div v-if="sidebarOpen" class="scrim" @click="sidebarOpen = false" />

    <div class="main">
      <header class="topbar">
        <button class="icon-btn only-mobile" aria-label="Abrir menú" @click="sidebarOpen = true">
          <AppIcon name="menu" :size="20" />
        </button>

        <h2>{{ currentTitle }}</h2>

        <div class="profile" @click.stop>
          <button class="profile-btn" @click="profileOpen = !profileOpen">
            <span class="avatar">{{ initials }}</span>
            <span class="who">
              <strong>{{ auth.user?.fullName }}</strong>
              <small class="muted">{{ auth.user?.roleName }}</small>
            </span>
          </button>

          <div v-if="profileOpen" class="menu" @click="profileOpen = false">
            <RouterLink :to="{ name: 'profile' }" class="menu-item">
              <AppIcon name="cog" :size="16" /> Mi cuenta
            </RouterLink>
            <button class="menu-item" @click="logout">
              <AppIcon name="logout" :size="16" /> Cerrar sesión
            </button>
          </div>
        </div>
      </header>

      <main @click="profileOpen = false">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<style scoped>
.shell {
  display: flex;
  min-height: 100vh;
}

/* ------------------------------------------------------------ Barra lateral ---- */

.sidebar {
  width: var(--sidebar-width);
  flex-shrink: 0;
  background: var(--surface);
  border-right: 1px solid var(--ink-200);
  display: flex;
  flex-direction: column;
  position: sticky;
  top: 0;
  height: 100vh;
}

.brand {
  display: flex;
  align-items: center;
  gap: 11px;
  padding: 16px 18px;
  border-bottom: 1px solid var(--ink-200);
}

.mark {
  width: 32px;
  height: 32px;
  border-radius: 9px;
  background: var(--brand-500);
  color: #fff;
  display: grid;
  place-items: center;
  font-weight: 700;
  font-size: 16px;
  flex-shrink: 0;
}

.brand strong {
  display: block;
  font-size: 15px;
  letter-spacing: -0.01em;
}

.brand small {
  display: block;
  font-size: 11.5px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 150px;
}

nav {
  flex: 1;
  overflow-y: auto;
  padding: 12px 10px;
}

.group + .group {
  margin-top: 14px;
}

.group-label {
  font-size: 10.5px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.07em;
  color: var(--ink-400);
  padding: 0 10px 6px;
}

.nav-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 7px 10px;
  border-radius: var(--radius-sm);
  color: var(--ink-700);
  font-size: 13.5px;
  font-weight: 500;
  margin-bottom: 1px;
}

.nav-item:hover {
  background: var(--ink-100);
}

.nav-item.router-link-active {
  background: var(--brand-50);
  color: var(--brand-700);
  font-weight: 600;
}

.nav-item.router-link-active :deep(svg) {
  color: var(--brand-500);
}

.nav-item.pending {
  color: var(--ink-400);
}

.nav-item em {
  margin-left: auto;
  font-size: 18px;
  line-height: 0;
  color: var(--accent-500);
}

.sidebar-footer {
  padding: 12px 18px;
  border-top: 1px solid var(--ink-200);
  font-size: 11.5px;
}

/* -------------------------------------------------------------- Encabezado ---- */

.main {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
}

.topbar {
  height: var(--header-height);
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 0 22px;
  background: var(--surface);
  border-bottom: 1px solid var(--ink-200);
  position: sticky;
  top: 0;
  z-index: 50;
}

.topbar h2 {
  font-size: 15px;
  font-weight: 600;
  flex: 1;
}

.icon-btn {
  background: none;
  border: none;
  color: var(--ink-500);
  cursor: pointer;
  padding: 5px;
  border-radius: var(--radius-sm);
  display: grid;
  place-items: center;
}

.icon-btn:hover {
  background: var(--ink-100);
}

.profile {
  position: relative;
}

.profile-btn {
  display: flex;
  align-items: center;
  gap: 9px;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px 6px;
  border-radius: var(--radius-sm);
}

.profile-btn:hover {
  background: var(--ink-100);
}

.avatar {
  width: 31px;
  height: 31px;
  border-radius: 50%;
  background: var(--brand-100);
  color: var(--brand-700);
  display: grid;
  place-items: center;
  font-size: 12px;
  font-weight: 700;
}

.who {
  text-align: left;
  line-height: 1.25;
}

.who strong {
  display: block;
  font-size: 13px;
}

.who small {
  font-size: 11.5px;
}

.menu {
  position: absolute;
  right: 0;
  top: calc(100% + 6px);
  min-width: 178px;
  background: var(--surface);
  border: 1px solid var(--ink-200);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 5px;
  z-index: 60;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 9px;
  width: 100%;
  padding: 8px 10px;
  border: none;
  background: none;
  border-radius: var(--radius-sm);
  font-size: 13.5px;
  color: var(--ink-700);
  cursor: pointer;
  text-align: left;
}

.menu-item:hover {
  background: var(--ink-100);
}

main {
  flex: 1;
  padding: 24px;
  max-width: 1400px;
  width: 100%;
}

.scrim {
  display: none;
}

.only-mobile {
  display: none;
}

@media (max-width: 900px) {
  .sidebar {
    position: fixed;
    z-index: 120;
    transform: translateX(-100%);
    transition: transform 0.2s ease;
    box-shadow: var(--shadow-lg);
  }

  .sidebar.open {
    transform: none;
  }

  .scrim {
    display: block;
    position: fixed;
    inset: 0;
    background: rgba(22, 25, 29, 0.4);
    z-index: 110;
  }

  .only-mobile {
    display: grid;
  }

  main {
    padding: 18px 16px;
  }

  .who {
    display: none;
  }
}
</style>

<script setup lang="ts">
import BaseButton from '@/components/ui/BaseButton.vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
</script>

<template>
  <div class="wrap">
    <p class="code">404</p>
    <h1>Esta página no existe</h1>
    <p class="muted">Revise la dirección o vuelva al inicio.</p>

    <RouterLink :to="{ name: auth.isAuthenticated ? 'dashboard' : 'login' }">
      <BaseButton variant="primary">
        {{ auth.isAuthenticated ? 'Ir al panel' : 'Iniciar sesión' }}
      </BaseButton>
    </RouterLink>
  </div>
</template>

<style scoped>
.wrap {
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 9px;
  padding: 24px;
  text-align: center;
}

.code {
  font-size: 54px;
  font-weight: 700;
  color: var(--brand-200);
  letter-spacing: -0.03em;
  line-height: 1;
}

h1 {
  font-size: 19px;
  font-weight: 650;
}

.wrap a {
  margin-top: 14px;
}
</style>

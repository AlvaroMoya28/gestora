<script setup lang="ts">
import { useNotificationStore } from '@/stores/notifications'

const notifications = useNotificationStore()
</script>

<template>
  <Teleport to="body">
    <div class="toast-host" role="status" aria-live="polite">
      <TransitionGroup name="toast">
        <div v-for="toast in notifications.toasts" :key="toast.id" class="toast" :class="toast.level">
          <span>{{ toast.message }}</span>
          <button type="button" aria-label="Cerrar aviso" @click="notifications.dismiss(toast.id)">
            &times;
          </button>
        </div>
      </TransitionGroup>
    </div>
  </Teleport>
</template>

<style scoped>
.toast-host {
  position: fixed;
  right: 18px;
  bottom: 18px;
  z-index: 300;
  display: flex;
  flex-direction: column;
  gap: 9px;
  max-width: min(380px, calc(100vw - 36px));
}

.toast {
  display: flex;
  align-items: flex-start;
  gap: 12px;
  padding: 11px 14px;
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  background: var(--surface);
  border-left: 3px solid var(--ink-400);
  font-size: 13.5px;
}

.toast.success {
  border-left-color: var(--success-600);
}

.toast.error {
  border-left-color: var(--danger-500);
}

.toast.info {
  border-left-color: var(--info-600);
}

.toast button {
  background: none;
  border: none;
  font-size: 18px;
  line-height: 1;
  color: var(--ink-400);
  cursor: pointer;
}

.toast-enter-active,
.toast-leave-active {
  transition: all 0.2s ease;
}

.toast-enter-from,
.toast-leave-to {
  opacity: 0;
  transform: translateX(18px);
}
</style>

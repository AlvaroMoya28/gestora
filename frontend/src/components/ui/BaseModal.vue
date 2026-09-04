<script setup lang="ts">
import { onBeforeUnmount, onMounted, watch } from 'vue'

const props = withDefaults(
  defineProps<{ open: boolean; title: string; subtitle?: string; width?: string }>(),
  { width: '640px' },
)

const emit = defineEmits<{ close: [] }>()

function onKeydown(event: KeyboardEvent) {
  if (event.key === 'Escape' && props.open) emit('close')
}

// Con el diálogo abierto la página de fondo no debe desplazarse.
watch(
  () => props.open,
  (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
  },
)

onMounted(() => window.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => {
  window.removeEventListener('keydown', onKeydown)
  document.body.style.overflow = ''
})
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div v-if="open" class="backdrop" @click.self="emit('close')">
        <div class="dialog" role="dialog" aria-modal="true" :style="{ maxWidth: width }">
          <header>
            <div>
              <h2>{{ title }}</h2>
              <p v-if="subtitle" class="muted">{{ subtitle }}</p>
            </div>
            <button class="close" type="button" aria-label="Cerrar" @click="emit('close')">&times;</button>
          </header>

          <div class="body">
            <slot />
          </div>

          <footer v-if="$slots.footer">
            <slot name="footer" />
          </footer>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.backdrop {
  position: fixed;
  inset: 0;
  background: rgba(22, 25, 29, 0.45);
  display: flex;
  align-items: flex-start;
  justify-content: center;
  padding: 5vh 16px;
  z-index: 200;
  overflow-y: auto;
}

.dialog {
  width: 100%;
  background: var(--surface);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-lg);
  display: flex;
  flex-direction: column;
}

header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  padding: 18px 22px;
  border-bottom: 1px solid var(--ink-200);
}

header h2 {
  font-size: 16px;
  font-weight: 650;
}

header p {
  font-size: 13px;
  margin-top: 2px;
}

.close {
  background: none;
  border: none;
  font-size: 24px;
  line-height: 1;
  color: var(--ink-400);
  cursor: pointer;
  padding: 0 4px;
  border-radius: var(--radius-sm);
}

.close:hover {
  color: var(--ink-900);
  background: var(--ink-100);
}

.body {
  padding: 20px 22px;
}

footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 14px 22px;
  border-top: 1px solid var(--ink-200);
  background: var(--ink-100);
  border-radius: 0 0 var(--radius-lg) var(--radius-lg);
}

.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.16s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>

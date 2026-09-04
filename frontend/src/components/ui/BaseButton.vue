<script setup lang="ts">
withDefaults(
  defineProps<{
    variant?: 'primary' | 'secondary' | 'ghost' | 'danger'
    size?: 'sm' | 'md'
    type?: 'button' | 'submit'
    disabled?: boolean
    loading?: boolean
    block?: boolean
  }>(),
  { variant: 'secondary', size: 'md', type: 'button' },
)
</script>

<template>
  <button
    :type="type"
    :class="['btn', `btn--${variant}`, `btn--${size}`, { 'btn--block': block }]"
    :disabled="disabled || loading"
  >
    <span v-if="loading" class="spinner" aria-hidden="true" />
    <slot />
  </button>
</template>

<style scoped>
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 7px;
  border: 1px solid transparent;
  border-radius: var(--radius-sm);
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.15s, border-color 0.15s, color 0.15s;
}

.btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.btn--md {
  padding: 8px 15px;
  font-size: 13.5px;
}

.btn--sm {
  padding: 5px 10px;
  font-size: 12.5px;
}

.btn--block {
  width: 100%;
}

.btn--primary {
  background: var(--brand-500);
  color: #fff;
}

.btn--primary:hover:not(:disabled) {
  background: var(--brand-600);
}

.btn--secondary {
  background: var(--surface);
  border-color: var(--ink-300);
  color: var(--ink-700);
}

.btn--secondary:hover:not(:disabled) {
  background: var(--ink-100);
  border-color: var(--ink-400);
}

.btn--ghost {
  background: transparent;
  color: var(--ink-500);
}

.btn--ghost:hover:not(:disabled) {
  background: var(--ink-100);
  color: var(--ink-900);
}

.btn--danger {
  background: var(--danger-500);
  color: #fff;
}

.btn--danger:hover:not(:disabled) {
  background: var(--danger-600);
}

.spinner {
  width: 13px;
  height: 13px;
  border: 2px solid currentColor;
  border-right-color: transparent;
  border-radius: 50%;
  animation: spin 0.65s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>

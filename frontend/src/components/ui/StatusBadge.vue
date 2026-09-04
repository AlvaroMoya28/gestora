<script setup lang="ts">
withDefaults(
  defineProps<{ tone?: 'neutral' | 'success' | 'warning' | 'danger' | 'info' | 'brand' }>(),
  { tone: 'neutral' },
)
</script>

<template>
  <span class="badge" :class="`badge--${tone}`"><slot /></span>
</template>

<style scoped>
.badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 2px 9px;
  border-radius: 999px;
  font-size: 11.5px;
  font-weight: 600;
  white-space: nowrap;
}

.badge--neutral {
  background: var(--ink-100);
  color: var(--ink-500);
}

.badge--success {
  background: var(--success-100);
  color: var(--success-600);
}

.badge--warning {
  background: var(--accent-100);
  color: var(--accent-600);
}

.badge--danger {
  background: var(--danger-100);
  color: var(--danger-600);
}

.badge--info {
  background: var(--info-100);
  color: var(--info-600);
}

.badge--brand {
  background: var(--brand-100);
  color: var(--brand-700);
}
</style>

<script setup lang="ts">
defineProps<{ title: string; description?: string }>()
</script>

<template>
  <header class="page-header">
    <div>
      <h1>{{ title }}</h1>
      <p v-if="description" class="muted">{{ description }}</p>
    </div>
    <div class="actions">
      <slot name="actions" />
    </div>
  </header>
</template>

<style scoped>
.page-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  margin-bottom: 20px;
}

h1 {
  font-size: 20px;
  font-weight: 650;
  letter-spacing: -0.01em;
}

p {
  font-size: 13.5px;
  margin-top: 3px;
}

.actions {
  display: flex;
  gap: 9px;
  flex-wrap: wrap;
}
</style>

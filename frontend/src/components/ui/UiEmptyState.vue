<script setup lang="ts">
defineProps<{
  title: string
  description?: string
}>()
</script>

<template>
  <div class="ui-empty">
    <div v-if="$slots.icon" class="ui-empty__icon">
      <slot name="icon" />
    </div>
    <h2 class="ui-empty__title">{{ title }}</h2>
    <p v-if="description" class="ui-empty__desc">{{ description }}</p>
    <div v-if="$slots.action" class="ui-empty__action">
      <slot name="action" />
    </div>
  </div>
</template>

<style scoped>
.ui-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: clamp(2rem, 6vw, 3rem) 1.25rem;
  border-radius: var(--tm-radius-lg);
  background: var(--tm-surface-2);
  border: 1px solid var(--tm-border);
  box-shadow: var(--tm-shadow-sm);
}

.ui-empty__icon {
  display: grid;
  place-items: center;
  width: 3.5rem;
  height: 3.5rem;
  margin-bottom: 1rem;
  border-radius: var(--tm-radius-md);
  background: var(--tm-accent-muted);
  color: var(--tm-accent);
}

.ui-empty__title {
  margin: 0 0 0.4rem;
  font-family: var(--tm-font-display);
  font-size: 1.25rem;
  font-weight: 500;
  color: var(--tm-ink);
}

.ui-empty__desc {
  margin: 0 0 1.25rem;
  max-width: 22rem;
  font-size: 0.9rem;
  line-height: 1.5;
  color: var(--tm-ink-muted);
}

.ui-empty__action {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  justify-content: center;
}
</style>

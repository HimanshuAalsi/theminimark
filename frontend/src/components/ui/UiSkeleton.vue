<script setup lang="ts">
withDefaults(
  defineProps<{
    width?: string
    height?: string
    rounded?: 'sm' | 'md' | 'lg' | 'full'
  }>(),
  { width: '100%', height: '1rem', rounded: 'md' },
)
</script>

<template>
  <span
    class="ui-skeleton"
    :class="`ui-skeleton--${rounded}`"
    :style="{ width, height }"
    aria-hidden="true"
  />
</template>

<style scoped>
.ui-skeleton {
  display: block;
  background: linear-gradient(
    90deg,
    var(--tm-skeleton) 0%,
    var(--tm-skeleton-shine) 50%,
    var(--tm-skeleton) 100%
  );
  background-size: 200% 100%;
  animation: ui-skeleton-shine 1.2s ease-in-out infinite;
}

.ui-skeleton--sm {
  border-radius: var(--tm-radius-xs);
}
.ui-skeleton--md {
  border-radius: var(--tm-radius-sm);
}
.ui-skeleton--lg {
  border-radius: var(--tm-radius-md);
}
.ui-skeleton--full {
  border-radius: var(--tm-radius-full);
}

@keyframes ui-skeleton-shine {
  0% {
    background-position: 100% 0;
  }
  100% {
    background-position: -100% 0;
  }
}
</style>

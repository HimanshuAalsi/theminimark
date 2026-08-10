<script setup lang="ts">
import UiSkeleton from '@/components/ui/UiSkeleton.vue'

withDefaults(defineProps<{ count?: number }>(), { count: 8 })
</script>

<template>
  <div class="grid-skel tm-product-grid" aria-hidden="true">
    <article v-for="i in count" :key="i" class="grid-skel__card">
      <UiSkeleton height="100%" rounded="md" class="grid-skel__img" />
      <UiSkeleton width="40%" height="0.65rem" rounded="sm" />
      <UiSkeleton width="85%" height="0.9rem" rounded="sm" />
      <UiSkeleton width="35%" height="1rem" rounded="sm" />
    </article>
  </div>
</template>

<style scoped>
.grid-skel {
  /* columns from .tm-product-grid */
}

.grid-skel__card {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.grid-skel__img {
  aspect-ratio: 1;
  width: 100%;
}
</style>

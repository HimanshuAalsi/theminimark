<script setup lang="ts">
defineProps<{
  page: number
  totalPages: number
  rangeLabel: string
  busy?: boolean
}>()

const emit = defineEmits<{ prev: []; next: [] }>()
</script>

<template>
  <div class="admin-pagination">
    <span class="admin-pagination__label">{{ rangeLabel }}</span>
    <div class="admin-pagination__actions">
      <button
        type="button"
        class="admin-btn admin-btn--ghost admin-btn--sm"
        :disabled="busy || page <= 1"
        @click="emit('prev')"
      >
        Previous
      </button>
      <span class="admin-pagination__page">Page {{ page }} / {{ totalPages }}</span>
      <button
        type="button"
        class="admin-btn admin-btn--ghost admin-btn--sm"
        :disabled="busy || page >= totalPages"
        @click="emit('next')"
      >
        Next
      </button>
    </div>
  </div>
</template>

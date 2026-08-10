<script setup lang="ts">
import type { PersonaliseType } from '@/data/personalise'
import { PERSONALISE_NAV } from '@/data/personaliseStudio'

defineProps<{
  active: PersonaliseType
}>()

const emit = defineEmits<{
  select: [type: PersonaliseType]
}>()
</script>

<template>
  <nav class="ps-nav" aria-label="Product type">
    <button
      v-for="item in PERSONALISE_NAV"
      :key="item.id"
      type="button"
      class="ps-nav__btn"
      :class="{ 'ps-nav__btn--on': active === item.id }"
      :aria-current="active === item.id ? 'page' : undefined"
      @click="emit('select', item.id)"
    >
      {{ item.label }}
    </button>
  </nav>
</template>

<style scoped>
.ps-nav {
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  min-width: 0;
}

@media (max-width: 900px) {
  .ps-nav {
    flex-direction: row;
    overflow-x: auto;
    padding-bottom: 0.25rem;
    scrollbar-width: none;
  }

  .ps-nav::-webkit-scrollbar {
    display: none;
  }
}

.ps-nav__btn {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 3.25rem;
  padding: 0.65rem 1rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: #e8e8e8;
  color: var(--color-ink);
  font: inherit;
  font-size: 0.92rem;
  font-weight: 600;
  text-align: center;
  cursor: pointer;
  transition:
    background 0.2s ease,
    border-color 0.2s ease,
    transform 0.15s ease,
    box-shadow 0.2s ease;
}

.ps-nav__btn:hover:not(.ps-nav__btn--on) {
  background: #ddd;
}

.ps-nav__btn--on {
  background: linear-gradient(135deg, #e91e8c 0%, #ff6b9d 100%);
  border-color: transparent;
  color: #fff;
  box-shadow: 0 6px 20px rgba(233, 30, 140, 0.35);
}

@media (max-width: 900px) {
  .ps-nav__btn {
    flex: 0 0 auto;
    min-width: 7.5rem;
    min-height: var(--tap-min);
  }
}
</style>

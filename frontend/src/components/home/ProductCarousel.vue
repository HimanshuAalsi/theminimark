<script setup lang="ts">
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { ref } from 'vue'
import type { SiteProduct } from '@/data/siteContent'
import ProductCard from '@/components/product/ProductCard.vue'

defineProps<{
  products: SiteProduct[]
}>()

const track = ref<HTMLElement | null>(null)

function scroll(dir: -1 | 1) {
  track.value?.scrollBy({ left: dir * 320, behavior: 'smooth' })
}
</script>

<template>
  <div class="pcar">
    <button type="button" class="pcar__arrow pcar__arrow--prev tm-press" aria-label="Previous" @click="scroll(-1)">
      <ChevronLeft :size="22" :stroke-width="2.25" />
    </button>
    <div ref="track" class="pcar__track">
      <div v-for="p in products" :key="p.id" class="pcar__cell">
        <ProductCard :product="p" />
      </div>
    </div>
    <button type="button" class="pcar__arrow pcar__arrow--next tm-press" aria-label="Next" @click="scroll(1)">
      <ChevronRight :size="22" :stroke-width="2.25" />
    </button>
  </div>
</template>

<style scoped>
.pcar {
  position: relative;
  margin: 0 -0.5rem;
  padding: 0 2.25rem;
}

.pcar__track {
  display: flex;
  gap: 16px;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  padding-bottom: 8px;
  scrollbar-width: thin;
}

.pcar__cell {
  flex: 0 0 calc(16.666% - 14px);
  min-width: 200px;
  max-width: 240px;
  scroll-snap-align: start;
}

@media (max-width: 1200px) {
  .pcar__cell {
    flex: 0 0 calc(25% - 12px);
  }
}

@media (max-width: 767px) {
  .pcar__cell {
    flex: 0 0 calc(50% - 8px);
    min-width: 160px;
  }
}

.pcar__arrow {
  position: absolute;
  top: 40%;
  transform: translateY(-50%);
  z-index: 2;
  width: 44px;
  height: 44px;
  border-radius: 50%;
  border: 1px solid var(--color-border);
  background: var(--color-surface-elevated);
  cursor: pointer;
  color: var(--color-ink);
  display: grid;
  place-items: center;
  box-shadow: var(--shadow-sm);
  transition:
    border-color 0.2s ease,
    color 0.2s ease,
    box-shadow 0.2s ease,
    transform 0.2s var(--ease-out, ease);
}

.pcar__arrow:hover {
  border-color: var(--color-accent);
  color: var(--color-accent);
  box-shadow: var(--shadow-float);
  transform: translateY(-50%) scale(1.05);
}

.pcar__arrow--prev {
  left: 0;
}

.pcar__arrow--next {
  right: 0;
}

@media (prefers-reduced-motion: reduce) {
  .pcar__arrow:hover {
    transform: translateY(-50%);
  }
}
</style>

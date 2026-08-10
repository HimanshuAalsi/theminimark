<script setup lang="ts">
import { ShoppingBag } from 'lucide-vue-next'
import { formatCurrency } from '@/lib/currency'

defineProps<{
  price: number
  compareAt?: number
  label?: string
  disabled?: boolean
}>()

const emit = defineEmits<{ action: [] }>()

const fmt = formatCurrency
</script>

<template>
  <div class="sticky-buy" role="region" aria-label="Purchase">
    <div class="sticky-buy__price">
      <span class="sticky-buy__current">{{ fmt(price) }}</span>
      <span v-if="compareAt && compareAt > price" class="sticky-buy__was">{{ fmt(compareAt) }}</span>
    </div>
    <button
      type="button"
      class="sticky-buy__btn"
      :disabled="disabled"
      @click="emit('action')"
    >
      <ShoppingBag :size="18" :stroke-width="2.25" aria-hidden="true" />
      {{ label ?? 'Add to cart' }}
    </button>
  </div>
</template>

<style scoped>
.sticky-buy {
  display: none;
}

@media (max-width: 900px) {
  .sticky-buy {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    position: fixed;
    left: 0;
    right: 0;
    bottom: var(--bottom-nav-h, 0);
    z-index: 35;
    padding: 0.65rem 1rem calc(0.65rem + env(safe-area-inset-bottom, 0));
    background: color-mix(in srgb, var(--tm-surface-2) 94%, transparent);
    border-top: 1px solid var(--tm-border);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
  }

  .layout--app .sticky-buy {
    bottom: calc(var(--app-tab-h, 4rem) + env(safe-area-inset-bottom, 0));
  }
}

.sticky-buy__price {
  display: flex;
  flex-direction: column;
  gap: 0.05rem;
}

.sticky-buy__current {
  font-size: 1.1rem;
  font-weight: 800;
  color: var(--tm-ink);
  font-variant-numeric: tabular-nums;
}

.sticky-buy__was {
  font-size: 0.78rem;
  color: var(--tm-ink-faint);
  text-decoration: line-through;
}

.sticky-buy__btn {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  min-height: var(--tm-tap);
  padding: 0 1.35rem;
  border: none;
  border-radius: var(--tm-radius-full);
  background: var(--tm-gradient);
  color: var(--tm-on-accent);
  font: inherit;
  font-weight: 700;
  font-size: 0.9rem;
  cursor: pointer;
  box-shadow: var(--tm-shadow-sm);
  transition: background var(--tm-duration) var(--tm-ease);
}

.sticky-buy__btn:hover:not(:disabled) {
  background: var(--tm-gradient-hover);
}

.sticky-buy__btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}
</style>

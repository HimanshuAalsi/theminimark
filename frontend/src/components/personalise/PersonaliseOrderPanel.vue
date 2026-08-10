<script setup lang="ts">
import { Check, Loader2, Minus, Plus, ShoppingBag, Truck } from 'lucide-vue-next'
import { computed } from 'vue'
import type { PersonaliseProduct } from '@/data/personalise'
import { STUDIO_PERKS } from '@/data/personaliseStudio'
import { formatCurrency, STORE_CURRENCY } from '@/lib/currency'

const props = defineProps<{
  product: PersonaliseProduct
  price: number
  compareAt: number
  quantity: number
  canAdd: boolean
  adding?: boolean
  added?: boolean
  error?: string
  details?: string[]
}>()

const emit = defineEmits<{
  'update:quantity': [n: number]
  add: []
  openCart: []
}>()

const fmt = formatCurrency
const onSale = computed(() => props.compareAt > props.price)
const lineTotal = computed(() => props.price * props.quantity)

function bump(delta: number) {
  emit('update:quantity', Math.max(1, Math.min(99, props.quantity + delta)))
}
</script>

<template>
  <aside class="ps-order" aria-label="Order summary">
    <p class="ps-order__eyebrow">Customize · Made to order</p>
    <h1 class="ps-order__title">{{ product.label }}</h1>
    <p class="ps-order__blurb">{{ product.blurb }}</p>

    <div class="ps-order__price-block">
      <div class="ps-order__price-row">
        <span class="ps-order__price">{{ fmt(price) }}</span>
        <span v-if="onSale" class="ps-order__was">{{ fmt(compareAt) }}</span>
      </div>
      <p v-if="onSale" class="ps-order__save">You save {{ fmt(compareAt - price) }}</p>
      <p class="ps-order__line-total">
        Total for {{ quantity }} · <strong>{{ fmt(lineTotal) }}</strong>
      </p>
      <p class="ps-order__ship">
        <Truck :size="14" aria-hidden="true" />
        Free delivery above ₹499 · {{ STORE_CURRENCY }}
      </p>
    </div>

    <ul v-if="details?.length" class="ps-order__details">
      <li v-for="(d, i) in details" :key="i">{{ d }}</li>
    </ul>

    <div class="ps-order__qty">
      <span class="ps-order__label">Quantity</span>
      <div class="ps-order__stepper">
        <button type="button" aria-label="Decrease" :disabled="quantity <= 1" @click="bump(-1)">
          <Minus :size="16" />
        </button>
        <span>{{ quantity }}</span>
        <button type="button" aria-label="Increase" :disabled="quantity >= 99" @click="bump(1)">
          <Plus :size="16" />
        </button>
      </div>
    </div>

    <slot />

    <p v-if="error" class="ps-order__error" role="alert">{{ error }}</p>

    <button type="button" class="ps-order__cta" :disabled="!canAdd || adding" @click="emit('add')">
      <Loader2 v-if="adding" :size="18" class="ps-order__spin" />
      <ShoppingBag v-else :size="18" />
      {{ adding ? 'Adding…' : `Add to cart · ${fmt(lineTotal)}` }}
    </button>

    <p v-if="added" class="ps-order__ok">
      <Check :size="16" />
      Added!
      <button type="button" class="ps-order__link" @click="emit('openCart')">View cart</button>
    </p>

    <ul class="ps-order__perks">
      <li v-for="p in STUDIO_PERKS" :key="p">{{ p }}</li>
    </ul>
  </aside>
</template>

<style scoped>
.ps-order {
  padding: 1rem 0.95rem;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-surface-elevated);
  box-shadow: var(--shadow-card);
  position: sticky;
  top: 5.5rem;
}

.ps-order__eyebrow {
  margin: 0 0 0.35rem;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: #e91e8c;
}

.ps-order__title {
  margin: 0 0 0.35rem;
  font-family: var(--font-display);
  font-size: 1.2rem;
  font-weight: 500;
  line-height: 1.2;
}

.ps-order__blurb {
  margin: 0 0 1rem;
  font-size: 0.9rem;
  line-height: 1.55;
  color: var(--color-ink-muted);
}

.ps-order__price-block {
  padding: 0.85rem 0;
  margin-bottom: 0.85rem;
  border-top: 1px solid var(--color-border);
  border-bottom: 1px solid var(--color-border);
}

.ps-order__price-row {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
}

.ps-order__price {
  font-size: 1.65rem;
  font-weight: 800;
}

.ps-order__was {
  text-decoration: line-through;
  color: var(--color-ink-faint);
  font-size: 0.95rem;
}

.ps-order__save {
  margin: 0.25rem 0 0;
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--color-accent);
}

.ps-order__line-total {
  margin: 0.5rem 0 0;
  font-size: 0.88rem;
  color: var(--color-ink-muted);
}

.ps-order__ship {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin: 0.45rem 0 0;
  font-size: 0.78rem;
  color: var(--color-ink-faint);
}

.ps-order__details {
  margin: 0 0 1rem;
  padding-left: 1.1rem;
  font-size: 0.82rem;
  color: var(--color-ink-muted);
  line-height: 1.45;
}

.ps-order__qty {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 1rem;
}

.ps-order__label {
  font-size: 0.88rem;
  font-weight: 600;
}

.ps-order__stepper {
  display: inline-flex;
  align-items: center;
  border: 1px solid var(--color-border);
  border-radius: 999px;
  overflow: hidden;
}

.ps-order__stepper button {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.25rem;
  height: 2.25rem;
  border: none;
  background: var(--color-page);
  cursor: pointer;
}

.ps-order__stepper button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.ps-order__stepper span {
  min-width: 2rem;
  text-align: center;
  font-weight: 700;
}

.ps-order__error {
  margin: 0 0 0.65rem;
  font-size: 0.85rem;
  color: var(--color-sale);
}

.ps-order__cta {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: 100%;
  min-height: var(--tap-min);
  border: none;
  border-radius: 999px;
  background: var(--tm-gradient);
  color: #fff;
  font-weight: 800;
  font-size: 0.95rem;
  cursor: pointer;
  box-shadow: 0 6px 18px rgba(45, 92, 82, 0.3);
}

.ps-order__cta:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  box-shadow: none;
}

.ps-order__spin {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.ps-order__ok {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin: 0.65rem 0 0;
  font-size: 0.88rem;
  color: var(--color-accent);
  font-weight: 600;
}

.ps-order__link {
  border: none;
  background: none;
  color: var(--color-accent);
  font-weight: 700;
  text-decoration: underline;
  cursor: pointer;
}

.ps-order__perks {
  margin: 1.25rem 0 0;
  padding-left: 1rem;
  font-size: 0.78rem;
  color: var(--color-ink-faint);
  line-height: 1.5;
}

@media (max-width: 1024px) {
  .ps-order {
    position: static;
  }
}
</style>

<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useCartStore } from '@/stores/cart'

const cart = useCartStore()

const formattedSubtotal = computed(() =>
  new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(
    cart.subtotal
  )
)
</script>

<template>
  <div class="cart">
    <h1 class="cart__title">Cart</h1>

    <template v-if="cart.lines.length === 0">
      <p class="cart__empty">Your cart is empty.</p>
      <RouterLink to="/shop" class="cart__link">Continue shopping</RouterLink>
    </template>

    <template v-else>
      <ul class="cart__list" role="list">
        <li v-for="line in cart.lines" :key="String(line.productId)" class="line">
          <div class="line__main">
            <p class="line__name">{{ line.name }}</p>
            <p class="line__meta">
              {{ new Intl.NumberFormat(undefined, { style: 'currency', currency: 'USD' }).format(line.unitPrice) }}
              each
            </p>
          </div>
          <div class="line__qty">
            <label class="sr-only" :for="`qty-${line.productId}`">Quantity</label>
            <input
              :id="`qty-${line.productId}`"
              class="line__input"
              type="number"
              min="1"
              :value="line.quantity"
              @change="
                cart.setQuantity(
                  line.productId,
                  Number(($event.target as HTMLInputElement).value) || 1
                )
              "
            />
            <button
              type="button"
              class="line__remove"
              @click="cart.removeLine(line.productId)"
            >
              Remove
            </button>
          </div>
        </li>
      </ul>

      <div class="cart__summary">
        <p class="cart__subtotal">
          Subtotal <strong>{{ formattedSubtotal }}</strong>
        </p>
        <p class="cart__hint">Checkout will connect to your PHP payment flow later.</p>
      </div>
    </template>
  </div>
</template>

<style scoped>
.cart__title {
  margin: 0 0 1.25rem;
  font-size: 1.75rem;
  font-weight: 650;
  color: var(--color-text);
}

.cart__empty {
  margin: 0 0 1rem;
  color: var(--color-text-muted);
}

.cart__link {
  font-weight: 600;
  color: var(--color-accent);
  text-decoration: none;
}

.cart__link:hover {
  text-decoration: underline;
}

.cart__list {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.line {
  display: flex;
  flex-wrap: wrap;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.75rem 1rem;
  padding: 1rem 1.1rem;
  border-radius: 0.75rem;
  border: 1px solid var(--color-border);
  background: var(--color-surface);
}

.line__name {
  margin: 0 0 0.25rem;
  font-weight: 600;
  color: var(--color-text);
}

.line__meta {
  margin: 0;
  font-size: 0.9rem;
  color: var(--color-text-muted);
}

.line__qty {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.line__input {
  width: 4rem;
  padding: 0.35rem 0.5rem;
  border-radius: 0.45rem;
  border: 1px solid var(--color-border);
  background: var(--color-bg);
  color: var(--color-text);
  font: inherit;
}

.line__remove {
  border: none;
  background: none;
  color: var(--color-text-muted);
  font: inherit;
  font-size: 0.9rem;
  cursor: pointer;
  text-decoration: underline;
}

.line__remove:hover {
  color: var(--color-warn);
}

.cart__summary {
  margin-top: 1.5rem;
  padding-top: 1.25rem;
  border-top: 1px solid var(--color-border);
}

.cart__subtotal {
  margin: 0 0 0.5rem;
  font-size: 1.05rem;
  color: var(--color-text-muted);
}

.cart__subtotal strong {
  color: var(--color-text);
}

.cart__hint {
  margin: 0;
  font-size: 0.9rem;
  color: var(--color-text-muted);
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  white-space: nowrap;
  border: 0;
}
</style>

<script setup lang="ts">
import {
  ArrowRight,
  Minus,
  Plus,
  ShoppingBag,
  Sparkles,
  Trash2,
  X,
} from 'lucide-vue-next'
import { computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { formatCurrency } from '@/lib/currency'
import { useCartStore } from '@/stores/cart'
import { useCartUiStore } from '@/stores/cartUi'
import { storeToRefs } from 'pinia'

const cart = useCartStore()
const cartUi = useCartUiStore()
const router = useRouter()
const route = useRoute()
const { isOpen } = storeToRefs(cartUi)

const fmt = formatCurrency
const lineCount = computed(() => cart.totalQuantity)
const formattedSubtotal = computed(() => fmt(cart.subtotal))
const isEmpty = computed(() => cart.lines.length === 0)

function lineTotal(unitPrice: number, quantity: number) {
  return fmt(unitPrice * quantity)
}

function close() {
  cartUi.close()
}

function onBackdropClick() {
  close()
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape' && isOpen.value) close()
}

function bumpQty(productId: string | number, delta: number) {
  const line = cart.lines.find((l) => l.productId === productId)
  if (!line) return
  cart.setQuantity(productId, line.quantity + delta)
}

function goCheckout() {
  close()
  router.push('/checkout')
}

function continueShopping() {
  close()
  if (route.name === 'cart') {
    router.replace('/shop')
  }
}

watch(isOpen, (open) => {
  if (open) {
    window.addEventListener('keydown', onKeydown)
  } else {
    window.removeEventListener('keydown', onKeydown)
    if (route.name === 'cart') {
      router.replace('/')
    }
  }
})
</script>

<template>
  <Teleport to="body">
    <Transition name="cart-backdrop">
      <div
        v-if="isOpen"
        class="cart-drawer__backdrop"
        aria-hidden="true"
        @click="onBackdropClick"
      />
    </Transition>

    <Transition name="cart-panel">
      <aside
        v-if="isOpen"
        class="cart-drawer"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cart-drawer-title"
      >
        <header class="cart-drawer__head">
          <div class="cart-drawer__head-main">
            <p class="cart-drawer__eyebrow">
              <ShoppingBag :size="15" :stroke-width="2.25" aria-hidden="true" />
              Your bag
            </p>
            <h2 id="cart-drawer-title" class="cart-drawer__title">
              Shopping cart
              <span v-if="lineCount > 0" class="cart-drawer__count">{{ lineCount }}</span>
            </h2>
          </div>
          <button type="button" class="cart-drawer__close tm-press" aria-label="Close cart" @click="close">
            <X :size="22" :stroke-width="2" />
          </button>
        </header>

        <div v-if="isEmpty" class="cart-drawer__empty">
          <div class="cart-drawer__empty-icon" aria-hidden="true">
            <ShoppingBag :size="40" :stroke-width="1.25" />
          </div>
          <p class="cart-drawer__empty-title">Your cart is empty</p>
          <p class="cart-drawer__empty-text">
            Add bookmarks, cards, or a custom piece — we will keep everything here while you browse.
          </p>
          <button type="button" class="cart-drawer__empty-btn" @click="continueShopping">
            <Sparkles :size="17" aria-hidden="true" />
            Start shopping
          </button>
        </div>

        <template v-else>
          <ul class="cart-drawer__list" role="list">
            <li v-for="line in cart.lines" :key="String(line.productId)" class="cart-line">
              <div class="cart-line__thumb-wrap">
                <img
                  v-if="line.imageUrl"
                  :src="line.imageUrl"
                  alt=""
                  class="cart-line__thumb"
                />
                <div v-else class="cart-line__thumb cart-line__thumb--placeholder">
                  <ShoppingBag :size="20" :stroke-width="1.5" aria-hidden="true" />
                </div>
              </div>

              <div class="cart-line__body">
                <p class="cart-line__name">{{ line.name }}</p>
                <p v-if="line.customType" class="cart-line__tag">Custom · {{ line.customType }}</p>
                <p class="cart-line__unit">{{ fmt(line.unitPrice) }} each</p>

                <div class="cart-line__actions">
                  <div class="cart-line__stepper" role="group" :aria-label="`Quantity for ${line.name}`">
                    <button
                      type="button"
                      class="cart-line__step"
                      :disabled="line.quantity <= 1"
                      aria-label="Decrease quantity"
                      @click="bumpQty(line.productId, -1)"
                    >
                      <Minus :size="14" :stroke-width="2.5" />
                    </button>
                    <span class="cart-line__qty" aria-live="polite">{{ line.quantity }}</span>
                    <button
                      type="button"
                      class="cart-line__step"
                      aria-label="Increase quantity"
                      @click="bumpQty(line.productId, 1)"
                    >
                      <Plus :size="14" :stroke-width="2.5" />
                    </button>
                  </div>
                  <p class="cart-line__total">{{ lineTotal(line.unitPrice, line.quantity) }}</p>
                </div>
              </div>

              <button
                type="button"
                class="cart-line__remove"
                aria-label="Remove item"
                @click="cart.removeLine(line.productId)"
              >
                <Trash2 :size="16" :stroke-width="2" />
              </button>
            </li>
          </ul>

          <footer class="cart-drawer__foot">
            <div class="cart-drawer__row">
              <span>Subtotal</span>
              <strong>{{ formattedSubtotal }}</strong>
            </div>
            <p class="cart-drawer__note">Shipping & taxes calculated at checkout.</p>
            <button type="button" class="cart-drawer__checkout tm-press" @click="goCheckout">
              Checkout
              <ArrowRight :size="18" :stroke-width="2.25" aria-hidden="true" />
            </button>
            <button type="button" class="cart-drawer__continue" @click="continueShopping">
              Continue shopping
            </button>
          </footer>
        </template>
      </aside>
    </Transition>
  </Teleport>
</template>

<style scoped>
.cart-drawer__backdrop {
  position: fixed;
  inset: 0;
  z-index: 200;
  background: rgba(20, 19, 18, 0.42);
  backdrop-filter: blur(4px);
}

.cart-drawer {
  position: fixed;
  top: 0;
  right: 0;
  z-index: 201;
  display: flex;
  flex-direction: column;
  width: min(100%, 26rem);
  max-width: 100vw;
  height: 100%;
  height: 100dvh;
  background: var(--color-surface);
  border-left: 1px solid var(--color-border);
  box-shadow: -12px 0 48px rgba(20, 19, 18, 0.14);
}

.cart-drawer__head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.15rem 1.15rem 1rem;
  border-bottom: 1px solid var(--color-border);
  background: linear-gradient(180deg, var(--color-surface-elevated) 0%, var(--color-surface) 100%);
}

.cart-drawer__head-main {
  min-width: 0;
}

.cart-drawer__eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  margin: 0 0 0.25rem;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--color-accent);
}

.cart-drawer__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: 1.35rem;
  font-weight: 500;
  line-height: 1.2;
  color: var(--color-ink);
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.cart-drawer__count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.5rem;
  height: 1.5rem;
  padding: 0 0.4rem;
  border-radius: 999px;
  font-family: var(--font-ui);
  font-size: 0.75rem;
  font-weight: 700;
  background: var(--color-accent-soft);
  color: var(--color-accent);
}

.cart-drawer__close {
  flex-shrink: 0;
  display: grid;
  place-items: center;
  width: 2.5rem;
  height: 2.5rem;
  border: 1px solid var(--color-border);
  border-radius: 999px;
  background: var(--color-surface-elevated);
  color: var(--color-ink);
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    color 0.2s ease,
    background 0.2s ease;
}

.cart-drawer__close:hover {
  border-color: var(--color-accent);
  color: var(--color-accent);
}

.cart-drawer__empty {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1.5rem;
  text-align: center;
}

.cart-drawer__empty-icon {
  display: grid;
  place-items: center;
  width: 4.5rem;
  height: 4.5rem;
  margin-bottom: 1rem;
  border-radius: 999px;
  background: var(--color-accent-soft);
  color: var(--color-accent);
}

.cart-drawer__empty-title {
  margin: 0 0 0.4rem;
  font-family: var(--font-display);
  font-size: 1.2rem;
  font-weight: 500;
}

.cart-drawer__empty-text {
  margin: 0 0 1.25rem;
  max-width: 16rem;
  font-size: 0.88rem;
  line-height: 1.5;
  color: var(--color-ink-muted);
}

.cart-drawer__empty-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  min-height: var(--tap-min);
  padding: 0 1.35rem;
  border: none;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--color-accent), #1a4a42);
  color: #fff;
  font-weight: 700;
  font-size: 0.92rem;
  cursor: pointer;
  box-shadow: 0 4px 16px rgba(45, 92, 82, 0.28);
}

.cart-drawer__list {
  flex: 1;
  overflow-y: auto;
  margin: 0;
  padding: 0.85rem 1rem 1rem;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.65rem;
  overscroll-behavior: contain;
}

.cart-line {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: 0.75rem;
  align-items: start;
  padding: 0.75rem;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  background: var(--color-surface-elevated);
  box-shadow: var(--shadow-sm);
}

.cart-line__thumb {
  width: 4.25rem;
  height: 4.25rem;
  object-fit: cover;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
}

.cart-line__thumb--placeholder {
  display: grid;
  place-items: center;
  background: var(--color-accent-soft);
  color: var(--color-accent);
}

.cart-line__body {
  min-width: 0;
}

.cart-line__name {
  margin: 0 0 0.15rem;
  font-size: 0.9rem;
  font-weight: 650;
  line-height: 1.3;
  color: var(--color-ink);
}

.cart-line__tag {
  margin: 0 0 0.2rem;
  font-size: 0.68rem;
  font-weight: 700;
  text-transform: capitalize;
  color: var(--color-accent);
}

.cart-line__unit {
  margin: 0 0 0.5rem;
  font-size: 0.78rem;
  color: var(--color-ink-muted);
}

.cart-line__actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.cart-line__stepper {
  display: inline-flex;
  align-items: center;
  border: 1px solid var(--color-border);
  border-radius: 999px;
  background: var(--color-surface);
  overflow: hidden;
}

.cart-line__step {
  display: grid;
  place-items: center;
  width: 2rem;
  height: 2rem;
  border: none;
  background: transparent;
  color: var(--color-ink);
  cursor: pointer;
  transition: background 0.15s ease;
}

.cart-line__step:hover:not(:disabled) {
  background: var(--color-accent-soft);
  color: var(--color-accent);
}

.cart-line__step:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.cart-line__qty {
  min-width: 1.75rem;
  text-align: center;
  font-size: 0.82rem;
  font-weight: 700;
}

.cart-line__total {
  margin: 0;
  font-size: 0.9rem;
  font-weight: 700;
  color: var(--color-ink);
}

.cart-line__remove {
  display: grid;
  place-items: center;
  width: 2rem;
  height: 2rem;
  padding: 0;
  border: none;
  border-radius: 999px;
  background: transparent;
  color: var(--color-ink-faint);
  cursor: pointer;
  transition:
    color 0.2s ease,
    background 0.2s ease;
}

.cart-line__remove:hover {
  color: var(--color-sale);
  background: rgba(196, 74, 74, 0.1);
}

.cart-drawer__foot {
  flex-shrink: 0;
  padding: 1rem 1.15rem 1.25rem;
  padding-bottom: max(1.25rem, env(safe-area-inset-bottom));
  border-top: 1px solid var(--color-border);
  background: var(--color-surface-elevated);
  box-shadow: 0 -8px 24px rgba(20, 19, 18, 0.06);
}

.cart-drawer__row {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  margin-bottom: 0.35rem;
  font-size: 0.95rem;
  color: var(--color-ink-muted);
}

.cart-drawer__row strong {
  font-family: var(--font-display);
  font-size: 1.25rem;
  font-weight: 600;
  color: var(--color-ink);
}

.cart-drawer__note {
  margin: 0 0 0.85rem;
  font-size: 0.75rem;
  color: var(--color-ink-faint);
}

.cart-drawer__checkout {
  display: flex;
  width: 100%;
  min-height: var(--tap-min);
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  margin-bottom: 0.55rem;
  border: none;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--color-accent), #1a4a42);
  color: #fff;
  font-weight: 700;
  font-size: 1rem;
  cursor: pointer;
  box-shadow: 0 4px 18px rgba(45, 92, 82, 0.32);
  transition:
    filter 0.2s ease,
    transform 0.15s ease;
}

.cart-drawer__checkout:hover {
  filter: brightness(1.05);
  transform: translateY(-1px);
}

.cart-drawer__continue {
  display: block;
  width: 100%;
  padding: 0.5rem;
  border: none;
  background: transparent;
  font-size: 0.88rem;
  font-weight: 650;
  color: var(--color-accent);
  cursor: pointer;
  text-decoration: underline;
  text-underline-offset: 3px;
}

.cart-backdrop-enter-active,
.cart-backdrop-leave-active {
  transition: opacity 0.28s var(--ease-out, ease);
}

.cart-backdrop-enter-from,
.cart-backdrop-leave-to {
  opacity: 0;
}

.cart-panel-enter-active,
.cart-panel-leave-active {
  transition: transform 0.32s var(--ease-out, ease);
}

.cart-panel-enter-from,
.cart-panel-leave-to {
  transform: translateX(100%);
}

@media (prefers-reduced-motion: reduce) {
  .cart-panel-enter-active,
  .cart-panel-leave-active,
  .cart-backdrop-enter-active,
  .cart-backdrop-leave-active {
    transition-duration: 0.01ms;
  }

  .cart-panel-enter-from,
  .cart-panel-leave-to {
    transform: none;
  }
}
</style>

<script setup lang="ts">
import { ArrowRight, Minus, Plus, ShoppingBag, Sparkles, Trash2, X } from 'lucide-vue-next'
import { computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import CartLineThumb from '@/components/cart/CartLineThumb.vue'
import CartFreeGiftPicker from '@/components/cart/CartFreeGiftPicker.vue'
import CartMilestoneBar from '@/components/cart/CartMilestoneBar.vue'
import { formatCurrency, formatLineTotal } from '@/lib/currency'
import { useCartStore } from '@/stores/cart'
import { useCartUiStore } from '@/stores/cartUi'
import { useCatalogStore } from '@/stores/catalog'
import { useFreeGiftsStore } from '@/stores/freeGifts'
import { storeToRefs } from 'pinia'

const cart = useCartStore()
const catalog = useCatalogStore()
const freeGifts = useFreeGiftsStore()
const cartUi = useCartUiStore()
const router = useRouter()
const route = useRoute()
const { isOpen } = storeToRefs(cartUi)

const fmt = formatCurrency
const lineCount = computed(() => cart.totalQuantity)
const isEmpty = computed(() => cart.lines.length === 0)

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
  if (!cart.freeGiftReady) return
  close()
  router.push('/checkout')
}

function continueShopping() {
  close()
  if (route.name === 'cart') router.replace('/shop')
}

watch(isOpen, async (open) => {
  if (open) {
    window.addEventListener('keydown', onKeydown)
    await catalog.ensureLoaded()
    await freeGifts.ensureLoaded()
    cart.refreshLineImagesFromCatalog(catalog.catalog)
    cart.refreshFreeGiftOptions(
      catalog.catalog,
      freeGifts.configuredOptions.map((o) => o.id),
    )
  } else {
    window.removeEventListener('keydown', onKeydown)
    if (route.name === 'cart') router.replace('/')
  }
})
</script>

<template>
  <Teleport to="body">
    <Transition name="cart-backdrop">
      <div v-if="isOpen" class="cart-backdrop" aria-hidden="true" @click="onBackdropClick" />
    </Transition>

    <Transition name="cart-panel">
      <aside v-if="isOpen" class="cart" role="dialog" aria-modal="true" aria-labelledby="cart-title">
        <header class="cart__head">
          <h2 id="cart-title" class="cart__title">
            Cart
            <span v-if="lineCount > 0" class="cart__count">{{ lineCount }}</span>
          </h2>
          <button type="button" class="cart__close" aria-label="Close" @click="close">
            <X :size="18" :stroke-width="2" />
          </button>
        </header>

        <div v-if="isEmpty" class="cart__empty">
          <ShoppingBag :size="32" :stroke-width="1.25" aria-hidden="true" />
          <p class="cart__empty-title">Your cart is empty</p>
          <button type="button" class="cart__cta" @click="continueShopping">
            <Sparkles :size="15" aria-hidden="true" />
            Shop now
          </button>
        </div>

        <template v-else>
          <div class="cart__body">
            <CartMilestoneBar :subtotal="cart.subtotal" compact />

            <div class="cart__items">
              <p class="cart__items-label">Your items · {{ lineCount }}</p>
              <ul class="cart__list" role="list">
                <li v-for="line in cart.lines" :key="String(line.productId)" class="cart-item">
                  <CartLineThumb
                    :src="line.imageUrl ?? line.customPhotoUrl"
                    :alt="line.name"
                    :is-custom="Boolean(line.customType)"
                  />

                  <div class="cart-item__info">
                    <div class="cart-item__top">
                      <p class="cart-item__name">{{ line.name }}</p>
                      <p class="cart-item__total">
                        {{ formatLineTotal(line.unitPrice, line.quantity) }}
                      </p>
                    </div>
                    <p class="cart-item__unit">{{ fmt(line.unitPrice) }} each</p>
                    <div class="cart-item__actions">
                      <div class="cart-item__qty" role="group" :aria-label="`Quantity for ${line.name}`">
                        <button
                          type="button"
                          class="cart-item__qty-btn"
                          :disabled="line.quantity <= 1"
                          aria-label="Decrease"
                          @click="bumpQty(line.productId, -1)"
                        >
                          <Minus :size="13" :stroke-width="2.5" />
                        </button>
                        <span class="cart-item__qty-num">{{ line.quantity }}</span>
                        <button
                          type="button"
                          class="cart-item__qty-btn"
                          aria-label="Increase"
                          @click="bumpQty(line.productId, 1)"
                        >
                          <Plus :size="13" :stroke-width="2.5" />
                        </button>
                      </div>
                      <button
                        type="button"
                        class="cart-item__remove"
                        aria-label="Remove"
                        @click="cart.removeLine(line.productId)"
                      >
                        <Trash2 :size="14" :stroke-width="2" />
                        Remove
                      </button>
                    </div>
                  </div>
                </li>
              </ul>
            </div>

            <CartFreeGiftPicker
              v-if="cart.hasFreeGift"
              compact
              :options="cart.freeGiftOptions"
            />
          </div>

          <footer class="cart__foot">
            <div class="cart__summary">
              <div class="cart__sum-row">
                <span>Subtotal</span>
                <span>{{ fmt(cart.subtotal) }}</span>
              </div>
              <div v-if="cart.discountAmount > 0" class="cart__sum-row cart__sum-row--off">
                <span>12% off</span>
                <span>−{{ fmt(cart.discountAmount) }}</span>
              </div>
              <div v-if="cart.selectedFreeGift" class="cart__sum-row cart__sum-row--gift">
                <span>Free gift</span>
                <span class="cart__gift-name">{{ cart.selectedFreeGift.name }}</span>
              </div>
              <div class="cart__sum-row">
                <span>Shipping</span>
                <span>{{ cart.shippingFee === 0 ? 'Free' : fmt(cart.shippingFee) }}</span>
              </div>
              <div class="cart__sum-row cart__sum-row--total">
                <span>Total</span>
                <span>{{ fmt(cart.total) }}</span>
              </div>
            </div>

            <p v-if="cart.hasFreeGift && !cart.freeGiftReady" class="cart__gift-warn">
              Pick a free gift above to continue
            </p>

            <button
              type="button"
              class="cart__checkout"
              :class="{ 'cart__checkout--disabled': !cart.freeGiftReady }"
              :disabled="!cart.freeGiftReady"
              @click="goCheckout"
            >
              Checkout · {{ fmt(cart.total) }}
              <ArrowRight :size="16" :stroke-width="2.5" aria-hidden="true" />
            </button>
          </footer>
        </template>
      </aside>
    </Transition>
  </Teleport>
</template>

<style scoped>
.cart-backdrop {
  position: fixed;
  inset: 0;
  z-index: 200;
  background: var(--tm-overlay);
  backdrop-filter: blur(4px);
}

.cart {
  position: fixed;
  top: 0;
  right: 0;
  z-index: 201;
  display: flex;
  flex-direction: column;
  width: min(100%, 24rem);
  max-width: 100vw;
  height: 100dvh;
  min-height: 0;
  background: var(--color-page);
  box-shadow: -12px 0 40px rgba(20, 19, 18, 0.12);
}

@media (max-width: 1023px) {
  .cart {
    top: auto;
    bottom: 0;
    left: 0;
    right: 0;
    width: 100%;
    max-width: 100%;
    height: min(94dvh, 100%);
    border-radius: 18px 18px 0 0;
    box-shadow: 0 -16px 48px rgba(20, 19, 18, 0.14);
  }
}

.cart__head {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 0.85rem;
  background: var(--color-surface);
  border-bottom: 1px solid var(--color-border);
}

.cart__title {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  margin: 0;
  font-family: var(--font-ui);
  font-size: 0.9375rem;
  font-weight: 700;
  letter-spacing: -0.02em;
  color: var(--color-ink);
}

.cart__count {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 1.25rem;
  height: 1.25rem;
  padding: 0 0.35rem;
  border-radius: 999px;
  font-size: 0.6875rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  color: var(--color-accent);
  background: var(--color-accent-soft);
}

.cart__close {
  display: grid;
  place-items: center;
  width: 2rem;
  height: 2rem;
  border: none;
  border-radius: 8px;
  background: transparent;
  color: var(--color-ink-muted);
  cursor: pointer;
}

.cart__close:hover {
  background: var(--color-page);
  color: var(--color-ink);
}

/* Scrollable middle: milestones + items + compact gift */
.cart__body {
  flex: 1;
  min-height: 0;
  overflow-y: auto;
  overscroll-behavior: contain;
  scrollbar-width: thin;
}

.cart__items {
  padding: 0 0.65rem;
}

.cart__items-label {
  margin: 0;
  padding: 0.55rem 0.1rem 0.4rem;
  font-family: var(--font-ui);
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  color: var(--color-ink-muted);
}

.cart__list {
  margin: 0;
  padding: 0 0 0.65rem;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
}

.cart-item {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  padding: 0.65rem;
  border-radius: 12px;
  background: var(--color-surface-elevated);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-sm);
}

.cart-item__info {
  flex: 1;
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.3rem;
}

.cart-item__top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 0.5rem;
}

.cart-item__name {
  margin: 0;
  font-family: var(--font-ui);
  font-size: 0.8125rem;
  font-weight: 650;
  line-height: 1.35;
  color: var(--color-ink);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.cart-item__total {
  margin: 0;
  flex-shrink: 0;
  font-family: var(--font-ui);
  font-size: 0.8125rem;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
  color: var(--color-ink);
}

.cart-item__unit {
  margin: 0;
  font-size: 0.6875rem;
  color: var(--color-ink-faint);
}

.cart-item__actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
  margin-top: 0.15rem;
}

.cart-item__qty {
  display: inline-flex;
  align-items: center;
  height: 1.85rem;
  border: 1px solid var(--color-border);
  border-radius: 8px;
  background: var(--color-page);
}

.cart-item__qty-btn {
  display: grid;
  place-items: center;
  width: 1.85rem;
  height: 100%;
  border: none;
  background: transparent;
  color: var(--color-ink-muted);
  cursor: pointer;
}

.cart-item__qty-btn:hover:not(:disabled) {
  color: var(--color-accent);
}

.cart-item__qty-btn:disabled {
  opacity: 0.3;
  cursor: not-allowed;
}

.cart-item__qty-num {
  min-width: 1.5rem;
  text-align: center;
  font-family: var(--font-ui);
  font-size: 0.75rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.cart-item__remove {
  display: inline-flex;
  align-items: center;
  gap: 0.2rem;
  padding: 0.25rem 0.35rem;
  border: none;
  border-radius: 7px;
  background: transparent;
  font-family: var(--font-ui);
  font-size: 0.6875rem;
  font-weight: 600;
  color: var(--color-ink-faint);
  cursor: pointer;
}

.cart-item__remove:hover {
  color: var(--color-sale);
  background: rgba(184, 69, 61, 0.08);
}

.cart__foot {
  flex-shrink: 0;
  padding: 0.75rem 0.85rem max(0.85rem, env(safe-area-inset-bottom));
  background: var(--color-surface);
  border-top: 1px solid var(--color-border);
  box-shadow: 0 -4px 20px rgba(20, 19, 18, 0.06);
}

.cart__gift-warn {
  margin: 0 0 0.45rem;
  font-size: 0.6875rem;
  font-weight: 600;
  color: var(--color-sale);
  text-align: center;
}

.cart__summary {
  margin-bottom: 0.6rem;
  font-family: var(--font-ui);
  font-size: 0.75rem;
  font-variant-numeric: tabular-nums;
  color: var(--color-ink-muted);
}

.cart__sum-row {
  display: flex;
  justify-content: space-between;
  padding: 0.12rem 0;
}

.cart__sum-row--off span:last-child {
  color: var(--color-sale);
  font-weight: 700;
}

.cart__sum-row--total {
  margin-top: 0.3rem;
  padding-top: 0.4rem;
  border-top: 1px solid var(--color-border);
  font-size: 0.875rem;
  font-weight: 700;
  color: var(--color-ink);
}

.cart__checkout {
  display: flex;
  width: 100%;
  min-height: 2.85rem;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  border: none;
  border-radius: 10px;
  background: var(--tm-gradient);
  color: var(--tm-on-accent);
  font-family: var(--font-ui);
  font-size: 0.8125rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
  cursor: pointer;
  transition: background 0.15s ease;
}

.cart__checkout:hover:not(:disabled) {
  background: var(--tm-gradient-hover);
}

.cart__checkout--disabled,
.cart__checkout:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}

.cart__sum-row--gift .cart__gift-name {
  max-width: 9rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-weight: 600;
  color: var(--color-accent);
}

.cart__empty {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2rem 1rem;
  color: var(--color-accent);
}

.cart__empty-title {
  margin: 0;
  font-family: var(--font-ui);
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-ink-muted);
}

.cart__cta {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.5rem 1rem;
  border: none;
  border-radius: 10px;
  background: var(--tm-gradient);
  color: var(--tm-on-accent);
  font-family: var(--font-ui);
  font-size: 0.8125rem;
  font-weight: 700;
  cursor: pointer;
}

.cart-backdrop-enter-active,
.cart-backdrop-leave-active {
  transition: opacity 0.22s ease;
}

.cart-backdrop-enter-from,
.cart-backdrop-leave-to {
  opacity: 0;
}

.cart-panel-enter-active,
.cart-panel-leave-active {
  transition: transform 0.28s var(--ease-out, ease);
}

.cart-panel-enter-from,
.cart-panel-leave-to {
  transform: translateX(100%);
}

@media (max-width: 1023px) {
  .cart-panel-enter-from,
  .cart-panel-leave-to {
    transform: translateY(100%);
  }
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

<script setup lang="ts">
import { CircleUser, Heart, Home, ShoppingBag, Store } from 'lucide-vue-next'
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { storeToRefs } from 'pinia'
import { useAuthStore } from '@/stores/auth'
import { useCartStore } from '@/stores/cart'
import { useCartUiStore } from '@/stores/cartUi'
import { useWishlistStore } from '@/stores/wishlist'

const route = useRoute()
const router = useRouter()
const cart = useCartStore()
const cartUi = useCartUiStore()
const { isOpen: cartOpen } = storeToRefs(cartUi)
const wishlist = useWishlistStore()
const auth = useAuthStore()
const { isAuthenticated } = storeToRefs(auth)

const cartBadge = computed(() => cart.totalQuantity)
const wishlistBadge = computed(() => wishlist.count)

const accountTo = computed(() => (isAuthenticated.value ? '/account' : '/login'))

function isActive(name: string): boolean {
  return route.name === name
}

function isShopActive(): boolean {
  return route.name === 'shop' || route.name === 'shop-category' || route.name === 'create-your-set'
}

function openCart() {
  if (route.name === 'checkout') return
  cartUi.open()
}

function go(path: string) {
  if (route.path !== path) router.push(path)
}
</script>

<template>
  <nav class="app-tab" aria-label="App navigation">
    <button
      type="button"
      class="app-tab__item"
      :class="{ 'app-tab__item--active': isActive('home') }"
      @click="go('/')"
    >
      <Home :size="22" :stroke-width="isActive('home') ? 2.5 : 2" aria-hidden="true" />
      <span>Home</span>
    </button>

    <button
      type="button"
      class="app-tab__item"
      :class="{ 'app-tab__item--active': isShopActive() }"
      @click="go('/shop')"
    >
      <Store :size="22" :stroke-width="isShopActive() ? 2.5 : 2" aria-hidden="true" />
      <span>Shop</span>
    </button>

    <button
      type="button"
      class="app-tab__item app-tab__item--cart"
      :class="{ 'app-tab__item--active': cartOpen || isActive('cart') }"
      aria-label="Open cart"
      @click="openCart"
    >
      <span class="app-tab__cart-wrap">
        <ShoppingBag :size="23" :stroke-width="2.25" aria-hidden="true" />
        <span v-if="cartBadge > 0" class="app-tab__badge">{{ cartBadge > 99 ? '99+' : cartBadge }}</span>
      </span>
      <span>Cart</span>
    </button>

    <button
      type="button"
      class="app-tab__item"
      :class="{ 'app-tab__item--active': isActive('wishlist') }"
      @click="go('/wishlist')"
    >
      <span class="app-tab__icon-wrap">
        <Heart :size="22" :stroke-width="isActive('wishlist') ? 2.5 : 2" aria-hidden="true" />
        <span v-if="wishlistBadge > 0" class="app-tab__badge app-tab__badge--wish">{{ wishlistBadge }}</span>
      </span>
      <span>Wishlist</span>
    </button>

    <button
      type="button"
      class="app-tab__item"
      :class="{ 'app-tab__item--active': isActive('account') || isActive('login') }"
      @click="go(accountTo)"
    >
      <CircleUser
        :size="22"
        :stroke-width="isActive('account') || isActive('login') ? 2.5 : 2"
        aria-hidden="true"
      />
      <span>{{ isAuthenticated ? 'Account' : 'Sign in' }}</span>
    </button>
  </nav>
</template>

<style scoped>
.app-tab {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  z-index: 120;
  display: grid;
  grid-template-columns: repeat(5, 1fr);
  gap: 0;
  min-height: var(--app-tab-h);
  padding: 0.35rem 0.25rem max(0.35rem, env(safe-area-inset-bottom));
  background: var(--tm-glass);
  backdrop-filter: blur(16px) saturate(1.15);
  -webkit-backdrop-filter: blur(16px) saturate(1.15);
  border-top: 1px solid var(--color-border);
  box-shadow: 0 -8px 28px rgba(20, 19, 18, 0.06);
}

.app-tab__item {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.15rem;
  min-height: 2.75rem;
  padding: 0.15rem 0.1rem;
  border: none;
  background: transparent;
  color: var(--color-ink-faint);
  font-family: var(--font-ui);
  font-size: 0.5625rem;
  font-weight: 700;
  letter-spacing: 0.02em;
  cursor: pointer;
  -webkit-tap-highlight-color: transparent;
  touch-action: manipulation;
  transition: color 0.15s ease;
}

.app-tab__item:active {
  transform: scale(0.96);
}

.app-tab__item--active {
  color: var(--color-accent);
}

.app-tab__item--cart {
  position: relative;
}

.app-tab__cart-wrap,
.app-tab__icon-wrap {
  position: relative;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.app-tab__item--cart .app-tab__cart-wrap {
  width: 2.35rem;
  height: 2.35rem;
  border-radius: 999px;
  background: var(--tm-gradient);
  color: var(--tm-on-accent);
  box-shadow: var(--tm-shadow-accent);
}

.app-tab__item--cart.app-tab__item--active .app-tab__cart-wrap {
  box-shadow: var(--tm-shadow-md);
}

.app-tab__item--cart span:last-child {
  color: var(--color-ink-muted);
}

.app-tab__item--cart.app-tab__item--active span:last-child {
  color: var(--color-accent);
}

.app-tab__badge {
  position: absolute;
  top: -4px;
  right: -7px;
  min-width: 16px;
  height: 16px;
  padding: 0 4px;
  border-radius: 999px;
  background: var(--color-highlight);
  color: #fff;
  font-size: 9px;
  font-weight: 800;
  line-height: 16px;
  text-align: center;
  box-shadow: 0 2px 6px rgba(196, 92, 60, 0.35);
}

.app-tab__badge--wish {
  background: var(--color-accent);
  box-shadow: 0 2px 6px rgba(45, 92, 82, 0.35);
}

.app-tab__item--cart .app-tab__badge {
  top: -2px;
  right: -2px;
  background: #fff;
  color: var(--color-accent);
  box-shadow: 0 2px 6px rgba(20, 19, 18, 0.2);
}
</style>

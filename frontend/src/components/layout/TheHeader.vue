<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import type { RouteLocationRaw } from 'vue-router'
import { useRoute, useRouter } from 'vue-router'
import { ChevronDown, CircleUser, Heart, LogIn, Menu, Search, ShoppingBag, X } from 'lucide-vue-next'
import { storeToRefs } from 'pinia'
import SiteLogo from '@/components/layout/SiteLogo.vue'
import { HEADER_NAV } from '@/data/navMenu'
import { useCartStore } from '@/stores/cart'
import { useCartUiStore } from '@/stores/cartUi'
import { useAuthStore } from '@/stores/auth'
import { useWishlistStore } from '@/stores/wishlist'

const cart = useCartStore()
const cartUi = useCartUiStore()
const wishlist = useWishlistStore()
const auth = useAuthStore()
const { isAuthenticated } = storeToRefs(auth)
const route = useRoute()
const router = useRouter()
const badge = computed(() => cart.totalQuantity)
const wishlistBadge = computed(() => wishlist.count)
const menuOpen = ref(false)
const searchOpen = ref(false)
const searchQ = ref('')
const searchInputRef = ref<HTMLInputElement | null>(null)
const searchWrapRef = ref<HTMLElement | null>(null)
const openDropdown = ref<string | null>(null)
const openMobileSection = ref<string | null>(null)

const nav = HEADER_NAV

function toggleDropdown(id: string) {
  openDropdown.value = openDropdown.value === id ? null : id
}

function closeDropdowns() {
  openDropdown.value = null
}

function toggleMobileSection(id: string) {
  openMobileSection.value = openMobileSection.value === id ? null : id
}

function navActive(item: (typeof nav)[number]): boolean {
  if (item.to && routeMatches(item.to)) return true
  return Boolean(item.children?.some((child) => routeMatches(child.to)))
}

function routeMatches(to: RouteLocationRaw): boolean {
  const resolved = router.resolve(to)
  return resolved.path === route.path && queriesMatch(resolved.query, route.query)
}

function queriesMatch(a: Record<string, unknown>, b: Record<string, unknown>): boolean {
  const keys = new Set([...Object.keys(a), ...Object.keys(b)])
  for (const key of keys) {
    if (String(a[key] ?? '') !== String(b[key] ?? '')) return false
  }
  return true
}

function onNavClick() {
  menuOpen.value = false
  closeDropdowns()
  openMobileSection.value = null
}

watch(
  () => [route.path, route.query.q] as const,
  () => {
    if (route.path === '/shop') {
      searchQ.value = String(route.query.q ?? '')
    }
  },
  { immediate: true }
)

function onSearch(e: Event) {
  e.preventDefault()
  router.push({
    path: '/shop',
    query: { q: searchQ.value.trim() || undefined },
  })
  menuOpen.value = false
  searchOpen.value = false
}

async function openSearch() {
  searchOpen.value = true
  await nextTick()
  searchInputRef.value?.focus()
}

function closeSearch() {
  searchOpen.value = false
}

function onSearchKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') closeSearch()
}

function onDocumentPointerDown(e: PointerEvent) {
  if (!searchOpen.value) return
  const root = searchWrapRef.value
  if (root && !root.contains(e.target as Node)) closeSearch()
}

onMounted(() => {
  document.addEventListener('pointerdown', onDocumentPointerDown)
})

onUnmounted(() => {
  document.removeEventListener('pointerdown', onDocumentPointerDown)
})
</script>

<template>
  <header class="head">
    <div class="head__bar tm-container">
      <button
        type="button"
        class="head__burger"
        :aria-label="menuOpen ? 'Close menu' : 'Open menu'"
        :aria-expanded="menuOpen"
        @click="menuOpen = !menuOpen"
      >
        <Menu v-if="!menuOpen" :size="22" :stroke-width="2" class="head__burger-ico" />
        <X v-else :size="22" :stroke-width="2" class="head__burger-ico" />
      </button>

      <RouterLink to="/" class="head__brand" @click="menuOpen = false">
        <SiteLogo size="header" />
      </RouterLink>

      <nav class="head__nav" aria-label="Main">
        <ul class="head__menu">
          <li
            v-for="item in nav"
            :key="item.id"
            class="head__menu-item"
            :class="{
              'head__menu-item--open': openDropdown === item.id,
              'head__menu-item--active': navActive(item),
            }"
            @mouseenter="item.children ? (openDropdown = item.id) : undefined"
            @mouseleave="item.children ? closeDropdowns() : undefined"
          >
            <RouterLink
              v-if="item.to && !item.children"
              :to="item.to"
              class="head__menu-link"
              @click="onNavClick"
            >
              {{ item.label }}
            </RouterLink>
            <template v-else-if="item.children">
              <button
                type="button"
                class="head__menu-link head__menu-trigger"
                :aria-expanded="openDropdown === item.id"
                @click="toggleDropdown(item.id)"
              >
                {{ item.label }}
                <ChevronDown class="head__menu-chevron" :size="14" :stroke-width="2.25" aria-hidden="true" />
              </button>
              <ul class="head__submenu">
                <li v-for="child in item.children" :key="child.label">
                  <RouterLink :to="child.to" class="head__submenu-link" @click="onNavClick">
                    <span class="head__submenu-label">{{ child.label }}</span>
                    <span v-if="child.description" class="head__submenu-desc">{{ child.description }}</span>
                  </RouterLink>
                </li>
              </ul>
            </template>
          </li>
        </ul>
      </nav>

      <div class="head__tools">
        <RouterLink to="/wishlist" class="head__icon-link head__wishlist" aria-label="Wishlist" @click="menuOpen = false">
          <Heart :size="21" :stroke-width="2" />
          <span v-if="wishlistBadge > 0" class="head__badge head__badge--wish">{{ wishlistBadge }}</span>
        </RouterLink>
        <RouterLink
          v-if="!isAuthenticated"
          to="/login"
          class="head__icon-link"
          aria-label="Sign in"
          @click="menuOpen = false"
        >
          <LogIn :size="21" :stroke-width="2" />
        </RouterLink>
        <RouterLink
          v-else
          to="/account"
          class="head__icon-link"
          aria-label="Account"
          @click="menuOpen = false"
        >
          <CircleUser :size="21" :stroke-width="2" />
        </RouterLink>
        <button
          type="button"
          class="head__cart head__icon-link"
          aria-label="Open cart"
          @click="cartUi.open(); menuOpen = false"
        >
          <ShoppingBag :size="21" :stroke-width="2" />
          <span v-if="badge > 0" class="head__badge">{{ badge }}</span>
        </button>

        <div
          ref="searchWrapRef"
          class="head__search-wrap"
          :class="{ 'head__search-wrap--open': searchOpen }"
        >
          <button
            v-if="!searchOpen"
            type="button"
            class="head__search-toggle"
            aria-label="Open search"
            @click="openSearch"
          >
            <Search :size="18" :stroke-width="2" />
          </button>
          <form v-else class="head__search" role="search" @submit="onSearch">
            <Search class="head__search-ico" :size="15" :stroke-width="2" aria-hidden="true" />
            <label class="sr-only" for="global-search">Search products</label>
            <input
              id="global-search"
              ref="searchInputRef"
              v-model="searchQ"
              type="search"
              class="head__search-input"
              placeholder="Search products…"
              autocomplete="off"
              @keydown="onSearchKeydown"
            />
            <button type="button" class="head__search-close" aria-label="Close search" @click="closeSearch">
              <X :size="14" :stroke-width="2.25" />
            </button>
          </form>
        </div>
      </div>
    </div>

    <div v-if="menuOpen" class="head__drawer">
      <nav aria-label="Mobile">
        <ul>
          <li v-for="item in nav" :key="'m-' + item.id" class="head__drawer-item">
            <RouterLink
              v-if="item.to && !item.children"
              :to="item.to"
              @click="onNavClick"
            >
              {{ item.label }}
            </RouterLink>
            <template v-else-if="item.children">
              <button
                type="button"
                class="head__drawer-trigger"
                :aria-expanded="openMobileSection === item.id"
                @click="toggleMobileSection(item.id)"
              >
                {{ item.label }}
                <ChevronDown
                  class="head__drawer-chevron"
                  :class="{ 'head__drawer-chevron--open': openMobileSection === item.id }"
                  :size="18"
                  :stroke-width="2"
                  aria-hidden="true"
                />
              </button>
              <ul v-if="openMobileSection === item.id" class="head__drawer-sub">
                <li v-for="child in item.children" :key="'m-' + item.id + '-' + child.label">
                  <RouterLink :to="child.to" @click="onNavClick">{{ child.label }}</RouterLink>
                </li>
              </ul>
            </template>
          </li>
          <li>
            <RouterLink to="/wishlist" @click="menuOpen = false">Wishlist ({{ wishlistBadge }})</RouterLink>
          </li>
          <li>
            <button type="button" class="head__drawer-cart" @click="cartUi.open(); menuOpen = false">
              Cart ({{ badge }})
            </button>
          </li>
          <li v-if="badge > 0">
            <RouterLink to="/checkout" @click="menuOpen = false">Checkout</RouterLink>
          </li>
          <li v-if="!isAuthenticated">
            <RouterLink to="/login" @click="menuOpen = false">Sign in</RouterLink>
          </li>
          <li v-if="!isAuthenticated">
            <RouterLink to="/register" @click="menuOpen = false">Create account</RouterLink>
          </li>
          <li v-else>
            <RouterLink to="/account" @click="menuOpen = false">My account</RouterLink>
          </li>
        </ul>
      </nav>
    </div>
  </header>
</template>

<style scoped>
.head {
  position: sticky;
  top: 0;
  z-index: 100;
  background: rgba(255, 252, 248, 0.88);
  backdrop-filter: blur(14px) saturate(1.2);
  -webkit-backdrop-filter: blur(14px) saturate(1.2);
  border-bottom: 1px solid var(--color-border);
  box-shadow: 0 4px 24px rgba(20, 19, 18, 0.04);
}

.head__bar {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: 0.65rem 0.85rem;
  min-height: var(--header-h);
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}

.head__nav {
  display: none;
}

@media (min-width: 1024px) {
  .head__bar {
    display: grid;
    grid-template-columns: minmax(0, max-content) minmax(0, 1fr) auto;
    column-gap: 1.1rem;
    align-items: center;
  }

  .head__burger {
    display: none !important;
  }

  .head__brand {
    grid-column: 1;
    min-width: 0;
  }

  .head__nav {
    grid-column: 2;
    display: flex !important;
    justify-content: flex-end;
    min-width: 0;
    overflow: visible;
  }

  .head__tools {
    grid-column: 3;
    justify-self: end;
  }
}

@media (max-width: 1023px) {
  .head__brand {
    flex: 1 1 auto;
    min-width: 0;
  }
}

.head__burger {
  display: none;
  align-items: center;
  justify-content: center;
  padding: 0.35rem;
  border: none;
  background: transparent;
  cursor: pointer;
  border-radius: var(--radius-sm);
  color: var(--color-ink);
  transition:
    background var(--duration, 0.22s) var(--ease-out, ease),
    color 0.2s ease;
}

.head__burger:hover {
  background: var(--color-accent-soft);
  color: var(--color-accent);
}

@media (max-width: 1023px) {
  .head__burger {
    display: flex;
  }
}

.head__burger-ico {
  display: block;
}

.head__brand {
  display: inline-flex;
  align-items: center;
  color: inherit;
  text-decoration: none;
  min-width: 0;
  line-height: 0;
  transition: opacity 0.2s ease;
}

.head__brand:hover {
  opacity: 0.88;
}

.head__search-wrap {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  flex-shrink: 0;
}

.head__search {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  width: min(15rem, 44vw);
  height: 2.125rem;
  padding: 0 0.35rem 0 0.65rem;
  border: 1px solid var(--color-border);
  border-radius: 999px;
  background: var(--color-surface-elevated);
  box-shadow: var(--shadow-sm);
  overflow: hidden;
  animation: head-search-in 0.22s var(--ease-out, ease);
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.head__search:focus-within {
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px var(--color-accent-soft);
}

.head__search-ico {
  flex-shrink: 0;
  color: var(--color-ink-faint);
  pointer-events: none;
}

.head__search-input {
  flex: 1;
  width: 100%;
  border: none;
  padding: 0;
  font: inherit;
  font-size: 0.8125rem;
  min-width: 0;
  background: transparent;
  color: var(--color-ink);
  appearance: none;
  -webkit-appearance: none;
}

.head__search-input::-webkit-search-decoration,
.head__search-input::-webkit-search-cancel-button {
  appearance: none;
  -webkit-appearance: none;
}

.head__search-input::placeholder {
  color: var(--color-ink-faint);
}

.head__search-input:focus,
.head__search-input:focus-visible {
  outline: none;
}

.head__search-close {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.5rem;
  height: 1.5rem;
  padding: 0;
  border: none;
  border-radius: 999px;
  background: transparent;
  cursor: pointer;
  color: var(--color-ink-muted);
  transition:
    background 0.2s ease,
    color 0.2s ease;
}

.head__search-close:hover {
  background: var(--color-accent-soft);
  color: var(--color-accent);
}

.head__search-close:focus-visible {
  outline: none;
  box-shadow: 0 0 0 2px var(--color-accent-soft);
}

.head__search-toggle {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  padding: 0;
  border: none;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--color-ink-muted);
  cursor: pointer;
  transition:
    color 0.2s ease,
    background 0.2s ease;
}

.head__search-toggle:hover {
  color: var(--color-accent);
  background: var(--color-accent-soft);
}

@keyframes head-search-in {
  from {
    opacity: 0;
    transform: translateX(0.35rem);
  }

  to {
    opacity: 1;
    transform: translateX(0);
  }
}

.head__menu {
  list-style: none;
  margin: 0;
  padding: 0 0.15rem;
  display: flex;
  flex-wrap: nowrap;
  gap: 0.15rem 0.75rem;
  justify-content: flex-end;
  align-items: center;
}

.head__menu-item {
  position: relative;
  flex-shrink: 0;
}

.head__menu-link {
  display: inline-flex;
  align-items: center;
  gap: 0.2rem;
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--color-ink-muted);
  padding: 0.4rem 0.35rem;
  white-space: nowrap;
  border-radius: var(--radius-sm);
  border: none;
  background: transparent;
  cursor: pointer;
  font-family: inherit;
  text-decoration: none;
  transition:
    color 0.2s ease,
    background 0.2s ease;
}

.head__menu-link:hover,
.head__menu-item--active > .head__menu-link,
.head__menu-item--active > .head__menu-trigger {
  color: var(--color-accent);
  background: var(--color-accent-soft);
}

.head__menu-chevron {
  transition: transform 0.2s ease;
}

.head__menu-item--open .head__menu-chevron {
  transform: rotate(180deg);
}

.head__submenu {
  list-style: none;
  margin: 0;
  padding: 0.35rem;
  position: absolute;
  top: 100%;
  left: 0;
  min-width: 11.5rem;
  padding-top: 0.4rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-surface-elevated);
  box-shadow: var(--shadow-card);
  opacity: 0;
  visibility: hidden;
  transform: translateY(-4px);
  transition:
    opacity 0.18s ease,
    transform 0.18s ease,
    visibility 0.18s ease;
  z-index: 20;
}

.head__submenu::before {
  content: '';
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  height: 0.4rem;
}

.head__menu-item--open .head__submenu,
.head__menu-item:hover .head__submenu {
  opacity: 1;
  visibility: visible;
  transform: translateY(0);
}

.head__submenu-link {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
  padding: 0.55rem 0.65rem;
  border-radius: var(--radius-sm);
  text-decoration: none;
  transition: background 0.2s ease;
}

.head__submenu-link:hover,
.head__submenu-link.router-link-active {
  background: var(--color-accent-soft);
}

.head__submenu-label {
  font-size: 0.8125rem;
  font-weight: 650;
  color: var(--color-ink);
}

.head__submenu-desc {
  font-size: 0.72rem;
  color: var(--color-ink-faint);
  line-height: 1.35;
}

.head__tools {
  display: flex;
  align-items: center;
  gap: 0.1rem;
}

.head__icon-link,
.head__cart {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: var(--tap-min);
  min-height: var(--tap-min);
  padding: 0;
  border: none;
  background: transparent;
  color: var(--color-ink-muted);
  border-radius: var(--radius-sm);
  cursor: pointer;
  text-decoration: none;
  font: inherit;
  transition:
    color 0.2s ease,
    background 0.2s ease,
    transform 0.2s var(--ease-out, ease);
}

.head__icon-link:hover,
.head__cart:hover {
  color: var(--color-accent);
  background: var(--color-accent-soft);
  transform: translateY(-2px);
}

.head__cart,
.head__wishlist {
  position: relative;
  text-decoration: none;
}

.head__badge--wish {
  background: linear-gradient(135deg, var(--color-accent), #1a4a42);
  box-shadow: 0 2px 8px rgba(45, 92, 82, 0.35);
}

.head__badge {
  position: absolute;
  top: 5px;
  right: 4px;
  min-width: 18px;
  height: 18px;
  padding: 0 5px;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--color-highlight), var(--color-accent));
  color: #fff;
  font-size: 10px;
  font-weight: 800;
  line-height: 18px;
  text-align: center;
  box-shadow: 0 2px 8px rgba(196, 92, 60, 0.35);
}

.head__drawer {
  display: none;
  border-top: 1px solid var(--color-border);
  padding: 1rem 1.25rem 1.5rem;
  background: var(--color-surface);
}

.head__drawer ul {
  list-style: none;
  margin: 0;
  padding: 0;
}

.head__drawer-item > a,
.head__drawer-cart,
.head__drawer-trigger {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 0.75rem 0;
  font-weight: 600;
  color: var(--color-ink);
  border-bottom: 1px solid var(--color-border);
  text-decoration: none;
  background: transparent;
  border-top: none;
  border-left: none;
  border-right: none;
  cursor: pointer;
  font-family: inherit;
  font-size: inherit;
  text-align: left;
}

.head__drawer-chevron {
  flex-shrink: 0;
  transition: transform 0.2s ease;
}

.head__drawer-chevron--open {
  transform: rotate(180deg);
}

.head__drawer-sub {
  list-style: none;
  margin: 0;
  padding: 0 0 0.35rem 0.85rem;
}

.head__drawer-sub a {
  display: block;
  padding: 0.55rem 0;
  font-weight: 600;
  font-size: 0.9rem;
  color: var(--color-ink-muted);
  border-bottom: 1px solid var(--color-border);
  text-decoration: none;
}

.head__drawer-sub a.router-link-active {
  color: var(--color-accent);
}

@media (max-width: 1023px) {
  .head__drawer {
    display: block;
  }
}

.sr-only {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}
</style>

<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useCartStore } from '@/stores/cart'

const cart = useCartStore()
const badge = computed(() => cart.totalQuantity)
</script>

<template>
  <header class="header">
    <div class="header__inner">
      <RouterLink to="/" class="header__brand"> The Minimark </RouterLink>

      <nav class="header__nav" aria-label="Main">
        <RouterLink to="/" class="header__link"> Home </RouterLink>
        <RouterLink to="/shop" class="header__link"> Shop </RouterLink>
        <RouterLink to="/cart" class="header__link header__link--cart">
          Cart
          <span v-if="badge > 0" class="header__badge">{{ badge }}</span>
        </RouterLink>
      </nav>
    </div>
  </header>
</template>

<style scoped>
.header {
  position: sticky;
  top: 0;
  z-index: 20;
  border-bottom: 1px solid var(--color-border);
  background: color-mix(in srgb, var(--color-bg) 92%, transparent);
  backdrop-filter: blur(10px);
}

.header__inner {
  max-width: var(--content-max);
  margin: 0 auto;
  padding: 0.75rem 1.25rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.header__brand {
  font-weight: 650;
  letter-spacing: -0.03em;
  font-size: 1.1rem;
  color: var(--color-text);
  text-decoration: none;
}

.header__brand:hover {
  color: var(--color-accent);
}

.header__nav {
  display: flex;
  align-items: center;
  gap: 0.35rem;
}

.header__link {
  padding: 0.45rem 0.65rem;
  border-radius: 0.5rem;
  font-size: 0.95rem;
  font-weight: 500;
  color: var(--color-text-muted);
  text-decoration: none;
}

.header__link.router-link-active {
  color: var(--color-text);
  background: var(--color-surface);
}

.header__link:hover {
  color: var(--color-text);
}

.header__link--cart {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.header__badge {
  min-width: 1.25rem;
  height: 1.25rem;
  padding: 0 0.35rem;
  border-radius: 999px;
  font-size: 0.7rem;
  font-weight: 700;
  line-height: 1.25rem;
  text-align: center;
  background: var(--color-accent);
  color: var(--color-accent-contrast);
}
</style>

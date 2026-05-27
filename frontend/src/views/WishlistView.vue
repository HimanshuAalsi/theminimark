<script setup lang="ts">
import { Heart, ShoppingBag } from 'lucide-vue-next'
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import ProductCard from '@/components/product/ProductCard.vue'
import { useAuthStore } from '@/stores/auth'
import { useCatalogStore } from '@/stores/catalog'
import { useWishlistStore } from '@/stores/wishlist'
import { storeToRefs } from 'pinia'

const auth = useAuthStore()
const catalogStore = useCatalogStore()
const wishlist = useWishlistStore()
const { isAuthenticated } = storeToRefs(auth)
const { catalog } = storeToRefs(catalogStore)
const { productIds, ready } = storeToRefs(wishlist)

void catalogStore.ensureLoaded()
void wishlist.ensureLoaded()

const items = computed(() => {
  const ids = new Set(productIds.value)
  return catalog.value.filter((p) => ids.has(p.id))
})
</script>

<template>
  <div class="wishlist tm-section tm-animate-in">
    <div class="tm-container">
      <header class="wishlist__head">
        <p class="wishlist__eyebrow">
          <Heart :size="16" aria-hidden="true" />
          Saved for later
        </p>
        <h1 class="wishlist__title">Wishlist</h1>
        <p v-if="!isAuthenticated" class="wishlist__lead">
          Items are saved on this device. <RouterLink to="/login">Sign in</RouterLink> to keep your
          wishlist tied to your account.
        </p>
        <p v-else class="wishlist__lead">
          Your saved items are linked to <strong>{{ auth.user?.email }}</strong>.
        </p>
      </header>

      <p v-if="!ready" class="wishlist__status">Loading wishlist…</p>

      <div v-else-if="items.length === 0" class="wishlist__empty tm-hover-lift">
        <Heart :size="40" :stroke-width="1.5" aria-hidden="true" />
        <p class="wishlist__empty-text">Your wishlist is empty.</p>
        <RouterLink to="/shop" class="wishlist__cta">
          <ShoppingBag :size="18" :stroke-width="2.25" aria-hidden="true" />
          Browse shop
        </RouterLink>
      </div>

      <div v-else class="wishlist__grid">
        <ProductCard v-for="p in items" :key="p.id" :product="p" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.wishlist {
  padding-top: 2rem;
  padding-bottom: 4rem;
}

.wishlist__head {
  max-width: 38rem;
  margin-bottom: 2rem;
}

.wishlist__eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin: 0 0 0.5rem;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--color-accent);
}

.wishlist__title {
  margin: 0 0 0.5rem;
  font-family: var(--font-display);
  font-size: clamp(1.85rem, 3vw, 2.35rem);
  font-weight: 500;
}

.wishlist__lead {
  margin: 0;
  color: var(--color-ink-muted);
  line-height: 1.6;
}

.wishlist__lead a {
  font-weight: 650;
  color: var(--color-accent);
}

.wishlist__status {
  color: var(--color-ink-muted);
}

.wishlist__empty {
  max-width: 24rem;
  margin: 0 auto;
  padding: 2.5rem 1.5rem;
  text-align: center;
  border: 1px dashed var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-surface-elevated);
  color: var(--color-ink-muted);
}

.wishlist__empty-text {
  margin: 0.75rem 0 1.25rem;
  font-weight: 600;
  color: var(--color-ink);
}

.wishlist__cta {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  min-height: var(--tap-min);
  padding: 0 1.25rem;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--color-accent), #1a4a42);
  color: #fff;
  font-weight: 700;
  text-decoration: none;
}

.wishlist__grid {
  display: grid;
  gap: 1.1rem;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
}
</style>

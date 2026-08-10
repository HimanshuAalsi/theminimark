<script setup lang="ts">
import { Heart, ShoppingBag } from 'lucide-vue-next'
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import ProductCard from '@/components/product/ProductCard.vue'
import ProductGridSkeleton from '@/components/shop/ProductGridSkeleton.vue'
import UiButton from '@/components/ui/UiButton.vue'
import UiEmptyState from '@/components/ui/UiEmptyState.vue'
import { useAuthStore } from '@/stores/auth'
import { useCatalogStore } from '@/stores/catalog'
import { useWishlistStore } from '@/stores/wishlist'
import { storeToRefs } from 'pinia'

const auth = useAuthStore()
const catalogStore = useCatalogStore()
const wishlist = useWishlistStore()
const { isAuthenticated } = storeToRefs(auth)
const { catalog, loading: catalogLoading, ready: catalogReady } = storeToRefs(catalogStore)
const { productIds, ready } = storeToRefs(wishlist)

void catalogStore.ensureLoaded()
void wishlist.ensureLoaded()

const items = computed(() => {
  const ids = new Set(productIds.value)
  return catalog.value.filter((p) => ids.has(p.id))
})

const loading = computed(() => !ready.value || (catalogLoading.value && !catalogReady.value))
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

      <ProductGridSkeleton v-if="loading" :count="4" />

      <UiEmptyState
        v-else-if="items.length === 0"
        title="Your wishlist is empty"
        description="Save pieces you love and come back when you're ready to order."
      >
        <template #icon>
          <Heart :size="22" :stroke-width="1.75" />
        </template>
        <template #action>
          <RouterLink to="/shop">
            <UiButton>
              <ShoppingBag :size="17" />
              Browse shop
            </UiButton>
          </RouterLink>
        </template>
      </UiEmptyState>

      <div v-else class="wishlist__grid tm-product-grid">
        <ProductCard v-for="p in items" :key="p.id" :product="p" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.wishlist {
  padding-top: 1.5rem;
  padding-bottom: 3rem;
}

.wishlist__head {
  margin-bottom: 1.75rem;
}

.wishlist__eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  margin: 0 0 0.35rem;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--tm-accent);
}

.wishlist__title {
  margin: 0 0 0.5rem;
  font-family: var(--tm-font-display);
  font-size: clamp(1.75rem, 4vw, 2.25rem);
  font-weight: 500;
  color: var(--tm-ink);
}

.wishlist__lead {
  margin: 0;
  max-width: 32rem;
  color: var(--tm-ink-muted);
  line-height: 1.55;
}

.wishlist__grid {
  /* Layout from .tm-product-grid */
}
</style>

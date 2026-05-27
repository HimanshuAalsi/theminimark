<script setup lang="ts">
import { Heart, Plus } from 'lucide-vue-next'
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { categoryLabel } from '@/data/siteContent'
import type { SiteProduct } from '@/data/siteContent'
import type { Product } from '@/types/product'
import { formatCurrency, STORE_CURRENCY } from '@/lib/currency'
import { useCartStore } from '@/stores/cart'
import { useCartUiStore } from '@/stores/cartUi'
import { useWishlistStore } from '@/stores/wishlist'

const props = defineProps<{
  product: SiteProduct
}>()

const cart = useCartStore()
const cartUi = useCartUiStore()
const wishlist = useWishlistStore()

const saved = computed(() => wishlist.has(props.product.id))

function toCartProduct(p: SiteProduct): Product {
  return {
    id: p.id,
    slug: p.slug,
    name: p.name,
    price: p.price,
    currency: STORE_CURRENCY,
    imageUrl: p.image,
  }
}

function addToCart() {
  cart.addProduct(toCartProduct(props.product))
  cartUi.open()
}

async function toggleWishlist() {
  await wishlist.toggle(props.product.id)
}

const fmt = formatCurrency
</script>

<template>
  <article class="pcard tm-hover-lift">
    <div class="pcard__media">
      <span class="pcard__sale">Sale</span>
      <button
        type="button"
        class="pcard__wish"
        :class="{ 'pcard__wish--on': saved }"
        :aria-label="saved ? 'Remove from wishlist' : 'Add to wishlist'"
        :aria-pressed="saved"
        @click.stop="toggleWishlist"
      >
        <Heart :size="17" :stroke-width="2.25" :fill="saved ? 'currentColor' : 'none'" />
      </button>
      <RouterLink :to="{ path: '/shop', query: { category: product.category } }" class="pcard__img-link">
        <img
          :src="product.image"
          :alt="product.name"
          width="400"
          height="400"
          loading="lazy"
          decoding="async"
        />
      </RouterLink>
    </div>

    <div class="pcard__body">
      <p class="pcard__cat">{{ categoryLabel(product.category) }}</p>
      <RouterLink :to="{ path: '/shop', query: { q: product.name } }" class="pcard__title-link">
        <h3 class="pcard__title">{{ product.name }}</h3>
      </RouterLink>
      <div class="pcard__price-row">
        <span class="pcard__price-old">{{ fmt(product.compareAt) }}</span>
        <span class="pcard__price">{{ fmt(product.price) }}</span>
      </div>
      <button type="button" class="pcard__add tm-press" @click="addToCart">
        <Plus :size="18" :stroke-width="2.5" aria-hidden="true" />
        Add to cart
      </button>
    </div>
  </article>
</template>

<style scoped>
.pcard {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--color-surface-elevated);
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-card);
  overflow: hidden;
  transition:
    box-shadow 0.28s var(--ease-out, ease),
    border-color 0.28s ease;
}

.pcard:hover {
  border-color: rgba(45, 92, 82, 0.22);
  box-shadow: var(--shadow-float);
}

.pcard__media {
  position: relative;
  aspect-ratio: 1;
  background: linear-gradient(145deg, #ebe6df, #f5f1ea);
  overflow: hidden;
}

.pcard__wish {
  position: absolute;
  top: 10px;
  right: 10px;
  z-index: 3;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.15rem;
  height: 2.15rem;
  border: none;
  border-radius: 999px;
  background: rgba(255, 252, 248, 0.92);
  color: var(--color-ink-muted);
  cursor: pointer;
  box-shadow: 0 2px 10px rgba(20, 19, 18, 0.12);
  transition:
    color 0.2s ease,
    background 0.2s ease,
    transform 0.15s ease;
}

.pcard__wish:hover {
  transform: scale(1.06);
  color: var(--color-highlight);
}

.pcard__wish--on {
  color: var(--color-highlight);
  background: rgba(255, 240, 236, 0.95);
}

.pcard__sale {
  position: absolute;
  top: 10px;
  left: 10px;
  z-index: 2;
  padding: 0.28rem 0.55rem;
  border-radius: 8px;
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.07em;
  text-transform: uppercase;
  background: linear-gradient(135deg, var(--color-sale), #8b2f2f);
  color: #fff;
  box-shadow: 0 2px 10px rgba(184, 69, 61, 0.35);
}

.pcard__img-link {
  display: block;
  height: 100%;
}

.pcard__img-link img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  vertical-align: middle;
  transition: transform 0.45s var(--ease-out, ease);
}

.pcard:hover .pcard__img-link img {
  transform: scale(1.06);
}

@media (prefers-reduced-motion: reduce) {
  .pcard:hover .pcard__img-link img {
    transform: none;
  }
}

.pcard__body {
  padding: 1rem 1rem 1.1rem;
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  flex: 1;
}

.pcard__cat {
  margin: 0;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.09em;
  text-transform: uppercase;
  color: var(--color-accent);
}

.pcard__title-link {
  color: inherit;
}

.pcard__title-link:hover .pcard__title {
  color: var(--color-accent);
}

.pcard__title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  line-height: 1.35;
  color: var(--color-ink);
  transition: color 0.2s ease;
}

.pcard__price-row {
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
  margin-top: 0.15rem;
}

.pcard__price-old {
  font-size: 0.88rem;
  color: var(--color-ink-faint);
  text-decoration: line-through;
}

.pcard__price {
  font-size: 1.06rem;
  font-weight: 800;
  color: var(--color-ink);
}

.pcard__add {
  margin-top: auto;
  padding-top: 0.65rem;
  width: 100%;
  min-height: var(--tap-min);
  border: none;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--color-accent), #1f4d44);
  color: #fff;
  font: inherit;
  font-size: 0.9rem;
  font-weight: 700;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  box-shadow: 0 4px 14px rgba(45, 92, 82, 0.25);
  transition:
    background 0.22s ease,
    box-shadow 0.22s ease,
    transform 0.15s ease;
}

.pcard__add:hover {
  background: linear-gradient(135deg, var(--color-accent-hover), #183c34);
  box-shadow: 0 6px 20px rgba(45, 92, 82, 0.32);
  transform: translateY(-1px);
}
</style>

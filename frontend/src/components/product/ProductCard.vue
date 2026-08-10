<script setup lang="ts">
import {
  ArrowUpRight,
  Bookmark,
  Calendar,
  CreditCard,
  Gift,
  Heart,
  Images,
  Magnet,
  Plus,
  ShoppingBag,
  Sparkles,
} from 'lucide-vue-next'
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useMediaQuery } from '@/composables/useMediaQuery'
import { categoryLabel } from '@/data/siteContent'
import type { ShopCategory, SiteProduct } from '@/data/siteContent'
import type { Product } from '@/types/product'
import ProductImage from '@/components/product/ProductImage.vue'
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
const isMobileCard = useMediaQuery('(max-width: 639px)')

const hoverIndex = ref(0)

const categoryIcons: Record<ShopCategory, typeof Bookmark> = {
  bookmarks: Bookmark,
  cards: CreditCard,
  calendars: Calendar,
  magnets: Magnet,
  hampers: Gift,
  'just-mini-knots': Sparkles,
}

const CategoryIcon = computed(() => categoryIcons[props.product.category] ?? Bookmark)

const photos = computed(() => {
  if (props.product.images && props.product.images.length > 0) {
    return props.product.images
  }
  return props.product.image ? [props.product.image] : []
})

const displayImage = computed(() => photos.value[hoverIndex.value] ?? photos.value[0] ?? '')

const photoCount = computed(() => photos.value.length)

const saved = computed(() => wishlist.has(props.product.id))

const onSale = computed(() => props.product.compareAt > props.product.price)

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

function onMediaEnter() {
  if (!isMobileCard.value && photos.value.length > 1) hoverIndex.value = 1
}

function onMediaLeave() {
  hoverIndex.value = 0
}

const fmt = formatCurrency
</script>

<template>
  <article class="pcard" :class="{ 'pcard--mobile': isMobileCard }">
    <div
      class="pcard__media"
      @mouseenter="onMediaEnter"
      @mouseleave="onMediaLeave"
    >
      <span v-if="onSale" class="pcard__sale">Sale</span>

      <span v-if="photoCount > 1" class="pcard__photos" :aria-label="`${photoCount} photos`">
        <Images :size="13" aria-hidden="true" />
        <span v-if="!isMobileCard" class="pcard__photos-num">{{ photoCount }}</span>
      </span>

      <button
        type="button"
        class="pcard__wish"
        :class="{ 'pcard__wish--on': saved }"
        :aria-label="saved ? 'Remove from wishlist' : 'Add to wishlist'"
        :aria-pressed="saved"
        @click.stop="toggleWishlist"
      >
        <Heart :size="18" :stroke-width="2" :fill="saved ? 'currentColor' : 'none'" />
      </button>

      <button
        v-if="isMobileCard"
        type="button"
        class="pcard__quick-add"
        aria-label="Add to cart"
        @click.stop="addToCart"
      >
        <ShoppingBag :size="18" :stroke-width="2.25" aria-hidden="true" />
      </button>

      <RouterLink :to="{ name: 'product', params: { slug: product.slug } }" class="pcard__img-link">
        <ProductImage
          :src="displayImage"
          :alt="product.name"
          :fallback-key="`${product.id}-${hoverIndex}`"
          width="400"
          height="400"
        />
      </RouterLink>
    </div>

    <div class="pcard__body">
      <p class="pcard__cat">
        <component :is="CategoryIcon" :size="12" :stroke-width="2.25" aria-hidden="true" />
        <span>{{ categoryLabel(product.category) }}</span>
      </p>
      <RouterLink :to="{ name: 'product', params: { slug: product.slug } }" class="pcard__title-link">
        <h3 class="pcard__title">{{ product.name }}</h3>
      </RouterLink>
      <div class="pcard__price-row">
        <span v-if="onSale" class="pcard__price-old">{{ fmt(product.compareAt) }}</span>
        <span class="pcard__price">{{ fmt(product.price) }}</span>
      </div>

      <!-- Desktop / tablet: full CTA -->
      <button v-if="!isMobileCard" type="button" class="pcard__add" @click="addToCart">
        <Plus :size="18" :stroke-width="2.5" aria-hidden="true" />
        <span class="pcard__add-label">Add to cart</span>
      </button>

      <!-- Mobile: compact icon action row (thumb zone) -->
      <div v-else class="pcard__actions" role="group" aria-label="Product actions">
        <button type="button" class="pcard__action pcard__action--primary" @click="addToCart">
          <ShoppingBag :size="17" :stroke-width="2.25" aria-hidden="true" />
          <span>Add</span>
        </button>
        <RouterLink
          :to="{ name: 'product', params: { slug: product.slug } }"
          class="pcard__action pcard__action--ghost"
          aria-label="View product details"
        >
          <ArrowUpRight :size="17" :stroke-width="2.25" aria-hidden="true" />
        </RouterLink>
      </div>
    </div>
  </article>
</template>
<style scoped>
.pcard {
  display: flex;
  flex-direction: column;
  height: 100%;
  background: var(--tm-surface-2);
  border-radius: var(--tm-radius-md);
  border: 1px solid var(--tm-border);
  box-shadow: var(--tm-shadow-sm);
  overflow: hidden;
  transition:
    box-shadow var(--tm-duration) var(--tm-ease),
    border-color var(--tm-duration) var(--tm-ease);
}

.pcard--mobile:active {
  transform: scale(0.985);
}

@media (min-width: 640px) {
  .pcard:hover {
    border-color: var(--tm-accent-soft);
    box-shadow: var(--tm-shadow-md);
  }
}

.pcard__media {
  position: relative;
  aspect-ratio: 1;
  background: var(--tm-page-2);
  overflow: hidden;
}

/* Wishlist — top-right, 44px touch target (mobile UX standard) */
.pcard__wish {
  position: absolute;
  top: 8px;
  right: 8px;
  z-index: 3;
  display: grid;
  place-items: center;
  width: 2.25rem;
  height: 2.25rem;
  min-width: var(--tm-tap);
  min-height: var(--tm-tap);
  border: none;
  border-radius: var(--tm-radius-full);
  background: var(--tm-surface-2);
  color: var(--tm-ink-muted);
  cursor: pointer;
  box-shadow: var(--tm-shadow-sm);
  transition:
    color var(--tm-duration) var(--tm-ease),
    background var(--tm-duration) var(--tm-ease);
}

.pcard--mobile .pcard__wish {
  width: 2.5rem;
  height: 2.5rem;
}

.pcard__wish--on {
  color: var(--tm-highlight);
  background: color-mix(in srgb, var(--tm-highlight) 12%, var(--tm-surface-2));
}

/* Quick-add on image — common in ASOS / Zara-style apps */
.pcard__quick-add {
  position: absolute;
  right: 8px;
  bottom: 8px;
  z-index: 3;
  display: grid;
  place-items: center;
  width: 2.5rem;
  height: 2.5rem;
  min-width: var(--tm-tap);
  min-height: var(--tm-tap);
  border: none;
  border-radius: var(--tm-radius-full);
  background: var(--tm-accent);
  color: #fff;
  cursor: pointer;
  box-shadow: var(--tm-shadow-md);
  transition: background var(--tm-duration) var(--tm-ease);
}

.pcard__quick-add:active {
  background: var(--tm-accent-hover);
}

.pcard__sale {
  position: absolute;
  top: 8px;
  left: 8px;
  z-index: 2;
  padding: 0.2rem 0.45rem;
  border-radius: var(--tm-radius-xs);
  font-size: 0.6rem;
  font-weight: 800;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  background: var(--tm-sale);
  color: #fff;
}

.pcard__photos {
  position: absolute;
  bottom: 8px;
  left: 8px;
  z-index: 2;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.2rem;
  min-width: 1.65rem;
  min-height: 1.65rem;
  padding: 0 0.35rem;
  border-radius: var(--tm-radius-full);
  background: var(--tm-surface-2);
  color: var(--tm-ink-muted);
  box-shadow: var(--tm-shadow-sm);
}

.pcard--mobile .pcard__photos {
  bottom: auto;
  top: 8px;
  left: auto;
  right: 3.35rem;
}

.pcard__photos-num {
  font-size: 0.62rem;
  font-weight: 700;
}

.pcard__img-link {
  display: block;
  height: 100%;
}

.pcard__img-link :deep(img) {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transition: transform 0.45s var(--tm-ease);
}

@media (min-width: 640px) {
  .pcard:hover .pcard__img-link :deep(img) {
    transform: scale(1.04);
  }
}

.pcard__body {
  padding: 0.65rem 0.7rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
  flex: 1;
}

@media (min-width: 640px) {
  .pcard__body {
    padding: 1rem 1rem 1.1rem;
    gap: 0.35rem;
  }
}

.pcard__cat {
  margin: 0;
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.62rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--tm-accent);
}

.pcard--mobile .pcard__cat {
  font-size: 0.58rem;
}

.pcard__title-link {
  color: inherit;
  text-decoration: none;
}

.pcard__title {
  margin: 0;
  font-size: 0.82rem;
  font-weight: 650;
  line-height: 1.3;
  color: var(--tm-ink);
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

@media (min-width: 640px) {
  .pcard__title {
    font-size: 1rem;
    font-weight: 700;
    -webkit-line-clamp: 3;
  }
}

.pcard__price-row {
  display: flex;
  align-items: baseline;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin-top: 0.1rem;
}

.pcard__price-old {
  font-size: 0.75rem;
  color: var(--tm-ink-faint);
  text-decoration: line-through;
}

.pcard__price {
  font-size: 0.95rem;
  font-weight: 800;
  color: var(--tm-ink);
  font-variant-numeric: tabular-nums;
}

@media (min-width: 640px) {
  .pcard__price {
    font-size: 1.06rem;
  }
}

/* Mobile: icon action row */
.pcard__actions {
  display: flex;
  gap: 0.4rem;
  margin-top: 0.45rem;
}

.pcard__action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.3rem;
  min-height: 2.25rem;
  padding: 0 0.65rem;
  border-radius: var(--tm-radius-full);
  font: inherit;
  font-size: 0.75rem;
  font-weight: 700;
  cursor: pointer;
  text-decoration: none;
  transition:
    background var(--tm-duration) var(--tm-ease),
    color var(--tm-duration) var(--tm-ease);
}

.pcard__action--primary {
  flex: 1;
  border: none;
  justify-content: center;
}

.pcard__action--ghost {
  flex-shrink: 0;
  width: 2.25rem;
  min-width: var(--tm-tap);
  padding: 0;
  border: 1px solid var(--tm-border);
  background: var(--tm-surface);
  color: var(--tm-ink-muted);
}

.pcard__action--ghost:active {
  border-color: var(--tm-accent);
  color: var(--tm-accent);
}

/* Desktop: full-width CTA */
.pcard__add {
  margin-top: auto;
  padding: 0.55rem 1rem;
  width: 100%;
  min-height: var(--tm-tap);
  border: none;
  border-radius: var(--tm-radius-full);
  background: var(--tm-gradient);
  color: var(--tm-on-accent);
  font: inherit;
  font-size: 0.9rem;
  font-weight: 650;
  line-height: 1.25;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  text-align: center;
  box-shadow: var(--tm-shadow-accent);
  transition: background var(--tm-duration) var(--tm-ease);
}

.pcard__add-label {
  flex: 0 1 auto;
}

.pcard__add:hover {
  background: var(--tm-gradient-hover);
}

.pcard__action--primary {
  background: var(--tm-gradient);
  color: var(--tm-on-accent);
}

.pcard__action--primary:active {
  background: var(--tm-gradient-hover);
}
</style>
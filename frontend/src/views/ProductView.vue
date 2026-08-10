<script setup lang="ts">
import {
  ArrowLeft,
  Check,
  Heart,
  Minus,
  Plus,
  ShoppingBag,
  Sparkles,
  Truck,
} from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import ProductCard from '@/components/product/ProductCard.vue'
import ProductGallery from '@/components/product/ProductGallery.vue'
import MobileStickyBuyBar from '@/components/layout/MobileStickyBuyBar.vue'
import ShopPersonalisePromo from '@/components/shop/ShopPersonalisePromo.vue'
import { categoryLabel } from '@/data/siteContent'
import { personalisePromoForCategory } from '@/data/personalise'
import { formatCurrency, STORE_CURRENCY } from '@/lib/currency'
import { fetchProductBySlug } from '@/lib/products'
import { useCartStore } from '@/stores/cart'
import { useCartUiStore } from '@/stores/cartUi'
import { useCatalogStore } from '@/stores/catalog'
import { useWishlistStore } from '@/stores/wishlist'
import type { Product } from '@/types/product'
import {
  CATEGORY_DESCRIPTION_FALLBACK,
  CATEGORY_FEATURES,
  type ProductDetail,
} from '@/types/productDetail'
import { storeToRefs } from 'pinia'

const route = useRoute()
const catalogStore = useCatalogStore()
const cart = useCartStore()
const cartUi = useCartUiStore()
const wishlist = useWishlistStore()
const { catalog } = storeToRefs(catalogStore)

const product = ref<ProductDetail | null>(null)
const loading = ref(true)
const error = ref('')
const qty = ref(1)
const descOpen = ref(true)
const featuresOpen = ref(true)

const fmt = formatCurrency

const slug = computed(() => String(route.params.slug ?? ''))

const onSale = computed(() => {
  if (!product.value) return false
  return product.value.compareAt > product.value.price
})

const discountPct = computed(() => {
  if (!product.value || !onSale.value) return 0
  const { compareAt, price } = product.value
  return Math.round(((compareAt - price) / compareAt) * 100)
})

const saved = computed(() => (product.value ? wishlist.has(product.value.id) : false))

const personalisePromo = computed(() =>
  product.value ? personalisePromoForCategory(product.value.category) : null,
)

const related = computed(() => {
  if (!product.value) return []
  return catalog.value
    .filter((p) => p.category === product.value!.category && p.id !== product.value!.id)
    .slice(0, 4)
})

const descriptionHtml = computed(() => {
  const d = product.value?.description
  if (!d) {
    return product.value
      ? CATEGORY_DESCRIPTION_FALLBACK[product.value.category]
      : ''
  }
  if (/<[a-z][\s\S]*>/i.test(d)) return d
  return d
    .split(/\n{2,}/)
    .map((p) => p.trim())
    .filter(Boolean)
    .map((p) => `<p>${escapeHtml(p).replace(/\n/g, '<br>')}</p>`)
    .join('')
})

const features = computed(() => {
  if (!product.value) return []
  if (product.value.features.length > 0) return product.value.features
  return CATEGORY_FEATURES[product.value.category]
})

function escapeHtml(s: string): string {
  return s
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
}

async function loadProduct() {
  loading.value = true
  error.value = ''
  product.value = null
  qty.value = 1

  await catalogStore.ensureLoaded()

  const s = slug.value
  if (!s) {
    error.value = 'Product not found'
    loading.value = false
    return
  }

  const fallback = catalog.value.find((p) => p.slug === s) ?? null

  try {
    product.value = await fetchProductBySlug(s, fallback)
    if (!product.value) error.value = 'Product not found'
    else document.title = `${product.value.name} | The Minimark`
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Could not load product'
  } finally {
    loading.value = false
  }
}

function toCartProduct(p: ProductDetail): Product {
  return {
    id: p.id,
    slug: p.slug,
    name: p.name,
    price: p.price,
    currency: STORE_CURRENCY,
    imageUrl: p.image,
    category: p.category,
  }
}

function addToCart() {
  if (!product.value) return
  cart.addProduct(toCartProduct(product.value), qty.value)
  cartUi.open()
}

async function toggleWishlist() {
  if (!product.value) return
  await wishlist.toggle(product.value.id)
}

function changeQty(delta: number) {
  qty.value = Math.max(1, Math.min(99, qty.value + delta))
}

watch(slug, () => {
  void loadProduct()
})

void loadProduct()
</script>

<template>
  <div class="pdp tm-section">
    <div class="tm-container">
      <nav class="pdp__crumb" aria-label="Breadcrumb">
        <RouterLink to="/shop" class="pdp__crumb-link">
          <ArrowLeft :size="16" :stroke-width="2.25" aria-hidden="true" />
          Shop
        </RouterLink>
        <span v-if="product" class="pdp__crumb-sep" aria-hidden="true">/</span>
        <span v-if="product" class="pdp__crumb-current">{{ categoryLabel(product.category) }}</span>
      </nav>

      <div v-if="loading" class="pdp__state" role="status">Loading product…</div>

      <div v-else-if="error || !product" class="pdp__state pdp__state--err">
        <p>{{ error || 'Product not found' }}</p>
        <RouterLink to="/shop" class="pdp__back-btn">Back to shop</RouterLink>
      </div>

      <template v-else>
        <div class="pdp__layout">
          <aside class="pdp__gallery-col" aria-label="Product images">
            <ProductGallery
              :images="product.images"
              :alt="product.name"
              :product-id="product.id"
            />
          </aside>

          <div class="pdp__content-col">
            <div class="pdp__buy-card">
              <p class="pdp__eyebrow">{{ categoryLabel(product.category) }}</p>
              <h1 class="pdp__title">{{ product.name }}</h1>

              <div class="pdp__price-block">
                <div class="pdp__prices">
                  <span class="pdp__price">{{ fmt(product.price) }}</span>
                  <span v-if="onSale" class="pdp__compare">{{ fmt(product.compareAt) }}</span>
                </div>
                <span v-if="onSale" class="pdp__badge">Save {{ discountPct }}%</span>
              </div>

              <p class="pdp__tax-note">Taxes included · Shipping calculated at checkout</p>

              <ul class="pdp__perks" aria-label="Order perks">
                <li>
                  <Truck :size="15" aria-hidden="true" />
                  Free delivery on orders over ₹499
                </li>
                <li>
                  <Check :size="15" aria-hidden="true" />
                  Returns within 24 hrs with unboxing video
                </li>
              </ul>

              <div class="pdp__qty-row">
                <span class="pdp__qty-label">Quantity</span>
                <div class="pdp__qty">
                  <button type="button" class="pdp__qty-btn" aria-label="Decrease quantity" @click="changeQty(-1)">
                    <Minus :size="16" />
                  </button>
                  <span class="pdp__qty-val" aria-live="polite">{{ qty }}</span>
                  <button type="button" class="pdp__qty-btn" aria-label="Increase quantity" @click="changeQty(1)">
                    <Plus :size="16" />
                  </button>
                </div>
              </div>

              <div class="pdp__actions">
                <button type="button" class="pdp__cart-btn tm-press" @click="addToCart">
                  <ShoppingBag :size="18" :stroke-width="2.25" aria-hidden="true" />
                  Add to cart
                </button>
                <button
                  type="button"
                  class="pdp__wish-btn"
                  :class="{ 'pdp__wish-btn--on': saved }"
                  :aria-label="saved ? 'Remove from wishlist' : 'Add to wishlist'"
                  :aria-pressed="saved"
                  @click="toggleWishlist"
                >
                  <Heart :size="18" :stroke-width="2.25" :fill="saved ? 'currentColor' : 'none'" />
                </button>
              </div>

              <RouterLink
                v-if="personalisePromo"
                :to="{ path: '/personalise' }"
                class="pdp__personalise"
              >
                <Sparkles :size="16" aria-hidden="true" />
                <span>
                  <strong>Personalise with your photo</strong>
                  <small>Upload & preview in our studio</small>
                </span>
              </RouterLink>
            </div>

            <div class="pdp__details">
              <section class="pdp__panel">
                <button type="button" class="pdp__panel-head" @click="descOpen = !descOpen">
                  <h2>Description</h2>
                  <span class="pdp__panel-toggle">{{ descOpen ? '−' : '+' }}</span>
                </button>
                <div v-show="descOpen" class="pdp__panel-body pdp__prose" v-html="descriptionHtml" />
              </section>

              <section class="pdp__panel">
                <button type="button" class="pdp__panel-head" @click="featuresOpen = !featuresOpen">
                  <h2>Features</h2>
                  <span class="pdp__panel-toggle">{{ featuresOpen ? '−' : '+' }}</span>
                </button>
                <ul v-show="featuresOpen" class="pdp__features">
                  <li v-for="(f, i) in features" :key="i">
                    <Check :size="16" aria-hidden="true" />
                    {{ f }}
                  </li>
                </ul>
              </section>

              <ShopPersonalisePromo v-if="personalisePromo" :promo="personalisePromo" />
            </div>
          </div>
        </div>

        <section v-if="related.length" class="pdp__related">
          <header class="pdp__related-head">
            <h2>You may also like</h2>
            <RouterLink :to="{ path: '/shop', query: { category: product.category } }">
              View all {{ categoryLabel(product.category).toLowerCase() }}
            </RouterLink>
          </header>
          <div class="pdp__related-grid tm-product-grid">
            <ProductCard v-for="p in related" :key="p.id" :product="p" />
          </div>
        </section>
      </template>
    </div>

    <MobileStickyBuyBar
      v-if="product && !loading"
      :price="product.price"
      :compare-at="onSale ? product.compareAt : undefined"
      @action="addToCart"
    />
  </div>
</template>

<style scoped>
.pdp {
  padding-top: 1.25rem;
  padding-bottom: 5rem;
}

@media (max-width: 900px) {
  .layout--app .pdp {
    padding-bottom: calc(5.5rem + var(--app-tab-h, 4rem));
  }
  .pdp {
    padding-bottom: 5.5rem;
  }
}

.pdp__crumb {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  margin-bottom: 1.5rem;
  font-size: 0.85rem;
}

.pdp__crumb-link {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  color: var(--color-accent);
  font-weight: 650;
  text-decoration: none;
}

.pdp__crumb-link:hover {
  text-decoration: underline;
}

.pdp__crumb-sep {
  color: var(--color-ink-faint);
}

.pdp__crumb-current {
  color: var(--color-ink-muted);
}

.pdp__state {
  padding: 3rem 1rem;
  text-align: center;
  color: var(--color-ink-muted);
}

.pdp__state--err {
  color: var(--color-sale);
}

.pdp__back-btn {
  display: inline-flex;
  margin-top: 1rem;
  padding: 0.55rem 1.1rem;
  border-radius: 999px;
  border: 2px solid var(--color-border-strong);
  color: var(--color-accent);
  font-weight: 650;
  text-decoration: none;
}

.pdp__layout {
  display: grid;
  grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
  gap: clamp(1.5rem, 4vw, 2.75rem);
  align-items: start;
  margin-bottom: 2.5rem;
}

@media (max-width: 900px) {
  .pdp__layout {
    grid-template-columns: 1fr;
  }
}

.pdp__gallery-col {
  position: sticky;
  top: calc(var(--header-h, 4rem) + 1rem);
  align-self: start;
}

@media (max-width: 900px) {
  .pdp__gallery-col {
    position: static;
  }
}

.pdp__content-col {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  min-width: 0;
}

.pdp__buy-card {
  padding: clamp(1.25rem, 3vw, 1.75rem);
  border-radius: var(--radius-xl);
  background: var(--color-surface-elevated);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-float);
}

.pdp__eyebrow {
  margin: 0 0 0.4rem;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: var(--color-accent);
}

.pdp__title {
  margin: 0 0 1rem;
  font-family: var(--font-display);
  font-size: clamp(1.65rem, 3.5vw, 2.25rem);
  font-weight: 500;
  line-height: 1.15;
  letter-spacing: -0.02em;
}

.pdp__price-block {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.65rem 0.85rem;
  margin-bottom: 0.5rem;
}

.pdp__prices {
  display: flex;
  align-items: baseline;
  gap: 0.55rem;
}

.pdp__price {
  font-size: 1.65rem;
  font-weight: 800;
  color: var(--color-ink);
}

.pdp__compare {
  font-size: 1rem;
  color: var(--color-ink-faint);
  text-decoration: line-through;
}

.pdp__badge {
  padding: 0.28rem 0.6rem;
  border-radius: 999px;
  background: linear-gradient(135deg, var(--color-sale), #8b2f2f);
  color: #fff;
  font-size: 0.72rem;
  font-weight: 800;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.pdp__tax-note {
  margin: 0 0 1rem;
  font-size: 0.82rem;
  color: var(--color-ink-muted);
}

.pdp__perks {
  margin: 0 0 1.25rem;
  padding: 0;
  list-style: none;
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.pdp__perks li {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.85rem;
  color: var(--color-ink-muted);
}

.pdp__perks svg {
  flex-shrink: 0;
  color: var(--color-accent);
}

.pdp__qty-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
}

.pdp__qty-label {
  font-size: 0.88rem;
  font-weight: 650;
  color: var(--color-ink);
}

.pdp__qty {
  display: inline-flex;
  align-items: center;
  border: 1px solid var(--color-border);
  border-radius: 999px;
  overflow: hidden;
  background: var(--color-page);
}

.pdp__qty-btn {
  display: grid;
  place-items: center;
  width: 2.5rem;
  height: 2.5rem;
  border: none;
  background: transparent;
  color: var(--color-ink);
  cursor: pointer;
}

.pdp__qty-btn:hover {
  background: var(--color-accent-soft);
  color: var(--color-accent);
}

.pdp__qty-val {
  min-width: 2rem;
  text-align: center;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.pdp__actions {
  display: flex;
  gap: 0.6rem;
}

.pdp__cart-btn {
  flex: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  min-height: var(--tap-min);
  padding: 0 1.25rem;
  border: none;
  border-radius: 999px;
  background: var(--tm-gradient);
  color: #fff;
  font: inherit;
  font-size: 0.95rem;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 18px rgba(45, 92, 82, 0.28);
}

.pdp__cart-btn:hover {
  filter: brightness(1.05);
}

.pdp__wish-btn {
  display: grid;
  place-items: center;
  width: var(--tap-min);
  height: var(--tap-min);
  flex-shrink: 0;
  border: 1px solid var(--color-border);
  border-radius: 999px;
  background: var(--color-page);
  color: var(--color-ink-muted);
  cursor: pointer;
  transition:
    color 0.2s ease,
    border-color 0.2s ease;
}

.pdp__wish-btn:hover,
.pdp__wish-btn--on {
  color: var(--color-highlight);
  border-color: rgba(184, 69, 61, 0.35);
}

.pdp__personalise {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  margin-top: 1.15rem;
  padding: 0.85rem 1rem;
  border-radius: var(--radius-md);
  background: var(--color-accent-soft);
  border: 1px solid rgba(45, 92, 82, 0.18);
  color: inherit;
  text-decoration: none;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.pdp__personalise:hover {
  border-color: var(--color-accent);
  box-shadow: var(--shadow-sm);
}

.pdp__personalise svg {
  flex-shrink: 0;
  color: var(--color-accent);
}

.pdp__personalise span {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}

.pdp__personalise strong {
  font-size: 0.88rem;
}

.pdp__personalise small {
  font-size: 0.78rem;
  color: var(--color-ink-muted);
}

.pdp__details {
  display: grid;
  gap: 1rem;
}

.pdp__panel {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  background: var(--color-surface-elevated);
  overflow: hidden;
}

.pdp__panel-head {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.15rem;
  border: none;
  background: transparent;
  cursor: pointer;
  text-align: left;
  font: inherit;
}

.pdp__panel-head h2 {
  margin: 0;
  font-family: var(--font-display);
  font-size: 1.1rem;
  font-weight: 500;
}

.pdp__panel-toggle {
  font-size: 1.25rem;
  font-weight: 300;
  color: var(--color-ink-muted);
  line-height: 1;
}

.pdp__panel-body {
  padding: 0 1.15rem 1.15rem;
}

.pdp__prose :deep(p) {
  margin: 0 0 0.85rem;
  line-height: 1.65;
  color: var(--color-ink-muted);
}

.pdp__prose :deep(p:last-child) {
  margin-bottom: 0;
}

.pdp__features {
  margin: 0;
  padding: 0 1.15rem 1.15rem;
  list-style: none;
  display: grid;
  gap: 0.55rem;
}

.pdp__features li {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  font-size: 0.9rem;
  color: var(--color-ink-muted);
  line-height: 1.45;
}

.pdp__features svg {
  flex-shrink: 0;
  margin-top: 0.15rem;
  color: var(--color-accent);
}

.pdp__related-head {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.25rem;
}

.pdp__related-head h2 {
  margin: 0;
  font-family: var(--font-display);
  font-size: 1.35rem;
  font-weight: 500;
}

.pdp__related-head a {
  font-size: 0.88rem;
  font-weight: 650;
  color: var(--color-accent);
  text-decoration: none;
}

.pdp__related-head a:hover {
  text-decoration: underline;
}
</style>

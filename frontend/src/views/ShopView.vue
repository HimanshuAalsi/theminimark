<script setup lang="ts">
import { Search, SlidersHorizontal } from 'lucide-vue-next'
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ProductCard from '@/components/product/ProductCard.vue'
import ProductGridSkeleton from '@/components/shop/ProductGridSkeleton.vue'
import ShopFilterSheet from '@/components/shop/ShopFilterSheet.vue'
import ShopPersonalisePromo from '@/components/shop/ShopPersonalisePromo.vue'
import UiEmptyState from '@/components/ui/UiEmptyState.vue'
import { useIsMobileApp } from '@/composables/useMediaQuery'
import { personalisePromoForCategory } from '@/data/personalise'
import { SHOP_CATEGORIES, matchesBookmarkType, type BookmarkType, type SiteProduct } from '@/data/siteContent'
import { useCatalogStore } from '@/stores/catalog'
import { useCategoriesStore } from '@/stores/categories'
import { storeToRefs } from 'pinia'

const route = useRoute()
const router = useRouter()
const catalogStore = useCatalogStore()
const categoriesStore = useCategoriesStore()
const { catalog, loading: catalogLoading, ready: catalogReady } = storeToRefs(catalogStore)
const isMobileApp = useIsMobileApp()
const filtersOpen = ref(false)

const q = ref(String(route.query.q ?? ''))
const category = ref(resolveCategoryFromRoute())
const subcategory = ref(String(route.query.subcategory ?? route.query.type ?? ''))

function resolveCategoryFromRoute(): string {
  const param = typeof route.params.category === 'string' ? route.params.category : ''
  if (param && SHOP_CATEGORIES.some((c) => c.id === param)) return param
  const queryCat = String(route.query.category ?? 'all')
  return queryCat || 'all'
}

onMounted(() => {
  void categoriesStore.ensureLoaded()
  void catalogStore.ensureLoaded()
})

const sort = ref<'featured' | 'price-asc' | 'price-desc' | 'name'>('featured')

const HAMPER_PRICE_TIERS = [
  { id: '', label: 'All' },
  { id: '99', label: 'Buy at ₹99' },
  { id: '199', label: 'Buy at ₹199' },
  { id: '299', label: 'Buy at ₹299' },
] as const

const priceTier = ref(String(route.query.price ?? ''))

watch(
  () => [route.params.category, route.query] as const,
  () => {
    q.value = String(route.query.q ?? '')
    category.value = resolveCategoryFromRoute()
    subcategory.value = String(route.query.subcategory ?? route.query.type ?? '')
    priceTier.value = String(route.query.price ?? '')
  },
  { deep: true },
)

const activeSubcategories = computed(() => {
  if (!category.value || category.value === 'all') return []
  return categoriesStore.subcategoriesFor(category.value)
})

const pageTitle = computed(() => {
  if (subcategory.value && category.value !== 'all') {
    return categoriesStore.subcategoryLabel(category.value, subcategory.value)
  }
  if (category.value && category.value !== 'all') {
    const cat = SHOP_CATEGORIES.find((c) => c.id === category.value)
    return cat?.label ?? 'Shop'
  }
  return 'Shop'
})

function productMatchesSubcategory(p: SiteProduct, sub: string): boolean {
  if (p.subcategory && p.subcategory === sub) return true
  if (p.category === 'bookmarks' && (sub === 'magnetic' || sub === 'classic')) {
    return matchesBookmarkType(p, sub as BookmarkType)
  }
  return false
}

function shopLocation(opts: {
  category?: string
  subcategory?: string
  q?: string
  price?: string
} = {}) {
  const cat = opts.category ?? category.value
  const sub = opts.subcategory ?? subcategory.value
  const search = opts.q ?? q.value.trim()
  const price = opts.price ?? priceTier.value
  const path = cat && cat !== 'all' ? `/shop/${cat}` : '/shop'
  return {
    path,
    query: {
      q: search || undefined,
      subcategory: sub || undefined,
      price: price || undefined,
    },
  }
}

const filtered = computed(() => {
  let list = [...catalog.value]
  if (category.value && category.value !== 'all') {
    list = list.filter((p) => p.category === category.value)
  }
  if (subcategory.value) {
    list = list.filter((p) => productMatchesSubcategory(p, subcategory.value))
  }
  const search = q.value.trim().toLowerCase()
  if (search) {
    list = list.filter((p) => p.name.toLowerCase().includes(search))
  }
  if (category.value === 'hampers' && priceTier.value) {
    const tier = Number.parseInt(priceTier.value, 10)
    if (Number.isFinite(tier)) {
      list = list.filter((p) => Math.round(p.price) === tier)
    }
  }
  const arr = [...list]
  if (sort.value === 'price-asc') arr.sort((a, b) => a.price - b.price)
  else if (sort.value === 'price-desc') arr.sort((a, b) => b.price - a.price)
  else if (sort.value === 'name') arr.sort((a, b) => a.name.localeCompare(b.name))
  return arr
})

const personalisePromo = computed(() => {
  if (!category.value || category.value === 'all') return null
  return personalisePromoForCategory(category.value)
})

function setCategory(id: string) {
  category.value = id
  subcategory.value = ''
  router.push(shopLocation({ category: id, subcategory: '' }))
}

function setSubcategory(sub: string) {
  const next = subcategory.value === sub ? '' : sub
  subcategory.value = next
  router.push(shopLocation({ subcategory: next }))
}

function setPriceTier(tier: string) {
  priceTier.value = tier
  router.push(shopLocation({ price: tier }))
}

function onSearchSubmit(e: Event) {
  e.preventDefault()
  router.push(shopLocation())
}

function applyFilters(payload: {
  category: string
  subcategory: string
  sort: 'featured' | 'price-asc' | 'price-desc' | 'name'
}) {
  category.value = payload.category
  subcategory.value = payload.subcategory
  sort.value = payload.sort
  router.push(shopLocation({ category: payload.category, subcategory: payload.subcategory }))
}

const activeFilterCount = computed(() => {
  let n = 0
  if (category.value !== 'all') n++
  if (subcategory.value) n++
  if (sort.value !== 'featured') n++
  return n
})
</script>

<template>
  <div class="page-shop tm-section tm-animate-in">
    <div class="tm-container">
      <header class="page-shop__intro">
        <h1 class="page-shop__title">{{ pageTitle }}</h1>
        <p class="page-shop__lead">
          <template v-if="subcategory && category !== 'all'">
            Browse {{ pageTitle.toLowerCase() }} in our {{ category }} collection.
          </template>
          <template v-else-if="category === 'magnets'">
            Fridge-ready magnets for photos, notes, and little moments.
          </template>
          <template v-else-if="category === 'cards'">
            Birthday, thank you, and love cards — ready to post or pair with a small gift.
          </template>
          <template v-else-if="category === 'calendars'">
            Desk and wall calendars for the year ahead.
          </template>
          <template v-else-if="category === 'bookmarks'">
            Magnetic and classic bookmarks — filter by type below or design a custom magnetic bookmark with your photo and text.
          </template>
          <template v-else>
            Search and filter by category — bookmarks, cards, calendars, magnets, and more.
          </template>
        </p>
      </header>

      <form class="shop-toolbar" role="search" @submit="onSearchSubmit">
        <label class="shop-search">
          <span class="sr-only">Search products</span>
          <Search class="shop-search__icon" :size="19" :stroke-width="2.25" aria-hidden="true" />
          <input v-model="q" type="search" class="shop-search__input" placeholder="Search by name…" autocomplete="off" />
        </label>
        <button v-if="isMobileApp" type="button" class="shop-toolbar__filter tm-press" @click="filtersOpen = true">
          <SlidersHorizontal :size="18" />
          Filters
          <span v-if="activeFilterCount" class="shop-toolbar__badge">{{ activeFilterCount }}</span>
        </button>
        <button v-else type="submit" class="shop-toolbar__btn tm-press">
          <Search :size="18" :stroke-width="2.25" aria-hidden="true" />
          Search
        </button>

        <div v-if="!isMobileApp" class="shop-sort">
          <span class="shop-sort__ico" aria-hidden="true">
            <SlidersHorizontal :size="18" :stroke-width="2" />
          </span>
          <label class="sr-only" for="sort-select">Sort</label>
          <select id="sort-select" v-model="sort" class="shop-sort__select">
            <option value="featured">Featured</option>
            <option value="price-asc">Price: low to high</option>
            <option value="price-desc">Price: high to low</option>
            <option value="name">Name A–Z</option>
          </select>
        </div>
      </form>

      <div v-if="!isMobileApp" class="shop-chips" role="group" aria-label="Filter by category">
        <button
          v-for="c in SHOP_CATEGORIES"
          :key="c.id"
          type="button"
          class="chip"
          :class="{ 'chip--on': category === c.id }"
          @click="setCategory(c.id)"
        >
          {{ c.label }}
        </button>
      </div>

      <div
        v-if="!isMobileApp && category === 'hampers'"
        class="shop-subchips"
        role="group"
        aria-label="Filter hampers by price"
      >
        <button
          v-for="tier in HAMPER_PRICE_TIERS"
          :key="tier.id || 'all'"
          type="button"
          class="chip chip--sub"
          :class="{ 'chip--on': priceTier === tier.id }"
          @click="setPriceTier(tier.id)"
        >
          {{ tier.label }}
        </button>
      </div>

      <div
        v-if="!isMobileApp && activeSubcategories.length"
        class="shop-subchips"
        role="group"
        :aria-label="`Filter ${category} by type`"
      >
        <button
          type="button"
          class="chip chip--sub"
          :class="{ 'chip--on': !subcategory }"
          @click="setSubcategory('')"
        >
          All
        </button>
        <button
          v-for="sub in activeSubcategories"
          :key="sub.slug"
          type="button"
          class="chip chip--sub"
          :class="{ 'chip--on': subcategory === sub.slug }"
          @click="setSubcategory(sub.slug)"
        >
          {{ sub.name }}
        </button>
      </div>

      <div class="page-shop__body" :class="{ 'page-shop__body--split': personalisePromo }">
        <div class="page-shop__main">
          <ProductGridSkeleton v-if="catalogLoading && !catalogReady" />
          <UiEmptyState
            v-else-if="filtered.length === 0"
            title="No products found"
            description="Try another category, clear filters, or search for something else."
          >
            <template #action>
              <button type="button" class="shop-empty-btn" @click="applyFilters({ category: 'all', subcategory: '', sort: 'featured' })">
                Clear filters
              </button>
            </template>
          </UiEmptyState>

          <div v-else class="page-shop__grid tm-product-grid">
            <ProductCard v-for="p in filtered" :key="p.id" :product="p" />
          </div>
        </div>

        <aside v-if="personalisePromo" class="page-shop__aside">
          <ShopPersonalisePromo :promo="personalisePromo" :category="category" sidebar />
        </aside>
      </div>
    </div>

    <ShopFilterSheet
      v-model:open="filtersOpen"
      :category="category"
      :subcategory="subcategory"
      :sort="sort"
      :subcategories="activeSubcategories"
      @apply="applyFilters"
    />
  </div>
</template>

<style scoped>
.page-shop {
  padding-top: 2rem;
  padding-bottom: 4rem;
  position: relative;
}

.page-shop__intro {
  margin-bottom: 2rem;
  max-width: 40rem;
}

.page-shop__title {
  margin: 0 0 0.5rem;
  font-family: var(--font-display);
  font-size: clamp(1.85rem, 3vw, 2.35rem);
  font-weight: 500;
  color: var(--color-ink);
}

.page-shop__lead {
  margin: 0;
  color: var(--color-ink-muted);
  line-height: 1.6;
}

.shop-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: stretch;
  margin-bottom: 1.25rem;
}

.shop-search {
  flex: 1;
  min-width: 200px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0 1rem;
  min-height: var(--tap-min);
  border-radius: 999px;
  border: 1px solid var(--color-border);
  background: var(--color-surface-elevated);
  box-shadow: var(--shadow-sm);
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.shop-search:focus-within {
  border-color: var(--color-accent);
  box-shadow: 0 0 0 3px var(--color-accent-soft);
}

.shop-search__icon {
  flex-shrink: 0;
  color: var(--color-accent);
  opacity: 0.85;
}

.shop-search__input {
  flex: 1;
  border: none;
  background: none;
  font: inherit;
  font-size: 1rem;
  min-width: 0;
}

.shop-toolbar__filter {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  min-height: var(--tap-min);
  padding: 0 1rem;
  border-radius: var(--tm-radius-full);
  border: 1px solid var(--tm-border);
  background: var(--tm-surface-2);
  font: inherit;
  font-weight: 650;
  color: var(--tm-ink);
  cursor: pointer;
  position: relative;
}

.shop-toolbar__badge {
  min-width: 1.1rem;
  height: 1.1rem;
  padding: 0 0.3rem;
  border-radius: var(--tm-radius-full);
  background: var(--tm-accent);
  color: #fff;
  font-size: 0.65rem;
  font-weight: 800;
  line-height: 1.1rem;
  text-align: center;
}

.shop-empty-btn {
  min-height: var(--tap-min);
  padding: 0 1.15rem;
  border: none;
  border-radius: var(--tm-radius-full);
  background: var(--tm-accent);
  color: #fff;
  font: inherit;
  font-weight: 650;
  cursor: pointer;
}

.shop-toolbar__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  min-height: var(--tap-min);
  padding: 0 1.25rem;
  border-radius: 999px;
  border: none;
  background: var(--tm-gradient);
  color: #fff;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  box-shadow: 0 4px 16px rgba(45, 92, 82, 0.28);
  transition:
    filter 0.2s ease,
    transform 0.15s ease;
}

.shop-toolbar__btn:hover {
  filter: brightness(1.05);
  transform: translateY(-1px);
}

.shop-sort {
  min-width: 200px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.shop-sort__ico {
  color: var(--color-ink-faint);
  flex-shrink: 0;
}

.shop-sort__select {
  flex: 1;
  min-height: var(--tap-min);
  padding: 0 0.85rem;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border);
  background: var(--color-surface-elevated);
  font: inherit;
  font-size: 0.95rem;
  color: var(--color-ink);
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
}

.shop-sort__select:hover {
  border-color: rgba(45, 92, 82, 0.3);
}

.shop-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.shop-subchips {
  display: flex;
  flex-wrap: wrap;
  gap: 0.4rem;
  margin-bottom: 1.5rem;
  padding-left: 0.15rem;
}

.chip--sub {
  min-height: 34px;
  font-size: 0.8125rem;
}

.chip {
  min-height: 40px;
  padding: 0 1rem;
  border-radius: 999px;
  border: 1px solid var(--color-border);
  background: var(--color-surface-elevated);
  font: inherit;
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-ink-muted);
  cursor: pointer;
  transition:
    background 0.2s ease,
    border-color 0.2s ease,
    color 0.2s ease,
    transform 0.15s ease;
}

.chip:hover {
  border-color: rgba(45, 92, 82, 0.25);
  transform: translateY(-1px);
}

.chip--on {
  background: var(--tm-gradient-soft);
  border-color: var(--color-accent);
  color: var(--color-accent);
  font-weight: 700;
}

.shop-empty {
  padding: 2rem;
  text-align: center;
  color: var(--color-ink-muted);
  background: var(--color-surface);
  border-radius: var(--radius-md);
  border: 1px dashed var(--color-border);
}

.page-shop__body--split {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(15.5rem, 18.5rem);
  gap: 1.5rem;
  align-items: start;
}

.page-shop__main {
  min-width: 0;
}

.page-shop__aside {
  position: sticky;
  top: calc(var(--header-h) + 1rem);
  align-self: start;
}

@media (max-width: 960px) {
  .page-shop__body--split {
    grid-template-columns: 1fr;
  }

  .page-shop__aside {
    position: static;
    order: -1;
  }
}

.page-shop__grid {
  /* Layout from .tm-product-grid */
}

.page-shop__body--split .page-shop__grid {
  grid-template-columns: repeat(2, minmax(0, 1fr));
}

@media (min-width: 640px) {
  .page-shop__body--split .page-shop__grid {
    grid-template-columns: repeat(auto-fill, minmax(10.5rem, 1fr));
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

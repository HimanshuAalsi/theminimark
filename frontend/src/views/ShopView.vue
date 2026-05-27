<script setup lang="ts">
import { Search, SlidersHorizontal } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import ProductCard from '@/components/product/ProductCard.vue'
import ShopPersonalisePromo from '@/components/shop/ShopPersonalisePromo.vue'
import { personalisePromoForCategory } from '@/data/personalise'
import { SHOP_CATEGORIES, matchesBookmarkType, type BookmarkType } from '@/data/siteContent'
import { useCatalogStore } from '@/stores/catalog'
import { storeToRefs } from 'pinia'

const route = useRoute()
const router = useRouter()
const catalogStore = useCatalogStore()
const { catalog } = storeToRefs(catalogStore)

const q = ref(String(route.query.q ?? ''))
const category = ref(String(route.query.category ?? 'all'))
const bookmarkType = ref(String(route.query.type ?? ''))
const sort = ref<'featured' | 'price-asc' | 'price-desc' | 'name'>('featured')

watch(
  () => route.query,
  () => {
    q.value = String(route.query.q ?? '')
    category.value = String(route.query.category ?? 'all')
    bookmarkType.value = String(route.query.type ?? '')
  },
  { deep: true }
)

const pageTitle = computed(() => {
  if (category.value === 'bookmarks' && bookmarkType.value === 'classic') return 'Classic bookmarks'
  if (category.value === 'bookmarks' && bookmarkType.value === 'magnetic') return 'Magnetic bookmarks'
  if (category.value === 'magnets') return 'Fridge magnets'
  if (category.value && category.value !== 'all') {
    const cat = SHOP_CATEGORIES.find((c) => c.id === category.value)
    return cat?.label ?? 'Shop'
  }
  return 'Shop'
})

const filtered = computed(() => {
  let list = [...catalog.value]
  if (category.value && category.value !== 'all') {
    list = list.filter((p) => p.category === category.value)
  }
  if (
    category.value === 'bookmarks' &&
    (bookmarkType.value === 'classic' || bookmarkType.value === 'magnetic')
  ) {
    list = list.filter((p) => matchesBookmarkType(p, bookmarkType.value as BookmarkType))
  }
  const search = q.value.trim().toLowerCase()
  if (search) {
    list = list.filter((p) => p.name.toLowerCase().includes(search))
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
  bookmarkType.value = ''
  router.push({
    path: '/shop',
    query: {
      q: q.value.trim() || undefined,
      category: id === 'all' ? undefined : id,
    },
  })
}

function onSearchSubmit(e: Event) {
  e.preventDefault()
  router.push({
    path: '/shop',
    query: {
      q: q.value.trim() || undefined,
      category: category.value === 'all' ? undefined : category.value,
    },
  })
}
</script>

<template>
  <div class="page-shop tm-section tm-animate-in">
    <div class="tm-container">
      <header class="page-shop__intro">
        <h1 class="page-shop__title">{{ pageTitle }}</h1>
        <p class="page-shop__lead">
          <template v-if="category === 'bookmarks' && bookmarkType === 'classic'">
            Paper and clip bookmarks for everyday reading.
          </template>
          <template v-else-if="category === 'bookmarks' && bookmarkType === 'magnetic'">
            Fold-over magnetic clips that stay put on every page.
          </template>
          <template v-else-if="category === 'magnets'">
            Fridge-ready magnets for photos, notes, and little moments — or upload your own in our
            personalise studio.
          </template>
          <template v-else-if="category === 'cards'">
            Birthday, thank you, and love cards — or design a custom photo card in the personalise
            studio.
          </template>
          <template v-else-if="category === 'calendars'">
            Desk and wall calendars for the year ahead — add your photos with our personalise
            studio.
          </template>
          <template v-else-if="category === 'bookmarks'">
            <template v-if="bookmarkType === 'magnetic'">
              Fold-over magnetic clips — create your own with a photo in the personalise studio.
            </template>
            <template v-else>
              Paper and clip bookmarks for everyday reading — try a custom magnetic bookmark too.
            </template>
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
        <button type="submit" class="shop-toolbar__btn tm-press">
          <Search :size="18" :stroke-width="2.25" aria-hidden="true" />
          Search
        </button>

        <div class="shop-sort">
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

      <div class="shop-chips" role="group" aria-label="Filter by category">
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

      <div class="page-shop__body" :class="{ 'page-shop__body--split': personalisePromo }">
        <div class="page-shop__main">
          <p v-if="filtered.length === 0" class="shop-empty" role="status">
            No products match. Try another category or clear your search.
          </p>

          <div v-else class="page-shop__grid">
            <ProductCard v-for="p in filtered" :key="p.id" :product="p" />
          </div>
        </div>

        <aside v-if="personalisePromo" class="page-shop__aside">
          <ShopPersonalisePromo :promo="personalisePromo" sidebar />
        </aside>
      </div>
    </div>
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

.shop-toolbar__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  min-height: var(--tap-min);
  padding: 0 1.25rem;
  border-radius: 999px;
  border: none;
  background: linear-gradient(135deg, var(--color-accent), #1a4a42);
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
  margin-bottom: 1.75rem;
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
  background: linear-gradient(135deg, var(--color-accent-soft), rgba(255, 255, 255, 0.9));
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
  display: grid;
  gap: 1.1rem;
  grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
}

.page-shop__body--split .page-shop__grid {
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
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

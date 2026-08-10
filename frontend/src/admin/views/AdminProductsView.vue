<script setup lang="ts">
import {
  Bookmark,
  Check,
  Eye,
  EyeOff,
  ExternalLink,
  Loader2,
  Pencil,
  Plus,
  RefreshCw,
  Search,
  Sparkles,
  Star,
  Trash2,
  Upload,
  Warehouse,
  X,
} from 'lucide-vue-next'
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import AdminPagination from '@/admin/components/AdminPagination.vue'
import { useAdminList } from '@/admin/composables/useAdminList'
import {
  adminBulkProducts,
  adminDeleteProduct,
  adminImageSrc,
  adminListCategories,
  adminListProducts,
  adminSaveProduct,
  type AdminCategory,
  type AdminProduct,
  type AdminProductBulkAction,
} from '@/admin/lib/adminApi'
import { formatCurrency } from '@/lib/currency'

const list = useAdminList(25)
const items = ref<AdminProduct[]>([])
const categories = ref<AdminCategory[]>([])
const selected = ref<Set<string>>(new Set())
const togglingId = ref<string | null>(null)
const bulkBusy = ref(false)
const info = ref('')

const q = ref('')
const qDebounced = ref('')
const category = ref('all')
const active = ref('all')
const stock = ref('all')
const featured = ref('all')
const sortBy = ref('sortOrder')
const sortDir = ref('asc')

const SORT_OPTIONS = [
  { value: 'sortOrder:asc', label: 'Sort order' },
  { value: 'id:asc', label: 'ID A→Z' },
  { value: 'id:desc', label: 'ID Z→A' },
  { value: 'sku:asc', label: 'SKU A→Z' },
  { value: 'sku:desc', label: 'SKU Z→A' },
  { value: 'slug:asc', label: 'Slug A→Z' },
  { value: 'slug:desc', label: 'Slug Z→A' },
  { value: 'name:asc', label: 'Name A→Z' },
  { value: 'name:desc', label: 'Name Z→A' },
  { value: 'category:asc', label: 'Category A→Z' },
  { value: 'category:desc', label: 'Category Z→A' },
  { value: 'price:asc', label: 'Price ↑' },
  { value: 'price:desc', label: 'Price ↓' },
  { value: 'stock:asc', label: 'Stock ↑' },
  { value: 'stock:desc', label: 'Stock ↓' },
  { value: 'created:desc', label: 'Newest' },
  { value: 'created:asc', label: 'Oldest' },
  { value: 'updated:desc', label: 'Updated' },
] as const

const sortValue = computed({
  get: () => `${sortBy.value}:${sortDir.value}`,
  set: (v: string) => {
    const [by, dir] = v.split(':')
    sortBy.value = by || 'sortOrder'
    sortDir.value = dir === 'desc' ? 'desc' : 'asc'
  },
})

const activeFiltersCount = computed(() => {
  let n = 0
  if (qDebounced.value.trim()) n++
  if (category.value !== 'all') n++
  if (active.value !== 'all') n++
  if (stock.value !== 'all') n++
  if (featured.value !== 'all') n++
  return n
})

const allOnPageSelected = computed(
  () => items.value.length > 0 && items.value.every((p) => selected.value.has(p.id)),
)
const someSelected = computed(() => selected.value.size > 0)

function buildParams(): Record<string, string> {
  return {
    q: qDebounced.value.trim(),
    category: category.value,
    active: active.value,
    stock: stock.value,
    featured: featured.value,
    sortBy: sortBy.value,
    sortDir: sortDir.value,
  }
}

async function load() {
  const res = await list.run(() => adminListProducts(list.listParams(buildParams())))
  if (res) {
    items.value = res.items
    list.setMeta(res.meta)
    const ids = new Set(res.items.map((p) => p.id))
    selected.value = new Set([...selected.value].filter((id) => ids.has(id)))
  }
}

async function remove(p: AdminProduct) {
  if (!confirm(`Delete "${p.name}"?`)) return
  try {
    await adminDeleteProduct(p.id)
    selected.value.delete(p.id)
    info.value = `Deleted "${p.name}"`
    await load()
  } catch (e) {
    list.error.value = e instanceof Error ? e.message : 'Delete failed'
  }
}

async function toggleStatus(p: AdminProduct) {
  if (togglingId.value) return
  togglingId.value = p.id
  list.error.value = ''
  try {
    const next = !p.isActive
    const res = await adminSaveProduct({ isActive: next }, p.id)
    if (!res.ok) throw new Error(res.message || 'Update failed')
    p.isActive = next
  } catch (e) {
    list.error.value = e instanceof Error ? e.message : 'Status update failed'
  } finally {
    togglingId.value = null
  }
}

async function runBulk(action: AdminProductBulkAction) {
  const ids = [...selected.value]
  if (!ids.length) return
  if (!confirm(`${action === 'delete' ? 'Delete' : 'Update'} ${ids.length} product(s)?`)) return

  bulkBusy.value = true
  list.error.value = ''
  info.value = ''
  try {
    const res = await adminBulkProducts(ids, action)
    if (!res.ok) throw new Error(res.message || 'Bulk action failed')
    selected.value.clear()
    info.value =
      action === 'delete'
        ? `Deleted ${res.deleted ?? ids.length} product(s)`
        : `Updated ${res.updated ?? ids.length} product(s)`
    await load()
  } catch (e) {
    list.error.value = e instanceof Error ? e.message : 'Bulk action failed'
  } finally {
    bulkBusy.value = false
  }
}

function toggleSelect(id: string) {
  const next = new Set(selected.value)
  if (next.has(id)) next.delete(id)
  else next.add(id)
  selected.value = next
}

function toggleSelectAll() {
  if (allOnPageSelected.value) {
    for (const p of items.value) selected.value.delete(p.id)
    selected.value = new Set(selected.value)
  } else {
    const next = new Set(selected.value)
    for (const p of items.value) next.add(p.id)
    selected.value = next
  }
}

function clearSelection() {
  selected.value = new Set()
}

function onFilterChange() {
  list.resetPage()
  void load()
}

function clearFilters() {
  q.value = ''
  qDebounced.value = ''
  category.value = 'all'
  active.value = 'all'
  stock.value = 'all'
  featured.value = 'all'
  sortBy.value = 'sortOrder'
  sortDir.value = 'asc'
  onFilterChange()
}

let searchTimer: ReturnType<typeof setTimeout> | undefined
watch(q, (val) => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    if (qDebounced.value !== val) {
      qDebounced.value = val
      onFilterChange()
    }
  }, 400)
})

watch(sortValue, () => onFilterChange())
watch(() => list.page.value, () => void load())
watch(() => list.perPage.value, () => {
  list.resetPage()
  void load()
})

onMounted(async () => {
  try {
    const catRes = await adminListCategories()
    categories.value = catRes.items.filter((c) => c.isActive)
  } catch {
    /* optional */
  }
  await load()
})

function stockLabel(p: AdminProduct): string {
  if (p.stockQuantity === null || p.stockQuantity === undefined) return '—'
  if (p.stockQuantity === 0) return '0'
  return String(p.stockQuantity)
}

function stockClass(p: AdminProduct): string {
  if (p.stockQuantity === null || p.stockQuantity === undefined) return 'prod-stock--na'
  if (p.stockQuantity === 0) return 'prod-stock--out'
  if (p.stockQuantity <= 5) return 'prod-stock--low'
  return ''
}

function categoryName(slug: string): string {
  return categories.value.find((c) => c.slug === slug)?.name ?? slug
}
</script>

<template>
  <div class="prod-page">
    <div class="admin-page-head prod-head">
      <div class="prod-head__left">
        <h1 class="admin-page-title">Products</h1>
        <span class="prod-head__count">{{ list.rangeLabel.value }}</span>
      </div>
      <div class="admin-actions">
        <RouterLink :to="{ name: 'admin-inventory' }" class="admin-btn admin-btn--ghost admin-btn--icon" title="Inventory">
          <Warehouse :size="16" />
        </RouterLink>
        <RouterLink :to="{ name: 'admin-bulk' }" class="admin-btn admin-btn--ghost admin-btn--icon" title="Bulk import">
          <Upload :size="16" />
        </RouterLink>
        <RouterLink :to="{ name: 'admin-bookmark-showcase' }" class="admin-btn admin-btn--ghost admin-btn--icon" title="Showcase">
          <Bookmark :size="16" />
        </RouterLink>
        <RouterLink :to="{ name: 'admin-product-new' }" class="admin-btn prod-head__new" title="New product">
          <Plus :size="16" />
          <span class="prod-head__new-label">New</span>
        </RouterLink>
      </div>
    </div>

    <div class="prod-toolbar admin-card">
      <div class="admin-input-wrap prod-toolbar__search">
        <Search :size="15" class="admin-input-wrap__icon" />
        <input v-model="q" type="search" placeholder="Search…" autocomplete="off" />
      </div>

      <select v-model="category" class="admin-select-compact" title="Category" @change="onFilterChange">
        <option value="all">Category</option>
        <option v-for="c in categories" :key="c.slug" :value="c.slug">{{ c.name }}</option>
      </select>

      <select v-model="active" class="admin-select-compact" title="Status" @change="onFilterChange">
        <option value="all">Status</option>
        <option value="1">Active</option>
        <option value="0">Hidden</option>
      </select>

      <select v-model="stock" class="admin-select-compact" title="Stock" @change="onFilterChange">
        <option value="all">Stock</option>
        <option value="in">In stock</option>
        <option value="low">Low ≤5</option>
        <option value="out">Out</option>
        <option value="untracked">Untracked</option>
      </select>

      <select v-model="featured" class="admin-select-compact" title="Homepage" @change="onFilterChange">
        <option value="all">Homepage</option>
        <option value="home">On home</option>
        <option value="bestseller">Bestseller</option>
        <option value="secondary">Secondary</option>
      </select>

      <select v-model="sortValue" class="admin-select-compact prod-toolbar__sort" title="Sort">
        <option v-for="opt in SORT_OPTIONS" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
      </select>

      <select v-model.number="list.perPage.value" class="admin-select-compact" title="Per page">
        <option :value="10">10</option>
        <option :value="25">25</option>
        <option :value="50">50</option>
        <option :value="100">100</option>
      </select>

      <button type="button" class="admin-btn admin-btn--ghost admin-btn--icon" title="Refresh" :disabled="list.busy.value" @click="load">
        <RefreshCw :size="15" :class="{ 'prod-spin': list.busy.value }" />
      </button>

      <button
        v-if="activeFiltersCount > 0"
        type="button"
        class="admin-btn admin-btn--ghost admin-btn--icon prod-toolbar__clear"
        :title="`Clear ${activeFiltersCount} filter(s)`"
        @click="clearFilters"
      >
        <X :size="15" />
        <span class="prod-toolbar__badge">{{ activeFiltersCount }}</span>
      </button>
    </div>

    <div v-if="someSelected" class="admin-bulk-bar prod-bulk">
      <span class="admin-bulk-bar__count">{{ selected.size }}</span>
      <button type="button" class="admin-btn admin-btn--sm admin-btn--icon" title="Activate" :disabled="bulkBusy" @click="runBulk('activate')">
        <Check :size="15" />
      </button>
      <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm admin-btn--icon" title="Hide" :disabled="bulkBusy" @click="runBulk('deactivate')">
        <EyeOff :size="15" />
      </button>
      <button type="button" class="admin-btn admin-btn--danger admin-btn--sm admin-btn--icon" title="Delete" :disabled="bulkBusy" @click="runBulk('delete')">
        <Trash2 :size="15" />
      </button>
      <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm admin-btn--icon" title="Clear selection" @click="clearSelection">
        <X :size="15" />
      </button>
      <Loader2 v-if="bulkBusy" :size="14" class="prod-spin" />
    </div>

    <p v-if="list.error.value" class="admin-error prod-flash">{{ list.error.value }}</p>
    <p v-else-if="info" class="prod-flash prod-flash--ok">{{ info }}</p>

    <div class="admin-card admin-table-wrap prod-table-wrap">
      <table class="admin-table prod-table">
        <thead>
          <tr>
            <th class="prod-col-check">
              <input
                type="checkbox"
                :checked="allOnPageSelected"
                :indeterminate="someSelected && !allOnPageSelected"
                aria-label="Select all"
                @change="toggleSelectAll"
              />
            </th>
            <th>Product</th>
            <th class="prod-col-num">Price</th>
            <th class="prod-col-num">Stock</th>
            <th class="prod-col-icon" title="Visibility">Vis</th>
            <th class="prod-col-actions"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="p in items" :key="p.id" :class="{ 'prod-row--selected': selected.has(p.id) }">
            <td class="prod-col-check">
              <input
                type="checkbox"
                :checked="selected.has(p.id)"
                :aria-label="`Select ${p.name}`"
                @change="toggleSelect(p.id)"
              />
            </td>
            <td class="prod-col-product">
              <div class="prod-product">
                <img
                  v-if="p.imageUrl || p.imagePath"
                  :src="adminImageSrc(p.imagePath || p.imageUrl)"
                  alt=""
                  width="36"
                  height="36"
                  class="admin-thumb prod-product__img"
                />
                <div v-else class="prod-product__img prod-product__img--empty" />
                <div class="prod-product__body">
                  <div class="prod-product__title-row">
                    <RouterLink
                      :to="{ name: 'admin-product-edit', params: { id: p.id } }"
                      class="prod-product__name"
                      :title="p.name"
                    >
                      {{ p.name }}
                    </RouterLink>
                    <span v-if="p.homeBestseller" class="prod-flag" title="Bestseller"><Star :size="11" /></span>
                    <span v-if="p.homeSecondary" class="prod-flag prod-flag--sec" title="Secondary"><Sparkles :size="11" /></span>
                  </div>
                  <div class="prod-product__meta">
                    <span class="prod-cat">{{ categoryName(p.category) }}</span>
                    <span v-if="p.sku" class="prod-sku">{{ p.sku }}</span>
                  </div>
                </div>
              </div>
            </td>
            <td class="prod-col-num">
              <span class="prod-price">{{ formatCurrency(p.price) }}</span>
              <span v-if="p.compareAt" class="prod-compare">{{ formatCurrency(p.compareAt) }}</span>
            </td>
            <td class="prod-col-num">
              <span class="prod-stock" :class="stockClass(p)">{{ stockLabel(p) }}</span>
            </td>
            <td class="prod-col-icon">
              <button
                type="button"
                class="admin-icon-btn"
                :class="p.isActive ? 'admin-icon-btn--active' : 'admin-icon-btn--hidden'"
                :disabled="togglingId === p.id"
                :title="p.isActive ? 'Active — click to hide' : 'Hidden — click to activate'"
                @click="toggleStatus(p)"
              >
                <Loader2 v-if="togglingId === p.id" :size="14" class="prod-spin" />
                <Eye v-else-if="p.isActive" :size="14" />
                <EyeOff v-else :size="14" />
              </button>
            </td>
            <td class="prod-col-actions">
              <div class="prod-row-actions">
                <RouterLink
                  :to="{ name: 'admin-product-edit', params: { id: p.id } }"
                  class="admin-icon-btn"
                  title="Edit"
                >
                  <Pencil :size="14" />
                </RouterLink>
                <a
                  :href="`/products/${p.slug}`"
                  target="_blank"
                  rel="noopener"
                  class="admin-icon-btn"
                  title="View on store"
                >
                  <ExternalLink :size="14" />
                </a>
                <button type="button" class="admin-icon-btn admin-icon-btn--danger" title="Delete" @click="remove(p)">
                  <Trash2 :size="14" />
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!items.length && !list.busy.value">
            <td colspan="6" class="prod-empty">No products match your filters.</td>
          </tr>
        </tbody>
      </table>
    </div>

    <AdminPagination
      :page="list.page.value"
      :total-pages="list.totalPages.value"
      :range-label="list.rangeLabel.value"
      :busy="list.busy.value"
      @prev="list.goToPage(list.page.value - 1)"
      @next="list.goToPage(list.page.value + 1)"
    />
  </div>
</template>

<style scoped>
.prod-page {
  --prod-row-pad: 0.45rem 0.6rem;
}

.prod-head {
  margin-bottom: 0.65rem;
}

.prod-head__left {
  display: flex;
  align-items: baseline;
  gap: 0.65rem;
  flex-wrap: wrap;
}

.prod-head__count {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--admin-muted);
}

.prod-head__new {
  min-height: 2rem;
  padding: 0 0.85rem;
}

.prod-head__new-label {
  font-size: 0.8125rem;
}

.prod-toolbar {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.4rem;
  padding: 0.55rem 0.65rem;
  margin-bottom: 0.65rem;
}

.prod-toolbar__search {
  flex: 1 1 10rem;
  min-width: 8rem;
}

.prod-toolbar__sort {
  max-width: 7.5rem;
}

.prod-toolbar__clear {
  position: relative;
}

.prod-toolbar__badge {
  position: absolute;
  top: -4px;
  right: -4px;
  min-width: 1rem;
  height: 1rem;
  padding: 0 0.2rem;
  border-radius: 999px;
  background: var(--admin-accent);
  color: #fff;
  font-size: 0.625rem;
  font-weight: 700;
  line-height: 1rem;
  text-align: center;
}

.prod-bulk {
  padding: 0.45rem 0.65rem;
  margin-bottom: 0.65rem;
}

.prod-bulk .admin-bulk-bar__count {
  min-width: 1.25rem;
  text-align: center;
}

.prod-flash {
  margin: 0 0 0.5rem;
  font-size: 0.75rem;
}

.prod-flash--ok {
  padding: 0.4rem 0.65rem;
  border-radius: 8px;
  background: #ecfdf5;
  color: #047857;
}

.prod-table-wrap {
  padding: 0;
  overflow: hidden;
}

.prod-table th,
.prod-table td {
  padding: var(--prod-row-pad);
  vertical-align: middle;
}

.prod-table th {
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
}

.prod-col-check {
  width: 2rem;
  text-align: center;
}

.prod-col-check input {
  width: 0.9rem;
  height: 0.9rem;
  accent-color: var(--admin-accent);
  cursor: pointer;
}

.prod-col-num {
  width: 5.5rem;
  text-align: right;
  white-space: nowrap;
}

.prod-col-icon {
  width: 2.5rem;
  text-align: center;
}

.prod-col-actions {
  width: 5.5rem;
}

.prod-col-product {
  min-width: 12rem;
}

.prod-product {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  min-width: 0;
}

.prod-product__img {
  width: 36px;
  height: 36px;
  flex-shrink: 0;
  border-radius: 6px;
  object-fit: cover;
}

.prod-product__img--empty {
  background: #e2e8f0;
}

.prod-product__body {
  min-width: 0;
  flex: 1;
}

.prod-product__title-row {
  display: flex;
  align-items: center;
  gap: 0.25rem;
  min-width: 0;
}

.prod-product__name {
  font-size: 0.8125rem;
  font-weight: 600;
  color: var(--admin-ink);
  text-decoration: none;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.prod-product__name:hover {
  color: var(--admin-accent);
}

.prod-flag {
  flex-shrink: 0;
  display: inline-grid;
  place-items: center;
  color: #b45309;
}

.prod-flag--sec {
  color: #6366f1;
}

.prod-product__meta {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin-top: 0.1rem;
  font-size: 0.625rem;
  color: var(--admin-muted);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.prod-cat {
  font-weight: 600;
  color: #64748b;
}

.prod-sku {
  opacity: 0.85;
}

.prod-sku::before {
  content: '·';
  margin-right: 0.35rem;
}

.prod-price {
  font-size: 0.8125rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}

.prod-compare {
  display: block;
  font-size: 0.625rem;
  color: var(--admin-muted);
  text-decoration: line-through;
  font-variant-numeric: tabular-nums;
}

.prod-stock {
  font-size: 0.8125rem;
  font-weight: 600;
  font-variant-numeric: tabular-nums;
}

.prod-stock--low {
  color: #b45309;
}

.prod-stock--out {
  color: var(--admin-danger);
}

.prod-stock--na {
  color: var(--admin-muted);
}

.prod-row-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.1rem;
}

.prod-row--selected {
  background: rgba(13, 148, 136, 0.05);
}

.prod-empty {
  text-align: center;
  padding: 1.5rem !important;
  color: var(--admin-muted);
  font-size: 0.8125rem;
}

.prod-spin {
  animation: prod-spin 0.8s linear infinite;
}

@keyframes prod-spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 768px) {
  .prod-head__new-label {
    display: none;
  }

  .prod-head__new {
    width: 2rem;
    min-width: 2rem;
    padding: 0;
  }

  .prod-col-num.prod-col-num {
    width: 4rem;
  }
}
</style>

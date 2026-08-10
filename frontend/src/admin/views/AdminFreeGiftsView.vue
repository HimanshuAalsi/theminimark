<script setup lang="ts">
import { Gift, Save, Search } from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'
import {
  adminGetFreeGifts,
  adminImageSrc,
  adminListProducts,
  adminSaveFreeGifts,
  type AdminProduct,
} from '@/admin/lib/adminApi'

const slots = ref<(AdminProduct | null)[]>([null, null, null, null])
const search = ref('')
const searchResults = ref<AdminProduct[]>([])
const activeSlot = ref<number | null>(null)
const error = ref('')
const message = ref('')
const busy = ref(false)
const loading = ref(true)

async function loadProducts(q = '') {
  const res = await adminListProducts({ q, perPage: '20', active: '1' })
  searchResults.value = res.items
}

async function load() {
  loading.value = true
  try {
    const res = await adminGetFreeGifts()
    const ids = res.freeGifts?.productIds ?? []
    slots.value = [null, null, null, null]
    for (let i = 0; i < 4; i++) {
      const id = ids[i]
      if (!id) continue
      const prodRes = await adminListProducts({ q: id, perPage: '1' })
      slots.value[i] = prodRes.items.find((p) => p.id === id) ?? prodRes.items[0] ?? null
    }
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Load failed'
  } finally {
    loading.value = false
  }
}

function openSearch(index: number) {
  activeSlot.value = index
  search.value = ''
  void loadProducts('')
}

function pickProduct(p: AdminProduct) {
  if (activeSlot.value === null) return
  slots.value[activeSlot.value] = p
  activeSlot.value = null
  searchResults.value = []
}

function clearSlot(index: number) {
  slots.value[index] = null
}

const filledCount = computed(() => slots.value.filter(Boolean).length)

async function save() {
  busy.value = true
  error.value = ''
  message.value = ''
  try {
    const productIds = slots.value.map((s) => s?.id ?? '').filter(Boolean)
    const res = await adminSaveFreeGifts(productIds)
    if (!res.ok) {
      error.value = res.message ?? 'Save failed'
      return
    }
    message.value = 'Free gift options saved. Cart will show these 4 products.'
    await load()
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Save failed'
  } finally {
    busy.value = false
  }
}

onMounted(load)
</script>

<template>
  <div>
    <header class="admin-page-head">
      <div>
        <h1 class="admin-page-title">Free gift options</h1>
        <p class="admin-meta">
          Choose exactly 4 products shoppers can pick from when their cart reaches ₹199+. Leave a slot
          empty to show fewer options (remaining slots fall back to random products until you fill all
          4).
        </p>
      </div>
      <button type="button" class="admin-btn" :disabled="busy" @click="save">
        <Save :size="15" />
        {{ busy ? 'Saving…' : 'Save' }}
      </button>
    </header>

    <p v-if="message" class="hp-success">{{ message }}</p>
    <p v-if="error" class="admin-error">{{ error }}</p>

    <div v-if="loading" class="admin-card admin-card--pad">Loading…</div>

    <div v-else class="fg-grid">
      <article v-for="(slot, i) in slots" :key="i" class="fg-slot admin-card">
        <p class="fg-slot__label">
          <Gift :size="14" aria-hidden="true" />
          Slot {{ i + 1 }}
        </p>

        <div v-if="slot" class="fg-slot__picked">
          <img
            v-if="slot.imageUrl || slot.imagePath"
            :src="slot.imageUrl || adminImageSrc(slot.imagePath)"
            alt=""
          />
          <div class="fg-slot__info">
            <strong>{{ slot.name }}</strong>
            <span>ID: {{ slot.id }}</span>
          </div>
          <div class="fg-slot__actions">
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" @click="openSearch(i)">
              Change
            </button>
            <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" @click="clearSlot(i)">
              Clear
            </button>
          </div>
        </div>

        <button v-else type="button" class="fg-slot__empty" @click="openSearch(i)">
          Select product
        </button>
      </article>
    </div>

    <p class="admin-meta fg-foot">{{ filledCount }} of 4 slots filled</p>

    <div v-if="activeSlot !== null" class="fg-search admin-card admin-card--pad">
      <h2 class="fg-search__title">Pick product for slot {{ activeSlot + 1 }}</h2>
      <form class="fg-search__bar" @submit.prevent="loadProducts(search)">
        <Search :size="16" aria-hidden="true" />
        <input v-model="search" type="search" placeholder="Search by name or ID…" />
        <button type="submit" class="admin-btn admin-btn--ghost">Search</button>
      </form>
      <ul class="fg-search__list" role="list">
        <li v-for="p in searchResults" :key="p.id">
          <button type="button" class="fg-search__item" @click="pickProduct(p)">
            <img
              v-if="p.imageUrl || p.imagePath"
              :src="p.imageUrl || adminImageSrc(p.imagePath)"
              alt=""
            />
            <span>{{ p.name }}</span>
            <span class="fg-search__id">{{ p.id }}</span>
          </button>
        </li>
      </ul>
      <button type="button" class="admin-btn admin-btn--ghost" @click="activeSlot = null">Cancel</button>
    </div>
  </div>
</template>

<style scoped>
.fg-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 1rem;
  margin-bottom: 1rem;
}

@media (max-width: 720px) {
  .fg-grid {
    grid-template-columns: 1fr;
  }
}

.fg-slot {
  padding: 1rem;
}

.fg-slot__label {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin: 0 0 0.75rem;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--admin-muted);
}

.fg-slot__picked {
  display: grid;
  grid-template-columns: 4rem 1fr;
  gap: 0.65rem;
  align-items: start;
}

.fg-slot__picked img {
  width: 4rem;
  height: 4rem;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid var(--admin-border);
}

.fg-slot__info {
  display: flex;
  flex-direction: column;
  gap: 0.2rem;
  min-width: 0;
}

.fg-slot__info strong {
  font-size: 0.875rem;
  line-height: 1.3;
}

.fg-slot__info span {
  font-size: 0.6875rem;
  color: var(--admin-muted);
}

.fg-slot__actions {
  grid-column: 1 / -1;
  display: flex;
  gap: 0.4rem;
}

.fg-slot__empty {
  width: 100%;
  min-height: 5rem;
  border: 2px dashed var(--admin-border);
  border-radius: 10px;
  background: #f8fafc;
  color: var(--admin-accent);
  font-weight: 650;
  cursor: pointer;
}

.fg-slot__empty:hover {
  border-color: var(--admin-accent);
  background: var(--admin-accent-light);
}

.fg-foot {
  margin-bottom: 1.5rem;
}

.fg-search__title {
  margin: 0 0 0.75rem;
  font-size: 1rem;
}

.fg-search__bar {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
}

.fg-search__bar input {
  flex: 1;
  min-height: 2.25rem;
  padding: 0 0.65rem;
  border: 1px solid var(--admin-border);
  border-radius: 8px;
}

.fg-search__list {
  margin: 0 0 0.75rem;
  padding: 0;
  list-style: none;
  max-height: 16rem;
  overflow-y: auto;
}

.fg-search__item {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  width: 100%;
  padding: 0.5rem;
  border: none;
  border-bottom: 1px solid var(--admin-border);
  background: transparent;
  text-align: left;
  cursor: pointer;
}

.fg-search__item:hover {
  background: #f8fafc;
}

.fg-search__item img {
  width: 2.5rem;
  height: 2.5rem;
  object-fit: cover;
  border-radius: 6px;
}

.fg-search__id {
  margin-left: auto;
  font-size: 0.6875rem;
  color: var(--admin-muted);
}

.hp-success {
  margin: 0 0 1rem;
  padding: 0.65rem 0.85rem;
  border-radius: 8px;
  background: #ecfdf5;
  color: #047857;
  font-size: 0.875rem;
  font-weight: 600;
}
</style>

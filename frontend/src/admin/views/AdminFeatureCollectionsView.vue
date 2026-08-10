<script setup lang="ts">
import { ListChecks, Plus, Save, Trash2 } from 'lucide-vue-next'
import { computed, onMounted, ref } from 'vue'
import {
  adminCreateFeatureCollection,
  adminDeleteFeatureCollection,
  adminListCategories,
  adminListFeatureCollections,
  adminUpdateFeatureCollection,
  type AdminFeatureCollection,
} from '@/admin/lib/adminApi'

const collections = ref<AdminFeatureCollection[]>([])
const categories = ref<{ slug: string; name: string }[]>([])
const error = ref('')
const message = ref('')
const busy = ref(false)
const loading = ref(true)
const expandedId = ref<string | null>(null)

const newForm = ref({ name: '', category: '', features: [''] as string[] })

const categoryLabel = computed(() => {
  const map = new Map(categories.value.map((c) => [c.slug, c.name]))
  return (slug: string) => (slug ? map.get(slug) ?? slug : 'Any category')
})

async function load() {
  loading.value = true
  error.value = ''
  try {
    const [colRes, catRes] = await Promise.all([adminListFeatureCollections(), adminListCategories()])
    collections.value = colRes.collections ?? []
    categories.value = catRes.items.map((c) => ({ slug: c.slug, name: c.name }))
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Load failed'
  } finally {
    loading.value = false
  }
}

function toggleExpand(id: string) {
  expandedId.value = expandedId.value === id ? null : id
}

function addNewFeatureLine() {
  newForm.value.features.push('')
}

function removeNewFeatureLine(index: number) {
  newForm.value.features = newForm.value.features.filter((_, i) => i !== index)
}

function addEditFeatureLine(col: AdminFeatureCollection) {
  col.features.push('')
}

function removeEditFeatureLine(col: AdminFeatureCollection, index: number) {
  col.features = col.features.filter((_, i) => i !== index)
}

async function createCollection() {
  const name = newForm.value.name.trim()
  if (!name) {
    error.value = 'Name is required'
    return
  }
  busy.value = true
  error.value = ''
  message.value = ''
  try {
    const res = await adminCreateFeatureCollection({
      name,
      category: newForm.value.category,
      features: newForm.value.features.map((f) => f.trim()).filter(Boolean),
    })
    if (!res.ok) {
      error.value = res.message ?? 'Create failed'
      return
    }
    collections.value = res.collections ?? collections.value
    newForm.value = { name: '', category: '', features: [''] }
    message.value = 'Collection created.'
    if (res.collection) expandedId.value = res.collection.id
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Create failed'
  } finally {
    busy.value = false
  }
}

async function saveCollection(col: AdminFeatureCollection) {
  busy.value = true
  error.value = ''
  message.value = ''
  try {
    const res = await adminUpdateFeatureCollection(col.id, {
      name: col.name.trim(),
      category: col.category,
      features: col.features.map((f) => f.trim()).filter(Boolean),
    })
    if (!res.ok) {
      error.value = res.message ?? 'Save failed'
      return
    }
    if (res.collections) collections.value = res.collections
    message.value = `"${col.name}" saved.`
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Save failed'
  } finally {
    busy.value = false
  }
}

async function removeCollection(col: AdminFeatureCollection) {
  if (!confirm(`Delete "${col.name}"? Products already saved keep their features.`)) return
  busy.value = true
  error.value = ''
  try {
    const res = await adminDeleteFeatureCollection(col.id)
    if (!res.ok) {
      error.value = res.message ?? 'Delete failed'
      return
    }
    collections.value = res.collections ?? collections.value.filter((c) => c.id !== col.id)
    if (expandedId.value === col.id) expandedId.value = null
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Delete failed'
  } finally {
    busy.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="admin-page">
    <div class="admin-page-head">
      <div>
        <h1 class="admin-page-title">Feature collections</h1>
        <p class="admin-meta">
          Reusable bullet-point sets for product pages. Import them when adding or editing a product instead of
          typing the same lines every time.
        </p>
      </div>
    </div>

    <p v-if="message" class="admin-success">{{ message }}</p>
    <p v-if="error" class="admin-error">{{ error }}</p>

    <div class="admin-card fc-new">
      <h2 class="admin-card-title">New collection</h2>
      <div class="admin-form-grid fc-new__grid">
        <div class="admin-field">
          <label>Name *</label>
          <input v-model="newForm.name" class="admin-input" placeholder="e.g. Magnetic bookmarks" />
        </div>
        <div class="admin-field">
          <label>Category (optional)</label>
          <select v-model="newForm.category" class="admin-select">
            <option value="">Any category</option>
            <option v-for="c in categories" :key="c.slug" :value="c.slug">{{ c.name }}</option>
          </select>
        </div>
      </div>
      <div class="admin-field">
        <label>Features</label>
        <ul class="fc-features" role="list">
          <li v-for="(_, idx) in newForm.features" :key="`new-${idx}`" class="fc-features__row">
            <input v-model="newForm.features[idx]" class="admin-input admin-input--sm" placeholder="Feature bullet point" />
            <button type="button" class="fc-features__remove" aria-label="Remove" @click="removeNewFeatureLine(idx)">
              <Trash2 :size="14" />
            </button>
          </li>
        </ul>
        <button type="button" class="admin-btn admin-btn--ghost fc-features__add" @click="addNewFeatureLine">
          <Plus :size="15" />
          Add line
        </button>
      </div>
      <button type="button" class="admin-btn" :disabled="busy" @click="createCollection">
        <Plus :size="16" />
        Create collection
      </button>
    </div>

    <div v-if="loading" class="admin-meta">Loading…</div>

    <div v-else-if="collections.length === 0" class="admin-card admin-empty">
      <ListChecks :size="28" />
      <p>No collections yet. Create one above or import defaults by saving on the server.</p>
    </div>

    <div v-else class="fc-list">
      <article v-for="col in collections" :key="col.id" class="admin-card fc-item">
        <button type="button" class="fc-item__head" @click="toggleExpand(col.id)">
          <div>
            <h3 class="fc-item__title">{{ col.name }}</h3>
            <p class="admin-meta">
              {{ categoryLabel(col.category) }} · {{ col.features.length }} feature{{
                col.features.length === 1 ? '' : 's'
              }}
            </p>
          </div>
          <span class="fc-item__toggle">{{ expandedId === col.id ? '−' : '+' }}</span>
        </button>

        <div v-if="expandedId === col.id" class="fc-item__body">
          <div class="admin-form-grid fc-new__grid">
            <div class="admin-field">
              <label>Name</label>
              <input v-model="col.name" class="admin-input" />
            </div>
            <div class="admin-field">
              <label>Category</label>
              <select v-model="col.category" class="admin-select">
                <option value="">Any category</option>
                <option v-for="c in categories" :key="c.slug" :value="c.slug">{{ c.name }}</option>
              </select>
            </div>
          </div>
          <ul class="fc-features" role="list">
            <li v-for="(_, idx) in col.features" :key="`${col.id}-${idx}`" class="fc-features__row">
              <input v-model="col.features[idx]" class="admin-input admin-input--sm" />
              <button
                type="button"
                class="fc-features__remove"
                aria-label="Remove"
                @click="removeEditFeatureLine(col, idx)"
              >
                <Trash2 :size="14" />
              </button>
            </li>
          </ul>
          <button type="button" class="admin-btn admin-btn--ghost fc-features__add" @click="addEditFeatureLine(col)">
            <Plus :size="15" />
            Add line
          </button>
          <div class="fc-item__actions">
            <button type="button" class="admin-btn" :disabled="busy" @click="saveCollection(col)">
              <Save :size="15" />
              Save
            </button>
            <button type="button" class="admin-btn admin-btn--ghost fc-item__delete" :disabled="busy" @click="removeCollection(col)">
              <Trash2 :size="15" />
              Delete
            </button>
          </div>
        </div>
      </article>
    </div>
  </div>
</template>

<style scoped>
.fc-new {
  margin-bottom: 1.25rem;
}

.fc-new__grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(12rem, 1fr));
  gap: 1rem;
  margin-bottom: 0.75rem;
}

.fc-list {
  display: grid;
  gap: 0.75rem;
}

.fc-item {
  padding: 0;
  overflow: hidden;
}

.fc-item__head {
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1rem 1.15rem;
  border: none;
  background: transparent;
  text-align: left;
  cursor: pointer;
}

.fc-item__head:hover {
  background: #f8fafc;
}

.fc-item__title {
  margin: 0 0 0.15rem;
  font-size: 0.9375rem;
  font-weight: 700;
}

.fc-item__toggle {
  flex-shrink: 0;
  font-size: 1.25rem;
  font-weight: 300;
  color: var(--admin-muted);
  line-height: 1;
}

.fc-item__body {
  padding: 0 1.15rem 1.15rem;
  border-top: 1px solid var(--admin-border);
}

.fc-item__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
  margin-top: 1rem;
}

.fc-item__delete {
  color: #b8453d;
}

.fc-features {
  margin: 0.5rem 0 0;
  padding: 0;
  list-style: none;
  display: grid;
  gap: 0.45rem;
}

.fc-features__row {
  display: flex;
  gap: 0.45rem;
  align-items: center;
}

.fc-features__row input {
  flex: 1;
}

.fc-features__remove {
  display: grid;
  place-items: center;
  flex-shrink: 0;
  width: 2.35rem;
  height: 2.35rem;
  border: 1.5px solid var(--admin-border);
  border-radius: 10px;
  background: #fff;
  color: var(--admin-muted);
  cursor: pointer;
}

.fc-features__remove:hover {
  color: var(--admin-danger);
  border-color: color-mix(in srgb, var(--admin-danger) 35%, var(--admin-border));
  background: #fef2f2;
}

.fc-features__add {
  margin-top: 0.55rem;
}

.admin-success {
  margin: 0 0 1rem;
  padding: 0.65rem 0.85rem;
  border-radius: 8px;
  background: #ecfdf5;
  color: #065f46;
  font-size: 0.8125rem;
  font-weight: 600;
}

.admin-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.65rem;
  padding: 2rem;
  text-align: center;
  color: var(--admin-muted);
}
</style>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  adminDeleteSubcategory,
  adminGetCategory,
  adminImageSrc,
  adminListSubcategories,
  adminSaveCategory,
  adminSaveSubcategory,
  adminUploadImage,
  type AdminSubcategory,
} from '@/admin/lib/adminApi'

const route = useRoute()
const router = useRouter()
const isNew = computed(() => route.name === 'admin-category-new')

const form = ref({
  slug: '',
  name: '',
  description: '',
  keywords: '',
  imagePath: '',
  sortOrder: 0,
  isActive: true,
})
const subcategories = ref<AdminSubcategory[]>([])
const newSub = ref({ name: '', slug: '', sortOrder: 0 })
const previewUrl = ref('')
const error = ref('')
const busy = ref(false)

async function loadSubs() {
  if (isNew.value || !form.value.slug) return
  const res = await adminListSubcategories(form.value.slug)
  subcategories.value = res.items
}

async function load() {
  if (isNew.value) return
  const res = await adminGetCategory(Number(route.params.id))
  const c = res.category
  form.value = {
    slug: c.slug,
    name: c.name,
    description: c.description,
    keywords: c.keywords,
    imagePath: c.imagePath,
    sortOrder: c.sortOrder,
    isActive: c.isActive,
  }
  if (c.imagePath) previewUrl.value = adminImageSrc(c.imagePath)
  await loadSubs()
}

async function onImage(e: Event) {
  const file = (e.target as HTMLInputElement).files?.[0]
  if (!file) return
  const res = await adminUploadImage(file, 'banners', 'site')
  if (res.path) {
    form.value.imagePath = res.path
    previewUrl.value = res.url ?? adminImageSrc(res.path)
  }
}

async function save() {
  busy.value = true
  error.value = ''
  try {
    const res = await adminSaveCategory({ ...form.value }, isNew.value ? undefined : Number(route.params.id))
    if (!res.ok) {
      error.value = res.message ?? 'Save failed'
      return
    }
    await router.push({ name: 'admin-categories' })
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Save failed'
  } finally {
    busy.value = false
  }
}

async function addSubcategory() {
  if (!newSub.value.name.trim()) return
  error.value = ''
  const res = await adminSaveSubcategory({
    categorySlug: form.value.slug,
    name: newSub.value.name.trim(),
    slug: newSub.value.slug.trim() || undefined,
    sortOrder: newSub.value.sortOrder,
    isActive: true,
  })
  if (!res.ok) {
    error.value = res.message ?? 'Could not add subcategory'
    return
  }
  newSub.value = { name: '', slug: '', sortOrder: 0 }
  await loadSubs()
}

async function removeSub(sub: AdminSubcategory) {
  if (!confirm(`Delete subcategory "${sub.name}"?`)) return
  const res = await adminDeleteSubcategory(sub.id)
  if (!res.ok) {
    error.value = res.message ?? 'Delete failed'
    return
  }
  await loadSubs()
}

watch(
  () => form.value.slug,
  () => {
    void loadSubs()
  },
)

onMounted(async () => {
  try {
    await load()
  } catch (e) {
    error.value = e instanceof Error ? e.message : 'Load failed'
  }
})
</script>

<template>
  <div>
    <h1 class="admin-page-title">{{ isNew ? 'New category' : 'Edit category' }}</h1>
    <div class="admin-card">
      <form class="admin-form" @submit.prevent="save">
        <div class="admin-field">
          <label>Name *</label>
          <input v-model="form.name" required />
        </div>
        <div class="admin-field">
          <label>Slug</label>
          <input v-model="form.slug" placeholder="auto-from-name" />
        </div>
        <div class="admin-field">
          <label>Keywords (comma-separated)</label>
          <input v-model="form.keywords" placeholder="bookmark, gift, reading" />
        </div>
        <div class="admin-field">
          <label>Description</label>
          <textarea v-model="form.description" />
        </div>
        <div class="admin-field">
          <label>Category image</label>
          <input type="file" accept="image/*" @change="onImage" />
          <img v-if="previewUrl" :src="previewUrl" alt="" class="admin-img-preview" style="margin-top: 0.5rem" />
        </div>
        <div class="admin-field">
          <label>Sort order</label>
          <input v-model.number="form.sortOrder" type="number" />
        </div>
        <label class="admin-check">
          <input v-model="form.isActive" type="checkbox" />
          Active on storefront
        </label>

        <section v-if="!isNew" class="subcats">
          <h2 class="subcats__title">Subcategories</h2>
          <p class="admin-meta">
            Types within {{ form.name }} — shown as filters on the shop page (e.g. Magnetic vs Classic
            bookmarks).
          </p>
          <ul v-if="subcategories.length" class="subcats__list" role="list">
            <li v-for="sub in subcategories" :key="sub.id" class="subcats__row">
              <div>
                <strong>{{ sub.name }}</strong>
                <span class="subcats__slug">{{ sub.slug }}</span>
              </div>
              <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" @click="removeSub(sub)">
                Delete
              </button>
            </li>
          </ul>
          <div class="subcats__add">
            <input v-model="newSub.name" placeholder="Subcategory name" />
            <input v-model="newSub.slug" placeholder="slug (optional)" />
            <button type="button" class="admin-btn admin-btn--ghost" @click="addSubcategory">Add</button>
          </div>
        </section>

        <p v-if="error" class="admin-error">{{ error }}</p>
        <div class="admin-actions">
          <button type="submit" class="admin-btn" :disabled="busy">Save</button>
          <button type="button" class="admin-btn admin-btn--ghost" @click="router.back()">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</template>

<style scoped>
.admin-check {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.8125rem;
  font-weight: 600;
}

.subcats {
  margin-top: 1.25rem;
  padding-top: 1.25rem;
  border-top: 1px solid var(--admin-border);
}

.subcats__title {
  margin: 0 0 0.35rem;
  font-size: 1rem;
}

.subcats__list {
  margin: 0.75rem 0;
  padding: 0;
  list-style: none;
}

.subcats__row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 0.5rem 0;
  border-bottom: 1px solid var(--admin-border);
}

.subcats__slug {
  display: block;
  font-size: 0.6875rem;
  color: var(--admin-muted);
}

.subcats__add {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 0.5rem;
}

@media (max-width: 640px) {
  .subcats__add {
    grid-template-columns: 1fr;
  }
}
</style>

<script setup lang="ts">

import {

  ArrowLeft,

  ImagePlus,

  IndianRupee,

  ListChecks,

  Package,

  Plus,

  Search,

  Star,

  Trash2,

  Type,

} from 'lucide-vue-next'

import { computed, onMounted, ref, watch } from 'vue'

import { RouterLink, useRoute, useRouter } from 'vue-router'

import AdminField from '@/admin/components/AdminField.vue'

import {

  adminGetProduct,

  adminImageSrc,

  adminListCategories,

  adminListFeatureCollections,

  adminListSubcategories,

  adminSaveProduct,

  adminUploadImage,

  type AdminFeatureCollection,

  type AdminProductImage,

} from '@/admin/lib/adminApi'



const route = useRoute()

const router = useRouter()

const isNew = computed(() => route.name === 'admin-product-new')



const categories = ref<{ slug: string; name: string }[]>([])

const subcategories = ref<{ slug: string; name: string }[]>([])

const featureCollections = ref<AdminFeatureCollection[]>([])

const importCollectionId = ref('')

const images = ref<AdminProductImage[]>([])

const form = ref({

  id: '',

  slug: '',

  name: '',

  description: '',

  features: [] as string[],

  keywords: '',

  price: 0,

  compareAt: '' as string | number,

  category: 'bookmarks',

  subcategory: '',

  sku: '',

  stockQuantity: '' as string | number,

  seoTitle: '',

  seoDescription: '',

  homeBestseller: false,

  homeSecondary: false,

  isActive: true,

  sortOrder: 0,

})

const error = ref('')

const busy = ref(false)

const uploading = ref(false)

const fileInput = ref<HTMLInputElement | null>(null)



async function loadSubcategories() {

  if (!form.value.category) {

    subcategories.value = []

    return

  }

  try {

    const res = await adminListSubcategories(form.value.category)

    subcategories.value = res.items.filter((s) => s.isActive).map((s) => ({ slug: s.slug, name: s.name }))

  } catch {

    subcategories.value = []

  }

}



async function loadCategories() {

  const res = await adminListCategories()

  categories.value = res.items.map((c) => ({ slug: c.slug, name: c.name }))

}



async function loadFeatureCollections() {

  try {

    const res = await adminListFeatureCollections()

    featureCollections.value = res.collections ?? []

  } catch {

    featureCollections.value = []

  }

}



const sortedFeatureCollections = computed(() => {

  const cat = form.value.category

  return [...featureCollections.value].sort((a, b) => {

    const aMatch = a.category === cat ? 0 : a.category ? 2 : 1

    const bMatch = b.category === cat ? 0 : b.category ? 2 : 1

    if (aMatch !== bMatch) return aMatch - bMatch

    return a.name.localeCompare(b.name)

  })

})



const suggestedCollection = computed(() =>

  sortedFeatureCollections.value.find((c) => c.category === form.value.category),

)



function importFeatures(mode: 'replace' | 'append') {

  const col = featureCollections.value.find((c) => c.id === importCollectionId.value)

  if (!col) return

  const lines = col.features.map((f) => f.trim()).filter(Boolean)

  if (mode === 'replace') {

    form.value.features = [...lines]

  } else {

    const existing = new Set(form.value.features.map((f) => f.trim()).filter(Boolean))

    for (const line of lines) {

      if (!existing.has(line)) {

        form.value.features.push(line)

        existing.add(line)

      }

    }

  }

}



async function load() {

  if (isNew.value) return

  const res = await adminGetProduct(route.params.id as string)

  const p = res.product

  form.value = {

    id: p.id,

    slug: p.slug,

    name: p.name,

    description: p.description,

    features: [...(p.features ?? [])],

    keywords: p.keywords ?? '',

    price: p.price,

    compareAt: p.compareAt ?? '',

    category: p.category,

    subcategory: p.subcategory ?? '',

    sku: p.sku ?? '',

    stockQuantity: p.stockQuantity ?? '',

    seoTitle: p.seoTitle ?? '',

    seoDescription: p.seoDescription ?? '',

    homeBestseller: p.homeBestseller,

    homeSecondary: p.homeSecondary,

    isActive: p.isActive,

    sortOrder: p.sortOrder,

  }

  images.value = (p.images ?? []).map((img) => ({ ...img }))

}



async function onFiles(e: Event) {

  const list = (e.target as HTMLInputElement).files

  if (!list?.length) return

  uploading.value = true

  error.value = ''

  try {

    for (const file of Array.from(list)) {

      const res = await adminUploadImage(file, form.value.category)

      if (res.path) {

        images.value.push({

          id: 0,

          path: res.path,

          url: res.url ?? adminImageSrc(res.path),

          sortOrder: images.value.length,

          isPrimary: images.value.length === 0,

        })

      }

    }

  } catch (err) {

    error.value = err instanceof Error ? err.message : 'Upload failed'

  } finally {

    uploading.value = false

  }

}



function setPrimary(index: number) {

  images.value = images.value.map((img, i) => ({ ...img, isPrimary: i === index }))

}



function removeImage(index: number) {

  images.value = images.value.filter((_, i) => i !== index)

  if (images.value.length && !images.value.some((i) => i.isPrimary)) {

    images.value[0].isPrimary = true

  }

}



function addFeature() {

  form.value.features.push('')

}



function removeFeature(index: number) {

  form.value.features = form.value.features.filter((_, i) => i !== index)

}



async function save() {

  if (images.value.length === 0) {

    error.value = 'Add at least one product image.'

    return

  }

  busy.value = true

  error.value = ''

  const payload = {

    ...form.value,

    features: form.value.features.map((f) => f.trim()).filter(Boolean),

    compareAt: form.value.compareAt === '' ? null : Number(form.value.compareAt),

    stockQuantity: form.value.stockQuantity === '' ? null : Number(form.value.stockQuantity),

    images: images.value.map((img, i) => ({

      path: img.path,

      sortOrder: i,

      isPrimary: img.isPrimary,

    })),

  }

  try {

    const res = await adminSaveProduct(payload, isNew.value ? undefined : form.value.id)

    if (!res.ok) {

      error.value = res.message ?? 'Save failed'

      return

    }

    await router.push({ name: 'admin-products' })

  } catch (e) {

    error.value = e instanceof Error ? e.message : 'Save failed'

  } finally {

    busy.value = false

  }

}



onMounted(async () => {

  try {

    await Promise.all([loadCategories(), loadFeatureCollections()])

    await load()

    await loadSubcategories()

    if (isNew.value && suggestedCollection.value) {

      importCollectionId.value = suggestedCollection.value.id

    }

  } catch (e) {

    error.value = e instanceof Error ? e.message : 'Load failed'

  }

})



watch(

  () => form.value.category,

  () => {

    form.value.subcategory = ''

    void loadSubcategories()

    const match = featureCollections.value.find((c) => c.category === form.value.category)

    if (match) importCollectionId.value = match.id

  },

)

</script>



<template>

  <div class="admin-page">

    <header class="admin-page-head admin-page-head--form">

      <div>

        <button type="button" class="admin-page-head__back" @click="router.back()">

          <ArrowLeft :size="16" />

          Back to products

        </button>

        <h1 class="admin-page-title">{{ isNew ? 'New product' : 'Edit product' }}</h1>

        <p class="admin-meta">

          {{ isNew ? 'Create a listing for your storefront.' : `Editing ${form.name || 'product'}.` }}

        </p>

      </div>

    </header>



    <form class="admin-form admin-form--wide admin-form-layout admin-form-layout--split" @submit.prevent="save">

      <div class="admin-form-main">

        <!-- Basics -->

        <section class="admin-form-section">

          <div class="admin-form-section__head">

            <h2 class="admin-form-section__title">

              <Package :size="17" style="vertical-align: -3px; margin-right: 0.35rem" />

              Product details

            </h2>

            <p class="admin-form-section__desc">Name, category, and identifiers shoppers and staff will see.</p>

          </div>

          <div class="admin-form-section__body">

            <div class="admin-form-grid">

              <AdminField v-if="isNew" label="Product ID" hint="Leave blank to auto-generate" :span="4">

                <input v-model="form.id" class="admin-input" placeholder="Auto-generated" />

              </AdminField>

              <AdminField label="Name" required :span="isNew ? 8 : 12">

                <input v-model="form.name" class="admin-input" required placeholder="Magnetic bookmark — Floral" />

              </AdminField>

              <AdminField label="URL slug" hint="Auto from name if empty" :span="6">

                <input v-model="form.slug" class="admin-input" placeholder="magnetic-bookmark-floral" />

              </AdminField>

              <AdminField label="Category" required :span="3">

                <select v-model="form.category" class="admin-select">

                  <option v-for="c in categories" :key="c.slug" :value="c.slug">{{ c.name }}</option>

                </select>

              </AdminField>

              <AdminField label="Subcategory" :span="3">

                <select v-model="form.subcategory" class="admin-select">

                  <option value="">— None —</option>

                  <option v-for="s in subcategories" :key="s.slug" :value="s.slug">{{ s.name }}</option>

                </select>

              </AdminField>

              <AdminField label="SKU" :span="4">

                <input v-model="form.sku" class="admin-input" placeholder="BMK-FLR-001" />

              </AdminField>

              <AdminField label="Sort order" :span="4">

                <input v-model.number="form.sortOrder" type="number" class="admin-input" />

              </AdminField>

            </div>

          </div>

        </section>



        <!-- Pricing -->

        <section class="admin-form-section">

          <div class="admin-form-section__head">

            <h2 class="admin-form-section__title">

              <IndianRupee :size="17" style="vertical-align: -3px; margin-right: 0.35rem" />

              Pricing & inventory

            </h2>

            <p class="admin-form-section__desc">Sale price, optional compare-at, and stock on hand.</p>

          </div>

          <div class="admin-form-section__body">

            <div class="admin-form-grid">

              <AdminField label="Price" required :span="4">

                <div class="admin-input-prefix">

                  <span class="admin-input-prefix__symbol">₹</span>

                  <input

                    v-model.number="form.price"

                    type="number"

                    min="0"

                    step="0.01"

                    class="admin-input"

                    required

                  />

                </div>

              </AdminField>

              <AdminField label="Compare at" hint="Shows as struck-through MRP" :span="4">

                <div class="admin-input-prefix">

                  <span class="admin-input-prefix__symbol">₹</span>

                  <input v-model="form.compareAt" type="number" min="0" step="0.01" class="admin-input" />

                </div>

              </AdminField>

              <AdminField label="Stock quantity" :span="4">

                <input v-model="form.stockQuantity" type="number" min="0" class="admin-input" placeholder="Unlimited" />

              </AdminField>

            </div>

          </div>

        </section>



        <!-- Content -->

        <section class="admin-form-section">

          <div class="admin-form-section__head">

            <h2 class="admin-form-section__title">

              <Type :size="17" style="vertical-align: -3px; margin-right: 0.35rem" />

              Description & features

            </h2>

            <p class="admin-form-section__desc">Product copy and bullet points on the product page.</p>

          </div>

          <div class="admin-form-section__body">

            <AdminField label="Description" :span="12">

              <textarea

                v-model="form.description"

                class="admin-textarea admin-input"

                rows="4"

                placeholder="Tell shoppers what makes this product special…"

              />

            </AdminField>



            <AdminField

              label="Features"

              hint="Leave empty to use category defaults on the storefront."

              :span="12"

            >

              <div v-if="featureCollections.length" class="admin-features-import">

                <select v-model="importCollectionId" class="admin-select admin-features-import__select">

                  <option value="">Import from collection…</option>

                  <option v-for="col in sortedFeatureCollections" :key="col.id" :value="col.id">

                    {{ col.name }}{{ col.category ? ` · ${col.category}` : '' }} ({{ col.features.length }})

                  </option>

                </select>

                <button

                  type="button"

                  class="admin-btn admin-btn--ghost admin-btn--sm"

                  :disabled="!importCollectionId"

                  @click="importFeatures('replace')"

                >

                  Replace

                </button>

                <button

                  type="button"

                  class="admin-btn admin-btn--ghost admin-btn--sm"

                  :disabled="!importCollectionId"

                  @click="importFeatures('append')"

                >

                  Append

                </button>

                <RouterLink :to="{ name: 'admin-feature-collections' }" class="admin-features-import__link">

                  <ListChecks :size="14" />

                  Manage

                </RouterLink>

              </div>

              <ul v-if="form.features.length" class="admin-features" role="list">

                <li v-for="(_, idx) in form.features" :key="idx" class="admin-features__row">

                  <input

                    v-model="form.features[idx]"

                    class="admin-input admin-input--sm"

                    placeholder="e.g. Fold-over magnetic clip stays put"

                  />

                  <button

                    type="button"

                    class="admin-features__remove"

                    aria-label="Remove feature"

                    @click="removeFeature(idx)"

                  >

                    <Trash2 :size="14" />

                  </button>

                </li>

              </ul>

              <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm admin-features__add" @click="addFeature">

                <Plus :size="15" />

                Add feature

              </button>

            </AdminField>

          </div>

        </section>



        <!-- SEO -->

        <section class="admin-form-section">

          <div class="admin-form-section__head">

            <h2 class="admin-form-section__title">

              <Search :size="17" style="vertical-align: -3px; margin-right: 0.35rem" />

              Search & SEO

            </h2>

            <p class="admin-form-section__desc">Keywords and meta fields for shop search and Google.</p>

          </div>

          <div class="admin-form-section__body">

            <AdminField label="Keywords" hint="Comma-separated" :span="12">

              <input v-model="form.keywords" class="admin-input" placeholder="magnetic bookmark, gift, reading" />

            </AdminField>

            <div class="admin-form-grid">

              <AdminField label="SEO title" :span="6">

                <input v-model="form.seoTitle" class="admin-input" />

              </AdminField>

              <AdminField label="SEO description" :span="6">

                <input v-model="form.seoDescription" class="admin-input" />

              </AdminField>

            </div>

          </div>

        </section>



        <!-- Images -->

        <section class="admin-form-section">

          <div class="admin-form-section__head">

            <h2 class="admin-form-section__title">

              <ImagePlus :size="17" style="vertical-align: -3px; margin-right: 0.35rem" />

              Product images

            </h2>

            <p class="admin-form-section__desc">Upload photos — star the main thumbnail for shop listings.</p>

          </div>

          <div class="admin-form-section__body">

            <input ref="fileInput" type="file" accept="image/*" multiple hidden @change="onFiles" />

            <button

              type="button"

              class="admin-btn admin-btn--ghost"

              :disabled="uploading"

              @click="fileInput?.click()"

            >

              <ImagePlus :size="15" />

              {{ uploading ? 'Uploading…' : 'Upload photos' }}

            </button>

            <p class="admin-upload-hint">JPEG, PNG, or GIF are optimized and saved as WebP automatically.</p>

            <ul v-if="images.length" class="admin-gallery" role="list">

              <li v-for="(img, idx) in images" :key="img.path + idx" class="admin-gallery__item">

                <img :src="img.url || adminImageSrc(img.path)" alt="" />

                <div class="admin-gallery__actions">

                  <button

                    type="button"

                    class="admin-gallery__btn"

                    :class="{ 'admin-gallery__btn--on': img.isPrimary }"

                    :title="img.isPrimary ? 'Primary image' : 'Set as primary'"

                    @click="setPrimary(idx)"

                  >

                    <Star :size="14" />

                  </button>

                  <button type="button" class="admin-gallery__btn admin-gallery__btn--del" @click="removeImage(idx)">

                    <Trash2 :size="14" />

                  </button>

                </div>

                <span v-if="img.isPrimary" class="admin-gallery__badge">Main</span>

              </li>

              <li

                class="admin-gallery__add"

                role="button"

                tabindex="0"

                @click="fileInput?.click()"

                @keydown.enter="fileInput?.click()"

              >

                <ImagePlus :size="24" aria-hidden="true" />

                <span>Add more</span>

              </li>

            </ul>

          </div>

        </section>

      </div>



      <!-- Sidebar -->

      <aside class="admin-form-aside admin-form-aside--sticky">

        <section class="admin-form-section">

          <div class="admin-form-section__head">

            <h2 class="admin-form-section__title">Visibility</h2>

            <p class="admin-form-section__desc">Control where this product appears.</p>

          </div>

          <div class="admin-check-panel">

            <label class="admin-check">

              <input v-model="form.isActive" type="checkbox" />

              Active on storefront

            </label>

            <label class="admin-check">

              <input v-model="form.homeBestseller" type="checkbox" />

              Home bestseller

            </label>

            <label class="admin-check">

              <input v-model="form.homeSecondary" type="checkbox" />

              Home secondary row

            </label>

          </div>

        </section>



        <section class="admin-form-section admin-form-footer--sticky">

          <p v-if="error" class="admin-error">{{ error }}</p>

          <div class="admin-form-footer">

            <button type="submit" class="admin-btn admin-btn--block" :disabled="busy">

              {{ busy ? 'Saving…' : 'Save product' }}

            </button>

            <button type="button" class="admin-btn admin-btn--ghost admin-btn--block" @click="router.back()">

              Cancel

            </button>

          </div>

        </section>

      </aside>

    </form>

  </div>

</template>



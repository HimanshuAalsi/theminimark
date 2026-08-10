<script setup lang="ts">
import { ExternalLink, ImagePlus, LayoutTemplate, Layers, RotateCcw, Save, Settings2 } from 'lucide-vue-next'
import { onMounted, ref } from 'vue'
import HomePageBuilder from '@/admin/components/HomePageBuilder.vue'
import HomeContentPoolEditors from '@/admin/components/homeContent/HomeContentPoolEditors.vue'
import {
  adminGetHomePage,
  adminImageSrc,
  adminSaveHomePage,
  adminUploadImage,
} from '@/admin/lib/adminApi'
import { ensureLayout } from '@/lib/homePageLayout'
import logoFallback from '@/assets/main-logo.webp'
import type { HomePageConfig } from '@/types/homePage'

const form = ref<HomePageConfig | null>(null)
const loading = ref(true)
const saving = ref(false)
const uploadingKey = ref<string | null>(null)
const message = ref('')
const error = ref('')
const tab = ref<'builder' | 'content' | 'global'>('builder')

function onContentUpdate(next: HomePageConfig) {
  if (form.value) form.value = next
}

function imgSrc(path: string): string {
  if (!path) return ''
  return adminImageSrc(path)
}

function logoPreview(): string {
  const path = form.value?.logoImage?.trim()
  if (path) return imgSrc(path)
  return logoFallback
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const res = await adminGetHomePage()
    const data = structuredClone(res.homePage)
    data.layout = ensureLayout(data.layout)
    form.value = data
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Could not load landing page'
  } finally {
    loading.value = false
  }
}

async function save() {
  if (!form.value) return
  saving.value = true
  message.value = ''
  error.value = ''
  try {
    form.value.layout = ensureLayout(form.value.layout)
    const res = await adminSaveHomePage(form.value)
    if (!res.ok) {
      error.value = res.message ?? 'Save failed'
      return
    }
    if (res.homePage) {
      const data = structuredClone(res.homePage)
      data.layout = ensureLayout(data.layout)
      form.value = data
    }
    message.value = 'Landing page saved — layout and content are live.'
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Save failed'
  } finally {
    saving.value = false
  }
}

async function onImagePick(key: string, e: Event, folder = 'home') {
  const input = e.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (!file || !form.value) return

  uploadingKey.value = key
  error.value = ''
  try {
    const res = await adminUploadImage(file, folder, 'site')
    if (!res.path) throw new Error('Upload failed')
    applyImagePath(key, res.path)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Upload failed'
  } finally {
    uploadingKey.value = null
  }
}

function applyImagePath(key: string, path: string) {
  if (!form.value) return
  if (key === 'logo') {
    form.value.logoImage = path
    return
  }
  const heroMatch = /^hero-(\d+)$/.exec(key)
  if (heroMatch) {
    const i = Number(heroMatch[1])
    if (form.value.heroSlides[i]) form.value.heroSlides[i].image = path
    return
  }
  const catMatch = /^cat-(\d+)$/.exec(key)
  if (catMatch) {
    const i = Number(catMatch[1])
    if (form.value.categoryStrip[i]) form.value.categoryStrip[i].image = path
    return
  }
  const persMatch = /^pers-(\d+)$/.exec(key)
  if (persMatch) {
    const i = Number(persMatch[1])
    if (form.value.personaliseCards[i]) form.value.personaliseCards[i].image = path
  }
}

function resetLogo() {
  if (form.value) form.value.logoImage = ''
}

function resetLayout() {
  if (!form.value || !confirm('Reset layout to default structure? Content pools stay unchanged.')) return
  form.value.layout = ensureLayout(undefined)
}

onMounted(load)
</script>

<template>
  <div class="hp-admin">
    <header class="admin-page-head">
      <div>
        <h1 class="admin-page-title">Landing page builder</h1>
        <p class="admin-meta">
          Drag sections, build rows &amp; columns, and drop product blocks — hero, categories, custom grids, and more.
        </p>
      </div>
      <div class="hp-head-actions">
        <a href="/" target="_blank" rel="noopener" class="admin-btn admin-btn--ghost admin-btn--icon" title="View site">
          <ExternalLink :size="15" />
        </a>
        <button type="button" class="admin-btn" :disabled="saving || loading" @click="save">
          <Save :size="15" />
          {{ saving ? 'Saving…' : 'Save' }}
        </button>
      </div>
    </header>

    <div class="hp-tabs">
      <button type="button" class="hp-tab" :class="{ 'hp-tab--active': tab === 'builder' }" @click="tab = 'builder'">
        <LayoutTemplate :size="15" /> Layout
      </button>
      <button type="button" class="hp-tab" :class="{ 'hp-tab--active': tab === 'content' }" @click="tab = 'content'">
        <Layers :size="15" /> Page content
      </button>
      <button type="button" class="hp-tab" :class="{ 'hp-tab--active': tab === 'global' }" @click="tab = 'global'">
        <Settings2 :size="15" /> Global
      </button>
    </div>

    <p v-if="message" class="hp-success">{{ message }}</p>
    <p v-if="error" class="admin-error">{{ error }}</p>

    <div v-if="loading" class="admin-card admin-card--pad">Loading…</div>

    <template v-else-if="form">
      <div v-show="tab === 'builder'" class="hp-builder-wrap">
        <div class="hp-builder-toolbar">
          <button type="button" class="admin-btn admin-btn--ghost admin-btn--sm" @click="resetLayout">Reset layout to default</button>
        </div>
        <HomePageBuilder v-if="form.layout" v-model="form.layout" :content="form" @update:content="onContentUpdate" @image-pick="onImagePick" :uploading-key="uploadingKey" />
      </div>

      <div v-show="tab === 'global'" class="hp-panels">
        <section class="admin-card admin-card--pad hp-section">
          <h2 class="hp-section__title">Announcement bar</h2>
          <textarea v-model="form.announcement" class="admin-input" rows="2" maxlength="500" />
        </section>
        <section class="admin-card admin-card--pad hp-section">
          <div class="hp-section__head">
            <h2 class="hp-section__title">Site logo</h2>
            <button v-if="form.logoImage" type="button" class="admin-btn admin-btn--ghost admin-btn--sm" @click="resetLogo">
              <RotateCcw :size="14" /> Default
            </button>
          </div>
          <div class="hp-logo">
            <img :src="logoPreview()" alt="Logo" class="hp-logo__img" />
            <label class="hp-upload-btn">
              <ImagePlus :size="16" />
              {{ uploadingKey === 'logo' ? 'Uploading…' : 'Replace' }}
              <input type="file" accept="image/*" hidden @change="onImagePick('logo', $event)" />
            </label>
          </div>
        </section>
      </div>

      <div v-show="tab === 'content'" class="hp-panels">
        <HomeContentPoolEditors
          v-if="form"
          :model-value="form"
          :uploading-key="uploadingKey"
          @update:model-value="onContentUpdate"
          @image-pick="onImagePick"
        />
      </div>
    </template>
  </div>
</template>

<style scoped>
.hp-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
  margin-bottom: 0.85rem;
}

.hp-tab {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.45rem 0.85rem;
  border: 1px solid var(--admin-border);
  border-radius: 999px;
  background: #fff;
  font-family: inherit;
  font-size: 0.75rem;
  font-weight: 700;
  color: var(--admin-muted);
  cursor: pointer;
}

.hp-tab--active {
  border-color: var(--admin-accent);
  background: var(--admin-accent-light);
  color: var(--admin-primary);
}

.hp-builder-toolbar {
  margin-bottom: 0.65rem;
}

.hp-success {
  margin: 0 0 0.75rem;
  padding: 0.55rem 0.75rem;
  border-radius: 8px;
  background: #ecfdf5;
  color: #047857;
  font-size: 0.8125rem;
  font-weight: 600;
}

.hp-head-actions {
  display: flex;
  gap: 0.5rem;
}

.hp-section {
  margin-bottom: 0.85rem;
}

.hp-section__head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.65rem;
}

.hp-section__title {
  margin: 0;
  font-size: 0.9375rem;
  font-weight: 700;
}

.hp-logo {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.hp-logo__img {
  height: 2.5rem;
  object-fit: contain;
}

.hp-upload-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0.45rem 0.75rem;
  border: 1px solid var(--admin-border);
  border-radius: 8px;
  font-size: 0.75rem;
  font-weight: 600;
  cursor: pointer;
}

.hp-upload-btn--sm {
  padding: 0.3rem 0.5rem;
  font-size: 0.6875rem;
}

.hp-mini-grid {
  display: grid;
  gap: 0.65rem;
  grid-template-columns: repeat(2, 1fr);
}

.hp-mini-grid--4 {
  grid-template-columns: repeat(4, 1fr);
}

.hp-mini-grid--6 {
  grid-template-columns: repeat(3, 1fr);
}

@media (max-width: 900px) {
  .hp-mini-grid--4,
  .hp-mini-grid--6 {
    grid-template-columns: repeat(2, 1fr);
  }
}

.hp-mini {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  font-size: 0.6875rem;
}

.hp-mini img {
  width: 100%;
  aspect-ratio: 4/3;
  object-fit: cover;
  border-radius: 8px;
}
</style>

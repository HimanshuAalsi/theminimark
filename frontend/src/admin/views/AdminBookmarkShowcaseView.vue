<script setup lang="ts">
import { Bookmark, Download, ImageDown, Loader2, RefreshCw, Upload } from 'lucide-vue-next'
import { computed, ref, watch } from 'vue'
import {
  BOOKMARK_VARIATIONS,
  downloadAllShowcases,
  downloadShowcase,
  generateAllShowcases,
  loadImageFromFile,
  type ShowcaseResult,
} from '@/admin/lib/bookmarkShowcase'

const dragOver = ref(false)
const sourceName = ref('')
const sourcePreview = ref('')
const sourceImage = ref<HTMLImageElement | null>(null)
const cornerRadiusPct = ref(8)
const outputSize = ref(1600)
const generating = ref(false)
const results = ref<ShowcaseResult[]>([])
const error = ref('')

const hasImage = computed(() => !!sourceImage.value)
const canGenerate = computed(() => hasImage.value && !generating.value)

const acceptTypes = 'image/jpeg,image/png,image/webp,image/gif'

async function processFile(file: File | undefined) {
  if (!file || !file.type.startsWith('image/')) {
    error.value = 'Please drop a JPG, PNG, or WebP image.'
    return
  }
  error.value = ''
  generating.value = true
  try {
    const img = await loadImageFromFile(file)
    sourceImage.value = img
    sourceName.value = file.name
    sourcePreview.value = URL.createObjectURL(file)
    regenerate()
  } catch {
    error.value = 'Could not read that image.'
  } finally {
    generating.value = false
  }
}

function onFileInput(e: Event) {
  const input = e.target as HTMLInputElement
  void processFile(input.files?.[0])
  input.value = ''
}

function onDrop(e: DragEvent) {
  dragOver.value = false
  e.preventDefault()
  void processFile(e.dataTransfer?.files?.[0])
}

function onDragOver(e: DragEvent) {
  e.preventDefault()
  dragOver.value = true
}

function onDragLeave() {
  dragOver.value = false
}

function regenerate() {
  if (!sourceImage.value) return
  generating.value = true
  error.value = ''
  try {
    results.value = generateAllShowcases(sourceImage.value, {
      cornerRadiusPct: cornerRadiusPct.value,
      outputSize: outputSize.value,
    })
  } catch {
    error.value = 'Could not generate mockups.'
    results.value = []
  } finally {
    generating.value = false
  }
}

function clearAll() {
  sourceImage.value = null
  sourceName.value = ''
  if (sourcePreview.value) URL.revokeObjectURL(sourcePreview.value)
  sourcePreview.value = ''
  results.value = []
  error.value = ''
}

watch([cornerRadiusPct, outputSize], () => {
  if (sourceImage.value) regenerate()
})
</script>

<template>
  <div class="admin-page">
    <div class="admin-page-head">
      <div>
        <h1 class="admin-page-title">Bookmark showcase</h1>
        <p class="admin-meta">
          Drop your flat bookmark artwork and get 5 product-ready mockups with rounded bottom corners —
          no Photoshop needed.
        </p>
      </div>
    </div>

    <div class="admin-card bmk-upload-card">
      <h2 class="admin-card-title">Upload flat bookmark</h2>
      <p class="admin-meta">
        Use a high-resolution PNG or JPG (portrait, ~2×6 in ratio works best). Top stays square; bottom
        corners are rounded automatically.
      </p>

      <div
        class="bmk-dropzone"
        :class="{ 'bmk-dropzone--active': dragOver, 'bmk-dropzone--filled': hasImage }"
        @dragover="onDragOver"
        @dragleave="onDragLeave"
        @drop="onDrop"
      >
        <input
          id="bmk-file"
          type="file"
          :accept="acceptTypes"
          hidden
          @change="onFileInput"
        />

        <template v-if="hasImage && sourcePreview">
          <img :src="sourcePreview" alt="Uploaded bookmark" class="bmk-dropzone__preview" />
          <p class="bmk-dropzone__name">{{ sourceName }}</p>
        </template>
        <template v-else>
          <Upload :size="32" class="bmk-dropzone__icon" />
          <p class="bmk-dropzone__label">Drag &amp; drop your bookmark image here</p>
          <p class="admin-meta">or</p>
        </template>

        <label for="bmk-file" class="admin-btn admin-btn--ghost bmk-dropzone__btn">
          <Upload :size="16" />
          Choose file
        </label>
      </div>

      <div v-if="hasImage" class="bmk-controls">
        <div class="admin-field">
          <label>Bottom corner radius ({{ cornerRadiusPct }}% of width)</label>
          <input v-model.number="cornerRadiusPct" type="range" min="2" max="18" step="1" />
        </div>
        <div class="admin-field">
          <label>Output size (px)</label>
          <select v-model.number="outputSize" class="admin-input">
            <option :value="1200">1200 × 1200</option>
            <option :value="1600">1600 × 1600</option>
            <option :value="2000">2000 × 2000</option>
          </select>
        </div>
        <div class="bmk-controls__actions">
          <button type="button" class="admin-btn" :disabled="!canGenerate" @click="regenerate">
            <RefreshCw :size="16" />
            Regenerate
          </button>
          <button type="button" class="admin-btn admin-btn--ghost" @click="clearAll">Clear</button>
        </div>
      </div>

      <p v-if="error" class="admin-error">{{ error }}</p>
    </div>

    <div v-if="generating && !results.length" class="bmk-loading admin-card">
      <Loader2 :size="24" class="bmk-spin" />
      <span>Generating mockups…</span>
    </div>

    <template v-if="results.length">
      <div class="admin-toolbar bmk-toolbar">
        <p class="admin-meta">
          <strong>{{ results.length }}</strong> variations · {{ outputSize }}px square · JPG
        </p>
        <button type="button" class="admin-btn" @click="downloadAllShowcases(results)">
          <ImageDown :size="16" />
          Download all
        </button>
      </div>

      <div class="bmk-grid">
        <article v-for="(item, i) in results" :key="item.id" class="bmk-card">
          <div class="bmk-card__frame">
            <span class="bmk-card__badge">{{ i + 1 }}</span>
            <img :src="item.dataUrl" :alt="item.name" loading="lazy" />
          </div>
          <div class="bmk-card__body">
            <h3 class="bmk-card__title">
              <Bookmark :size="15" />
              {{ item.name }}
            </h3>
            <p class="bmk-card__desc">{{ item.description }}</p>
            <button type="button" class="admin-btn bmk-card__dl" @click="downloadShowcase(item)">
              <Download :size="15" />
              Download JPG
            </button>
          </div>
        </article>
      </div>
    </template>

    <div v-else-if="!hasImage" class="admin-card bmk-variations-info">
      <h2 class="admin-card-title">5 photorealistic styles</h2>
      <div class="bmk-style-grid">
        <div v-for="(v, i) in BOOKMARK_VARIATIONS" :key="v.id" class="bmk-style-chip">
          <span class="bmk-style-chip__num">{{ i + 1 }}</span>
          <div>
            <strong>{{ v.name }}</strong>
            <p>{{ v.description }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.bmk-upload-card {
  margin-bottom: 1.25rem;
}

.bmk-dropzone {
  margin-top: 1rem;
  border: 2px dashed var(--admin-border);
  border-radius: 14px;
  padding: 2rem 1.25rem;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  text-align: center;
  background: #f8fafc;
  transition: border-color 0.15s, background 0.15s;
}

.bmk-dropzone--active {
  border-color: var(--admin-accent);
  background: var(--admin-accent-light);
}

.bmk-dropzone--filled {
  padding: 1.25rem;
}

.bmk-dropzone__icon {
  color: var(--admin-muted);
}

.bmk-dropzone__label {
  margin: 0;
  font-weight: 600;
}

.bmk-dropzone__preview {
  max-height: 220px;
  max-width: min(100%, 140px);
  border-radius: 0 0 12px 12px;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.15);
  object-fit: contain;
}

.bmk-dropzone__name {
  margin: 0.5rem 0 0;
  font-size: 0.8125rem;
  color: var(--admin-muted);
  word-break: break-all;
}

.bmk-dropzone__btn {
  margin-top: 0.35rem;
  cursor: pointer;
}

.bmk-controls {
  margin-top: 1.25rem;
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(auto-fit, minmax(14rem, 1fr));
  align-items: end;
}

.bmk-controls__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.bmk-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  padding: 2rem;
  margin-bottom: 1.25rem;
}

.bmk-spin {
  animation: bmk-spin 0.8s linear infinite;
}

@keyframes bmk-spin {
  to {
    transform: rotate(360deg);
  }
}

.bmk-toolbar {
  margin-bottom: 1rem;
}

.bmk-grid {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: repeat(auto-fill, minmax(18rem, 1fr));
}

.bmk-card {
  display: flex;
  flex-direction: column;
  border-radius: 16px;
  overflow: hidden;
  background: var(--admin-surface, #fff);
  border: 1px solid var(--admin-border);
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06), 0 8px 24px rgba(15, 23, 42, 0.04);
  transition: box-shadow 0.2s ease, transform 0.2s ease;
}

.bmk-card:hover {
  box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08), 0 16px 40px rgba(15, 23, 42, 0.08);
  transform: translateY(-2px);
}

.bmk-card__frame {
  position: relative;
  aspect-ratio: 1;
  padding: 0.65rem;
  background: linear-gradient(145deg, #f1f5f9 0%, #e2e8f0 100%);
}

.bmk-card__badge {
  position: absolute;
  top: 0.85rem;
  left: 0.85rem;
  z-index: 1;
  display: grid;
  place-items: center;
  width: 1.5rem;
  height: 1.5rem;
  border-radius: 999px;
  background: rgba(15, 23, 42, 0.72);
  color: #fff;
  font-size: 0.6875rem;
  font-weight: 700;
  backdrop-filter: blur(4px);
}

.bmk-card__frame img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  border-radius: 10px;
  box-shadow: 0 4px 20px rgba(15, 23, 42, 0.12);
}

.bmk-card__body {
  padding: 1rem 1.15rem 1.2rem;
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
  flex: 1;
}

.bmk-card__title {
  margin: 0;
  font-size: 0.9375rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  gap: 0.4rem;
  color: var(--admin-ink, #0f172a);
}

.bmk-card__desc {
  margin: 0;
  font-size: 0.8125rem;
  line-height: 1.5;
  color: var(--admin-muted);
  flex: 1;
}

.bmk-card__dl {
  margin-top: 0.5rem;
  align-self: flex-start;
  font-size: 0.8125rem;
}

.bmk-variations-info {
  margin-top: 0.5rem;
}

.bmk-style-grid {
  display: grid;
  gap: 0.75rem;
  margin-top: 1rem;
  grid-template-columns: repeat(auto-fill, minmax(15rem, 1fr));
}

.bmk-style-chip {
  display: flex;
  gap: 0.75rem;
  align-items: flex-start;
  padding: 0.85rem 1rem;
  border-radius: 12px;
  background: linear-gradient(135deg, #f8fafc, #f1f5f9);
  border: 1px solid var(--admin-border);
}

.bmk-style-chip__num {
  flex-shrink: 0;
  display: grid;
  place-items: center;
  width: 1.65rem;
  height: 1.65rem;
  border-radius: 8px;
  background: var(--admin-accent, #2d5c52);
  color: #fff;
  font-size: 0.75rem;
  font-weight: 800;
}

.bmk-style-chip strong {
  display: block;
  font-size: 0.875rem;
  margin-bottom: 0.15rem;
}

.bmk-style-chip p {
  margin: 0;
  font-size: 0.75rem;
  line-height: 1.45;
  color: var(--admin-muted);
}
</style>

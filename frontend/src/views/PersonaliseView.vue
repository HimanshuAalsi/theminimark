<script setup lang="ts">
import {
  ImagePlus,
  Loader2,
  RotateCcw,
  ShoppingBag,
  Sparkles,
  Upload,
  ZoomIn,
} from 'lucide-vue-next'
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import CustomProductPreview from '@/components/personalise/CustomProductPreview.vue'
import {
  isPersonaliseType,
  PERSONALISE_PRODUCTS,
  personaliseProduct,
  type PersonaliseType,
} from '@/data/personalise'
import { formatCurrency } from '@/lib/currency'
import { useCartStore } from '@/stores/cart'
import { useCartUiStore } from '@/stores/cartUi'

const route = useRoute()
const router = useRouter()
const cart = useCartStore()
const cartUi = useCartUiStore()

const activeType = ref<PersonaliseType>('bookmark')
const photoUrl = ref<string | null>(null)
const zoom = ref(1)
const posX = ref(50)
const posY = ref(50)
const dragOver = ref(false)
const uploadError = ref('')
const adding = ref(false)
const added = ref(false)

const fmt = formatCurrency
const activeProduct = computed(() => personaliseProduct(activeType.value))
const hasPhoto = computed(() => Boolean(photoUrl.value))

function readTypeFromRoute() {
  const q = route.query.type
  if (typeof q === 'string' && isPersonaliseType(q)) {
    activeType.value = q
  }
}

readTypeFromRoute()

watch(
  () => route.query.type,
  () => readTypeFromRoute()
)

watch(activeType, (type) => {
  if (route.query.type !== type) {
    router.replace({ query: { ...route.query, type } })
  }
})

function revokePhoto() {
  if (photoUrl.value?.startsWith('blob:')) {
    URL.revokeObjectURL(photoUrl.value)
  }
}

onBeforeUnmount(revokePhoto)

function resetAdjustments() {
  zoom.value = 1
  posX.value = 50
  posY.value = 50
}

function clearPhoto() {
  revokePhoto()
  photoUrl.value = null
  resetAdjustments()
  uploadError.value = ''
  added.value = false
}

function acceptFile(file: File | undefined | null) {
  uploadError.value = ''
  added.value = false
  if (!file) return
  if (!file.type.startsWith('image/')) {
    uploadError.value = 'Please choose a JPG, PNG, or WebP image.'
    return
  }
  if (file.size > 12 * 1024 * 1024) {
    uploadError.value = 'Image must be under 12 MB.'
    return
  }
  revokePhoto()
  photoUrl.value = URL.createObjectURL(file)
  resetAdjustments()
}

function onFileInput(e: Event) {
  const input = e.target as HTMLInputElement
  acceptFile(input.files?.[0])
  input.value = ''
}

function onDrop(e: DragEvent) {
  dragOver.value = false
  acceptFile(e.dataTransfer?.files?.[0])
}

async function addToCart() {
  if (!photoUrl.value) {
    uploadError.value = 'Upload a photo first to preview and add to cart.'
    return
  }
  adding.value = true
  added.value = false
  try {
    cart.addCustomProduct({
      type: activeType.value,
      name: activeProduct.value.label,
      unitPrice: activeProduct.value.price,
      photoUrl: photoUrl.value,
      zoom: zoom.value,
      posX: posX.value,
      posY: posY.value,
    })
    added.value = true
  } finally {
    adding.value = false
  }
}
</script>

<template>
  <div class="studio tm-section tm-animate-in">
    <div class="tm-container">
      <header class="studio__head">
        <p class="studio__eyebrow">
          <Sparkles :size="16" aria-hidden="true" />
          Personalise studio
        </p>
        <h1 class="studio__title">Design your custom piece</h1>
        <p class="studio__lead">
          Upload once, preview on bookmarks, calendars, cards, and fridge magnets — the same flow
          used by photo-gift shops like Snapfish and Vistaprint.
        </p>
      </header>

      <div class="studio__layout">
        <aside class="studio__panel studio__panel--tools">
          <div class="studio__types" role="tablist" aria-label="Product type">
            <button
              v-for="p in PERSONALISE_PRODUCTS"
              :key="p.id"
              type="button"
              role="tab"
              class="studio__type"
              :class="{ 'studio__type--active': activeType === p.id }"
              :aria-selected="activeType === p.id"
              @click="activeType = p.id"
            >
              <img :src="p.sampleImage" alt="" class="studio__type-img" loading="lazy" />
              <span class="studio__type-label">{{ p.shortLabel }}</span>
            </button>
          </div>

          <div
            class="studio__drop"
            :class="{ 'studio__drop--over': dragOver, 'studio__drop--filled': hasPhoto }"
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop.prevent="onDrop"
          >
            <input
              id="photo-upload"
              type="file"
              accept="image/jpeg,image/png,image/webp,image/heic,image/heif"
              class="studio__file"
              @change="onFileInput"
            />
            <label v-if="!hasPhoto" for="photo-upload" class="studio__drop-inner">
              <span class="studio__drop-icon">
                <Upload :size="26" :stroke-width="2" aria-hidden="true" />
              </span>
              <span class="studio__drop-title">Drop your photo here</span>
              <span class="studio__drop-hint">or click to browse · JPG, PNG, WebP up to 12 MB</span>
            </label>
            <div v-else class="studio__thumb-wrap">
              <img :src="photoUrl || ''" alt="Uploaded preview" class="studio__thumb" />
              <div class="studio__thumb-actions">
                <label for="photo-upload" class="studio__ghost-btn">
                  <ImagePlus :size="16" aria-hidden="true" />
                  Replace
                </label>
                <button type="button" class="studio__ghost-btn" @click="clearPhoto">Remove</button>
              </div>
            </div>
          </div>

          <p v-if="uploadError" class="studio__error" role="alert">{{ uploadError }}</p>

          <div v-if="hasPhoto" class="studio__adjust">
            <p class="studio__adjust-title">
              <ZoomIn :size="15" aria-hidden="true" />
              Fine-tune placement
            </p>
            <label class="studio__slider">
              <span>Zoom</span>
              <input v-model.number="zoom" type="range" min="1" max="2.5" step="0.05" />
            </label>
            <label class="studio__slider">
              <span>Horizontal</span>
              <input v-model.number="posX" type="range" min="0" max="100" step="1" />
            </label>
            <label class="studio__slider">
              <span>Vertical</span>
              <input v-model.number="posY" type="range" min="0" max="100" step="1" />
            </label>
            <button type="button" class="studio__reset" @click="resetAdjustments">
              <RotateCcw :size="14" aria-hidden="true" />
              Reset crop
            </button>
          </div>

          <div class="studio__buy">
            <p class="studio__price">
              <strong>{{ fmt(activeProduct.price) }}</strong>
              <span v-if="activeProduct.compareAt > activeProduct.price" class="studio__was">{{
                fmt(activeProduct.compareAt)
              }}</span>
            </p>
            <button
              type="button"
              class="studio__cta"
              :disabled="!hasPhoto || adding"
              @click="addToCart"
            >
              <Loader2 v-if="adding" :size="18" class="studio__spin" aria-hidden="true" />
              <ShoppingBag v-else :size="18" :stroke-width="2.25" aria-hidden="true" />
              {{ adding ? 'Adding…' : 'Add to cart' }}
            </button>
            <p v-if="added" class="studio__success">
              Added!
              <button type="button" class="studio__view-cart" @click="cartUi.open()">View cart</button>
            </p>
          </div>
        </aside>

        <section class="studio__panel studio__panel--preview" aria-live="polite">
          <h2 class="studio__preview-title">Live preview — {{ activeProduct.shortLabel }}</h2>
          <div class="studio__hero-preview">
            <CustomProductPreview
              :type="activeType"
              :photo-url="photoUrl"
              :zoom="zoom"
              :pos-x="posX"
              :pos-y="posY"
            />
          </div>

          <div class="studio__all-previews">
            <h3 class="studio__all-title">See it on every product</h3>
            <div class="studio__all-grid">
              <button
                v-for="p in PERSONALISE_PRODUCTS"
                :key="p.id"
                type="button"
                class="studio__mini"
                :class="{ 'studio__mini--active': activeType === p.id }"
                @click="activeType = p.id"
              >
                <CustomProductPreview
                  :type="p.id"
                  :photo-url="photoUrl"
                  :zoom="zoom"
                  :pos-x="posX"
                  :pos-y="posY"
                  compact
                />
                <span class="studio__mini-label">{{ p.shortLabel }}</span>
              </button>
            </div>
          </div>
        </section>
      </div>

      <p class="studio__footer-note">
        High-resolution print files are prepared after checkout. Need a set?
        <RouterLink to="/create-your-set">Build a mixed set</RouterLink>
        instead.
      </p>
    </div>
  </div>
</template>

<style scoped>
.studio {
  padding-top: 1.5rem;
  padding-bottom: 4rem;
}

.studio__head {
  max-width: 40rem;
  margin-bottom: 2rem;
}

.studio__eyebrow {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin: 0 0 0.5rem;
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--color-accent);
}

.studio__title {
  margin: 0 0 0.5rem;
  font-family: var(--font-display);
  font-size: clamp(1.75rem, 4vw, 2.35rem);
  font-weight: 500;
  line-height: 1.15;
}

.studio__lead {
  margin: 0;
  color: var(--color-ink-muted);
  line-height: 1.55;
  font-size: 0.98rem;
}

.studio__layout {
  display: grid;
  gap: 1.5rem;
  grid-template-columns: minmax(0, 22rem) minmax(0, 1fr);
  align-items: start;
}

@media (max-width: 960px) {
  .studio__layout {
    grid-template-columns: 1fr;
  }
}

.studio__panel {
  border: 1px solid var(--color-border);
  border-radius: var(--radius-lg);
  background: var(--color-surface-raised, #fff);
  box-shadow: var(--shadow-card);
}

.studio__panel--tools {
  padding: 1.1rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.studio__types {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.45rem;
}

.studio__type {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.3rem;
  padding: 0.35rem;
  border: 2px solid transparent;
  border-radius: var(--radius-md);
  background: var(--color-surface);
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    background 0.2s ease;
}

.studio__type:hover {
  border-color: var(--color-border-strong);
}

.studio__type--active {
  border-color: var(--color-accent);
  background: rgba(45, 92, 82, 0.06);
}

.studio__type-img {
  width: 100%;
  aspect-ratio: 1;
  object-fit: cover;
  border-radius: 6px;
}

.studio__type-label {
  font-size: 0.62rem;
  font-weight: 700;
  text-align: center;
  line-height: 1.2;
  color: var(--color-ink);
}

.studio__file {
  position: absolute;
  width: 0;
  height: 0;
  opacity: 0;
  pointer-events: none;
}

.studio__drop {
  position: relative;
  border: 2px dashed var(--color-border-strong);
  border-radius: var(--radius-md);
  background: var(--color-surface);
  transition:
    border-color 0.2s ease,
    background 0.2s ease;
}

.studio__drop--over {
  border-color: var(--color-accent);
  background: rgba(45, 92, 82, 0.05);
}

.studio__drop--filled {
  border-style: solid;
}

.studio__drop-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.35rem;
  padding: 1.75rem 1rem;
  cursor: pointer;
  text-align: center;
}

.studio__drop-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 3rem;
  height: 3rem;
  border-radius: 999px;
  background: rgba(45, 92, 82, 0.1);
  color: var(--color-accent);
  margin-bottom: 0.25rem;
}

.studio__drop-title {
  font-weight: 700;
  font-size: 0.95rem;
}

.studio__drop-hint {
  font-size: 0.78rem;
  color: var(--color-ink-muted);
}

.studio__thumb-wrap {
  padding: 0.65rem;
}

.studio__thumb {
  width: 100%;
  max-height: 10rem;
  object-fit: contain;
  border-radius: var(--radius-sm);
  display: block;
  margin: 0 auto;
}

.studio__thumb-actions {
  display: flex;
  gap: 0.5rem;
  justify-content: center;
  margin-top: 0.6rem;
}

.studio__ghost-btn {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.35rem 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: 999px;
  background: transparent;
  font-size: 0.78rem;
  font-weight: 650;
  cursor: pointer;
  color: var(--color-ink);
}

.studio__ghost-btn:hover {
  border-color: var(--color-accent);
  color: var(--color-accent);
}

.studio__error {
  margin: 0;
  font-size: 0.85rem;
  color: var(--color-highlight);
  font-weight: 600;
}

.studio__adjust {
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
  padding-top: 0.25rem;
  border-top: 1px solid var(--color-border);
}

.studio__adjust-title {
  display: flex;
  align-items: center;
  gap: 0.35rem;
  margin: 0;
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--color-ink);
}

.studio__slider {
  display: grid;
  grid-template-columns: 5.5rem 1fr;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.78rem;
  color: var(--color-ink-muted);
}

.studio__slider input {
  width: 100%;
  accent-color: var(--color-accent);
}

.studio__reset {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  align-self: flex-start;
  padding: 0.3rem 0.65rem;
  border: none;
  background: transparent;
  font-size: 0.78rem;
  font-weight: 650;
  color: var(--color-accent);
  cursor: pointer;
}

.studio__buy {
  padding-top: 0.5rem;
  border-top: 1px solid var(--color-border);
}

.studio__price {
  margin: 0 0 0.65rem;
  display: flex;
  align-items: baseline;
  gap: 0.5rem;
}

.studio__price strong {
  font-size: 1.35rem;
  font-family: var(--font-display);
}

.studio__was {
  font-size: 0.88rem;
  color: var(--color-ink-muted);
  text-decoration: line-through;
}

.studio__cta {
  display: inline-flex;
  width: 100%;
  min-height: var(--tap-min);
  align-items: center;
  justify-content: center;
  gap: 0.45rem;
  border: none;
  border-radius: 999px;
  background: var(--color-accent);
  color: #fff !important;
  font-weight: 700;
  font-size: 0.95rem;
  cursor: pointer;
  transition: opacity 0.2s ease;
}

.studio__cta:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

.studio__cta:not(:disabled):hover {
  opacity: 0.92;
}

.studio__spin {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.studio__success {
  margin: 0.5rem 0 0;
  font-size: 0.85rem;
  color: var(--color-accent);
  font-weight: 600;
}

.studio__view-cart {
  margin-left: 0.35rem;
  padding: 0;
  border: none;
  background: none;
  font: inherit;
  font-weight: 700;
  color: var(--color-accent);
  text-decoration: underline;
  text-underline-offset: 2px;
  cursor: pointer;
}

.studio__panel--preview {
  padding: 1.25rem;
}

.studio__preview-title {
  margin: 0 0 1rem;
  font-size: 0.88rem;
  font-weight: 700;
  color: var(--color-ink-muted);
  text-transform: uppercase;
  letter-spacing: 0.06em;
}

.studio__hero-preview {
  margin-bottom: 1.5rem;
}

.studio__all-title {
  margin: 0 0 0.75rem;
  font-size: 0.95rem;
  font-weight: 700;
}

.studio__all-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 0.65rem;
}

@media (max-width: 720px) {
  .studio__all-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

.studio__mini {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  padding: 0.45rem;
  border: 2px solid var(--color-border);
  border-radius: var(--radius-md);
  background: var(--color-surface);
  cursor: pointer;
  text-align: left;
  transition: border-color 0.2s ease;
}

.studio__mini:hover,
.studio__mini--active {
  border-color: var(--color-accent);
}

.studio__mini-label {
  font-size: 0.72rem;
  font-weight: 700;
  text-align: center;
}

.studio__footer-note {
  margin: 2rem 0 0;
  text-align: center;
  font-size: 0.88rem;
  color: var(--color-ink-muted);
}
</style>

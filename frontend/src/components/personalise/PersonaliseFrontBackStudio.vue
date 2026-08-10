<script setup lang="ts">
import { ImagePlus, Loader2, Minus, Plus, RotateCcw, Upload } from 'lucide-vue-next'
import { computed, toRef } from 'vue'
import {
  clampPhotoZoom,
  defaultPhotoTransform,
  photoCropStyle,
  usePhotoEditor,
  type PhotoTransform,
} from '@/composables/usePhotoEditor'

export type { PhotoTransform }

const props = withDefaults(
  defineProps<{
    mode: 'bookmark' | 'card'
    frontPhotoUrl?: string | null
    backPhotoUrl?: string | null
    accentColor: string
    frontColor: string
    frontTransform: PhotoTransform
    backTransform: PhotoTransform
    frontUploading?: boolean
    backUploading?: boolean
    frontDragOver?: boolean
    backDragOver?: boolean
  }>(),
  {
    frontPhotoUrl: null,
    backPhotoUrl: null,
    frontUploading: false,
    backUploading: false,
    frontDragOver: false,
    backDragOver: false,
  },
)

const emit = defineEmits<{
  'update:accentColor': [hex: string]
  'update:frontColor': [hex: string]
  'update:frontTransform': [t: PhotoTransform]
  'update:backTransform': [t: PhotoTransform]
  frontUpload: [file: File]
  backUpload: [file: File]
  frontDrag: [over: boolean]
  backDrag: [over: boolean]
}>()

const BOOKMARK_ASPECT = '5 / 7'
const faceRatio = computed(() => (props.mode === 'bookmark' ? BOOKMARK_ASPECT : '3 / 5'))

const frontTransformRef = toRef(props, 'frontTransform')
const backTransformRef = toRef(props, 'backTransform')

const frontEditor = usePhotoEditor(frontTransformRef, (t) => emit('update:frontTransform', t))
const backEditor = usePhotoEditor(backTransformRef, (t) => emit('update:backTransform', t))

const frontZoomPct = computed(() => Math.round(props.frontTransform.zoom * 100))
const backZoomPct = computed(() => Math.round(props.backTransform.zoom * 100))

function onBackColorInput(e: Event) {
  emit('update:accentColor', (e.target as HTMLInputElement).value)
}

function onFrontColorInput(e: Event) {
  emit('update:frontColor', (e.target as HTMLInputElement).value)
}

function onFrontChange(e: Event) {
  const f = (e.target as HTMLInputElement).files?.[0]
  if (f) emit('frontUpload', f)
  ;(e.target as HTMLInputElement).value = ''
}

function onBackChange(e: Event) {
  const f = (e.target as HTMLInputElement).files?.[0]
  if (f) emit('backUpload', f)
  ;(e.target as HTMLInputElement).value = ''
}

function resetFront() {
  emit('update:frontTransform', defaultPhotoTransform())
}

function resetBack() {
  emit('update:backTransform', defaultPhotoTransform())
}
</script>

<template>
  <div class="studio">
    <div class="studio__cards" :class="{ 'studio__cards--bookmark': mode === 'bookmark' }">
      <!-- Front -->
      <article class="studio__card">
        <header class="studio__card-head">
          <span class="studio__card-title">Front</span>
          <label
            v-if="!frontPhotoUrl"
            class="studio__upload-chip"
            :class="{ 'studio__upload-chip--busy': frontUploading, 'studio__upload-chip--over': frontDragOver }"
            @dragover.prevent="emit('frontDrag', true)"
            @dragleave.prevent="emit('frontDrag', false)"
            @drop.prevent="
              emit('frontDrag', false);
              ($event.dataTransfer?.files?.[0] && emit('frontUpload', $event.dataTransfer.files[0]));
            "
          >
            <input type="file" accept="image/jpeg,image/png,image/webp" hidden @change="onFrontChange" />
            <Loader2 v-if="frontUploading" :size="14" class="studio__spin" />
            <Upload v-else :size="14" />
            Add photo
          </label>
        </header>

        <div
          class="studio__frame studio__frame--front"
          :class="{ 'studio__frame--bookmark': mode === 'bookmark', 'studio__frame--empty': !frontPhotoUrl }"
          :style="{ aspectRatio: faceRatio, backgroundColor: frontColor }"
        >
          <div
            v-if="frontPhotoUrl"
            class="studio__crop studio__crop--front"
            :class="{ 'studio__crop--dragging': frontEditor.dragging.value }"
            :style="{ backgroundColor: frontColor }"
            @pointerdown="frontEditor.onPointerDown"
            @pointermove="frontEditor.onPointerMove"
            @pointerup="frontEditor.onPointerUp"
            @pointercancel="frontEditor.onPointerUp"
            @wheel.prevent="frontEditor.onWheel"
          >
            <img
              :src="frontPhotoUrl"
              alt="Front preview"
              class="studio__photo"
              draggable="false"
              :style="photoCropStyle(frontTransform)"
            />
            <span class="studio__crop-hint">Drag to move · scroll to zoom</span>
          </div>
          <label
            v-else
            class="studio__drop"
            :class="{ 'studio__drop--over': frontDragOver, 'studio__drop--busy': frontUploading }"
            @dragover.prevent="emit('frontDrag', true)"
            @dragleave.prevent="emit('frontDrag', false)"
            @drop.prevent="
              emit('frontDrag', false);
              ($event.dataTransfer?.files?.[0] && emit('frontUpload', $event.dataTransfer.files[0]));
            "
          >
            <input type="file" accept="image/jpeg,image/png,image/webp" hidden @change="onFrontChange" />
            <Loader2 v-if="frontUploading" :size="22" class="studio__spin" />
            <Upload v-else :size="22" />
            <span>Drop or tap to upload</span>
          </label>
        </div>

        <div v-if="mode === 'bookmark'" class="studio__color">
          <span class="studio__color-label">Front colour</span>
          <label class="studio__color-picker">
            <span class="studio__color-wheel" :style="{ backgroundColor: frontColor }">
              <input type="color" :value="frontColor" @input="onFrontColorInput" />
            </span>
            <span class="studio__color-meta">
              <span class="studio__color-hint">Tap wheel to pick any colour</span>
              <span class="studio__color-hex">{{ frontColor }}</span>
            </span>
          </label>
        </div>

        <div v-if="frontPhotoUrl" class="studio__toolbar">
          <div class="studio__toolbar-row">
            <div class="studio__zoom">
              <button type="button" class="studio__zoom-btn" aria-label="Zoom out" @click="frontEditor.zoomBy(-0.12)">
                <Minus :size="14" />
              </button>
              <span class="studio__zoom-val">{{ frontZoomPct }}%</span>
              <button type="button" class="studio__zoom-btn" aria-label="Zoom in" @click="frontEditor.zoomBy(0.12)">
                <Plus :size="14" />
              </button>
            </div>
            <button type="button" class="studio__reset" aria-label="Reset position and zoom" @click="resetFront">
              <RotateCcw :size="14" />
            </button>
          </div>
          <input
            type="range"
            class="studio__zoom-slider"
            :min="20"
            :max="400"
            :value="frontZoomPct"
            @input="emit('update:frontTransform', { ...frontTransform, zoom: clampPhotoZoom(Number(($event.target as HTMLInputElement).value) / 100) })"
          />
        </div>

        <label v-if="frontPhotoUrl" class="studio__replace">
          <input type="file" accept="image/jpeg,image/png,image/webp" hidden @change="onFrontChange" />
          <ImagePlus :size="14" /> Replace photo
        </label>
      </article>

      <!-- Back -->
      <article class="studio__card">
        <header class="studio__card-head">
          <span class="studio__card-title">Back</span>
          <label
            v-if="!backPhotoUrl"
            class="studio__upload-chip"
            :class="{ 'studio__upload-chip--busy': backUploading, 'studio__upload-chip--over': backDragOver }"
            @dragover.prevent="emit('backDrag', true)"
            @dragleave.prevent="emit('backDrag', false)"
            @drop.prevent="
              emit('backDrag', false);
              ($event.dataTransfer?.files?.[0] && emit('backUpload', $event.dataTransfer.files[0]));
            "
          >
            <input type="file" accept="image/jpeg,image/png,image/webp" hidden @change="onBackChange" />
            <Loader2 v-if="backUploading" :size="14" class="studio__spin" />
            <ImagePlus v-else :size="14" />
            Add photo
          </label>
        </header>

        <div
          class="studio__frame studio__frame--back"
          :class="{ 'studio__frame--bookmark': mode === 'bookmark', 'studio__frame--empty': !backPhotoUrl }"
          :style="{ backgroundColor: accentColor, aspectRatio: faceRatio }"
        >
          <div
            v-if="backPhotoUrl"
            class="studio__crop studio__crop--back"
            :class="{ 'studio__crop--dragging': backEditor.dragging.value }"
            :style="{ backgroundColor: accentColor }"
            @pointerdown="backEditor.onPointerDown"
            @pointermove="backEditor.onPointerMove"
            @pointerup="backEditor.onPointerUp"
            @pointercancel="backEditor.onPointerUp"
            @wheel.prevent="backEditor.onWheel"
          >
            <img
              :src="backPhotoUrl"
              alt="Back preview"
              class="studio__photo"
              draggable="false"
              :style="photoCropStyle(backTransform)"
            />
            <span class="studio__crop-hint">Drag to move · scroll to zoom</span>
          </div>
          <template v-else>
            <span v-if="mode === 'bookmark'" class="studio__placeholder">back</span>
            <div v-if="mode === 'bookmark'" class="studio__brand">
              <svg class="studio__avatar" viewBox="0 0 48 48" aria-hidden="true">
                <defs>
                  <clipPath id="bookmark-avatar-clip">
                    <circle cx="24" cy="24" r="21" />
                  </clipPath>
                </defs>
                <circle cx="24" cy="24" r="21" fill="#9fd4dc" stroke="#111" stroke-width="1.5" />
                <image
                  href="/minimark-logo-circle.png"
                  x="-6"
                  y="6"
                  width="60"
                  height="36"
                  clip-path="url(#bookmark-avatar-clip)"
                  preserveAspectRatio="xMinYMid slice"
                />
              </svg>
              <p class="studio__site">theminimark.in</p>
              <p class="studio__brand-name">The MiniMark</p>
              <p class="studio__brand-tag">Bookmarks · Fridge Magnets · Custom Hampers</p>
            </div>
          </template>
        </div>

        <div v-if="mode === 'bookmark'" class="studio__color">
          <span class="studio__color-label">Back colour</span>
          <label class="studio__color-picker">
            <span class="studio__color-wheel" :style="{ backgroundColor: accentColor }">
              <input type="color" :value="accentColor" @input="onBackColorInput" />
            </span>
            <span class="studio__color-meta">
              <span class="studio__color-hint">Tap wheel to pick any colour</span>
              <span class="studio__color-hex">{{ accentColor }}</span>
            </span>
          </label>
        </div>

        <div v-if="backPhotoUrl" class="studio__toolbar">
          <div class="studio__toolbar-row">
            <div class="studio__zoom">
              <button type="button" class="studio__zoom-btn" aria-label="Zoom out" @click="backEditor.zoomBy(-0.12)">
                <Minus :size="14" />
              </button>
              <span class="studio__zoom-val">{{ backZoomPct }}%</span>
              <button type="button" class="studio__zoom-btn" aria-label="Zoom in" @click="backEditor.zoomBy(0.12)">
                <Plus :size="14" />
              </button>
            </div>
            <button type="button" class="studio__reset" aria-label="Reset position and zoom" @click="resetBack">
              <RotateCcw :size="14" />
            </button>
          </div>
          <input
            type="range"
            class="studio__zoom-slider"
            :min="20"
            :max="400"
            :value="backZoomPct"
            @input="emit('update:backTransform', { ...backTransform, zoom: clampPhotoZoom(Number(($event.target as HTMLInputElement).value) / 100) })"
          />
        </div>

        <label v-if="backPhotoUrl" class="studio__replace">
          <input type="file" accept="image/jpeg,image/png,image/webp" hidden @change="onBackChange" />
          <ImagePlus :size="14" /> Replace photo
        </label>
      </article>
    </div>
  </div>
</template>

<style scoped>
.studio__cards {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.85rem;
}

.studio__cards--bookmark {
  grid-template-columns: repeat(2, minmax(0, 10.5rem));
  justify-content: center;
}

.studio__card {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
  padding: 0.65rem;
  border-radius: var(--radius-lg);
  background: var(--color-surface-elevated, #fff);
  border: 1px solid var(--color-border);
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
}

.studio__card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.studio__card-title {
  font-size: 0.72rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--color-ink-muted);
}

.studio__upload-chip {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.28rem 0.55rem;
  border-radius: 999px;
  background: var(--color-accent);
  color: #fff;
  font-size: 0.68rem;
  font-weight: 700;
  cursor: pointer;
  border: none;
  transition: opacity 0.15s, transform 0.15s;
}

.studio__upload-chip:hover,
.studio__upload-chip--over {
  opacity: 0.92;
  transform: translateY(-1px);
}

.studio__upload-chip--busy {
  opacity: 0.65;
  pointer-events: none;
}

.studio__frame {
  position: relative;
  width: 100%;
  border-radius: 8px;
  overflow: hidden;
  box-shadow: 0 1px 0 rgba(0, 0, 0, 0.06), inset 0 0 0 1px rgba(0, 0, 0, 0.06);
}

.studio__frame--bookmark {
  box-shadow:
    0 6px 18px rgba(0, 0, 0, 0.16),
    0 2px 6px rgba(0, 0, 0, 0.1),
    inset 0 0 0 1px rgba(255, 255, 255, 0.08);
}

.studio__frame--front.studio__frame--empty {
  background: #4a1942;
}

.studio__frame--back {
  background: #f4b8d0;
}

.studio__crop {
  position: absolute;
  inset: 0;
  overflow: hidden;
  cursor: grab;
  touch-action: none;
  background: inherit;
}

.studio__crop--dragging {
  cursor: grabbing;
}

.studio__crop--front {
  background: inherit;
}

.studio__photo {
  width: 100%;
  height: 100%;
  object-fit: contain;
  transform-origin: center center;
  will-change: transform;
  user-select: none;
  pointer-events: none;
}

.studio__crop-hint {
  position: absolute;
  left: 50%;
  bottom: 0.35rem;
  transform: translateX(-50%);
  padding: 0.2rem 0.45rem;
  border-radius: 999px;
  background: rgba(0, 0, 0, 0.55);
  color: #fff;
  font-size: 0.55rem;
  font-weight: 600;
  white-space: nowrap;
  pointer-events: none;
  opacity: 0;
  transition: opacity 0.2s;
}

.studio__crop:hover .studio__crop-hint,
.studio__crop--dragging .studio__crop-hint {
  opacity: 1;
}

.studio__drop {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.72rem;
  font-weight: 600;
  cursor: pointer;
  border: 2px dashed rgba(255, 255, 255, 0.35);
  border-radius: 8px;
  transition: background 0.15s, border-color 0.15s;
}

.studio__drop:hover,
.studio__drop--over {
  background: rgba(255, 255, 255, 0.08);
  border-color: rgba(255, 255, 255, 0.65);
}

.studio__drop--busy {
  pointer-events: none;
  opacity: 0.7;
}

.studio__placeholder {
  position: absolute;
  top: 38%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-weight: 700;
  font-size: 0.95rem;
  color: #111;
  z-index: 1;
}

.studio__brand {
  position: absolute;
  left: 50%;
  bottom: 8%;
  transform: translateX(-50%);
  width: 84%;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.08rem;
  text-align: center;
  color: #111;
  z-index: 2;
  pointer-events: none;
}

.studio__avatar {
  width: 2rem;
  height: 2rem;
  flex-shrink: 0;
  display: block;
}

.studio__site {
  margin: 0.1rem 0 0;
  font-size: 0.44rem;
  font-weight: 600;
  line-height: 1;
}

.studio__brand-name {
  margin: 0.06rem 0 0;
  font-family: var(--font-display);
  font-size: 0.82rem;
  font-weight: 800;
  line-height: 1.05;
}

.studio__brand-tag {
  margin: 0.04rem 0 0;
  font-size: 0.34rem;
  font-weight: 500;
  line-height: 1.25;
  max-width: 9rem;
}

.studio__toolbar {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
  padding: 0.45rem 0.5rem;
  border-radius: 8px;
  background: var(--color-page, #f8f8f6);
  border: 1px solid var(--color-border);
}

.studio__toolbar-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.35rem;
  min-width: 0;
}

.studio__zoom {
  display: flex;
  align-items: center;
  gap: 0.2rem;
}

.studio__zoom-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.65rem;
  height: 1.65rem;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  background: #fff;
  color: var(--color-ink);
  cursor: pointer;
  transition: background 0.12s, border-color 0.12s;
}

.studio__zoom-btn:hover {
  border-color: var(--color-accent);
  background: rgba(45, 92, 82, 0.06);
}

.studio__zoom-val {
  min-width: 2.5rem;
  text-align: center;
  font-size: 0.72rem;
  font-weight: 800;
  font-variant-numeric: tabular-nums;
}

.studio__zoom-slider {
  width: 100%;
  accent-color: var(--color-accent);
  cursor: pointer;
}

.studio__reset {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.65rem;
  height: 1.65rem;
  padding: 0;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  background: #fff;
  color: var(--color-accent);
  cursor: pointer;
  flex-shrink: 0;
  transition: background 0.12s, border-color 0.12s;
}

.studio__reset:hover {
  border-color: var(--color-accent);
  background: rgba(45, 92, 82, 0.06);
}

.studio__replace {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  padding: 0.35rem;
  font-size: 0.68rem;
  font-weight: 600;
  color: var(--color-ink-muted);
  cursor: pointer;
  border-radius: 6px;
  transition: color 0.12s, background 0.12s;
}

.studio__replace:hover {
  color: var(--color-accent);
  background: rgba(45, 92, 82, 0.06);
}

.studio__color {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
  padding: 0.55rem;
  border-radius: 8px;
  background: var(--color-page, #f8f8f6);
  border: 1px solid var(--color-border);
}

.studio__color-label {
  font-size: 0.68rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.06em;
  color: var(--color-ink-muted);
}

.studio__color-picker {
  display: flex;
  align-items: center;
  gap: 0.65rem;
  cursor: pointer;
}

.studio__color-wheel {
  position: relative;
  flex-shrink: 0;
  width: 2.75rem;
  height: 2.75rem;
  border-radius: 50%;
  border: 2px solid rgba(0, 0, 0, 0.12);
  overflow: hidden;
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.35);
}

.studio__color-wheel input[type='color'] {
  position: absolute;
  inset: -6px;
  width: calc(100% + 12px);
  height: calc(100% + 12px);
  border: none;
  padding: 0;
  cursor: pointer;
}

.studio__color-meta {
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
  min-width: 0;
}

.studio__color-hint {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--color-ink);
}

.studio__color-hex {
  font-family: ui-monospace, monospace;
  font-size: 0.68rem;
  color: var(--color-ink-muted);
}

.studio__spin {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 640px) {
  .studio__cards--bookmark {
    grid-template-columns: 1fr 1fr;
  }
}
</style>

<script setup lang="ts">
import { ImagePlus, Loader2 } from 'lucide-vue-next'
import { computed } from 'vue'
import {
  MAGNET_STRIP_FRAMES,
  magnetStripFrameByIndex,
  magnetStripSlotKey,
} from '@/data/personaliseStudio'

const props = defineProps<{
  activeFrame: number
  slotPhotos: Record<string, string | null>
  slotTexts: Record<string, string>
  uploadingSlot?: string | null
}>()

const emit = defineEmits<{
  'update:activeFrame': [n: number]
  slotUpload: [frameIndex: number, slotIndex: number, file: File]
  'update:slotText': [frameIndex: number, slotIndex: number, text: string]
}>()

const currentFrame = computed(() => magnetStripFrameByIndex(props.activeFrame))

function slotPhoto(frameIndex: number, slotIndex: number): string | null {
  return props.slotPhotos[magnetStripSlotKey(frameIndex, slotIndex)] ?? null
}

function slotText(frameIndex: number, slotIndex: number): string {
  return props.slotTexts[magnetStripSlotKey(frameIndex, slotIndex)] ?? ''
}

function uploadingKey(frameIndex: number, slotIndex: number): boolean {
  return props.uploadingSlot === magnetStripSlotKey(frameIndex, slotIndex)
}

function onPhotoFile(frameIndex: number, slotIndex: number, e: Event) {
  const f = (e.target as HTMLInputElement).files?.[0]
  if (f) emit('slotUpload', frameIndex, slotIndex, f)
  ;(e.target as HTMLInputElement).value = ''
}

function onTextInput(frameIndex: number, slotIndex: number, e: Event) {
  emit('update:slotText', frameIndex, slotIndex, (e.target as HTMLInputElement).value)
}
</script>

<template>
  <div class="mag">
    <div class="mag__preview-col">
      <div
        class="mag__strip"
        :class="`mag__strip--${currentFrame.variant}`"
        aria-label="Magnetic photo strip preview"
      >
        <template v-for="(slot, si) in currentFrame.slots" :key="`${activeFrame}-${si}`">
          <div
            v-if="slot.type === 'photo'"
            class="mag__strip-slot mag__strip-slot--photo"
          >
            <img
              v-if="slotPhoto(activeFrame, si)"
              :src="slotPhoto(activeFrame, si)!"
              alt=""
            />
            <span v-else class="mag__strip-placeholder">{{ slot.label }}</span>
          </div>
          <div
            v-else
            class="mag__strip-slot mag__strip-slot--text"
          >
            <span>{{ slotText(activeFrame, si) || slot.label }}</span>
          </div>
        </template>
      </div>
      <p class="mag__strip-label">Magnetic photo strip</p>
    </div>

    <div class="mag__config">
      <p class="mag__hint">
        These frames are different designs for the magnetic photo strip.
      </p>

      <div class="mag__frames" role="tablist" aria-label="Frame design">
        <button
          v-for="(frame, fi) in MAGNET_STRIP_FRAMES"
          :key="frame.id"
          type="button"
          role="tab"
          class="mag__frame-btn"
          :class="{ 'mag__frame-btn--on': activeFrame === fi }"
          :aria-selected="activeFrame === fi"
          @click="emit('update:activeFrame', fi)"
        >
          {{ frame.label }}
        </button>
      </div>

      <p class="mag__frame-note">{{ currentFrame.hint }}</p>

      <div class="mag__slots">
        <template v-for="(slot, si) in currentFrame.slots" :key="`slot-${activeFrame}-${si}`">
          <div v-if="slot.type === 'photo'" class="mag__slot">
            <div class="mag__slot-thumb">
              <img v-if="slotPhoto(activeFrame, si)" :src="slotPhoto(activeFrame, si)!" alt="" />
              <ImagePlus v-else :size="16" />
            </div>
            <div class="mag__slot-body">
              <span class="mag__slot-label">{{ slot.label }}</span>
              <label class="mag__slot-upload">
                <input
                  type="file"
                  accept="image/jpeg,image/png,image/webp"
                  hidden
                  @change="onPhotoFile(activeFrame, si, $event)"
                />
                <Loader2 v-if="uploadingKey(activeFrame, si)" :size="14" class="mag__spin" />
                <span v-else>Upload photo</span>
              </label>
            </div>
          </div>

          <label v-else class="mag__slot mag__slot--text">
            <span class="mag__slot-label">{{ slot.label }} <small>optional</small></span>
            <input
              type="text"
              class="mag__text-input"
              :value="slotText(activeFrame, si)"
              placeholder="Name, date, or short quote…"
              maxlength="60"
              @input="onTextInput(activeFrame, si, $event)"
            />
          </label>
        </template>
      </div>
    </div>
  </div>
</template>

<style scoped>
.mag {
  display: grid;
  grid-template-columns: minmax(7rem, 9.5rem) minmax(0, 1fr);
  gap: 0.85rem;
  padding: 0.75rem;
  background: #ececec;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
}

@media (max-width: 640px) {
  .mag {
    grid-template-columns: 1fr;
  }
}

.mag__preview-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.45rem;
}

.mag__strip {
  width: 100%;
  max-width: 7.5rem;
  min-height: 14rem;
  padding: 0.35rem;
  background: #d8d8d8;
  border: 3px solid #111;
  border-radius: 4px;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.mag__strip--split .mag__strip-slot--photo {
  border-bottom: 2px solid #111;
}

.mag__strip--split .mag__strip-slot--photo:last-of-type {
  border-bottom: none;
}

.mag__strip-slot {
  flex: 1;
  min-height: 2.4rem;
  background: #fff;
  border-radius: 2px;
  overflow: hidden;
  display: flex;
  align-items: center;
  justify-content: center;
}

.mag__strip-slot--photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.mag__strip-placeholder {
  font-size: 0.55rem;
  font-weight: 700;
  color: var(--color-ink-faint);
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.mag__strip-slot--text {
  flex: 0 0 auto;
  min-height: 1.6rem;
  padding: 0.2rem 0.35rem;
  background: #f5f5f5;
  font-size: 0.55rem;
  font-weight: 700;
  text-align: center;
  line-height: 1.2;
  color: var(--color-ink-muted);
}

.mag__strip--triple .mag__strip-slot--photo {
  min-height: 3.2rem;
}

.mag__strip--triple-text .mag__strip-slot--photo {
  min-height: 2.8rem;
}

.mag__strip-label {
  margin: 0;
  font-size: 0.65rem;
  font-weight: 700;
  color: var(--color-ink-muted);
  text-align: center;
  line-height: 1.3;
  max-width: 7.5rem;
}

.mag__config {
  min-width: 0;
  display: flex;
  flex-direction: column;
  gap: 0.55rem;
}

.mag__hint {
  margin: 0;
  font-size: 0.72rem;
  line-height: 1.4;
  color: var(--color-ink-muted);
}

.mag__frames {
  display: flex;
  flex-wrap: wrap;
  gap: 0.35rem;
}

.mag__frame-btn {
  flex: 1 1 calc(20% - 0.35rem);
  min-width: 3.5rem;
  padding: 0.4rem 0.35rem;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  background: #fff;
  font-size: 0.72rem;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s, color 0.15s;
}

.mag__frame-btn--on {
  background: #e91e8c;
  border-color: #e91e8c;
  color: #fff;
}

.mag__frame-note {
  margin: 0;
  font-size: 0.68rem;
  color: var(--color-ink-faint);
  line-height: 1.35;
}

.mag__slots {
  display: flex;
  flex-direction: column;
  gap: 0.45rem;
}

.mag__slot {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  padding: 0.45rem 0.55rem;
  background: #fff;
  border-radius: var(--radius-md);
  border: 1px solid var(--color-border);
}

.mag__slot--text {
  flex-direction: column;
  align-items: stretch;
  gap: 0.3rem;
}

.mag__slot-thumb {
  flex: 0 0 2.5rem;
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 4px;
  background: #ececec;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  color: var(--color-ink-faint);
}

.mag__slot-thumb img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.mag__slot-body {
  flex: 1;
  min-width: 0;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.5rem;
}

.mag__slot-label {
  font-size: 0.78rem;
  font-weight: 700;
}

.mag__slot-label small {
  font-weight: 500;
  color: var(--color-ink-faint);
}

.mag__slot-upload {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  padding: 0.35rem 0.65rem;
  border-radius: 6px;
  background: #ececec;
  border: 1px dashed rgba(45, 92, 82, 0.3);
  font-size: 0.72rem;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
}

.mag__text-input {
  width: 100%;
  padding: 0.45rem 0.55rem;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  font: inherit;
  font-size: 0.82rem;
  background: var(--color-page);
}

.mag__spin {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>

<script setup lang="ts">
import { ImagePlus, Loader2 } from 'lucide-vue-next'
import { computed } from 'vue'
import {
  CALENDAR_DESIGNS,
  CALENDAR_MONTHS,
  calendarDesignById,
  type CalendarDesign,
} from '@/data/personaliseStudio'

const props = defineProps<{
  selectedDesignId: string
  monthPhotos: Record<string, string | null>
  uploadingMonth?: string | null
}>()

const emit = defineEmits<{
  'update:selectedDesignId': [id: string]
  monthUpload: [monthIndex: number, file: File]
  selectDesign: [design: CalendarDesign]
}>()

const design = computed(() => calendarDesignById(props.selectedDesignId))

function monthPhoto(monthIndex: number): string | null {
  return props.monthPhotos[`${props.selectedDesignId}-m${monthIndex}`] ?? null
}

function isUploading(monthIndex: number): boolean {
  return props.uploadingMonth === `${props.selectedDesignId}-m${monthIndex}`
}

function onMonthFile(monthIndex: number, e: Event) {
  const f = (e.target as HTMLInputElement).files?.[0]
  if (f) emit('monthUpload', monthIndex, f)
  ;(e.target as HTMLInputElement).value = ''
}

function pickDesign(d: CalendarDesign) {
  emit('update:selectedDesignId', d.id)
  emit('selectDesign', d)
}
</script>

<template>
  <div class="cal">
    <aside class="cal__designs" aria-label="Calendar designs">
      <button
        v-for="d in CALENDAR_DESIGNS"
        :key="d.id"
        type="button"
        class="cal__design-btn"
        :class="{ 'cal__design-btn--on': selectedDesignId === d.id }"
        @click="pickDesign(d)"
      >
        {{ d.shortLabel }}
      </button>
      <p class="cal__design-note">These are designs for the calendar.</p>
    </aside>

    <div class="cal__main">
      <p class="cal__hint">
        12 months of the year — pick a design above, then upload a photo for each month.
      </p>

      <div
        class="cal__grid"
        :class="`cal__grid--${design.variant}`"
        role="list"
        aria-label="Calendar months"
      >
        <div
          v-for="(month, mi) in CALENDAR_MONTHS"
          :key="`${selectedDesignId}-${mi}`"
          class="cal__month"
          role="listitem"
          :style="{
            '--cal-accent': design.accent,
            '--cal-photo-bg': design.photoBg,
            '--cal-label-bg': design.labelBg,
          }"
        >
          <label class="cal__month-photo" :class="{ 'cal__month-photo--filled': monthPhoto(mi) }">
            <input
              type="file"
              accept="image/jpeg,image/png,image/webp"
              hidden
              @change="onMonthFile(mi, $event)"
            />
            <img v-if="monthPhoto(mi)" :src="monthPhoto(mi)!" :alt="month" />
            <span v-else class="cal__month-placeholder">
              <Loader2 v-if="isUploading(mi)" :size="16" class="cal__spin" />
              <ImagePlus v-else :size="16" />
            </span>
          </label>
          <div class="cal__month-label">{{ month.slice(0, 3) }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.cal {
  display: grid;
  grid-template-columns: 5.5rem minmax(0, 1fr);
  gap: 0.75rem;
  padding: 0.65rem;
  background: #ececec;
  border-radius: var(--radius-lg);
  border: 1px solid var(--color-border);
}

@media (max-width: 768px) {
  .cal {
    grid-template-columns: 1fr;
  }

  .cal__designs {
    flex-direction: row !important;
    flex-wrap: wrap;
  }

  .cal__design-note {
    width: 100%;
    writing-mode: horizontal-tb !important;
  }
}

.cal__designs {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.cal__design-btn {
  min-height: 2.1rem;
  padding: 0.3rem 0.45rem;
  border: 1px solid var(--color-border);
  border-radius: 6px;
  background: #fff;
  font-size: 0.68rem;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.15s, border-color 0.15s, color 0.15s;
}

.cal__design-btn--on {
  background: #e91e8c;
  border-color: #e91e8c;
  color: #fff;
}

.cal__design-note {
  margin: 0.35rem 0 0;
  font-size: 0.58rem;
  line-height: 1.35;
  color: var(--color-ink-muted);
  writing-mode: vertical-rl;
  transform: rotate(180deg);
  align-self: center;
  max-height: 8rem;
}

.cal__main {
  min-width: 0;
}

.cal__hint {
  margin: 0 0 0.55rem;
  font-size: 0.72rem;
  line-height: 1.4;
  color: var(--color-ink-muted);
}

.cal__grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 0.45rem;
}

@media (max-width: 900px) {
  .cal__grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 520px) {
  .cal__grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

.cal__month {
  display: flex;
  flex-direction: column;
  border: 2px solid var(--cal-accent, #ccc);
  border-radius: 4px;
  overflow: hidden;
  background: var(--cal-photo-bg, #fff);
}

.cal__grid--bold .cal__month {
  border-width: 3px;
}

.cal__grid--floral .cal__month {
  border-radius: 8px 8px 4px 4px;
}

.cal__grid--grid .cal__month-photo {
  border-bottom: 2px dashed var(--cal-accent, #ccc);
}

.cal__month-photo {
  position: relative;
  aspect-ratio: 1;
  background: var(--cal-photo-bg, #fff);
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  overflow: hidden;
}

.cal__month-photo--filled {
  cursor: pointer;
}

.cal__month-photo img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.cal__month-placeholder {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  color: var(--color-ink-faint);
  background: rgba(255, 255, 255, 0.65);
}

.cal__month-label {
  padding: 0.28rem 0.2rem;
  text-align: center;
  font-size: 0.62rem;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.04em;
  background: var(--cal-label-bg, #d8d8d8);
  color: var(--color-ink);
}

.cal__spin {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}
</style>

<script setup lang="ts">
import { ImageIcon } from 'lucide-vue-next'
import { computed } from 'vue'
import type { PersonaliseType } from '@/data/personalise'

const props = withDefaults(
  defineProps<{
    type: PersonaliseType
    photoUrl?: string | null
    zoom?: number
    posX?: number
    posY?: number
    compact?: boolean
  }>(),
  {
    photoUrl: null,
    zoom: 1,
    posX: 50,
    posY: 50,
    compact: false,
  }
)

const photoStyle = computed(() => ({
  objectPosition: `${props.posX}% ${props.posY}%`,
  transform: `scale(${props.zoom})`,
}))

const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']
</script>

<template>
  <div class="preview" :class="[`preview--${type}`, { 'preview--compact': compact }]">
    <!-- Bookmark mockup -->
    <div v-if="type === 'bookmark'" class="mock mock--bookmark">
      <div class="mock__scene">
        <div class="mock__book-edge" aria-hidden="true" />
        <div class="mock__bookmark">
          <div class="mock__bookmark-fold" aria-hidden="true" />
          <div class="mock__photo-area mock__photo-area--tall">
            <img
              v-if="photoUrl"
              :src="photoUrl"
              alt=""
              class="mock__photo"
              :style="photoStyle"
            />
            <div v-else class="mock__placeholder">
              <ImageIcon :size="28" :stroke-width="1.5" aria-hidden="true" />
              <span>Your photo</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Calendar mockup -->
    <div v-else-if="type === 'calendar'" class="mock mock--calendar">
      <div class="mock__calendar">
        <div class="mock__photo-area mock__photo-area--wide">
          <img
            v-if="photoUrl"
            :src="photoUrl"
            alt=""
            class="mock__photo"
            :style="photoStyle"
          />
          <div v-else class="mock__placeholder">
            <ImageIcon :size="28" :stroke-width="1.5" aria-hidden="true" />
            <span>Cover photo</span>
          </div>
        </div>
        <div class="mock__cal-body">
          <p class="mock__cal-month">May 2026</p>
          <div class="mock__cal-grid" aria-hidden="true">
            <span v-for="d in 6" :key="d" class="mock__cal-day">{{ monthLabels[d - 1] }}</span>
          </div>
          <div class="mock__cal-dates" aria-hidden="true">
            <span v-for="n in 28" :key="n" class="mock__cal-num" :class="{ 'mock__cal-num--today': n === 15 }">{{
              n
            }}</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Card mockup -->
    <div v-else-if="type === 'card'" class="mock mock--card">
      <div class="mock__card-scene">
        <div class="mock__card mock__card--back" aria-hidden="true" />
        <div class="mock__card mock__card--front">
          <div class="mock__photo-area mock__photo-area--card">
            <img
              v-if="photoUrl"
              :src="photoUrl"
              alt=""
              class="mock__photo"
              :style="photoStyle"
            />
            <div v-else class="mock__placeholder">
              <ImageIcon :size="28" :stroke-width="1.5" aria-hidden="true" />
              <span>Card front</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Magnet mockup -->
    <div v-else class="mock mock--magnet">
      <div class="mock__fridge">
        <div class="mock__magnet">
          <div class="mock__photo-area mock__photo-area--square">
            <img
              v-if="photoUrl"
              :src="photoUrl"
              alt=""
              class="mock__photo"
              :style="photoStyle"
            />
            <div v-else class="mock__placeholder">
              <ImageIcon :size="28" :stroke-width="1.5" aria-hidden="true" />
              <span>Your photo</span>
            </div>
          </div>
          <span class="mock__magnet-shine" aria-hidden="true" />
        </div>
        <p class="mock__fridge-note" aria-hidden="true">The Minimark</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.preview {
  width: 100%;
}

.mock__photo-area {
  position: relative;
  overflow: hidden;
  background: linear-gradient(145deg, #e8e4df, #d4cfc8);
}

.mock__photo {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transform-origin: center center;
  transition: transform 0.15s ease;
}

.mock__placeholder {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  width: 100%;
  height: 100%;
  color: var(--color-ink-muted);
  font-size: 0.72rem;
  font-weight: 600;
}

/* Bookmark */
.mock--bookmark .mock__scene {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  padding: 1.5rem 1rem;
  min-height: 14rem;
  background: linear-gradient(180deg, #faf8f5 0%, #f0ebe4 100%);
  border-radius: var(--radius-lg);
}

.mock__book-edge {
  width: 3.5rem;
  height: 9rem;
  border-radius: 2px 6px 6px 2px;
  background: linear-gradient(90deg, #fff 0%, #f5f2ed 40%, #e8e2d9 100%);
  box-shadow: inset -3px 0 8px rgba(20, 19, 18, 0.08), 2px 4px 12px rgba(20, 19, 18, 0.1);
}

.mock__bookmark {
  position: relative;
  width: 3.25rem;
  filter: drop-shadow(0 6px 14px rgba(20, 19, 18, 0.18));
}

.mock__bookmark-fold {
  height: 0.55rem;
  background: linear-gradient(180deg, #2d5c52 0%, #1a4a42 100%);
  border-radius: 3px 3px 0 0;
}

.mock__photo-area--tall {
  height: 8.5rem;
  border: 2px solid rgba(45, 92, 82, 0.35);
  border-top: none;
  border-radius: 0 0 4px 4px;
}

/* Calendar */
.mock--calendar .mock__calendar {
  max-width: 13rem;
  margin: 0 auto;
  border-radius: var(--radius-md);
  overflow: hidden;
  box-shadow: 0 8px 28px rgba(20, 19, 18, 0.14);
  background: #fff;
}

.mock__photo-area--wide {
  aspect-ratio: 4 / 3;
}

.mock__cal-body {
  padding: 0.65rem 0.75rem 0.85rem;
  background: #fff;
}

.mock__cal-month {
  margin: 0 0 0.4rem;
  font-family: var(--font-display);
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--color-accent);
  text-align: center;
}

.mock__cal-grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 2px;
  margin-bottom: 0.35rem;
}

.mock__cal-day {
  font-size: 0.55rem;
  font-weight: 700;
  text-align: center;
  color: var(--color-ink-muted);
  text-transform: uppercase;
}

.mock__cal-dates {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 2px;
}

.mock__cal-num {
  font-size: 0.58rem;
  text-align: center;
  color: var(--color-ink);
  line-height: 1.4;
}

.mock__cal-num--today {
  background: var(--color-accent);
  color: #fff;
  border-radius: 999px;
  font-weight: 700;
}

.preview--compact.mock--calendar .mock__calendar,
.preview--calendar.preview--compact .mock__calendar {
  max-width: 100%;
}

/* Card */
.mock--card .mock__card-scene {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 14rem;
  padding: 1.5rem;
  background: linear-gradient(160deg, #f7f4ef 0%, #ebe6de 100%);
  border-radius: var(--radius-lg);
}

.mock__card {
  width: 7.5rem;
  aspect-ratio: 5 / 7;
  border-radius: 6px;
  box-shadow: 0 10px 24px rgba(20, 19, 18, 0.16);
}

.mock__card--back {
  position: absolute;
  background: #fff;
  transform: rotate(-6deg) translateX(-1.2rem);
  opacity: 0.55;
}

.mock__card--front {
  position: relative;
  background: #fff;
  transform: rotate(3deg);
  overflow: hidden;
  z-index: 1;
}

.mock__photo-area--card {
  width: 100%;
  height: 100%;
}

/* Magnet */
.mock--magnet .mock__fridge {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.75rem;
  min-height: 14rem;
  padding: 1.5rem;
  background:
    linear-gradient(135deg, rgba(255, 255, 255, 0.04) 25%, transparent 25%) 0 0 / 12px 12px,
    linear-gradient(225deg, rgba(255, 255, 255, 0.04) 25%, transparent 25%) 0 0 / 12px 12px,
    linear-gradient(180deg, #e8ecef 0%, #d8dde2 100%);
  border-radius: var(--radius-lg);
}

.mock__magnet {
  position: relative;
  width: 6.5rem;
  padding: 0.35rem;
  background: #fff;
  border-radius: 8px;
  box-shadow:
    0 2px 0 rgba(20, 19, 18, 0.08),
    0 8px 20px rgba(20, 19, 18, 0.2);
}

.mock__photo-area--square {
  aspect-ratio: 1;
  border-radius: 4px;
}

.mock__magnet-shine {
  position: absolute;
  inset: 0;
  border-radius: 8px;
  background: linear-gradient(
    135deg,
    rgba(255, 255, 255, 0.45) 0%,
    transparent 42%,
    transparent 100%
  );
  pointer-events: none;
}

.mock__fridge-note {
  margin: 0;
  font-size: 0.65rem;
  font-weight: 700;
  letter-spacing: 0.12em;
  text-transform: uppercase;
  color: rgba(20, 19, 18, 0.35);
}

.preview--compact .mock--bookmark .mock__scene,
.preview--compact.mock--bookmark .mock__scene {
  min-height: 9rem;
  padding: 1rem 0.5rem;
}

.preview--compact .mock__book-edge {
  width: 2.5rem;
  height: 6rem;
}

.preview--compact .mock__photo-area--tall {
  height: 5.5rem;
}

.preview--compact .mock__bookmark {
  width: 2.5rem;
}

.preview--compact.mock--card .mock__card-scene,
.preview--compact .mock--card .mock__card-scene {
  min-height: 9rem;
  padding: 1rem;
}

.preview--compact .mock__card {
  width: 5rem;
}

.preview--compact.mock--magnet .mock__fridge,
.preview--compact .mock--magnet .mock__fridge {
  min-height: 9rem;
  padding: 1rem;
}

.preview--compact .mock__magnet {
  width: 4.5rem;
}
</style>

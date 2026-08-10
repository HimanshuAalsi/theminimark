<script setup lang="ts">
import { ImageIcon, Move, Rotate3d } from 'lucide-vue-next'
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import type { PersonaliseType } from '@/data/personalise'
import type { CalendarLayout } from '@/data/personaliseOptions'

const props = withDefaults(
  defineProps<{
    type: PersonaliseType
    photoUrl?: string | null
    zoom?: number
    posX?: number
    posY?: number
    compact?: boolean
    interactive?: boolean
    theme?: 'light' | 'dark'
    calendarLayout?: CalendarLayout
  }>(),
  {
    photoUrl: null,
    zoom: 1,
    posX: 50,
    posY: 50,
    compact: false,
    interactive: true,
    theme: 'light',
    calendarLayout: 'desk',
  },
)

const emit = defineEmits<{
  'update:posX': [value: number]
  'update:posY': [value: number]
}>()

const rotY = ref(-28)
const rotX = ref(12)
const draggingRotate = ref(false)
const draggingPhoto = ref(false)
const rotateStart = ref({ x: 0, y: 0, ry: 0, rx: 0 })
const photoStart = ref({ x: 0, y: 0, px: 50, py: 50 })

const rigStyle = computed(() => ({
  transform: `rotateX(${rotX.value}deg) rotateY(${rotY.value}deg)`,
}))

const photoStyle = computed(() => ({
  objectPosition: `${props.posX}% ${props.posY}%`,
  transform: `scale(${props.zoom})`,
}))

const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun']

function clamp(n: number, min: number, max: number) {
  return Math.min(max, Math.max(min, n))
}

function onRotateDown(e: PointerEvent) {
  if (!props.interactive || props.compact || draggingPhoto.value) return
  draggingRotate.value = true
  rotateStart.value = { x: e.clientX, y: e.clientY, ry: rotY.value, rx: rotX.value }
  ;(e.currentTarget as HTMLElement).setPointerCapture(e.pointerId)
}

function onRotateMove(e: PointerEvent) {
  if (!draggingRotate.value) return
  const dx = e.clientX - rotateStart.value.x
  const dy = e.clientY - rotateStart.value.y
  rotY.value = clamp(rotateStart.value.ry + dx * 0.4, -55, 55)
  rotX.value = clamp(rotateStart.value.rx - dy * 0.25, -8, 28)
}

function onRotateUp(e: PointerEvent) {
  draggingRotate.value = false
  try {
    ;(e.currentTarget as HTMLElement).releasePointerCapture(e.pointerId)
  } catch {
    /* ignore */
  }
}

function onPhotoDown(e: PointerEvent) {
  if (!props.interactive || !props.photoUrl || props.compact) return
  e.stopPropagation()
  draggingPhoto.value = true
  photoStart.value = { x: e.clientX, y: e.clientY, px: props.posX, py: props.posY }
  ;(e.currentTarget as HTMLElement).setPointerCapture(e.pointerId)
}

function onPhotoMove(e: PointerEvent) {
  if (!draggingPhoto.value) return
  const dx = e.clientX - photoStart.value.x
  const dy = e.clientY - photoStart.value.y
  const sensitivity = props.compact ? 0.15 : 0.22
  emit('update:posX', clamp(photoStart.value.px + dx * sensitivity, 0, 100))
  emit('update:posY', clamp(photoStart.value.py + dy * sensitivity, 0, 100))
}

function onPhotoUp(e: PointerEvent) {
  draggingPhoto.value = false
  try {
    ;(e.currentTarget as HTMLElement).releasePointerCapture(e.pointerId)
  } catch {
    /* ignore */
  }
}

watch(
  () => props.type,
  (t) => {
    if (t === 'bookmark') {
      rotY.value = -32
      rotX.value = 14
    } else if (t === 'calendar') {
      rotY.value = -18
      rotX.value = 10
    } else if (t === 'card') {
      rotY.value = -24
      rotX.value = 8
    } else {
      rotY.value = -20
      rotX.value = 11
    }
  },
  { immediate: true },
)

onBeforeUnmount(() => {
  draggingRotate.value = false
  draggingPhoto.value = false
})
</script>

<template>
  <div
    class="studio3d"
    :class="[
      `studio3d--${type}`,
      `studio3d--${theme}`,
      { 'studio3d--compact': compact, 'studio3d--interactive': interactive && !compact },
    ]"
  >
    <div v-if="interactive && !compact" class="studio3d__hints">
      <span><Rotate3d :size="13" aria-hidden="true" /> Drag background to rotate</span>
      <span v-if="photoUrl"><Move :size="13" aria-hidden="true" /> Drag photo to reposition</span>
    </div>

    <div
      class="studio3d__stage"
      :class="{ 'studio3d__stage--drag': draggingRotate }"
      @pointerdown="onRotateDown"
      @pointermove="onRotateMove"
      @pointerup="onRotateUp"
      @pointercancel="onRotateUp"
    >
      <div class="studio3d__ambient" aria-hidden="true" />
      <div class="studio3d__shadow" aria-hidden="true" />

      <div class="studio3d__viewport">
        <div
          class="studio3d__float"
          :class="{ 'studio3d__float--idle': !draggingRotate && !draggingPhoto && !compact }"
        >
          <div class="studio3d__rig" :style="rigStyle">
        <!-- Bookmark: open book + magnetic bookmark -->
        <div v-if="type === 'bookmark'" class="product product--bookmark">
          <div class="book">
            <div class="book__cover book__cover--left" />
            <div class="book__block">
              <div v-for="n in 5" :key="n" class="book__page" :style="{ '--i': n }" />
            </div>
            <div class="book__cover book__cover--right" />
          </div>
          <div class="bookmark-clip">
            <div class="bookmark-clip__tab" />
            <div class="bookmark-clip__body">
              <div
                class="face face--tall"
                @pointerdown="onPhotoDown"
                @pointermove="onPhotoMove"
                @pointerup="onPhotoUp"
                @pointercancel="onPhotoUp"
              >
                <img v-if="photoUrl" :src="photoUrl" alt="" class="face__img" :style="photoStyle" />
                <div v-else class="face__empty">
                  <ImageIcon :size="compact ? 20 : 32" :stroke-width="1.5" />
                  <span>Your photo</span>
                </div>
                <span class="face__gloss" aria-hidden="true" />
              </div>
            </div>
          </div>
        </div>

        <!-- Calendar: desk tent calendar -->
        <div
          v-else-if="type === 'calendar'"
          class="product product--calendar"
          :class="{ 'product--calendar-wall': calendarLayout === 'wall' }"
        >
          <div class="cal">
            <div class="cal__stand" />
            <div class="cal__shell">
              <div
                class="face face--cover"
                @pointerdown="onPhotoDown"
                @pointermove="onPhotoMove"
                @pointerup="onPhotoUp"
                @pointercancel="onPhotoUp"
              >
                <img v-if="photoUrl" :src="photoUrl" alt="" class="face__img" :style="photoStyle" />
                <div v-else class="face__empty">
                  <ImageIcon :size="compact ? 20 : 32" :stroke-width="1.5" />
                  <span>Cover photo</span>
                </div>
                <span class="face__gloss" aria-hidden="true" />
              </div>
              <div class="cal__pages">
                <p class="cal__month">2026</p>
                <div class="cal__grid">
                  <span v-for="d in monthLabels" :key="d" class="cal__label">{{ d }}</span>
                </div>
                <div class="cal__dots">
                  <span
                    v-for="n in 28"
                    :key="n"
                    class="cal__dot"
                    :class="{ 'cal__dot--on': n === 15 }"
                  >{{ n }}</span>
                </div>
              </div>
              <div class="cal__edge" aria-hidden="true" />
            </div>
          </div>
        </div>

        <!-- Card: folded greeting card -->
        <div v-else-if="type === 'card'" class="product product--card">
          <div class="card3d">
            <div class="card3d__back" />
            <div class="card3d__fold">
              <div class="card3d__inside" />
              <div class="card3d__front">
                <div
                  class="face face--card"
                  @pointerdown="onPhotoDown"
                  @pointermove="onPhotoMove"
                  @pointerup="onPhotoUp"
                  @pointercancel="onPhotoUp"
                >
                  <img v-if="photoUrl" :src="photoUrl" alt="" class="face__img" :style="photoStyle" />
                  <div v-else class="face__empty">
                    <ImageIcon :size="compact ? 20 : 32" :stroke-width="1.5" />
                    <span>Card front</span>
                  </div>
                  <span class="face__gloss" aria-hidden="true" />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Magnet: on fridge -->
        <div v-else class="product product--magnet">
          <div class="fridge">
            <div class="fridge__door">
              <div class="fridge__branding">the minimark</div>
              <div class="magnet3d">
                <div class="magnet3d__side magnet3d__side--l" />
                <div class="magnet3d__side magnet3d__side--r" />
                <div class="magnet3d__side magnet3d__side--t" />
                <div class="magnet3d__face-wrap">
                  <div
                    class="face face--sq"
                    @pointerdown="onPhotoDown"
                    @pointermove="onPhotoMove"
                    @pointerup="onPhotoUp"
                    @pointercancel="onPhotoUp"
                  >
                    <img v-if="photoUrl" :src="photoUrl" alt="" class="face__img" :style="photoStyle" />
                    <div v-else class="face__empty">
                      <ImageIcon :size="compact ? 20 : 32" :stroke-width="1.5" />
                      <span>Your photo</span>
                    </div>
                    <span class="face__gloss face__gloss--magnet" aria-hidden="true" />
                  </div>
                </div>
              </div>
            </div>
            <div class="fridge__handle" aria-hidden="true" />
          </div>
        </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.studio3d {
  --stage-h: clamp(280px, 42vh, 440px);
  --preview-scale: 1;
  --paper: #faf8f5;
  --paper-edge: #e8e2d8;
  --accent-3d: var(--tm-accent-a);
  width: 100%;
}

@media (min-width: 900px) {
  .studio3d:not(.studio3d--compact) {
    --stage-h: clamp(320px, 38vh, 420px);
    --preview-scale: min(1.15, max(0.72, 100vw / 1280px));
  }
}

@media (min-width: 1400px) {
  .studio3d:not(.studio3d--compact) {
    --preview-scale: min(1.05, max(0.8, 1100px / 100vw));
  }
}

.studio3d--compact {
  --stage-h: 9.5rem;
}

.studio3d--light .studio3d__hints {
  color: var(--color-ink-muted);
}

.studio3d--dark .studio3d__hints {
  color: rgba(255, 255, 255, 0.75);
}

.studio3d__hints {
  display: flex;
  flex-wrap: wrap;
  gap: 0.65rem 1.25rem;
  margin-bottom: 0.75rem;
  font-size: 0.72rem;
  font-weight: 600;
}

.studio3d__hints span {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
}

.studio3d__stage {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: var(--stage-h);
  height: var(--stage-h);
  max-height: min(55vh, 480px);
  border-radius: 20px;
  overflow: hidden;
  cursor: grab;
  touch-action: none;
  box-sizing: border-box;
  padding: 1.5rem 1rem;
}

.studio3d--light .studio3d__stage {
  background:
    radial-gradient(ellipse 70% 55% at 50% 30%, rgba(45, 92, 82, 0.08), transparent 65%),
    linear-gradient(180deg, #faf9f7 0%, #f0ebe4 55%, #e8e2d9 100%);
  border: 1px solid rgba(20, 19, 18, 0.06);
}

.studio3d--dark .studio3d__stage {
  background:
    radial-gradient(ellipse 80% 60% at 50% 35%, rgba(45, 92, 82, 0.35), transparent 70%),
    linear-gradient(165deg, #1a2332 0%, #0f1419 45%, #1a1f26 100%);
}

.studio3d__stage--drag {
  cursor: grabbing;
}

.studio3d--compact .studio3d__stage {
  border-radius: 12px;
  cursor: default;
}

.studio3d__ambient {
  position: absolute;
  inset: 0;
  background: radial-gradient(circle at 50% 40%, rgba(255, 255, 255, 0.08), transparent 55%);
  pointer-events: none;
}

.studio3d__shadow {
  position: absolute;
  left: 50%;
  top: 58%;
  width: 50%;
  height: 14%;
  transform: translateX(-50%);
  background: radial-gradient(ellipse, rgba(0, 0, 0, 0.12) 0%, transparent 72%);
  filter: blur(10px);
  pointer-events: none;
}

.studio3d--dark .studio3d__shadow {
  background: radial-gradient(ellipse, rgba(0, 0, 0, 0.45) 0%, transparent 70%);
}

.studio3d__viewport {
  position: relative;
  z-index: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 100%;
  height: 100%;
  max-width: 100%;
  max-height: 100%;
}

.studio3d__float {
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  transform: scale(var(--preview-scale));
  transform-origin: center center;
  will-change: transform;
}

.studio3d__float--idle {
  animation: studio-float 5s ease-in-out infinite;
}

@keyframes studio-float {
  0%,
  100% {
    transform: scale(var(--preview-scale)) translateY(0);
  }
  50% {
    transform: scale(var(--preview-scale)) translateY(-6px);
  }
}

.studio3d__rig {
  transform-style: preserve-3d;
  transition: transform 0.08s ease-out;
  will-change: transform;
}

.studio3d--compact .studio3d__stage {
  min-height: var(--stage-h);
  height: var(--stage-h);
  max-height: var(--stage-h);
  padding: 0.35rem;
}

.studio3d--compact .studio3d__float {
  --preview-scale: 0.52;
}

.product {
  position: relative;
  transform-style: preserve-3d;
}

/* Photo face shared */
.face {
  position: relative;
  overflow: hidden;
  background: linear-gradient(145deg, #ece8e2, #d8d2c8);
  cursor: grab;
  transform-style: preserve-3d;
}

.face:active {
  cursor: grabbing;
}

.studio3d--compact .face {
  cursor: default;
}

.face__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
  transform-origin: center center;
  pointer-events: none;
  user-select: none;
}

.face__empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  width: 100%;
  height: 100%;
  color: rgba(20, 19, 18, 0.45);
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.face__gloss {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    125deg,
    rgba(255, 255, 255, 0.55) 0%,
    rgba(255, 255, 255, 0.08) 28%,
    transparent 50%
  );
  pointer-events: none;
}

.face__gloss--magnet {
  background: linear-gradient(
    140deg,
    rgba(255, 255, 255, 0.7) 0%,
    transparent 40%,
    rgba(255, 255, 255, 0.15) 100%
  );
}

/* Bookmark */
.product--bookmark {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
}

.book {
  display: flex;
  transform: rotateY(-18deg);
  transform-style: preserve-3d;
  filter: drop-shadow(-8px 12px 20px rgba(0, 0, 0, 0.35));
}

.book__cover {
  width: 2.2rem;
  height: 9.5rem;
  background: linear-gradient(90deg, #8b5a3c, #6d4530);
  border-radius: 3px 2px 2px 3px;
  transform: translateZ(-2px);
}

.book__cover--right {
  background: linear-gradient(270deg, #a06845, #7a5238);
  transform: translateZ(2px);
}

.book__block {
  position: relative;
  width: 5.5rem;
  height: 9.2rem;
  transform-style: preserve-3d;
}

.book__page {
  position: absolute;
  inset: 0;
  background: var(--paper);
  border: 1px solid var(--paper-edge);
  border-radius: 1px;
  transform: translateZ(calc(var(--i) * 0.8px));
  box-shadow: 0 1px 0 rgba(0, 0, 0, 0.04);
}

.bookmark-clip {
  transform: translateZ(28px) rotateY(8deg);
  filter: drop-shadow(0 14px 24px rgba(0, 0, 0, 0.4));
}

.bookmark-clip__tab {
  height: 0.65rem;
  margin: 0 0.15rem;
  background: linear-gradient(180deg, #3d8b7a, var(--accent-3d));
  border-radius: 4px 4px 0 0;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.25);
}

.bookmark-clip__body {
  width: 4.2rem;
  padding: 2px;
  background: linear-gradient(180deg, #fff, #f0ebe4);
  border-radius: 0 0 6px 6px;
  border: 1px solid rgba(45, 92, 82, 0.35);
}

.face--tall {
  height: 9.5rem;
  border-radius: 0 0 5px 5px;
}

.studio3d--compact .face--tall {
  height: 5.5rem;
}

.studio3d--compact .bookmark-clip__body {
  width: 2.6rem;
}

.studio3d--compact .book__block {
  width: 3.2rem;
  height: 5.5rem;
}

.studio3d--compact .book__cover {
  width: 1.2rem;
  height: 5.5rem;
}

/* Calendar */
.cal {
  transform-style: preserve-3d;
  filter: drop-shadow(0 16px 28px rgba(0, 0, 0, 0.38));
}

.cal__stand {
  position: absolute;
  left: 50%;
  bottom: -1.5rem;
  width: 8rem;
  height: 2.5rem;
  margin-left: -4rem;
  background: linear-gradient(180deg, #ddd8d0, #c5bfb5);
  transform: rotateX(-68deg) translateZ(-12px);
  border-radius: 4px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
}

.cal__shell {
  position: relative;
  width: 11.5rem;
  transform: rotateX(-6deg);
  transform-style: preserve-3d;
  background: var(--paper);
  border-radius: 8px 8px 4px 4px;
  box-shadow:
    inset 0 0 0 1px rgba(0, 0, 0, 0.06),
    0 2px 0 #fff;
}

.studio3d--compact .cal__shell {
  width: 6.5rem;
}

.product--calendar-wall .cal__shell {
  width: 13rem;
  transform: rotateX(-2deg);
}

.product--calendar-wall .cal__stand {
  display: none;
}

.face--cover {
  aspect-ratio: 4 / 3;
  border-radius: 6px 6px 0 0;
}

.cal__pages {
  padding: 0.55rem 0.65rem 0.75rem;
  background: #fff;
  border-radius: 0 0 6px 6px;
}

.cal__month {
  margin: 0 0 0.35rem;
  font-family: var(--font-display);
  font-size: 0.85rem;
  font-weight: 600;
  text-align: center;
  color: var(--accent-3d);
}

.studio3d--compact .cal__month {
  font-size: 0.55rem;
}

.cal__grid {
  display: grid;
  grid-template-columns: repeat(6, 1fr);
  gap: 2px;
  margin-bottom: 0.25rem;
}

.cal__label {
  font-size: 0.45rem;
  font-weight: 800;
  text-align: center;
  color: var(--color-ink-muted, #8c8880);
  text-transform: uppercase;
}

.cal__dots {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 1px;
}

.cal__dot {
  font-size: 0.42rem;
  text-align: center;
  line-height: 1.35;
  color: var(--color-ink, #141312);
}

.cal__dot--on {
  background: var(--accent-3d);
  color: #fff;
  border-radius: 999px;
  font-weight: 800;
}

.cal__edge {
  position: absolute;
  right: -5px;
  top: 8%;
  bottom: 8%;
  width: 5px;
  background: linear-gradient(90deg, #e0dbd3, #cfc8be);
  transform: rotateY(88deg);
  transform-origin: left center;
  border-radius: 0 2px 2px 0;
}

/* Card */
.card3d {
  position: relative;
  transform-style: preserve-3d;
  filter: drop-shadow(0 18px 32px rgba(0, 0, 0, 0.35));
}

.card3d__back {
  position: absolute;
  width: 8.5rem;
  height: 11.5rem;
  background: #fff;
  border-radius: 6px;
  transform: rotateY(-12deg) translateX(-1.8rem) translateZ(-8px);
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
  opacity: 0.7;
}

.studio3d--compact .card3d__back {
  width: 5rem;
  height: 6.5rem;
}

.card3d__fold {
  display: flex;
  transform: rotateY(-8deg);
  transform-style: preserve-3d;
}

.card3d__inside {
  width: 0.35rem;
  height: 11.5rem;
  background: linear-gradient(90deg, #f5f2ed, #e8e2d9);
  transform: rotateY(-22deg);
  transform-origin: right center;
}

.card3d__front {
  width: 8.5rem;
  height: 11.5rem;
  background: #fff;
  border-radius: 4px 8px 8px 4px;
  transform: translateZ(4px);
  box-shadow:
    inset -3px 0 8px rgba(0, 0, 0, 0.04),
    4px 0 12px rgba(0, 0, 0, 0.08);
  overflow: hidden;
}

.studio3d--compact .card3d__front,
.studio3d--compact .card3d__inside {
  height: 6.5rem;
}

.studio3d--compact .card3d__front {
  width: 5rem;
}

.face--card {
  width: 100%;
  height: 100%;
}

/* Magnet / fridge */
.fridge {
  transform-style: preserve-3d;
  filter: drop-shadow(0 12px 28px rgba(0, 0, 0, 0.45));
}

.fridge__door {
  position: relative;
  width: 14rem;
  height: 12rem;
  padding: 1.25rem;
  border-radius: 10px;
  background: linear-gradient(
    145deg,
    #e8ecef 0%,
    #d4d9de 35%,
    #c8ced4 50%,
    #dce1e6 100%
  );
  box-shadow:
    inset 0 0 40px rgba(255, 255, 255, 0.35),
    inset -8px 0 16px rgba(0, 0, 0, 0.06);
  transform: translateZ(0);
}

.studio3d--compact .fridge__door {
  width: 8rem;
  height: 7rem;
  padding: 0.65rem;
}

.fridge__branding {
  position: absolute;
  bottom: 0.65rem;
  left: 50%;
  transform: translateX(-50%);
  font-size: 0.55rem;
  font-weight: 800;
  letter-spacing: 0.2em;
  text-transform: uppercase;
  color: rgba(20, 19, 18, 0.2);
}

.fridge__handle {
  position: absolute;
  right: -0.35rem;
  top: 35%;
  width: 0.45rem;
  height: 3.5rem;
  background: linear-gradient(90deg, #b8bcc2, #9aa0a8);
  border-radius: 0 4px 4px 0;
  transform: translateZ(14px);
  box-shadow: 2px 0 6px rgba(0, 0, 0, 0.2);
}

.magnet3d {
  position: absolute;
  left: 50%;
  top: 42%;
  transform: translate(-50%, -50%) translateZ(24px) rotateX(4deg);
  transform-style: preserve-3d;
}

.magnet3d__face-wrap {
  width: 6.5rem;
  height: 6.5rem;
  padding: 0.4rem;
  background: linear-gradient(145deg, #fff, #f4f1ec);
  border-radius: 10px;
  box-shadow:
    0 3px 0 rgba(0, 0, 0, 0.12),
    0 12px 24px rgba(0, 0, 0, 0.25);
  transform: translateZ(6px);
}

.studio3d--compact .magnet3d__face-wrap {
  width: 4rem;
  height: 4rem;
}

.magnet3d__side {
  position: absolute;
  background: linear-gradient(180deg, #e8e4de, #ccc6bc);
}

.magnet3d__side--l {
  left: 0;
  top: 0.4rem;
  bottom: 0.4rem;
  width: 5px;
  transform: rotateY(-90deg) translateX(-2px);
  transform-origin: left;
}

.magnet3d__side--r {
  right: 0;
  top: 0.4rem;
  bottom: 0.4rem;
  width: 5px;
  transform: rotateY(90deg) translateX(2px);
  transform-origin: right;
}

.magnet3d__side--t {
  top: 0;
  left: 0.4rem;
  right: 0.4rem;
  height: 5px;
  transform: rotateX(90deg) translateY(-2px);
}

.face--sq {
  width: 100%;
  aspect-ratio: 1;
  border-radius: 6px;
}

.studio3d__stage {
  perspective: 1100px;
  perspective-origin: 50% 50%;
}

.studio3d--compact .studio3d__stage {
  perspective: 600px;
  perspective-origin: 50% 50%;
}

@media (prefers-reduced-motion: reduce) {
  .studio3d__float--idle {
    animation: none;
  }
}
</style>

<script setup lang="ts">
import { ArrowRight, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { heroSlides } from '@/data/siteContent'

const slides = heroSlides
const active = ref(0)
const reduceMotion = ref(false)
const isPaused = ref(false)
let autoplayTimer: ReturnType<typeof setInterval> | null = null
const AUTOPLAY_MS = 7000

function go(delta: number) {
  const n = slides.length
  active.value = (active.value + delta + n) % n
}

function goTo(i: number) {
  const n = slides.length
  active.value = ((i % n) + n) % n
}

const current = computed(() => slides[active.value])

function startAutoplay() {
  stopAutoplay()
  if (reduceMotion.value || slides.length <= 1) return
  autoplayTimer = setInterval(() => {
    if (!isPaused.value && document.visibilityState === 'visible') go(1)
  }, AUTOPLAY_MS)
}

function stopAutoplay() {
  if (autoplayTimer) {
    clearInterval(autoplayTimer)
    autoplayTimer = null
  }
}

function onKeydown(e: KeyboardEvent) {
  if (e.key === 'ArrowLeft') {
    e.preventDefault()
    go(-1)
  } else if (e.key === 'ArrowRight') {
    e.preventDefault()
    go(1)
  }
}

watch([active, reduceMotion], () => {
  stopAutoplay()
  startAutoplay()
})

let removeMqListener: (() => void) | null = null

function onVisibility() {
  if (document.visibilityState === 'visible') startAutoplay()
  else stopAutoplay()
}

onMounted(() => {
  reduceMotion.value = window.matchMedia('(prefers-reduced-motion: reduce)').matches
  const mq = window.matchMedia('(prefers-reduced-motion: reduce)')
  const handler = () => {
    reduceMotion.value = mq.matches
  }
  mq.addEventListener('change', handler)
  removeMqListener = () => mq.removeEventListener('change', handler)
  document.addEventListener('visibilitychange', onVisibility)
  startAutoplay()
})

onUnmounted(() => {
  removeMqListener?.()
  document.removeEventListener('visibilitychange', onVisibility)
  stopAutoplay()
})
</script>

<template>
  <section
    class="hero"
    aria-label="Featured"
    @mouseenter="isPaused = true"
    @mouseleave="isPaused = false"
  >
    <div class="hero__wrap tm-container">
      <div
        class="hero__grid"
        role="region"
        aria-roledescription="carousel"
        aria-label="Featured collections"
        tabindex="0"
        @keydown="onKeydown"
      >
        <!-- Copy column -->
        <div class="hero__copy">
          <div class="hero__copy-inner">
            <Transition :name="reduceMotion ? 'hero-fade' : 'hero-slide'" mode="out-in">
              <div :key="active" class="hero__text-block">
                <p class="hero__eyebrow">{{ current.eyebrow }}</p>
                <h1 class="hero__title">{{ current.title }}</h1>
                <p class="hero__lead">{{ current.text }}</p>
                <div class="hero__actions">
                  <RouterLink class="hero__btn hero__btn--primary" :to="current.ctaPrimary.to">
                    {{ current.ctaPrimary.label }}
                    <ArrowRight :size="17" :stroke-width="2.25" aria-hidden="true" />
                  </RouterLink>
                  <RouterLink class="hero__btn hero__btn--soft" :to="current.ctaSecondary.to">
                    {{ current.ctaSecondary.label }}
                  </RouterLink>
                </div>
              </div>
            </Transition>
          </div>

          <div v-if="slides.length > 1" class="hero__tabs" role="tablist" aria-label="Choose slide">
            <button
              v-for="(slide, i) in slides"
              :key="i"
              type="button"
              role="tab"
              class="hero__tab"
              :class="{ 'hero__tab--active': i === active }"
              :aria-selected="i === active"
              @click="goTo(i)"
            >
              <span class="hero__tab-num">{{ String(i + 1).padStart(2, '0') }}</span>
              <span class="hero__tab-label">{{ slide.tabLabel }}</span>
            </button>
          </div>
        </div>

        <!-- Visual column -->
        <div class="hero__visual">
          <div class="hero__frame">
            <Transition name="hero-img" mode="out-in">
              <img
                :key="active"
                class="hero__img"
                :src="current.image"
                alt=""
                loading="eager"
                decoding="async"
                fetchpriority="high"
              />
            </Transition>
            <div class="hero__frame-accent" aria-hidden="true" />
          </div>

          <div v-if="slides.length > 1" class="hero__controls">
            <button type="button" class="hero__ctrl" aria-label="Previous slide" @click="go(-1)">
              <ChevronLeft :size="18" :stroke-width="2.25" />
            </button>
            <span class="hero__counter" aria-live="polite">
              <span class="hero__counter-current">{{ String(active + 1).padStart(2, '0') }}</span>
              <span class="hero__counter-sep">/</span>
              <span class="hero__counter-total">{{ String(slides.length).padStart(2, '0') }}</span>
            </span>
            <button type="button" class="hero__ctrl" aria-label="Next slide" @click="go(1)">
              <ChevronRight :size="18" :stroke-width="2.25" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.hero {
  position: relative;
  padding: clamp(1.25rem, 3vw, 2rem) 0 clamp(1.5rem, 4vw, 2.5rem);
  background:
    radial-gradient(ellipse 80% 60% at 10% 0%, rgba(45, 92, 82, 0.07) 0%, transparent 55%),
    var(--color-page);
  border-bottom: 1px solid var(--color-border);
}

.hero__wrap {
  position: relative;
}

.hero__grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.05fr);
  gap: clamp(1.5rem, 4vw, 3rem);
  align-items: center;
  min-height: clamp(20rem, 52vw, 28rem);
}

@media (max-width: 900px) {
  .hero__grid {
    grid-template-columns: 1fr;
    min-height: auto;
  }
}

.hero__copy {
  display: flex;
  flex-direction: column;
  gap: 1.75rem;
  padding: 0.25rem 0;
}

.hero__copy-inner {
  min-height: 14rem;
}

@media (max-width: 900px) {
  .hero__copy-inner {
    min-height: auto;
  }
}

.hero__eyebrow {
  margin: 0 0 0.65rem;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.14em;
  text-transform: uppercase;
  color: var(--color-accent);
}

.hero__title {
  margin: 0 0 0.85rem;
  font-family: var(--font-display);
  font-weight: 500;
  font-size: clamp(1.85rem, 4.5vw, 2.85rem);
  line-height: 1.1;
  letter-spacing: -0.03em;
  color: var(--color-ink);
}

.hero__lead {
  margin: 0 0 1.35rem;
  max-width: 38ch;
  font-size: clamp(0.95rem, 1.8vw, 1.05rem);
  line-height: 1.65;
  color: var(--color-ink-muted);
}

.hero__actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
}

.hero__btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.4rem;
  min-height: var(--tap-min);
  padding: 0 1.35rem;
  border-radius: 999px;
  font-weight: 650;
  font-size: 0.92rem;
  text-decoration: none;
  transition:
    background 0.22s ease,
    color 0.22s ease,
    border-color 0.22s ease,
    transform 0.15s ease,
    box-shadow 0.22s ease;
}

.hero__btn--primary {
  background: var(--color-accent);
  color: #fff !important;
  border: 2px solid transparent;
  box-shadow: 0 4px 18px rgba(45, 92, 82, 0.22);
}

.hero__btn--primary:hover {
  background: var(--color-accent-hover);
  transform: translateY(-1px);
  box-shadow: 0 6px 22px rgba(45, 92, 82, 0.28);
}

.hero__btn--soft {
  background: var(--color-surface-elevated);
  color: var(--color-ink) !important;
  border: 2px solid var(--color-border-strong);
}

.hero__btn--soft:hover {
  border-color: var(--color-accent);
  color: var(--color-accent) !important;
}

.hero__tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
}

.hero__tab {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  padding: 0.45rem 0.75rem;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-sm);
  background: var(--color-surface-elevated);
  cursor: pointer;
  text-align: left;
  transition:
    border-color 0.2s ease,
    background 0.2s ease,
    box-shadow 0.2s ease;
}

.hero__tab:hover {
  border-color: var(--color-border-strong);
}

.hero__tab--active {
  border-color: var(--color-accent);
  background: var(--color-accent-soft);
  box-shadow: var(--shadow-sm);
}

.hero__tab-num {
  font-size: 0.65rem;
  font-weight: 800;
  letter-spacing: 0.06em;
  color: var(--color-accent);
}

.hero__tab-label {
  font-size: 0.72rem;
  font-weight: 650;
  color: var(--color-ink-muted);
  line-height: 1.2;
  max-width: 8rem;
}

.hero__tab--active .hero__tab-label {
  color: var(--color-ink);
}

.hero__visual {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: 0.85rem;
}

.hero__frame {
  position: relative;
  aspect-ratio: 5 / 4;
  border-radius: var(--radius-xl);
  overflow: hidden;
  background: var(--color-surface-elevated);
  border: 1px solid var(--color-border);
  box-shadow: var(--shadow-float);
}

.hero__img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  display: block;
}

.hero__frame-accent {
  position: absolute;
  inset: auto -0.5rem -0.5rem auto;
  width: 45%;
  height: 35%;
  border-radius: var(--radius-lg);
  background: linear-gradient(135deg, var(--color-accent-soft), transparent);
  pointer-events: none;
  z-index: -1;
}

.hero__controls {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 0.65rem;
}

.hero__ctrl {
  display: grid;
  place-items: center;
  width: 2.5rem;
  height: 2.5rem;
  border: 1px solid var(--color-border);
  border-radius: 999px;
  background: var(--color-surface-elevated);
  color: var(--color-ink);
  cursor: pointer;
  transition:
    border-color 0.2s ease,
    color 0.2s ease,
    background 0.2s ease;
}

.hero__ctrl:hover {
  border-color: var(--color-accent);
  color: var(--color-accent);
  background: var(--color-accent-soft);
}

.hero__counter {
  display: inline-flex;
  align-items: baseline;
  gap: 0.15rem;
  font-variant-numeric: tabular-nums;
  font-size: 0.82rem;
  font-weight: 700;
  color: var(--color-ink-muted);
}

.hero__counter-current {
  font-size: 1.1rem;
  color: var(--color-ink);
}

.hero__counter-sep {
  opacity: 0.45;
}

/* Text transitions */
.hero-slide-enter-active,
.hero-slide-leave-active {
  transition:
    opacity 0.35s ease,
    transform 0.35s var(--ease-out, ease);
}

.hero-slide-enter-from {
  opacity: 0;
  transform: translateY(12px);
}

.hero-slide-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

.hero-fade-enter-active,
.hero-fade-leave-active {
  transition: opacity 0.2s ease;
}

.hero-fade-enter-from,
.hero-fade-leave-to {
  opacity: 0;
}

/* Image crossfade */
.hero-img-enter-active,
.hero-img-leave-active {
  transition: opacity 0.5s ease;
}

.hero-img-enter-from,
.hero-img-leave-to {
  opacity: 0;
}

@media (prefers-reduced-motion: reduce) {
  .hero-slide-enter-active,
  .hero-slide-leave-active,
  .hero-img-enter-active,
  .hero-img-leave-active {
    transition-duration: 0.01ms;
  }

  .hero-slide-enter-from,
  .hero-slide-leave-to {
    transform: none;
  }
}
</style>

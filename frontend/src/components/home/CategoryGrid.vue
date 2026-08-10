<script setup lang="ts">
import { ArrowUpRight, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { storeToRefs } from 'pinia'
import { aosDelay } from '@/lib/aos'
import { homePageImageSrc, useHomePageStore } from '@/stores/homePage'

const homePage = useHomePageStore()
const { categoryStrip } = storeToRefs(homePage)

const tiles = computed(() =>
  categoryStrip.value.map((c) => ({
    ...c,
    image: homePageImageSrc(c.image),
  })),
)

const track = ref<HTMLElement | null>(null)
const canScrollPrev = ref(false)
const canScrollNext = ref(false)
const useRail = ref(false)

function updateScrollState() {
  const el = track.value
  if (!el) return
  useRail.value = window.matchMedia('(max-width: 639px)').matches
  if (!useRail.value) return
  const max = el.scrollWidth - el.clientWidth - 2
  canScrollPrev.value = el.scrollLeft > 4
  canScrollNext.value = el.scrollLeft < max
}

function scroll(dir: -1 | 1) {
  const el = track.value
  if (!el) return
  const card = el.querySelector<HTMLElement>('.cat-tile')
  const step = card ? card.offsetWidth + 12 : Math.round(el.clientWidth * 0.85)
  el.scrollBy({ left: dir * step, behavior: 'smooth' })
}

let ro: ResizeObserver | null = null

onMounted(() => {
  updateScrollState()
  window.addEventListener('resize', updateScrollState)
  const el = track.value
  if (el) {
    el.addEventListener('scroll', updateScrollState, { passive: true })
    ro = new ResizeObserver(updateScrollState)
    ro.observe(el)
  }
})

onUnmounted(() => {
  window.removeEventListener('resize', updateScrollState)
  track.value?.removeEventListener('scroll', updateScrollState)
  ro?.disconnect()
})
</script>

<template>
  <div class="cat-section">
    <div ref="track" class="cat-grid" role="list">
      <RouterLink
        v-for="(c, i) in tiles"
        :key="c.title"
        :to="c.href"
        class="cat-tile tm-hover-lift"
        role="listitem"
        data-aos="fade-up"
        :data-aos-delay="String(aosDelay(i, 55, 320))"
      >
        <div class="cat-tile__media">
          <img :src="c.image" :alt="c.title" loading="lazy" />
        </div>
        <div class="cat-tile__body">
          <p class="cat-tile__index">{{ String(i + 1).padStart(2, '0') }}</p>
          <h3 class="cat-tile__title">{{ c.title }}</h3>
          <p class="cat-tile__blurb">{{ c.blurb }}</p>
          <span class="cat-tile__cta">
            Shop
            <ArrowUpRight :size="15" :stroke-width="2.25" aria-hidden="true" />
          </span>
        </div>
      </RouterLink>
    </div>

    <div v-if="useRail" class="cat-section__nav" aria-hidden="false">
      <button
        type="button"
        class="cat-section__arrow tm-press"
        aria-label="Scroll categories left"
        :disabled="!canScrollPrev"
        @click="scroll(-1)"
      >
        <ChevronLeft :size="18" :stroke-width="2.25" />
      </button>
      <button
        type="button"
        class="cat-section__arrow tm-press"
        aria-label="Scroll categories right"
        :disabled="!canScrollNext"
        @click="scroll(1)"
      >
        <ChevronRight :size="18" :stroke-width="2.25" />
      </button>
    </div>
  </div>
</template>

<style scoped>
.cat-section {
  width: 100%;
}

/* Desktop & tablet: balanced grid — all categories visible */
.cat-grid {
  display: grid;
  gap: 1rem;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  align-items: stretch;
}

.cat-tile {
  display: flex;
  flex-direction: column;
  min-height: 100%;
  border-radius: var(--radius-lg);
  overflow: hidden;
  background: var(--color-surface-elevated);
  border: 1px solid var(--color-border);
  color: inherit;
  text-decoration: none;
  box-shadow: var(--shadow-sm);
  transition:
    border-color 0.25s ease,
    box-shadow 0.28s ease,
    transform 0.25s var(--ease-out, ease);
}

.cat-tile:hover {
  border-color: rgba(58, 143, 124, 0.35);
  box-shadow: var(--shadow-card);
}

.cat-tile__media {
  position: relative;
  aspect-ratio: 1;
  overflow: hidden;
  background: linear-gradient(145deg, var(--color-page-2), var(--color-border));
}

.cat-tile__media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.5s var(--ease-out, ease);
}

.cat-tile:hover .cat-tile__media img {
  transform: scale(1.05);
}

.cat-tile__body {
  display: flex;
  flex-direction: column;
  flex: 1;
  gap: 0.2rem;
  padding: 0.85rem 0.9rem 1rem;
}

.cat-tile__index {
  margin: 0 0 0.15rem;
  font-size: 0.62rem;
  font-weight: 800;
  letter-spacing: 0.12em;
  color: var(--color-accent);
}

.cat-tile__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: 1.02rem;
  font-weight: 500;
  line-height: 1.2;
  color: var(--color-ink);
}

.cat-tile__blurb {
  margin: 0;
  flex: 1;
  font-size: 0.78rem;
  line-height: 1.45;
  color: var(--color-ink-muted);
}

.cat-tile__cta {
  display: inline-flex;
  align-items: center;
  gap: 0.25rem;
  margin-top: 0.55rem;
  font-size: 0.78rem;
  font-weight: 700;
  color: var(--color-accent);
  transition: gap 0.2s ease;
}

.cat-tile:hover .cat-tile__cta {
  gap: 0.4rem;
}

.cat-section__nav {
  display: none;
}

@media (max-width: 1080px) {
  .cat-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 720px) {
  .cat-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.85rem;
  }
}

@media (max-width: 639px) {
  .cat-grid {
    display: flex;
    gap: 0.75rem;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    scroll-padding-inline: 0.15rem;
    padding-bottom: 0.25rem;
    scrollbar-width: none;
    -ms-overflow-style: none;
  }

  .cat-grid::-webkit-scrollbar {
    display: none;
  }

  .cat-tile {
    flex: 0 0 min(72vw, 14.5rem);
    scroll-snap-align: start;
  }

  .cat-section__nav {
    display: flex;
    justify-content: center;
    gap: 0.65rem;
    margin-top: 0.85rem;
  }

  .cat-section__arrow {
    display: grid;
    place-items: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 999px;
    border: 1px solid var(--color-border);
    background: var(--color-surface-elevated);
    color: var(--color-ink);
    cursor: pointer;
    box-shadow: var(--shadow-sm);
    transition:
      border-color 0.2s ease,
      color 0.2s ease,
      opacity 0.2s ease;
  }

  .cat-section__arrow:hover:not(:disabled) {
    border-color: var(--color-accent);
    color: var(--color-accent);
  }

  .cat-section__arrow:disabled {
    opacity: 0.35;
    cursor: default;
  }
}

@media (prefers-reduced-motion: reduce) {
  .cat-tile:hover .cat-tile__media img {
    transform: none;
  }
}
</style>

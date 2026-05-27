<script setup lang="ts">
import { ArrowUpRight, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { categoryStrip } from '@/data/siteContent'

const track = ref<HTMLElement | null>(null)

function scroll(dir: -1 | 1) {
  const el = track.value
  if (!el) return
  const step = Math.max(220, Math.round(el.clientWidth * 0.72))
  el.scrollBy({ left: dir * step, behavior: 'smooth' })
}
</script>

<template>
  <div class="cat-rail">
    <button
      type="button"
      class="cat-rail__arrow cat-rail__arrow--prev tm-press"
      aria-label="Scroll categories left"
      @click="scroll(-1)"
    >
      <ChevronLeft :size="20" :stroke-width="2.25" />
    </button>

    <div ref="track" class="cat-rail__track" role="list">
      <RouterLink
        v-for="c in categoryStrip"
        :key="c.title"
        :to="c.href"
        class="cat-tile tm-hover-lift"
        role="listitem"
      >
        <div class="cat-tile__media">
          <img :src="c.image" :alt="c.title" loading="lazy" />
          <div class="cat-tile__overlay" aria-hidden="true" />
        </div>
        <div class="cat-tile__content">
          <h3 class="cat-tile__title">{{ c.title }}</h3>
          <p class="cat-tile__blurb">{{ c.blurb }}</p>
          <span class="cat-tile__cta">
            Shop
            <ArrowUpRight :size="15" :stroke-width="2.25" aria-hidden="true" />
          </span>
        </div>
      </RouterLink>
    </div>

    <button
      type="button"
      class="cat-rail__arrow cat-rail__arrow--next tm-press"
      aria-label="Scroll categories right"
      @click="scroll(1)"
    >
      <ChevronRight :size="20" :stroke-width="2.25" />
    </button>
  </div>
</template>

<style scoped>
.cat-rail {
  position: relative;
  margin: 0 -0.35rem;
  padding: 0 2.4rem;
}

.cat-rail__track {
  display: flex;
  gap: 0.85rem;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  scroll-padding-inline: 0.25rem;
  padding: 0.25rem 0.15rem 0.65rem;
  scrollbar-width: none;
  -ms-overflow-style: none;
}

.cat-rail__track::-webkit-scrollbar {
  display: none;
}

.cat-tile {
  flex: 0 0 clamp(9.5rem, 14vw, 11.5rem);
  scroll-snap-align: start;
  display: flex;
  flex-direction: column;
  border-radius: var(--radius-lg);
  overflow: hidden;
  background: var(--color-surface-elevated);
  border: 1px solid var(--color-border);
  color: inherit;
  text-decoration: none;
  box-shadow: var(--shadow-card);
  transition:
    border-color 0.25s ease,
    box-shadow 0.25s ease,
    transform 0.25s var(--ease-out, ease);
}

.cat-tile:hover {
  border-color: rgba(45, 92, 82, 0.28);
  box-shadow: var(--shadow-float);
}

.cat-tile__media {
  position: relative;
  aspect-ratio: 4 / 5;
  overflow: hidden;
  background: linear-gradient(160deg, #ebe6de, #d8d2c8);
}

.cat-tile__media img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 0.45s var(--ease-out, ease);
}

.cat-tile:hover .cat-tile__media img {
  transform: scale(1.06);
}

.cat-tile__overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    180deg,
    transparent 35%,
    rgba(20, 19, 18, 0.55) 100%
  );
  pointer-events: none;
}

.cat-tile__content {
  position: relative;
  margin-top: -2.75rem;
  z-index: 1;
  padding: 0 0.75rem 0.85rem;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}

.cat-tile__title {
  margin: 0;
  font-family: var(--font-display);
  font-size: 0.98rem;
  font-weight: 500;
  color: #fff;
  line-height: 1.2;
  text-shadow: 0 1px 6px rgba(20, 19, 18, 0.35);
}

.cat-tile__blurb {
  margin: 0;
  font-size: 0.68rem;
  font-weight: 600;
  color: rgba(255, 252, 248, 0.88);
  line-height: 1.3;
}

.cat-tile__cta {
  display: inline-flex;
  align-items: center;
  gap: 0.2rem;
  margin-top: 0.35rem;
  padding: 0.28rem 0.55rem;
  width: fit-content;
  border-radius: 999px;
  background: rgba(255, 252, 248, 0.95);
  font-size: 0.72rem;
  font-weight: 700;
  color: var(--color-accent);
  transition:
    gap 0.2s ease,
    background 0.2s ease;
}

.cat-tile:hover .cat-tile__cta {
  gap: 0.35rem;
  background: #fff;
}

.cat-rail__arrow {
  position: absolute;
  top: 42%;
  transform: translateY(-50%);
  z-index: 2;
  display: grid;
  place-items: center;
  width: 2.35rem;
  height: 2.35rem;
  border-radius: 999px;
  border: 1px solid var(--color-border);
  background: var(--color-surface-elevated);
  color: var(--color-ink);
  cursor: pointer;
  box-shadow: var(--shadow-sm);
  transition:
    border-color 0.2s ease,
    color 0.2s ease,
    box-shadow 0.2s ease;
}

.cat-rail__arrow:hover {
  border-color: var(--color-accent);
  color: var(--color-accent);
  box-shadow: var(--shadow-float);
}

.cat-rail__arrow--prev {
  left: 0;
}

.cat-rail__arrow--next {
  right: 0;
}

@media (max-width: 640px) {
  .cat-rail {
    padding: 0 2rem;
  }

  .cat-tile {
    flex-basis: 42vw;
    min-width: 9rem;
  }

  .cat-rail__arrow {
    width: 2rem;
    height: 2rem;
  }
}

@media (prefers-reduced-motion: reduce) {
  .cat-tile:hover .cat-tile__media img {
    transform: none;
  }
}
</style>

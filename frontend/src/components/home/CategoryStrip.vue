<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { categoryStrip } from '@/data/siteContent'

const track = ref<HTMLElement | null>(null)

function scrollByDir(dir: -1 | 1) {
  track.value?.scrollBy({ left: dir * 280, behavior: 'smooth' })
}
</script>

<template>
  <div class="cat-strip">
    <button type="button" class="cat-strip__arrow cat-strip__arrow--prev" @click="scrollByDir(-1)">
      <span aria-hidden="true">‹</span>
      <span class="sr">Previous</span>
    </button>
    <div ref="track" class="cat-strip__track" role="list">
      <div v-for="c in categoryStrip" :key="c.title" class="cat-strip__item" role="listitem">
        <RouterLink :to="c.href" class="cat-strip__card">
          <div class="cat-strip__img-wrap">
            <img :src="c.image" :alt="c.title" width="200" height="200" loading="lazy" />
          </div>
          <div class="cat-strip__label">{{ c.title }}</div>
        </RouterLink>
      </div>
    </div>
    <button type="button" class="cat-strip__arrow cat-strip__arrow--next" @click="scrollByDir(1)">
      <span aria-hidden="true">›</span>
      <span class="sr">Next</span>
    </button>
  </div>
</template>

<style scoped>
.cat-strip {
  position: relative;
  margin: 0 -1.25rem;
  padding: 0 2.5rem;
}

.cat-strip__track {
  display: flex;
  gap: 12px;
  overflow-x: auto;
  scroll-snap-type: x mandatory;
  scroll-behavior: smooth;
  padding-bottom: 8px;
  -webkit-overflow-scrolling: touch;
  scrollbar-width: thin;
}

.cat-strip__track::-webkit-scrollbar {
  height: 6px;
}

.cat-strip__item {
  flex: 0 0 calc(100% / 7 - 10px);
  min-width: 120px;
  max-width: 160px;
  scroll-snap-align: start;
}

@media (max-width: 1024px) {
  .cat-strip__item {
    flex: 0 0 calc(20% - 10px);
    min-width: 100px;
  }
}

@media (max-width: 767px) {
  .cat-strip {
    padding: 0 2rem;
  }
  .cat-strip__item {
    flex: 0 0 calc(50% - 6px);
    min-width: 140px;
  }
}

.cat-strip__card {
  display: block;
  color: inherit;
  text-align: center;
}

.cat-strip__img-wrap {
  border-radius: 50%;
  overflow: hidden;
  aspect-ratio: 1;
  border: 1px solid var(--color-border);
  background: var(--color-page);
}

.cat-strip__img-wrap img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.cat-strip__label {
  margin-top: 10px;
  font-size: 14px;
  font-weight: 500;
  color: var(--color-ink);
}

.cat-strip__arrow {
  position: absolute;
  top: 50%;
  transform: translateY(-50%);
  z-index: 2;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: 1px solid var(--color-border);
  background: var(--color-page);
  cursor: pointer;
  font-size: 22px;
  line-height: 1;
  color: var(--color-ink);
  display: grid;
  place-items: center;
  box-shadow: var(--shadow-sm);
}

.cat-strip__arrow--prev {
  left: 0;
}

.cat-strip__arrow--next {
  right: 0;
}

.cat-strip__arrow:hover {
  border-color: var(--color-accent);
  color: var(--color-accent);
}

.sr {
  position: absolute;
  width: 1px;
  height: 1px;
  padding: 0;
  margin: -1px;
  overflow: hidden;
  clip: rect(0, 0, 0, 0);
  border: 0;
}
</style>

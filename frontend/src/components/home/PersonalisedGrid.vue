<script setup lang="ts">
import { ArrowRight } from 'lucide-vue-next'
import { RouterLink } from 'vue-router'
import { PERSONALISE_PRODUCTS } from '@/data/personalise'
</script>

<template>
  <div class="pers-strip">
    <ul class="pers-strip__list" role="list">
      <li v-for="p in PERSONALISE_PRODUCTS" :key="p.id" class="pers-strip__item">
        <RouterLink
          :to="{ path: '/personalise', query: { type: p.id } }"
          class="pers-card tm-hover-lift"
        >
          <div class="pers-card__media">
            <img :src="p.sampleImage" :alt="p.label" class="pers-card__img" loading="lazy" />
          </div>
          <div class="pers-card__body">
            <h3 class="pers-card__title">{{ p.shortLabel }}</h3>
            <p class="pers-card__text">{{ p.blurb }}</p>
            <span class="pers-card__link">
              Upload & preview
              <ArrowRight :size="15" :stroke-width="2.25" aria-hidden="true" />
            </span>
          </div>
        </RouterLink>
      </li>
    </ul>
  </div>
</template>

<style scoped>
.pers-strip__list {
  display: grid;
  gap: 0.85rem;
  grid-template-columns: repeat(4, 1fr);
  margin: 0;
  padding: 0;
  list-style: none;
}

@media (max-width: 960px) {
  .pers-strip__list {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .pers-strip__list {
    grid-template-columns: 1fr;
  }
}

.pers-card {
  display: flex;
  flex-direction: column;
  height: 100%;
  border: 1px solid var(--color-border);
  border-radius: var(--radius-md);
  overflow: hidden;
  background: var(--color-surface-raised, #fff);
  color: inherit;
  text-decoration: none;
  box-shadow: var(--shadow-card);
  transition:
    border-color 0.25s ease,
    box-shadow 0.25s ease;
}

.pers-card:hover {
  border-color: rgba(45, 92, 82, 0.25);
  box-shadow: var(--shadow-float);
}

.pers-card__media {
  overflow: hidden;
}

.pers-card__img {
  width: 100%;
  aspect-ratio: 1;
  object-fit: cover;
  display: block;
  transition: transform 0.45s var(--ease-out, ease);
}

.pers-card:hover .pers-card__img {
  transform: scale(1.04);
}

@media (prefers-reduced-motion: reduce) {
  .pers-card:hover .pers-card__img {
    transform: none;
  }
}

.pers-card__body {
  padding: 0.85rem 0.9rem 1rem;
  display: flex;
  flex-direction: column;
  flex: 1;
}

.pers-card__title {
  margin: 0 0 0.25rem;
  font-family: var(--font-display);
  font-size: 1rem;
  font-weight: 500;
}

.pers-card__text {
  margin: 0 0 0.65rem;
  font-size: 0.82rem;
  line-height: 1.45;
  color: var(--color-ink-muted);
  flex: 1;
}

.pers-card__link {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.8rem;
  font-weight: 700;
  color: var(--color-accent);
}
</style>

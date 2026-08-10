<script setup lang="ts">

import { ArrowRight } from 'lucide-vue-next'

import { computed } from 'vue'

import { RouterLink } from 'vue-router'

import { storeToRefs } from 'pinia'

import { aosDelay } from '@/lib/aos'
import { homePageImageSrc, useHomePageStore } from '@/stores/homePage'



const homePage = useHomePageStore()

const { personaliseCards } = storeToRefs(homePage)



const cards = computed(() =>

  personaliseCards.value.map((p) => ({

    ...p,

    sampleImage: homePageImageSrc(p.image),

  })),

)

</script>



<template>

  <div class="pers-strip">

    <ul class="pers-strip__list" role="list">

      <li
        v-for="(p, i) in cards"
        :key="p.id"
        class="pers-strip__item"
        data-aos="fade-up"
        :data-aos-delay="String(aosDelay(i, 75, 300))"
      >

        <RouterLink

          :to="{ path: '/personalise' }"

          class="pers-card tm-hover-lift"

        >

          <div class="pers-card__media">

            <img :src="p.sampleImage" :alt="p.shortLabel" class="pers-card__img" loading="lazy" />

          </div>

          <div class="pers-card__body">

            <h3 class="pers-card__title">{{ p.shortLabel }}</h3>

            <p class="pers-card__text">{{ p.blurb }}</p>

            <span class="pers-card__cta">

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

  border: 1px solid var(--tm-border);

  border-radius: var(--tm-radius-md);

  overflow: hidden;

  background: var(--tm-surface-2);

  color: inherit;

  text-decoration: none;

  box-shadow: var(--tm-shadow-sm);

  transition:

    border-color 0.25s ease,

    box-shadow 0.25s ease;

}



.pers-card:hover {

  border-color: var(--tm-accent-soft);

  box-shadow: var(--tm-shadow-md);

}



.pers-card__media {

  overflow: hidden;

  background: var(--tm-page-2);

}



.pers-card__img {

  width: 100%;

  aspect-ratio: 1;

  object-fit: cover;

  display: block;

  transition: transform 0.45s var(--tm-ease);

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

  align-items: stretch;

}



.pers-card__title {

  margin: 0 0 0.25rem;

  font-family: var(--font-display);

  font-size: 1rem;

  font-weight: 500;

  color: var(--tm-ink);

}



.pers-card__text {

  margin: 0 0 0.75rem;

  font-size: 0.82rem;

  line-height: 1.45;

  color: var(--tm-ink-muted);

  flex: 1;

}



.pers-card__cta {

  display: inline-flex;

  align-items: center;

  justify-content: center;

  align-self: center;

  gap: 0.35rem;

  min-height: 2.25rem;

  padding: 0 0.85rem;

  border-radius: var(--tm-radius-full);

  background: var(--tm-gradient);

  color: var(--tm-on-accent);

  font-size: 0.78rem;

  font-weight: 700;

  box-shadow: var(--tm-shadow-accent);

  transition: background 0.2s ease;

}



.pers-card:hover .pers-card__cta {

  background: var(--tm-gradient-hover);

}

</style>


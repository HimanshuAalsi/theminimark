<script setup lang="ts">
import { ArrowRight, Sparkles } from 'lucide-vue-next'
import { RouterLink } from 'vue-router'
import type { HomeSegmentCta } from '@/types/homePageLayout'

defineProps<{
  eyebrow?: string
  title?: string
  description?: string
  align?: 'left' | 'center'
  titleSize?: 'sm' | 'md' | 'lg' | 'xl'
  cta?: HomeSegmentCta
  split?: boolean
}>()
</script>

<template>
  <header
    class="section-head"
    :class="{
      'section-head--center': align === 'center',
      'section-head--split': split && cta,
    }"
    data-aos="fade-up"
  >
    <div class="section-head__text">
      <p v-if="eyebrow" class="section-eyebrow">{{ eyebrow }}</p>
      <h2 v-if="title" class="section-title" :class="titleSize ? `section-title--${titleSize}` : ''">{{ title }}</h2>
      <p v-if="description" class="section-desc">{{ description }}</p>
    </div>
    <RouterLink
      v-if="cta?.label && cta?.to"
      :to="cta.to"
      class="home-section-cta tm-hover-lift"
    >
      <Sparkles v-if="cta.to.includes('personalise')" :size="18" :stroke-width="2.25" aria-hidden="true" />
      <span>{{ cta.label }}</span>
      <ArrowRight :size="17" :stroke-width="2.25" aria-hidden="true" />
    </RouterLink>
  </header>
</template>

<style scoped>
.section-head--center {
  text-align: center;
  align-items: center;
}

.section-head--center .section-head__text {
  max-width: 40rem;
  margin-inline: auto;
}

.section-head--split {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1.5rem 2rem;
}

.section-head__text {
  flex: 1;
  min-width: 0;
  max-width: 38rem;
}

.section-title--sm {
  font-size: clamp(1.25rem, 2vw, 1.5rem);
}

.section-title--md {
  font-size: clamp(1.5rem, 2.5vw, 1.875rem);
}

.section-title--lg {
  font-size: clamp(1.75rem, 3vw, 2.25rem);
}

.section-title--xl {
  font-size: clamp(2rem, 3.5vw, 2.75rem);
}

.home-section-cta {
  flex-shrink: 0;
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  min-height: var(--tap-min);
  padding: 0.65rem 1.2rem;
  border-radius: var(--tm-radius-full);
  font-size: 0.88rem;
  font-weight: 700;
  text-decoration: none;
  white-space: nowrap;
  background: var(--tm-ink);
  color: var(--tm-surface);
}

@media (max-width: 720px) {
  .section-head--split {
    flex-direction: column;
    align-items: flex-start;
  }

  .home-section-cta {
    white-space: normal;
  }
}
</style>

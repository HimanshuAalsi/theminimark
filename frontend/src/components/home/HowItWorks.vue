<script setup lang="ts">
import { ArrowRight, Grid3x3, Package, ShoppingBag } from 'lucide-vue-next'
import { RouterLink } from 'vue-router'
import { storeToRefs } from 'pinia'
import { aosDelay } from '@/lib/aos'
import { useHomePageStore } from '@/stores/homePage'

const homePage = useHomePageStore()
const { howItWorksIntro, howItWorksSteps } = storeToRefs(homePage)

const stepIcons = [Grid3x3, ShoppingBag, Package] as const
</script>

<template>
  <section class="hiw tm-section">
    <div class="tm-container">
      <header class="section-head section-head--center">
        <p class="section-eyebrow">{{ howItWorksIntro.eyebrow }}</p>
        <h2 class="section-title">{{ howItWorksIntro.title }}</h2>
        <p class="section-desc">{{ howItWorksIntro.description }}</p>
      </header>
      <ol class="hiw__steps">
        <li
          v-for="(s, i) in howItWorksSteps"
          :key="s.step"
          class="hiw__step tm-hover-lift"
          data-aos="fade-up"
          :data-aos-delay="String(aosDelay(i, 90, 270))"
        >
          <div class="hiw__icon-wrap" aria-hidden="true">
            <component :is="stepIcons[i] ?? Grid3x3" :size="24" :stroke-width="2" />
          </div>
          <span class="hiw__num">{{ s.step }}</span>
          <h3 class="hiw__title">{{ s.title }}</h3>
          <p class="hiw__text">{{ s.text }}</p>
          <RouterLink :to="s.ctaTo" class="hiw__cta">
            {{ s.ctaLabel }}
            <ArrowRight :size="16" :stroke-width="2.25" aria-hidden="true" />
          </RouterLink>
        </li>
      </ol>
    </div>
  </section>
</template>

<style scoped>
.hiw {
  border-top: 1px solid var(--tm-border);
  border-bottom: 1px solid var(--tm-border);
}

.hiw__steps {
  list-style: none;
  margin: 0;
  padding: 0;
  display: grid;
  gap: 1.35rem;
  grid-template-columns: repeat(3, 1fr);
}

@media (max-width: 768px) {
  .hiw__steps {
    grid-template-columns: 1fr;
  }
}

.hiw__step {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  padding: 1.5rem 1.35rem 1.35rem;
  border-radius: var(--tm-radius-lg);
  border: 1px solid var(--tm-border);
  background: var(--tm-surface-2);
  position: relative;
  transition:
    border-color 0.25s ease,
    box-shadow 0.25s ease;
}

.hiw__step:hover {
  border-color: var(--tm-accent-soft);
  box-shadow: var(--tm-shadow-md);
}

.hiw__icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: var(--tm-radius-sm);
  background: var(--tm-page);
  color: var(--tm-accent);
  display: grid;
  place-items: center;
  margin-bottom: 0.75rem;
  border: 1px solid var(--tm-border);
  transition:
    transform 0.3s var(--ease-spring, ease),
    box-shadow 0.25s ease;
}

.hiw__step:hover .hiw__icon-wrap {
  transform: scale(1.05) rotate(-2deg);
  box-shadow: var(--tm-shadow-sm);
}

.hiw__num {
  position: absolute;
  top: 1rem;
  right: 1rem;
  display: inline-flex;
  width: 1.75rem;
  height: 1.75rem;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  background: var(--tm-gradient);
  color: var(--tm-on-accent);
  font-size: 0.8rem;
  font-weight: 800;
}

.hiw__title {
  margin: 0 0 0.5rem;
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--tm-ink);
}

.hiw__text {
  margin: 0 0 1rem;
  font-size: 0.92rem;
  color: var(--tm-ink-muted);
  line-height: 1.55;
  flex: 1;
}

.hiw__cta {
  margin-top: auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.35rem;
  min-height: var(--tm-tap);
  padding: 0 1.15rem;
  border-radius: var(--tm-radius-full);
  background: var(--tm-gradient);
  color: var(--tm-on-accent) !important;
  font-size: 0.88rem;
  font-weight: 700;
  text-decoration: none;
  box-shadow: var(--tm-shadow-accent);
  transition:
    background 0.2s ease,
    transform 0.15s ease;
}

.hiw__cta:hover {
  background: var(--tm-gradient-hover);
  transform: translateY(-1px);
}

@media (prefers-reduced-motion: reduce) {
  .hiw__step:hover .hiw__icon-wrap {
    transform: none;
  }

  .hiw__cta:hover {
    transform: none;
  }
}
</style>
